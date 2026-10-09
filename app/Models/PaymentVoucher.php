<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ERP Payment Voucher — agency money-OUT document.
 *
 * Lifecycle: draft → approved → paid, with cancel allowed from draft/approved.
 * Only drafts are editable or (soft) deletable. Money/derived columns, the
 * number columns and the approval/payment columns are NOT fillable: only
 * App\Services\PaymentVoucherService writes them (integer-cent math, locked
 * numbering, row-locked state transitions). Who may do what lives in
 * App\Policies\PaymentVoucherPolicy.
 */
class PaymentVoucher extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft'     => 'Draft',
        'approved'  => 'Approved',
        'paid'      => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    public const PAYEE_TYPES = [
        'party'        => 'Party',
        'individual'   => 'Individual',
        'organization' => 'Organization',
    ];

    public const PAYMENT_METHODS = [
        'cash'           => 'Cash',
        'cheque'         => 'Cheque',
        'bank_transfer'  => 'Bank Transfer',
        'mobile_banking' => 'Mobile Banking',
    ];

    public const ADJUST_TYPES = [
        'none'    => 'None',
        'percent' => 'Percent (%)',
        'fixed'   => 'Fixed amount',
    ];

    protected $fillable = [
        'voucher_date',
        'expense_head_id',
        'payee_type', 'payee_name', 'payee_phone', 'payee_address', 'payee_account',
        'payment_method', 'cheque_number', 'bank_name', 'reference_number',
        'description',
        'tax_type', 'tax_value', 'discount_type', 'discount_value',
        'notes',
    ];

    protected $casts = [
        'voucher_date'    => 'date',
        'payment_date'    => 'date',
        'approved_at'     => 'datetime',
        'paid_at'         => 'datetime',
        'cancelled_at'    => 'datetime',
        'subtotal'        => 'decimal:2',
        'tax_value'       => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount_value'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function expenseHeadLabel(): string
    {
        return ($this->expenseHead?->agency_id === $this->agency_id ? $this->expenseHead->name : null) ?? 'Payment Voucher (legacy)';
    }

    public function items(): HasMany
    {
        return $this->hasMany(PaymentVoucherItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The Expense booked when this voucher was paid (null until then). */
    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    // ── State helpers (state only — permissions live in the policy) ─────────

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function canApprove(): bool
    {
        return $this->status === 'draft' && (float) $this->total_amount > 0;
    }

    public function canPay(): bool
    {
        return $this->status === 'approved';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'approved'], true);
    }

    public function isDeletable(): bool
    {
        return $this->status === 'draft';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function payeeTypeLabel(): string
    {
        return self::PAYEE_TYPES[$this->payee_type] ?? ucfirst((string) $this->payee_type);
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst(str_replace('_', ' ', (string) $this->payment_method));
    }

    /** "BDT 1,250.00" — vouchers are BDT-only (agency money-out ledger currency). */
    public function money($value): string
    {
        return 'BDT ' . number_format((float) $value, 2);
    }
}
