<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Agency license page: two-column details + payment card (payment rows only when Super Admin set them). */
class LicensePageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $slug = 'alpha', array $agency = []): User
    {
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::firstOrCreate(['slug' => 'test'], ['name' => 'Test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $a = Agency::create($agency + ['name' => ucfirst($slug) . ' Agency', 'slug' => $slug, 'status' => 'active', 'rl_number' => '1001', 'owner_name' => 'Sabbir Howlader']);
        Subscription::create(['agency_id' => $a->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);
        $u = User::create(['name' => 'Admin', 'email' => "admin@{$slug}.test", 'password' => Hash::make('secret1234'), 'agency_id' => $a->id, 'is_super_admin' => false, 'is_active' => true]);
        $u->assignRole('agency_admin');

        return $u;
    }

    public function test_details_card_shows_the_agencys_own_license(): void
    {
        $u = $this->admin('alpha', ['license_expiry_date' => now()->addDays(100)]);
        $this->admin('beta', ['name' => 'Beta Secret Agency']);

        $this->actingAs($u)->get(route('license.index'))->assertOk()
            ->assertSee('License Information')->assertSee('License Details')
            ->assertSee('Sabbir Howlader')->assertSee('Alpha Agency')->assertSee('1001')
            ->assertSee('Next Renewal Due Date')->assertSee('100 days')->assertSee('Active')
            ->assertDontSee('Beta Secret Agency')
            // Same two-column layout even before Super Admin adds payment details — no fake data, just a hint.
            ->assertSee('Payment Information')->assertSee('Payment details have not been added yet')
            ->assertSee('Last Renewal Date');
    }

    public function test_payment_card_appears_only_with_configured_details(): void
    {
        $u = $this->admin();
        Setting::set('renewal_amount', '500');
        Setting::set('bkash_number', '01700000000');

        $this->actingAs($u)->get(route('license.index'))->assertOk()
            ->assertSee('Payment Information')->assertSee('Renewal Amount')->assertSee('500')
            ->assertSee('bKash')->assertSee('01700000000')
            ->assertDontSee('Nagad')->assertDontSee('Bank Account Details')
            ->assertDontSee('Payment details have not been added yet');
    }

    public function test_super_admin_extending_the_license_records_the_renewal_date(): void
    {
        $u = $this->admin('alpha', ['license_expiry_date' => '2027-01-01']);
        $agency = $u->agency;
        $root = User::create(['name' => 'Root', 'email' => 'root@ksa.test', 'password' => Hash::make('secret1234'), 'is_super_admin' => true, 'is_active' => true]);

        $base = ['name' => $agency->name, 'print_logo' => 0, 'status' => 'active'];
        // Same expiry → not a renewal.
        $this->actingAs($root)->put(route('super-admin.agencies.update', $agency), $base + ['license_expiry_date' => '2027-01-01'])->assertRedirect();
        $this->assertNull($agency->fresh()->license_renewed_at);

        // Later expiry → renewal recorded as today and shown on the agency's page.
        $this->actingAs($root)->put(route('super-admin.agencies.update', $agency), $base + ['license_expiry_date' => '2028-01-01'])->assertRedirect();
        $this->assertSame(today()->format('Y-m-d'), $agency->fresh()->license_renewed_at->format('Y-m-d'));
        $this->actingAs(User::find($u->id))->get(route('license.index'))->assertSee(today()->format('d M Y'));
    }
}
