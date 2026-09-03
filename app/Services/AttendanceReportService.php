<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * AttendanceReportService — READ-ONLY aggregation for the H3d Dashboard & Reports.
 *
 * Held to the ErpReportService discipline: it takes an explicit int $agencyId,
 * scopes every query to that agency BEFORE grouping, and NEVER writes to
 * attendance_records / leave_requests. It is the first production consumer of the
 * pure AttendanceCalculator::deriveDayStatus() (proven by test in H3b/H3c) — the
 * calculator stays untouched; this service only orchestrates the read.
 *
 * resolveRange() is the core read-loop. The N+1 guard is structural: it runs a
 * FIXED number of batched queries up front (settings, records, holidays, approved
 * leave) and then derives every employee-day in memory with ZERO further queries —
 * cost is O(employees × days) CPU but a constant ~4 queries regardless of range.
 */
class AttendanceReportService
{
    public function __construct(private AttendanceCalculator $calc)
    {
    }

    /**
     * The per-employee, per-day derived status matrix for [$from, $to] inclusive.
     *
     * @param  array<int,int>  $employeeIds  agency-scoped employee ids to resolve
     * @param  ?CarbonInterface $nowUtc       injectable "now" (UTC) for deterministic tests
     * @return array<int,array<string,string>>  [employeeId => ['Y-m-d' => status, …]]
     */
    public function resolveRange(int $agencyId, array $employeeIds, string $from, string $to, ?CarbonInterface $nowUtc = null): array
    {
        $employeeIds = array_values(array_unique(array_map('intval', $employeeIds)));
        if (empty($employeeIds) || $from > $to) {
            return array_fill_keys($employeeIds, []);
        }

        $now = $nowUtc ? CarbonImmutable::instance($nowUtc) : CarbonImmutable::now('UTC');

        // ── (1) settings: agency-wide weekend days / timezone / auto-absent cutoff ──
        $settings    = AttendanceSetting::forAgency($agencyId)->first();
        $tz          = ($settings?->timezone ?: null) ?: 'UTC';
        $weekendDays = $settings?->weekend_days ?? [];
        $autoAbsent  = $settings?->auto_absent_time; // 'H:i(:s)' or null

        // ── (2) authoritative rows (real check-ins + admin markers) in range ──
        $rowStatus = []; // [employeeId][Y-m-d] => status
        AttendanceRecord::forAgency($agencyId)
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('work_date', [$from, $to])
            ->get(['employee_id', 'work_date', 'status'])
            ->each(function ($r) use (&$rowStatus) {
                $rowStatus[(int) $r->employee_id][$r->work_date->format('Y-m-d')] = $r->status;
            });

        // ── (3) holidays: agency-wide set of 'Y-m-d' ──
        $holidaySet = Holiday::forAgency($agencyId)
            ->whereBetween('holiday_date', [$from, $to])
            ->pluck('holiday_date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->flip()->all(); // ['Y-m-d' => idx] for O(1) isset()

        // ── (4) approved leave per employee (ONE query, expanded in PHP) ──
        $leaveSet = [];
        foreach (LeaveRequest::approvedDatesForMany($agencyId, $employeeIds, $from, $to) as $empId => $dates) {
            $leaveSet[(int) $empId] = array_flip($dates); // ['Y-m-d' => idx]
        }

        // Pre-expand the calendar once (shared across all employees).
        $dates  = [];
        $cursor = CarbonImmutable::parse($from);
        $last   = CarbonImmutable::parse($to);
        for (; $cursor->lessThanOrEqualTo($last); $cursor = $cursor->addDay()) {
            $dates[] = $cursor->format('Y-m-d');
        }

        // ── In-memory derivation: NO database access inside this loop ──
        $matrix = [];
        foreach ($employeeIds as $empId) {
            $days = [];
            foreach ($dates as $date) {
                $days[$date] = $this->calc->deriveDayStatus(
                    $date,
                    $rowStatus[$empId][$date] ?? null,
                    $this->calc->isWeekend($date, $weekendDays, $tz),
                    isset($holidaySet[$date]),
                    isset($leaveSet[$empId][$date]),
                    $now,
                    $tz,
                    $autoAbsent,
                );
            }
            $matrix[$empId] = $days;
        }

        return $matrix;
    }
}
