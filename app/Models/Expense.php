<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Expense (E3). Agency money-OUT log — simple, no ledger/cache.
 *
 * expense_head_id identifies the agency-managed head. category retains its
 * stable code (or historical string); CATEGORIES is a legacy label/CSV alias map.
 * paid_via uses PAID_VIA. Default heads exclude Agent Khata payouts.
 *
 * PaymentVoucherService books the selected head when a voucher is paid.
 * payment_voucher_id (UNIQUE → one expense per voucher) makes these rows
 * read-only on the Expenses screen. SYSTEM_CATEGORIES labels legacy rows.
 */
class Expense extends Model
{
    protected $fillable = [
        'agency_id', 'expense_date', 'category', 'expense_head_id', 'amount',
        'paid_via', 'note', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public const CATEGORIES = [
        'office_rent'     => 'Office Rent',
        'salary'          => 'Salary',
        'utilities'       => 'Utilities',
        'government_fees' => 'Government Fees',
        'travel'          => 'Travel / Transport',
        'marketing'       => 'Marketing',
        'office_supplies' => 'Office Supplies',
        'bank_charge'     => 'Bank Charge',
        'enjaz_dollar'    => 'Enjaz Dollar',
        'air_ticket'      => 'Air Ticket',
        'manpower'        => 'Manpower',
        'other'           => 'Other',
    ];

    /** Code-only categories — deliberately NOT in CATEGORIES (not selectable/importable). */
    public const SYSTEM_CATEGORIES = [
        'payment_voucher' => 'Payment Voucher',
    ];

    public const PAID_VIA = [
        'cash'  => 'Cash',
        'bank'  => 'Bank',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'card'  => 'Card',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class)->withTrashed();
    }

    /** Auto-created from a paid Payment Voucher → read-only on the Expenses screen. */
    public function isSystemGenerated(): bool
    {
        return $this->payment_voucher_id !== null;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function categoryLabel(): string
    {
        return ($this->expenseHead?->agency_id === $this->agency_id ? $this->expenseHead->name : null) ?? self::CATEGORIES[$this->category]
            ?? self::SYSTEM_CATEGORIES[$this->category]
            ?? ucfirst((string) $this->category);
    }

    public function paidViaLabel(): ?string
    {
        return $this->paid_via ? (self::PAID_VIA[$this->paid_via] ?? ucfirst((string) $this->paid_via)) : null;
    }
}
