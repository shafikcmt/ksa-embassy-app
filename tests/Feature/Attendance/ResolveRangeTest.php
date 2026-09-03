<?php

namespace Tests\Feature\Attendance;

use App\Models\Agency;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AttendanceReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * H3d — AttendanceReportService::resolveRange proven in ISOLATION (views/exports
 * not built yet). Two proof families:
 *   (A) Triangulation — the resolved matrix equals a hand-derived oracle, so the
 *       read-loop is checked against an INDEPENDENT computation, not itself.
 *   (B) Efficiency + safety — a CONSTANT query count regardless of employees×days
 *       (no N+1) and ZERO writes (read-only, ErpReportService discipline).
 *
 * Weekend = Fri(5)+Sat(6), tz UTC. Anchor dates (2026-09):
 *   07 Mon · 08 Tue · 09 Wed · 10 Thu · 11 Fri* · 12 Sat* · 13 Sun   (*=weekend)
 */
class ResolveRangeTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private Employee $emp1;
    private Employee $emp2;
    private LeaveType $type;
    private AttendanceReportService $svc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::create([
            'name' => 'Resolver Co', 'slug' => 'resolver-co', 'status' => 'active',
            'license_number' => 'LIC-RES-0001',
        ]);
        $this->emp1 = Employee::create(['agency_id' => $this->agency->id, 'name' => 'Emp One', 'status' => 'active']);
        $this->emp2 = Employee::create(['agency_id' => $this->agency->id, 'name' => 'Emp Two', 'status' => 'active']);
        $this->type = LeaveType::create(['agency_id' => $this->agency->id, 'name' => 'Annual', 'is_paid' => true]);

        AttendanceSetting::create([
            'agency_id' => $this->agency->id,
            'office_start' => '09:00', 'office_end' => '17:00', 'grace_minutes' => 0,
            'timezone' => 'UTC', 'weekend_days' => [5, 6], 'auto_absent_time' => null,
        ]);

        $this->svc = app(AttendanceReportService::class);
    }

    private function row(Employee $e, string $date, string $status): void
    {
        AttendanceRecord::create([
            'agency_id' => $this->agency->id, 'employee_id' => $e->id,
            'work_date' => $date, 'status' => $status, 'source' => 'admin',
        ]);
    }

    private function approvedLeave(Employee $e, string $from, string $to): LeaveRequest
    {
        return LeaveRequest::create([
            'agency_id' => $this->agency->id, 'employee_id' => $e->id, 'leave_type_id' => $this->type->id,
            'start_date' => $from, 'end_date' => $to, 'days' => 1, 'status' => 'approved',
        ]);
    }

    private function holiday(string $date): void
    {
        Holiday::create(['agency_id' => $this->agency->id, 'title' => 'Holiday', 'holiday_date' => $date]);
    }

    // ── (A) Triangulation ─────────────────────────────────────────────────────

    public function test_resolved_matrix_equals_hand_derived_oracle(): void
    {
        // Fixture.
        $this->row($this->emp1, '2026-09-07', 'present');
        $this->row($this->emp1, '2026-09-08', 'late');
        $this->approvedLeave($this->emp1, '2026-09-09', '2026-09-10'); // Wed–Thu
        $this->holiday('2026-09-09');                                  // clashes with leave → holiday wins
        $this->row($this->emp2, '2026-09-12', 'present');             // a Saturday → row beats weekend

        $now = CarbonImmutable::parse('2026-09-20 12:00', 'UTC'); // whole range is past

        // Independently reasoned expectation (NOT produced by resolveRange).
        $oracle = [
            $this->emp1->id => [
                '2026-09-07' => 'present',  // row
                '2026-09-08' => 'late',     // row
                '2026-09-09' => 'holiday',  // holiday > leave
                '2026-09-10' => 'on_leave', // approved leave
                '2026-09-11' => 'weekend',
                '2026-09-12' => 'weekend',
                '2026-09-13' => 'absent',   // past working day, no row
            ],
            $this->emp2->id => [
                '2026-09-07' => 'absent',
                '2026-09-08' => 'absent',
                '2026-09-09' => 'holiday',  // agency-wide
                '2026-09-10' => 'absent',
                '2026-09-11' => 'weekend',
                '2026-09-12' => 'present',  // row > weekend
                '2026-09-13' => 'absent',
            ],
        ];

        $actual = $this->svc->resolveRange(
            $this->agency->id, [$this->emp1->id, $this->emp2->id], '2026-09-07', '2026-09-13', $now
        );

        $this->assertSame($oracle, $actual);
    }

    public function test_every_cell_is_classified_exactly_once_partition(): void
    {
        $this->row($this->emp1, '2026-09-07', 'present');
        $this->approvedLeave($this->emp1, '2026-09-10', '2026-09-10');
        $this->holiday('2026-09-09');
        $now = CarbonImmutable::parse('2026-09-20 12:00', 'UTC');

        $matrix = $this->svc->resolveRange($this->agency->id, [$this->emp1->id, $this->emp2->id], '2026-09-07', '2026-09-13', $now);

        $cells = 0;
        foreach ($matrix as $days) {
            foreach ($days as $status) {
                $this->assertNotNull($status);
                $cells++;
            }
        }
        $this->assertSame(2 * 7, $cells); // employees × days, each classified once
    }

    public function test_cross_wire_reverts_when_approval_is_withdrawn(): void
    {
        $lr = $this->approvedLeave($this->emp1, '2026-09-10', '2026-09-10'); // Thursday, working day
        $now = CarbonImmutable::parse('2026-09-20 12:00', 'UTC');

        $before = $this->svc->resolveRange($this->agency->id, [$this->emp1->id], '2026-09-10', '2026-09-10', $now);
        $this->assertSame('on_leave', $before[$this->emp1->id]['2026-09-10']);

        // Withdraw the approval (e.g. reject/cancel/revoke) — derivation must revert.
        $lr->update(['status' => 'rejected']);

        $after = $this->svc->resolveRange($this->agency->id, [$this->emp1->id], '2026-09-10', '2026-09-10', $now);
        $this->assertSame('absent', $after[$this->emp1->id]['2026-09-10']);
    }

    public function test_today_and_future_days_are_pending_without_a_cutoff(): void
    {
        // No rows/holidays/leave in this window; auto_absent_time is null.
        $now = CarbonImmutable::parse('2026-09-15 12:00', 'UTC');
        $matrix = $this->svc->resolveRange($this->agency->id, [$this->emp2->id], '2026-09-14', '2026-09-16', $now);

        $this->assertSame('absent',  $matrix[$this->emp2->id]['2026-09-14']); // past
        $this->assertSame('pending', $matrix[$this->emp2->id]['2026-09-15']); // today, no cutoff
        $this->assertSame('pending', $matrix[$this->emp2->id]['2026-09-16']); // future
    }

    public function test_tenant_isolation_ignores_other_agency_rows(): void
    {
        $other = Agency::create(['name' => 'Other', 'slug' => 'other-res', 'status' => 'active', 'license_number' => 'LIC-RES-0002']);
        $otherEmp = Employee::create(['agency_id' => $other->id, 'name' => 'Intruder', 'status' => 'active']);
        AttendanceRecord::create([
            'agency_id' => $other->id, 'employee_id' => $otherEmp->id,
            'work_date' => '2026-09-07', 'status' => 'present', 'source' => 'admin',
        ]);
        $now = CarbonImmutable::parse('2026-09-20 12:00', 'UTC');

        // Resolving our agency's employee must NOT see the other tenant's present row.
        $matrix = $this->svc->resolveRange($this->agency->id, [$this->emp1->id], '2026-09-07', '2026-09-07', $now);
        $this->assertSame('absent', $matrix[$this->emp1->id]['2026-09-07']);
    }

    // ── (B) Efficiency + safety ───────────────────────────────────────────────

    public function test_query_count_is_constant_regardless_of_employees_and_days(): void
    {
        $now = CarbonImmutable::parse('2026-10-01 12:00', 'UTC');

        // Small: 2 employees × 3 days.
        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();
        $this->svc->resolveRange($this->agency->id, [$this->emp1->id, $this->emp2->id], '2026-09-07', '2026-09-09', $now);
        $small = count(DB::connection()->getQueryLog());

        // Large: 6 employees × 60 days, with some rows/holidays/leave sprinkled in.
        $ids = [$this->emp1->id, $this->emp2->id];
        for ($i = 0; $i < 4; $i++) {
            $ids[] = Employee::create(['agency_id' => $this->agency->id, 'name' => "Bulk $i", 'status' => 'active'])->id;
        }
        $this->row($this->emp1, '2026-09-07', 'present');
        $this->holiday('2026-09-20');
        $this->approvedLeave($this->emp2, '2026-09-15', '2026-09-18');

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();
        $this->svc->resolveRange($this->agency->id, $ids, '2026-09-01', '2026-10-30', $now);
        $large = count(DB::connection()->getQueryLog());
        DB::connection()->disableQueryLog();

        // The N+1 guard: identical, constant query count (settings+records+holidays+leave).
        $this->assertSame($small, $large);
        $this->assertLessThanOrEqual(4, $large);
    }

    public function test_resolver_performs_no_writes(): void
    {
        $this->row($this->emp1, '2026-09-07', 'present');
        $this->approvedLeave($this->emp1, '2026-09-10', '2026-09-10');
        $this->holiday('2026-09-09');
        $now = CarbonImmutable::parse('2026-09-20 12:00', 'UTC');

        $recBefore   = AttendanceRecord::count();
        $leaveBefore = LeaveRequest::count();

        $this->svc->resolveRange($this->agency->id, [$this->emp1->id, $this->emp2->id], '2026-09-01', '2026-09-30', $now);

        $this->assertSame($recBefore, AttendanceRecord::count(), 'resolveRange must not create/delete records');
        $this->assertSame($leaveBefore, LeaveRequest::count(), 'resolveRange must not touch leave_requests');
    }

    public function test_empty_inputs_are_handled_gracefully(): void
    {
        $now = CarbonImmutable::parse('2026-09-20 12:00', 'UTC');
        $this->assertSame([], $this->svc->resolveRange($this->agency->id, [], '2026-09-07', '2026-09-13', $now));
        // Inverted range → empty day maps, no error.
        $this->assertSame(
            [$this->emp1->id => []],
            $this->svc->resolveRange($this->agency->id, [$this->emp1->id], '2026-09-13', '2026-09-07', $now)
        );
    }
}
