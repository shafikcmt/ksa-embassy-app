<?php

namespace App\Services\Attendance;

/**
 * The attendance "expectation" for one employee on one work date — a pure value
 * object with everything AttendanceCalculator::evaluate() needs to judge a
 * check-in/out. Wall-clock times are agency-local ('H:i'); the calculator builds
 * the concrete instants in $timezone from $date. Immutable.
 */
final class Expectation
{
    public function __construct(
        public readonly string $date,            // agency-tz work date, 'Y-m-d'
        public readonly string $timezone,        // IANA, e.g. 'Asia/Riyadh'
        public readonly string $expectedStart,   // 'H:i'
        public readonly string $expectedEnd,     // 'H:i' (may be <= start → overnight)
        public readonly int $graceMinutes,
        public readonly ?int $halfDayAfterMinutes, // null → half-day tier disabled
        public readonly ?int $overtimeAfterMinutes, // null → any overtime counts (threshold 0)
    ) {
    }
}
