<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Delivery;
use App\Models\Medical;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ERP "Reference" = agent dropdown: agency-scoped agent list, agent name saved
 * as the plain reference string, and old free-text references survive edits.
 *
 * MySQL only (see project test-DB note):
 *   DB_CONNECTION=mysql DB_DATABASE=ksa_embassy_test php artisan test tests/Feature/ErpReferenceAgentTest.php
 */
class ErpReferenceAgentTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = $this->makeAgency('alpha', $plan);
        $other = $this->makeAgency('beta', $plan);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@alpha.test', 'password' => Hash::make('secret1234'),
            'agency_id' => $this->agency->id, 'is_super_admin' => false, 'is_active' => true,
        ]);
        $this->admin->assignRole('agency_admin');

        $this->agent('Horizon Recruitment', $this->agency, 'active');
        $this->agent('Sleeping Agent', $this->agency, 'inactive');
        $this->agent('Foreign Agent', $other, 'active');
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

    private function agent(string $name, Agency $agency, string $status): Agent
    {
        return Agent::create(['agency_id' => $agency->id, 'name' => $name, 'phone' => '01700000000', 'address' => 'Dhaka', 'status' => $status]);
    }

    private function medicalPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Md Rayhan Islam', 'father_name' => 'Md Shafiqul Islam', 'passport_no' => 'A16451237',
            'date_of_birth' => '2003-01-26', 'medical_center_name' => 'Horizon Health Care', 'country' => 'Saudi Arabia',
            'medical_code' => 'SA260978', 'mobile_no' => '01712345678', 'medical_issue_date' => '2026-09-01',
            'medical_expire_date' => '2026-12-01', 'medical_status' => 'fit', 'reference' => null, 'remarks' => null,
        ], $overrides);
    }

    private function deliveryPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Delivery Pax', 'passport_no' => 'DL1234567', 'delivery_date' => '2026-09-20',
            'total_amount' => '1500', 'status' => 'pending', 'payment_method' => '', 'reference' => null,
        ], $overrides);
    }

    /** @return array<int, string> */
    private function names(array $options): array
    {
        return array_column($options, 'name');
    }

    public function test_agent_options_are_scoped_to_own_agency_with_active_flag(): void
    {
        foreach (['erp.medical', 'erp.delivery', 'erp.mofa', 'erp.double-mofa'] as $route) {
            $options = $this->actingAs($this->admin)->get(route($route))->assertOk()->viewData('agentOptions');

            $this->assertSame(['Horizon Recruitment', 'Sleeping Agent'], $this->names($options), $route);
            $this->assertNotContains('Foreign Agent', $this->names($options), $route);
            $this->assertSame([true, false], array_column($options, 'active'), $route);
        }
    }

    public function test_missing_agency_gives_empty_list_without_querying_null(): void
    {
        $this->assertSame([], Agent::referenceOptions(null));
        $this->assertSame([], Agent::referenceOptions(0));
    }

    public function test_classic_forms_submit_exactly_one_reference_field(): void
    {
        foreach (['erp.delivery', 'erp.double-mofa'] as $route) {
            $html = $this->actingAs($this->admin)->get(route($route))->assertOk()->getContent();
            // One hidden input in the Add card + one in the Edit modal; the search boxes have no name.
            $this->assertSame(2, substr_count($html, 'name="reference"'), $route);
            $this->assertStringNotContainsString('list="', $html, $route);
        }
    }

    public function test_choosing_an_agent_saves_the_agent_name_string(): void
    {
        $this->actingAs($this->admin)->postJson(route('erp.medical.store'), $this->medicalPayload(['reference' => 'Horizon Recruitment']))->assertOk();
        $this->assertSame('Horizon Recruitment', Medical::latest('id')->value('reference'));

        $this->post(route('erp.delivery.store'), $this->deliveryPayload(['reference' => 'Horizon Recruitment']))->assertRedirect();
        $this->assertSame('Horizon Recruitment', Delivery::latest('id')->value('reference'));
    }

    public function test_updating_without_touching_reference_keeps_old_free_text(): void
    {
        // Old free-text values that match no agent ("(old)" in the dropdown).
        $this->actingAs($this->admin)->postJson(route('erp.medical.store'), $this->medicalPayload(['reference' => 'Dr. Karim']))->assertOk();
        $medical = Medical::latest('id')->first();
        $this->putJson(route('erp.medical.update', $medical), $this->medicalPayload(['reference' => 'Dr. Karim', 'remarks' => 'edited']))->assertOk();
        $this->assertSame('Dr. Karim', $medical->fresh()->reference);
        $this->assertSame('edited', $medical->fresh()->remarks);

        $this->post(route('erp.delivery.store'), $this->deliveryPayload(['reference' => 'Walk-in Customer']))->assertRedirect();
        $delivery = Delivery::latest('id')->first();
        $this->put(route('erp.delivery.update', $delivery), $this->deliveryPayload(['reference' => 'Walk-in Customer', 'total_amount' => '1800']))->assertRedirect();
        $this->assertSame('Walk-in Customer', $delivery->fresh()->reference);
        $this->assertEquals(1800, (float) $delivery->fresh()->total_amount);

        // An inactive agent's name also stays as-is.
        $this->put(route('erp.delivery.update', $delivery), $this->deliveryPayload(['reference' => 'Sleeping Agent']))->assertRedirect();
        $this->assertSame('Sleeping Agent', $delivery->fresh()->reference);
    }
}
