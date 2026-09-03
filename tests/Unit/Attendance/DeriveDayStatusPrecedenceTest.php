<?php

namespace Tests\Unit\Attendance;

use App\Services\AttendanceCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * H3c seam — precedence proof for deriveDayStatus(). The leave workflow feeds a
 * per-day $isOnLeave bool; these cases lock the ordering the design promised:
 *
 *     row status  >  holiday  >  weekend  >  on_leave  >  absent / pending
 *
 * Pure (no DB, no clock beyond the injected $now) so it can live in the Unit suite.
 */
class DeriveDayStatusPrecedenceTest extends TestCase
{
    private AttendanceCalculator $calc;
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calc = new AttendanceCalculator();
        // A fixed "now" well after the two test dates, so a flagless past day = absent.
        $this->now = CarbonImmutable::parse('2026-09-10 12:00', 'UTC');
    }

    private function derive(?string $row, bool $weekend, bool $holiday, bool $onLeave, string $date = '2026-09-01'): string
    {
        return $this->calc->deriveDayStatus($date, $row, $weekend, $holiday, $onLeave, $this->now, 'UTC', null);
    }

    /** An existing row (a real check-in / admin marker) outranks EVERY derived flag. */
    public function test_row_status_wins_over_holiday_weekend_and_leave(): void
    {
        $this->assertSame('present', $this->derive('present', weekend: true, holiday: true, onLeave: true));
        $this->assertSame('late', $this->derive('late', weekend: false, holiday: false, onLeave: true));
    }

    /** Holiday beats weekend and leave — you don't consume leave on a holiday. */
    public function test_holiday_beats_weekend_and_leave(): void
    {
        $this->assertSame('holiday', $this->derive(null, weekend: true, holiday: true, onLeave: true));
    }

    /** Weekend beats leave — a leave range spanning a weekend still shows the weekend. */
    public function test_weekend_beats_leave(): void
    {
        $this->assertSame('weekend', $this->derive(null, weekend: true, holiday: false, onLeave: true));
    }

    /** Only when no row/holiday/weekend applies does approved leave surface as on_leave. */
    public function test_on_leave_when_only_leave_flag_is_set(): void
    {
        $this->assertSame('on_leave', $this->derive(null, weekend: false, holiday: false, onLeave: true));
    }

    /** No flags → the ordinary absent/pending derivation (leave doesn't invent a status). */
    public function test_no_leave_falls_through_to_absent_or_pending(): void
    {
        // Past working day, no leave → absent.
        $this->assertSame('absent', $this->derive(null, false, false, false, '2026-09-01'));
        // Future working day, no leave → pending.
        $this->assertSame('pending', $this->derive(null, false, false, false, '2026-09-20'));
    }
}
