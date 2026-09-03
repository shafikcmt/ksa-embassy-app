<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Leave request (H3c). Staff submit → admin approve/reject. An APPROVED request
 * is the ONLY kind that drives attendance: approvedDatesFor() expands approved
 * ranges into the per-day $isOnLeave bool that AttendanceCalculator::deriveDayStatus()
 * consumes. status + decision columns are written by the controller state machine,
 * never mass-assigned from raw input.
 */
class LeaveRequest extends Model
{
    protected $fillable = [
        'agency_id', 'employee_id', 'leave_type_id',
        'start_date', 'end_date', 'days',
        'status', 'reason', 'decision_note',
        'decided_by', 'decided_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'decided_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    /** Statuses that still occupy a date range for overlap purposes (a live claim). */
    public const ACTIVE_STATUSES = ['pending', 'approved'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
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

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Requests that would collide with [$from, $to] for one employee — a live
     * claim (pending or approved) whose range overlaps. Excludes $ignoreId so an
     * edit doesn't clash with itself. Overlap = start <= to AND end >= from.
     */
    public function scopeOverlapping($query, int $employeeId, string $from, string $to, ?int $ignoreId = null)
    {
        return $query->where('employee_id', $employeeId)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId));
    }

    /**
     * THE derivation seam. The set of 'Y-m-d' dates covered by APPROVED leave for
     * one employee, intersected with [$from, $to]. The read path (H3d) reduces this
     * to the per-day bool `in_array($date, …, true)` it hands deriveDayStatus() —
     * the calculator itself never changes.
     *
     * @return array<int,string> distinct 'Y-m-d' strings, unordered
     */
    public static function approvedDatesFor(int $agencyId, int $employeeId, string $from, string $to): array
    {
        return static::approvedDatesForMany($agencyId, [$employeeId], $from, $to)[$employeeId] ?? [];
    }

    /**
     * Batched form of the seam for the H3d report read-loop: approved-leave dates
     * for MANY employees in ONE query (avoids a query-per-employee N+1). Ranges are
     * expanded and clipped to [$from, $to] in PHP. Every requested id is present in
     * the result (empty array when an employee has no approved leave in the window).
     *
     * @param  array<int,int>  $employeeIds
     * @return array<int,array<int,string>>  [employeeId => ['Y-m-d', …]]
     */
    public static function approvedDatesForMany(int $agencyId, array $employeeIds, string $from, string $to): array
    {
        $ids    = array_values(array_unique(array_map('intval', $employeeIds)));
        $result = array_fill_keys($ids, []);
        if (empty($ids)) {
            return $result;
        }

        $rows = static::query()
            ->where('agency_id', $agencyId)
            ->whereIn('employee_id', $ids)
            ->approved()
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get(['employee_id', 'start_date', 'end_date']);

        $windowStart = CarbonImmutable::parse($from);
        $windowEnd   = CarbonImmutable::parse($to);
        $sets        = [];

        foreach ($rows as $row) {
            $cursor = CarbonImmutable::parse($row->start_date->format('Y-m-d'));
            $last   = CarbonImmutable::parse($row->end_date->format('Y-m-d'));
            for (; $cursor->lessThanOrEqualTo($last); $cursor = $cursor->addDay()) {
                if ($cursor->lessThan($windowStart) || $cursor->greaterThan($windowEnd)) {
                    continue; // clip to the requested window
                }
                $sets[(int) $row->employee_id][$cursor->format('Y-m-d')] = true;
            }
        }

        foreach ($sets as $empId => $dates) {
            $result[$empId] = array_keys($dates);
        }

        return $result;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
