<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One invoice line — per-passenger recruitment fee record.
 *
 * total_amount = processing_fee + mofa_fee
 * due_amount   = total_amount  − (paid_amount ?? 0)
 *
 * Both derived columns are set by the booted saving hook; never trust them
 * from the request.
 */
class InvoiceItem extends Model
{
    protected $fillable = [
        'hr_profile_id',
        'passenger_name',
        'passport_no',
        'processing_fee',
        'mofa_fee',
        'paid_amount',
        'remarks',
    ];

    protected $casts = [
        'processing_fee' => 'decimal:2',
        'mofa_fee'       => 'decimal:2',
        'total_amount'   => 'decimal:2',
        'paid_amount'    => 'decimal:2',
        'due_amount'     => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $proc = (float) ($item->processing_fee ?? 0);
            $mofa = (float) ($item->mofa_fee ?? 0);
            $paid = (float) ($item->paid_amount ?? 0);
            $item->total_amount = round($proc + $mofa, 2);
            $item->due_amount   = round($item->total_amount - $paid, 2);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }

    /** Name shown on the invoice: the saved snapshot, else the linked HR profile. */
    public function displayName(): ?string
    {
        return $this->passenger_name ?: $this->hrProfile?->full_name_en;
    }

    public function displayPassport(): ?string
    {
        return $this->passport_no ?: $this->hrProfile?->passport?->passport_number;
    }
}
