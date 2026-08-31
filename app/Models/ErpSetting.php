<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

/**
 * Per-agency ERP settings (opening balance + Profit/Loss privacy controls).
 *
 * The Profit/Loss security code is never stored in plaintext: the setter hashes
 * it with bcrypt (same as user passwords), and verifyCode() checks against the
 * hash. Assigning an empty value clears protection.
 */
class ErpSetting extends Model
{
    protected $fillable = [
        'agency_id',
        'opening_balance', 'opening_balance_note',
        'pl_security_code', 'pl_visible_to_all',
        'double_mofa_rate',
    ];

    protected $casts = [
        'opening_balance'   => 'decimal:2',
        'double_mofa_rate'  => 'decimal:2',
        'pl_visible_to_all' => 'boolean',
    ];

    protected $hidden = [
        'pl_security_code',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /**
     * Hash the Profit/Loss security code on assignment. An empty value clears
     * it (no protection). An already-hashed bcrypt string is stored as-is so a
     * plain updateOrCreate round-trip does not double-hash.
     */
    public function setPlSecurityCodeAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['pl_security_code'] = null;
            return;
        }

        if (Hash::isHashed($value)) {
            $this->attributes['pl_security_code'] = $value;
            return;
        }

        $this->attributes['pl_security_code'] = Hash::make($value);
    }

    public function hasSecurityCode(): bool
    {
        return ! empty($this->pl_security_code);
    }

    public function verifyCode(?string $code): bool
    {
        if (! $this->hasSecurityCode()) {
            return true;
        }

        return $code !== null && Hash::check($code, $this->pl_security_code);
    }
}
