<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\ExpenseHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseHeadController extends Controller
{
    private function agencyId(): int
    {
        abort_unless(auth()->user()->isAgencyAdmin() && auth()->user()->agency_id, 403);

        return auth()->user()->agency_id;
    }

    private function owned(ExpenseHead $head): int
    {
        $agencyId = $this->agencyId();
        abort_unless($head->agency_id === $agencyId, 403);

        return $agencyId;
    }

    public function index()
    {
        $agencyId = $this->agencyId();

        return view('erp.expense-heads.index', [
            'heads' => ExpenseHead::forAgency($agencyId)->ordered()->withCount(['expenses', 'paymentVouchers'])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $agencyId = $this->agencyId();
        $data = $this->validated($request, $agencyId);
        DB::transaction(function () use ($agencyId, $data) {
            Agency::whereKey($agencyId)->lockForUpdate()->firstOrFail();
            $base = Str::slug($data['name'], '_') ?: 'expense_head';
            $base = substr($base, 0, 60);
            $code = $base;
            for ($suffix = 2; ExpenseHead::forAgency($agencyId)->where('code', $code)->exists(); $suffix++) {
                $code = $base.'_'.$suffix;
            }
            ExpenseHead::create($data + ['agency_id' => $agencyId, 'code' => $code]);
        });

        return back()->with('success', 'Expense head added.');
    }

    public function update(Request $request, ExpenseHead $expenseHead)
    {
        $agencyId = $this->owned($expenseHead);
        $data = $this->validated($request, $agencyId, $expenseHead);
        abort_if($expenseHead->is_system && ($data['is_active'] || $data['name'] !== $expenseHead->name), 422,
            'The archival Payment Voucher head must remain inactive and retain its name.');
        $expenseHead->update($data);

        return back()->with('success', 'Expense head updated.');
    }

    public function reorder(Request $request)
    {
        $agencyId = $this->agencyId();
        $data = $request->validate(['heads' => ['required', 'array', 'min:1'],
            'heads.*' => ['required', 'integer', 'distinct', Rule::exists('expense_heads', 'id')->where('agency_id', $agencyId)]]);
        DB::transaction(function () use ($agencyId, $data) {
            Agency::whereKey($agencyId)->lockForUpdate()->firstOrFail();
            foreach ($data['heads'] as $index => $id) {
                ExpenseHead::forAgency($agencyId)->whereKey($id)->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        return back()->with('success', 'Expense heads reordered.');
    }

    public function destroy(ExpenseHead $expenseHead)
    {
        $this->owned($expenseHead);
        if ($expenseHead->is_system || $expenseHead->expenses()->exists() || $expenseHead->paymentVouchers()->exists()) {
            throw ValidationException::withMessages(['head' => 'This head is retained for accounting history. Deactivate it instead.']);
        }
        $expenseHead->delete();

        return back()->with('success', 'Unused expense head deleted.');
    }

    private function validated(Request $request, int $agencyId, ?ExpenseHead $head = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('expense_heads', 'name')->where('agency_id', $agencyId)->ignore($head?->id)],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);
    }
}
