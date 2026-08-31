<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * ERP payment ledger row (E2) — append-only.
 *
 * amount is always positive; `type` gives the sign when summing
 * (payment = +amount, reversal = -amount). Never updated or deleted.
 */
class PaymentReceipt extends Model
{
    protected $fillable = [
        'agency_id', 'payable_type', 'payable_id',
        'type', 'amount', 'note', 'reverses_id', 'received_by', 'received_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public const TYPE_PAYMENT  = 'payment';
    public const TYPE_REVERSAL = 'reversal';

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function isReversal(): bool
    {
        return $this->type === self::TYPE_REVERSAL;
    }

    /** The signed effect on the parent's paid_amount. */
    public function signedAmount(): string
    {
        $sign = $this->isReversal() ? -1 : 1;
        return number_format($sign * (float) $this->amount, 2, '.', '');
    }
}
