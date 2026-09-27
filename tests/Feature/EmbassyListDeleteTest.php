<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\EmbassyList;
use App\Models\HrProfile;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Embassy list soft delete — status rule, tenancy, double submit, numbering,
 * re-adding candidates, HR history page.
 *
 * MySQL only (see project test-DB note):
 *   DB_CONNECTION=mysql DB_DATABASE=ksa_embassy_test php artisan test tests/Feature/EmbassyListDeleteTest.php
 */
class EmbassyListDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private Agency $other;
    private User $admin;
    private User $staff;
    private User $otherAdmin;
    private HrProfile $hr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true, 'max_embassy_lists_monthly' => 60]);

        $this->agency = $this->makeAgency('alpha', $plan);
        $this->other  = $this->makeAgency('beta', $plan);

        $this->admin      = $this->makeUser($this->agency, 'admin@alpha.test', 'agency_admin');
        $this->staff      = $this->makeUser($this->agency, 'staff@alpha.test', 'agency_staff');
        $this->staff->givePermissionTo('access_embassy_list');
        $this->otherAdmin = $this->makeUser($this->other, 'admin@beta.test', 'agency_admin');

        $this->hr = HrProfile::create([
            'agency_id' => $this->agency->id, 'full_name_en' => 'Test Pax', 'status' => 'active',
            'nationality' => 'Bangladeshi', 'date_of_birth' => '1990-01-01', 'gender' => 'male',
        ]);
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

    private function createList(): EmbassyList
    {
        $this->actingAs($this->admin)->post(route('embassy-lists.store'), [
            'list_date' => now()->toDateString(),
            'items'     => [['hr_profile_id' => $this->hr->id, 'category' => 'new']],
        ])->assertRedirect();

        return EmbassyList::latest('id')->firstOrFail();
    }

    /** The Alpine hook the index Delete button renders (destroy URL == show URL, so match the hook). */
    private function deleteHook(EmbassyList $list): string
    {
        return "del.action = '" . route('embassy-lists.destroy', $list) . "'";
    }

    public function test_admin_sees_delete_button_on_draft(): void
    {
        $list = $this->createList();

        $this->actingAs($this->admin)->get(route('embassy-lists.index'))
            ->assertOk()->assertSee($this->deleteHook($list), false);
    }

    public function test_draft_list_is_soft_deleted_and_audited(): void
    {
        $list = $this->createList();

        $this->actingAs($this->admin)->delete(route('embassy-lists.destroy', $list))
            ->assertRedirect(route('embassy-lists.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($list);
        $this->assertDatabaseHas('embassy_list_items', ['embassy_list_id' => $list->id]);
        $this->assertTrue(AuditLog::where('action', 'delete')->where('auditable_id', $list->id)->exists());
        $this->assertSame('active', $this->hr->fresh()->status);

        $this->actingAs($this->admin)->get(route('embassy-lists.index'))
            ->assertOk()->assertDontSee('href="' . route('embassy-lists.show', $list) . '"', false);
    }

    public function test_cancelled_list_can_be_deleted(): void
    {
        $list = $this->createList();
        $list->update(['status' => 'cancelled']);

        $this->actingAs($this->admin)->delete(route('embassy-lists.destroy', $list))->assertSessionHas('success');
        $this->assertSoftDeleted($list);
    }

    public function test_finalized_and_printed_lists_are_rejected(): void
    {
        foreach (['finalized', 'printed'] as $status) {
            $list = $this->createList();
            $list->update(['status' => $status]);

            $this->actingAs($this->admin)->get(route('embassy-lists.index'))
                ->assertOk()->assertDontSee($this->deleteHook($list), false);

            $this->actingAs($this->admin)->delete(route('embassy-lists.destroy', $list))
                ->assertRedirect()->assertSessionHas('error');
            $this->assertNotSoftDeleted($list);
        }
    }

    public function test_double_submit_is_harmless(): void
    {
        $list = $this->createList();

        $this->actingAs($this->admin)->delete(route('embassy-lists.destroy', $list))->assertSessionHas('success');
        $this->actingAs($this->admin)->delete(route('embassy-lists.destroy', $list))
            ->assertRedirect(route('embassy-lists.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, AuditLog::where('action', 'delete')->where('auditable_id', $list->id)->count());
    }

    public function test_other_agency_gets_404(): void
    {
        $list = $this->createList();

        $this->actingAs($this->otherAdmin)->delete(route('embassy-lists.destroy', $list))->assertNotFound();
        $this->assertNotSoftDeleted($list);
    }

    public function test_staff_cannot_delete_or_see_button(): void
    {
        $list = $this->createList();

        $this->actingAs($this->staff)->get(route('embassy-lists.index'))
            ->assertOk()->assertDontSee($this->deleteHook($list), false);

        $this->actingAs($this->staff)->delete(route('embassy-lists.destroy', $list))->assertForbidden();
        $this->assertNotSoftDeleted($list);
    }

    public function test_list_number_is_not_reused_after_deleting_latest(): void
    {
        $first  = $this->createList();
        $second = $this->createList();

        $this->actingAs($this->admin)->delete(route('embassy-lists.destroy', $second))->assertSessionHas('success');

        $third = $this->createList();

        $prefix = 'EL-' . now()->year . '-';
        $this->assertSame($prefix . '0001', $first->list_no);
        $this->assertSame($prefix . '0002', $second->list_no);
        $this->assertSame($prefix . '0003', $third->list_no);
    }

    public function test_list_number_ignores_previous_years(): void
    {
        EmbassyList::create([
            'agency_id' => $this->agency->id, 'list_no' => 'EL-' . (now()->year - 1) . '-0042',
            'list_date' => now()->subYear()->toDateString(), 'status' => 'draft',
        ]);

        $this->assertSame('EL-' . now()->year . '-0001', $this->createList()->list_no);
    }

    public function test_candidate_from_deleted_draft_can_join_new_list_and_hr_page_renders(): void
    {
        $deleted = $this->createList();
        $this->actingAs($this->admin)->delete(route('embassy-lists.destroy', $deleted));

        // Builder no longer flags the candidate as "already in a list".
        $this->actingAs($this->admin)->getJson(route('embassy-lists.available-hr'))
            ->assertOk()
            ->assertJsonFragment(['id' => $this->hr->id, 'in_active_list' => false]);

        $fresh = $this->createList();
        $this->assertSame(1, $fresh->items()->where('hr_profile_id', $this->hr->id)->count());

        $this->actingAs($this->admin)->get(route('hr.show', $this->hr))
            ->assertOk()
            ->assertSee($fresh->list_no)
            ->assertDontSee($deleted->list_no);
    }
}
