<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $fillable = [
        'agency_id', 'title', 'body', 'type', 'is_active', 'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'date',
    ];

    public function agency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function scopeForAgency($query, ?int $agencyId)
    {
        return $query->where(fn($q) => $q->whereNull('agency_id')->orWhere('agency_id', $agencyId));
    }
}
