<?php

namespace Tests\Feature\Attendance;

use App\Models\Agency;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AttendanceCalculator;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * H3c — Leave Requests. Covers the state machine (double-decision + illegal
 * transition), cross-tenant isolation, overlap/day-count rules, and the
 * derivation cross-wire (approvedDatesFor → deriveDayStatus → on_leave, and
 * revert on reject/cancel/delete). Weekend = Fri(5)+Sat(6); tz UTC for simple
 * derivation. Dates: 2026-09-07 Mon … 09-13 Sun (11 Fri, 12 Sat are weekend).
 */
class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $admin;
    private User $staffUser;
    private Employee $staffEmployee;   // linked to $staffUser
    private Employee $otherEmployee;   // login-less (admin-on-behalf target)
    private LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $this->agency = Agency::create([
            'name' => 'Test Agency', 'slug' => 'test-agency', 'status' => 'active',
            'license_number' => 'LIC-TEST-0001',
        ]);

        $this->admin = $this->makeUser('admin@test.sa', 'agency_admin');
        $this->staffUser = $this->makeUser('staff@test.sa', 'agency_staff');
        $this->staffUser->givePermissionTo('access_attendance');

        $this->staffEmployee = Employee::create([
            'agency_id' => $this->agency->id, 'user_id' => $this->staffUser->id,
            'name' => 'Staff Person', 'status' => 'active',
        ]);
        $this->otherEmployee = Employee::create([
            'agency_id' => $this->agency->id, 'name' => 'Login-less Worker', 'status' => 'active',
        ]);

        $this->type = LeaveType::create([
            'agency_id' => $this->agency->id, 'name' => 'Annual', 'is_paid' => true,
        ]);

        AttendanceSetting::create([
            'agency_id' => $this->agency->id,
            'office_start' => '09:00', 'office_end' => '17:00', 'grace_minutes' => 0,
            'timezone' => 'UTC', 'weekend_days' => [5, 6],
        ]);
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::create([
            'name' => $email, 'email' => $email, 'password' => Hash::make('secret1234'),
            'agency_id' => $this->agency->id, 'is_super_admin' => false, 'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function submit(User $as, array $overrides = [])
    {
        return $this->actingAs($as)->post(route('attendance.leave-requests.store'), array_merge([
            'leave_type_id' => $this->type->id,
            'start_date'    => '2026-09-07',
            'end_date'      => '2026-09-08',
        ], $overrides));
    }

    // ── Submit + day-count ────────────────────────────────────────────────────

    public function test_staff_can_submit_pending_request(): void
    {
        $this->submit($this->staffUser)->assertRedirect();

        $lr = LeaveRequest::first();
        $this->assertNotNull($lr);
        $this->assertSame('pending', $lr->status);
        $this->assertSame($this->staffEmployee->id, $lr->employee_id);
        $this->assertSame(2, $lr->days); // Mon+Tue, no weekend/holiday
    }

    public function test_admin_on_behalf_submit_defaults_pending(): void
    {
        $this->actingAs($this->admin)->post(route('attendance.leave-requests.store'), [
            'employee_id' => $this->otherEmployee->id,
            'leave_type_id' => $this->type->id,
            'start_date' => '2026-09-07', 'end_date' => '2026-09-08',
        ])->assertRedirect();

        $lr = LeaveRequest::first();
        $this->assertSame('pending', $lr->status);
        $this->assertSame($this->otherEmployee->id, $lr->employee_id);
    }

    public function test_day_count_excludes_weekends_and_holidays(): void
    {
        Holiday::create(['agency_id' => $this->agency->id, 'title' => 'Mid-week', 'holiday_date' => '2026-09-09']);

        // 09-07 Mon … 09-13 Sun = 7 days; minus Fri(11)+Sat(12) minus holiday(09) = 4.
        $this->submit($this->staffUser, ['start_date' => '2026-09-07', 'end_date' => '2026-09-13'])->assertRedirect();

        $this->assertSame(4, LeaveRequest::first()->days);
    }

    public function test_weekend_only_range_is_blocked(): void
    {
        // 09-11 Fri … 09-12 Sat = zero working days.
        $this->submit($this->staffUser, ['start_date' => '2026-09-11', 'end_date' => '2026-09-12'])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, LeaveRequest::count());
    }

    // ── Overlap / re-request ──────────────────────────────────────────────────

    public function test_overlap_with_pending_or_approved_is_blocked(): void
    {
        $this->submit($this->staffUser)->assertRedirect(); // pending 07-08
        // Overlaps on the 08th.
        $this->submit($this->staffUser, ['start_date' => '2026-09-08', 'end_date' => '2026-09-09'])
            ->assertSessionHas('error');

        $this->assertSame(1, LeaveRequest::count());
    }

    public function test_rejected_or_cancelled_allows_resubmit(): void
    {
        $this->submit($this->staffUser)->assertRedirect();
        $lr = LeaveRequest::first();
        $lr->update(['status' => 'rejected']); // simulate a decided-away request

        $this->submit($this->staffUser)->assertRedirect(); // same dates now allowed
        $this->assertSame(2, LeaveRequest::count());
    }

    // ── State machine: decisions ──────────────────────────────────────────────

    public function test_admin_can_approve_pending(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();

        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.approve', $lr))->assertRedirect();

        $lr->refresh();
        $this->assertSame('approved', $lr->status);
        $this->assertSame($this->admin->id, $lr->decided_by);
        $this->assertNotNull($lr->decided_at);
    }

    public function test_admin_can_reject_with_note(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();

        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.reject', $lr), [
            'decision_note' => 'Peak season',
        ])->assertRedirect();

        $lr->refresh();
        $this->assertSame('rejected', $lr->status);
        $this->assertSame('Peak season', $lr->decision_note);
    }

    public function test_double_decision_is_blocked(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();

        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.approve', $lr))->assertRedirect();
        // Second decision on a now-terminal request → 422, status unchanged.
        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.approve', $lr))->assertStatus(422);
        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.reject', $lr))->assertStatus(422);

        $this->assertSame('approved', $lr->refresh()->status);
    }

    // ── Cancel rules ──────────────────────────────────────────────────────────

    public function test_staff_can_cancel_own_pending(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();

        $this->actingAs($this->staffUser)->patch(route('attendance.leave-requests.cancel', $lr))->assertRedirect();
        $this->assertSame('cancelled', $lr->refresh()->status);
    }

    public function test_staff_cannot_cancel_an_approved_request(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();
        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.approve', $lr));

        $this->actingAs($this->staffUser)->patch(route('attendance.leave-requests.cancel', $lr))->assertStatus(422);
        $this->assertSame('approved', $lr->refresh()->status);
    }

    // ── Cross-tenant isolation ────────────────────────────────────────────────

    public function test_other_agency_admin_cannot_decide_or_delete(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();

        // A whole second tenant.
        $otherAgency = Agency::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active', 'license_number' => 'LIC-TEST-0002']);
        $intruder = User::create([
            'name' => 'x@other.sa', 'email' => 'x@other.sa', 'password' => Hash::make('secret1234'),
            'agency_id' => $otherAgency->id, 'is_super_admin' => false, 'is_active' => true,
        ]);
        $intruder->assignRole('agency_admin');

        $this->actingAs($intruder)->patch(route('attendance.leave-requests.approve', $lr))->assertStatus(403);
        $this->actingAs($intruder)->delete(route('attendance.leave-requests.destroy', $lr))->assertStatus(403);
        $this->assertSame('pending', $lr->refresh()->status);
    }

    public function test_staff_cannot_grant_themselves_admin_powers(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();

        // Staff hitting the admin-only approve endpoint → 403 (authorizeAdmin).
        $this->actingAs($this->staffUser)->patch(route('attendance.leave-requests.approve', $lr))->assertStatus(403);
        $this->assertSame('pending', $lr->refresh()->status);
    }

    // ── The derivation cross-wire (heavy focus) ───────────────────────────────

    public function test_approved_leave_flips_derive_to_on_leave_and_reverts_on_removal(): void
    {
        $calc = new AttendanceCalculator();
        $now  = CarbonImmutable::parse('2026-09-20 12:00', 'UTC'); // both dates are in the past
        $probe = '2026-09-07'; // Monday, a working day

        $deriveProbe = function () use ($calc, $now, $probe) {
            $approved = LeaveRequest::approvedDatesFor($this->agency->id, $this->staffEmployee->id, '2026-09-01', '2026-09-30');
            return $calc->deriveDayStatus(
                $probe, null,
                isWeekend: false, isHoliday: false,
                isOnLeave: in_array($probe, $approved, true),
                nowUtc: $now, timezone: 'UTC', autoAbsentTime: null,
            );
        };

        // Pending → not yet on leave: probe day derives absent.
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();
        $this->assertSame('absent', $deriveProbe(), 'pending leave must NOT flip derivation');

        // Approved → probe day flips to on_leave; approvedDatesFor returns both days.
        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.approve', $lr));
        $this->assertEqualsCanonicalizing(
            ['2026-09-07', '2026-09-08'],
            LeaveRequest::approvedDatesFor($this->agency->id, $this->staffEmployee->id, '2026-09-01', '2026-09-30'),
        );
        $this->assertSame('on_leave', $deriveProbe(), 'approved leave must flip derivation to on_leave');

        // Admin revoke (hard delete) → derivation reverts to absent.
        $this->actingAs($this->admin)->delete(route('attendance.leave-requests.destroy', $lr))->assertRedirect();
        $this->assertSame('absent', $deriveProbe(), 'removing an approved leave must revert derivation');
    }

    public function test_rejected_leave_does_not_flip_derivation(): void
    {
        $this->submit($this->staffUser);
        $lr = LeaveRequest::first();
        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.reject', $lr));

        $this->assertSame(
            [],
            LeaveRequest::approvedDatesFor($this->agency->id, $this->staffEmployee->id, '2026-09-01', '2026-09-30'),
            'a rejected request must contribute no on_leave dates',
        );
    }

    public function test_approved_dates_are_clipped_to_the_requested_window(): void
    {
        $this->submit($this->staffUser, ['start_date' => '2026-09-07', 'end_date' => '2026-09-13']);
        $lr = LeaveRequest::first();
        $this->actingAs($this->admin)->patch(route('attendance.leave-requests.approve', $lr));

        // Window intersects only 09-07..09-09 of the approved 09-07..09-13 range.
        $this->assertEqualsCanonicalizing(
            ['2026-09-07', '2026-09-08', '2026-09-09'],
            LeaveRequest::approvedDatesFor($this->agency->id, $this->staffEmployee->id, '2026-09-05', '2026-09-09'),
        );
    }
}
