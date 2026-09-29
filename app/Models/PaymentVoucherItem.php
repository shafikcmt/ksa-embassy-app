<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One payment voucher line. `amount` (= quantity × unit_price) is not fillable —
 * App\Services\PaymentVoucherService computes it in integer cents together with
 * the parent's totals, in one transaction (no float saving-hook math).
 */
class PaymentVoucherItem extends Model
{
    protected $fillable = [
        'description', 'quantity', 'unit_price', 'remarks', 'sort_order',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'amount'     => 'decimal:2',
    ];

    public function paymentVoucher(): BelongsTo
    {
        return $this->belongsTo(PaymentVoucher::class);
    }
}
