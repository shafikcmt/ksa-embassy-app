<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\HrProfile;
use App\Models\Passport;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HrErpLookupTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->agency = Agency::create(['name' => 'Lookup Agency', 'slug' => 'hr-lookup', 'status' => 'active']);
        $this->user = User::factory()->create(['agency_id' => $this->agency->id, 'is_active' => true]);
        $this->user->assignRole('agency_staff');
        $this->user->givePermissionTo('access_hr');
    }

    private function record(string $table, array $values = []): void
    {
        $defaults = match ($table) {
            'medicals' => ['father_name' => 'Father', 'medical_status' => 'fit'],
            'mofa_entries', 'double_mofas' => ['mofa_date' => '2026-09-01'],
            'stampings' => ['stamp_date' => '2026-09-01'],
            'manpower_completions' => ['completed_date' => '2026-09-01'],
            'deliveries' => ['delivery_date' => '2026-09-01'],
        };
        DB::table($table)->insert($values + $defaults + [
            'agency_id' => $this->agency->id, 'passport_no' => ' ab1234567 ',
            $table === 'manpower_completions' ? 'customer_name' : 'full_name' => 'ERP Person',
            'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-01 10:00:00',
        ]);
    }

    private function lookup(array $params)
    {
        return $this->actingAs($this->user)->getJson(route('hr.erp-lookup', $params));
    }

    public static function searchColumns(): array
    {
        $cases = [];
        foreach (['medicals', 'mofa_entries', 'double_mofas', 'stampings', 'manpower_completions', 'deliveries'] as $table) {
            $cases[$table . ' passport'] = [$table, 'passport_no', 'passport'];
        }
        foreach (['mofa_entries', 'double_mofas', 'stampings', 'deliveries'] as $table) {
            $cases[$table . ' serial'] = [$table, 'visa_serial', 'visa'];
        }
        foreach (['stampings', 'manpower_completions'] as $table) {
            $cases[$table . ' visa'] = [$table, 'visa_number', 'visa'];
        }
        foreach (['mofa_entries', 'stampings'] as $table) {
            $cases[$table . ' mofa'] = [$table, 'mofa_number', 'mofa'];
        }
        $cases['old mofa'] = ['double_mofas', 'old_mofa_number', 'mofa'];
        return $cases;
    }

    #[DataProvider('searchColumns')]
    public function test_each_real_column_is_searchable(string $table, string $column, string $param): void
    {
        $this->record($table, [$column => ' ab1234567 ']);
        $this->lookup([$param => ' AB1234567 '])->assertOk()->assertJsonCount(1, 'candidates')
            ->assertJsonPath('candidates.0.passport', 'AB1234567');
        $this->assertDatabaseHas($table, [$column => ' ab1234567 ']);
    }

    public function test_shared_serial_pick_list_and_cross_module_and_matching(): void
    {
        $this->record('deliveries', ['visa_serial' => 'SHARED']);
        $this->record('double_mofas', ['passport_no' => 'ZZ7654321', 'visa_serial' => 'SHARED']);
        $this->record('mofa_entries', ['mofa_number' => 'MOFA-ONE']);
        $this->lookup(['visa' => 'shared'])->assertJsonCount(2, 'candidates');
        $this->lookup(['visa' => 'shared', 'passport' => 'ab1234567'])->assertJsonCount(1, 'candidates')
            ->assertJsonPath('candidates.0.passport', 'AB1234567');
        $this->lookup(['visa' => 'shared', 'mofa' => 'mofa-one'])->assertJsonCount(1, 'candidates');
        $this->lookup(['passport' => 'ab1234567', 'visa' => 'shared', 'mofa' => 'mofa-one'])->assertJsonCount(1, 'candidates');
        $this->lookup(['passport' => 'ZZ7654321', 'visa' => 'shared', 'mofa' => 'mofa-one'])->assertExactJson(['candidates' => []]);
    }

    public function test_newest_nonempty_mapping_dates_and_identity_whitelist(): void
    {
        $this->record('mofa_entries', ['full_name' => 'Older Name', 'mother_name' => 'Mother', 'father_name' => 'Father',
            'date_of_birth' => '1995-02-03', 'issue_date' => '2020-01-15', 'expiry_date' => '2030-01-14',
            'visa_serial' => 'VISA-MOFA', 'mofa_number' => 'NEW-MOFA', 'id_number' => 'UNPROVEN-ID']);
        $this->record('mofa_entries', ['full_name' => 'Latest Name', 'updated_at' => '2026-09-20 10:00:00']);
        $this->record('stampings', ['mother_name' => 'Latest Mother', 'full_name' => 'Stamp Name',
            'visa_number' => 'VISA-STAMP', 'issued_date' => '2026-09-01', 'expiry_date' => '2026-12-01',
            'issued_visa_number' => 'ISSUED-DIFFERENT', 'updated_at' => '2026-09-10 10:00:00']);
        $this->record('manpower_completions', ['id_number' => 'SPONSOR-ID']);
        $this->record('double_mofas', ['old_mofa_number' => 'OLD-MOFA', 'billing_amount' => 9999, 'status' => 'paid']);
        $response = $this->lookup(['passport' => 'ab1234567'])->assertOk()->assertJsonCount(1, 'candidates')
            ->assertJsonPath('candidates.0.fields.full_name_en', ['value' => 'Latest Name', 'source' => 'MOFA Entry'])
            ->assertJsonPath('candidates.0.fields.mother_name.value', 'Latest Mother')
            ->assertJsonPath('candidates.0.fields.father_name.value', 'Father')
            ->assertJsonPath('candidates.0.fields.date_of_birth.value', '1995-02-03')
            ->assertJsonPath('candidates.0.fields.passport_issue_date.value', '15-01-2020')
            ->assertJsonPath('candidates.0.fields.passport_expiry_date.value', '14-01-2030')
            ->assertJsonPath('candidates.0.fields.visa_number.value', 'VISA-STAMP')
            ->assertJsonPath('candidates.0.fields.sponsor_id.value', 'SPONSOR-ID')
            ->assertJsonPath('candidates.0.fields.mofa_old.value', 'OLD-MOFA');
        foreach (['billing_amount', 'paid_amount', 'payment', 'UNPROVEN-ID', 'ISSUED-DIFFERENT', 'paid'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
    }

    public function test_other_agency_cannot_match_enrich_or_supply_an_and_condition(): void
    {
        $other = Agency::create(['name' => 'Other', 'slug' => 'other-hr', 'status' => 'active']);
        $this->record('deliveries', ['visa_serial' => 'LOCAL']);
        $this->record('mofa_entries', ['agency_id' => $other->id, 'mofa_number' => 'SECRET', 'full_name' => 'Secret Person']);
        $this->lookup(['mofa' => 'SECRET'])->assertExactJson(['candidates' => []]);
        $this->lookup(['visa' => 'LOCAL', 'mofa' => 'SECRET'])->assertExactJson(['candidates' => []]);
        $this->lookup(['passport' => 'AB1234567'])->assertJsonCount(1, 'candidates.0.records')->assertDontSee('Secret Person');
    }

    public function test_validation_not_found_and_hr_access_without_erp_access(): void
    {
        $this->lookup([])->assertUnprocessable();
        $this->lookup(['passport' => ' ', 'visa' => '', 'mofa' => ' '])->assertUnprocessable();
        $this->lookup(['visa' => ['invalid']])->assertUnprocessable();
        $this->lookup(['mofa' => str_repeat('x', 101)])->assertUnprocessable();
        $this->lookup(['passport' => 'NOTFOUND'])->assertOk()->assertExactJson(['candidates' => []]);
        $this->assertFalse($this->user->can('access_erp'));
        $this->user->revokePermissionTo('access_hr');
        $this->lookup(['passport' => 'AB1234567'])->assertRedirect(route('dashboard'));
    }

    public function test_duplicate_hr_warning_is_agency_scoped(): void
    {
        $this->record('mofa_entries');
        $profile = HrProfile::create(['agency_id' => $this->agency->id, 'full_name_en' => 'Existing HR', 'nationality' => 'BANGLADESH', 'date_of_birth' => '1990-01-01', 'gender' => 'male']);
        Passport::create(['hr_profile_id' => $profile->id, 'passport_number' => ' ab1234567 ']);
        $other = Agency::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active']);
        $foreign = HrProfile::create(['agency_id' => $other->id, 'full_name_en' => 'Private HR', 'nationality' => 'BANGLADESH', 'date_of_birth' => '1990-01-01', 'gender' => 'male']);
        Passport::create(['hr_profile_id' => $foreign->id, 'passport_number' => 'AB1234567']);
        $this->lookup(['passport' => 'ab1234567'])->assertJsonCount(1, 'candidates.0.hr_profiles')
            ->assertJsonPath('candidates.0.hr_profiles.0.id', $profile->id)->assertDontSee('Private HR');
    }

    public function test_soft_deleted_rows_are_not_searchable(): void
    {
        $this->record('mofa_entries', ['deleted_at' => now()]);
        $this->lookup(['passport' => 'AB1234567'])->assertExactJson(['candidates' => []]);
    }

    public function test_numeric_passports_keep_leading_zero_identities_separate(): void
    {
        $this->record('deliveries', ['passport_no' => '1234567']);
        $this->record('mofa_entries', ['passport_no' => '01234567', 'mother_name' => 'Another Person']);
        $this->lookup(['passport' => '1234567'])->assertOk()->assertJsonCount(1, 'candidates')
            ->assertJsonCount(1, 'candidates.0.records')->assertDontSee('Another Person');
    }
}
