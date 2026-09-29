<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $fillable = [
        'agency_id', 'name', 'email', 'phone',
        'address', 'status', 'notes', 'opening_balance',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'status'          => 'string',
        'opening_balance' => 'decimal:2',
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

    /**
     * ERP "Reference" dropdown source: this agency's agents as [name, active].
     * The dropdown lists active ones; inactive names only label saved values.
     * No agency → empty list (never queries with a null agency_id).
     */
    public static function referenceOptions(?int $agencyId): array
    {
        if (! $agencyId) {
            return [];
        }

        return static::forAgency($agencyId)->orderBy('name')->get(['name', 'status'])
            ->map(fn (self $a) => ['name' => $a->name, 'active' => $a->isActive()])
            ->values()
            ->all();
    }

    public function hrProfiles(): HasMany
    {
        return $this->hasMany(HrProfile::class);
    }

    public function agentTransactions(): HasMany
    {
        return $this->hasMany(AgentTransaction::class);
    }

    public function embassyListItems(): HasMany
    {
        return $this->hasMany(EmbassyListItem::class);
    }
}
