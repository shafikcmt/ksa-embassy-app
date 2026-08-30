<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP MOFA application log entry (E1 operational tracker).
 *
 * payment_method is a categorical tag only (no amount / no money math).
 */
class MofaEntry extends Model
{
    protected $fillable = [
        'agency_id', 'mofa_date', 'mofa_number', 'visa_serial',
        'full_name', 'passport_no', 'reference_name',
        'payment_method', 'whatsapp_number', 'payment_note',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'mofa_date' => 'date',
    ];

    /** Categorical payment tags (value => label). No monetary meaning. */
    public const PAYMENT_METHODS = [
        'company_account' => 'Company Account',
        'card_payment'    => 'Card Payment',
        'no_payment'      => 'No Payment',
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

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function paymentMethodLabel(): ?string
    {
        return $this->payment_method
            ? (self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method)
            : null;
    }
}
