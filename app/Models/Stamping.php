<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP visa stamping log entry (E1 operational tracker). Workflow status only —
 * no money math.
 */
class Stamping extends Model
{
    protected $fillable = [
        'agency_id', 'stamp_date', 'visa_serial',
        'full_name', 'passport_no', 'visa_number', 'id_number',
        'reference', 'status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'stamp_date' => 'date',
    ];

    public const STATUSES = [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'stamped'    => 'Stamped',
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

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
