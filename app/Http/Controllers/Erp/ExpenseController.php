<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\RendersPrintableList;
use App\Models\Expense;
use App\Models\ExpenseHead;
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
 * CSV export remains staff-visible; imports and manual money changes are
 * admin-only. Imports retain the five-column format and resolve active agency
 * head codes/names plus known legacy labels. Agent payouts belong to Khata.
 */
class ExpenseController extends Controller
{
    use RendersPrintableList;

    private const CSV_HEADERS = ['expense_date', 'category', 'amount', 'paid_via', 'note'];

    private const IMPORT_SESSION_KEY = 'erp_expense_import_path';

    public function index(Request $request)
    {
        $agencyId = auth()->user()->agency_id;

        $expenses = $this->listing($agencyId);
        $allExpenses = $this->listing($agencyId, false);

        $now = now();
        $monthTotal = (float) $allExpenses
            ->filter(fn ($e) => $e->expense_date->year === $now->year && $e->expense_date->month === $now->month)
            ->sum(fn ($e) => (float) $e->amount);
        $allTimeTotal = (float) $allExpenses->sum(fn ($e) => (float) $e->amount);

        // By-category breakdown (all-time), largest first, for the summary strip.
        $byCategory = $allExpenses
            ->groupBy(fn ($expense) => $expense->categoryLabel())
            ->map(fn ($rows) => (float) $rows->sum(fn ($e) => (float) $e->amount))
            ->sortDesc();

        return view('erp.expense.index', [
            'expenses'     => $expenses,
            'heads'        => ExpenseHead::forAgency($agencyId)->ordered()->get(),
            'selectedHead' => (string) $request->query('expense_head_id', ''),
            'legacyCategories' => $allExpenses->whereNull('expense_head_id')->mapWithKeys(fn ($e) => [$e->category => $e->categoryLabel()]),
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

    /** Shared listing used by both index() and printPdf() (oldest-first). */
    private function listing(int $agencyId, bool $filtered = true): Collection
    {
        return Expense::forAgency($agencyId)
            ->with(['createdBy:id,name', 'paymentVoucher:id,voucher_number', 'expenseHead'])
            ->when($filtered && request('expense_head_id'), fn ($q) => $q->where('expense_head_id', request('expense_head_id')))
            ->when($filtered && request('category'), fn ($q) => $q->where('category', request('category')))
            ->orderBy('expense_date')->orderBy('id')
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
        if ($blocked = $this->guardSystemGenerated($expense)) {
            return $blocked;
        }

        $expense->update($this->validated($request, $expense) + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.expenses')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorizeAgency($expense);
        abort_unless(auth()->user()->isAgencyAdmin(), 403); // money-moving action: admin-only
        if ($blocked = $this->guardSystemGenerated($expense)) {
            return $blocked;
        }

        $expense->delete();

        return redirect()->route('erp.expenses')->with('success', 'Expense deleted.');
    }

    /**
     * Expenses booked from a paid Payment Voucher mirror that voucher exactly —
     * editing/deleting them here would make Expenses/P&L disagree with it.
     */
    private function guardSystemGenerated(Expense $expense)
    {
        if (! $expense->isSystemGenerated()) {
            return null;
        }

        return redirect()->route('erp.expenses')->with('error',
            'This expense was created automatically from payment voucher '
            . ($expense->paymentVoucher?->voucher_number ?? '#' . $expense->payment_voucher_id)
            . ' and cannot be edited or deleted here.');
    }

    private function validated(Request $request, ?Expense $expense = null): array
    {
        $head = ExpenseHead::forAgency(auth()->user()->agency_id)
            ->when($request->filled('expense_head_id'), fn ($q) => $q->whereKey($request->input('expense_head_id')),
                fn ($q) => $q->where('code', $request->input('category')))->first();
        if ($head) {
            $request->merge(['expense_head_id' => $head->id, 'category' => $head->code]);
        }
        return $request->validate($this->rules($expense));
    }

    /** Single source of truth for validation — shared by manual Add and CSV import. */
    private function rules(?Expense $expense = null): array
    {
        $heads = ExpenseHead::forAgency(auth()->user()->agency_id)->where('is_active', true)->get();
        $ids = $heads->pluck('id')->all();
        $codes = $heads->pluck('code')->all();
        if ($expense) {
            $codes[] = $expense->category;
            if ($expense->expense_head_id) $ids[] = $expense->expense_head_id;
        }
        return [
            'expense_date' => ['required', 'date'],
            'category'     => ['required', Rule::in($codes)],
            'expense_head_id' => [$expense && ! $expense->expense_head_id ? 'nullable' : 'required', 'integer', Rule::in($ids)],
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
                    $e->isSystemGenerated()
                        ? 'voucher:' . ($e->paymentVoucher?->voucher_number ?? $e->payment_voucher_id) . ' — ' . $e->categoryLabel()
                        : $e->categoryLabel(),
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
        $cats = ExpenseHead::forAgency(auth()->user()->agency_id)->where('is_active', true)->ordered()
            ->get()->map(fn ($head) => "$head->code ($head->name)")->implode(', ');

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
                'Auto-booked voucher rows exported with a voucher: marker are read-only and cannot be imported.',
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
     * Import config: date → Y-m-d; active agency head code/name or legacy label,
     * and paid_via key/label (case-insensitive). Unknown/inactive categories fail
     * validation; the commit revalidates every row before importing any of them.
     */
    private function importConfig(): array
    {
        $catByLabel = [];
        $categories = ExpenseHead::forAgency(auth()->user()->agency_id)->where('is_active', true)->pluck('name', 'code')->all();
        $headIds = ExpenseHead::forAgency(auth()->user()->agency_id)->where('is_active', true)->pluck('id', 'code')->all();
        foreach ($categories as $key => $label) {
            $catByLabel[strtolower($label)] = $key;
        }
        foreach (Expense::CATEGORIES as $key => $label) {
            if (isset($categories[$key]) && ! isset($catByLabel[strtolower($label)])) $catByLabel[strtolower($label)] = $key;
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
            'normalize' => function (array $r) use ($resolve, $catByLabel, $viaByLabel, $categories, $headIds) {
                $a = array_map(fn ($v) => trim((string) $v), $r);

                $a['expense_date'] = CsvImportService::toYmd($a['expense_date'] ?? '');

                // amount: strip a stray leading currency symbol/spaces; leave the
                // numeric string for the `numeric`/`gt:0` rules to judge.
                $a['amount'] = ltrim($a['amount'] ?? '', " ৳\t");

                // Keep voucher exports read-only even when their meaningful head is active.
                $a['category'] = str_starts_with(strtolower($a['category'] ?? ''), 'voucher:')
                    ? 'payment_voucher' : $resolve($a['category'] ?? '', $categories, $catByLabel);
                $a['expense_head_id'] = $headIds[$a['category']] ?? null;

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
