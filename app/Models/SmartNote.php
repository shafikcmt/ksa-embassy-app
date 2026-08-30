<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmartNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id', 'user_id', 'title', 'body',
        'category', 'priority', 'status', 'is_private', 'pinned',
        'reminder_at', 'archived_at',
    ];

    protected $casts = [
        'is_private'  => 'boolean',
        'pinned'      => 'boolean',
        'reminder_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'general'   => 'General',
        'important' => 'Important',
        'office'    => 'Office',
        'accounts'  => 'Accounts',
        'embassy'   => 'Embassy',
        'mofa'      => 'MOFA',
        'agent'     => 'Agent',
        'client'    => 'Client',
        'personal'  => 'Personal',
    ];

    public const PRIORITIES = [
        'urgent' => 'Urgent',
        'high'   => 'High',
        'medium' => 'Medium',
        'low'    => 'Low',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
