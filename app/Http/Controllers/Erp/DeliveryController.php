<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\PaymentReceipt;
use App\Services\ErpPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * ERP Delivery (E2) — file delivery + payment collection. Money-bearing.
 *
 * All money mutations are delegated to ErpPaymentService (locked, ledger-based).
 * This controller only ever writes total_amount/status/meta via $fillable;
 * paid_amount is never mass-assigned. Everything is scoped to the caller's
 * agency_id, and every row action re-checks ownership (abort 403 otherwise).
 */
class DeliveryController extends Controller
{
    public function index()
    {
        $agencyId = auth()->user()->agency_id;

        $deliveries = Delivery::forAgency($agencyId)
            ->with(['createdBy:id,name', 'receipts.receivedBy:id,name'])
            ->orderByDesc('delivery_date')->orderByDesc('id')
            ->get();

        $totalBilled    = (float) $deliveries->sum(fn ($d) => (float) $d->total_amount);
        $totalCollected = (float) $deliveries->sum(fn ($d) => (float) $d->paid_amount);

        return view('erp.delivery.index', [
            'deliveries'     => $deliveries,
            'statuses'       => Delivery::STATUSES,
            'totalBilled'    => $totalBilled,
            'totalCollected' => $totalCollected,
            'totalDue'       => $totalBilled - $totalCollected,
        ]);
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

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payments->receivePayment($delivery, (float) $validated['amount'], $validated['note'] ?? null, auth()->id());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('erp.delivery')->with('success', 'Payment recorded.');
    }

    public function reverse(Request $request, PaymentReceipt $receipt, ErpPaymentService $payments)
    {
        // Must belong to this agency AND to a Delivery (not a Double MOFA row).
        abort_unless($receipt->agency_id === auth()->user()->agency_id, 403);
        abort_unless($receipt->payable_type === Delivery::class, 404);

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
        return $request->validate([
            'delivery_date' => ['required', 'date'],
            'full_name'     => ['required', 'string', 'max:255'],
            'passport_no'   => ['required', 'string', 'max:100'],
            'visa_serial'   => ['nullable', 'string', 'max:100'],
            'reference'     => ['nullable', 'string', 'max:255'],
            'total_amount'  => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'status'        => ['required', Rule::in(array_keys(Delivery::STATUSES))],
        ]);
    }

    private function authorizeAgency(Delivery $delivery): void
    {
        abort_unless($delivery->agency_id === auth()->user()->agency_id, 403);
    }
}
