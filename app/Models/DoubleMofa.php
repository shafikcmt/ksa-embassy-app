<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * ERP Double MOFA (E2). Money-bearing record.
 *
 * billing_amount is snapshotted from erp_settings.double_mofa_rate at creation.
 * paid_amount is EXCLUDED from $fillable — only ErpPaymentService writes it
 * (recomputed from the payment_receipts ledger inside a locked transaction).
 */
class DoubleMofa extends Model
{
    protected $table = 'double_mofas';

    protected $fillable = [
        'agency_id', 'mofa_date', 'full_name', 'passport_no',
        'visa_serial', 'old_mofa_number', 'reference', 'billing_amount', 'status',
        'created_by', 'updated_by',
        // NOTE: paid_amount is deliberately NOT fillable.
    ];

    protected $casts = [
        'mofa_date'      => 'date',
        'billing_amount' => 'decimal:2',
        'paid_amount'    => 'decimal:2',
    ];

    public const STATUSES = [
        'unpaid'  => 'Unpaid',
        'partial' => 'Partial',
        'paid'    => 'Paid',
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

    /** Unpaid balance (never stored). */
    public function getUnpaidAttribute(): string
    {
        return number_format((float) $this->billing_amount - (float) $this->paid_amount, 2, '.', '');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
