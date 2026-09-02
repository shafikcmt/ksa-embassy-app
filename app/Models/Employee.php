<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Attendance employee (H3a). The agency's own workforce that checks in —
 * distinct from HrProfile (visa candidates). Optionally linked to a login
 * (user_id) for self-check-in; a null user_id is a valid admin-managed,
 * login-less employee. SoftDeletes so retiring never orphans attendance_records.
 */
class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id', 'user_id', 'shift_id',
        'name', 'designation', 'phone', 'email', 'join_date', 'status',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public const STATUSES = [
        'active'   => 'Active',
        'inactive' => 'Inactive',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
