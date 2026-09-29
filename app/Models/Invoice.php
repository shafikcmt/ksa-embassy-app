<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ERP Invoice — agency-scoped, multi-line.
 *
 * Money/derived columns (subtotal, tax_amount, discount_amount, total_amount),
 * the number columns and the payment/lock columns are deliberately NOT fillable:
 * only App\Services\InvoiceService writes them (integer-cent math, locked
 * numbering). Lifecycle: draft/pending are editable; paid/cancelled are locked.
 */
class Invoice extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft'     => 'Draft',
        'pending'   => 'Pending',
        'paid'      => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    /** Statuses a user may pick on the create/edit form (paid/cancelled come from actions). */
    public const EDITABLE_STATUSES = ['draft', 'pending'];

    public const PAYMENT_METHODS = [
        'cash'          => 'Cash',
        'cheque'        => 'Cheque',
        'bank_transfer' => 'Bank Transfer',
        'bkash'         => 'bKash',
        'nagad'         => 'Nagad',
    ];

    public const ADJUST_TYPES = [
        'none'    => 'None',
        'percent' => 'Percent (%)',
        'amount'  => 'Fixed amount',
    ];

    public const CURRENCIES = [
        'BDT' => '৳',
        'SAR' => 'SAR',
        'USD' => '$',
    ];

    protected $fillable = [
        'invoice_date', 'due_date',
        'agent_id', 'bill_to_name', 'bill_to_phone', 'bill_to_address', 'bill_to_email',
        'status', 'currency',
        'tax_type', 'tax_value', 'discount_type', 'discount_value',
        'notes',
    ];

    protected $casts = [
        'invoice_date'    => 'date',
        'due_date'        => 'date',
        'paid_at'         => 'date',
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

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    // ── State helpers ────────────────────────────────────────────────────────

    /** Draft/pending only — paid and cancelled invoices are locked. */
    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    public function isLocked(): bool
    {
        return ! $this->isEditable();
    }

    /** Only drafts can be (soft) deleted; anything issued must be cancelled instead. */
    public function isDeletable(): bool
    {
        return $this->status === 'draft';
    }

    public function canBePaid(): bool
    {
        return $this->isEditable() && (float) $this->total_amount > 0;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date->isBefore(today());
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function paymentMethodLabel(): ?string
    {
        return $this->payment_method
            ? (self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method))
            : null;
    }

    public function currencySymbol(): string
    {
        return self::CURRENCIES[$this->currency] ?? $this->currency;
    }

    /** Formatted money in this invoice's currency, e.g. "৳ 1,250.00". */
    public function money($value): string
    {
        return $this->currencySymbol() . ' ' . number_format((float) $value, 2);
    }

    /** Bill-to display name: free text first, then the linked agent. */
    public function billToLabel(): string
    {
        return $this->bill_to_name ?: ($this->agent?->name ?? '—');
    }
}
