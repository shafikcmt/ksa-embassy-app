<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\HrProfile;
use App\Models\Medical;
use App\Models\Passport;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ERP Medical Entry — auto age, validation, duplicate-passport warning,
 * HR-profile lookup + link, search/filter/stats, soft delete + admin restore,
 * tenancy, and the Medical Summary print (preview + mPDF download).
 * MySQL only (see project test-DB note).
 */
class MedicalEntryTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name'           => 'Md Rayhan Islam',
            'father_name'         => 'Md Shafiqul Islam',
            'passport_no'         => 'A16451237',
            'date_of_birth'       => '2003-01-26',
            'medical_center_name' => 'Horizon Health Care',
            'country'             => 'Saudi Arabia',
            'medical_code'        => 'SA260978',
            'medical_issue_date'  => '2026-09-07',
            'medical_expire_date' => '2026-12-07',
            'medical_status'      => 'fit',
            'mobile_no'           => '01761927361',
            'reference'           => 'Abdus Salam',
            'remarks'             => null,
        ], $overrides);
    }

    private function makeEntry(Agency $agency, array $overrides = []): Medical
    {
        return Medical::create($this->payload($overrides) + ['agency_id' => $agency->id]);
    }

    private function makeHrProfile(Agency $agency, string $passport): HrProfile
    {
        $hr = HrProfile::create([
            'agency_id' => $agency->id, 'full_name_en' => 'HR Candidate ' . $agency->slug,
            'father_name' => 'HR Father', 'nationality' => 'Bangladeshi',
            'date_of_birth' => '1995-05-10', 'gender' => 'male', 'phone' => '01811111111',
        ]);
        Passport::create(['hr_profile_id' => $hr->id, 'passport_number' => $passport]);

        return $hr;
    }

    // ── Age ────────────────────────────────────────────────────────────────

    public function test_age_is_calculated_from_date_of_birth_on_save(): void
    {
        $entry = $this->makeEntry($this->agency, ['date_of_birth' => '2003-12-31']);

        $this->assertSame(now()->year - 2003, $entry->fresh()->age);

        $entry->update(['date_of_birth' => '1990-01-01']);
        $this->assertSame(now()->year - 1990, $entry->fresh()->age);
    }

    public function test_age_cannot_be_mass_assigned(): void
    {
        $entry = Medical::create($this->payload(['date_of_birth' => '2000-06-01']) + ['agency_id' => $this->agency->id, 'age' => 99]);

        $this->assertSame(now()->year - 2000, $entry->fresh()->age);
    }

    // ── Create / validation ────────────────────────────────────────────────

    public function test_staff_can_open_form_and_store_entry(): void
    {
        // The Add form is a modal on the list page; /medical/add deep-links to it.
        $this->actingAs($this->staff)->get(route('erp.medical.create'))->assertRedirect(route('erp.medical', ['add' => 1]));
        $this->actingAs($this->staff)->get(route('erp.medical', ['add' => 1]))->assertOk()->assertSee('medical-modal-config', false);

        $response = $this->actingAs($this->staff)->post(route('erp.medical.store'), $this->payload());

        $entry = Medical::firstOrFail();
        $response->assertRedirect(route('erp.medical.show', $entry));
        $this->assertSame($this->agency->id, $entry->agency_id);
        $this->assertSame($this->staff->id, $entry->created_by);
        $this->assertSame(now()->year - 2003, $entry->age);
        $this->assertSame('fit', $entry->medical_status);
    }

    public function test_required_fields_are_enforced(): void
    {
        $this->actingAs($this->admin)
            ->post(route('erp.medical.store'), [])
            ->assertSessionHasErrors([
                'full_name', 'father_name', 'passport_no', 'date_of_birth',
                'medical_center_name', 'country', 'medical_issue_date', 'medical_expire_date', 'medical_status',
            ]);

        $this->assertSame(0, Medical::count());
    }

    public function test_expiry_must_be_after_issue_and_dob_before_today(): void
    {
        $this->actingAs($this->admin)
            ->post(route('erp.medical.store'), $this->payload([
                'medical_issue_date'  => '2026-09-07',
                'medical_expire_date' => '2026-09-07',
                'date_of_birth'       => today()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors(['medical_expire_date', 'date_of_birth']);
    }

    public function test_expired_status_is_accepted_and_unknown_status_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('erp.medical.store'), $this->payload(['medical_status' => 'expired']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Expired', Medical::first()->statusLabel());

        $this->actingAs($this->admin)->post(route('erp.medical.store'), $this->payload(['passport_no' => 'B1', 'medical_status' => 'bogus']))
            ->assertSessionHasErrors('medical_status');
    }

    public function test_duplicate_passport_warns_then_allows_on_confirm(): void
    {
        $this->makeEntry($this->agency);

        $this->actingAs($this->admin)
            ->from(route('erp.medical.create'))
            ->post(route('erp.medical.store'), $this->payload())
            ->assertRedirect(route('erp.medical.create'))
            ->assertSessionHas('duplicate_warning');
        $this->assertSame(1, Medical::count());

        $this->actingAs($this->admin)
            ->post(route('erp.medical.store'), $this->payload() + ['confirm_duplicate' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, Medical::count());
    }

    public function test_same_passport_in_another_agency_is_not_a_duplicate(): void
    {
        $this->makeEntry($this->other);

        $this->actingAs($this->admin)
            ->post(route('erp.medical.store'), $this->payload())
            ->assertSessionMissing('duplicate_warning');
        $this->assertSame(1, Medical::forAgency($this->agency->id)->count());
    }

    // ── HR profile auto-fill + link ────────────────────────────────────────

    public function test_lookup_returns_own_agency_hr_profile_only(): void
    {
        $this->makeHrProfile($this->agency, 'HR1234567');
        $this->makeHrProfile($this->other, 'HR7654321');

        $this->actingAs($this->staff)
            ->getJson(route('erp.medical.lookup', ['passport_no' => 'HR1234567']))
            ->assertOk()
            ->assertJson(['found' => true, 'full_name' => 'HR Candidate alpha', 'father_name' => 'HR Father', 'date_of_birth' => '1995-05-10']);

        $this->actingAs($this->staff)
            ->getJson(route('erp.medical.lookup', ['passport_no' => 'HR7654321']))
            ->assertOk()
            ->assertJson(['found' => false]);
    }

    public function test_lookup_falls_back_to_previous_medical_entry(): void
    {
        $this->makeEntry($this->agency, ['passport_no' => 'P9990001', 'father_name' => 'Prev Father']);

        $this->actingAs($this->staff)
            ->getJson(route('erp.medical.lookup', ['passport_no' => 'P9990001']))
            ->assertJson(['found' => true, 'source' => 'Previous medical entry', 'father_name' => 'Prev Father']);
    }

    public function test_entry_links_matching_hr_profile(): void
    {
        $hr = $this->makeHrProfile($this->agency, 'HR1234567');

        $this->actingAs($this->admin)->post(route('erp.medical.store'), $this->payload(['passport_no' => 'HR1234567']));

        $this->assertSame($hr->id, Medical::first()->hr_profile_id);
    }

    // ── List / search / filter / stats ─────────────────────────────────────

    public function test_index_searches_filters_and_counts(): void
    {
        $this->makeEntry($this->agency, ['full_name' => 'Karim Uddin', 'passport_no' => 'K1', 'medical_status' => 'fit']);
        $this->makeEntry($this->agency, ['full_name' => 'Rahim Mia', 'passport_no' => 'R1', 'medical_status' => 'pending']);
        $this->makeEntry($this->agency, ['full_name' => 'Jamal Hossain', 'passport_no' => 'J1', 'medical_status' => 'expired']);
        $this->makeEntry($this->other, ['full_name' => 'Other Agency Person', 'passport_no' => 'O1']);

        $page = $this->actingAs($this->staff)->get(route('erp.medical'));
        $page->assertOk()->assertSee('Karim Uddin')->assertSee('Rahim Mia')->assertDontSee('Other Agency Person');
        $this->assertSame(['total' => 3, 'pending' => 1, 'fit' => 1, 'expired' => 1], $page->viewData('stats'));

        $this->actingAs($this->staff)->get(route('erp.medical', ['q' => 'rahim']))
            ->assertSee('Rahim Mia')->assertDontSee('Karim Uddin');

        $this->actingAs($this->staff)->get(route('erp.medical', ['status' => 'expired']))
            ->assertSee('Jamal Hossain')->assertDontSee('Rahim Mia');
    }

    // ── Soft delete / restore ──────────────────────────────────────────────

    public function test_delete_is_soft_and_only_admin_can_restore(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->delete(route('erp.medical.destroy', $entry))->assertRedirect(route('erp.medical'));
        $this->assertSoftDeleted('medicals', ['id' => $entry->id]);
        $this->actingAs($this->staff)->get(route('erp.medical'))->assertDontSee($entry->full_name);

        $this->actingAs($this->staff)->patch(route('erp.medical.restore', $entry->id))->assertForbidden();
        $this->actingAs($this->otherAdmin)->patch(route('erp.medical.restore', $entry->id))->assertNotFound();

        $this->actingAs($this->admin)->get(route('erp.medical', ['trashed' => 1]))->assertSee($entry->full_name);
        $this->actingAs($this->admin)->patch(route('erp.medical.restore', $entry->id))
            ->assertRedirect(route('erp.medical'))
            ->assertSessionHas('medical_highlight', $entry->id);
        $this->assertNotSoftDeleted('medicals', ['id' => $entry->id]);
    }

    // ── Tenancy ────────────────────────────────────────────────────────────

    public function test_other_agency_cannot_touch_entry(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->otherAdmin)->get(route('erp.medical.show', $entry))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.medical.edit', $entry))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.medical.print-pdf', $entry))->assertForbidden();
        $this->actingAs($this->otherAdmin)->put(route('erp.medical.update', $entry), $this->payload(['full_name' => 'Hacked']))->assertForbidden();
        $this->actingAs($this->otherAdmin)->delete(route('erp.medical.destroy', $entry))->assertForbidden();

        $this->assertSame('Md Rayhan Islam', $entry->fresh()->full_name);
        $this->assertNotSoftDeleted('medicals', ['id' => $entry->id]);
    }

    // ── Show / edit / update ───────────────────────────────────────────────

    public function test_show_edit_and_update(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->get(route('erp.medical.show', $entry))->assertOk()->assertSee('Horizon Health Care');
        $this->actingAs($this->staff)->get(route('erp.medical.edit', $entry))->assertRedirect(route('erp.medical', ['edit' => $entry->id]));
        $this->actingAs($this->staff)->getJson(route('erp.medical.show', $entry))
            ->assertOk()->assertJson(['id' => $entry->id, 'passport_no' => 'A16451237', 'date_of_birth' => '2003-01-26']);

        $this->actingAs($this->staff)
            ->put(route('erp.medical.update', $entry), $this->payload(['medical_status' => 'unfit', 'date_of_birth' => '1999-03-03']))
            ->assertRedirect(route('erp.medical.show', $entry));

        $entry->refresh();
        $this->assertSame('unfit', $entry->medical_status);
        $this->assertSame(now()->year - 1999, $entry->age);
        $this->assertSame($this->staff->id, $entry->updated_by);
    }

    // ── Modal (JSON) saves ─────────────────────────────────────────────────

    public function test_modal_json_store_flashes_toast_and_highlight(): void
    {
        $response = $this->actingAs($this->staff)->postJson(route('erp.medical.store'), $this->payload());

        $entry = Medical::firstOrFail();
        $response->assertOk()->assertJson(['ok' => true, 'id' => $entry->id]);
        $response->assertSessionHas('medical_toast', 'Medical entry saved successfully.');
        $response->assertSessionHas('medical_highlight', $entry->id);
    }

    public function test_modal_json_validation_and_duplicate(): void
    {
        $this->actingAs($this->staff)->postJson(route('erp.medical.store'), $this->payload(['passport_no' => 'AB 12', 'full_name' => '']))
            ->assertStatus(422)->assertJsonValidationErrors(['passport_no', 'full_name']);

        $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->postJson(route('erp.medical.store'), $this->payload())
            ->assertStatus(409)->assertJson(['duplicate' => true]);
        $this->assertSame(1, Medical::count());

        $this->actingAs($this->staff)->postJson(route('erp.medical.store'), $this->payload() + ['confirm_duplicate' => 1])
            ->assertOk();
        $this->assertSame(2, Medical::count());
    }

    public function test_modal_json_update(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->putJson(route('erp.medical.update', $entry), $this->payload(['medical_status' => 'pending']))
            ->assertOk()->assertJson(['ok' => true, 'id' => $entry->id]);
        $this->assertSame('pending', $entry->fresh()->medical_status);

        $this->actingAs($this->otherAdmin)->putJson(route('erp.medical.update', $entry), $this->payload())->assertForbidden();
        $this->actingAs($this->otherAdmin)->getJson(route('erp.medical.show', $entry))->assertForbidden();
    }

    public function test_hr_search_returns_own_agency_matches_only(): void
    {
        $this->makeHrProfile($this->agency, 'HR1234567');
        $this->makeHrProfile($this->other, 'HR1239999');

        $rows = $this->actingAs($this->staff)->getJson(route('erp.medical.hr-search', ['q' => 'HR123']))->assertOk()->json();

        $this->assertCount(1, $rows);
        $this->assertSame('HR1234567', $rows[0]['passport_no']);
        $this->assertSame('HR Father', $rows[0]['father_name']);

        $this->actingAs($this->staff)->getJson(route('erp.medical.hr-search', ['q' => 'ZZ999']))->assertOk()->assertExactJson([]);
    }

    public function test_index_filters_by_issue_date_range(): void
    {
        $this->makeEntry($this->agency, ['full_name' => 'August Person', 'passport_no' => 'AUG0001', 'medical_issue_date' => '2026-08-10', 'medical_expire_date' => '2026-11-10']);
        $this->makeEntry($this->agency, ['full_name' => 'September Person', 'passport_no' => 'SEP0001', 'medical_issue_date' => '2026-09-10', 'medical_expire_date' => '2026-12-10']);

        $this->actingAs($this->staff)->get(route('erp.medical', ['from' => '2026-09-01', 'to' => '2026-09-30']))
            ->assertSee('September Person')->assertDontSee('August Person');
    }

    public function test_empty_state_is_shown(): void
    {
        $this->actingAs($this->staff)->get(route('erp.medical'))->assertOk()->assertSee('No medical entries yet');
    }

    // ── Print ──────────────────────────────────────────────────────────────

    public function test_print_preview_and_pdf_download(): void
    {
        $entry = $this->makeEntry($this->agency);

        $this->actingAs($this->staff)->get(route('erp.medical.print-pdf', $entry))
            ->assertOk()
            ->assertSee('Medical Summary')
            ->assertSee('M. Issu. D.')
            ->assertSee('A16451237');

        $pdf = $this->actingAs($this->staff)->get(route('erp.medical.print-pdf', [$entry, 'download' => 1]));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($this->staff)->get(route('erp.medical.print', ['download' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_csv_export_uses_reference_column_order(): void
    {
        $this->makeEntry($this->agency);

        $csv = $this->actingAs($this->staff)->get(route('erp.medical.export'))->streamedContent();
        $header = str_getcsv(strtok($csv, "\n"));

        $this->assertSame([
            'full_name', 'father_name', 'passport_no', 'date_of_birth',
            'medical_center_name', 'country', 'medical_code',
            'medical_issue_date', 'medical_expire_date', 'medical_status',
            'mobile_no', 'reference', 'remarks',
        ], $header);
        $this->assertStringContainsString('A16451237', $csv);
    }
}
