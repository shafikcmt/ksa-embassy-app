<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\HrProfile;
use App\Models\MofaEntry;
use App\Models\Passport;
use App\Models\Plan;
use App\Models\Stamping;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VisaStamping;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ERP Visa Stamping — computed age / left day, validation (required, unique
 * passport per agency, no future stamping date), modal JSON saves, HR + MOFA
 * auto-fill lookups, filters / sort / stats, soft delete + admin restore,
 * tenancy, legacy /erp/stamping redirect, and the summary print + CSV.
 * MySQL only (see project test-DB note).
 */
class VisaStampingTest extends TestCase
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
        $this->other  = $this->makeAgency('beta', $plan);

        $this->admin      = $this->makeUser($this->agency, 'admin@alpha.test', 'agency_admin');
        $this->staff      = $this->makeUser($this->agency, 'staff@alpha.test', 'agency_staff');
        $this->staff->givePermissionTo('access_erp');
        $this->otherAdmin = $this->makeUser($this->other, 'admin@beta.test', 'agency_admin');
    }

    private function makeAgency(string $slug, Plan $plan): Agency
    {
        $agency = Agency::create(['name' => ucfirst($slug) . ' Agency', 'slug' => $slug, 'status' => 'active']);
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

    private function makeAgent(Agency $agency, string $name = 'Abdus Salam Travels'): Agent
    {
        return Agent::create(['agency_id' => $agency->id, 'name' => $name, 'phone' => '01700000000', 'address' => 'Dhaka', 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name'          => 'Md Rayhan Islam',
            'passport_number'    => 'A16451237',
            'visa_number'        => '6104827033',
            'id_number'          => '10987654320',
            'stamping_date'      => today()->subDay()->format('Y-m-d'),
            'status'             => 'stamped',
            'agent_id'           => null,
            'reference'          => 'Abdus Salam',
            'father_name'        => 'Md Shafiqul Islam',
            'mother_name'        => 'Mst Rokeya Begum',
            'date_of_birth'      => '2003-01-26',
            'mofa_number'        => 'E12345670',
            'mofa_date'          => '2026-08-17',
            'issued_visa_number' => '9876543010',
            'issued_date'        => '2026-09-07',
            'expiry_date'        => '2026-12-06',
            'remarks'            => null,
        ], $overrides);
    }

    private function makeEntry(Agency $agency, array $overrides = []): VisaStamping
    {
        return VisaStamping::create($this->payload($overrides) + ['agency_id' => $agency->id]);
    }

    // ── Computed values ────────────────────────────────────────────────────

    public function test_age_and_left_day_are_computed(): void
    {
        $entry = $this->makeEntry($this->agency)->fresh();

        $this->assertSame(now()->year - 2003, $entry->age);
        $this->assertSame(90, $entry->left_day);          // 07-Sep → 06-Dec
        $this->assertFalse($entry->leftDayIsLow());

        $entry->update(['expiry_date' => '2026-09-30']);
        $this->assertSame(23, $entry->fresh()->left_day);
        $this->assertTrue($entry->fresh()->leftDayIsLow());

        $entry->update(['issued_date' => null]);
        $this->assertNull($entry->fresh()->left_day);
    }

    public function test_aliases_map_to_legacy_columns(): void
    {
        $entry = $this->makeEntry($this->agency, ['passport_number' => 'ab1234567']);

        $legacy = Stamping::find($entry->id);
        $this->assertSame('AB1234567', $legacy->passport_no);
        $this->assertSame($this->payload()['stamping_date'], $legacy->stamp_date->format('Y-m-d'));
    }

    // ── Create / validation ────────────────────────────────────────────────

    public function test_modal_json_store_creates_entry_and_flashes_toast(): void
    {
        $response = $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), $this->payload());

        $entry = VisaStamping::firstOrFail();
        $response->assertOk()->assertJson(['ok' => true, 'id' => $entry->id]);
        $response->assertSessionHas('stamping_toast', 'Visa stamping entry saved successfully');
        $response->assertSessionHas('stamping_highlight', $entry->id);
        $this->assertSame($this->agency->id, $entry->agency_id);
        $this->assertSame($this->staff->id, $entry->created_by);
        $this->assertSame('A16451237', $entry->passport_no);
    }

    public function test_required_fields_and_messages(): void
    {
        $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'passport_number', 'stamping_date', 'status'])
            ->assertJsonPath('errors.full_name.0', 'Full name is required');

        // Only the four required fields are needed.
        $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), [
            'full_name' => 'Minimal Person', 'passport_number' => 'MIN12345',
            'stamping_date' => today()->format('Y-m-d'), 'status' => 'pending',
        ])->assertOk();
    }

    public function test_stamping_date_cannot_be_in_the_future_and_expiry_after_issue(): void
    {
        $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), $this->payload([
            'stamping_date' => today()->addDay()->format('Y-m-d'),
            'expiry_date'   => '2026-09-01',
            'full_name'     => str_repeat('x', 101),
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.stamping_date.0', 'Date cannot be in the future')
            ->assertJsonValidationErrors(['expiry_date', 'full_name']);
    }

    public function test_passport_is_unique_per_agency(): void
    {
        $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), $this->payload(['passport_number' => 'a16451237']))
            ->assertStatus(422)->assertJsonPath('errors.passport_number.0', 'Passport number already exists');

        // Another agency may use the same passport.
        $this->actingAs($this->otherAdmin)->postJson(route('erp.visa-stamping.store'), $this->payload())->assertOk();

        // Updating the same row keeps its own passport.
        $entry = VisaStamping::forAgency($this->agency->id)->first();
        $this->actingAs($this->staff)->putJson(route('erp.visa-stamping.update', $entry), $this->payload(['status' => 'completed']))->assertOk();
        $this->assertSame('completed', $entry->fresh()->status);

        // A soft-deleted entry frees the passport.
        $entry->delete();
        $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), $this->payload())->assertOk();
    }

    public function test_agent_must_belong_to_the_agency(): void
    {
        $foreign = $this->makeAgent($this->other, 'Foreign Agent');
        $own     = $this->makeAgent($this->agency);

        $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), $this->payload(['agent_id' => $foreign->id]))
            ->assertStatus(422)->assertJsonValidationErrors('agent_id');

        $this->actingAs($this->staff)->postJson(route('erp.visa-stamping.store'), $this->payload(['agent_id' => $own->id]))->assertOk();
        $this->assertSame($own->id, VisaStamping::first()->agent_id);
    }

    // ── Auto-fill lookups ──────────────────────────────────────────────────

    public function test_hr_search_returns_own_agency_profiles_with_mother_name(): void
    {
        foreach ([[$this->agency, 'HR1234567'], [$this->other, 'HR1239999']] as [$agency, $passport]) {
            $hr = HrProfile::create([
                'agency_id' => $agency->id, 'full_name_en' => 'HR ' . $agency->slug, 'father_name' => 'HR Father',
                'mother_name' => 'HR Mother', 'nationality' => 'Bangladeshi', 'date_of_birth' => '1995-05-10', 'gender' => 'male',
            ]);
            Passport::create(['hr_profile_id' => $hr->id, 'passport_number' => $passport]);
        }

        $rows = $this->actingAs($this->staff)->getJson(route('erp.visa-stamping.hr-search', ['q' => 'HR123']))->assertOk()->json();

        $this->assertCount(1, $rows);
        $this->assertSame(['HR1234567', 'HR Mother', '1995-05-10'], [$rows[0]['passport_number'], $rows[0]['mother_name'], $rows[0]['date_of_birth']]);
    }

    public function test_mofa_lookup_fills_from_own_agency_only(): void
    {
        MofaEntry::create([
            'agency_id' => $this->agency->id, 'full_name' => 'Mofa Person', 'passport_no' => 'MF1234567',
            'mofa_number' => 'E55555', 'mofa_date' => '2026-08-01', 'visa_serial' => '6100000001',
            'id_number' => '1000000001', 'issue_date' => '2026-08-05', 'expiry_date' => '2026-11-03',
        ]);
        MofaEntry::create([
            'agency_id' => $this->other->id, 'full_name' => 'Other', 'passport_no' => 'MF7654321',
            'mofa_number' => 'E99999', 'mofa_date' => '2026-08-01',
        ]);

        $this->actingAs($this->staff)->getJson(route('erp.visa-stamping.mofa-lookup', ['passport' => 'mf1234567']))
            ->assertOk()
            ->assertJson([
                'found' => true, 'mofa_number' => 'E55555', 'mofa_date' => '2026-08-01',
                'visa_number' => '6100000001', 'passport_issue_date' => '2026-08-05', 'passport_expiry_date' => '2026-11-03',
            ])
            ->assertJsonMissingPath('issued_date')   // passport dates must never pose as visa dates
            ->assertJsonMissingPath('expiry_date');

        $this->actingAs($this->staff)->getJson(route('erp.visa-stamping.mofa-lookup', ['passport' => 'MF7654321']))
            ->assertExactJson(['found' => false]);
    }

    // ── List ───────────────────────────────────────────────────────────────

    public function test_index_search_filters_sort_and_stats(): void
    {
        $agent = $this->makeAgent($this->agency);
        $this->makeEntry($this->agency, ['full_name' => 'Karim Uddin', 'passport_number' => 'KK1111111', 'status' => 'stamped', 'agent_id' => $agent->id, 'stamping_date' => '2026-09-01']);
        $this->makeEntry($this->agency, ['full_name' => 'Rahim Mia', 'passport_number' => 'RR2222222', 'status' => 'pending', 'mofa_number' => 'E77777', 'stamping_date' => '2026-09-10']);
        $this->makeEntry($this->agency, ['full_name' => 'Jamal Hossain', 'passport_number' => 'JJ3333333', 'status' => 'expired', 'expiry_date' => '2026-10-01', 'stamping_date' => '2026-08-15']);
        $this->makeEntry($this->other, ['full_name' => 'Other Agency Person', 'passport_number' => 'OO4444444']);

        $page = $this->actingAs($this->staff)->get(route('erp.visa-stamping.index'));
        $page->assertOk()->assertSee('Karim Uddin')->assertDontSee('Other Agency Person');
        $this->assertSame(['total' => 3, 'stamped' => 1, 'pending' => 1, 'expired' => 1], $page->viewData('stats'));

        $this->actingAs($this->staff)->get(route('erp.visa-stamping.index', ['q' => 'E77777']))->assertSee('Rahim Mia')->assertDontSee('Karim Uddin');
        $this->actingAs($this->staff)->get(route('erp.visa-stamping.index', ['status' => 'expired']))->assertSee('Jamal Hossain')->assertDontSee('Rahim Mia');
        $this->actingAs($this->staff)->get(route('erp.visa-stamping.index', ['agent_id' => $agent->id]))->assertSee('Karim Uddin')->assertDontSee('Rahim Mia');
        $this->actingAs($this->staff)->get(route('erp.visa-stamping.index', ['date_field' => 'expiry', 'from' => '2026-09-25', 'to' => '2026-10-05']))
            ->assertSee('Jamal Hossain')->assertDontSee('Karim Uddin');

        $names = $this->actingAs($this->staff)->get(route('erp.visa-stamping.index', ['sort' => 'name', 'dir' => 'asc']))
            ->viewData('entries')->pluck('full_name')->all();
        $this->assertSame(['Jamal Hossain', 'Karim Uddin', 'Rahim Mia'], $names);
    }

    public function test_empty_state_and_legacy_redirect(): void
    {
        $this->actingAs($this->staff)->get(route('erp.visa-stamping.index'))->assertSee('No visa stamping entries yet');
        $this->actingAs($this->staff)->get(route('erp.stamping'))->assertRedirect('/erp/visa-stamping');
        // The redirect is GET-only, so the legacy POST /erp/stamping store is never shadowed.
        $this->assertSame(['GET', 'HEAD'], app('router')->getRoutes()->getByName('erp.stamping')->methods());
        $this->actingAs($this->staff)->get(route('erp.visa-stamping.create'))->assertRedirect(route('erp.visa-stamping.index', ['add' => 1]));
    }

    // ── Show / edit / update ───────────────────────────────────────────────

    public function test_show_json_edit_redirect_and_update(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->get(route('erp.visa-stamping.show', $entry))->assertOk()->assertSee('Mst Rokeya Begum');
        $this->actingAs($this->staff)->getJson(route('erp.visa-stamping.show', $entry))
            ->assertOk()->assertJson(['id' => $entry->id, 'passport_number' => 'A16451237', 'expiry_date' => '2026-12-06']);
        $this->actingAs($this->staff)->get(route('erp.visa-stamping.edit', $entry))->assertRedirect(route('erp.visa-stamping.index', ['edit' => $entry->id]));

        $this->actingAs($this->staff)->putJson(route('erp.visa-stamping.update', $entry), $this->payload(['status' => 'rejected', 'remarks' => 'Embassy rejected']))
            ->assertOk()->assertSessionHas('stamping_toast');
        $this->assertSame(['rejected', 'Embassy rejected', $this->staff->id], [$entry->fresh()->status, $entry->fresh()->remarks, $entry->fresh()->updated_by]);
    }

    // ── Soft delete / restore ──────────────────────────────────────────────

    public function test_soft_delete_and_admin_restore(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->delete(route('erp.visa-stamping.destroy', $entry))
            ->assertRedirect(route('erp.visa-stamping.index'))->assertSessionHas('stamping_toast', 'Entry deleted successfully');
        $this->assertSoftDeleted('stampings', ['id' => $entry->id]);
        $this->assertSame(0, Stamping::count()); // legacy model also skips deleted rows

        $this->actingAs($this->staff)->patch(route('erp.visa-stamping.restore', $entry->id))->assertForbidden();
        $this->actingAs($this->otherAdmin)->patch(route('erp.visa-stamping.restore', $entry->id))->assertNotFound();

        $this->actingAs($this->admin)->patch(route('erp.visa-stamping.restore', $entry->id))->assertRedirect(route('erp.visa-stamping.index'));
        $this->assertNotSoftDeleted('stampings', ['id' => $entry->id]);
    }

    public function test_restore_is_blocked_when_passport_is_taken_again(): void
    {
        $entry = $this->makeEntry($this->agency);
        $entry->delete();
        $this->makeEntry($this->agency); // same passport, now the live row

        $this->actingAs($this->admin)->patch(route('erp.visa-stamping.restore', $entry->id))
            ->assertSessionHas('stamping_toast_error');
        $this->assertSoftDeleted('stampings', ['id' => $entry->id]);
    }

    // ── Tenancy ────────────────────────────────────────────────────────────

    public function test_other_agency_cannot_touch_entry(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->otherAdmin)->get(route('erp.visa-stamping.show', $entry))->assertForbidden();
        $this->actingAs($this->otherAdmin)->getJson(route('erp.visa-stamping.show', $entry))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.visa-stamping.edit', $entry))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.visa-stamping.print-pdf', $entry))->assertForbidden();
        $this->actingAs($this->otherAdmin)->putJson(route('erp.visa-stamping.update', $entry), $this->payload(['full_name' => 'Hacked']))->assertForbidden();
        $this->actingAs($this->otherAdmin)->delete(route('erp.visa-stamping.destroy', $entry))->assertForbidden();

        $this->assertSame('Md Rayhan Islam', $entry->fresh()->full_name);
    }

    // ── Print / CSV ────────────────────────────────────────────────────────

    public function test_print_preview_pdf_and_csv(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->get(route('erp.visa-stamping.print-pdf', $entry))
            ->assertOk()->assertSee('Visa Stamping Summary')->assertSee('Issu Visa No')->assertSee('A16451237')->assertSee('Total records: 1')
            ->assertDontSee('Exp. Date')->assertDontSee('Left Day')->assertDontSee('Remarks');

        $pdf = $this->actingAs($this->staff)->get(route('erp.visa-stamping.print-pdf', [$entry, 'download' => 1]));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($this->staff)->get(route('erp.visa-stamping.print', ['download' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $csv = $this->actingAs($this->staff)->get(route('erp.visa-stamping.export'))->streamedContent();
        $this->assertStringStartsWith('full_name,father_name,mother_name,passport_number', $csv);
        $this->assertStringContainsString('A16451237', $csv);
    }
}
