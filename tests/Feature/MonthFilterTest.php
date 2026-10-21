<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\BmetEntry;
use App\Models\MofaEntry;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VisaStamping;
use App\Support\MonthRange;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** "Month" filter on the MOFA, Visa Stamping and BMET lists — list, CSV export and print share it. */
class MonthFilterTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = Agency::create(['name' => 'Alpha', 'slug' => 'alpha', 'status' => 'active']);
        Subscription::create(['agency_id' => $this->agency->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);
        $this->admin = User::create(['name' => 'A', 'email' => 'a@alpha.test', 'password' => Hash::make('secret1234'), 'agency_id' => $this->agency->id, 'is_super_admin' => false, 'is_active' => true]);
        $this->admin->assignRole('agency_admin');
    }

    public function test_month_bounds(): void
    {
        $this->assertSame(['2026-02-01', '2026-02-28'], MonthRange::bounds('2026-02'));
        $this->assertSame(['2024-02-01', '2024-02-29'], MonthRange::bounds('2024-02'));
        $this->assertSame(['2026-10-01', '2026-10-31'], MonthRange::bounds('2026-10'));
        $this->assertNull(MonthRange::bounds('2026-13'));
        $this->assertNull(MonthRange::bounds('garbage'));
        $this->assertNull(MonthRange::bounds(null));
    }

    public function test_mofa_month_filter_covers_list_export_and_print(): void
    {
        $base = ['agency_id' => $this->agency->id, 'father_name' => 'F', 'mother_name' => 'M', 'date_of_birth' => '1990-01-01', 'issue_date' => '2020-01-01', 'expiry_date' => '2030-01-01'];
        MofaEntry::create($base + ['full_name' => 'SEPT PAX', 'passport_number' => 'AA1111111', 'mofa_date' => '2026-09-30']);
        MofaEntry::create($base + ['full_name' => 'OCT PAX', 'passport_number' => 'BB2222222', 'mofa_date' => '2026-10-01']);
        MofaEntry::create($base + ['full_name' => 'OCT LATE PAX', 'passport_number' => 'CC3333333', 'mofa_date' => '2026-10-31']);
        MofaEntry::create($base + ['full_name' => 'NOV PAX', 'passport_number' => 'DD4444444', 'mofa_date' => '2026-11-01']);

        $this->actingAs($this->admin)->get(route('erp.mofa', ['month' => '2026-10']))
            ->assertOk()->assertSee('OCT PAX')->assertSee('OCT LATE PAX')->assertDontSee('SEPT PAX')->assertDontSee('NOV PAX')
            ->assertSee('month=2026-10', false); // print / export links keep the month
        $csv = $this->get(route('erp.mofa.export', ['month' => '2026-10']))->assertOk()->streamedContent();
        $this->assertStringContainsString('OCT PAX', $csv);
        $this->assertStringNotContainsString('NOV PAX', $csv);
        $this->assertStringNotContainsString('SEPT PAX', $csv);
        $this->get(route('erp.mofa.print', ['month' => '2026-10']))->assertOk();
    }

    public function test_stamping_month_filter(): void
    {
        $base = ['agency_id' => $this->agency->id, 'status' => 'pending'];
        VisaStamping::create($base + ['full_name' => 'SEPT STAMP', 'passport_number' => 'AA1111111', 'stamping_date' => '2026-09-15']);
        VisaStamping::create($base + ['full_name' => 'OCT STAMP', 'passport_number' => 'BB2222222', 'stamping_date' => '2026-10-15']);

        $this->actingAs($this->admin)->get(route('erp.visa-stamping.index', ['month' => '2026-10']))
            ->assertOk()->assertSee('OCT STAMP')->assertDontSee('SEPT STAMP');
        $csv = $this->get(route('erp.visa-stamping.export', ['month' => '2026-10']))->assertOk()->streamedContent();
        $this->assertStringContainsString('OCT STAMP', $csv);
        $this->assertStringNotContainsString('SEPT STAMP', $csv);
    }

    public function test_bmet_month_filter(): void
    {
        foreach ([['SEPT BMET', 'AA1111111', '2026-09-20'], ['OCT BMET', 'BB2222222', '2026-10-20']] as [$name, $pp, $date]) {
            BmetEntry::create(['agency_id' => $this->agency->id, 'full_name' => $name, 'passport_number' => $pp, 'ec_date' => $date, 'status' => 'pending']);
        }

        $this->actingAs($this->admin)->get(route('erp.bmet.index', ['month' => '2026-10']))
            ->assertOk()->assertSee('OCT BMET')->assertDontSee('SEPT BMET');
        $csv = $this->get(route('erp.bmet.export', ['month' => '2026-10']))->assertOk()->streamedContent();
        $this->assertStringContainsString('OCT BMET', $csv);
        $this->assertStringNotContainsString('SEPT BMET', $csv);
    }

    public function test_an_invalid_month_is_rejected_on_mofa_and_ignored_elsewhere(): void
    {
        $this->actingAs($this->admin)->get(route('erp.mofa', ['month' => '2026-13']))->assertSessionHasErrors('month');
        $this->get(route('erp.visa-stamping.index', ['month' => 'nope']))->assertOk();
    }

    public function test_invoice_and_voucher_lists_filter_by_month_and_offer_print_all(): void
    {
        $this->actingAs($this->admin);
        foreach ([['2026-09-30', 'SEPT BILL'], ['2026-10-05', 'OCT BILL ONE'], ['2026-10-20', 'OCT BILL TWO']] as $i => [$date, $name]) {
            $this->post(route('erp.invoices.store'), [
                'invoice_date' => $date, 'bill_to_name' => $name, 'status' => 'pending', 'currency' => 'BDT', 'tax_type' => 'none', 'discount_type' => 'none',
                'items' => [['passenger_name' => 'P'.$i, 'passport_no' => 'A00000'.$i, 'processing_fee' => '1000', 'mofa_fee' => '0']],
            ])->assertRedirect();
        }
        $res = $this->get(route('erp.invoices.index', ['month' => '2026-10']))->assertOk()
            ->assertSee('OCT BILL ONE')->assertSee('OCT BILL TWO')->assertDontSee('SEPT BILL')
            ->assertSee('Print all 2');
        $this->assertCount(2, $res->viewData('printIds'));
        // No month → no print-all bar.
        $this->get(route('erp.invoices.index'))->assertOk()->assertDontSee('Print all');

        foreach ([['2026-09-30', 'SEPT PAYEE'], ['2026-10-12', 'OCT PAYEE']] as [$date, $payee]) {
            $this->post(route('erp.payment-vouchers.store'), [
                'voucher_date' => $date, 'payee_type' => 'party', 'payee_name' => $payee, 'payment_method' => 'cash',
                'tax_type' => 'none', 'discount_type' => 'none', 'description' => 'Cost',
                'expense_head_id' => \App\Models\ExpenseHead::forAgency($this->agency->id)->value('id'),
                'items' => [['description' => 'Cost', 'quantity' => 1, 'unit_price' => '500']],
            ])->assertSessionHasNoErrors();
        }
        $this->get(route('erp.payment-vouchers.index', ['month' => '2026-10']))->assertOk()
            ->assertSee('OCT PAYEE')->assertDontSee('SEPT PAYEE');
    }
}
