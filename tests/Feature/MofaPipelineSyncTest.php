<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\BmetEntry;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\MofaEntry;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VisaStamping;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** MOFA entry → Double MOFA / Visa Stamping / BMET / Delivery auto create + update sync. */
class MofaPipelineSyncTest extends TestCase
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

    public function test_new_mofa_entry_creates_pending_rows_in_stamping_bmet_and_delivery(): void
    {
        Agent::create(['agency_id' => $this->agency->id, 'name' => 'Agent', 'phone' => '01700000000', 'address' => 'Dhaka', 'status' => 'active']);
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload(['visa_number' => 'V-1', 'id_number' => 'ID-1']))->assertOk();
        $entry = MofaEntry::firstOrFail();

        $s = VisaStamping::where('mofa_entry_id', $entry->id)->sole();
        $this->assertSame(['Test Passenger', 'Test Father', 'Test Mother', 'AB1234567', 'V-1', 'ID-1', 'MOFA-100', 'pending', $this->agency->id],
            [$s->full_name, $s->father_name, $s->mother_name, $s->passport_no, $s->visa_number, $s->id_number, $s->mofa_number, $s->status, $s->agency_id]);
        $this->assertSame('2026-09-25', $s->mofa_date->format('Y-m-d'));
        $this->assertNotNull($s->agent_id);

        $b = BmetEntry::where('mofa_entry_id', $entry->id)->sole();
        $this->assertSame(['Test Passenger', 'AB1234567', 'V-1', 'pending'], [$b->full_name, $b->passport_no, $b->visa_number, $b->status]);

        $d = Delivery::where('mofa_entry_id', $entry->id)->sole();
        $this->assertSame(['Test Passenger', 'AB1234567', 'V-1', 'pending', 'Agent'], [$d->full_name, $d->passport_no, $d->visa_serial, $d->status, $d->reference]);

        // First MOFA for this passport is not a "double" MOFA.
        $this->assertSame(0, DoubleMofa::count());
    }

    public function test_editing_mofa_updates_shared_fields_but_keeps_manual_fields(): void
    {
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload())->assertOk();
        $entry = MofaEntry::firstOrFail();

        // User fills module-own fields by hand.
        VisaStamping::where('mofa_entry_id', $entry->id)->sole()->update(['status' => 'stamped', 'issued_visa_number' => 'IV-9']);
        BmetEntry::where('mofa_entry_id', $entry->id)->sole()->update(['ec_number' => 'EC-5', 'status' => 'cleared']);
        Delivery::where('mofa_entry_id', $entry->id)->sole()->update(['total_amount' => 5000, 'status' => 'ready']);

        $this->putJson(route('erp.mofa.update', $entry), $this->payload(['full_name' => 'Renamed', 'passport_number' => 'ZZ7654321', 'visa_number' => 'V-2']))->assertOk();

        $s = VisaStamping::where('mofa_entry_id', $entry->id)->sole();
        $this->assertSame(['Renamed', 'ZZ7654321', 'V-2', 'stamped', 'IV-9'], [$s->full_name, $s->passport_no, $s->visa_number, $s->status, $s->issued_visa_number]);
        $b = BmetEntry::where('mofa_entry_id', $entry->id)->sole();
        $this->assertSame(['Renamed', 'ZZ7654321', 'EC-5', 'cleared'], [$b->full_name, $b->passport_no, $b->ec_number, $b->status]);
        $d = Delivery::where('mofa_entry_id', $entry->id)->sole();
        $this->assertSame(['Renamed', 'ZZ7654321', '5000.00', 'ready'], [$d->full_name, $d->passport_no, $d->total_amount, $d->status]);
        $this->assertSame(1, VisaStamping::count() + DoubleMofa::count());
    }

    public function test_existing_manual_row_for_the_passport_is_linked_not_duplicated(): void
    {
        $manual = Delivery::create(['agency_id' => $this->agency->id, 'delivery_date' => '2026-09-01', 'full_name' => 'Old Name', 'passport_no' => 'AB1234567', 'total_amount' => 300, 'status' => 'ready']);
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload())->assertOk();

        $this->assertSame(1, Delivery::count());
        $manual->refresh();
        $this->assertSame([MofaEntry::firstOrFail()->id, 'Test Passenger', '300.00', 'ready'], [$manual->mofa_entry_id, $manual->full_name, $manual->total_amount, $manual->status]);
    }

    public function test_repeat_mofa_for_a_passport_creates_a_double_mofa_row_and_moves_the_links(): void
    {
        $this->actingAs($this->staff);
        $this->postJson(route('erp.mofa.store'), $this->payload(['mofa_number' => 'M-1']))->assertOk();
        $this->postJson(route('erp.mofa.store'), $this->payload(['mofa_number' => 'M-2', 'mofa_date' => '2026-10-01']))->assertOk();
        [$first, $second] = MofaEntry::orderBy('id')->get()->all();

        $dm = DoubleMofa::sole();
        $this->assertSame([$second->id, 'M-1', 'AB1234567', '2026-10-01', 'unpaid'], [$dm->mofa_entry_id, $dm->old_mofa_number, $dm->passport_no, $dm->mofa_date->format('Y-m-d'), $dm->status]);

        // One Stamping / BMET / Delivery row per passport, now following the newest MOFA.
        $this->assertSame([$second->id], VisaStamping::pluck('mofa_entry_id')->all());
        $this->assertSame([$second->id], BmetEntry::pluck('mofa_entry_id')->all());
        $this->assertSame([$second->id], Delivery::pluck('mofa_entry_id')->all());
        $this->assertSame('M-2', VisaStamping::sole()->mofa_number);

        // Editing the older entry no longer changes the newest MOFA's rows.
        $this->putJson(route('erp.mofa.update', $first), $this->payload(['mofa_number' => 'M-1', 'full_name' => 'Old Edit']))->assertOk();
        $this->assertSame('Test Passenger', VisaStamping::sole()->full_name);
    }

    public function test_deleting_mofa_keeps_downstream_rows_and_only_unlinks(): void
    {
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload())->assertOk();
        $entry = MofaEntry::firstOrFail();
        $this->delete(route('erp.mofa.destroy', $entry))->assertRedirect();

        $this->assertSame([null], VisaStamping::pluck('mofa_entry_id')->all());
        $this->assertSame([null], BmetEntry::pluck('mofa_entry_id')->all());
        $this->assertSame([null], Delivery::pluck('mofa_entry_id')->all());
    }

    public function test_user_deleted_stamping_row_is_not_recreated_on_mofa_edit(): void
    {
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload())->assertOk();
        $entry = MofaEntry::firstOrFail();
        VisaStamping::sole()->delete();

        $this->putJson(route('erp.mofa.update', $entry), $this->payload(['full_name' => 'Again']))->assertOk();
        $this->assertSame(0, VisaStamping::count());
    }

    public function test_sync_is_agency_scoped(): void
    {
        Delivery::create(['agency_id' => $this->other->id, 'delivery_date' => '2026-09-01', 'full_name' => 'Foreign', 'passport_no' => 'AB1234567', 'status' => 'ready']);
        $this->actingAs($this->staff)->postJson(route('erp.mofa.store'), $this->payload())->assertOk();

        $this->assertSame('Foreign', Delivery::forAgency($this->other->id)->sole()->full_name);
        $this->assertNull(Delivery::forAgency($this->other->id)->sole()->mofa_entry_id);
        $this->assertSame(1, Delivery::forAgency($this->agency->id)->count());
    }

    public function test_backfill_command_links_existing_entries_once(): void
    {
        MofaEntry::create($this->payload() + ['agency_id' => $this->agency->id]);

        $this->artisan('erp:sync-mofa-pipeline')->assertSuccessful();
        $this->artisan('erp:sync-mofa-pipeline')->assertSuccessful();

        $this->assertSame(1, VisaStamping::count());
        $this->assertSame(1, BmetEntry::count());
        $this->assertSame(1, Delivery::count());
        $this->assertSame(0, DoubleMofa::count());
    }
}
