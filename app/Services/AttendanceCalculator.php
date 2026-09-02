<?php

namespace App\Services;

use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Services\Attendance\AttendanceResult;
use App\Services\Attendance\Expectation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Attendance late/absent/overtime engine (H3b). The genuinely-new calculation
 * category of the Attendance module — analogous to ErpReportService's role, and
 * held to the same discipline: the core math is PURE (no DB, fully unit-testable)
 * and this service NEVER writes attendance_records (the controller owns writes).
 *
 * Timezone model (decision 1A): instants are stored UTC; every wall-clock
 * comparison goes through toAgencyTz(), so a single UTC moment is judged against
 * the agency's own timezone. workDateFor() derives the agency-tz calendar date a
 * check-in belongs to (correct across UTC midnight + overnight shifts).
 *
 * The read-only helpers that assemble an Expectation from settings/shift/holidays
 * and derive empty-day status (decision 2A, lazy-on-read) are added in the next
 * step; this file currently holds the proven pure core.
 */
class AttendanceCalculator
{
    /** THE central conversion helper — the only place UTC becomes agency-local. */
    public function toAgencyTz(CarbonInterface $utc, string $timezone): CarbonImmutable
    {
        return CarbonImmutable::instance($utc)->setTimezone($timezone);
    }

    /**
     * The agency-tz calendar date a check-in belongs to ('Y-m-d'). Uses the
     * agency timezone, so a 00:30-local check-in is NOT mis-filed onto the
     * previous UTC day (and vice-versa near midnight).
     */
    public function workDateFor(CarbonInterface $checkInUtc, string $timezone): string
    {
        return $this->toAgencyTz($checkInUtc, $timezone)->format('Y-m-d');
    }

    /**
     * PURE. Judge one day's check-in/out against an Expectation. No DB, no clock,
     * no mutation of inputs — same (in, out, exp) always yields the same result.
     *
     * Rules:
     *  - lateness = max(0, checkIn_local − expected_start).
     *      ≤ grace                    → present (late 0)
     *      ≥ halfDayAfter (if set)    → half_day (late = lateness)
     *      otherwise                  → late     (late = lateness)
     *  - overtime = over ≥ threshold ? over : 0, where over = max(0, out_local −
     *      expected_end) and threshold = overtimeAfter ?? 0.
     *  - worked = max(0, out_local − in_local).
     *  - Overnight (expected_end ≤ expected_start) rolls expected_end to +1 day.
     *
     * A null checkIn yields present/0s (callers only invoke evaluate() for rows
     * that actually have a check-in; empty days are a derive-side concern).
     */
    public function evaluate(?CarbonInterface $checkInUtc, ?CarbonInterface $checkOutUtc, Expectation $exp): AttendanceResult
    {
        $tz = $exp->timezone;

        $start = CarbonImmutable::parse("{$exp->date} {$exp->expectedStart}", $tz);
        $end   = CarbonImmutable::parse("{$exp->date} {$exp->expectedEnd}", $tz);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay(); // overnight shift: end is the next calendar day
        }

        if ($checkInUtc === null) {
            return new AttendanceResult('present', 0, 0, 0);
        }

        $in  = $this->toAgencyTz($checkInUtc, $tz);
        $out = $checkOutUtc !== null ? $this->toAgencyTz($checkOutUtc, $tz) : null;

        $lateness = max(0, $this->minutesBetween($start, $in));

        if ($lateness <= $exp->graceMinutes) {
            $status = 'present';
            $lateMinutes = 0;
        } elseif ($exp->halfDayAfterMinutes !== null && $lateness >= $exp->halfDayAfterMinutes) {
            $status = 'half_day';
            $lateMinutes = $lateness;
        } else {
            $status = 'late';
            $lateMinutes = $lateness;
        }

        $overtimeMinutes = 0;
        $workedMinutes   = 0;
        if ($out !== null) {
            $over = max(0, $this->minutesBetween($end, $out));
            $threshold = $exp->overtimeAfterMinutes ?? 0;
            $overtimeMinutes = $over >= $threshold ? $over : 0;

            $workedMinutes = max(0, $this->minutesBetween($in, $out));
        }

        return new AttendanceResult($status, $lateMinutes, $overtimeMinutes, $workedMinutes);
    }

    /**
     * PURE lazy-on-read resolver (decision 2A): the display status for one
     * employee-day WITHOUT ever writing a row. An existing row always wins (a
     * real check-in or an admin marker is authoritative). Otherwise the status is
     * DERIVED and nothing is materialised:
     *   holiday → 'holiday'; weekend → 'weekend'; approved leave → 'on_leave';
     *   else a past day (or today past auto_absent_time) → 'absent';
     *        today before the cutoff / no cutoff / future → 'pending'.
     *
     * @param ?string $rowStatus  status of an existing attendance_records row, or null if none exists
     * @param ?string $autoAbsentTime  'H:i' cutoff, or null (never auto-absent same-day)
     */
    public function deriveDayStatus(
        string $date,
        ?string $rowStatus,
        bool $isWeekend,
        bool $isHoliday,
        bool $isOnLeave,
        CarbonInterface $nowUtc,
        string $timezone,
        ?string $autoAbsentTime
    ): string {
        if ($rowStatus !== null) {
            return $rowStatus; // a real/manual row is authoritative (e.g. worked on a holiday)
        }

        if ($isHoliday) {
            return 'holiday';
        }
        if ($isWeekend) {
            return 'weekend';
        }
        if ($isOnLeave) {
            return 'on_leave';
        }

        $nowLocal = $this->toAgencyTz($nowUtc, $timezone);
        $today    = $nowLocal->format('Y-m-d');

        if ($date < $today) {
            return 'absent'; // a past working day with no check-in
        }
        if ($date > $today) {
            return 'pending'; // future working day, not yet due
        }

        // Today: absent only once the auto-absent cutoff has passed.
        if ($autoAbsentTime === null) {
            return 'pending';
        }
        $cutoff = CarbonImmutable::parse("{$today} {$autoAbsentTime}", $timezone);

        return $nowLocal->greaterThanOrEqualTo($cutoff) ? 'absent' : 'pending';
    }

    // ── Read-only assembly (never writes attendance_records) ──────────────────

    /**
     * Build the Expectation for an employee on a date from the agency settings.
     * The employee's assigned shift wins for start/end times; with no (or a
     * retired) shift it falls back to the office_start/office_end defaults. Only
     * reads — the pure evaluate()/deriveDayStatus() above do the judging.
     */
    public function resolveExpectation(Employee $employee, string $date, AttendanceSetting $settings): Expectation
    {
        $shift = $employee->shift; // null when unassigned or the shift was soft-deleted

        $start = $this->hm($shift?->start_time ?? $settings->office_start);
        $end   = $this->hm($shift?->end_time ?? $settings->office_end);

        return new Expectation(
            $date,
            $settings->timezone ?: 'UTC',
            $start,
            $end,
            (int) $settings->grace_minutes,
            $settings->half_day_after_minutes !== null ? (int) $settings->half_day_after_minutes : null,
            $settings->overtime_after_minutes !== null ? (int) $settings->overtime_after_minutes : null,
        );
    }

    /** Is $date a weekend for this agency? weekend_days are Carbon dayOfWeek ints (0=Sun). */
    public function isWeekend(string $date, array $weekendDays, string $timezone): bool
    {
        $dow = CarbonImmutable::parse($date, $timezone)->dayOfWeek;

        return in_array($dow, array_map('intval', $weekendDays), true);
    }

    /** Normalise a stored time ('HH:MM:SS' | 'HH:MM' | null) to 'H:i'; default 09:00/17:00 handled by caller. */
    private function hm(?string $time): string
    {
        return $time ? substr($time, 0, 5) : '00:00';
    }

    /** Whole minutes from $a to $b (signed, floored). Deterministic via timestamps. */
    private function minutesBetween(CarbonInterface $a, CarbonInterface $b): int
    {
        return (int) floor(($b->getTimestamp() - $a->getTimestamp()) / 60);
    }
}
