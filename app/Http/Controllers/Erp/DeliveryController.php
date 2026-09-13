<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\Delivery;
use App\Models\PaymentReceipt;
use App\Services\CsvImportService;
use App\Services\ErpPaymentService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Delivery (E2) — file delivery + payment collection. Money-bearing.
 *
 * All money mutations are delegated to ErpPaymentService (locked, ledger-based).
 * This controller only ever writes total_amount/status/meta via $fillable;
 * paid_amount is never mass-assigned. Everything is scoped to the caller's
 * agency_id, and every row action re-checks ownership (abort 403 otherwise).
 *
 * E7e adds CSV export (staff-visible) + import (admin-only). Import creates the
 * billing row ONLY, exactly like manual Add: paid_amount is not fillable, so it
 * defaults to 0 and the append-only payment_receipts ledger is never touched.
 * The importable columns are the manual-Add fields (paid/due are excluded — they
 * belong to Print/Reports, not the data-entry round-trip).
 */
class DeliveryController extends Controller
{
    use RendersPrintableList;

    private const CSV_HEADERS = [
        'delivery_date', 'full_name', 'passport_no',
        'visa_serial', 'reference', 'total_amount', 'status',
    ];

    private const IMPORT_SESSION_KEY = 'erp_delivery_import_path';

    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $deliveries = $this->listing($agencyId);

        $totalBilled    = (float) $deliveries->sum(fn ($d) => (float) $d->total_amount);
        $totalCollected = (float) $deliveries->sum(fn ($d) => (float) $d->paid_amount);

        return view('erp.delivery.index', [
            'deliveries'     => $deliveries,
            'statuses'       => Delivery::STATUSES,
            'paymentMethods' => Delivery::PAYMENT_METHODS,
            'totalBilled'    => $totalBilled,
            'totalCollected' => $totalCollected,
            'totalDue'       => $totalBilled - $totalCollected,
        ]);
    }

    /** Print the full module list (E7a) — reuses the EXACT index() query + totals. */
    public function printPdf(PdfGeneratorService $pdf)
    {
        $deliveries = $this->listing(auth()->user()->agency_id);

        $money = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $billed    = (float) $deliveries->sum(fn ($d) => (float) $d->total_amount);
        $collected = (float) $deliveries->sum(fn ($d) => (float) $d->paid_amount);

        $columns = [
            ['label' => 'Date'], ['label' => 'Name'], ['label' => 'Passport'], ['label' => 'Visa Serial'],
            ['label' => 'Reference'], ['label' => 'Total', 'align' => 'right'], ['label' => 'Paid', 'align' => 'right'],
            ['label' => 'Due', 'align' => 'right'], ['label' => 'Status'], ['label' => 'Payment'],
        ];
        $rows = $deliveries->map(fn (Delivery $d) => [
            $d->delivery_date->format('d M Y'), $d->full_name, $d->passport_no, $d->visa_serial ?: '—',
            $d->reference ?: '—', $money($d->total_amount), $money($d->paid_amount), $money($d->due), $d->statusLabel(), $d->paymentMethodLabel() ?: '—',
        ])->all();

        // Totals footer aligned to the money columns (indices 5/6/7).
        $totals = ['Totals', '', '', '', '', $money($billed), $money($collected), $money($billed - $collected), '', ''];

        return $this->respondPrintableList($pdf, [
            'title'    => 'Delivery',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $deliveries->count() . ' deliver' . ($deliveries->count() === 1 ? 'y' : 'ies')
                          . ' · Billed ' . $money($billed) . ' · Collected ' . $money($collected),
            'columns'  => $columns,
            'rows'     => $rows,
            'totals'   => $totals,
        ], 'delivery-' . now()->format('Y-m-d'), 'erp.delivery');
    }

    /** Shared listing used by both index() and printPdf() (newest-first). */
    private function listing(int $agencyId): Collection
    {
        return Delivery::forAgency($agencyId)
            ->with(['createdBy:id,name', 'receipts.receivedBy:id,name'])
            ->orderByDesc('delivery_date')->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Delivery::create($data + [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('erp.delivery')->with('success', 'Delivery entry added.');
    }

    public function update(Request $request, Delivery $delivery)
    {
        $this->authorizeAgency($delivery);

        $data = $this->validated($request);

        // Money-integrity guard: total can never drop below what is already paid.
        if ((float) $data['total_amount'] < (float) $delivery->paid_amount) {
            return back()
                ->with('error', 'Total amount cannot be less than the amount already paid ('.$delivery->paid_amount.').')
                ->withInput();
        }

        $delivery->update($data + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.delivery')->with('success', 'Delivery entry updated.');
    }

    public function destroy(Delivery $delivery)
    {
        $this->authorizeAgency($delivery);

        // Never orphan money: block deletion while payment records exist.
        if ($delivery->receipts()->exists()) {
            return back()->with('error', 'Cannot delete a delivery that has payment records. Reverse the payments first.');
        }

        $delivery->delete();

        return redirect()->route('erp.delivery')->with('success', 'Delivery entry deleted.');
    }

    public function receivePayment(Request $request, Delivery $delivery, ErpPaymentService $payments)
    {
        $this->authorizeAgency($delivery);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payments->receivePayment($delivery, (float) $validated['amount'], $validated['note'] ?? null, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Return to wherever the payment was taken (module page OR Due List),
        // falling back to the Delivery page if there is no referer.
        return redirect()->back(fallback: route('erp.delivery'))->with('success', 'Payment recorded.');
    }

    public function reverse(Request $request, PaymentReceipt $receipt, ErpPaymentService $payments)
    {
        // Must belong to this agency AND to a Delivery (not a Double MOFA row).
        abort_unless($receipt->agency_id === auth()->user()->agency_id, 403);
        abort_unless($receipt->payable_type === Delivery::class, 404);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:255'],
        ]);

        try {
            $payments->reversePayment($receipt, $validated['note'], auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('erp.delivery')->with('success', 'Payment reversed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate($this->rules());
    }

    /** Single source of truth for validation — shared by manual Add and CSV import. */
    private function rules(): array
    {
        return [
            'delivery_date' => ['required', 'date'],
            'full_name'     => ['required', 'string', 'max:255'],
            'passport_no'   => ['required', 'string', 'max:100'],
            'visa_serial'   => ['nullable', 'string', 'max:100'],
            'reference'     => ['nullable', 'string', 'max:255'],
            'total_amount'   => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'status'         => ['required', Rule::in(array_keys(Delivery::STATUSES))],
            'payment_method' => ['nullable', Rule::in(array_keys(Delivery::PAYMENT_METHODS))],
        ];
    }

    /** CSV data export (E7e) — staff-visible; header matches the import template. */
    public function exportCsv(): StreamedResponse
    {
        $deliveries = $this->listing(auth()->user()->agency_id);

        return response()->streamDownload(function () use ($deliveries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($deliveries as $d) {
                fputcsv($out, [
                    optional($d->delivery_date)->format('Y-m-d'),
                    $d->full_name, $d->passport_no, $d->visa_serial, $d->reference,
                    number_format((float) $d->total_amount, 2, '.', ''), // plain decimal, no thousands sep
                    $d->statusLabel(),
                ]);
            }
            fclose($out);
        }, 'delivery-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
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
        }, 'delivery-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $path   = $request->file('file')->store('erp-imports');
        $result = $importer->process(Storage::path($path), $this->importConfig(auth()->user()->agency_id));

        if ($result['fileError']) {
            Storage::delete($path);
            return redirect()->route('erp.delivery.import.form')->with('error', $result['fileError']);
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
            return redirect()->route('erp.delivery.import.form')
                ->with('error', 'Import session expired — please upload the file again.');
        }

        $result = $importer->process(Storage::path($path), $this->importConfig($agencyId));

        if (! $result['ok']) {
            return view('erp.import.form', $this->importView() + ['result' => $result])
                ->withErrors(['file' => 'Some rows are invalid — nothing was imported. Fix and re-upload.']);
        }

        // Creates the billing row ONLY. paid_amount is not fillable → defaults 0;
        // no ErpPaymentService call, so payment_receipts is never written.
        DB::transaction(function () use ($result, $agencyId) {
            foreach ($result['rows'] as $row) {
                Delivery::create($row['attrs'] + [
                    'agency_id'  => $agencyId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $count = $result['total'];
        Storage::delete($path);
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return redirect()->route('erp.delivery')
            ->with('success', "Imported {$count} deliver" . ($count === 1 ? 'y' : 'ies') . '.');
    }

    /** Shared-view props for the parameterized import screen. */
    private function importView(): array
    {
        $statusValues = collect(Delivery::STATUSES)->map(fn ($l, $k) => "$k ($l)")->implode(', ');

        return [
            'title'         => 'Import Delivery — CSV',
            'heading'       => 'Import Delivery entries from CSV',
            'back'          => 'erp.delivery',
            'formRoute'     => 'erp.delivery.import.form',
            'templateRoute' => 'erp.delivery.import.template',
            'previewRoute'  => 'erp.delivery.import.preview',
            'commitRoute'   => 'erp.delivery.import',
            'columnsHint'   => 'delivery_date*, full_name*, passport_no*, visa_serial, reference, total_amount*, status*',
            'legend'        => [
                'total_amount: a number (0 or more), max 2 decimals, no thousands separators. Paid amount is NOT imported — every row starts unpaid; collect payments in the module.',
                "status: accepts the key or its label — {$statusValues}.",
            ],
            'previewCols'   => [
                ['label' => 'Date', 'key' => 'delivery_date'],
                ['label' => 'Name', 'key' => 'full_name'],
                ['label' => 'Passport', 'key' => 'passport_no'],
                ['label' => 'Total', 'key' => 'total_amount'],
                ['label' => 'Status', 'key' => 'status'],
            ],
        ];
    }

    /** Import config: date → Y-m-d, currency-strip total_amount, lenient status key/label, duplicate-passport notice. */
    private function importConfig(int $agencyId): array
    {
        $labelToKey = [];
        foreach (Delivery::STATUSES as $key => $label) {
            $labelToKey[strtolower($label)] = $key;
        }

        return [
            'headers' => self::CSV_HEADERS,
            'rules'   => $this->rules(),
            'normalize' => function (array $r) use ($labelToKey) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                $a['delivery_date'] = CsvImportService::toYmd($a['delivery_date'] ?? '');

                // amount: strip a stray leading currency symbol/spaces; leave the
                // numeric string for the `numeric`/`min:0` rules to judge.
                $a['total_amount'] = ltrim($a['total_amount'] ?? '', " ৳\t");

                $st = $a['status'] ?? '';
                if ($st !== '') {
                    $lower = strtolower($st);
                    if (array_key_exists($lower, Delivery::STATUSES)) {
                        $a['status'] = $lower;
                    } elseif (isset($labelToKey[$lower])) {
                        $a['status'] = $labelToKey[$lower];
                    }
                }

                foreach (['visa_serial', 'reference'] as $f) {
                    if (($a[$f] ?? '') === '') {
                        $a[$f] = null;
                    }
                }

                return $a;
            },
            'notices' => function (array $a) use ($agencyId) {
                $p = $a['passport_no'] ?? '';
                if ($p !== '' && Delivery::forAgency($agencyId)->where('passport_no', $p)->exists()) {
                    return ["Passport {$p} already has a delivery — added as a repeat."];
                }
                return [];
            },
        ];
    }

    private function authorizeAgency(Delivery $delivery): void
    {
        abort_unless($delivery->agency_id === auth()->user()->agency_id, 403);
    }
}
