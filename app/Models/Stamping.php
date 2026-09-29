<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ERP visa stamping log entry (E1 operational tracker). Workflow status only —
 * no money math. The Visa Stamping screens use App\Models\VisaStamping over the
 * same table; SoftDeletes lives here so every Stamping query skips deleted rows.
 */
class Stamping extends Model
{
    use SoftDeletes;

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
        'completed'  => 'Completed',
        'stamped'    => 'Stamped',
        'expired'    => 'Expired',
        'rejected'   => 'Rejected',
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
