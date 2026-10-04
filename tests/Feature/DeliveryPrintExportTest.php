<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Delivery;
use App\Models\Expense;
use App\Models\Plan;
use App\Models\Stamping;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PdfGeneratorService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Delivery print preview, PDF and CSV export share one column set; the shared print template stays unchanged for other modules. */
class DeliveryPrintExportTest extends TestCase
{
    use RefreshDatabase;

    private const COLUMNS = ['Name', 'Date', 'Passport', 'Total', 'Paid', 'Due', 'Status', 'Payment', 'Reference'];

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $plan = Plan::create(['name' => 'Test', 'slug' => 'test', 'price' => 0, 'duration_days' => 365, 'is_active' => true]);
        $this->agency = $this->makeAgency('alpha', $plan);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@alpha.test', 'password' => Hash::make('secret1234'), 'agency_id' => $this->agency->id, 'is_super_admin' => false, 'is_active' => true]);
        $this->admin->assignRole('agency_admin');

        // The PDF path returns the HTML mPDF would receive, so its table can be inspected.
        $this->app->instance(PdfGeneratorService::class, new class extends PdfGeneratorService {
            public function generateFromView(string $view, array $data, string $filename, bool $inline = false, array $options = []): Response
            {
                return response(view($view, array_merge($data, ['_pdf' => true]))->render());
            }
        });
    }

    private function makeAgency(string $slug, Plan $plan): Agency
    {
        $agency = Agency::create(['name' => ucfirst($slug).' Agency', 'slug' => $slug, 'status' => 'active']);
        Subscription::create(['agency_id' => $agency->id, 'plan_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active', 'payment_status' => 'paid', 'amount' => 0]);

        return $agency;
    }

    private function delivery(Agency $agency, array $overrides = []): Delivery
    {
        return Delivery::create($overrides + ['agency_id' => $agency->id, 'delivery_date' => '2026-09-01', 'full_name' => 'Mohammad Hosen', 'passport_no' => 'A01234567', 'visa_serial' => 'VS-SECRET', 'reference' => 'Horizon', 'total_amount' => 15000, 'status' => 'pending', 'payment_method' => 'cash']);
    }

    /** @return array{0: string[], 1: string[][], 2: string[]} headers, body rows, totals */
    private function table(string $html): array
    {
        $cells = fn (string $s, string $tag) => array_map(fn ($c) => trim(html_entity_decode(strip_tags($c))), preg_match_all("~<{$tag}[^>]*>(.*?)</{$tag}>~s", $s, $m) ? $m[1] : []);
        $body = substr($html, strpos($html, '<tbody>'), strpos($html, '</tbody>') - strpos($html, '<tbody>'));
        preg_match_all('~<tr>(.*?)</tr>~s', $body, $rows);
        $foot = str_contains($html, '<tfoot>') ? substr($html, strpos($html, '<tfoot>')) : '';

        return [$cells(substr($html, strpos($html, '<thead>'), strpos($html, '</thead>') - strpos($html, '<thead>')), 'th'), array_map(fn ($r) => $cells($r, 'td'), $rows[1]), $cells($foot, 'td')];
    }

    public function test_preview_pdf_and_export_share_columns_and_order_without_visa_serial(): void
    {
        $this->delivery($this->agency);
        $this->delivery($this->agency, ['delivery_date' => '2026-09-02', 'full_name' => 'Rahim', 'passport_no' => 'B7654321', 'reference' => null, 'total_amount' => 2500.5, 'payment_method' => null]);
        $other = $this->makeAgency('beta', Plan::first());
        $this->delivery($other, ['full_name' => 'Other Agency Person', 'passport_no' => 'Z9999999']);

        $this->actingAs($this->admin);
        [$previewHead, $previewRows, $previewTotals] = $this->table($this->get(route('erp.delivery.print'))->assertOk()->getContent());
        [$pdfHead, $pdfRows, $pdfTotals] = $this->table($this->get(route('erp.delivery.print', ['download' => 1]))->assertOk()->getContent());
        $csv = $this->get(route('erp.delivery.export'))->assertOk()->streamedContent();

        $this->assertSame(self::COLUMNS, array_map('ucfirst', array_map('strtolower', $previewHead)));
        $this->assertSame($previewHead, $pdfHead);
        $this->assertSame($previewRows, $pdfRows);
        $this->assertSame(['Mohammad Hosen', '01 Sep 2026', 'A01234567', '৳ 15,000.00', '৳ 0.00', '৳ 15,000.00', 'Pending', 'Cash', 'Horizon'], $previewRows[0]);
        $this->assertSame(['Totals', '', '', '৳ 17,500.50', '৳ 0.00', '৳ 17,500.50', '', '', ''], $previewTotals);
        $this->assertSame($previewTotals, $pdfTotals);

        // CSV: UTF-8 BOM, the same headers and order, plain numeric amounts, no totals row.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lines = array_map('str_getcsv', array_filter(explode("\n", trim(substr($csv, 3)))));
        $this->assertSame(self::COLUMNS, $lines[0]);
        $this->assertSame(['Mohammad Hosen', '01 Sep 2026', 'A01234567', '15000.00', '0.00', '15000.00', 'Pending', 'Cash', 'Horizon'], $lines[1]);
        $this->assertSame(['Rahim', '02 Sep 2026', 'B7654321', '2500.50', '0.00', '2500.50', 'Pending', '—', '—'], $lines[2]);
        $this->assertCount(3, $lines);
        $this->assertTrue(is_numeric($lines[1][3]) && is_numeric($lines[1][4]) && is_numeric($lines[1][5]));

        // Visa Serial is gone from every output; another agency's rows never appear.
        foreach ([$this->get(route('erp.delivery.print'))->getContent(), $this->get(route('erp.delivery.print', ['download' => 1]))->getContent(), $csv] as $output) {
            $this->assertStringNotContainsStringIgnoringCase('Visa Serial', $output);
            $this->assertStringNotContainsString('VS-SECRET', $output);
            $this->assertStringNotContainsString('Other Agency Person', $output);
        }
    }

    public function test_date_does_not_wrap_and_import_format_is_unchanged(): void
    {
        $this->delivery($this->agency);
        $this->actingAs($this->admin);
        $html = $this->get(route('erp.delivery.print'))->getContent();
        $this->assertStringContainsString('<td class="l" style="white-space:nowrap;">01 Sep 2026</td>', $html);

        $template = $this->get(route('erp.delivery.import.template'))->assertOk()->streamedContent();
        $this->assertSame('delivery_date,full_name,passport_no,visa_serial,reference,total_amount,status', trim(strtok($template, "\n")));
    }

    public function test_shared_print_template_renders_other_modules_without_the_new_options(): void
    {
        $base = ['agency_id' => $this->agency->id, 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id];
        Stamping::create($base + ['stamp_date' => '2026-09-03', 'visa_serial' => 'V1', 'full_name' => 'Stamp Person', 'passport_no' => 'S1', 'visa_number' => '130', 'id_number' => '70', 'reference' => 'Ref', 'status' => 'pending']);
        Expense::create($base + ['expense_date' => '2026-09-05', 'category' => 'office_rent', 'amount' => 900, 'paid_via' => 'cash', 'note' => 'Rent']);
        $this->actingAs($this->admin);

        foreach (['erp.stamping.print', 'erp.expenses.print', 'erp.double-mofa.print', 'erp.agent-khata.print'] as $route) {
            foreach ([[], ['download' => 1]] as $query) {
                $html = $this->get(route($route, $query))->assertOk()->getContent();
                $list = substr($html, strrpos($html, '<thead>'));
                // Default cells are exactly as before: a bare l/r class, no inline style.
                $this->assertDoesNotMatchRegularExpression('~<t[hd] class="[lr]" style=~', $list, $route);
                $this->assertMatchesRegularExpression('~<th class="[lr]">~', $list, $route);
            }
        }
    }
}
