<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmbassyList extends Model
{
    protected $fillable = [
        'agency_id', 'list_no', 'title', 'list_date', 'status',
        'total_new', 'total_restamping', 'total_cancellation', 'total_items',
        'notes', 'finalized_at', 'printed_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'list_date'    => 'date',
        'finalized_at' => 'datetime',
        'printed_at'   => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(EmbassyListItem::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('created_at', now()->year)
                     ->whereMonth('created_at', now()->month);
    }

    // ── Helpers ──────────���───────────────────────────────��──────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['finalized', 'printed']);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canEdit(): bool
    {
        return $this->isDraft();
    }

    public function recalculateTotals(): void
    {
        $counts = $this->items()
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category');

        $this->update([
            'total_new'          => $counts['new'] ?? 0,
            'total_restamping'   => $counts['restamping'] ?? 0,
            'total_cancellation' => $counts['cancellation'] ?? 0,
            'total_items'        => array_sum($counts->toArray()),
        ]);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'draft'      => 'bg-warning text-dark',
            'finalized'  => 'bg-success',
            'printed'    => 'bg-info text-dark',
            'cancelled'  => 'bg-secondary',
            default      => 'bg-secondary',
        };
    }
}
