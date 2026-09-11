<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\DoubleMofa;
use App\Models\ErpSetting;
use App\Models\PaymentReceipt;
use App\Services\CsvImportService;
use App\Services\ErpPaymentService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Double MOFA (E2) — passports billed for a repeated MOFA. Money-bearing.
 *
 * billing_amount is SNAPSHOTTED from erp_settings.double_mofa_rate at creation
 * (pre-filled in the form, editable per row) so later rate changes never rewrite
 * historical bills. Status (unpaid/partial/paid) is payment-derived and only
 * ErpPaymentService writes paid_amount + status. All actions are agency-scoped.
 *
 * E7e adds CSV export (staff-visible) + import (admin-only). Import mirrors manual
 * Add EXACTLY: billing_amount comes straight from the CSV column (the rate is only
 * a form pre-fill, which manual Add never reads at store() time, so import does not
 * either); there is NO status column (status is payment-derived and defaults to
 * 'unpaid'); paid_amount is not fillable → defaults 0 and the payment_receipts
 * ledger is never touched. Importable columns are the manual-Add fields only.
 */
class DoubleMofaController extends Controller
{
    use RendersPrintableList;

    private const CSV_HEADERS = [
        'mofa_date', 'full_name', 'passport_no',
        'visa_serial', 'reference', 'billing_amount',
    ];

    private const IMPORT_SESSION_KEY = 'erp_double_mofa_import_path';

    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $entries = $this->listing($agencyId);

        $totalBilled    = (float) $entries->sum(fn ($e) => (float) $e->billing_amount);
        $totalCollected = (float) $entries->sum(fn ($e) => (float) $e->paid_amount);

        return view('erp.double-mofa.index', [
            'entries'        => $entries,
            'statuses'       => DoubleMofa::STATUSES,
            'defaultRate'    => $this->currentRate($agencyId),
            'totalBilled'    => $totalBilled,
            'totalCollected' => $totalCollected,
            'totalDue'       => $totalBilled - $totalCollected,
        ]);
    }

    /** Print the full module list (E7a) — reuses the EXACT index() query + totals. */
    public function printPdf(PdfGeneratorService $pdf)
    {
        $entries = $this->listing(auth()->user()->agency_id);

        $money = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $billed    = (float) $entries->sum(fn ($e) => (float) $e->billing_amount);
        $collected = (float) $entries->sum(fn ($e) => (float) $e->paid_amount);

        $columns = [
            ['label' => 'Date'], ['label' => 'Name'], ['label' => 'Visa Serial'], ['label' => 'Passport'],
            ['label' => 'Reference'], ['label' => 'Billed', 'align' => 'right'], ['label' => 'Paid', 'align' => 'right'],
            ['label' => 'Unpaid', 'align' => 'right'], ['label' => 'Status'],
        ];
        $rows = $entries->map(fn (DoubleMofa $e) => [
            $e->mofa_date->format('d M Y'), $e->full_name, $e->visa_serial ?: '—', $e->passport_no,
            $e->reference ?: '—', $money($e->billing_amount), $money($e->paid_amount), $money($e->unpaid), $e->statusLabel(),
        ])->all();

        $totals = ['Totals', '', '', '', '', $money($billed), $money($collected), $money($billed - $collected), ''];

        return $this->respondPrintableList($pdf, [
            'title'    => 'Double MOFA',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $entries->count() . ' entr' . ($entries->count() === 1 ? 'y' : 'ies')
                          . ' · Billed ' . $money($billed) . ' · Collected ' . $money($collected),
            'columns'  => $columns,
            'rows'     => $rows,
            'totals'   => $totals,
        ], 'double-mofa-' . now()->format('Y-m-d'), 'erp.double-mofa');
    }

    /** Shared listing used by both index() and printPdf() (newest-first). */
    private function listing(int $agencyId): Collection
    {
        return DoubleMofa::forAgency($agencyId)
            ->with(['createdBy:id,name', 'receipts.receivedBy:id,name'])
            ->orderByDesc('mofa_date')->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // Snapshot: whatever billing_amount is submitted is frozen on the row.
        DoubleMofa::create($data + [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            // status defaults to 'unpaid'; paid_amount defaults 0 (not fillable).
        ]);

        return redirect()->route('erp.double-mofa')->with('success', 'Double MOFA entry added.');
    }

    public function update(Request $request, DoubleMofa $doubleMofa, ErpPaymentService $payments)
    {
        $this->authorizeAgency($doubleMofa);

        $data = $this->validated($request);

        // Money-integrity guard: billing can never drop below what is already paid.
        if ((float) $data['billing_amount'] < (float) $doubleMofa->paid_amount) {
            return back()
                ->with('error', 'Billing amount cannot be less than the amount already paid ('.$doubleMofa->paid_amount.').')
                ->withInput();
        }

        $doubleMofa->update($data + ['updated_by' => auth()->id()]);

        // Billing changed → re-derive payment status (unpaid/partial/paid) from
        // the ledger under a lock. paid_amount is untouched (ledger unchanged).
        $payments->recompute($doubleMofa->refresh());

        return redirect()->route('erp.double-mofa')->with('success', 'Double MOFA entry updated.');
    }

    public function destroy(DoubleMofa $doubleMofa)
    {
        $this->authorizeAgency($doubleMofa);

        if ($doubleMofa->receipts()->exists()) {
            return back()->with('error', 'Cannot delete a Double MOFA that has payment records. Reverse the payments first.');
        }

        $doubleMofa->delete();

        return redirect()->route('erp.double-mofa')->with('success', 'Double MOFA entry deleted.');
    }

    public function receivePayment(Request $request, DoubleMofa $doubleMofa, ErpPaymentService $payments)
    {
        $this->authorizeAgency($doubleMofa);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payments->receivePayment($doubleMofa, (float) $validated['amount'], $validated['note'] ?? null, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Return to wherever the payment was taken (module page OR Due List),
        // falling back to the Double MOFA page if there is no referer.
        return redirect()->back(fallback: route('erp.double-mofa'))->with('success', 'Payment recorded.');
    }

    public function reverse(Request $request, PaymentReceipt $receipt, ErpPaymentService $payments)
    {
        abort_unless($receipt->agency_id === auth()->user()->agency_id, 403);
        abort_unless($receipt->payable_type === DoubleMofa::class, 404);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:255'],
        ]);

        try {
            $payments->reversePayment($receipt, $validated['note'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('erp.double-mofa')->with('success', 'Payment reversed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate($this->rules());
    }

    /** Single source of truth for validation — shared by manual Add and CSV import. */
    private function rules(): array
    {
        return [
            'mofa_date'      => ['required', 'date'],
            'full_name'      => ['required', 'string', 'max:255'],
            'passport_no'    => ['required', 'string', 'max:100'],
            'visa_serial'    => ['nullable', 'string', 'max:100'],
            'reference'      => ['nullable', 'string', 'max:255'],
            'billing_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }

    /** CSV data export (E7e) — staff-visible; header matches the import template. */
    public function exportCsv(): StreamedResponse
    {
        $entries = $this->listing(auth()->user()->agency_id);

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($entries as $e) {
                fputcsv($out, [
                    optional($e->mofa_date)->format('Y-m-d'),
                    $e->full_name, $e->passport_no, $e->visa_serial, $e->reference,
                    number_format((float) $e->billing_amount, 2, '.', ''), // plain decimal, no thousands sep
                ]);
            }
            fclose($out);
        }, 'double-mofa-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function importForm()
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        return view('erp.import.form', $this->importView() + ['result' => null]);
    }

    public function importTemplate(): StreamedResponse
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            fclose($out);
        }, 'double-mofa-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $path   = $request->file('file')->store('erp-imports');
        $result = $importer->process(Storage::path($path), $this->importConfig(auth()->user()->agency_id));

        if ($result['fileError']) {
            Storage::delete($path);
            return redirect()->route('erp.double-mofa.import.form')->with('error', $result['fileError']);
        }

        $request->session()->put(self::IMPORT_SESSION_KEY, $path);

        return view('erp.import.form', $this->importView() + ['result' => $result]);
    }

    public function import(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);

        $agencyId = auth()->user()->agency_id;
        $path     = $request->session()->get(self::IMPORT_SESSION_KEY);

        if (! $path || ! Storage::exists($path)) {
            return redirect()->route('erp.double-mofa.import.form')
                ->with('error', 'Import session expired — please upload the file again.');
        }

        $result = $importer->process(Storage::path($path), $this->importConfig($agencyId));

        if (! $result['ok']) {
            return view('erp.import.form', $this->importView() + ['result' => $result])
                ->withErrors(['file' => 'Some rows are invalid — nothing was imported. Fix and re-upload.']);
        }

        // Creates the billing row ONLY, exactly like manual Add: billing_amount is
        // taken straight from the row; paid_amount is not fillable → defaults 0;
        // status defaults 'unpaid'. No ErpPaymentService call, so payment_receipts
        // is never written.
        DB::transaction(function () use ($result, $agencyId) {
            foreach ($result['rows'] as $row) {
                DoubleMofa::create($row['attrs'] + [
                    'agency_id'  => $agencyId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $count = $result['total'];
        Storage::delete($path);
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return redirect()->route('erp.double-mofa')
            ->with('success', "Imported {$count} Double MOFA entr" . ($count === 1 ? 'y' : 'ies') . '.');
    }

    /** Shared-view props for the parameterized import screen. */
    private function importView(): array
    {
        return [
            'title'         => 'Import Double MOFA — CSV',
            'heading'       => 'Import Double MOFA entries from CSV',
            'back'          => 'erp.double-mofa',
            'formRoute'     => 'erp.double-mofa.import.form',
            'templateRoute' => 'erp.double-mofa.import.template',
            'previewRoute'  => 'erp.double-mofa.import.preview',
            'commitRoute'   => 'erp.double-mofa.import',
            'columnsHint'   => 'mofa_date*, full_name*, passport_no*, visa_serial, reference, billing_amount*',
            'legend'        => [
                'billing_amount: a number (0 or more), max 2 decimals, no thousands separators. Set the exact amount per row (the settings rate is only a form default, not applied on import).',
                'Status and paid amount are NOT imported — every row starts Unpaid; collect payments in the module.',
            ],
            'previewCols'   => [
                ['label' => 'Date', 'key' => 'mofa_date'],
                ['label' => 'Name', 'key' => 'full_name'],
                ['label' => 'Passport', 'key' => 'passport_no'],
                ['label' => 'Billing', 'key' => 'billing_amount'],
            ],
        ];
    }

    /** Import config: date → Y-m-d, currency-strip billing_amount, duplicate-passport notice. No status column. */
    private function importConfig(int $agencyId): array
    {
        return [
            'headers' => self::CSV_HEADERS,
            'rules'   => $this->rules(),
            'normalize' => function (array $r) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                $a['mofa_date'] = CsvImportService::toYmd($a['mofa_date'] ?? '');

                // amount: strip a stray leading currency symbol/spaces; leave the
                // numeric string for the `numeric`/`min:0` rules to judge.
                $a['billing_amount'] = ltrim($a['billing_amount'] ?? '', " ৳\t");

                foreach (['visa_serial', 'reference'] as $f) {
                    if (($a[$f] ?? '') === '') {
                        $a[$f] = null;
                    }
                }

                return $a;
            },
            'notices' => function (array $a) use ($agencyId) {
                $p = $a['passport_no'] ?? '';
                if ($p !== '' && DoubleMofa::forAgency($agencyId)->where('passport_no', $p)->exists()) {
                    return ["Passport {$p} already has a Double MOFA — added as a repeat."];
                }
                return [];
            },
        ];
    }

    private function currentRate(int $agencyId): float
    {
        $settings = ErpSetting::forAgency($agencyId)->first();

        return $settings ? (float) $settings->double_mofa_rate : 3000.0;
    }

    private function authorizeAgency(DoubleMofa $doubleMofa): void
    {
        abort_unless($doubleMofa->agency_id === auth()->user()->agency_id, 403);
    }
}
