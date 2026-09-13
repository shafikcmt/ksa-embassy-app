<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP Agent Khata ledger row (E3) — append-only.
 *
 * amount is always positive; `type` gives the sign when summing
 * (debit = +, credit = −). Never updated or deleted; corrected via a reversal
 * row (reverses_id set). There is no stored balance — it is live-computed.
 */
class AgentTransaction extends Model
{
    protected $fillable = [
        'agency_id', 'agent_id', 'txn_date', 'type',
        'amount', 'note', 'reverses_id', 'recorded_by',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'amount'   => 'decimal:2',
    ];

    public const TYPE_DEBIT  = 'debit';
    public const TYPE_CREDIT = 'credit';

    // Display labels for the human-facing dropdown / ledger. The stored enum
    // keys ('debit'/'credit') and the balance math are unchanged: debit = +amount,
    // credit = -amount. Per business convention: "Received" (money in from the
    // agent) is a credit (-), "Paid" (money out to the agent) is a debit (+).
    public const TYPES = [
        'credit' => 'Received',
        'debit'  => 'Paid',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function isReversal(): bool
    {
        return $this->reverses_id !== null;
    }

    /** Signed effect on the agent balance: debit = +amount, credit = −amount. */
    public function signedAmount(): string
    {
        $sign = $this->type === self::TYPE_DEBIT ? 1 : -1;
        return number_format($sign * (float) $this->amount, 2, '.', '');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }
}
