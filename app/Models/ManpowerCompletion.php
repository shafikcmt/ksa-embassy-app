<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ERP manpower completion log entry (E1 operational tracker).
 *
 * agent_id is a nullable link to an existing Agent so the E3 Agent Khata
 * ledger can aggregate per agent later. No money math here.
 */
class ManpowerCompletion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id', 'completed_date', 'customer_name', 'passport_no',
        'ec_number', 'agent_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'completed_date' => 'date',
        'ec_expiry_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $entry) {
            $entry->ec_expiry_date = $entry->completed_date?->copy()->addYearNoOverflow();
            if (! $entry->status) {
                $entry->status = filled($entry->ec_number) ? 'cleared' : 'pending';
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
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
}
