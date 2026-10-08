<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\Invoice;
use App\Models\PaymentVoucher;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentVoucherService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ERP Payment Vouchers — numbering, integer-cent totals, draft → approved → paid
 * workflow, admin-only money actions, tenancy, soft delete, audit log, PDF.
 * MySQL only (see project test-DB note).
 */
class PaymentVoucherTest extends TestCase
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
        $this->other  = $this->makeAgency('beta', $plan);

        $this->admin      = $this->makeUser($this->agency, 'admin@alpha.test', 'agency_admin');
        $this->staff      = $this->makeUser($this->agency, 'staff@alpha.test', 'agency_staff');
        $this->staff->givePermissionTo('access_erp');
        $this->otherAdmin = $this->makeUser($this->other, 'admin@beta.test', 'agency_admin');
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'expense_head_id' => ExpenseHead::forAgency($this->agency->id)->where('code', 'manpower')->value('id'),
            'voucher_date'   => '2026-09-24',
            'payee_type'     => 'party',
            'payee_name'     => 'Dhaka Manpower Ltd',
            'payee_phone'    => '01700000000',
            'description'    => 'September batch manpower clearance',
            'payment_method' => 'cash',
            'tax_type'       => 'percent',
            'tax_value'      => '5',
            'discount_type'  => 'fixed',
            'discount_value' => '1000',
            'items'          => [
                ['description' => 'Manpower Fee', 'quantity' => 10, 'unit_price' => '5000'],
                ['description' => 'Processing', 'quantity' => 1, 'unit_price' => '5000.10', 'remarks' => 'Rush'],
            ],
        ], $overrides);
    }

    private function createAs(User $user, array $overrides = []): PaymentVoucher
    {
        $headId = ExpenseHead::forAgency($user->agency_id)->where('code', 'manpower')->value('id');
        $this->actingAs($user)->post(route('erp.payment-vouchers.store'), $this->payload($overrides + ['expense_head_id' => $headId]))->assertRedirect()->assertSessionHasNoErrors();

        return PaymentVoucher::latest('id')->firstOrFail();
    }

    public function test_staff_can_open_create_and_store_a_draft_with_server_totals(): void
    {
        $this->actingAs($this->staff)->get(route('erp.payment-vouchers.create'))->assertOk();

        $v = $this->createAs($this->staff, ['total_amount' => '1.00', 'status' => 'paid']); // hostile fields ignored

        $this->assertSame('draft', $v->status);
        $this->assertSame("VCH-{$this->agency->id}-2026-0001", $v->voucher_number);
        $this->assertSame('55000.10', (string) $v->subtotal);
        $this->assertSame('2750.01', (string) $v->tax_amount);       // 2750.005 → half-up
        $this->assertSame('1000.00', (string) $v->discount_amount);
        $this->assertSame('56750.11', (string) $v->total_amount);
        $this->assertSame(['50000.00', '5000.10'], $v->items->pluck('amount')->map(fn ($a) => (string) $a)->all());
        $this->assertTrue(AuditLog::where('action', 'voucher_created')->where('auditable_id', $v->id)->exists());
    }

    public function test_numbering_is_per_agency_and_never_reuses_deleted_numbers(): void
    {
        $first = $this->createAs($this->admin);
        $this->actingAs($this->admin)->delete(route('erp.payment-vouchers.destroy', $first))->assertRedirect();
        $this->assertSoftDeleted('payment_vouchers', ['id' => $first->id]);

        $this->assertStringEndsWith('-0002', $this->createAs($this->admin)->voucher_number);
        $this->assertSame("VCH-{$this->other->id}-2026-0001", $this->createAs($this->otherAdmin)->voucher_number);
    }

    public function test_full_workflow_draft_approved_paid_is_admin_only_and_locks(): void
    {
        $v = $this->createAs($this->staff);

        // Staff can't approve; admin can't pay a draft.
        $this->actingAs($this->staff)->patch(route('erp.payment-vouchers.approve', $v))->assertForbidden();
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.mark-paid', $v), ['payment_method' => 'cash', 'payment_date' => '2026-09-24'])->assertForbidden();

        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.approve', $v))->assertRedirect();
        $v->refresh();
        $this->assertSame('approved', $v->status);
        $this->assertSame($this->admin->id, $v->approved_by);

        // Approved = no longer editable.
        $this->actingAs($this->staff)->put(route('erp.payment-vouchers.update', $v), $this->payload())->assertForbidden();

        // Staff can't pay; cheque needs a number.
        $this->actingAs($this->staff)->patch(route('erp.payment-vouchers.mark-paid', $v), ['payment_method' => 'cash', 'payment_date' => '2026-09-24'])->assertForbidden();
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.mark-paid', $v), ['payment_method' => 'cheque', 'payment_date' => '2026-09-24'])
            ->assertSessionHasErrors('cheque_number');

        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.mark-paid', $v), [
            'payment_method' => 'cheque', 'payment_date' => '2026-09-24', 'cheque_number' => 'CHQ-778', 'bank_name' => 'Sonali Bank',
        ])->assertRedirect();

        $v->refresh();
        $this->assertSame('paid', $v->status);
        $this->assertSame('CHQ-778', $v->cheque_number);
        $this->assertSame($this->admin->id, $v->paid_by);

        // Paid = locked: no cancel, no delete, no second payment.
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.cancel', $v))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('erp.payment-vouchers.destroy', $v))->assertForbidden();
        $this->assertSame(['voucher_created', 'voucher_approved', 'voucher_paid', 'voucher_expense_created'],
            AuditLog::where('auditable_type', PaymentVoucher::class)->where('auditable_id', $v->id)->orderBy('id')->pluck('action')->all());
    }

    public function test_cancel_keeps_the_record_and_locks_it(): void
    {
        $v = $this->createAs($this->admin);
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.approve', $v));
        $this->actingAs($this->staff)->patch(route('erp.payment-vouchers.cancel', $v))->assertForbidden();

        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.cancel', $v))->assertRedirect();
        $v->refresh();
        $this->assertSame('cancelled', $v->status);
        $this->assertNotSoftDeleted('payment_vouchers', ['id' => $v->id]);
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.mark-paid', $v), ['payment_method' => 'cash', 'payment_date' => '2026-09-24'])->assertForbidden();
    }

    public function test_other_agency_is_fully_isolated(): void
    {
        $v = $this->createAs($this->admin);

        $this->actingAs($this->otherAdmin)->get(route('erp.payment-vouchers.show', $v))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.payment-vouchers.edit', $v))->assertForbidden();
        $this->actingAs($this->otherAdmin)->put(route('erp.payment-vouchers.update', $v), $this->payload())->assertForbidden();
        $this->actingAs($this->otherAdmin)->patch(route('erp.payment-vouchers.approve', $v))->assertForbidden();
        $this->actingAs($this->otherAdmin)->delete(route('erp.payment-vouchers.destroy', $v))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.payment-vouchers.download-pdf', $v))->assertForbidden();
        $this->actingAs($this->otherAdmin)->get(route('erp.payment-vouchers.index'))->assertOk()->assertDontSee($v->voucher_number);
    }

    public function test_validation_rejects_bad_money_and_negative_totals(): void
    {
        $this->actingAs($this->admin)->post(route('erp.payment-vouchers.store'), $this->payload([
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => '10.005']],
        ]))->assertSessionHasErrors('items.0.unit_price');
        $this->actingAs($this->admin)->post(route('erp.payment-vouchers.store'), $this->payload(['tax_value' => '101']))
            ->assertSessionHasErrors('tax_value');
        $this->actingAs($this->admin)->post(route('erp.payment-vouchers.store'), $this->payload([
            'tax_type' => 'none', 'discount_value' => '999999',
        ]))->assertSessionHasErrors('discount_value');
        $this->actingAs($this->admin)->post(route('erp.payment-vouchers.store'), $this->payload(['payee_name' => '', 'items' => []]))
            ->assertSessionHasErrors(['payee_name', 'items']);

        $this->assertSame(0, PaymentVoucher::count());
    }

    public function test_update_recalculates_and_drops_irrelevant_cheque_details(): void
    {
        $v = $this->createAs($this->staff, ['payment_method' => 'cheque', 'cheque_number' => '111', 'bank_name' => 'X Bank']);
        $this->assertSame('111', $v->cheque_number);

        $this->actingAs($this->staff)->put(route('erp.payment-vouchers.update', $v), $this->payload([
            'payment_method' => 'cash', 'cheque_number' => '111', 'bank_name' => 'X Bank',
            'tax_type' => 'none', 'discount_type' => 'none',
            'items' => [['description' => 'Delivery Charge', 'quantity' => 3, 'unit_price' => '333.33']],
        ]))->assertRedirect(route('erp.payment-vouchers.show', $v));

        $v->refresh();
        $this->assertSame('999.99', (string) $v->total_amount);
        $this->assertNull($v->cheque_number);
        $this->assertNull($v->bank_name);
        $this->assertNull($v->tax_value);
    }

    public function test_calculation_helper_is_exact(): void
    {
        $r = PaymentVoucherService::calculateTotals(
            [['quantity' => 3, 'unit_price' => '0.10'], ['quantity' => 1, 'unit_price' => '0.20']],
            'percent', '12.5', 'percent', '10',
        );
        $this->assertSame(50, $r['subtotal']);
        $this->assertSame(6, $r['tax']);        // 6.25 → 6
        $this->assertSame(5, $r['discount']);
        $this->assertSame(51, $r['total']);
    }

    private function paidVoucher(string $method = 'cheque'): PaymentVoucher
    {
        $v = $this->createAs($this->admin);
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.approve', $v));
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.mark-paid', $v), [
            'payment_method' => $method, 'payment_date' => '2026-09-20', 'cheque_number' => '1001234',
        ])->assertRedirect();

        return $v->fresh();
    }

    public function test_marking_paid_books_exactly_one_expense(): void
    {
        $v = $this->paidVoucher('cheque');

        $expense = Expense::where('payment_voucher_id', $v->id)->sole();
        $this->assertSame($this->agency->id, $expense->agency_id);
        $this->assertSame('manpower', $expense->category);
        $this->assertSame('Manpower Cost', $expense->categoryLabel());
        $this->assertSame($v->expense_head_id, $expense->expense_head_id);
        $this->assertSame('56750.11', (string) $expense->amount);          // exact, equals voucher total
        $this->assertSame((string) $v->total_amount, (string) $expense->amount);
        $this->assertSame('2026-09-20', $expense->expense_date->format('Y-m-d')); // payment date, not "now"
        $this->assertSame('bank', $expense->paid_via);                    // cheque → bank
        $this->assertStringContainsString($v->voucher_number, $expense->note);
        $this->assertStringContainsString('Dhaka Manpower Ltd', $expense->note);
        $this->assertTrue(AuditLog::where('action', 'voucher_expense_created')->where('auditable_id', $v->id)->exists());

        // A second payment attempt is refused and never creates a second expense.
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.mark-paid', $v), ['payment_method' => 'cash', 'payment_date' => '2026-09-20'])->assertForbidden();
        $this->assertSame(1, Expense::where('payment_voucher_id', $v->id)->count());

        // It flows into the same totals Reports / Profit-Loss read.
        $this->assertSame(56750.11, app(\App\Services\ErpReportService::class)->yearlySummary($this->agency->id, 2026)['expense']);

        $this->actingAs($this->admin)->get(route('erp.payment-vouchers.show', $v))->assertOk()->assertSee('Booked in Expenses automatically');
    }

    public function test_unpaid_vouchers_book_nothing(): void
    {
        $v = $this->createAs($this->admin);
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.approve', $v));
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.cancel', $v));

        $this->assertSame(0, Expense::count());
    }

    public function test_auto_expense_is_read_only_and_category_is_not_manually_usable(): void
    {
        $expense = Expense::where('payment_voucher_id', $this->paidVoucher()->id)->sole();

        $this->actingAs($this->admin)->put(route('erp.expenses.update', $expense), [
            'expense_date' => '2026-09-21', 'category' => 'other', 'amount' => '1.00',
        ])->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('erp.expenses.destroy', $expense))->assertSessionHas('error');
        $this->assertSame('56750.11', (string) $expense->fresh()->amount);

        // The system category can't be chosen on the manual Add form.
        $this->actingAs($this->admin)->post(route('erp.expenses.store'), [
            'expense_date' => '2026-09-21', 'category' => 'payment_voucher', 'amount' => '10',
        ])->assertSessionHasErrors('category');

        $this->actingAs($this->admin)->get(route('erp.expenses'))->assertOk()->assertSee('Payment Voucher');
    }

    public function test_invoice_search_is_agency_scoped_and_falls_back_to_agent(): void
    {
        $agent = \App\Models\Agent::create([
            'agency_id' => $this->agency->id, 'name' => 'Rahim Travels', 'phone' => '01800000000',
            'address' => 'Motijheel', 'status' => 'active',
        ]);
        $mine = $this->makeInvoice($this->agency, 1, ['agent_id' => $agent->id]);
        $theirs = $this->makeInvoice($this->other, 1, ['bill_to_name' => 'Rahim Travels']);

        $json = $this->actingAs($this->staff)->getJson(route('erp.invoices.search', ['q' => 'Rahim']))->assertOk()->json();
        $this->assertCount(1, $json);
        $this->assertSame($mine->invoice_number, $json[0]['invoice_number']);
        $this->assertSame('Rahim Travels', $json[0]['bill_to_name']);      // agent fallback
        $this->assertSame('01800000000', $json[0]['bill_to_phone']);
        $this->assertSame('Motijheel', $json[0]['bill_to_address']);

        $this->actingAs($this->staff)->getJson(route('erp.invoices.search', ['q' => 'INV-' . $this->agency->id]))
            ->assertOk()->assertJsonCount(1)->assertDontSee($theirs->invoice_number);
        $this->actingAs($this->staff)->getJson(route('erp.invoices.search', ['q' => 'R']))->assertOk()->assertExactJson([]);
        $this->actingAs($this->staff)->getJson(route('erp.invoices.search', ['q' => '%']))->assertOk()->assertExactJson([]);
    }

    private function makeInvoice(Agency $agency, int $seq, array $attrs = []): Invoice
    {
        $invoice = new Invoice(array_merge([
            'invoice_date' => '2026-09-24', 'status' => 'pending', 'currency' => 'BDT',
            'tax_type' => 'none', 'discount_type' => 'none',
        ], $attrs));
        $invoice->forceFill([
            'agency_id' => $agency->id, 'number_year' => 2026, 'number_seq' => $seq,
            'invoice_number' => "INV-{$agency->id}-2026-" . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'subtotal' => '100.00', 'total_amount' => '100.00',
        ])->save();

        return $invoice;
    }

    public function test_pages_and_pdfs_render(): void
    {
        $v = $this->createAs($this->admin);
        $this->actingAs($this->admin)->patch(route('erp.payment-vouchers.approve', $v));

        $this->actingAs($this->staff)->get(route('erp.payment-vouchers.index', ['q' => 'Manpower', 'status' => 'approved']))
            ->assertOk()->assertSee($v->voucher_number);
        $this->actingAs($this->staff)->get(route('erp.payment-vouchers.show', $v))->assertOk()->assertSee('56,750.11');
        $this->actingAs($this->staff)->get(route('erp.payment-vouchers.edit', $v))->assertRedirect(route('erp.payment-vouchers.show', $v));

        $this->actingAs($this->staff)->get(route('erp.payment-vouchers.preview-pdf', $v))
            ->assertOk()->assertSee('frame.contentWindow.print()', false);
        $this->actingAs($this->staff)->get(route('erp.payment-vouchers.preview-pdf', [$v, 'raw' => 1]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $res = $this->actingAs($this->staff)->get(route('erp.payment-vouchers.download-pdf', $v))->assertOk();
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
    }
}
