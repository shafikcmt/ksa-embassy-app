<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\BmetEntry;
use App\Models\HrProfile;
use App\Models\ManpowerCompletion;
use App\Models\Passport;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Visa;
use Carbon\Carbon;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/** BMET CRUD, operational validity, tenant isolation, import compatibility and PDF output. */
class BmetEntryTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Agency $other;

    private User $admin;

    private User $staff;

    private User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-25 12:00:00'));
        $this->seed(RolesPermissionsSeeder::class);

        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = $this->makeAgency('alpha', $plan);
        $this->other = $this->makeAgency('beta', $plan);

        $this->admin = $this->makeUser($this->agency, 'admin@alpha.test', 'agency_admin');
        $this->staff = $this->makeUser($this->agency, 'staff@alpha.test', 'agency_staff');
        $this->staff->givePermissionTo('access_erp');
        $this->otherAdmin = $this->makeUser($this->other, 'admin@beta.test', 'agency_admin');
    }

    private function makeAgency(string $slug, Plan $plan): Agency
    {
        $agency = Agency::create(['name' => ucfirst($slug).' Agency', 'slug' => $slug, 'status' => 'active']);
        Subscription::create([
            'agency_id' => $agency->id, 'plan_id' => $plan->id,
            'start_date' => now()->subDay(), 'end_date' => now()->addYear(),
            'status' => 'active', 'payment_status' => 'paid', 'amount' => 0,
        ]);

        return $agency;
    }

    private function makeUser(Agency $agency, string $email, string $role): User
    {
        $user = User::create([
            'name' => $email, 'email' => $email, 'password' => Hash::make('secret1234'),
            'agency_id' => $agency->id, 'is_super_admin' => false, 'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['full_name' => 'BMET Passenger', 'father_name' => 'Passenger Father', 'passport_number' => 'AB1234567', 'visa_number' => 'VISA-101', 'id_number' => 'ID-201', 'ec_number' => 'EC-301', 'ec_date' => '2026-09-25', 'reference' => 'Recruitment Desk', 'remarks' => 'Certificate verified', 'status' => 'auto', 'agent_id' => null], $overrides);
    }

    private function entry(array $overrides = []): BmetEntry
    {
        return BmetEntry::create($this->payload(array_merge(['status' => 'cleared'], $overrides)) + ['agency_id' => $this->agency->id]);
    }

    public function test_staff_can_create_view_edit_and_soft_delete(): void
    {
        $this->actingAs($this->staff)->get(route('erp.bmet.create'))->assertRedirect(route('erp.bmet.index', ['add' => 1]));
        $this->get(route('erp.bmet.index'))->assertOk()->assertSee('No BMET entries yet');
        $this->postJson(route('erp.bmet.store'), $this->payload(['agency_id' => $this->other->id, 'created_by' => $this->otherAdmin->id]))->assertOk();
        $entry = BmetEntry::firstOrFail();
        $this->assertSame($this->agency->id, $entry->agency_id);
        $this->assertSame($this->staff->id, $entry->created_by);
        $this->assertSame('cleared', $entry->effective_status);
        $this->get(route('erp.bmet.index'))->assertOk()->assertSee('BMET Passenger')->assertSee('bmet-config', false);
        $this->get(route('erp.bmet.show', $entry))->assertOk()->assertSee('EC-301');
        $this->getJson(route('erp.bmet.show', $entry))->assertJsonPath('ec_date', '2026-09-25');
        $this->get(route('erp.bmet.edit', $entry))->assertRedirect(route('erp.bmet.index', ['edit' => $entry->id]));
        $this->putJson(route('erp.bmet.update', $entry), $this->payload(['full_name' => 'Edited Passenger', 'status' => 'hold']))->assertOk();
        $this->assertSame('hold', $entry->fresh()->effective_status);
        $this->delete(route('erp.bmet.destroy', $entry))->assertRedirect(route('erp.bmet.index'));
        $this->assertSoftDeleted($entry);
        $this->get(route('erp.bmet.show', $entry))->assertNotFound();
    }

    public function test_required_dates_lengths_and_date_formats(): void
    {
        $this->actingAs($this->admin)->postJson(route('erp.bmet.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['full_name', 'passport_number', 'ec_date']);
        $this->postJson(route('erp.bmet.store'), $this->payload(['passport_number' => ['unexpected']]))->assertUnprocessable()->assertJsonValidationErrors('passport_number');
        $this->postJson(route('erp.bmet.store'), $this->payload(['ec_date' => '2026-09-26', 'full_name' => str_repeat('x', 101)]))->assertUnprocessable()->assertJsonValidationErrors(['ec_date', 'full_name']);
        $this->postJson(route('erp.bmet.store'), $this->payload(['ec_date' => '31/02/2026']))->assertUnprocessable()->assertJsonValidationErrors('ec_date');
        $this->postJson(route('erp.bmet.store'), $this->payload(['ec_date' => '25/09/2026', 'passport_number' => ' ab1234567 ']))->assertOk();
        $this->assertSame('AB1234567', BmetEntry::firstOrFail()->passport_number);
    }

    public function test_unique_passports_are_scoped_and_deleted_passports_can_be_reused(): void
    {
        $entry = $this->entry();
        $this->actingAs($this->admin)->postJson(route('erp.bmet.store'), $this->payload())->assertJsonValidationErrors('passport_number');
        $this->actingAs($this->otherAdmin)->postJson(route('erp.bmet.store'), $this->payload())->assertOk();
        $entry->delete();
        $this->actingAs($this->admin)->postJson(route('erp.bmet.store'), $this->payload())->assertOk();
    }

    public function test_cross_tenant_reads_writes_and_agent_injection_are_rejected(): void
    {
        $entry = $this->entry();
        $this->actingAs($this->otherAdmin);
        foreach (['show', 'edit', 'print-pdf'] as $action) {
            $this->get(route('erp.bmet.'.$action, $entry))->assertForbidden();
        }
        $this->putJson(route('erp.bmet.update', $entry), $this->payload())->assertForbidden();
        $this->delete(route('erp.bmet.destroy', $entry))->assertForbidden();
        $foreign = Agent::create(['agency_id' => $this->agency->id, 'name' => 'Foreign Agent', 'phone' => '01700000001', 'address' => 'Dhaka', 'status' => 'active']);
        $this->postJson(route('erp.bmet.store'), $this->payload(['agent_id' => $foreign->id]))->assertJsonValidationErrors('agent_id');
        $this->get(route('erp.bmet.index'))->assertDontSee('BMET Passenger');
        $this->assertStringNotContainsString('BMET Passenger', $this->get(route('erp.bmet.export'))->streamedContent());
    }

    public function test_status_boundaries_and_leap_year_expiry(): void
    {
        $e = $this->entry(['ec_date' => '2025-09-24']);
        $this->assertSame('expired', $e->effective_status);
        $e->update(['ec_date' => '2025-09-25']);
        $this->assertSame('cleared', $e->effective_status);
        $this->assertTrue($e->expiring_soon);
        $e->update(['ec_date' => '2025-10-25']);
        $this->assertFalse($e->expiring_soon);
        $e->update(['status' => 'hold', 'ec_date' => '2020-01-01']);
        $this->assertSame('hold', $e->effective_status);
        $e->update(['status' => 'pending']);
        $this->assertSame('pending', $e->effective_status);
        $e->update(['ec_date' => '2024-02-29']);
        $this->assertSame('2025-02-28', $e->ec_expiry_date->toDateString());
    }

    public function test_automatic_pending_and_manual_hold_are_supported(): void
    {
        $this->actingAs($this->admin)->postJson(route('erp.bmet.store'), $this->payload(['ec_number' => null]))->assertOk();
        $entry = BmetEntry::firstOrFail();
        $this->assertSame('pending', $entry->status);
        $this->putJson(route('erp.bmet.update', $entry), $this->payload(['status' => 'hold']))->assertOk();
        $this->assertSame('hold', $entry->fresh()->status);
    }

    public function test_hr_lookup_returns_visa_and_sponsor_id_and_links_only_own_profile(): void
    {
        $hr = HrProfile::create(['agency_id' => $this->agency->id, 'full_name_en' => 'HR Passenger', 'father_name' => 'HR Father', 'nationality' => 'Bangladeshi', 'date_of_birth' => '1995-01-01', 'gender' => 'male']);
        Passport::create(['hr_profile_id' => $hr->id, 'passport_number' => 'AB1234567']);
        Visa::create(['hr_profile_id' => $hr->id, 'visa_number' => 'HR-VISA', 'sponsor_id' => 'HR-ID']);
        $this->actingAs($this->admin)->getJson(route('erp.bmet.hr-search', ['q' => 'AB123']))->assertOk()->assertJsonPath('0.visa_number', 'HR-VISA')->assertJsonPath('0.id_number', 'HR-ID');
        $this->postJson(route('erp.bmet.store'), $this->payload())->assertOk();
        $this->assertSame($hr->id, BmetEntry::firstOrFail()->hr_profile_id);
        $this->actingAs($this->otherAdmin)->getJson(route('erp.bmet.hr-search', ['q' => 'AB123']))->assertExactJson([]);
        $this->postJson(route('erp.bmet.store'), $this->payload(['hr_profile_id' => $hr->id]))->assertOk();
        $this->assertNull(BmetEntry::forAgency($this->other->id)->first()->hr_profile_id);
    }

    public function test_filters_sort_pagination_and_stats_share_effective_status(): void
    {
        $agent = Agent::create(['agency_id' => $this->agency->id, 'name' => 'Local Agent', 'phone' => '01700000002', 'address' => 'Dhaka', 'status' => 'active']);
        $this->entry(['ec_date' => '2025-09-24', 'passport_number' => 'EXPIRED', 'full_name' => 'Zed', 'agent_id' => $agent->id]);
        $this->entry(['passport_number' => 'HOLD', 'full_name' => 'Amy', 'status' => 'hold']);
        $this->actingAs($this->admin)->get(route('erp.bmet.index', ['status' => 'expired', 'agent_id' => $agent->id]))->assertOk()->assertViewHas('entries', fn ($e) => $e->total() === 1)->assertViewHas('stats', fn ($s) => $s['expired'] === 1 && $s['hold'] === 1);
        $this->get(route('erp.bmet.index', ['q' => 'EXPIRED']))->assertViewHas('entries', fn ($e) => $e->total() === 1);
        $this->get(route('erp.bmet.index', ['q' => 'EXPIRE']))->assertViewHas('entries', fn ($e) => $e->total() === 0);
        $this->get(route('erp.bmet.index', ['sort' => 'name_asc']))->assertViewHas('entries', fn ($e) => $e->first()->full_name === 'Amy');
        $this->get(route('erp.bmet.index', ['sort' => 'status_asc']))->assertViewHas('entries', fn ($e) => $e->first()->effective_status === 'expired');
        for ($i = 0; $i < 21; $i++) {
            $this->entry(['passport_number' => 'PAGE'.$i]);
        }
        $this->get(route('erp.bmet.index', ['page' => 2]))->assertViewHas('entries', fn ($e) => $e->count() === 3 && $e->total() === 23);
    }

    public function test_date_filters_allow_either_boundary_and_reject_reversed_ranges(): void
    {
        $this->entry();
        $this->actingAs($this->admin);
        $this->get(route('erp.bmet.index', ['to' => '2026-09-25']))->assertOk()->assertViewHas('entries', fn ($e) => $e->total() === 1);
        $this->get(route('erp.bmet.index', ['from' => '2026-09-26']))->assertOk()->assertViewHas('entries', fn ($e) => $e->total() === 0);
        $this->get(route('erp.bmet.index', ['from' => '2026-09-26', 'to' => '2026-09-25']))->assertSessionHasErrors('to');
    }

    public function test_legacy_manpower_records_routes_and_soft_deletes_stay_connected(): void
    {
        $old = ManpowerCompletion::create(['agency_id' => $this->agency->id, 'customer_name' => 'Legacy Passenger', 'passport_no' => 'LEGACY123', 'completed_date' => '2026-09-20', 'ec_number' => 'OLD-EC']);
        $this->assertSame('Legacy Passenger', BmetEntry::findOrFail($old->id)->full_name);
        $this->actingAs($this->admin)->get(route('erp.manpower'))->assertOk()->assertSee('Legacy Passenger');
        $this->delete(route('erp.bmet.destroy', $old->id))->assertRedirect();
        $this->assertNull(ManpowerCompletion::find($old->id));
    }

    public function test_legacy_csv_import_and_duplicate_batch_rollback(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);
        $head = "completed_date,customer_name,passport_no,agent\n";
        $row = "2026-09-20,Legacy CSV,IMPORT123,\n";
        $this->post(route('erp.manpower.import.preview'), ['file' => UploadedFile::fake()->createWithContent('bmet.csv', $head.$row.$row)])->assertOk();
        $this->post(route('erp.manpower.import'))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('manpower_completions', 0);
        $this->post(route('erp.manpower.import.preview'), ['file' => UploadedFile::fake()->createWithContent('bmet.csv', $head.$row)])->assertOk();
        $this->post(route('erp.manpower.import'))->assertRedirect();
        $this->assertSame('Legacy CSV', BmetEntry::firstOrFail()->full_name);
    }

    public function test_pdf_and_csv_contain_all_reference_columns_and_respect_filters(): void
    {
        $entry = $this->entry(['remarks' => '=UNSAFE()']);
        $this->actingAs($this->admin);
        $this->get(route('erp.bmet.print-pdf', [$entry, 'preview' => 1]))->assertOk()->assertSee('BMET Clearance Summary')->assertSee('Remarks');
        $response = $this->get(route('erp.bmet.print-pdf', [$entry, 'download' => 1]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $pdf = (new Parser)->parseContent($response->getContent());
        $this->assertCount(1, $pdf->getPages());
        $this->assertStringContainsString('BMET Passenger', $pdf->getText());
        $box = $pdf->getPages()[0]->getDetails()['MediaBox'];
        $this->assertGreaterThan($box[3], $box[2]);
        $csv = $this->get(route('erp.bmet.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=UNSAFE()", $csv);
        $csv = $this->get(route('erp.bmet.export', ['status' => 'hold']))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('BMET Passenger', $csv);
        $this->get(route('erp.bmet.print', ['status' => 'hold', 'preview' => 1]))->assertOk()->assertDontSee('BMET Passenger');
        $this->get(route('erp.bmet.print'))->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8')->assertSee('window.print()', false);
    }

    public function test_guests_and_expired_subscription_cannot_create(): void
    {
        $this->get(route('erp.bmet.index'))->assertRedirect(route('login'));
        Subscription::where('agency_id',$this->agency->id)->update(['end_date' => today()->subDay()]);
        $this->actingAs($this->staff)->postJson(route('erp.bmet.store'),$this->payload())->assertForbidden();
    }
}
