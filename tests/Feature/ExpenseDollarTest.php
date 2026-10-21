<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Expense;
use App\Models\ExpenseHead;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\UsdNote;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Enjaz Dollar expenses also record the dollar amount; a summary card shows dollars next to the taka. */
class ExpenseDollarTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::create(['name' => 'T', 'slug' => 't', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = Agency::create(['name' => 'Alpha', 'slug' => 'alpha', 'status' => 'active']);
        Subscription::create(['agency_id' => $this->agency->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);
        $this->admin = User::factory()->create(['agency_id' => $this->agency->id, 'is_super_admin' => false, 'is_active' => true]);
        $this->admin->assignRole('agency_admin');
        $this->admin->givePermissionTo('access_erp');
    }

    private function expHead(string $code): ExpenseHead
    {
        return ExpenseHead::forAgency($this->agency->id)->where('code', $code)->sole();
    }

    private function payload(string $code, array $extra = []): array
    {
        return $extra + ['expense_date' => '2026-10-08', 'expense_head_id' => $this->expHead($code)->id, 'amount' => '53775', 'paid_via' => 'bank', 'note' => 'Top Up'];
    }

    public function test_dollar_is_read_out_of_old_notes(): void
    {
        $this->assertSame('10.55', UsdNote::parse('Enjaz Dollars Top Up $10.55*1 Card'));
        $this->assertSame('420.00', UsdNote::parse('Enjaz Dollars Top Up $420'));
        $this->assertSame('390.00', UsdNote::parse('Enjaz Dollars Top Up $390'));
        $this->assertSame('21.10', UsdNote::parse('Enjaz Dollars Top Up $21.10'));
        $this->assertSame('31.65', UsdNote::parse('Top Up $10.55*3'));
        $this->assertSame('1250.00', UsdNote::parse('Top Up $1,250'));
        $this->assertNull(UsdNote::parse('Monthly Internet Bill Feb\'26'));
        $this->assertNull(UsdNote::parse(null));
        $this->assertNull(UsdNote::parse('Cost $0'));
    }

    public function test_the_migration_backfill_logic_fills_old_dollar_rows_only(): void
    {
        $dollar = $this->expHead('enjaz_dollar');
        $row = fn (string $category, string $note) => Expense::forceCreate(['agency_id' => $this->agency->id, 'expense_date' => '2026-01-03', 'category' => $category, 'expense_head_id' => $this->expHead($category)->id, 'amount' => 1371.5, 'note' => $note]);
        $a = $row('enjaz_dollar', 'Enjaz Dollars Top Up $10.55*1 Card');
        $b = $row('enjaz_dollar', 'Enjaz Dollars Top Up $420');
        $c = $row('other', 'Paid $50 for something');

        // Same statement the migration runs.
        Expense::where('category', 'enjaz_dollar')->whereNull('usd_amount')->whereNotNull('note')->get()
            ->each(fn ($e) => ($usd = UsdNote::parse($e->note)) ? $e->forceFill(['usd_amount' => $usd])->save() : null);

        $this->assertSame('10.55', (string) $a->fresh()->usd_amount);
        $this->assertSame('420.00', (string) $b->fresh()->usd_amount);
        $this->assertNull($c->fresh()->usd_amount, 'only Enjaz Dollar rows are touched');
        $this->assertSame('Enjaz Dollars Top Up $420', $b->fresh()->note, 'the note itself is left as written');
    }

    public function test_a_new_dollar_expense_must_state_the_dollar_amount(): void
    {
        $this->actingAs($this->admin)->post(route('erp.expenses.store'), $this->payload('enjaz_dollar'))->assertSessionHasErrors('usd_amount');

        $this->post(route('erp.expenses.store'), $this->payload('enjaz_dollar', ['usd_amount' => '420']))->assertRedirect()->assertSessionHasNoErrors();
        $e = Expense::firstOrFail();
        $this->assertSame('420.00', (string) $e->usd_amount);
        $this->assertTrue($e->isDollar());
    }

    public function test_other_heads_never_keep_a_dollar_amount(): void
    {
        $this->actingAs($this->admin)->post(route('erp.expenses.store'), $this->payload('salary', ['usd_amount' => '99']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull(Expense::firstOrFail()->usd_amount);
    }

    public function test_editing_a_dollar_expense_updates_and_an_old_row_without_dollars_still_saves(): void
    {
        $old = Expense::forceCreate(['agency_id' => $this->agency->id, 'expense_date' => '2026-01-03', 'category' => 'enjaz_dollar', 'expense_head_id' => $this->expHead('enjaz_dollar')->id, 'amount' => 1000, 'note' => 'old, no dollar in the note']);
        $this->actingAs($this->admin)->put(route('erp.expenses.update', $old), $this->payload('enjaz_dollar', ['amount' => '1200']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($old->fresh()->usd_amount);

        $this->put(route('erp.expenses.update', $old), $this->payload('enjaz_dollar', ['amount' => '1200', 'usd_amount' => '9.20']))->assertRedirect();
        $this->assertSame('9.20', (string) $old->fresh()->usd_amount);
    }

    public function test_page_shows_the_dollar_card_field_and_print_column(): void
    {
        $head = $this->expHead('enjaz_dollar');
        foreach ([['2026-01-03', 1371.5, 10.55], ['2026-01-04', 53775, 420]] as [$date, $bdt, $usd]) {
            Expense::forceCreate(['agency_id' => $this->agency->id, 'expense_date' => $date, 'category' => 'enjaz_dollar', 'expense_head_id' => $head->id, 'amount' => $bdt, 'usd_amount' => $usd, 'note' => 'Top Up']);
        }
        Expense::forceCreate(['agency_id' => $this->agency->id, 'expense_date' => '2026-01-07', 'category' => 'other', 'expense_head_id' => $this->expHead('other')->id, 'amount' => 1000, 'note' => 'Internet']);

        $this->actingAs($this->admin)->get(route('erp.expenses'))->assertOk()
            ->assertSee('Dollar Purchased')->assertSee('$ 430.55')->assertSee('৳55,146.50 spent')
            ->assertSee('Dollar Amount ($)')->assertSee('$ 420.00');

        $print = $this->get(route('erp.expenses.print'))->assertOk();
        $print->assertSee('USD')->assertSee('$ 430.55');
    }
}
