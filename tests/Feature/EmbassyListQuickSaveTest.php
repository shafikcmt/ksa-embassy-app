<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\EmbassyList;
use App\Models\HrProfile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** "Save & Print" / "Save & Finalize" on the create form run the normal finalize step in one request. */
class EmbassyListQuickSaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private HrProfile $hr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true, 'max_embassy_lists_monthly' => 60]);
        $agency = Agency::create(['name' => 'Alpha Agency', 'slug' => 'alpha', 'status' => 'active']);
        Subscription::create(['agency_id' => $agency->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);
        $this->admin = User::create(['name' => 'A', 'email' => 'admin@alpha.test', 'password' => Hash::make('secret1234'), 'agency_id' => $agency->id, 'is_super_admin' => false, 'is_active' => true]);
        $this->admin->assignRole('agency_admin');
        $this->hr = HrProfile::create(['agency_id' => $agency->id, 'full_name_en' => 'Test Pax', 'status' => 'active', 'nationality' => 'Bangladeshi', 'date_of_birth' => '1990-01-01', 'gender' => 'male']);
    }

    private function store(array $extra = [])
    {
        return $this->actingAs($this->admin)->post(route('embassy-lists.store'), $extra + [
            'list_date' => '2026-10-12',
            'items' => [['hr_profile_id' => $this->hr->id, 'category' => 'new']],
        ]);
    }

    public function test_plain_save_stays_a_draft(): void
    {
        $this->store()->assertRedirect();
        $list = EmbassyList::firstOrFail();
        $this->assertSame('draft', $list->status);
        $this->assertSame('active', $this->hr->fresh()->status);
    }

    public function test_save_and_print_finalizes_and_goes_to_print(): void
    {
        $res = $this->store(['after' => 'print']);
        $list = EmbassyList::firstOrFail();
        $res->assertRedirect(route('embassy-lists.print', $list));
        $this->assertSame('finalized', $list->status);
        $this->assertSame('listed', $this->hr->fresh()->status);
        $this->assertSame(1, $list->items()->first()->serial_no);
    }

    public function test_save_and_finalize_goes_to_the_list(): void
    {
        $res = $this->store(['after' => 'finalize']);
        $list = EmbassyList::firstOrFail();
        $res->assertRedirect(route('embassy-lists.show', $list));
        $this->assertSame('finalized', $list->status);
    }
}
