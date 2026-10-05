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

    public function test_validation_still_applies_and_a_repeated_passport_is_allowed(): void
    {
        $this->entry();
        // Same passport in the same agency: a second MOFA is allowed (no "already exists" block).
        $this->actingAs($this->admin)->postJson(route('erp.mofa.store'), $this->payload())->assertOk()->assertJsonMissingValidationErrors('passport_number');
        $this->assertSame(2, MofaEntry::forAgency($this->agency->id)->where('passport_no', 'AB1234567')->count());
        $this->postJson(route('erp.mofa.store'), $this->payload(['passport_number' => 'NEW', 'expiry_date' => '2025-01-01', 'date_of_birth' => today()->format('Y-m-d'), 'mofa_expiry_date' => '2026-08-01']))->assertUnprocessable()->assertJsonValidationErrors(['expiry_date', 'date_of_birth', 'mofa_expiry_date']);
        $this->actingAs($this->otherAdmin)->postJson(route('erp.mofa.store'), $this->payload())->assertOk();
    }

    public function test_same_passport_can_have_several_entries_and_each_one_edits(): void
    {
        $this->actingAs($this->staff);
        $this->postJson(route('erp.mofa.store'), $this->payload(['mofa_number' => 'M-1']))->assertOk();
        $this->postJson(route('erp.mofa.store'), $this->payload(['mofa_number' => 'M-2']))->assertOk();
        [$first, $second] = MofaEntry::orderBy('id')->get()->all();
        $this->putJson(route('erp.mofa.update', $first), $this->payload(['mofa_number' => 'M-1', 'remarks' => 'first']))->assertOk();
        $this->putJson(route('erp.mofa.update', $second), $this->payload(['mofa_number' => 'M-2', 'remarks' => 'second']))->assertOk();
        $this->assertSame(['first', 'second'], MofaEntry::orderBy('id')->pluck('remarks')->all());
        $this->assertSame([$this->agency->id], MofaEntry::distinct()->pluck('agency_id')->all());
    }

    public function test_passport_count_is_agency_scoped_and_excludes_the_entry_being_edited(): void
    {
        $first = $this->entry(['mofa_number' => 'M-1']);
        $this->entry(['mofa_number' => 'M-2']);
        $foreign = MofaEntry::create($this->payload(['mofa_number' => 'X-1']) + ['agency_id' => $this->other->id]);

        $this->actingAs($this->staff);
        $this->getJson(route('erp.mofa.passport-count', ['passport' => ' ab1234567 ']))->assertOk()->assertExactJson(['count' => 2]);
        $this->getJson(route('erp.mofa.passport-count', ['passport' => 'AB1234567', 'exclude' => $first->id]))->assertExactJson(['count' => 1]);
        $this->getJson(route('erp.mofa.passport-count', ['passport' => 'NOPE123']))->assertExactJson(['count' => 0]);

        // Another agency only ever sees its own entries, whatever it excludes.
        $this->actingAs($this->otherAdmin);
        $this->getJson(route('erp.mofa.passport-count', ['passport' => 'AB1234567', 'exclude' => $first->id]))->assertExactJson(['count' => 1]);
        $this->getJson(route('erp.mofa.passport-count', ['passport' => 'AB1234567', 'exclude' => $foreign->id]))->assertExactJson(['count' => 0]);

        // Unauthenticated: no count.
        $this->app['auth']->forgetGuards();
        $this->getJson(route('erp.mofa.passport-count', ['passport' => 'AB1234567']))->assertUnauthorized();
    }

    public function test_lookups_return_the_latest_mofa_by_date_then_id_within_the_agency(): void
    {
        $old = $this->entry(['mofa_number' => 'OLD', 'mofa_date' => '2026-01-10', 'mofa_expiry_date' => '2026-04-10', 'visa_number' => 'V-OLD']);
        $this->entry(['mofa_number' => 'NEW', 'mofa_date' => '2026-05-01', 'mofa_expiry_date' => '2026-07-30', 'visa_number' => 'V-NEW']);
        $this->entry(['mofa_number' => 'TIE', 'mofa_date' => '2026-05-01', 'mofa_expiry_date' => '2026-07-30', 'visa_number' => 'V-TIE']);
        $undated = $this->entry(['mofa_number' => 'NODATE', 'mofa_date' => null, 'mofa_expiry_date' => '2026-12-01', 'visa_number' => 'V-ND']);
        MofaEntry::create($this->payload(['mofa_number' => 'FOREIGN', 'mofa_date' => '2027-01-01', 'mofa_expiry_date' => '2027-03-01', 'visa_number' => 'V-F']) + ['agency_id' => $this->other->id]);
        // Editing an older/undated entry later must not make it "latest".
        $this->travel(5)->minutes();
        $old->touch();
        $undated->touch();

        $this->assertSame('TIE', MofaEntry::forAgency($this->agency->id)->where('passport_no', 'AB1234567')->latestMofa()->first()->mofa_number);
        $this->assertSame('TIE', app(\App\Services\ErpPassportDataService::class)->forPassport($this->agency->id, 'AB1234567')['merged']['mofa_number']['value']);
        $this->assertSame('FOREIGN', app(\App\Services\ErpPassportDataService::class)->forPassport($this->other->id, 'AB1234567')['merged']['mofa_number']['value']);

        $this->actingAs($this->admin);
        $this->getJson(route('erp.visa-stamping.mofa-lookup', ['passport' => 'AB1234567']))->assertOk()->assertJsonPath('mofa_number', 'TIE');
        $this->getJson(route('erp.passport-lookup', ['passport_no' => 'AB1234567']))->assertOk()->assertJsonPath('visa_serial', 'V-TIE');

        // An entry without a MOFA Date sorts last even when it is the only other one.
        $this->assertSame('NODATE', MofaEntry::forAgency($this->agency->id)->where('passport_no', 'AB1234567')->latestMofa()->get()->last()->mofa_number);
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
            ->assertOk();
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

    private const IMPORT_HEADERS = "mofa_date,mofa_number,visa_serial,full_name,passport_no,reference_name,payment_method,whatsapp_number,payment_note\n";

    private function importCsv(string $rows)
    {
        $file = UploadedFile::fake()->createWithContent('mofa.csv', self::IMPORT_HEADERS.$rows);

        return $this->post(route('erp.mofa.import.preview'), ['file' => $file])->assertOk();
    }

    public function test_import_skips_exact_repeats_and_allows_another_mofa_for_the_same_passport(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);
        $m100 = "2026-09-25,M100,V100,Legacy Passenger,LEGACY123,Agent,no_payment,,\n";
        $m200 = "2026-10-01,M200,V200,Legacy Passenger,LEGACY123,Agent,no_payment,,\n";
        $blank = "2026-10-02,,V300,Legacy Passenger,LEGACY123,Agent,no_payment,,\n";
        $this->importCsv($m100.$m100.$m200.$blank)
            ->assertSee('appears earlier in this file')
            ->assertSee('MOFA Number is empty')
            ->assertSee('imported as another entry');
        $this->post(route('erp.mofa.import'))->assertRedirect(route('erp.mofa'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Imported 3') && str_contains($m, 'Skipped 1'));
        $this->assertSame(['M100', 'M200', null], MofaEntry::forAgency($this->agency->id)->orderBy('id')->pluck('mofa_number')->all());
        $this->assertSame('processing', MofaEntry::firstOrFail()->status);
    }

    public function test_same_file_uploaded_twice_adds_no_rows(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);
        $rows = "2026-09-25,M100,V100,Legacy Passenger,LEGACY123,Agent,no_payment,,\n"
            ."2026-10-01,M200,V200,Other Passenger,OTHER456,Agent,no_payment,,\n";
        $this->importCsv($rows);
        $this->post(route('erp.mofa.import'))->assertSessionHas('success', fn ($m) => str_contains($m, 'Imported 2'));

        $this->importCsv($rows)->assertSee('is already saved');
        $this->post(route('erp.mofa.import'))->assertSessionHas('success', fn ($m) => str_contains($m, 'Imported 0') && str_contains($m, 'Skipped 2'));
        $this->assertDatabaseCount('mofa_entries', 2);
    }

    public function test_import_with_an_invalid_row_imports_nothing(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin);
        $this->importCsv("2026-09-25,M100,V100,Good Row,GOOD123,Agent,no_payment,,\n"
            ."not-a-date,M200,V200,Bad Row,BAD123,Agent,no_payment,,\n");
        $this->post(route('erp.mofa.import'))->assertOk()->assertSee('nothing was imported');
        $this->assertDatabaseCount('mofa_entries', 0);
        // Another agency is never affected by this agency's imports.
        $this->assertSame(0, MofaEntry::forAgency($this->other->id)->count());
    }

    public function test_listing_empty_states_use_a_standalone_panel_and_populated_table_still_scrolls(): void
    {
        $this->actingAs($this->admin);
        $empty = $this->get(route('erp.mofa'))->assertOk()->assertSee('0 records')
            ->assertSee('No MOFA entries yet')->assertSee('id="mf-empty-title"', false)
            ->assertDontSee('Scroll to see all passenger details')
            ->assertDontSee('<table class="mf-table">', false)
            ->assertDontSee('aria-label="MOFA entries, scroll horizontally"', false);
        $this->assertStringContainsString('@click="$dispatch(\'mofa-add\')">+ Add MOFA Entry</button>', $empty->getContent());
        $empty->assertSee('mofa-config', false);

        $entry = $this->entry();
        foreach ([['q' => 'NO-MATCH'], ['status' => 'processing'], ['from' => '2027-01-01']] as $filters) {
            $this->get(route('erp.mofa', $filters))->assertOk()->assertSee('0 records')
                ->assertSee('No matching MOFA entries')->assertSee('id="mf-empty-title"', false)
                ->assertDontSee('Scroll to see all passenger details')
                ->assertDontSee('<table class="mf-table">', false)
                ->assertDontSee('aria-label="MOFA entries, scroll horizontally"', false);
        }
        $this->get(route('erp.mofa', ['q' => $entry->passport_number]))->assertOk()
            ->assertSee('1 records')->assertSee('Scroll to see all passenger details')
            ->assertSee('<table class="mf-table">', false)
            ->assertSee('aria-label="MOFA entries, scroll horizontally"', false)
            ->assertSee('Test Passenger')->assertDontSee('id="mf-empty-title"', false);
    }

    public function test_modal_moves_reference_and_removes_expiry_and_countdown(): void
    {
        $this->actingAs($this->staff);
        $this->get(route('erp.mofa.create'))->assertRedirect(route('erp.mofa', ['add' => 1]));
        $response = $this->get(route('erp.mofa', ['add' => 1]));
        $response->assertOk()->assertDontSee('id="mf-mofa_expiry_date"', false)
            ->assertDontSee('id="mf-left_day"', false)
            ->assertSeeInOrder(['MOFA &amp; Visa Details', 'id="mf-reference"', 'id="mf-remarks"'], false);
        $this->assertSame(1, substr_count($response->getContent(), 'id="mf-reference"'));
        $this->assertSame(1, substr_count($response->getContent(), '>Print</a>'));
        $response->assertDontSee('Print Landscape')->assertDontSee('Portrait');
        $response->assertSee('You can still save.')->assertSee('erp-autofilled', false);
    }

    public function test_create_and_legacy_edit_without_retired_inputs(): void
    {
        $payload = $this->payload();
        unset($payload['mofa_expiry_date']);
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $payload)->assertOk();
        $entry = MofaEntry::firstOrFail();
        $this->assertSame('2026-12-24', $entry->mofa_expiry_date->format('Y-m-d'));
        foreach (['2026-09-01', null] as $expiry) {
            $entry->update(['mofa_expiry_date' => $expiry]);
            $this->get(route('erp.mofa.edit', $entry))->assertRedirect(route('erp.mofa', ['edit' => $entry->id]));
            $this->putJson(route('erp.mofa.update', $entry), $payload + ['remarks' => 'Edited'])->assertOk();
            $this->assertSame($expiry, $entry->fresh()->mofa_expiry_date?->format('Y-m-d'));
            $this->assertSame('Agent', $entry->fresh()->reference);
        }
        $this->putJson(route('erp.mofa.update', $entry), array_merge($payload, ['mofa_date' => '2026-10-01']))->assertOk();
        $this->assertSame('2026-12-30', $entry->fresh()->mofa_expiry_date->format('Y-m-d'));
        $this->postJson(route('erp.mofa.store'), array_merge($payload, ['mofa_date' => '', 'passport_number' => 'UNDATED']))->assertOk();
        $this->assertNull(MofaEntry::where('passport_no', 'UNDATED')->firstOrFail()->mofa_expiry_date);
    }

    public function test_pdf_columns_widths_and_empty_state(): void
    {
        $this->actingAs($this->admin);
        $html = $this->get(route('erp.mofa.print', ['preview' => 1]))->assertOk()
            ->assertSee('No MOFA records found.')->assertSee('colspan="15"', false)
            ->assertDontSee('Remarks')->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $headers = $xpath->query('//table[contains(@class, "mofa-print")]/thead/tr/th');
        $labels = [];
        $totalWidth = 0;
        foreach ($headers as $header) {
            $labels[] = trim($header->textContent);
            preg_match('/width:\s*(\d+)%/', $header->getAttribute('style'), $width);
            $totalWidth += (int) $width[1];
        }
        $this->assertCount(15, $labels);
        $this->assertSame(['MOFA No', 'MOFA Date', 'Reference'], array_slice($labels, -3));
        $this->assertSame(100, $totalWidth);
        $pdf = $this->get(route('erp.mofa.print'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $text = (new \Smalot\PdfParser\Parser)->parseContent($pdf->getContent())->getText();
        $this->assertStringContainsString('No MOFA records found.', $text);
        $this->assertStringNotContainsString('Remarks', $text);

        $entry = $this->entry(['reference' => 'Final Reference', 'remarks' => 'Private Remarks']);
        $html = $this->get(route('erp.mofa.print', ['preview' => 1]))->assertOk()
            ->assertDontSee('Private Remarks')->getContent();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $cells = $xpath->query('//table[contains(@class, "mofa-print")]/tbody/tr/td');
        $this->assertSame(15, $cells->length);
        $this->assertSame('Final Reference', trim($cells->item(14)->textContent));
        $this->assertSame('Private Remarks', $entry->fresh()->remarks);
    }

    public function test_pdf_layouts_and_csv(): void
    {
        $entry = $this->entry(['remarks' => '=HYPERLINK("bad")']);
        $this->actingAs($this->admin);
        foreach (['landscape', 'portrait'] as $layout) {
            $this->get(route('erp.mofa.print-pdf', [$entry, 'layout' => $layout, 'preview' => 1]))->assertOk()->assertSee('MOFA Summary')->assertDontSee('Left Day')->assertDontSee('MOFA Expiry Date')->assertDontSee('Part 1 of 2')->assertDontSee('Remarks')->assertDontSee('=HYPERLINK')->assertSee('Reference');
            $response = $this->get(route('erp.mofa.print-pdf', [$entry, 'layout' => $layout]));
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
            $text = (new \Smalot\PdfParser\Parser)->parseContent($response->getContent())->getText();
            $this->assertStringNotContainsString('Left Day', $text);
            $this->assertStringNotContainsString('MOFA Expiry Date', $text);
            $this->assertStringNotContainsString('Remarks', $text);
            $this->assertStringNotContainsString('HYPERLINK', $text);
            $this->assertStringContainsString('Reference', $text);
        }
        $this->get(route('erp.mofa.show', $entry))->assertOk()->assertDontSee('Landscape')->assertDontSee('Portrait');
        $this->get(route('erp.mofa.print'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $response = $this->get(route('erp.mofa.export'));
        $response->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
        $this->get(route('erp.mofa.print', ['layout' => 'bad']))->assertSessionHasErrors('layout');
    }
}
