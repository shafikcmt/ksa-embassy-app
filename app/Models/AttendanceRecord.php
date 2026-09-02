<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance event-log row (H3b). Exists only for a real check-in or an admin
 * marker. status + the minute columns are computed by AttendanceCalculator and
 * set by the controller — never mass-assigned from raw request input.
 */
class AttendanceRecord extends Model
{
    protected $fillable = [
        'agency_id', 'employee_id', 'shift_id', 'work_date',
        'check_in_at', 'check_out_at', 'status',
        'late_minutes', 'overtime_minutes', 'worked_minutes',
        'source', 'note', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'work_date'    => 'date',
        'check_in_at'  => 'datetime', // UTC
        'check_out_at' => 'datetime', // UTC
    ];

    public const STATUSES = [
        'present'  => 'Present',
        'late'     => 'Late',
        'half_day' => 'Half day',
        'absent'   => 'Absent',
        'excused'  => 'Excused',
        'on_leave' => 'On leave',
    ];

    /** Statuses an admin may set directly on a manual (times-less) marker row. */
    public const MANUAL_STATUSES = ['present', 'late', 'half_day', 'absent', 'excused', 'on_leave'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
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

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('work_date', $date);
    }

    /** Open = checked in but not yet checked out (for the check-out lookup). */
    public function scopeOpen($query)
    {
        return $query->whereNotNull('check_in_at')->whereNull('check_out_at');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
