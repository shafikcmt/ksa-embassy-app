<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $fillable = [
        'agency_id', 'name', 'email', 'phone',
        'address', 'status', 'notes',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
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

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function hrProfiles(): HasMany
    {
        return $this->hasMany(HrProfile::class);
    }

    public function embassyListItems(): HasMany
    {
        return $this->hasMany(EmbassyListItem::class);
    }
}
