<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ERP medical-check log entry (E1 operational tracker). Workflow status only —
 * no money math.
 */
class Medical extends Model
{
    protected $fillable = [
        'agency_id', 'full_name', 'father_name', 'passport_no',
        'medical_center_name', 'medical_code',
        'medical_issue_date', 'medical_expire_date', 'medical_status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'medical_issue_date'  => 'date',
        'medical_expire_date' => 'date',
    ];

    public const MEDICAL_STATUSES = [
        'pending'      => 'Pending',
        'process'      => 'Process',
        'under_review' => 'Under Review',
        'fit'          => 'Fit',
        'unfit'        => 'Unfit',
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
        return self::MEDICAL_STATUSES[$this->medical_status] ?? ucfirst((string) $this->medical_status);
    }
}
