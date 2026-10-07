<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\Invoice;
use App\Models\Medical;
use App\Models\MofaEntry;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Agent-wise filter on every module list that carries an agent (reference name or agent_id). */
class AgentFilterTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = Agency::create(['name' => 'Alpha Agency', 'slug' => 'alpha', 'status' => 'active']);
        Subscription::create(['agency_id' => $this->agency->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@alpha.test', 'password' => Hash::make('secret1234'), 'agency_id' => $this->agency->id, 'is_super_admin' => false, 'is_active' => true]);
        $this->admin->assignRole('agency_admin');

        foreach (['Sabbir Howlader', 'Manik Palton'] as $name) {
            Agent::create(['agency_id' => $this->agency->id, 'name' => $name, 'phone' => '01700000000', 'address' => 'Dhaka', 'status' => 'active']);
        }
    }

    public function test_mofa_list_filters_by_agent(): void
    {
        $base = ['agency_id' => $this->agency->id, 'mofa_date' => '2026-09-25'];
        MofaEntry::create($base + ['full_name' => 'Sabbir Passenger', 'passport_number' => 'AA1111111', 'reference' => 'Sabbir Howlader']);
        MofaEntry::create($base + ['full_name' => 'Manik Passenger', 'passport_number' => 'BB2222222', 'reference' => 'Manik Palton']);

        $this->actingAs($this->admin)->get(route('erp.mofa', ['agent' => 'Sabbir Howlader']))
            ->assertOk()->assertSee('Sabbir Passenger')->assertDontSee('Manik Passenger')
            ->assertSee('agent=Sabbir', false); // print / export links keep the filter
    }

    public function test_medical_list_filters_by_agent(): void
    {
        $base = ['agency_id' => $this->agency->id, 'father_name' => 'Father', 'mobile_no' => '01700000000', 'medical_center_name' => 'Horizon', 'country' => 'Saudi Arabia', 'medical_status' => 'fit', 'medical_issue_date' => '2026-09-01', 'medical_expire_date' => '2026-12-01', 'date_of_birth' => '2000-01-01'];
        Medical::create($base + ['full_name' => 'Sabbir Passenger', 'passport_no' => 'AA1111111', 'reference' => 'Sabbir Howlader']);
        Medical::create($base + ['full_name' => 'Manik Passenger', 'passport_no' => 'BB2222222', 'reference' => 'Manik Palton']);

        $this->actingAs($this->admin)->get(route('erp.medical', ['agent' => 'Manik Palton']))
            ->assertOk()->assertSee('Manik Passenger')->assertDontSee('Sabbir Passenger');
    }

    public function test_delivery_and_double_mofa_filter_list_and_print_by_agent(): void
    {
        Delivery::create(['agency_id' => $this->agency->id, 'delivery_date' => '2026-09-01', 'full_name' => 'Sabbir Passenger', 'passport_no' => 'AA1111111', 'reference' => 'Sabbir Howlader', 'total_amount' => 1000, 'status' => 'pending']);
        Delivery::create(['agency_id' => $this->agency->id, 'delivery_date' => '2026-09-01', 'full_name' => 'Manik Passenger', 'passport_no' => 'BB2222222', 'reference' => 'Manik Palton', 'total_amount' => 1000, 'status' => 'pending']);
        DoubleMofa::create(['agency_id' => $this->agency->id, 'mofa_date' => '2026-09-01', 'full_name' => 'Sabbir Double', 'passport_no' => 'AA1111111', 'reference' => 'Sabbir Howlader']);
        DoubleMofa::create(['agency_id' => $this->agency->id, 'mofa_date' => '2026-09-01', 'full_name' => 'Manik Double', 'passport_no' => 'BB2222222', 'reference' => 'Manik Palton']);

        $this->actingAs($this->admin);
        $this->get(route('erp.delivery', ['agent' => 'Sabbir Howlader']))->assertOk()->assertSee('Sabbir Passenger')->assertDontSee('Manik Passenger');
        $this->get(route('erp.delivery.print', ['agent' => 'Sabbir Howlader']))->assertOk()->assertSee('Sabbir Passenger')->assertDontSee('Manik Passenger');
        $this->get(route('erp.delivery'))->assertOk()->assertSee('Sabbir Passenger')->assertSee('Manik Passenger');
        $this->get(route('erp.double-mofa', ['agent' => 'Manik Palton']))->assertOk()->assertSee('Manik Double')->assertDontSee('Sabbir Double');
    }

    public function test_invoice_list_filters_by_agent_id(): void
    {
        [$sabbir, $manik] = Agent::orderBy('id')->get()->all();
        foreach ([[$sabbir, 'INV-SABBIR', 1], [$manik, 'INV-MANIK', 2]] as [$agent, $number, $seq]) {
            Invoice::forceCreate(['agency_id' => $this->agency->id, 'agent_id' => $agent->id, 'invoice_number' => $number, 'number_year' => 2026, 'number_seq' => $seq, 'invoice_date' => '2026-09-01', 'status' => 'pending']);
        }

        $this->actingAs($this->admin)->get(route('erp.invoices.index', ['agent_id' => $sabbir->id]))
            ->assertOk()->assertSee('INV-SABBIR')->assertDontSee('INV-MANIK');
    }

    public function test_embassy_list_index_accepts_agent_filter(): void
    {
        $agent = Agent::first();
        $this->actingAs($this->admin)->get(route('embassy-lists.index', ['agent_id' => $agent->id]))
            ->assertOk()->assertSee('All agents')->assertSee($agent->name);
    }
}
