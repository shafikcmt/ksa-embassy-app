<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * ERP Expenses (E3, sub-phase 1). Agency-scoped money-OUT log.
 *
 * Low-risk: no ledger, no cache, no ErpPaymentService. Each row is the full
 * outflow. Everything is scoped to the caller's own agency_id (never null),
 * and every row action re-checks ownership (abort 403 otherwise).
 */
class ExpenseController extends Controller
{
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

        return $pdf->generateFromView('erp.print.list', [
            'title'    => 'Expenses',
            'agency'   => auth()->user()->agency,
            'generated'=> now(),
            'subtitle' => $expenses->count() . ' expense' . ($expenses->count() === 1 ? '' : 's') . ' · Total ' . $money($total),
            'columns'  => $columns,
            'rows'     => $rows,
            'totals'   => $totals,
        ], 'expenses-' . now()->format('Y-m-d'));
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
        return $request->validate([
            'expense_date' => ['required', 'date'],
            'category'     => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'amount'       => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'paid_via'     => ['nullable', Rule::in(array_keys(Expense::PAID_VIA))],
            'note'         => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function authorizeAgency(Expense $expense): void
    {
        abort_unless($expense->agency_id === auth()->user()->agency_id, 403);
    }
}
