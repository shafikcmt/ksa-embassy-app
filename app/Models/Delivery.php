<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * ERP Delivery (E2). Money-bearing record.
 *
 * paid_amount is intentionally EXCLUDED from $fillable — it must never be mass
 * assigned. Only ErpPaymentService writes it (direct property set inside a
 * locked transaction, recomputed from the payment_receipts ledger).
 */
class Delivery extends Model
{
    protected $fillable = [
        'agency_id', 'delivery_date', 'full_name', 'passport_no',
        'visa_serial', 'reference', 'total_amount', 'status', 'payment_method',
        'created_by', 'updated_by',
        // NOTE: paid_amount is deliberately NOT fillable.
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'total_amount'  => 'decimal:2',
        'paid_amount'   => 'decimal:2',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'ready'     => 'Ready',
        'delivered' => 'Delivered',
    ];

    /** How the delivery fee was collected (separate from the workflow status). */
    public const PAYMENT_METHODS = [
        'cash'   => 'Cash',
        'bank'   => 'Bank',
        'online' => 'Online',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function receipts(): MorphMany
    {
        return $this->morphMany(PaymentReceipt::class, 'payable');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /** Outstanding balance (never stored). */
    public function getDueAttribute(): string
    {
        return number_format((float) $this->total_amount - (float) $this->paid_amount, 2, '.', '');
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
}
