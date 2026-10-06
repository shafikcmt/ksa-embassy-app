<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The BMET interface uses the existing manpower register without duplicating records. */
class BmetEntry extends ManpowerCompletion
{
    protected $table = 'manpower_completions';

    protected $fillable = [
        'agency_id', 'mofa_entry_id', 'hr_profile_id', 'full_name', 'father_name', 'passport_number',
        'visa_number', 'id_number', 'ec_number', 'ec_date', 'reference', 'remarks',
        'agent_id', 'status', 'created_by', 'updated_by',
    ];

    public const STATUSES = ['pending' => 'Pending', 'cleared' => 'Cleared', 'expired' => 'Expired', 'hold' => 'Hold'];

    public const ICONS = ['pending' => 'bi-hourglass-split', 'cleared' => 'bi-check-circle', 'expired' => 'bi-calendar-x', 'hold' => 'bi-pause-circle'];

    public function hrProfile(): BelongsTo
    {
        return $this->belongsTo(HrProfile::class);
    }

    public function getFullNameAttribute(): ?string
    {
        return $this->customer_name;
    }

    public function setFullNameAttribute($value): void
    {
        $this->attributes['customer_name'] = $value;
    }

    public function getPassportNumberAttribute(): ?string
    {
        return $this->passport_no;
    }

    public function setPassportNumberAttribute($value): void
    {
        $this->attributes['passport_no'] = strtoupper(trim($value));
    }

    public function getEcDateAttribute()
    {
        return $this->completed_date;
    }

    public function setEcDateAttribute($value): void
    {
        $this->setAttribute('completed_date', $value);
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'cleared' && $this->ec_expiry_date?->lt(today())) {
            return 'expired';
        }

        return $this->status ?? 'pending';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->effective_status];
    }

    public function getExpiringSoonAttribute(): bool
    {
        return $this->effective_status === 'cleared' && $this->ec_expiry_date
            && $this->ec_expiry_date->lt(today()->addDays(30));
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'expired' => $query->where(fn ($q) => $q->where('status', 'expired')->orWhere(fn ($q) => $q->where('status', 'cleared')->whereDate('ec_expiry_date', '<', today()))),
            'cleared' => $query->where('status', 'cleared')->whereDate('ec_expiry_date', '>=', today()),
            'pending', 'hold' => $query->where('status', $status),
            default => $query,
        };
    }
}
