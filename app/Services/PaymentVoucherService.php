<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\PaymentVoucher;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The ONLY writer of payment-voucher money, numbering and lifecycle columns.
 *
 * Money: parsed from decimal strings into integer cents (InvoiceService::toCents
 * — same parser, same rounding as invoices), integer arithmetic only, percent as
 * basis points rounded half-up once per derived amount.
 *
 *   line amount = quantity × unit_price
 *   subtotal    = Σ line amounts
 *   tax         = none | subtotal × tax% | fixed
 *   discount    = none | subtotal × discount% | fixed
 *   total       = subtotal + tax − discount   (must stay ≥ 0)
 *
 * Every state transition re-reads the row under lockForUpdate so two users
 * can't approve/pay/cancel the same voucher concurrently. All changes are
 * written to AuditLog (user + timestamp + old/new values).
 *
 * Paying a voucher also books it as an Expense (category payment_voucher) in
 * the SAME transaction — so it flows into Expenses, Reports and Profit/Loss.
 * expenses.payment_voucher_id is UNIQUE: one voucher can never become two
 * expenses. Paid vouchers can't be cancelled/deleted, so the pair never splits.
 */
class PaymentVoucherService
{
    // ── Math ─────────────────────────────────────────────────────────────────

    /**
     * @param  array<int, array{quantity:int|string, unit_price:string|int|float}>  $items
     * @return array{lines:int[], subtotal:int, tax:int, discount:int, total:int}
     */
    public static function calculateTotals(array $items, string $taxType, $taxValue, string $discountType, $discountValue): array
    {
        $lines = [];
        $subtotal = 0;
        foreach (array_values($items) as $item) {
            $line = (int) $item['quantity'] * InvoiceService::toCents($item['unit_price']);
            $lines[] = $line;
            $subtotal += $line;
        }

        $adjust = fn (string $type, $value) => match ($type) {
            'percent' => intdiv($subtotal * InvoiceService::toCents($value) + 5000, 10000),
            'fixed'   => InvoiceService::toCents($value),
            default   => 0,
        };

        $tax = $adjust($taxType, $taxValue);
        $discount = $adjust($discountType, $discountValue);

        return [
            'lines'    => $lines,
            'subtotal' => $subtotal,
            'tax'      => $tax,
            'discount' => $discount,
            'total'    => $subtotal + $tax - $discount,
        ];
    }

    // ── Numbering ────────────────────────────────────────────────────────────

    public static function formatNumber(int $agencyId, int $year, int $seq): string
    {
        return 'VCH-' . $agencyId . '-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /** Preview only (create form) — the real number is allocated under lock on save. */
    public function peekNextNumber(int $agencyId, int $year): string
    {
        return self::formatNumber($agencyId, $year, $this->maxSeq($agencyId, $year) + 1);
    }

    /** Must run inside a transaction: the agency-row lock serialises concurrent creates. */
    public function generateVoucherNumber(int $agencyId, int $year): array
    {
        Agency::whereKey($agencyId)->lockForUpdate()->first();
        $seq = $this->maxSeq($agencyId, $year) + 1;

        return [$year, $seq, self::formatNumber($agencyId, $year, $seq)];
    }

    /** withTrashed: a soft-deleted draft keeps its number — never reissued. */
    private function maxSeq(int $agencyId, int $year): int
    {
        return (int) PaymentVoucher::withTrashed()
            ->where('agency_id', $agencyId)->where('number_year', $year)->max('number_seq');
    }

    // ── Writes ───────────────────────────────────────────────────────────────

    /**
     * Create (when $voucher is null) or update a DRAFT voucher + replace its lines.
     *
     * @param  array  $data   validated header fields (PaymentVoucher::$fillable keys)
     * @param  array  $items  validated lines: description, quantity, unit_price, remarks
     */
    public function save(?PaymentVoucher $voucher, int $agencyId, array $data, array $items, User $user): PaymentVoucher
    {
        foreach (['tax', 'discount'] as $k) {
            if ($data[$k . '_type'] === 'none') {
                $data[$k . '_value'] = null;
            }
        }
        // Drop details that don't belong to the chosen method (hidden form fields still post).
        if (($data['payment_method'] ?? null) !== 'cheque') {
            $data['cheque_number'] = null;
        }
        if (! in_array($data['payment_method'] ?? null, ['cheque', 'bank_transfer'], true)) {
            $data['bank_name'] = null;
        }

        $calc = self::calculateTotals(
            $items,
            $data['tax_type'], $data['tax_value'] ?? 0,
            $data['discount_type'], $data['discount_value'] ?? 0,
        );

        if ($calc['total'] < 0) {
            throw ValidationException::withMessages(['discount_value' => 'Discount cannot be larger than subtotal + tax.']);
        }
        if ($calc['subtotal'] > InvoiceService::MAX_CENTS || $calc['total'] > InvoiceService::MAX_CENTS) {
            throw ValidationException::withMessages(['items' => 'Voucher total is too large.']);
        }

        return DB::transaction(function () use ($voucher, $agencyId, $data, $items, $calc, $user) {
            $isNew = $voucher === null;
            $old = [];

            if ($isNew) {
                $voucher = new PaymentVoucher();
                $voucher->agency_id = $agencyId;
                [$voucher->number_year, $voucher->number_seq, $voucher->voucher_number] =
                    $this->generateVoucherNumber($agencyId, (int) date('Y', strtotime($data['voucher_date'])));
                $voucher->status = 'draft';
                $voucher->created_by = $user->id;
            } else {
                $voucher = PaymentVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
                if (! $voucher->isEditable()) {
                    throw ValidationException::withMessages(['status' => 'This voucher is ' . $voucher->statusLabel() . ' and can no longer be edited.']);
                }
                $old = $this->snapshot($voucher->load('items'));
            }

            $voucher->fill($data);
            $voucher->subtotal        = InvoiceService::fromCents($calc['subtotal']);
            $voucher->tax_amount      = InvoiceService::fromCents($calc['tax']);
            $voucher->discount_amount = InvoiceService::fromCents($calc['discount']);
            $voucher->total_amount    = InvoiceService::fromCents($calc['total']);
            $voucher->updated_by      = $user->id;
            $voucher->save();

            $voucher->items()->delete();
            foreach (array_values($items) as $i => $item) {
                $line = $voucher->items()->make([
                    'description' => $item['description'],
                    'quantity'    => (int) $item['quantity'],
                    'unit_price'  => InvoiceService::fromCents(InvoiceService::toCents($item['unit_price'])),
                    'remarks'     => $item['remarks'] ?? null,
                    'sort_order'  => $i,
                ]);
                $line->amount = InvoiceService::fromCents($calc['lines'][$i]);
                $line->save();
            }

            AuditLog::record($isNew ? 'voucher_created' : 'voucher_updated', $voucher, $old, $this->snapshot($voucher->load('items')));

            return $voucher;
        });
    }

    public function approve(PaymentVoucher $voucher, User $user): PaymentVoucher
    {
        return $this->transition($voucher, fn (PaymentVoucher $v) => $v->canApprove(),
            'Only a draft voucher with a positive total can be approved.',
            function (PaymentVoucher $v) use ($user) {
                $v->status = 'approved';
                $v->approved_by = $user->id;
                $v->approved_at = now();
            }, 'voucher_approved', $user);
    }

    /** @param array{payment_method:string, payment_date:string, cheque_number?:?string, bank_name?:?string, reference_number?:?string} $details */
    public function pay(PaymentVoucher $voucher, User $user, array $details): PaymentVoucher
    {
        return DB::transaction(function () use ($voucher, $user, $details) {
            $voucher = $this->markPaid($voucher, $user, $details);
            $this->bookExpense($voucher, $user);

            return $voucher;
        });
    }

    /** Voucher paid → matching money-OUT Expense (idempotent per voucher). */
    private function bookExpense(PaymentVoucher $voucher, User $user): Expense
    {
        $existing = Expense::where('payment_voucher_id', $voucher->id)->first();
        if ($existing) {
            return $existing;
        }

        $expense = new Expense([
            'agency_id'    => $voucher->agency_id,
            'expense_date' => $voucher->payment_date->format('Y-m-d'),
            'category'     => 'payment_voucher',
            'amount'       => (string) $voucher->total_amount,   // decimal string — no float
            'paid_via'     => self::EXPENSE_PAID_VIA[$voucher->payment_method] ?? null,
            'note'         => mb_substr($voucher->voucher_number . ' · ' . $voucher->payee_name, 0, 255),
            'created_by'   => $user->id,
            'updated_by'   => $user->id,
        ]);
        $expense->payment_voucher_id = $voucher->id;
        $expense->save();

        AuditLog::record('voucher_expense_created', $voucher, [], [
            'expense_id' => $expense->id,
            'amount'     => (string) $expense->amount,
        ]);

        return $expense;
    }

    /** Voucher payment method → Expense::PAID_VIA key (mobile banking has no single match). */
    private const EXPENSE_PAID_VIA = [
        'cash'          => 'cash',
        'cheque'        => 'bank',
        'bank_transfer' => 'bank',
    ];

    private function markPaid(PaymentVoucher $voucher, User $user, array $details): PaymentVoucher
    {
        return $this->transition($voucher, fn (PaymentVoucher $v) => $v->canPay(),
            'Only an approved voucher can be marked as paid.',
            function (PaymentVoucher $v) use ($user, $details) {
                $v->status           = 'paid';
                $v->payment_method   = $details['payment_method'];
                $v->payment_date     = $details['payment_date'];
                $v->cheque_number    = $details['cheque_number'] ?? null;
                $v->bank_name        = $details['bank_name'] ?? null;
                $v->reference_number = $details['reference_number'] ?? $v->reference_number;
                $v->paid_by          = $user->id;
                $v->paid_at          = now();
            }, 'voucher_paid', $user);
    }

    /** Status change (record kept for the audit trail) — not a delete. */
    public function cancel(PaymentVoucher $voucher, User $user): PaymentVoucher
    {
        return $this->transition($voucher, fn (PaymentVoucher $v) => $v->canCancel(),
            'Only draft or approved vouchers can be cancelled.',
            function (PaymentVoucher $v) {
                $v->status = 'cancelled';
                $v->cancelled_at = now();
            }, 'voucher_cancelled', $user);
    }

    /** Soft delete — drafts only. */
    public function delete(PaymentVoucher $voucher, User $user): void
    {
        DB::transaction(function () use ($voucher, $user) {
            $voucher = PaymentVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            if (! $voucher->isDeletable()) {
                throw ValidationException::withMessages(['status' => 'Only draft vouchers can be deleted. Cancel it instead.']);
            }
            $voucher->updated_by = $user->id;
            $voucher->save();
            $voucher->delete();

            AuditLog::record('voucher_deleted', $voucher, $this->snapshot($voucher->load('items')), []);
        });
    }

    /** Undo a soft delete (drafts only ever get deleted). */
    public function restore(PaymentVoucher $voucher, User $user): PaymentVoucher
    {
        $voucher->restore();
        AuditLog::record('voucher_restored', $voucher, [], ['status' => $voucher->status]);

        return $voucher;
    }

    private function transition(PaymentVoucher $voucher, callable $allowed, string $error, callable $apply, string $action, User $user): PaymentVoucher
    {
        return DB::transaction(function () use ($voucher, $allowed, $error, $apply, $action, $user) {
            $voucher = PaymentVoucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            if (! $allowed($voucher)) {
                throw ValidationException::withMessages(['status' => $error]);
            }

            $old = ['status' => $voucher->status];
            $apply($voucher);
            $voucher->updated_by = $user->id;
            $voucher->save();

            AuditLog::record($action, $voucher, $old, array_filter([
                'status'           => $voucher->status,
                'payment_method'   => $action === 'voucher_paid' ? $voucher->payment_method : null,
                'payment_date'     => $action === 'voucher_paid' ? optional($voucher->payment_date)->format('Y-m-d') : null,
                'reference_number' => $action === 'voucher_paid' ? $voucher->reference_number : null,
                'total_amount'     => (string) $voucher->total_amount,
            ]));

            return $voucher;
        });
    }

    private function snapshot(PaymentVoucher $voucher): array
    {
        return [
            'voucher_number'  => $voucher->voucher_number,
            'status'          => $voucher->status,
            'voucher_date'    => optional($voucher->voucher_date)->format('Y-m-d'),
            'payee_name'      => $voucher->payee_name,
            'payment_method'  => $voucher->payment_method,
            'subtotal'        => (string) $voucher->subtotal,
            'tax_amount'      => (string) $voucher->tax_amount,
            'discount_amount' => (string) $voucher->discount_amount,
            'total_amount'    => (string) $voucher->total_amount,
            'items'           => $voucher->items->map(fn ($i) => [
                'description' => $i->description,
                'quantity'    => $i->quantity,
                'unit_price'  => (string) $i->unit_price,
                'amount'      => (string) $i->amount,
            ])->all(),
        ];
    }
}
