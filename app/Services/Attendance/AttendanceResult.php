<?php

namespace App\Services\Attendance;

/**
 * Immutable output of AttendanceCalculator::evaluate() for a single day's
 * check-in/out. status is one of present|late|half_day (the calculator only ever
 * produces these three; absent/on_leave/excused are admin/derive concerns).
 */
final class AttendanceResult
{
    public function __construct(
        public readonly string $status,          // present | late | half_day
        public readonly int $lateMinutes,
        public readonly int $overtimeMinutes,
        public readonly int $workedMinutes,
    ) {
    }
}
