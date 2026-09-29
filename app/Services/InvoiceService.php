<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The ONLY writer of invoice money + numbering + lifecycle columns.
 *
 * Precision: every amount is converted to integer cents by parsing the decimal
 * STRING (never via float), all arithmetic is integer, and results are written
 * back as "123.45" strings. Percentages are held as basis points (15.25% → 1525)
 * and rounded half-up once per derived amount, so browser preview and server
 * totals agree to the cent.
 *
 * Formula (both adjustments are based on the subtotal):
 *   line amount = quantity × unit_price
 *   subtotal    = Σ line amounts
 *   tax         = none | subtotal × tax% | fixed amount
 *   discount    = none | subtotal × discount% | fixed amount
 *   total       = subtotal + tax − discount   (must not go below zero)
 */
class InvoiceService
{
    /** decimal(14,2) ceiling, in cents. */
    public const MAX_CENTS = 99_999_999_999_999;

    // ── Money helpers ────────────────────────────────────────────────────────

    /** "1,250.5" / "1250.50" / 1250 → 125050. Accepts only non-negative decimals with ≤ 2 places. */
    public static function toCents(string|int|float|null $value): int
    {
        $s = str_replace([',', ' '], '', trim((string) $value));
        if ($s === '') {
            return 0;
        }
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $s, $m)) {
            throw new \InvalidArgumentException("Invalid money value [{$value}].");
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign . intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Round-half-up of $cents × basisPoints / 10000, all integer. */
    private static function percentOf(int $cents, int $basisPoints): int
    {
        return intdiv($cents * $basisPoints + 5000, 10000);
    }

    /**
     * Compute every derived amount from raw inputs. Returns cents.
     *
     * @param  array<int, array{quantity:int|string, unit_price:string|int|float}>  $items
     * @return array{lines:int[], subtotal:int, tax:int, discount:int, total:int}
     */
    public static function calculate(array $items, string $taxType, $taxValue, string $discountType, $discountValue): array
    {
        $lines = [];
        $subtotal = 0;
        foreach (array_values($items) as $item) {
            $line = self::toCents($item['processing_fee'] ?? 0) + self::toCents($item['mofa_fee'] ?? 0);
            $lines[] = $line;
            $subtotal += $line;
        }

        $tax = match ($taxType) {
            'percent' => self::percentOf($subtotal, self::toCents($taxValue)),
            'amount'  => self::toCents($taxValue),
            default   => 0,
        };

        $discount = match ($discountType) {
            'percent' => self::percentOf($subtotal, self::toCents($discountValue)),
            'amount'  => self::toCents($discountValue),
            default   => 0,
        };

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
        return 'INV-' . $agencyId . '-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /** Preview only (create form header) — the real number is allocated under lock in save(). */
    public function peekNextNumber(int $agencyId, int $year): string
    {
        $seq = (int) Invoice::withTrashed()->where('agency_id', $agencyId)->where('number_year', $year)->max('number_seq') + 1;

        return self::formatNumber($agencyId, $year, $seq);
    }

    /** Must run inside a transaction: locks the agency row so concurrent creates serialise. */
    private function allocateNumber(int $agencyId, int $year): array
    {
        Agency::whereKey($agencyId)->lockForUpdate()->first();

        // withTrashed: a soft-deleted draft still "owns" its number — never reissued.
        $seq = (int) Invoice::withTrashed()->where('agency_id', $agencyId)->where('number_year', $year)->max('number_seq') + 1;

        return [$year, $seq, self::formatNumber($agencyId, $year, $seq)];
    }

    // ── Writes ───────────────────────────────────────────────────────────────

    /**
     * Create (when $invoice is null) or update an invoice + replace its lines.
     *
     * @param  array  $data   validated header fields (Invoice::$fillable keys)
     * @param  array  $items  validated lines: hr_profile_id, description, quantity, unit_price
     */
    public function save(?Invoice $invoice, int $agencyId, array $data, array $items, User $user): Invoice
    {
        $calc = self::calculate(
            $items,
            $data['tax_type'], $data['tax_value'] ?? 0,
            $data['discount_type'], $data['discount_value'] ?? 0,
        );

        if ($calc['total'] < 0) {
            throw ValidationException::withMessages(['discount_value' => 'Discount cannot be larger than subtotal + tax.']);
        }
        if ($calc['subtotal'] > self::MAX_CENTS || $calc['total'] > self::MAX_CENTS) {
            throw ValidationException::withMessages(['items' => 'Invoice total is too large.']);
        }

        // Adjustment "value" is meaningless when the type is none — store 0.
        if ($data['tax_type'] === 'none') {
            $data['tax_value'] = '0';
        }
        if ($data['discount_type'] === 'none') {
            $data['discount_value'] = '0';
        }

        return DB::transaction(function () use ($invoice, $agencyId, $data, $items, $calc, $user) {
            $isNew = $invoice === null;
            $old = [];

            if ($isNew) {
                $invoice = new Invoice();
                $invoice->agency_id = $agencyId;
                [$invoice->number_year, $invoice->number_seq, $invoice->invoice_number] =
                    $this->allocateNumber($agencyId, (int) date('Y', strtotime($data['invoice_date'])));
                $invoice->created_by = $user->id;
            } else {
                // Re-read under lock so a concurrent mark-paid can't be overwritten.
                $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
                if ($invoice->isLocked()) {
                    throw ValidationException::withMessages(['status' => 'This invoice is ' . $invoice->statusLabel() . ' and can no longer be edited.']);
                }
                $old = $this->snapshot($invoice->load('items'));
            }

            $invoice->fill($data);
            $invoice->subtotal        = self::fromCents($calc['subtotal']);
            $invoice->tax_amount      = self::fromCents($calc['tax']);
            $invoice->discount_amount = self::fromCents($calc['discount']);
            $invoice->total_amount    = self::fromCents($calc['total']);
            $invoice->updated_by      = $user->id;
            $invoice->save();

            // Replace lines wholesale (simple + exact; line ids are not referenced elsewhere).
            $invoice->items()->delete();
            foreach (array_values($items) as $i => $item) {
                $paid = isset($item['paid_amount']) && trim((string) $item['paid_amount']) !== ''
                    ? self::fromCents(self::toCents($item['paid_amount'])) : null;
                $line = $invoice->items()->make([
                    'hr_profile_id'  => $item['hr_profile_id'] ?? null,
                    'processing_fee' => self::fromCents(self::toCents($item['processing_fee'] ?? 0)),
                    'mofa_fee'       => self::fromCents(self::toCents($item['mofa_fee'] ?? 0)),
                    'paid_amount'    => $paid,
                    'remarks'        => isset($item['remarks']) && trim((string) $item['remarks']) !== ''
                                        ? trim((string) $item['remarks']) : null,
                ]);
                // booted saving hook computes total_amount and due_amount.
                $line->save();
            }

            AuditLog::record(
                $isNew ? 'invoice_created' : 'invoice_updated',
                $invoice,
                $old,
                $this->snapshot($invoice->load('items')),
            );

            return $invoice;
        });
    }

    public function markPaid(Invoice $invoice, string $method, string $paidAt, ?string $reference, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $method, $paidAt, $reference, $user) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if (! $invoice->canBePaid()) {
                throw ValidationException::withMessages(['payment_method' => 'Only a draft/pending invoice with a positive total can be marked as paid.']);
            }

            $old = ['status' => $invoice->status];
            $invoice->status            = 'paid';
            $invoice->payment_method    = $method;
            $invoice->paid_at           = $paidAt;
            $invoice->payment_reference = $reference;
            $invoice->paid_by           = $user->id;
            $invoice->updated_by        = $user->id;
            $invoice->save();

            AuditLog::record('invoice_paid', $invoice, $old, [
                'status'            => 'paid',
                'payment_method'    => $method,
                'paid_at'           => $paidAt,
                'payment_reference' => $reference,
                'total_amount'      => (string) $invoice->total_amount,
            ]);

            return $invoice;
        });
    }

    public function cancel(Invoice $invoice, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $user) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if (! $invoice->isEditable()) {
                throw ValidationException::withMessages(['status' => 'Only draft/pending invoices can be cancelled.']);
            }

            $old = ['status' => $invoice->status];
            $invoice->status       = 'cancelled';
            $invoice->cancelled_at = now();
            $invoice->updated_by   = $user->id;
            $invoice->save();

            AuditLog::record('invoice_cancelled', $invoice, $old, ['status' => 'cancelled']);

            return $invoice;
        });
    }

    /** Soft delete — drafts only. */
    public function delete(Invoice $invoice, User $user): void
    {
        DB::transaction(function () use ($invoice, $user) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if (! $invoice->isDeletable()) {
                throw ValidationException::withMessages(['status' => 'Only draft invoices can be deleted. Cancel it instead.']);
            }

            $invoice->updated_by = $user->id;
            $invoice->save();
            $invoice->delete();

            AuditLog::record('invoice_deleted', $invoice, $this->snapshot($invoice->load('items')), []);
        });
    }

    private function snapshot(Invoice $invoice): array
    {
        return [
            'invoice_number'  => $invoice->invoice_number,
            'status'          => $invoice->status,
            'invoice_date'    => optional($invoice->invoice_date)->format('Y-m-d'),
            'due_date'        => optional($invoice->due_date)->format('Y-m-d'),
            'bill_to_name'    => $invoice->bill_to_name,
            'currency'        => $invoice->currency,
            'subtotal'        => (string) $invoice->subtotal,
            'tax_type'        => $invoice->tax_type,
            'tax_value'       => (string) $invoice->tax_value,
            'tax_amount'      => (string) $invoice->tax_amount,
            'discount_type'   => $invoice->discount_type,
            'discount_value'  => (string) $invoice->discount_value,
            'discount_amount' => (string) $invoice->discount_amount,
            'total_amount'    => (string) $invoice->total_amount,
            'items'           => $invoice->items->map(fn ($i) => [
                'hr_profile_id'  => $i->hr_profile_id,
                'processing_fee' => (string) $i->processing_fee,
                'mofa_fee'       => (string) $i->mofa_fee,
                'total_amount'   => (string) $i->total_amount,
                'paid_amount'    => (string) $i->paid_amount,
                'due_amount'     => (string) $i->due_amount,
            ])->all(),
        ];
    }
}
