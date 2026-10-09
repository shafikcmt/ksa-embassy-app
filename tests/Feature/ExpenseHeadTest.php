<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\PaymentVoucher;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AgentKhataService;
use App\Services\ErpReportService;
use App\Services\PaymentVoucherService;
use App\Services\ProfitLossService;
use App\Support\NumberToWords;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class ExpenseHeadTest extends TestCase
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
        $plan = Plan::create(['name' => 'Heads', 'slug' => 'heads', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        foreach (['agency', 'other'] as $property) {
            $this->$property = Agency::create(['name' => $property, 'slug' => $property, 'status' => 'active']);
            Subscription::create(['agency_id' => $this->$property->id, 'plan_id' => $plan->id,
                'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);
        }
        foreach (['admin', 'staff', 'otherAdmin'] as $property) {
            $this->$property = User::factory()->create(['agency_id' => $property === 'otherAdmin' ? $this->other->id : $this->agency->id, 'is_super_admin' => false, 'is_active' => true]);
            $this->$property->assignRole($property === 'staff' ? 'agency_staff' : 'agency_admin');
            $this->$property->givePermissionTo('access_erp');
        }
    }

    private function expenseHead(string $code = 'salary', ?Agency $agency = null): ExpenseHead
    {
        return ExpenseHead::forAgency(($agency ?? $this->agency)->id)->where('code', $code)->sole();
    }

    private function expensePayload(array $extra = []): array
    {
        return $extra + ['expense_date' => '2026-10-08', 'expense_head_id' => $this->expenseHead()->id,
            'amount' => '125.50', 'paid_via' => 'cash', 'note' => 'Staff salary'];
    }

    private function voucherPayload(array $extra = []): array
    {
        return $extra + ['voucher_date' => '2026-10-08', 'expense_head_id' => $this->expenseHead()->id,
            'payee_name' => 'Recipient Example', 'payment_method' => 'cash', 'amount' => '11500.50',
            'reference_number' => 'REF-123', 'notes' => 'October salary'];
    }

    private function voucher(array $extra = []): PaymentVoucher
    {
        $this->actingAs($this->admin)->post(route('erp.payment-vouchers.store'), $this->voucherPayload($extra))
            ->assertRedirect()->assertSessionHasNoErrors();

        return PaymentVoucher::latest('id')->firstOrFail();
    }

    public function test_defaults_are_created_once_per_agency_without_agent_payouts(): void
    {
        foreach ([$this->agency, $this->other] as $agency) {
            $this->assertSame(19, ExpenseHead::forAgency($agency->id)->where('is_active', true)->count());
            $this->assertSame(ExpenseHead::DEFAULTS, ExpenseHead::forAgency($agency->id)->where('is_active', true)->ordered()->pluck('name', 'code')->all());
            $this->assertFalse(ExpenseHead::forAgency($agency->id)->where('name', 'like', '%Agent%')->exists());
            ExpenseHead::createDefaults($agency->id);
            $this->assertSame(20, ExpenseHead::forAgency($agency->id)->count());
        }
    }

    public function test_admin_can_add_rename_disable_reorder_and_delete_unused_heads(): void
    {
        $this->actingAs($this->admin)->post(route('erp.expense-heads.store'), ['name' => 'Local Service', 'is_active' => 1, 'sort_order' => 5])->assertRedirect()->assertSessionHasNoErrors();
        $head = ExpenseHead::forAgency($this->agency->id)->where('code', 'local_service')->sole();
        $this->put(route('erp.expense-heads.update', $head), ['name' => 'Local Services', 'is_active' => 0, 'sort_order' => 20])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Local Services', $head->fresh()->name);
        $this->assertSame('local_service', $head->fresh()->code);
        $this->assertFalse($head->fresh()->is_active);
        $this->patch(route('erp.expense-heads.reorder'), ['heads' => [$head->id, $this->expenseHead()->id]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(10, $head->fresh()->sort_order);
        $this->assertSame(20, $this->expenseHead()->sort_order);
        $this->delete(route('erp.expense-heads.destroy', $head))->assertRedirect();
        $this->assertModelMissing($head);
        $this->get(route('erp.expense-heads.index'))->assertOk();
        $this->assertModelMissing($head); // Visiting settings never silently reseeds deleted heads.
    }

    public function test_used_and_system_heads_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)->post(route('erp.expenses.store'), $this->expensePayload())->assertRedirect()->assertSessionHasNoErrors();
        $this->delete(route('erp.expense-heads.destroy', $this->expenseHead()))->assertSessionHasErrors('head');
        $voucher = $this->voucher(['expense_head_id' => $this->expenseHead('office_rent')->id]);
        $this->delete(route('erp.payment-vouchers.destroy', $voucher))->assertRedirect();
        $this->delete(route('erp.expense-heads.destroy', $this->expenseHead('office_rent')))->assertSessionHasErrors('head');
        $archive = $this->expenseHead('payment_voucher');
        $this->delete(route('erp.expense-heads.destroy', $archive))->assertSessionHasErrors('head');
        $this->put(route('erp.expense-heads.update', $archive), ['name' => $archive->name, 'is_active' => 1, 'sort_order' => 1])->assertUnprocessable();
    }

    public function test_agencies_cannot_see_use_edit_delete_or_reorder_each_others_heads(): void
    {
        $foreign = $this->expenseHead('salary', $this->other);
        $foreign->update(['name' => 'Private Beta Head']);
        $this->actingAs($this->admin)->get(route('erp.expense-heads.index'))->assertOk()->assertDontSee('Private Beta Head');
        $this->get(route('erp.expenses'))->assertOk()->assertDontSee('Private Beta Head');
        $this->get(route('erp.payment-vouchers.create'))->assertOk()->assertDontSee('Private Beta Head');
        $this->put(route('erp.expense-heads.update', $foreign), ['name' => 'Hacked', 'is_active' => 1, 'sort_order' => 0])->assertForbidden();
        $this->delete(route('erp.expense-heads.destroy', $foreign))->assertForbidden();
        $this->patch(route('erp.expense-heads.reorder'), ['heads' => [$this->expenseHead()->id, $foreign->id]])->assertSessionHasErrors('heads.1');
        $this->post(route('erp.expenses.store'), $this->expensePayload(['expense_head_id' => $foreign->id]))->assertSessionHasErrors('expense_head_id');
        $this->post(route('erp.payment-vouchers.store'), $this->voucherPayload(['expense_head_id' => $foreign->id]))->assertSessionHasErrors('expense_head_id');
        $this->assertSame('Private Beta Head', $foreign->fresh()->name);
        $this->assertSame(0, Expense::count());
        $this->assertSame(0, PaymentVoucher::count());
    }

    public function test_non_admin_and_guests_cannot_manage_heads(): void
    {
        $head = $this->expenseHead();
        $this->get(route('erp.expense-heads.index'))->assertRedirect(route('login'));
        $this->actingAs($this->staff)->get(route('erp.expense-heads.index'))->assertForbidden();
        $this->post(route('erp.expense-heads.store'), ['name' => 'Denied', 'is_active' => 1, 'sort_order' => 0])->assertForbidden();
        $this->put(route('erp.expense-heads.update', $head), ['name' => 'Denied', 'is_active' => 1, 'sort_order' => 0])->assertForbidden();
        $this->patch(route('erp.expense-heads.reorder'), ['heads' => [$head->id]])->assertForbidden();
        $this->delete(route('erp.expense-heads.destroy', $head))->assertForbidden();
        $this->post(route('erp.expenses.store'), $this->expensePayload())->assertForbidden();
    }

    public function test_manual_expense_uses_dynamic_head_and_legacy_records_still_render(): void
    {
        $head = ExpenseHead::create(['agency_id' => $this->agency->id, 'code' => 'local', 'name' => 'Local Processing', 'is_active' => true, 'sort_order' => 1]);
        $this->actingAs($this->admin)->post(route('erp.expenses.store'), $this->expensePayload(['expense_head_id' => $head->id]))->assertRedirect()->assertSessionHasNoErrors();
        $expense = Expense::sole();
        $this->assertSame('local', $expense->category);
        $this->assertSame($head->id, $expense->expense_head_id);
        $head->update(['name' => 'Renamed Processing']);
        $this->assertSame('Renamed Processing', $expense->fresh()->categoryLabel());
        $legacy = Expense::create(['agency_id' => $this->agency->id, 'expense_date' => '2026-10-08', 'category' => 'utilities', 'amount' => '10']);
        $unknown = Expense::create(['agency_id' => $this->agency->id, 'expense_date' => '2026-10-08', 'category' => 'historical_custom', 'amount' => '20']);
        $this->get(route('erp.expenses'))->assertOk()->assertSee('Renamed Processing')->assertSee('Utilities')->assertSee('Historical_custom');
        $this->assertSame('Utilities', $legacy->categoryLabel());
        $this->put(route('erp.expenses.update', $unknown), ['expense_date' => '2026-10-08', 'category' => 'historical_custom', 'amount' => '21'])->assertSessionHasNoErrors();
        $this->assertNull($unknown->fresh()->expense_head_id);
    }

    public function test_inactive_heads_are_unavailable_for_new_entries_but_current_records_can_retain_them(): void
    {
        $voucher = $this->voucher();
        $this->actingAs($this->admin)->post(route('erp.expenses.store'), $this->expensePayload())->assertSessionHasNoErrors();
        $expense = Expense::sole();
        $head = $this->expenseHead();
        $head->update(['is_active' => false]);
        $this->post(route('erp.expenses.store'), $this->expensePayload())->assertSessionHasErrors('expense_head_id');
        $this->post(route('erp.payment-vouchers.store'), $this->voucherPayload())->assertSessionHasErrors('expense_head_id');
        $this->put(route('erp.expenses.update', $expense), $this->expensePayload(['amount' => '126']))->assertSessionHasNoErrors();
        $this->put(route('erp.payment-vouchers.update', $voucher), $this->voucherPayload(['amount' => '12000']))->assertSessionHasNoErrors();
        $this->get(route('erp.payment-vouchers.show', $voucher))->assertOk()->assertSee('Salary');
        $this->get(route('erp.payment-vouchers.create'))->assertOk()->assertDontSee('<option value="'.$head->id.'"', false);
        $this->get(route('erp.payment-vouchers.edit', $voucher))->assertOk()->assertSee('inactive — current head');
    }

    public function test_paid_voucher_books_the_same_head_once_without_affecting_agent_khata(): void
    {
        $voucher = $this->voucher(['total_amount' => '1', 'tax_type' => 'percent', 'tax_value' => '100']);
        $this->assertSame('11500.50', $voucher->total_amount);
        $this->assertSame('0.00', $voucher->tax_amount);
        $this->patch(route('erp.payment-vouchers.approve', $voucher))->assertRedirect();
        $this->expenseHead()->update(['is_active' => false]); // Already approved obligations remain payable.
        $this->patch(route('erp.payment-vouchers.mark-paid', $voucher), ['payment_method' => 'cash', 'payment_date' => '2026-10-08'])->assertRedirect()->assertSessionHasNoErrors();
        $expense = Expense::sole();
        $this->assertSame($voucher->expense_head_id, $expense->expense_head_id);
        $this->assertSame('salary', $expense->category);
        $this->assertSame('11500.50', $expense->amount);
        $this->assertSame('Salary', $expense->categoryLabel());
        $this->patch(route('erp.payment-vouchers.mark-paid', $voucher), ['payment_method' => 'cash', 'payment_date' => '2026-10-08'])->assertForbidden();
        $this->assertSame(1, Expense::count());
        $this->assertSame(0, AgentTransaction::count());
        $this->put(route('erp.expenses.update', $expense), $this->expensePayload(['amount' => '1']))->assertSessionHas('error');
        $this->delete(route('erp.expenses.destroy', $expense))->assertSessionHas('error');
    }

    public function test_stale_draft_cannot_reselect_an_inactive_head_after_another_edit(): void
    {
        $stale = $this->voucher();
        $currentHead = $this->expenseHead('office_rent');
        DB::table('payment_vouchers')->where('id', $stale->id)->update(['expense_head_id' => $currentHead->id]);
        $this->expenseHead()->update(['is_active' => false]);
        try {
            app(PaymentVoucherService::class)->save($stale, $this->agency->id, [
                'expense_head_id' => $this->expenseHead()->id, 'voucher_date' => '2026-10-08',
                'payment_method' => 'cash', 'tax_type' => 'none', 'discount_type' => 'none',
            ], [['description' => 'Salary', 'quantity' => 1, 'unit_price' => '1']], $this->admin);
            $this->fail('A stale draft must not bypass inactive-head validation.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('expense_head_id', $error->errors());
        }
        $this->assertSame($currentHead->id, $stale->fresh()->expense_head_id);
        $this->assertSame('11500.50', $stale->fresh()->total_amount);
    }

    public function test_legacy_voucher_purpose_and_recipient_type_survive_the_simple_edit_form(): void
    {
        $voucher = $this->voucher();
        $voucher->update(['expense_head_id' => null, 'description' => 'Historical purpose', 'payee_type' => 'individual']);
        $this->get(route('erp.payment-vouchers.show', $voucher))->assertOk()->assertSee('Historical purpose')->assertSee('Payment Voucher (legacy)');
        $this->put(route('erp.payment-vouchers.update', $voucher), $this->voucherPayload())->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Historical purpose', $voucher->fresh()->description);
        $this->assertSame('individual', $voucher->fresh()->payee_type);
        $this->assertSame('11500.50', $voucher->fresh()->total_amount);
    }

    public function test_voucher_form_and_pdf_use_one_head_amount_and_required_signatures(): void
    {
        $voucher = $this->voucher();
        $form = $this->get(route('erp.payment-vouchers.create'))->assertOk();
        $form->assertSee('Expense Head / Reason of Costing')->assertSee('Paid To / Recipient')->assertSee('name="amount"', false)->assertDontSee('name="items', false)->assertDontSee('Qty');
        $html = view('prints.payment-voucher', ['voucher' => $voucher->load('expenseHead'), 'agency' => $this->agency,
            'amountInWords' => NumberToWords::currency($voucher->total_amount, 'BDT')])->render();
        foreach (['Expense Head / Reason of Costing', 'Salary', 'Paid To / Recipient', 'Recipient Example', '11,500.50',
            'REF-123', 'October salary', 'Taka Eleven Thousand Five Hundred and Paisa Fifty Only', 'Recipient', 'Accountant', 'Proprietor'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('>Qty<', $html);
        $this->assertStringNotContainsString('>Description<', $html);
        $this->assertStringNotContainsString('>SL<', $html);
        $pdf = $this->get(route('erp.payment-vouchers.preview-pdf', [$voucher, 'raw' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $text = (new Parser)->parseContent($pdf->getContent())->getText();
        $this->assertStringContainsString('Salary', $text);
        $this->assertStringContainsString('Accountant', $text);
        $this->assertStringContainsString('11,500.50', $text);
        $this->assertStringNotContainsString('Qty', $text);
    }

    public function test_reports_and_profit_loss_keep_totals_and_agent_payouts_separate(): void
    {
        $this->actingAs($this->admin)->post(route('erp.expenses.store'), $this->expensePayload())->assertSessionHasNoErrors();
        $this->post(route('erp.expenses.store'), $this->expensePayload(['expense_head_id' => $this->expenseHead('office_rent')->id, 'amount' => '200']))->assertSessionHasNoErrors();
        $agent = Agent::create(['agency_id' => $this->agency->id, 'name' => 'Agent', 'phone' => '01712345678', 'address' => 'Dhaka', 'status' => 'active']);
        app(AgentKhataService::class)->record($agent, AgentTransaction::TYPE_DEBIT, 50, 'Khata payout', $this->admin->id);
        $reports = app(ErpReportService::class);
        $pl = app(ProfitLossService::class);
        $before = $pl->summary($this->agency->id);
        $this->assertSame(325.5, $before['expenseCost']);
        $this->assertSame(50.0, $before['agentPayoutCost']);
        $this->assertSame(375.5, $before['totalCost']);
        $this->expenseHead()->update(['name' => 'Staff Salary', 'is_active' => false]);
        $this->assertSame($before, $pl->summary($this->agency->id));
        $totals = $reports->expenseTotals($this->agency->id, ['expense_head_id' => $this->expenseHead()->id]);
        $this->assertSame(325.5, $totals['allTime']);
        $this->assertSame(125.5, $totals['rangeTotal']);
        $this->assertSame(125.5, $totals['byCategory']['Staff Salary']);
        $this->get(route('erp.expenses', ['expense_head_id' => $this->expenseHead()->id]))->assertOk()->assertViewHas('expenses', fn ($rows) => $rows->count() === 1);
        $this->get(route('erp.reports', ['expense_head_id' => $this->expenseHead()->id]))->assertOk()->assertViewHas('expenses', fn ($data) => $data['rows']->count() === 1 && $data['rangeTotal'] === 125.5);
        $csv = $this->get(route('erp.reports.export.csv'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Staff Salary', $csv);
        $this->get(route('erp.reports.export.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('erp.profit-loss', ['period' => 'all']))->assertOk()->assertViewHas('summary', fn ($data) => $data['totalCost'] === 375.5);
        $this->actingAs($this->staff)->get(route('erp.profit-loss'))->assertForbidden();
        $this->assertSame(0.0, $pl->summary($this->other->id)['totalCost']);
    }

    public function test_csv_keeps_headers_legacy_aliases_and_dynamic_codes_but_rejects_inactive_or_agent_categories(): void
    {
        Storage::fake('local');
        ExpenseHead::create(['agency_id' => $this->agency->id, 'code' => 'local', 'name' => 'Local Processing', 'is_active' => true, 'sort_order' => 1]);
        $csv = "expense_date,category,amount,paid_via,note\n2026-10-08,Utilities,10,Cash,Legacy alias\n2026-10-08,local,20,Cash,Dynamic code\n";
        $this->actingAs($this->admin)->post(route('erp.expenses.import.preview'), ['file' => UploadedFile::fake()->createWithContent('expenses.csv', $csv)])->assertOk()->assertViewHas('result', fn ($result) => $result['ok']);
        $this->post(route('erp.expenses.import'))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, Expense::count());
        $this->assertSame(2, Expense::whereNotNull('expense_head_id')->count());
        $export = $this->get(route('erp.expenses.export'))->assertOk()->streamedContent();
        $this->assertStringStartsWith('expense_date,category,amount,paid_via,note', $export);
        $this->assertStringContainsString('Local Processing', $export);
        $this->expenseHead('utilities')->update(['is_active' => false]);
        foreach (['utilities', 'agent_commission', 'payment_voucher'] as $category) {
            $this->post(route('erp.expenses.import.preview'), ['file' => UploadedFile::fake()->createWithContent('invalid.csv', "expense_date,category,amount,paid_via,note\n2026-10-08,$category,10,,\n")])->assertOk()->assertViewHas('result', fn ($result) => ! $result['ok']);
        }
        $this->assertSame(2, Expense::count());
    }

    public function test_migration_backfills_known_categories_preserves_unknowns_and_does_not_change_money(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.head_backfill' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('head_backfill');
        try {
            Schema::create('agencies', function (Blueprint $table) {
                $table->id();
            });
            Schema::create('payment_vouchers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id');
                $table->decimal('total_amount', 14, 2);
                $table->text('description');
            });
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id');
                $table->date('expense_date');
                $table->string('category');
                $table->decimal('amount', 14, 2);
                $table->unsignedBigInteger('payment_voucher_id')->nullable();
            });
            DB::table('agencies')->insert([['id' => 1], ['id' => 2]]);
            DB::table('payment_vouchers')->insert(['id' => 1, 'agency_id' => 1, 'total_amount' => '11500.50', 'description' => 'Old purpose']);
            DB::table('expenses')->insert([
                ['agency_id' => 1, 'expense_date' => '2026-10-08', 'category' => 'salary', 'amount' => '100.50', 'payment_voucher_id' => null],
                ['agency_id' => 1, 'expense_date' => '2026-10-08', 'category' => 'payment_voucher', 'amount' => '11500.50', 'payment_voucher_id' => 1],
                ['agency_id' => 1, 'expense_date' => '2026-10-08', 'category' => 'unknown_old', 'amount' => '30', 'payment_voucher_id' => null],
                ['agency_id' => 2, 'expense_date' => '2026-10-08', 'category' => 'salary', 'amount' => '40', 'payment_voucher_id' => null],
            ]);
            $beforeExpenses = DB::table('expenses')->orderBy('id')->get()->map(fn ($e) => (array) $e)->all();
            $beforeVoucher = (array) DB::table('payment_vouchers')->where('id', 1)->first();
            $migration = require database_path('migrations/2026_10_08_000001_create_expense_heads.php');
            $migration->up();
            $afterExpenses = DB::table('expenses')->orderBy('id')->get()->map(function ($e) {
                $a = (array) $e;
                unset($a['expense_head_id']);

                return $a;
            })->all();
            $afterVoucher = (array) DB::table('payment_vouchers')->where('id', 1)->first();
            unset($afterVoucher['expense_head_id']);
            $this->assertSame($beforeExpenses, $afterExpenses);
            $this->assertSame($beforeVoucher, $afterVoucher);
            foreach (DB::table('expenses')->whereNotNull('expense_head_id')->get() as $expense) {
                $this->assertSame($expense->agency_id, DB::table('expense_heads')->where('id', $expense->expense_head_id)->value('agency_id'));
            }
            $this->assertNull(DB::table('expenses')->where('category', 'unknown_old')->value('expense_head_id'));
            $archiveId = DB::table('expense_heads')->where('agency_id', 1)->where('code', 'payment_voucher')->value('id');
            $this->assertSame($archiveId, DB::table('payment_vouchers')->where('id', 1)->value('expense_head_id'));
            $this->assertSame(11631.0, (float) DB::table('expenses')->where('agency_id', 1)->sum('amount'));
            $migration->down();
            $this->assertFalse(Schema::hasColumn('expenses', 'expense_head_id'));
            $this->assertSame($beforeExpenses, DB::table('expenses')->orderBy('id')->get()->map(fn ($e) => (array) $e)->all());
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('head_backfill');
        }
    }

    public function test_auto_booked_voucher_csv_rows_cannot_be_imported_as_a_second_outflow(): void
    {
        Storage::fake('local');
        $voucher = $this->voucher();
        $this->patch(route('erp.payment-vouchers.approve', $voucher))->assertRedirect();
        $this->patch(route('erp.payment-vouchers.mark-paid', $voucher), ['payment_method' => 'cash', 'payment_date' => '2026-10-08'])->assertRedirect();
        $csv = $this->get(route('erp.expenses.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('voucher:'.$voucher->voucher_number, $csv);
        $this->assertStringContainsString('Salary', $csv);
        $this->post(route('erp.expenses.import.preview'), ['file' => UploadedFile::fake()->createWithContent('voucher-export.csv', $csv)])
            ->assertOk()->assertViewHas('result', fn ($result) => ! $result['ok']);
        $this->post(route('erp.expenses.import'))->assertOk()->assertViewHas('result', fn ($result) => ! $result['ok']);
        $this->assertSame(1, Expense::count());
        $this->assertSame(11500.5, app(ProfitLossService::class)->summary($this->agency->id)['expenseCost']);
    }
}
