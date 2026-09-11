<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\Expense;
use App\Services\CsvImportService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ERP Expenses (E3, sub-phase 1). Agency-scoped money-OUT log.
 *
 * Low-risk: no ledger, no cache, no ErpPaymentService. Each row is the full
 * outflow. Everything is scoped to the caller's own agency_id (never null),
 * and every row action re-checks ownership (abort 403 otherwise).
 *
 * E7d adds CSV export (staff-visible) + import (admin-only) — the first import
 * carrying a money amount. Import reuses the EXACT rules() (amount gt:0 rejects
 * zero/negatives) and the lenient category/paid_via key-or-label mapping is built
 * ONLY from the model constants, so the deliberately-absent "agent_commission"
 * category is architecturally impossible to import.
 */
class ExpenseController extends Controller
{
    use RendersPrintableList;

    private const CSV_HEADERS = ['expense_date', 'category', 'amount', 'paid_via', 'note'];

    private const IMPORT_SESSION_KEY = 'erp_expense_import_path';

    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $expenses = $this->listing($agencyId);

        $now = now();
        $monthTotal = (float) $expenses
            ->filter(fn ($e) => $e->expense_date->year === $now->year && $e->expense_date->month === $now->month)
            ->sum(fn ($e) => (float) $e->amount);
        $allTimeTotal = (float) $expenses->sum(fn ($e) => (float) $e->amount);

        // By-category breakdown (all-time), largest first, for the summary strip.
        $byCategory = $expenses
            ->groupBy('category')
            ->map(fn ($rows) => (float) $rows->sum(fn ($e) => (float) $e->amount))
            ->sortDesc();

        return view('erp.expense.index', [
            'expenses'     => $expenses,
            'categories'   => Expense::CATEGORIES,
            'paidVia'      => Expense::PAID_VIA,
            'monthTotal'   => $monthTotal,
            'allTimeTotal' => $allTimeTotal,
            'byCategory'   => $byCategory,
        ]);
    }

    /** Print the full module list (E7a) — reuses the EXACT index() query + total. */
    public function printPdf(PdfGeneratorService $pdf)
    {
        $expenses = $this->listing(auth()->user()->agency_id);

        $money = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $total = (float) $expenses->sum(fn ($e) => (float) $e->amount);

        $columns = [
            ['label' => 'Date'], ['label' => 'Category'], ['label' => 'Paid Via'],
            ['label' => 'Amount', 'align' => 'right'], ['label' => 'Note'],
        ];
        $rows = $expenses->map(fn (Expense $e) => [
            $e->expense_date->format('d M Y'), $e->categoryLabel(), $e->paidViaLabel() ?: '—',
            $money($e->amount), $e->note ?: '—',
        ])->all();

        $totals = ['Total', '', '', $money($total), ''];

        return $this->respondPrintableList($pdf, [
            'title'    => 'Expenses',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $expenses->count() . ' expense' . ($expenses->count() === 1 ? '' : 's') . ' · Total ' . $money($total),
            'columns'  => $columns,
            'rows'     => $rows,
            'totals'   => $totals,
        ], 'expenses-' . now()->format('Y-m-d'), 'erp.expenses');
    }

    /** Shared listing used by both index() and printPdf() (newest-first). */
    private function listing(int $agencyId): Collection
    {
        return Expense::forAgency($agencyId)
            ->with('createdBy:id,name')
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $data = $this->validated($request);

        Expense::create($data + [
            'agency_id'  => auth()->user()->agency_id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('erp.expenses')->with('success', 'Expense added.');
    }

    public function update(Request $request, Expense $expense)
    {
        $this->authorizeAgency($expense);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $expense->update($this->validated($request) + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.expenses')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorizeAgency($expense);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only

        $expense->delete();

        return redirect()->route('erp.expenses')->with('success', 'Expense deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate($this->rules());
    }

    /** Single source of truth for validation — shared by manual Add and CSV import. */
    private function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'category'     => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'amount'       => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'paid_via'     => ['nullable', Rule::in(array_keys(Expense::PAID_VIA))],
            'note'         => ['nullable', 'string', 'max:255'],
        ];
    }

    /** CSV data export (E7d) — staff-visible; header matches the import template. */
    public function exportCsv(): StreamedResponse
    {
        $expenses = $this->listing(auth()->user()->agency_id);

        return response()->streamDownload(function () use ($expenses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_HEADERS);
            foreach ($expenses as $e) {
                fputcsv($out, [
                    optional($e->expense_date)->format('Y-m-d'),
                    $e->categoryLabel(),
                    number_format((float) $e->amount, 2, '.', ''), // plain decimal, no thousands sep
                    $e->paidViaLabel(),
                    $e->note,
                ]);
            }
            fclose($out);
        }, 'expenses-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
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
        }, 'expenses-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function importPreview(Request $request, CsvImportService $importer)
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $path   = $request->file('file')->store('erp-imports');
        $result = $importer->process(Storage::path($path), $this->importConfig());

        if ($result['fileError']) {
            Storage::delete($path);
            return redirect()->route('erp.expenses.import.form')->with('error', $result['fileError']);
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
            return redirect()->route('erp.expenses.import.form')
                ->with('error', 'Import session expired — please upload the file again.');
        }

        $result = $importer->process(Storage::path($path), $this->importConfig());

        if (! $result['ok']) {
            return view('erp.import.form', $this->importView() + ['result' => $result])
                ->withErrors(['file' => 'Some rows are invalid — nothing was imported. Fix and re-upload.']);
        }

        DB::transaction(function () use ($result, $agencyId) {
            foreach ($result['rows'] as $row) {
                Expense::create($row['attrs'] + [
                    'agency_id'  => $agencyId,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        });

        $count = $result['total'];
        Storage::delete($path);
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return redirect()->route('erp.expenses')
            ->with('success', "Imported {$count} expense" . ($count === 1 ? '' : 's') . '.');
    }

    /** Shared-view props for the parameterized import screen. */
    private function importView(): array
    {
        $cats = collect(Expense::CATEGORIES)->map(fn ($l, $k) => "$k ($l)")->implode(', ');

        return [
            'title'         => 'Import Expenses — CSV',
            'heading'       => 'Import Expenses from CSV',
            'back'          => 'erp.expenses',
            'formRoute'     => 'erp.expenses.import.form',
            'templateRoute' => 'erp.expenses.import.template',
            'previewRoute'  => 'erp.expenses.import.preview',
            'commitRoute'   => 'erp.expenses.import',
            'columnsHint'   => 'expense_date*, category*, amount*, paid_via, note',
            'legend'        => [
                'amount: a positive number (greater than 0), max 2 decimals, no thousands separators.',
                "category: accepts the key or its label — {$cats}.",
            ],
            'previewCols'   => [
                ['label' => 'Date', 'key' => 'expense_date'],
                ['label' => 'Category', 'key' => 'category'],
                ['label' => 'Amount', 'key' => 'amount'],
                ['label' => 'Paid Via', 'key' => 'paid_via'],
            ],
        ];
    }

    /**
     * Import config: date → Y-m-d; lenient category + paid_via (key OR label,
     * case-insensitive). The label→key maps are built ONLY from the model
     * constants, so "agent_commission" — which is intentionally NOT a category —
     * can never match and always fails Rule::in (all-or-nothing blocks the file).
     */
    private function importConfig(): array
    {
        $catByLabel = [];
        foreach (Expense::CATEGORIES as $key => $label) {
            $catByLabel[strtolower($label)] = $key;
        }
        $viaByLabel = [];
        foreach (Expense::PAID_VIA as $key => $label) {
            $viaByLabel[strtolower($label)] = $key;
        }

        $resolve = function (string $val, array $constMap, array $labelMap) {
            $lower = strtolower(trim($val));
            if ($lower === '') {
                return '';
            }
            if (array_key_exists($lower, $constMap)) {
                return $lower;                 // matched a key
            }
            return $labelMap[$lower] ?? $val;  // matched a label, else leave → Rule::in fails
        };

        return [
            'headers' => self::CSV_HEADERS,
            'rules'   => $this->rules(),
            'normalize' => function (array $r) use ($resolve, $catByLabel, $viaByLabel) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                $a['expense_date'] = CsvImportService::toYmd($a['expense_date'] ?? '');

                // amount: strip a stray leading currency symbol/spaces; leave the
                // numeric string for the `numeric`/`gt:0` rules to judge.
                $a['amount'] = ltrim($a['amount'] ?? '', " ৳\t");

                $a['category'] = $resolve($a['category'] ?? '', Expense::CATEGORIES, $catByLabel);

                $pv = $resolve($a['paid_via'] ?? '', Expense::PAID_VIA, $viaByLabel);
                $a['paid_via'] = $pv === '' ? null : $pv;

                if (($a['note'] ?? '') === '') {
                    $a['note'] = null;
                }

                return $a;
            },
        ];
    }

    private function authorizeAgency(Expense $expense): void
    {
        abort_unless($expense->agency_id === auth()->user()->agency_id, 403);
    }
}
