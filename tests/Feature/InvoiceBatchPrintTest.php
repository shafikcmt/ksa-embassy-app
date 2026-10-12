<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/** "Print selected" on the invoice list: 2 invoices per A4 sheet, agency-scoped, plus the slimmer single-invoice top. */
class InvoiceBatchPrintTest extends TestCase
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

    /** Create an invoice (as $user) with $items passenger rows. */
    protected function invoice(User $user, int $items = 1, array $overrides = []): Invoice
    {
        $rows = [];
        for ($i = 1; $i <= $items; $i++) {
            $rows[] = ['passenger_name' => "Passenger {$i}", 'passport_no' => 'A000000' . $i, 'processing_fee' => '9000', 'mofa_fee' => '3000'];
        }
        $this->actingAs($user)->post(route('erp.invoices.store'), $overrides + [
            'invoice_date' => '2026-10-10', 'bill_to_name' => 'Sabbir Howlader', 'status' => 'pending', 'currency' => 'BDT',
            'tax_type' => 'none', 'discount_type' => 'none', 'items' => $rows,
        ])->assertRedirect();

        return Invoice::latest('id')->firstOrFail();
    }

    protected function batch(array $invoices, array $extra = [])
    {
        $ids = collect($invoices)->pluck('id')->implode(',');

        return $this->actingAs($this->admin)->get(route('erp.invoices.print-selected', ['ids' => $ids, 'raw' => 1] + $extra));
    }

    private function pdf($response): \Smalot\PdfParser\Document
    {
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        return (new Parser)->parseContent($response->getContent());
    }

    public function test_single_invoice_has_no_top_accent_bar_and_a_slim_top_margin(): void
    {
        $inv = $this->invoice($this->admin)->load('items', 'agent');
        $html = view('prints.invoice', ['invoice' => $inv, 'agency' => $this->agency, 'barcodeSrc' => null, 'amountInWords' => 'x', 'dueInWords' => ''])->render();

        $this->assertStringNotContainsString('class="accent"', $html);
        $this->assertStringContainsString('INVOICE', $html);

        // The saved PDF still renders as a single A4 page.
        $pdf = $this->pdf($this->actingAs($this->admin)->get(route('erp.invoices.preview-pdf', [$inv, 'raw' => 1])));
        $this->assertCount(1, $pdf->getPages());
    }

    public function test_print_selected_opens_the_print_dialog_wrapper(): void
    {
        $a = $this->invoice($this->admin);
        $b = $this->invoice($this->admin);

        $this->actingAs($this->admin)->get(route('erp.invoices.print-selected', ['ids' => "{$a->id},{$b->id}"]))
            ->assertOk()
            ->assertSee('frame.contentWindow.print()', false)
            ->assertSee('Print 2 invoices')
            ->assertSee('raw=1', false)
            ->assertSee('download=1', false);
    }

    public function test_two_normal_invoices_share_one_a4_page(): void
    {
        $a = $this->invoice($this->admin, 2);
        $b = $this->invoice($this->admin, 3);
        $pdf = $this->pdf($this->batch([$a, $b]));

        $this->assertCount(1, $pdf->getPages());
        $text = $pdf->getText();
        $this->assertStringContainsString($a->invoice_number, $text);
        $this->assertStringContainsString($b->invoice_number, $text);
        [, , $w, $h] = $pdf->getPages()[0]->getDetails()['MediaBox'];
        $this->assertGreaterThan($w, $h, 'A4 portrait');
    }

    public function test_pages_fill_two_at_a_time(): void
    {
        $inv = [$this->invoice($this->admin), $this->invoice($this->admin), $this->invoice($this->admin), $this->invoice($this->admin), $this->invoice($this->admin)];

        $this->assertCount(2, $this->pdf($this->batch(array_slice($inv, 0, 3)))->getPages()); // 2 + 1
        $this->assertCount(2, $this->pdf($this->batch(array_slice($inv, 0, 4)))->getPages()); // 2 + 2
        $this->assertCount(3, $this->pdf($this->batch($inv))->getPages());                     // 2 + 2 + 1
    }

    public function test_the_largest_half_page_invoices_still_share_a_page(): void
    {
        $max = \App\Http\Controllers\Erp\InvoiceController::HALF_PAGE_MAX_ROWS;

        // Passengers only …
        $this->assertCount(1, $this->pdf($this->batch([$this->invoice($this->admin, $max), $this->invoice($this->admin, $max)]))->getPages());

        // … and one fewer passenger plus a (one-line) note.
        $note = ['notes' => 'Payment by cash on delivery.'];
        $this->assertCount(1, $this->pdf($this->batch([$this->invoice($this->admin, $max - 1, $note), $this->invoice($this->admin, $max - 1, $note)]))->getPages());
    }

    public function test_an_invoice_over_the_row_limit_takes_a_page_of_its_own(): void
    {
        $max = \App\Http\Controllers\Erp\InvoiceController::HALF_PAGE_MAX_ROWS;
        $pages = $this->pdf($this->batch([$this->invoice($this->admin, 1), $this->invoice($this->admin, $max + 1)]))->getPages();

        $this->assertCount(2, $pages);
    }

    public function test_a_long_invoice_gets_its_own_page_and_nothing_is_lost(): void
    {
        $small = $this->invoice($this->admin);
        $long = $this->invoice($this->admin, 14);
        $small2 = $this->invoice($this->admin);
        $pdf = $this->pdf($this->batch([$small, $long, $small2]));

        $this->assertGreaterThanOrEqual(3, count($pdf->getPages())); // small | long (1+ page) | small2
        $text = $pdf->getText();
        foreach ([$small, $long, $small2] as $inv) {
            $this->assertStringContainsString($inv->invoice_number, $text);
        }
        $this->assertStringContainsString('Passenger 14', $text);
    }

    public function test_download_returns_an_attachment(): void
    {
        $a = $this->invoice($this->admin);
        $res = $this->actingAs($this->admin)->get(route('erp.invoices.print-selected', ['ids' => (string) $a->id, 'download' => 1]))->assertOk();

        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
    }

    public function test_other_agencies_invoices_are_never_printed(): void
    {
        $mine = $this->invoice($this->admin);
        $theirs = $this->invoice($this->otherAdmin);

        $text = $this->pdf($this->actingAs($this->admin)->get(route('erp.invoices.print-selected', ['ids' => "{$mine->id},{$theirs->id}", 'raw' => 1])))->getText();
        $this->assertStringContainsString($mine->invoice_number, $text);
        $this->assertStringNotContainsString($theirs->invoice_number, $text);

        $this->actingAs($this->admin)->get(route('erp.invoices.print-selected', ['ids' => (string) $theirs->id, 'raw' => 1]))->assertNotFound();
    }

    public function test_ids_are_validated(): void
    {
        $this->actingAs($this->admin);
        $this->get(route('erp.invoices.print-selected'))->assertStatus(422);
        $this->get(route('erp.invoices.print-selected', ['ids' => 'abc']))->assertStatus(422);
        $this->get(route('erp.invoices.print-selected', ['ids' => '1,,2']))->assertStatus(422);
        $this->get(route('erp.invoices.print-selected', ['ids' => implode(',', range(1, 51))]))->assertStatus(422);
        $this->get(route('erp.invoices.print-selected', ['ids' => '999999']))->assertNotFound();
    }

    public function test_guests_cannot_print(): void
    {
        $this->get(route('erp.invoices.print-selected', ['ids' => '1']))->assertRedirect(route('login'));
    }

    public function test_list_offers_selection_and_print_selected(): void
    {
        $this->invoice($this->admin);

        $this->actingAs($this->admin)->get(route('erp.invoices.index'))
            ->assertOk()
            ->assertSee('data-invoice-check', false)
            ->assertSee('Print selected');
    }
}
