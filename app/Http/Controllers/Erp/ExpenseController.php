<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
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

        $expenses = Expense::forAgency($agencyId)
            ->with('createdBy:id,name')
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->get();

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

    public function store(Request $request)
    {
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

        $expense->update($this->validated($request) + ['updated_by' => auth()->id()]);

        return redirect()->route('erp.expenses')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $this->authorizeAgency($expense);

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
