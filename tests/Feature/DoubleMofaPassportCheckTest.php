<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsurePageAccess;
use App\Models\Agency;
use App\Models\Delivery;
use App\Models\DoubleMofa;
use App\Models\ManpowerCompletion;
use App\Models\Medical;
use App\Models\MofaEntry;
use App\Models\Stamping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Double MOFA cross-module passport warning (warn-and-confirm, never a hard block).
 *
 * Subscription + per-staff page gating are bypassed here (covered elsewhere);
 * auth + agency-access (tenancy) stay ON.
 *
 * These tests must run on MySQL (project setup): a pre-existing MySQL-only
 * migration breaks the SQLite default for RefreshDatabase feature tests.
 */
class DoubleMofaPassportCheckTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureActiveSubscription::class, EnsurePageAccess::class]);

        $this->agency = $this->makeAgency('alpha');
        $this->user = User::factory()->create(['agency_id' => $this->agency->id, 'is_active' => true]);
    }

    private function makeAgency(string $slug): Agency
    {
        return Agency::create([
            'name'           => ucfirst($slug) . ' Agency',
            'slug'           => $slug,
            'status'         => 'active',
            'license_number' => 'LIC-' . strtoupper($slug) . '-0001',
        ]);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'mofa_date'      => '2026-09-01',
            'full_name'      => 'Test Applicant',
            'passport_no'    => 'A1234567',
            'billing_amount' => '500.00',
        ];
    }

    private function base(array $extra = []): array
    {
        return $extra + ['agency_id' => $this->agency->id, 'full_name' => 'Rahim Uddin', 'passport_no' => 'A1234567'];
    }

    private function records(string $passport)
    {
        return $this->actingAs($this->user)->getJson(route('erp.passport-records', ['passport_no' => $passport]));
    }

    public function test_passport_not_found_anywhere_saves_normally(): void
    {
        $this->records('A1234567')->assertOk()->assertJson(['found' => false, 'matches' => []]);

        $this->actingAs($this->user)->post(route('erp.double-mofa.store'), $this->payload())
            ->assertRedirect(route('erp.double-mofa'))
            ->assertSessionMissing('passport_matches');

        $this->assertSame(1, DoubleMofa::count());
    }

    public function test_passport_only_in_medical_warns_and_does_not_save(): void
    {
        Medical::create($this->base(['father_name' => 'X', 'medical_status' => 'fit']));

        $this->records(' a1234567 ')->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.module', 'Medical')
            ->assertJsonPath('matches.0.status', 'Fit')
            ->assertJsonPath('matches.0.name', 'Rahim Uddin');

        $this->actingAs($this->user)->from(route('erp.double-mofa'))
            ->post(route('erp.double-mofa.store'), $this->payload())
            ->assertRedirect(route('erp.double-mofa'))
            ->assertSessionHas('passport_matches', fn ($m) => count($m) === 1 && $m[0]['module'] === 'Medical');

        $this->assertSame(0, DoubleMofa::count());
    }

    public function test_passport_only_in_mofa_entry_shows_mofa_number(): void
    {
        MofaEntry::create($this->base(['mofa_date' => '2026-08-01', 'mofa_number' => 'MOFA-55555']));

        $this->records('A1234567')->assertOk()
            ->assertJsonCount(1, 'matches')
            ->assertJsonPath('matches.0.module', 'MOFA Entry')
            ->assertJsonPath('matches.0.reference', 'MOFA #: MOFA-55555');
    }

    public function test_passport_in_multiple_modules_lists_every_match(): void
    {
        Medical::create($this->base(['father_name' => 'X', 'medical_status' => 'fit']));
        MofaEntry::create($this->base(['mofa_date' => '2026-08-01', 'mofa_number' => 'MOFA-1']));
        Stamping::create($this->base(['stamp_date' => '2026-08-05', 'status' => 'pending']));
        ManpowerCompletion::create(['agency_id' => $this->agency->id, 'customer_name' => 'Rahim', 'passport_no' => 'a1234567', 'completed_date' => '2026-08-06']);
        Delivery::create($this->base(['delivery_date' => '2026-08-07', 'total_amount' => 100]));

        $modules = collect($this->records('A1234567')->assertOk()->json('matches'))->pluck('module')->all();

        $this->assertSame(['Medical', 'MOFA Entry', 'Visa Stamping', 'BMET Clearance', 'Delivery'], $modules);
    }

    public function test_passport_already_in_double_mofa_warns(): void
    {
        DoubleMofa::create($this->base(['mofa_date' => '2026-08-01', 'billing_amount' => 500, 'old_mofa_number' => 'OLD-9']));

        $this->records('A1234567')->assertJsonPath('matches.0.module', 'Double MOFA')
            ->assertJsonPath('matches.0.reference', 'Old MOFA #: OLD-9');

        $this->actingAs($this->user)->post(route('erp.double-mofa.store'), $this->payload())
            ->assertSessionHas('passport_matches');
        $this->assertSame(1, DoubleMofa::count());
    }

    public function test_continue_anyway_saves_after_confirmation(): void
    {
        MofaEntry::create($this->base(['mofa_date' => '2026-08-01']));
        DoubleMofa::create($this->base(['mofa_date' => '2026-08-01', 'billing_amount' => 500]));

        $this->actingAs($this->user)->post(route('erp.double-mofa.store'), $this->payload(['confirm_duplicate' => '1']))
            ->assertRedirect(route('erp.double-mofa'))
            ->assertSessionMissing('passport_matches')
            ->assertSessionHas('success');

        $this->assertSame(2, DoubleMofa::count());
    }

    public function test_confirm_zero_still_warns_and_validation_still_applies(): void
    {
        MofaEntry::create($this->base(['mofa_date' => '2026-08-01']));

        $this->actingAs($this->user)->post(route('erp.double-mofa.store'), $this->payload(['confirm_duplicate' => '0']))
            ->assertSessionHas('passport_matches');

        // Confirmation never bypasses normal validation.
        $this->actingAs($this->user)->post(route('erp.double-mofa.store'), ['confirm_duplicate' => '1', 'passport_no' => ''])
            ->assertSessionHasErrors(['passport_no', 'full_name', 'mofa_date', 'billing_amount']);

        $this->assertSame(0, DoubleMofa::count());
    }

    public function test_other_agency_records_are_not_matched(): void
    {
        $other = $this->makeAgency('beta');
        MofaEntry::create(['agency_id' => $other->id, 'full_name' => 'Other', 'passport_no' => 'A1234567', 'mofa_date' => '2026-08-01']);

        $this->records('A1234567')->assertJson(['found' => false]);

        $this->actingAs($this->user)->post(route('erp.double-mofa.store'), $this->payload())
            ->assertSessionMissing('passport_matches');
        $this->assertSame(1, DoubleMofa::forAgency($this->agency->id)->count());
    }

    public function test_other_modules_keep_their_own_duplicate_rules(): void
    {
        Medical::create($this->base(['father_name' => 'X', 'medical_status' => 'fit']));

        $this->actingAs($this->user)->post(route('erp.medical.store'), [
            'full_name' => 'Rahim Uddin', 'father_name' => 'X', 'passport_no' => 'A1234567', 'medical_status' => 'fit',
        ])->assertSessionHas('duplicate_warning');

        $this->assertSame(1, Medical::count());
    }
}
