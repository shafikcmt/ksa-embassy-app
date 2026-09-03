<?php

namespace Tests\Unit\Attendance;

use App\Services\AttendanceCalculator;
use App\Services\AttendanceReportService;
use PHPUnit\Framework\TestCase;

/**
 * H3d check (C) — aggregation math, proven against an INDEPENDENT hand computation.
 * tally / onTimePct / perEmployee / dailyTotals are pure array ops (no DB), so this
 * lives in the SQLite-clean Unit suite. on_time% = present / (present+late+half_day).
 */
class AttendanceAggregationTest extends TestCase
{
    private AttendanceReportService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new AttendanceReportService(new AttendanceCalculator());
    }

    /** A fixed matrix whose tallies are worked out by hand below. */
    private function matrix(): array
    {
        return [
            1 => ['2026-09-07' => 'present', '2026-09-08' => 'late', '2026-09-09' => 'present', '2026-09-10' => 'half_day', '2026-09-11' => 'absent'],
            2 => ['2026-09-07' => 'on_leave', '2026-09-08' => 'present', '2026-09-09' => 'weekend', '2026-09-10' => 'holiday', '2026-09-11' => 'present'],
        ];
    }

    public function test_tally_counts_and_on_time_pct(): void
    {
        $t = $this->svc->tally($this->matrix());

        // present: emp1(07,09) + emp2(08,11) = 4; late 1; half_day 1; absent 1; on_leave 1; weekend 1; holiday 1.
        $this->assertSame(4, $t['present']);
        $this->assertSame(1, $t['late']);
        $this->assertSame(1, $t['half_day']);
        $this->assertSame(1, $t['absent']);
        $this->assertSame(1, $t['on_leave']);
        $this->assertSame(1, $t['weekend']);
        $this->assertSame(1, $t['holiday']);
        $this->assertSame(0, $t['excused']);
        $this->assertSame(0, $t['pending']);
        // on_time = 4 / (4 + 1 + 1) = 66.7
        $this->assertSame(66.7, $t['on_time_pct']);
    }

    public function test_on_time_pct_is_null_when_nobody_was_expected(): void
    {
        $this->assertNull($this->svc->tally([])['on_time_pct']);
        // Only off-days (no present/late/half_day) → denominator 0 → null.
        $this->assertNull($this->svc->tally([9 => ['2026-09-11' => 'weekend', '2026-09-12' => 'holiday']])['on_time_pct']);
    }

    public function test_per_employee_tallies_are_independent(): void
    {
        $per = $this->svc->perEmployee($this->matrix());

        $this->assertSame(50.0, $per[1]['on_time_pct']);  // 2 present / (2+1+1)
        $this->assertSame(100.0, $per[2]['on_time_pct']); // 2 present / (2+0+0)
        $this->assertSame(2, $per[1]['present']);
        $this->assertSame(1, $per[1]['absent']);
        $this->assertSame(1, $per[2]['on_leave']);
    }

    public function test_daily_totals_are_date_ascending_and_fully_keyed(): void
    {
        $daily = $this->svc->dailyTotals($this->matrix());

        $this->assertSame(['2026-09-07', '2026-09-08', '2026-09-09', '2026-09-10', '2026-09-11'], array_keys($daily));
        // 07: emp1 present + emp2 on_leave.
        $this->assertSame(1, $daily['2026-09-07']['present']);
        $this->assertSame(1, $daily['2026-09-07']['on_leave']);
        $this->assertSame(0, $daily['2026-09-07']['absent']);
        // Every day carries the full status vocabulary (zeros included) for the chart.
        foreach ($daily as $counts) {
            foreach (AttendanceReportService::STATUS_KEYS as $k) {
                $this->assertArrayHasKey($k, $counts);
            }
        }
        // Column total for a day equals the number of employees (partition).
        $this->assertSame(2, array_sum($daily['2026-09-07']));
    }
}
