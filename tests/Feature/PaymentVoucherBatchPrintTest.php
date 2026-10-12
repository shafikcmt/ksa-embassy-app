<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\ExpenseHead;
use App\Models\PaymentVoucher;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/** "Print selected" on the payment-voucher list: 2 vouchers per A4 sheet, agency-scoped, slimmer top. */
class PaymentVoucherBatchPrintTest extends TestCase
{
    use RefreshDatabase;

    protected Agency $agency;

    protected Agency $other;

    protected User $admin;

    protected User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = $this->makeAgency('alpha', $plan);
        $this->other = $this->makeAgency('beta', $plan);
        $this->admin = $this->makeUser($this->agency, 'admin@alpha.test');
        $this->otherAdmin = $this->makeUser($this->other, 'admin@beta.test');
    }

    private function makeAgency(string $slug, Plan $plan): Agency
    {
        $agency = Agency::create(['name' => ucfirst($slug) . ' Agency', 'slug' => $slug, 'status' => 'active']);
        Subscription::create(['agency_id' => $agency->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);

        return $agency;
    }

    private function makeUser(Agency $agency, string $email): User
    {
        $user = User::create(['name' => $email, 'email' => $email, 'password' => Hash::make('secret1234'), 'agency_id' => $agency->id, 'is_super_admin' => false, 'is_active' => true]);
        $user->assignRole('agency_admin');

        return $user;
    }

    protected function voucher(User $user, array $overrides = []): PaymentVoucher
    {
        $head = ExpenseHead::forAgency($user->agency_id)->where('code', 'manpower')->value('id');
        $this->actingAs($user)->post(route('erp.payment-vouchers.store'), $overrides + [
            'expense_head_id' => $head, 'voucher_date' => '2026-10-08', 'payee_name' => 'Rayhan Ahmed',
            'amount' => '6000', 'payment_method' => 'cash',
        ])->assertRedirect()->assertSessionHasNoErrors();

        return PaymentVoucher::latest('id')->firstOrFail();
    }

    protected function batch(array $vouchers, array $extra = [])
    {
        return $this->actingAs($this->admin)->get(route('erp.payment-vouchers.print-selected', ['ids' => collect($vouchers)->pluck('id')->implode(','), 'raw' => 1] + $extra));
    }

    protected function pdf($response): \Smalot\PdfParser\Document
    {
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        return (new Parser)->parseContent($response->getContent());
    }

    public function test_single_voucher_has_no_top_accent_bar(): void
    {
        $v = $this->voucher($this->admin)->load('items', 'expenseHead');
        $html = view('prints.payment-voucher', ['voucher' => $v, 'agency' => $this->agency, 'amountInWords' => 'x'])->render();

        $this->assertStringNotContainsString('class="accent"', $html);
        $this->assertStringNotContainsString('height:5mm', $html);
        $this->assertStringContainsString('PAYMENT VOUCHER', $html);

        $this->assertCount(1, $this->pdf($this->actingAs($this->admin)->get(route('erp.payment-vouchers.preview-pdf', [$v, 'raw' => 1])))->getPages());
    }

    public function test_print_selected_opens_the_print_dialog_wrapper(): void
    {
        $a = $this->voucher($this->admin);
        $b = $this->voucher($this->admin);

        $this->actingAs($this->admin)->get(route('erp.payment-vouchers.print-selected', ['ids' => "{$a->id},{$b->id}"]))
            ->assertOk()->assertSee('frame.contentWindow.print()', false)->assertSee('Print 2 vouchers')
            ->assertSee('raw=1', false)->assertSee('download=1', false);
    }

    public function test_two_vouchers_share_one_a4_page_and_pages_fill_two_at_a_time(): void
    {
        $v = [$this->voucher($this->admin), $this->voucher($this->admin), $this->voucher($this->admin), $this->voucher($this->admin), $this->voucher($this->admin)];

        $pdf = $this->pdf($this->batch([$v[0], $v[1]]));
        $this->assertCount(1, $pdf->getPages());
        $this->assertStringContainsString($v[0]->voucher_number, $pdf->getText());
        $this->assertStringContainsString($v[1]->voucher_number, $pdf->getText());
        [, , $w, $h] = $pdf->getPages()[0]->getDetails()['MediaBox'];
        $this->assertGreaterThan($w, $h, 'A4 portrait');

        $this->assertCount(2, $this->pdf($this->batch(array_slice($v, 0, 3)))->getPages());
        $this->assertCount(2, $this->pdf($this->batch(array_slice($v, 0, 4)))->getPages());
        $this->assertCount(3, $this->pdf($this->batch($v))->getPages());
    }

    public function test_the_heaviest_half_page_vouchers_still_share_a_page(): void
    {
        $max = \App\Http\Controllers\Erp\PaymentVoucherController::HALF_PAGE_MAX_ROWS;
        // $max rows of remarks: a note with $max explicit lines.
        $notes = implode("\n", array_fill(0, $max, 'Salary for September, paid in cash.'));
        $a = $this->voucher($this->admin, ['notes' => $notes]);
        $b = $this->voucher($this->admin, ['notes' => $notes]);

        $this->assertCount(1, $this->pdf($this->batch([$a, $b]))->getPages());
    }

    public function test_a_heavy_voucher_takes_a_page_of_its_own_and_nothing_is_lost(): void
    {
        $max = \App\Http\Controllers\Erp\PaymentVoucherController::HALF_PAGE_MAX_ROWS;
        $small = $this->voucher($this->admin);
        $heavy = $this->voucher($this->admin, ['notes' => implode("\n", array_fill(0, $max + 2, 'A long remark line.'))]);
        $small2 = $this->voucher($this->admin);
        $pdf = $this->pdf($this->batch([$small, $heavy, $small2]));

        $this->assertGreaterThanOrEqual(3, count($pdf->getPages()));
        foreach ([$small, $heavy, $small2] as $v) {
            $this->assertStringContainsString($v->voucher_number, $pdf->getText());
        }
    }

    public function test_download_returns_an_attachment(): void
    {
        $a = $this->voucher($this->admin);
        $res = $this->actingAs($this->admin)->get(route('erp.payment-vouchers.print-selected', ['ids' => (string) $a->id, 'download' => 1]))->assertOk();

        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
    }

    public function test_other_agencies_vouchers_are_never_printed(): void
    {
        $mine = $this->voucher($this->admin);
        $theirs = $this->voucher($this->otherAdmin);

        $text = $this->pdf($this->actingAs($this->admin)->get(route('erp.payment-vouchers.print-selected', ['ids' => "{$mine->id},{$theirs->id}", 'raw' => 1])))->getText();
        $this->assertStringContainsString($mine->voucher_number, $text);
        $this->assertStringNotContainsString($theirs->voucher_number, $text);

        $this->actingAs($this->admin)->get(route('erp.payment-vouchers.print-selected', ['ids' => (string) $theirs->id, 'raw' => 1]))->assertNotFound();
    }

    public function test_ids_are_validated_and_guests_are_sent_to_login(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('erp.payment-vouchers.print-selected'))->assertStatus(422);
        $this->get(route('erp.payment-vouchers.print-selected', ['ids' => 'abc']))->assertStatus(422);
        $this->get(route('erp.payment-vouchers.print-selected', ['ids' => implode(',', range(1, 51))]))->assertStatus(422);
        $this->get(route('erp.payment-vouchers.print-selected', ['ids' => '999999']))->assertNotFound();

        auth()->logout();
        $this->get(route('erp.payment-vouchers.print-selected', ['ids' => '1']))->assertRedirect(route('login'));
    }

    public function test_list_offers_selection_and_print_selected(): void
    {
        $this->voucher($this->admin);

        $this->actingAs($this->admin)->get(route('erp.payment-vouchers.index'))
            ->assertOk()->assertSee('data-voucher-check', false)->assertSee('Print selected');
    }
}
