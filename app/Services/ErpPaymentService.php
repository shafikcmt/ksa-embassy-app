<?php

namespace App\Services;

use App\Models\DoubleMofa;
use App\Models\PaymentReceipt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ErpPaymentService — the ONLY code that writes money on E2 records.
 *
 * Design contract (money integrity):
 *  - payment_receipts is the append-only source of truth. Rows are never
 *    updated or deleted; a mistake is corrected by writing a `reversal` row.
 *  - A payable's `paid_amount` is a denormalized cache. It is NEVER incremented
 *    in place — it is recomputed as the signed SUM of the ledger every time the
 *    ledger changes (payment = +amount, reversal = -amount).
 *  - Every mutation runs in a DB transaction with a `lockForUpdate` on the
 *    payable row, so two concurrent "Receive Payment" clicks serialize instead
 *    of both reading a stale cache and over-crediting.
 *  - `paid_amount` is not in either model's $fillable; it is set here by direct
 *    property assignment (mass-assignment can never touch it).
 */
class ErpPaymentService
{
    /**
     * Record a payment against a Delivery or DoubleMofa. Returns the ledger row.
     *
     * @throws RuntimeException on non-positive amount or overpayment.
     */
    public function receivePayment(Model $payable, float $amount, ?string $note, int $userId): PaymentReceipt
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($payable, $amount, $note, $userId) {
            // Lock the money row for the life of the transaction.
            $locked = $payable->newQuery()->lockForUpdate()->findOrFail($payable->getKey());

            $base    = round((float) $this->paymentBase($locked), 2);
            $current = round((float) $locked->paid_amount, 2);

            // Overpayment guard — cache is already correct because we hold the lock.
            if ($current + $amount > $base + 0.001) {
                throw new RuntimeException(sprintf(
                    'Payment of %.2f exceeds the outstanding due of %.2f.',
                    $amount,
                    max($base - $current, 0)
                ));
            }

            $receipt = PaymentReceipt::create([
                'agency_id'    => $locked->agency_id,
                'payable_type' => $locked->getMorphClass(),
                'payable_id'   => $locked->getKey(),
                'type'         => PaymentReceipt::TYPE_PAYMENT,
                'amount'       => $amount,
                'note'         => $note !== null && trim($note) !== '' ? trim($note) : null,
                'received_by'  => $userId,
                'received_at'  => now(),
            ]);

            $this->recompute($locked);

            return $receipt;
        });
    }

    /**
     * Reverse a prior payment. Writes a `reversal` row pointing at the original
     * payment; `reverses_id` is UNIQUE so a payment can be reversed at most once.
     * A note (reason) is required.
     *
     * @throws RuntimeException if the target is not a payment, is already
     *                          reversed, or no note is given.
     */
    public function reversePayment(PaymentReceipt $receipt, string $note, int $userId): PaymentReceipt
    {
        $note = trim($note);
        if ($note === '') {
            throw new RuntimeException('A reason (note) is required to reverse a payment.');
        }
        if ($receipt->type !== PaymentReceipt::TYPE_PAYMENT) {
            throw new RuntimeException('Only a payment can be reversed.');
        }

        return DB::transaction(function () use ($receipt, $note, $userId) {
            // Re-read the original under lock so a concurrent reversal serializes.
            $original = PaymentReceipt::whereKey($receipt->getKey())->lockForUpdate()->firstOrFail();

            if ($original->type !== PaymentReceipt::TYPE_PAYMENT) {
                throw new RuntimeException('Only a payment can be reversed.');
            }

            $alreadyReversed = PaymentReceipt::where('reverses_id', $original->id)
                ->lockForUpdate()->exists();
            if ($alreadyReversed) {
                throw new RuntimeException('This payment has already been reversed.');
            }

            // Resolve + lock the parent money record (payable_type is the FQCN).
            $payableClass = $original->payable_type;
            $payable = $payableClass::whereKey($original->payable_id)->lockForUpdate()->firstOrFail();

            $reversal = PaymentReceipt::create([
                'agency_id'    => $original->agency_id,
                'payable_type' => $original->payable_type,
                'payable_id'   => $original->payable_id,
                'type'         => PaymentReceipt::TYPE_REVERSAL,
                'amount'       => $original->amount, // stored positive; sign applied on sum
                'note'         => $note,
                'reverses_id'  => $original->id,
                'received_by'  => $userId,
                'received_at'  => now(),
            ]);

            $this->recompute($payable);

            return $reversal;
        });
    }

    /**
     * Recompute paid_amount from the ledger and sync payment-derived status.
     * MUST be called inside a transaction with the payable already locked.
     * Safe to call after a billing_amount/total_amount edit to re-derive status.
     */
    public function recompute(Model $payable): void
    {
        $paid = PaymentReceipt::where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->getKey())
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'reversal' THEN -amount ELSE amount END), 0) AS net")
            ->value('net');

        $paid = round((float) $paid, 2);
        if ($paid < 0) {
            $paid = 0.0; // defensive: reversals should never outstrip payments
        }

        // Direct set — deliberately bypasses $fillable.
        $payable->paid_amount = $paid;

        // Delivery.status is a fulfillment state (pending/ready/delivered) set by
        // hand — it is NOT payment-driven, so we never touch it here. Only the
        // DoubleMofa payment status is derived from the ledger.
        if ($payable instanceof DoubleMofa) {
            $payable->status = $this->doubleMofaStatus($paid, (float) $payable->billing_amount);
        }

        $payable->save();
    }

    /** The billable base for overpayment checks. */
    private function paymentBase(Model $payable): float
    {
        if ($payable instanceof DoubleMofa) {
            return (float) $payable->billing_amount;
        }

        return (float) $payable->total_amount; // Delivery
    }

    private function doubleMofaStatus(float $paid, float $billing): string
    {
        if ($paid <= 0.0) {
            return 'unpaid';
        }
        if ($paid + 0.001 < $billing) {
            return 'partial';
        }

        return 'paid';
    }
}
