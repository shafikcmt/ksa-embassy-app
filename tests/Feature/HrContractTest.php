<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\HrProfile;
use App\Models\Passport;
use App\Models\User;
use App\Models\Visa;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Employment Contract page (hr.contract) + HR list search / page size.
 *
 * These tests must run on MySQL (project setup): a pre-existing MySQL-only
 * migration breaks the SQLite default for RefreshDatabase feature tests.
 */
class HrContractTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->agency = Agency::create(['name' => 'Contract Agency', 'slug' => 'contract', 'status' => 'active']);
        $this->user = User::factory()->create(['agency_id' => $this->agency->id, 'is_active' => true]);
        $this->user->assignRole('agency_staff');
        $this->user->givePermissionTo('access_hr');
    }

    private function candidate(Agency $agency, string $name, string $passport): HrProfile
    {
        $hr = HrProfile::create(['agency_id' => $agency->id, 'full_name_en' => $name, 'nationality' => 'BANGLADESH',
            'date_of_birth' => '1995-01-01', 'gender' => 'male', 'mofa_new' => 'E' . substr($passport, -6)]);
        Passport::create(['hr_profile_id' => $hr->id, 'passport_number' => $passport]);
        Visa::create(['hr_profile_id' => $hr->id, 'visa_number' => '13' . substr($passport, -8), 'sponsor_id' => '70' . substr($passport, -8),
            'sponsor_name_ar' => 'فيصل اليامي للنقليات', 'profession_en' => 'Driver']);

        return $hr;
    }

    public function test_contract_page_is_prefilled_from_the_hr_record(): void
    {
        $hr = $this->candidate($this->agency, 'Md Saydul Khan', 'A19101335');

        $this->actingAs($this->user)->get(route('hr.contract', $hr))
            ->assertOk()
            ->assertSee('EMPLOYMENT CONTRACT')
            ->assertSee('A19101335')
            ->assertSee('MD SAYDUL KHAN')
            ->assertSee('BANGLADESHI')
            ->assertSee('Driver')
            // Browser auto-translate must not rewrite the Arabic contract text.
            ->assertSee('translate="no"', false)
            ->assertSee('<meta name="google" content="notranslate">', false);
    }

    public function test_contract_of_another_agency_is_forbidden(): void
    {
        $other = Agency::create(['name' => 'Other', 'slug' => 'other-c', 'status' => 'active']);
        $foreign = $this->candidate($other, 'Foreign Person', 'ZZ0000001');

        $this->actingAs($this->user)->get(route('hr.contract', $foreign))->assertForbidden();
    }

    public function test_list_search_matches_passport_visa_mofa_and_sponsor_id(): void
    {
        $this->candidate($this->agency, 'Alpha One', 'AA1111111');
        $this->candidate($this->agency, 'Beta Two', 'BB2222222');

        // passport, visa no, MOFA id and sponsor id of "Alpha One" (see candidate()).
        foreach (['AA1111111', '13A1111111', 'E111111', '70A1111111'] as $q) {
            $this->actingAs($this->user)->get(route('hr.index', ['search' => $q]))
                ->assertOk()->assertSee('Alpha One')->assertDontSee('Beta Two');
        }
    }

    public function test_page_size_is_whitelisted(): void
    {
        $this->actingAs($this->user)->get(route('hr.index', ['per_page' => 25]))->assertOk()->assertViewHas('perPage', 25);
        $this->actingAs($this->user)->get(route('hr.index', ['per_page' => 9999]))->assertOk()->assertViewHas('perPage', 10);
    }
}
