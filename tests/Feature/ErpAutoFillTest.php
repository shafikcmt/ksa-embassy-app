<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\BmetEntry;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\HrProfile;
use App\Models\Medical;
use App\Models\MofaEntry;
use App\Models\Passport;
use App\Models\Plan;
use App\Models\Visa;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VisaStamping;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Cross-module ERP auto-fill (GET erp/autofill/{passport}) — source lookup per
 * module, per-field priority merge, HR-profile fallback, exclude-self,
 * passport-vs-visa date separation, tenancy and input validation.
 * MySQL only (see project test-DB note).
 */
class ErpAutoFillTest extends TestCase
{
    use RefreshDatabase;

    private const PP = 'AB1234567';

    private Agency $agency;
    private Agency $other;
    private User $staff;
    private User $otherStaff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = $this->makeAgency('alpha', $plan);
        $this->other  = $this->makeAgency('beta', $plan);
        $this->staff      = $this->makeUser($this->agency, 'staff@alpha.test');
        $this->otherStaff = $this->makeUser($this->other, 'staff@beta.test');
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

    private function makeUser(Agency $agency, string $email): User
    {
        $user = User::create([
            'name' => $email, 'email' => $email, 'password' => Hash::make('secret1234'),
            'agency_id' => $agency->id, 'is_super_admin' => false, 'is_active' => true,
        ]);
        $user->assignRole('agency_staff');
        $user->givePermissionTo('access_erp');

        return $user;
    }

    private function medical(Agency $agency, array $o = []): Medical
    {
        return Medical::create($o + [
            'agency_id' => $agency->id, 'full_name' => 'Medical Name', 'father_name' => 'Medical Father',
            'passport_no' => self::PP, 'date_of_birth' => '2003-01-26', 'mobile_no' => '01761927361',
            'medical_center_name' => 'Horizon', 'country' => 'Saudi Arabia', 'medical_status' => 'fit',
            'medical_issue_date' => '2026-09-01', 'medical_expire_date' => '2026-12-01',
        ]);
    }

    private function mofa(Agency $agency): MofaEntry
    {
        return MofaEntry::create([
            'agency_id' => $agency->id, 'full_name' => 'Mofa Name', 'father_name' => 'Mofa Father',
            'mother_name' => 'Mofa Mother', 'passport_no' => self::PP, 'date_of_birth' => '1999-09-09',
            'mofa_number' => 'E55555', 'mofa_date' => '2026-08-01', 'visa_serial' => '6100000001',
            'id_number' => '1000000001', 'issue_date' => '2020-01-15', 'expiry_date' => '2030-01-14',
        ]);
    }

    private function stamping(Agency $agency): VisaStamping
    {
        return VisaStamping::create([
            'agency_id' => $agency->id, 'full_name' => 'Stamp Name', 'mother_name' => 'Stamp Mother',
            'passport_number' => self::PP, 'visa_number' => '6199999999', 'mofa_number' => 'E00000',
            'issued_visa_number' => '9876543010', 'issued_date' => '2026-09-07', 'expiry_date' => '2026-12-06',
            'stamping_date' => '2026-09-20', 'status' => 'stamped', 'reference' => 'Stamp Ref',
        ]);
    }

    private function bmet(Agency $agency): BmetEntry
    {
        return BmetEntry::create([
            'agency_id' => $agency->id, 'full_name' => 'Bmet Name', 'passport_number' => self::PP,
            'ec_number' => 'EC-301', 'ec_date' => '2026-09-21', 'reference' => 'Bmet Ref', 'status' => 'cleared',
        ]);
    }

    private function hr(Agency $agency): HrProfile
    {
        $hr = HrProfile::create([
            'agency_id' => $agency->id, 'full_name_en' => 'HR Name', 'father_name' => 'HR Father', 'mother_name' => 'HR Mother',
            'nationality' => 'Bangladeshi', 'date_of_birth' => '1995-05-10', 'gender' => 'male', 'phone' => '01811111111',
        ]);
        Passport::create(['hr_profile_id' => $hr->id, 'passport_number' => self::PP, 'issue_date' => '2021-02-02', 'expiry_date' => '2031-02-01']);

        return $hr;
    }

    private function lookup(string $passport = self::PP, array $query = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->staff)->getJson(route('erp.autofill', ['passport' => $passport] + $query));
    }

    public function test_all_modules_are_found_and_merged_by_priority(): void
    {
        $this->medical($this->agency);
        $this->mofa($this->agency);
        $this->stamping($this->agency);
        $this->bmet($this->agency);
        $this->hr($this->agency); // listed as a source, but ranks last for every field

        $json = $this->lookup()->assertOk()->json();

        $this->assertTrue($json['found']);
        $this->assertTrue($json['erp']);
        $this->assertSame(['medical', 'mofa', 'stamping', 'bmet', 'hr'], array_keys($json['sources']));

        $m = $json['merged'];
        $this->assertSame(['Medical Name', 'medical'], [$m['full_name']['value'], $m['full_name']['source']]);
        $this->assertSame(['2003-01-26', 'medical'], [$m['date_of_birth']['value'], $m['date_of_birth']['source']]);
        $this->assertSame(['01761927361', 'medical'], [$m['mobile_no']['value'], $m['mobile_no']['source']]);
        $this->assertSame(['Mofa Mother', 'mofa'], [$m['mother_name']['value'], $m['mother_name']['source']]);
        $this->assertSame(['E55555', 'mofa'], [$m['mofa_number']['value'], $m['mofa_number']['source']]);
        $this->assertSame(['6100000001', 'mofa'], [$m['visa_number']['value'], $m['visa_number']['source']]);
        $this->assertSame(['9876543010', 'stamping'], [$m['issued_visa_number']['value'], $m['issued_visa_number']['source']]);
        $this->assertSame(['2026-09-07', 'stamping'], [$m['issued_date']['value'], $m['issued_date']['source']]);
        $this->assertSame(['Bmet Ref', 'bmet'], [$m['reference']['value'], $m['reference']['source']]);

        // Each source carries a link + readable rows for the tag panel.
        $this->assertSame(route('erp.medical.show', Medical::first()), $json['sources']['medical']['url']);
        $this->assertNotEmpty($json['sources']['stamping']['display']);
    }

    public function test_mofa_passport_dates_never_become_visa_dates(): void
    {
        $this->mofa($this->agency);

        $m = $this->lookup()->json('merged');

        $this->assertSame('2020-01-15', $m['passport_issue_date']['value']);
        $this->assertSame('2030-01-14', $m['passport_expiry_date']['value']);
        $this->assertArrayNotHasKey('issued_date', $m);
        $this->assertArrayNotHasKey('expiry_date', $m);
    }

    public function test_hr_profile_ranks_last_and_only_fills_gaps(): void
    {
        $this->hr($this->agency);
        $medical = $this->medical($this->agency);

        $json = $this->lookup()->assertOk()->json();
        $this->assertSame(['medical', 'hr'], array_keys($json['sources']));
        // ERP wins where it has a value …
        $this->assertSame(['Medical Name', 'medical'], [$json['merged']['full_name']['value'], $json['merged']['full_name']['source']]);
        $this->assertSame('medical', $json['merged']['mobile_no']['source']);
        // … and HR fills what the ERP record lacks.
        $this->assertSame(['HR Mother', 'hr'], [$json['merged']['mother_name']['value'], $json['merged']['mother_name']['source']]);
        $this->assertSame('2021-02-02', $json['merged']['passport_issue_date']['value']);

        $medical->delete(); // soft-deleted records don't count

        $json = $this->lookup()->assertOk()->json();
        $this->assertSame(['hr'], array_keys($json['sources']));
        $this->assertTrue($json['found']);
        $this->assertFalse($json['erp']);
        $this->assertSame(['HR Mother', 'hr'], [$json['merged']['mother_name']['value'], $json['merged']['mother_name']['source']]);
    }

    public function test_hr_visa_sponsor_mofa_and_agent_feed_the_erp_forms(): void
    {
        $hr = $this->hr($this->agency);
        $agent = Agent::create(['agency_id' => $this->agency->id, 'name' => 'Sabbir Howlader', 'phone' => '01700000000', 'address' => 'Dhaka', 'status' => 'active']);
        $hr->update(['agent_id' => $agent->id, 'mofa_new' => 'E-HR-777']);
        Visa::create(['hr_profile_id' => $hr->id, 'visa_number' => '1306123456', 'sponsor_id' => '7023490084']);
        $this->medical($this->agency); // knows nothing about visa / sponsor / mofa / agent

        $m = $this->lookup()->assertOk()->json('merged');

        $this->assertSame(['1306123456', 'hr'], [$m['visa_number']['value'], $m['visa_number']['source']]);
        $this->assertSame(['7023490084', 'hr'], [$m['id_number']['value'], $m['id_number']['source']]);
        $this->assertSame(['E-HR-777', 'hr'], [$m['mofa_number']['value'], $m['mofa_number']['source']]);
        $this->assertSame(['Sabbir Howlader', 'hr'], [$m['reference']['value'], $m['reference']['source']]);
    }

    public function test_medical_delivery_and_double_mofa_supply_the_agent_reference(): void
    {
        $this->medical($this->agency)->update(['reference' => 'Medical Agent']);
        $this->assertSame(['Medical Agent', 'medical'], array_values(array_intersect_key($this->lookup()->json('merged.reference'), ['value' => 1, 'source' => 1])));

        Medical::query()->delete();
        Delivery::create(['agency_id' => $this->agency->id, 'delivery_date' => '2026-09-01', 'full_name' => 'Delivery Name', 'passport_no' => self::PP, 'reference' => 'Delivery Agent', 'total_amount' => 100, 'status' => 'pending']);
        $json = $this->lookup()->json();
        $this->assertSame(['delivery'], array_keys($json['sources']));
        $this->assertTrue($json['erp']);
        $this->assertSame(['Delivery Name', 'delivery'], [$json['merged']['full_name']['value'], $json['merged']['full_name']['source']]);

        DoubleMofa::create(['agency_id' => $this->agency->id, 'mofa_date' => '2026-09-02', 'full_name' => 'Double Name', 'passport_no' => self::PP, 'reference' => 'Double Agent']);
        $json = $this->lookup()->json();
        $this->assertSame(['delivery', 'double_mofa'], array_keys($json['sources']));
        $this->assertSame('Delivery Agent', $json['merged']['reference']['value'], 'Delivery outranks Double MOFA');
    }

    public function test_exclude_leaves_out_the_record_being_edited(): void
    {
        $medical = $this->medical($this->agency);
        $this->mofa($this->agency);

        $json = $this->lookup(self::PP, ['exclude' => 'medical:' . $medical->id])->json();

        $this->assertSame(['mofa'], array_keys($json['sources']));
        $this->assertSame('mofa', $json['merged']['full_name']['source']);

        // Garbage exclude values are ignored, not errors.
        $this->assertSame(['medical', 'mofa'], array_keys($this->lookup(self::PP, ['exclude' => 'users:1'])->json('sources')));
    }

    public function test_other_agencies_records_are_invisible(): void
    {
        $this->medical($this->other);
        $this->mofa($this->other);
        $this->hr($this->other);

        $this->lookup()->assertOk()->assertJson(['found' => false, 'sources' => [], 'merged' => []]);
        $this->assertTrue($this->lookup(self::PP, [], $this->otherStaff)->json('found'));
    }

    public function test_lookup_is_case_insensitive_and_validates_input(): void
    {
        $this->medical($this->agency);

        // Guests are rejected (checked before actingAs, which persists for the test).
        $this->getJson(route('erp.autofill', ['passport' => self::PP]))->assertUnauthorized();

        $this->lookup('ab1234567')->assertOk()->assertJsonPath('passport', self::PP)->assertJsonPath('found', true);
        $this->lookup('AB12')->assertStatus(422);
    }
}
