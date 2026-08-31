<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\DoubleMofa;
use App\Models\ErpSetting;
use App\Models\PaymentReceipt;
use App\Services\ErpPaymentService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * ERP Double MOFA (E2) — passports billed for a repeated MOFA. Money-bearing.
 *
 * billing_amount is SNAPSHOTTED from erp_settings.double_mofa_rate at creation
 * (pre-filled in the form, editable per row) so later rate changes never rewrite
 * historical bills. Status (unpaid/partial/paid) is payment-derived and only
 * ErpPaymentService writes paid_amount + status. All actions are agency-scoped.
 */
class DoubleMofaController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $entries = DoubleMofa::forAgency($agencyId)
            ->with(['createdBy:id,name', 'receipts.receivedBy:id,name'])
            ->orderByDesc('mofa_date')->orderByDesc('id')
            ->get();

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
        return $request->validate([
            'mofa_date'      => ['required', 'date'],
            'full_name'      => ['required', 'string', 'max:255'],
            'passport_no'    => ['required', 'string', 'max:100'],
            'visa_serial'    => ['nullable', 'string', 'max:100'],
            'reference'      => ['nullable', 'string', 'max:255'],
            'billing_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ]);
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
