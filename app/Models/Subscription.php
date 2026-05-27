<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'agency_id', 'plan_id', 'start_date', 'end_date',
        'status', 'payment_status', 'amount', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function agency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial'])
            && $this->end_date->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast() || $this->status === 'expired';
    }

    public function daysRemaining(): int
    {
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trial'])
            ->where('end_date', '>=', now());
    }
}
