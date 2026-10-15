<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\MofaEntry;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** A MOFA entry saved (or backfilled) with blank candidate fields gets them from the passport's other records. */
class MofaAutofillOnSaveTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Agency $other;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = $this->agency('alpha', $plan);
        $this->other = $this->agency('beta', $plan);
        $this->admin = User::create(['name' => 'A', 'email' => 'a@alpha.test', 'password' => Hash::make('secret1234'), 'agency_id' => $this->agency->id, 'is_super_admin' => false, 'is_active' => true]);
        $this->admin->assignRole('agency_admin');
    }

    private function agency(string $slug, Plan $plan): Agency
    {
        $a = Agency::create(['name' => ucfirst($slug), 'slug' => $slug, 'status' => 'active']);
        Subscription::create(['agency_id' => $a->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);

        return $a;
    }

    private function existing(Agency $agency, array $extra = []): MofaEntry
    {
        return MofaEntry::create($extra + [
            'agency_id' => $agency->id, 'full_name' => 'SHANTO HOWLADER', 'passport_number' => 'A04886630', 'passport_no' => 'A04886630',
            'father_name' => 'SUSHIL', 'mother_name' => 'DIPA', 'date_of_birth' => '2002-12-20', 'issue_date' => '2022-09-28', 'expiry_date' => '2032-09-27',
            'visa_number' => '1306344922', 'id_number' => '7023490084', 'mofa_date' => '2026-09-12', 'mofa_number' => 'E825125229',
        ]);
    }

    private function blankRow(Agency $agency, array $extra = []): MofaEntry
    {
        return MofaEntry::create($extra + ['agency_id' => $agency->id, 'full_name' => 'SANTO HOWLADER', 'passport_number' => 'A04886630', 'passport_no' => 'A04886630', 'mofa_date' => '2026-09-02', 'mofa_number' => 'E824469156']);
    }

    public function test_fill_missing_fills_blanks_only_and_never_overwrites(): void
    {
        $this->existing($this->agency);
        $svc = app(\App\Services\ErpPassportDataService::class);
        $out = $svc->fillMissing(['father_name' => 'TYPED', 'mother_name' => '', 'passport_number' => 'a04886630'], $this->agency->id, 'a04886630', \App\Http\Controllers\Erp\MofaController::AUTOFILL_MAP);

        $this->assertSame('TYPED', $out['father_name']);
        $this->assertSame('DIPA', $out['mother_name']);
        $this->assertSame('1306344922', $out['visa_number']);
        $this->assertSame('2022-09-28', $out['issue_date']);
        $this->assertArrayNotHasKey('mofa_number', $out, 'entry-specific MOFA no is never copied');
    }

    public function test_other_agencies_data_is_never_used(): void
    {
        $this->existing($this->other);
        $out = app(\App\Services\ErpPassportDataService::class)->fillMissing([], $this->agency->id, 'A04886630', \App\Http\Controllers\Erp\MofaController::AUTOFILL_MAP);

        $this->assertSame([], $out);
    }

    public function test_backfill_command_fills_old_blank_rows_only(): void
    {
        $this->existing($this->agency);
        $blank = $this->blankRow($this->agency, ['father_name' => 'KEEP']);

        $this->artisan('erp:backfill-mofa-blanks', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($blank->fresh()->mother_name);

        $this->artisan('erp:backfill-mofa-blanks')->assertSuccessful();
        $blank = $blank->fresh();
        $this->assertSame('KEEP', $blank->father_name);
        $this->assertSame('DIPA', $blank->mother_name);
        $this->assertSame('1306344922', $blank->visa_number);
        $this->assertSame('E824469156', $blank->mofa_number);
    }
}
