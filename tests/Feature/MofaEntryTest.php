<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\HrProfile;
use App\Models\MofaEntry;
use App\Models\Passport;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** MOFA summary: CRUD, computed fields, validation, tenancy, HR search and real PDF output. */
class MofaEntryTest extends TestCase
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
        return array_merge(['full_name' => 'Test Passenger', 'father_name' => 'Test Father', 'mother_name' => 'Test Mother', 'passport_number' => 'AB1234567', 'date_of_birth' => '1995-12-31', 'issue_date' => '2025-01-01', 'expiry_date' => '2030-01-01', 'mofa_expiry_date' => '2026-12-01', 'mofa_number' => 'MOFA-100', 'mofa_date' => '2026-09-25', 'reference' => 'Agent'], $overrides);
    }

    private function entry(array $overrides = []): MofaEntry
    {
        return MofaEntry::create($this->payload($overrides) + ['agency_id' => $this->agency->id]);
    }

    public function test_crud_and_computed_values(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 12:00:00'));
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload(['age' => 99, 'left_day' => 999]))->assertOk();
        $entry = MofaEntry::firstOrFail();
        $this->assertSame(today()->year - 1995, $entry->age);
        $this->assertSame(67, $entry->left_day); // 2026-09-25 → 2026-12-01
        $this->assertSame($this->staff->id, $entry->created_by);
        $this->get(route('erp.mofa'))->assertOk()->assertSee('Test Passenger')->assertSee('mofa-config', false);
        $this->get(route('erp.mofa.show', $entry))->assertOk();
        $this->getJson(route('erp.mofa.show', $entry))->assertJsonPath('mofa_expiry_date', '2026-12-01');
        $this->putJson(route('erp.mofa.update', $entry), $this->payload(['full_name' => 'Updated']))->assertOk();
        $this->assertSame('Updated', $entry->fresh()->full_name);
        $this->delete(route('erp.mofa.destroy', $entry))->assertRedirect();
        $this->assertSoftDeleted($entry);
    }

    public function test_validation_and_unique_passports_are_agency_scoped(): void
    {
        $this->entry();
        $this->actingAs($this->admin)->postJson(route('erp.mofa.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('passport_number');
        $this->postJson(route('erp.mofa.store'), $this->payload(['passport_number' => 'NEW', 'expiry_date' => '2025-01-01', 'date_of_birth' => today()->format('Y-m-d'), 'mofa_expiry_date' => '2026-08-01']))->assertUnprocessable()->assertJsonValidationErrors(['expiry_date', 'date_of_birth', 'mofa_expiry_date']);
        $this->actingAs($this->otherAdmin)->postJson(route('erp.mofa.store'), $this->payload())->assertOk();
    }

    public function test_mofa_expiry_is_exactly_ninety_days_after_mofa_date(): void
    {
        $this->assertSame(90, MofaEntry::MOFA_VALIDITY_DAYS);
        foreach (['2026-09-12' => '2026-12-11', '2026-12-09' => '2027-03-09', '2028-01-15' => '2028-04-14', '2027-12-31' => '2028-03-30'] as $mofaDate => $expiry) {
            $this->assertSame($expiry, MofaEntry::mofaExpiryFor($mofaDate), $mofaDate);
        }
    }

    public function test_saves_without_mofa_issue_date_and_server_fills_missing_expiry(): void
    {
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload(['mofa_date' => '2026-09-12', 'mofa_expiry_date' => '']))->assertOk();
        $entry = MofaEntry::firstOrFail();
        $this->assertNull($entry->mofa_issue_date);
        $this->assertSame('2026-12-11', $entry->mofa_expiry_date->format('Y-m-d'));

        // Edit (also without MOFA Issue Date): a provided expiry is kept, not recalculated.
        $this->putJson(route('erp.mofa.update', $entry), $this->payload(['mofa_date' => '2026-09-12', 'mofa_expiry_date' => '2026-12-20']))->assertOk();
        $this->assertSame('2026-12-20', $entry->fresh()->mofa_expiry_date->format('Y-m-d'));

        // Neither MOFA Date nor Expiry → still rejected, as before.
        $this->postJson(route('erp.mofa.store'), $this->payload(['passport_number' => 'NODATES', 'mofa_date' => '', 'mofa_expiry_date' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors('mofa_expiry_date');
    }

    public function test_unchanged_legacy_edit_saves_and_keeps_stored_dates(): void
    {
        // Legacy row whose expiry is not after its MOFA Date (allowed by the old rule).
        $legacy = $this->entry(['mofa_issue_date' => '2026-08-01', 'mofa_date' => '2026-09-25', 'mofa_expiry_date' => '2026-09-01']);
        $this->actingAs($this->admin);
        $loaded = $this->getJson(route('erp.mofa.show', $legacy))->assertJsonPath('mofa_issue_date', '2026-08-01')->json();

        $this->putJson(route('erp.mofa.update', $legacy), array_merge($loaded, ['remarks' => 'touched']))->assertOk();
        $fresh = $legacy->fresh();
        $this->assertSame('2026-08-01', $fresh->mofa_issue_date->format('Y-m-d'));
        $this->assertSame('2026-09-01', $fresh->mofa_expiry_date->format('Y-m-d'));

        // Changing either MOFA date re-applies "expiry after MOFA Date".
        $this->putJson(route('erp.mofa.update', $legacy), array_merge($loaded, ['mofa_date' => '2026-09-26']))
            ->assertUnprocessable()->assertJsonValidationErrors('mofa_expiry_date');
    }

    public function test_left_day_counts_down_from_today_in_dhaka(): void
    {
        $entry = $this->entry(['mofa_expiry_date' => '2026-12-11']);
        // 2026-09-24 20:00 UTC is already 2026-09-25 02:00 in Dhaka.
        $this->travelTo(Carbon::parse('2026-09-24 20:00:00', 'UTC'));
        $this->assertSame(77, $entry->left_day);
        $this->travelTo(Carbon::parse('2026-12-13 12:00:00', 'UTC'));
        $this->assertSame(-2, $entry->left_day);
        $this->assertNull($this->entry(['passport_number' => 'NOEXP', 'mofa_expiry_date' => null])->left_day);
    }

    public function test_issue_date_column_falls_back_to_mofa_date_for_display_only(): void
    {
        $entry = $this->entry(['mofa_issue_date' => null, 'mofa_date' => '2026-09-12']);
        $this->actingAs($this->admin);
        $this->assertSame('12-Sep-2026', \App\Http\Controllers\Erp\MofaController::value($entry, 'mofa_issue_date'));
        $this->assertStringContainsString('12-Sep-2026', $this->get(route('erp.mofa.export'))->streamedContent());
        // The edit form must not receive (and later save) the fallback.
        $this->getJson(route('erp.mofa.show', $entry))->assertJsonPath('mofa_issue_date', null);
        $this->assertNull($entry->fresh()->mofa_issue_date);
    }

    public function test_cross_agency_access_is_forbidden(): void
    {
        $e = $this->entry();
        $this->actingAs($this->otherAdmin);
        foreach (['show', 'edit', 'print-pdf'] as $action) {
            $this->get(route('erp.mofa.'.$action, $e))->assertForbidden();
        }
        $this->putJson(route('erp.mofa.update', $e), $this->payload())->assertForbidden();
        $this->delete(route('erp.mofa.destroy', $e))->assertForbidden();
    }

    public function test_status_boundaries_and_filters(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 12:00:00'));
        foreach (['2026-09-24' => 'expired', '2026-09-25' => 'expiring', '2026-10-24' => 'expiring', '2026-10-25' => 'active'] as $date => $status) {
            $e = $this->entry(['passport_number' => $date, 'mofa_expiry_date' => $date]);
            $this->assertSame($status, $e->status);
        }
        $this->assertSame('processing', $this->entry(['passport_number' => 'LEGACY', 'mofa_expiry_date' => null])->status);
        $this->actingAs($this->admin)->get(route('erp.mofa', ['status' => 'expired']))->assertOk()->assertViewHas('entries', fn ($entries) => $entries->total() === 1);
        $this->get(route('erp.mofa', ['q' => 'LEGACY']))->assertViewHas('entries', fn ($entries) => $entries->total() === 1);
    }

    public function test_date_range_accepts_either_boundary_and_rejects_reversed_range(): void
    {
        $this->entry();
        $this->actingAs($this->admin)->get(route('erp.mofa', ['to' => '2026-09-25']))->assertOk()->assertViewHas('entries', fn ($entries) => $entries->total() === 1);
        $this->get(route('erp.mofa', ['from' => '2026-09-26']))->assertOk()->assertViewHas('entries', fn ($entries) => $entries->total() === 0);
        $this->get(route('erp.mofa', ['from' => '2026-09-26', 'to' => '2026-09-25']))->assertSessionHasErrors('to');
        $this->postJson(route('erp.mofa.store'), $this->payload(['passport_number' => 'DDMM123', 'date_of_birth' => '31/12/1995']))->assertOk();
    }

    public function test_hr_search_and_linking(): void
    {
        $hr = HrProfile::create(['agency_id' => $this->agency->id, 'full_name_en' => 'HR Person', 'father_name' => 'Father', 'mother_name' => 'Mother', 'nationality' => 'Bangladeshi', 'date_of_birth' => '1995-01-01', 'gender' => 'male']);
        Passport::create(['hr_profile_id' => $hr->id, 'passport_number' => 'AB1234567']);
        $this->actingAs($this->admin)->getJson(route('erp.mofa.hr-search', ['q' => 'AB123']))->assertOk()->assertJsonPath('0.mother_name', 'Mother');
        $this->postJson(route('erp.mofa.store'), $this->payload())->assertOk();
        $this->assertSame($hr->id, MofaEntry::first()->hr_profile_id);
        $this->actingAs($this->otherAdmin)->getJson(route('erp.mofa.hr-search', ['q' => 'AB123']))->assertExactJson([]);
    }

    public function test_legacy_import_preserves_format_and_rejects_duplicate_batch_atomically(): void
    {
        Storage::fake('local');
        $headers = "mofa_date,mofa_number,visa_serial,full_name,passport_no,reference_name,payment_method,whatsapp_number,payment_note\n";
        $row = "2026-09-25,M100,V100,Legacy Passenger,LEGACY123,Agent,no_payment,,\n";
        $this->actingAs($this->admin);
        $file = UploadedFile::fake()->createWithContent('mofa.csv', $headers.$row.$row);
        $this->post(route('erp.mofa.import.preview'), ['file' => $file])->assertOk();
        $this->post(route('erp.mofa.import'))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('mofa_entries', 0);
        $file = UploadedFile::fake()->createWithContent('mofa.csv', $headers.$row);
        $this->post(route('erp.mofa.import.preview'), ['file' => $file])->assertOk();
        $this->post(route('erp.mofa.import'))->assertRedirect(route('erp.mofa'));
        $this->assertSame('processing', MofaEntry::firstOrFail()->status);
    }

    public function test_pdf_layouts_and_csv(): void
    {
        $entry = $this->entry(['remarks' => '=HYPERLINK("bad")']);
        $this->actingAs($this->admin);
        foreach (['landscape', 'portrait'] as $layout) {
            $this->get(route('erp.mofa.print-pdf', [$entry, 'layout' => $layout, 'preview' => 1]))->assertOk()->assertSee('MOFA Summary');
            $response = $this->get(route('erp.mofa.print-pdf', [$entry, 'layout' => $layout]));
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
        $response = $this->get(route('erp.mofa.export'));
        $response->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
        $this->get(route('erp.mofa.print', ['layout' => 'bad']))->assertSessionHasErrors('layout');
    }
}
