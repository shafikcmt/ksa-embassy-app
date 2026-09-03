<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceCalculator;
use App\Services\AttendanceReportService;
use App\Services\PdfGeneratorService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance module — configuration phase (M4).
 *
 * A single tabbed screen (Dashboard/Employees/Shifts/Settings/Leave/Holidays/
 * Reports). This controller powers the config-like tabs only: Settings, Shifts,
 * Holidays and Leave Types. The Dashboard/Employees/Reports/leave-requests tabs
 * are placeholders until the attendance transactional core lands.
 *
 * Tenancy: every read is scoped to the caller's agency_id and every write
 * re-checks the target row belongs to that agency (aborts 403 otherwise), the
 * same guard style as StaffController. Route-level access is enforced by the
 * page-access:attendance middleware.
 */
class AttendanceController extends Controller
{
    /** Tabs the shell renders; used to validate the ?tab= deep link. */
    private const TABS = ['dashboard', 'employees', 'shifts', 'settings', 'leave', 'holidays', 'reports'];

    public function index(Request $request, AttendanceCalculator $calc, AttendanceReportService $reports)
    {
        $agencyId = auth()->user()->agency_id;

        $tab = in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : 'dashboard';

        $settings   = AttendanceSetting::forAgency($agencyId)->first() ?? new AttendanceSetting();
        $shifts     = Shift::forAgency($agencyId)->orderByDesc('is_default')->orderBy('name')->get();
        $holidays   = Holiday::forAgency($agencyId)->orderBy('holiday_date')->get();
        $leaveTypes = LeaveType::forAgency($agencyId)->orderBy('name')->get();

        $employees     = Employee::forAgency($agencyId)
            ->with(['user:id,name,email', 'shift:id,name'])
            ->orderBy('name')->get();
        $linkableUsers = $this->linkableUsers($agencyId); // agency_staff logins for the link <select>

        // Self check-in/out card: the acting user's linked active employee + today's row.
        $selfEmployee = Employee::forAgency($agencyId)->active()->where('user_id', auth()->id())->first();
        $selfToday    = null;
        if ($selfEmployee) {
            $tz    = $settings->timezone ?: 'UTC';
            $today = $calc->workDateFor(now(), $tz);
            $selfToday = AttendanceRecord::forAgency($agencyId)
                ->where('employee_id', $selfEmployee->id)->forDate($today)->first();
        }

        // Admin per-employee Records modal feed (recent first; filtered client-side by employee).
        $records = $this->userIsAdmin()
            ? AttendanceRecord::forAgency($agencyId)->with('employee:id,name')
                ->orderByDesc('work_date')->orderByDesc('id')->limit(500)->get()
            : collect();

        // Leave Requests (H3c): admin sees the whole agency queue; a linked staff
        // member sees only their own. $selfEmployee (resolved above) is the staff lens.
        $leaveRequests = LeaveRequest::forAgency($agencyId)
            ->with(['employee:id,name', 'leaveType:id,name,color', 'decidedBy:id,name'])
            ->when(! $this->userIsAdmin(), function ($q) use ($selfEmployee) {
                // Non-admin: own requests only (empty set if not a linked employee).
                $q->where('employee_id', $selfEmployee?->id ?? 0);
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('start_date')->orderByDesc('id')
            ->limit(500)->get();

        // ── H3d Dashboard + Reports (READ-ONLY, resolver-backed) ──────────────────
        // Scope: an admin sees the whole active workforce; a staff member sees only
        // their own linked employee (empty set if they aren't linked).
        $isAdmin  = $this->userIsAdmin();
        $activeIds = $employees->where('status', 'active')->pluck('id')->map(fn ($v) => (int) $v)->all();
        $scopeIds  = $isAdmin ? $activeIds : ($selfEmployee ? [$selfEmployee->id] : []);
        $empNames  = $employees->pluck('name', 'id');

        $tz     = $settings->timezone ?: 'UTC';
        $nowUtc = CarbonImmutable::now('UTC');
        $today  = $calc->workDateFor($nowUtc, $tz);

        $dashboard = $this->buildDashboard($agencyId, $scopeIds, $today, $nowUtc, $reports);
        $report    = $this->buildReport($request, $agencyId, $isAdmin, $selfEmployee?->id, $today, $nowUtc, $reports, $empNames);

        return view('agency.attendance.index', [
            'tab'           => $tab,
            'settings'      => $settings,
            'shifts'        => $shifts,
            'holidays'      => $holidays,
            'leaveTypes'    => $leaveTypes,
            'employees'     => $employees,
            'linkableUsers' => $linkableUsers,
            'selfEmployee'  => $selfEmployee,
            'selfToday'     => $selfToday,
            'records'       => $records,
            'leaveRequests' => $leaveRequests,
            'dashboard'     => $dashboard,
            'report'        => $report,
            'isReportAdmin' => $isAdmin,
            'recordStatuses'=> AttendanceRecord::STATUSES,
            'leaveStatuses' => LeaveRequest::STATUSES,
            'timezones'     => AttendanceSetting::TIMEZONES,
            'weekdays'      => AttendanceSetting::WEEKDAYS,
        ]);
    }

    // ── Settings ────────────────────────────────────────────────────────────

    public function updateSettings(Request $request)
    {
        $agencyId = auth()->user()->agency_id;

        $validated = $request->validate([
            'office_start'           => ['required', 'date_format:H:i'],
            'office_end'             => ['required', 'date_format:H:i'],
            'grace_minutes'          => ['required', 'integer', 'min:0', 'max:600'],
            'auto_absent_time'       => ['nullable', 'date_format:H:i'],
            'half_day_after_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'overtime_after_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'timezone'               => ['required', Rule::in(array_keys(AttendanceSetting::TIMEZONES))],
            'weekend_days'           => ['array'],
            'weekend_days.*'         => ['integer', Rule::in(array_keys(AttendanceSetting::WEEKDAYS))],
            'alert_on_late'          => ['nullable', 'boolean'],
            'alert_on_absent'        => ['nullable', 'boolean'],
            'alert_on_checkin'       => ['nullable', 'boolean'],
            'alert_on_checkout'      => ['nullable', 'boolean'],
        ]);

        AttendanceSetting::updateOrCreate(
            ['agency_id' => $agencyId],
            [
                'office_start'           => $validated['office_start'],
                'office_end'             => $validated['office_end'],
                'grace_minutes'          => $validated['grace_minutes'],
                'auto_absent_time'       => $validated['auto_absent_time'] ?? null,
                'half_day_after_minutes' => $validated['half_day_after_minutes'] ?? null,
                'overtime_after_minutes' => $validated['overtime_after_minutes'] ?? null,
                'timezone'               => $validated['timezone'],
                'weekend_days'           => array_values($validated['weekend_days'] ?? []),
                'alert_on_late'          => $request->boolean('alert_on_late'),
                'alert_on_absent'        => $request->boolean('alert_on_absent'),
                'alert_on_checkin'       => $request->boolean('alert_on_checkin'),
                'alert_on_checkout'      => $request->boolean('alert_on_checkout'),
            ]
        );

        return redirect()->route('attendance.index', ['tab' => 'settings'])
            ->with('success', 'Attendance settings saved.');
    }

    // ── Shifts ──────────────────────────────────────────────────────────────

    public function storeShift(Request $request)
    {
        $validated = $this->validateShift($request);

        $shift = Shift::create($validated + ['agency_id' => auth()->user()->agency_id]);

        if ($shift->is_default) {
            $this->clearOtherDefaults($shift);
        }

        return redirect()->route('attendance.index', ['tab' => 'shifts'])
            ->with('success', 'Shift "' . $shift->name . '" created.');
    }

    public function updateShift(Request $request, Shift $shift)
    {
        $this->authorizeAgency($shift);

        $validated = $this->validateShift($request);
        $shift->update($validated);

        if ($shift->is_default) {
            $this->clearOtherDefaults($shift);
        }

        return redirect()->route('attendance.index', ['tab' => 'shifts'])
            ->with('success', 'Shift "' . $shift->name . '" updated.');
    }

    public function destroyShift(Shift $shift)
    {
        $this->authorizeAgency($shift);

        $name = $shift->name;
        $shift->delete(); // soft delete

        return redirect()->route('attendance.index', ['tab' => 'shifts'])
            ->with('success', "Shift \"$name\" deleted.");
    }

    private function validateShift(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:80'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i'],
            'is_default' => ['nullable', 'boolean'],
        ]) + ['is_default' => $request->boolean('is_default')];
    }

    /** Ensure exactly one default shift per agency. */
    private function clearOtherDefaults(Shift $shift): void
    {
        Shift::forAgency($shift->agency_id)
            ->where('id', '!=', $shift->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    // ── Holidays ────────────────────────────────────────────────────────────

    public function storeHoliday(Request $request)
    {
        $validated = $this->validateHoliday($request);

        Holiday::create($validated + ['agency_id' => auth()->user()->agency_id]);

        return redirect()->route('attendance.index', ['tab' => 'holidays'])
            ->with('success', 'Holiday added.');
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        $this->authorizeAgency($holiday);

        $holiday->update($this->validateHoliday($request));

        return redirect()->route('attendance.index', ['tab' => 'holidays'])
            ->with('success', 'Holiday updated.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
        $this->authorizeAgency($holiday);

        $holiday->delete();

        return redirect()->route('attendance.index', ['tab' => 'holidays'])
            ->with('success', 'Holiday deleted.');
    }

    private function validateHoliday(Request $request): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'max:120'],
            'holiday_date' => ['required', 'date'],
        ]);
    }

    // ── Leave Types ─────────────────────────────────────────────────────────

    public function storeLeaveType(Request $request)
    {
        $validated = $this->validateLeaveType($request);

        LeaveType::create($validated + ['agency_id' => auth()->user()->agency_id]);

        return redirect()->route('attendance.index', ['tab' => 'leave'])
            ->with('success', 'Leave type created.');
    }

    public function updateLeaveType(Request $request, LeaveType $leaveType)
    {
        $this->authorizeAgency($leaveType);

        $leaveType->update($this->validateLeaveType($request));

        return redirect()->route('attendance.index', ['tab' => 'leave'])
            ->with('success', 'Leave type updated.');
    }

    public function destroyLeaveType(LeaveType $leaveType)
    {
        $this->authorizeAgency($leaveType);

        $leaveType->delete(); // soft delete

        return redirect()->route('attendance.index', ['tab' => 'leave'])
            ->with('success', 'Leave type deleted.');
    }

    private function validateLeaveType(Request $request): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:80'],
            'is_paid'      => ['nullable', 'boolean'],
            'default_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'color'        => ['nullable', 'string', 'max:20'],
        ]) + ['is_paid' => $request->boolean('is_paid')];
    }

    // ── Employees (H3a) ───────────────────────────────────────────────────────
    //
    // NOTE: unlike the M4 config tabs above (shifts/holidays/leave-types, which any
    // staff with access_attendance may edit), employee management is ADMIN-ONLY —
    // authorizeAdmin() guards all three writes, mirroring StaffController. The
    // Employees list itself stays visible to any attendance-access user (read-only).

    public function storeEmployee(Request $request)
    {
        $this->authorizeAdmin();

        $agencyId = auth()->user()->agency_id;
        $data     = $this->validateEmployee($request, $agencyId);

        Employee::create($data + [
            'agency_id'  => $agencyId,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('attendance.index', ['tab' => 'employees'])
            ->with('success', 'Employee "' . $data['name'] . '" added.');
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $this->authorizeAdmin();
        $this->authorizeAgency($employee);

        $data = $this->validateEmployee($request, $employee->agency_id, $employee);

        $employee->update($data + ['updated_by' => auth()->id()]);

        return redirect()->route('attendance.index', ['tab' => 'employees'])
            ->with('success', 'Employee "' . $employee->name . '" updated.');
    }

    public function destroyEmployee(Employee $employee)
    {
        $this->authorizeAdmin();
        $this->authorizeAgency($employee);

        $name = $employee->name;

        // Free the linked login BEFORE soft-deleting so the unique (agency_id,
        // user_id) slot is released and that user can be linked to a new employee.
        $employee->update(['user_id' => null, 'updated_by' => auth()->id()]);
        $employee->delete(); // soft delete

        return redirect()->route('attendance.index', ['tab' => 'employees'])
            ->with('success', "Employee \"$name\" retired.");
    }

    /**
     * Validate an employee payload. Cross-tenant guards: shift_id and user_id
     * exists-rules are scoped to $agencyId, so a guessed id from another tenant
     * fails. unique(agency_id,user_id) (ignoring soft-deleted + the current row)
     * enforces one-employee-per-login. The exists-rule can't assert a Spatie
     * role, so a linked user_id is additionally checked to be an agency_staff
     * (not admin/super-admin) via linkableUserIds().
     */
    private function validateEmployee(Request $request, int $agencyId, ?Employee $employee = null): array
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'phone'       => ['nullable', 'string', 'max:40'],
            'email'       => ['nullable', 'email', 'max:255'],
            'join_date'   => ['nullable', 'date'],
            'status'      => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'shift_id'    => [
                'nullable', 'integer',
                Rule::exists('shifts', 'id')->where('agency_id', $agencyId)->whereNull('deleted_at'),
            ],
            'user_id'     => [
                'nullable', 'integer',
                // Must be a linkable agency_staff login in THIS agency (role-checked below too).
                Rule::in($this->linkableUserIds($agencyId)),
                // One employee per login (ignore soft-deleted rows + the row being edited).
                Rule::unique('employees', 'user_id')
                    ->where('agency_id', $agencyId)
                    ->whereNull('deleted_at')
                    ->ignore($employee?->id),
            ],
        ], [
            'user_id.in' => 'The selected login is not a valid staff account for this agency.',
        ]);

        // Normalise blanks → null for the optional columns.
        foreach (['designation', 'phone', 'email', 'join_date', 'shift_id', 'user_id'] as $f) {
            if (($validated[$f] ?? '') === '') {
                $validated[$f] = null;
            }
        }

        return $validated;
    }

    /** agency_staff logins in this agency, for the link <select>. */
    private function linkableUsers(int $agencyId)
    {
        return User::where('agency_id', $agencyId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'agency_staff'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /** The id set the user_id link is allowed to reference (agency_staff only). */
    private function linkableUserIds(int $agencyId): array
    {
        return $this->linkableUsers($agencyId)->pluck('id')->all();
    }

    // ── Attendance records (H3b) ──────────────────────────────────────────────
    //
    // Self check-in/out is available to any logged-in user linked to an ACTIVE
    // employee (own record only) — NOT admin-gated and NOT behind active-
    // subscription (daily work flow must not depend on billing). Admin manual
    // records (create/edit/hard-delete, any employee, any date) are admin-only.
    // status + minute columns are always written FROM AttendanceCalculator, never
    // from raw input. attendance_records is a truthful event log: absent/weekend/
    // holiday are derived at read time and never stored.

    /** Self check-in / check-out toggle for the acting user's linked employee. */
    public function check(Request $request, AttendanceCalculator $calc)
    {
        $agencyId = auth()->user()->agency_id;

        $employee = Employee::forAgency($agencyId)->active()->where('user_id', auth()->id())->first();
        abort_unless($employee, 403); // no active linked employee → cannot self-check-in

        $settings = AttendanceSetting::forAgency($agencyId)->first();
        if (! $settings) {
            return back()->with('error', 'Attendance settings are not configured yet — ask your admin to set them.');
        }

        $tz  = $settings->timezone ?: 'UTC';
        $now = CarbonImmutable::now('UTC');

        // A still-open row (checked in, not out) within a sane window handles
        // overnight shifts where the check-out lands on the next calendar day.
        $open = AttendanceRecord::forAgency($agencyId)
            ->where('employee_id', $employee->id)->open()
            ->where('check_in_at', '>=', $now->copy()->subHours(18))
            ->orderByDesc('check_in_at')->first();

        if ($open) {
            $exp = $calc->resolveExpectation($employee, $open->work_date->format('Y-m-d'), $settings);
            $res = $calc->evaluate($open->check_in_at, $now, $exp);
            $open->update([
                'check_out_at'     => $now,
                'status'           => $res->status,
                'late_minutes'     => $res->lateMinutes,
                'overtime_minutes' => $res->overtimeMinutes,
                'worked_minutes'   => $res->workedMinutes,
                'updated_by'       => auth()->id(),
            ]);

            return back()->with('success', 'Checked out. Worked ' . $this->hoursLabel($res->workedMinutes) . '.');
        }

        $today   = $calc->workDateFor($now, $tz);
        $existing = AttendanceRecord::forAgency($agencyId)
            ->where('employee_id', $employee->id)->forDate($today)->first();

        if ($existing) {
            return back()->with('error', 'You have already completed attendance for today.');
        }

        $exp = $calc->resolveExpectation($employee, $today, $settings);
        $res = $calc->evaluate($now, null, $exp);
        AttendanceRecord::create([
            'agency_id'        => $agencyId,
            'employee_id'      => $employee->id,
            'shift_id'         => $employee->shift_id,
            'work_date'        => $today,
            'check_in_at'      => $now,
            'status'           => $res->status,
            'late_minutes'     => $res->lateMinutes,
            'source'           => 'self',
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        $flash = $res->status === 'present' ? 'Checked in. Have a great day!' : 'Checked in (late by ' . $res->lateMinutes . 'm).';

        return back()->with('success', $flash);
    }

    public function storeRecord(Request $request, AttendanceCalculator $calc)
    {
        $this->authorizeAdmin();

        $agencyId = auth()->user()->agency_id;
        $data     = $this->validateRecord($request, $agencyId);
        $employee = Employee::forAgency($agencyId)->findOrFail($data['employee_id']);

        AttendanceRecord::create(
            $this->recordAttributes($data, $employee, $calc, $agencyId) + [
                'agency_id'  => $agencyId,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        return redirect()->route('attendance.index', ['tab' => 'employees'])
            ->with('success', 'Attendance record saved.');
    }

    public function updateRecord(Request $request, AttendanceRecord $record, AttendanceCalculator $calc)
    {
        $this->authorizeAdmin();
        $this->authorizeAgency($record);

        $agencyId = auth()->user()->agency_id;
        $data     = $this->validateRecord($request, $agencyId, $record);
        $employee = Employee::forAgency($agencyId)->findOrFail($data['employee_id']);

        $record->update(
            $this->recordAttributes($data, $employee, $calc, $agencyId) + ['updated_by' => auth()->id()]
        );

        return redirect()->route('attendance.index', ['tab' => 'employees'])
            ->with('success', 'Attendance record updated.');
    }

    public function destroyRecord(AttendanceRecord $record)
    {
        $this->authorizeAdmin();
        $this->authorizeAgency($record);

        $record->delete(); // hard delete → the day reverts to its derived status

        return redirect()->route('attendance.index', ['tab' => 'employees'])
            ->with('success', 'Attendance record deleted.');
    }

    /**
     * Validate an admin record. Two modes:
     *  - Times mode  (check_in_time present): status is COMPUTED by the calculator.
     *  - Marker mode (no check_in_time): admin sets a status directly (+ optional note).
     * employee_id is agency-scoped; unique (agency, employee, work_date) enforced.
     */
    private function validateRecord(Request $request, int $agencyId, ?AttendanceRecord $record = null): array
    {
        return $request->validate([
            'employee_id'    => ['required', 'integer', Rule::exists('employees', 'id')->where('agency_id', $agencyId)->whereNull('deleted_at')],
            'work_date'      => [
                'required', 'date',
                Rule::unique('attendance_records', 'work_date')
                    ->where(fn ($q) => $q->where('agency_id', $agencyId)->where('employee_id', $request->input('employee_id')))
                    ->ignore($record?->id),
            ],
            'check_in_time'  => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i', 'required_with:check_in_time'],
            'status'         => ['required_without:check_in_time', 'nullable', Rule::in(AttendanceRecord::MANUAL_STATUSES)],
            'note'           => ['nullable', 'string', 'max:255'],
        ], [
            'work_date.unique' => 'This employee already has a record for that date — edit it instead.',
        ]);
    }

    /** Build the persisted attribute set for an admin record from validated input. */
    private function recordAttributes(array $data, Employee $employee, AttendanceCalculator $calc, int $agencyId): array
    {
        $settings = $this->settingsOrDefault($agencyId);
        $tz       = $settings->timezone ?: 'UTC';
        $date     = CarbonImmutable::parse($data['work_date'])->format('Y-m-d');

        // Marker mode: no check-in time → store the admin's explicit status, no calc.
        if (empty($data['check_in_time'])) {
            return [
                'employee_id'      => $employee->id,
                'shift_id'         => $employee->shift_id,
                'work_date'        => $date,
                'check_in_at'      => null,
                'check_out_at'     => null,
                'status'           => $data['status'],
                'late_minutes'     => 0,
                'overtime_minutes' => 0,
                'worked_minutes'   => 0,
                'source'           => 'admin',
                'note'             => $data['note'] ?? null,
            ];
        }

        // Times mode: build UTC instants (overnight-safe) and let the calculator judge.
        $in  = CarbonImmutable::parse("{$date} {$data['check_in_time']}", $tz);
        $out = null;
        if (! empty($data['check_out_time'])) {
            $out = CarbonImmutable::parse("{$date} {$data['check_out_time']}", $tz);
            if ($out->lessThanOrEqualTo($in)) {
                $out = $out->addDay(); // overnight
            }
        }

        $exp = $calc->resolveExpectation($employee, $date, $settings);
        $res = $calc->evaluate($in->utc(), $out?->utc(), $exp);

        return [
            'employee_id'      => $employee->id,
            'shift_id'         => $employee->shift_id,
            'work_date'        => $date,
            'check_in_at'      => $in->utc(),
            'check_out_at'     => $out?->utc(),
            'status'           => $res->status,
            'late_minutes'     => $res->lateMinutes,
            'overtime_minutes' => $res->overtimeMinutes,
            'worked_minutes'   => $res->workedMinutes,
            'source'           => 'admin',
            'note'             => $data['note'] ?? null,
        ];
    }

    /** Existing settings, or a transient default (UTC / 09:00–17:00 / grace 0) so check-in never hard-fails. */
    private function settingsOrDefault(int $agencyId): AttendanceSetting
    {
        return AttendanceSetting::forAgency($agencyId)->first()
            ?? new AttendanceSetting([
                'timezone' => 'UTC', 'office_start' => '09:00', 'office_end' => '17:00', 'grace_minutes' => 0,
            ]);
    }

    private function hoursLabel(int $minutes): string
    {
        return intdiv($minutes, 60) . 'h ' . ($minutes % 60) . 'm';
    }

    // ── Leave requests (H3c) ──────────────────────────────────────────────────
    //
    // Staff submit + cancel-own-pending are open to any login linked to an ACTIVE
    // employee (like self check-in — daily flow, not billing-gated). approve/reject/
    // delete are admin-only. State machine: pending is the ONLY non-terminal state;
    // every decision asserts status === 'pending' first (one guard covers both the
    // double-decision and illegal-transition cases). An admin may also submit on
    // behalf of any employee — it still lands as pending and is approved separately.
    // Only APPROVED requests flip derivation (via LeaveRequest::approvedDatesFor);
    // reverting an approval is an admin HARD delete, mirroring attendance_records.

    public function storeLeaveRequest(Request $request, AttendanceCalculator $calc)
    {
        $agencyId = auth()->user()->agency_id;
        $isAdmin  = $this->userIsAdmin();

        // Resolve the target employee. Admin may file for anyone in the agency; a
        // non-admin may only file for their OWN active linked employee.
        if ($isAdmin) {
            $rules = [
                'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('agency_id', $agencyId)->whereNull('deleted_at')],
            ];
        } else {
            $self = Employee::forAgency($agencyId)->active()->where('user_id', auth()->id())->first();
            abort_unless($self, 403); // no active linked employee → cannot submit
            $rules = [];
        }

        $rules += [
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('agency_id', $agencyId)->whereNull('deleted_at')],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['nullable', 'string', 'max:255'],
        ];
        $validated = $request->validate($rules);

        $employee = $isAdmin
            ? Employee::forAgency($agencyId)->findOrFail($validated['employee_id'])
            : $self;

        $from = CarbonImmutable::parse($validated['start_date'])->format('Y-m-d');
        $to   = CarbonImmutable::parse($validated['end_date'])->format('Y-m-d');

        // Overlap: a live claim (pending/approved) for this employee blocks a new one.
        if (LeaveRequest::forAgency($agencyId)->overlapping($employee->id, $from, $to)->exists()) {
            return back()->with('error', 'This employee already has a pending or approved leave overlapping those dates.');
        }

        // Working days = span minus weekends minus holidays. A range with zero
        // working days (e.g. a lone weekend) is rejected — nothing to take leave on.
        $days = $this->workingDaysBetween($from, $to, $agencyId, $calc);
        if ($days === 0) {
            return back()->with('error', 'The selected range has no working days (only weekends/holidays).');
        }

        LeaveRequest::create([
            'agency_id'     => $agencyId,
            'employee_id'   => $employee->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date'    => $from,
            'end_date'      => $to,
            'days'          => $days,
            'status'        => 'pending',
            'reason'        => $validated['reason'] ?? null,
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        return redirect()->route('attendance.index', ['tab' => 'leave'])
            ->with('success', 'Leave request submitted.');
    }

    /** Staff cancel — own request, pending only. */
    public function cancelLeaveRequest(LeaveRequest $leaveRequest)
    {
        $this->authorizeAgency($leaveRequest);

        // Ownership: the caller's linked employee must be this request's employee
        // (unless they're an admin, who may cancel any pending request in-agency).
        if (! $this->userIsAdmin()) {
            $self = Employee::forAgency($leaveRequest->agency_id)->where('user_id', auth()->id())->first();
            abort_unless($self && $self->id === $leaveRequest->employee_id, 403);
        }

        abort_unless($leaveRequest->isPending(), 422); // cannot cancel a decided request

        $leaveRequest->update(['status' => 'cancelled', 'updated_by' => auth()->id()]);

        return redirect()->route('attendance.index', ['tab' => 'leave'])
            ->with('success', 'Leave request cancelled.');
    }

    public function approveLeaveRequest(Request $request, LeaveRequest $leaveRequest)
    {
        return $this->decideLeaveRequest($request, $leaveRequest, 'approved', 'Leave request approved.');
    }

    public function rejectLeaveRequest(Request $request, LeaveRequest $leaveRequest)
    {
        return $this->decideLeaveRequest($request, $leaveRequest, 'rejected', 'Leave request rejected.');
    }

    /** Admin revoke: hard-delete reverts any covered days back to their derived status. */
    public function destroyLeaveRequest(LeaveRequest $leaveRequest)
    {
        $this->authorizeAdmin();
        $this->authorizeAgency($leaveRequest);

        $leaveRequest->delete(); // hard delete → derivation stops seeing these dates

        return redirect()->route('attendance.index', ['tab' => 'leave'])
            ->with('success', 'Leave request removed.');
    }

    /** Shared approve/reject transition — admin-only, pending-only (the state guard). */
    private function decideLeaveRequest(Request $request, LeaveRequest $leaveRequest, string $status, string $flash)
    {
        $this->authorizeAdmin();
        $this->authorizeAgency($leaveRequest);

        // The single guard for BOTH double-decision and illegal-transition: only a
        // pending request can be decided. Anything terminal is left untouched.
        abort_unless($leaveRequest->isPending(), 422);

        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:255'],
        ]);

        $leaveRequest->update([
            'status'        => $status,
            'decision_note' => $validated['decision_note'] ?? null,
            'decided_by'    => auth()->id(),
            'decided_at'    => now(),
            'updated_by'    => auth()->id(),
        ]);

        return redirect()->route('attendance.index', ['tab' => 'leave'])->with('success', $flash);
    }

    /**
     * Working days in [$from, $to] inclusive = calendar span minus agency weekends
     * minus holidays. Reuses AttendanceCalculator::isWeekend() + the Holiday table;
     * NOT a new calculation category, just a filtered day loop.
     */
    private function workingDaysBetween(string $from, string $to, int $agencyId, AttendanceCalculator $calc): int
    {
        $settings = $this->settingsOrDefault($agencyId);
        $tz       = $settings->timezone ?: 'UTC';
        $weekend  = $settings->weekend_days ?? [];

        $holidays = Holiday::forAgency($agencyId)
            ->whereBetween('holiday_date', [$from, $to])
            ->pluck('holiday_date')->map(fn ($d) => $d->format('Y-m-d'))->all();
        $holidays = array_flip($holidays);

        $count  = 0;
        $cursor = CarbonImmutable::parse($from);
        $last   = CarbonImmutable::parse($to);
        for (; $cursor->lessThanOrEqualTo($last); $cursor = $cursor->addDay()) {
            $date = $cursor->format('Y-m-d');
            if ($calc->isWeekend($date, $weekend, $tz)) {
                continue;
            }
            if (isset($holidays[$date])) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    // ── Dashboard + Reports (H3d) — READ-ONLY, resolver-backed ────────────────
    //
    // No writes: every figure is a tally over AttendanceReportService::resolveRange,
    // which derives status from existing rows/holidays/weekends/approved-leave and
    // never materialises anything (ErpReportService discipline). Viewing is open to
    // any access_attendance user (staff scoped to own); bulk EXPORTS are admin-only.

    /** Today's board (status counts + on-time %) and the last-7-day trend series. */
    private function buildDashboard(int $agencyId, array $scopeIds, string $today, CarbonImmutable $nowUtc, AttendanceReportService $reports): array
    {
        $weekFrom   = CarbonImmutable::parse($today)->subDays(6)->format('Y-m-d');
        $weekMatrix = $reports->resolveRange($agencyId, $scopeIds, $weekFrom, $today, $nowUtc);

        // Board = tally restricted to today's column only.
        $todayCol = [];
        foreach ($weekMatrix as $eid => $days) {
            if (array_key_exists($today, $days)) {
                $todayCol[$eid] = [$today => $days[$today]];
            }
        }

        return [
            'today'     => $today,
            'headcount' => count($scopeIds),
            'board'     => $reports->tally($todayCol),
            'daily'     => $reports->dailyTotals($weekMatrix),
        ];
    }

    /**
     * Shared Reports build — the SINGLE source for the screen and both exports, so
     * they can never diverge. Staff are forced to their own employee; an admin may
     * filter to one employee (agency-scoped) or see the whole active workforce.
     */
    private function buildReport(Request $request, int $agencyId, bool $isAdmin, ?int $selfEmployeeId, string $today, CarbonImmutable $nowUtc, AttendanceReportService $reports, $empNames): array
    {
        $monthStart = CarbonImmutable::parse($today)->startOfMonth()->format('Y-m-d');
        $from = $this->safeDate($request->input('from'), $monthStart);
        $to   = $this->safeDate($request->input('to'), $today);
        if ($from > $to) {
            [$from, $to] = [$to, $from]; // tolerate a reversed range
        }

        $employeeId = $isAdmin ? $request->input('employee_id') : $selfEmployeeId;
        $employeeId = ($employeeId === null || $employeeId === '') ? null : (int) $employeeId;

        if ($employeeId !== null) {
            // Tenant guard: a filter id from another agency resolves to no scope.
            $valid = Employee::forAgency($agencyId)->whereKey($employeeId)->exists();
            $reportIds = $valid ? [$employeeId] : [];
        } else {
            $reportIds = Employee::forAgency($agencyId)->active()->pluck('id')->map(fn ($v) => (int) $v)->all();
        }

        $matrix  = $reports->resolveRange($agencyId, $reportIds, $from, $to, $nowUtc);
        $summary = $reports->perEmployee($matrix);

        // A single-employee focus (admin filter or any staff view) gets the per-day grid.
        $singleId  = count($reportIds) === 1 ? (int) reset($reportIds) : null;
        $dayMatrix = $singleId !== null ? ($matrix[$singleId] ?? []) : null;

        return [
            'filters'   => ['from' => $from, 'to' => $to, 'employee_id' => $employeeId],
            'summary'   => $summary,
            'dayMatrix' => $dayMatrix,
            'singleId'  => $singleId,
            'empNames'  => $empNames,
        ];
    }

    /** Parse a request date to 'Y-m-d', falling back to $default on anything invalid. */
    private function safeDate($value, string $default): string
    {
        if (! is_string($value) || $value === '') {
            return $default;
        }
        try {
            return CarbonImmutable::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return $default;
        }
    }

    public function exportReportsCsv(Request $request, AttendanceCalculator $calc, AttendanceReportService $reports): StreamedResponse
    {
        $this->authorizeAdmin(); // bulk export: admin-only

        $agencyId = auth()->user()->agency_id;
        $settings = $this->settingsOrDefault($agencyId);
        $nowUtc   = CarbonImmutable::now('UTC');
        $today    = $calc->workDateFor($nowUtc, $settings->timezone ?: 'UTC');
        $empNames = Employee::forAgency($agencyId)->pluck('name', 'id');

        $report = $this->buildReport($request, $agencyId, true, null, $today, $nowUtc, $reports, $empNames);

        $filename = 'attendance-report-' . $report['filters']['from'] . '_to_' . $report['filters']['to'] . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Attendance Report']);
            fputcsv($out, ['Range', $report['filters']['from'] . ' to ' . $report['filters']['to']]);
            fputcsv($out, []);
            fputcsv($out, ['Employee', 'Present', 'Late', 'Half day', 'Excused', 'On leave', 'Weekend', 'Holiday', 'Absent', 'Pending', 'On-time %']);
            foreach ($report['summary'] as $empId => $c) {
                fputcsv($out, [
                    $report['empNames'][$empId] ?? ('#' . $empId),
                    $c['present'], $c['late'], $c['half_day'], $c['excused'], $c['on_leave'],
                    $c['weekend'], $c['holiday'], $c['absent'], $c['pending'],
                    $c['on_time_pct'] === null ? '—' : $c['on_time_pct'] . '%',
                ]);
            }
            if ($report['dayMatrix'] !== null) {
                fputcsv($out, []);
                fputcsv($out, ['Day-by-day — ' . ($report['empNames'][$report['singleId']] ?? ('#' . $report['singleId']))]);
                fputcsv($out, ['Date', 'Status']);
                foreach ($report['dayMatrix'] as $date => $status) {
                    fputcsv($out, [$date, AttendanceRecord::STATUSES[$status] ?? ucfirst(str_replace('_', ' ', $status))]);
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportReportsPdf(Request $request, AttendanceCalculator $calc, AttendanceReportService $reports, PdfGeneratorService $pdf)
    {
        $this->authorizeAdmin(); // bulk export: admin-only

        $agencyId = auth()->user()->agency_id;
        $settings = $this->settingsOrDefault($agencyId);
        $nowUtc   = CarbonImmutable::now('UTC');
        $today    = $calc->workDateFor($nowUtc, $settings->timezone ?: 'UTC');
        $empNames = Employee::forAgency($agencyId)->pluck('name', 'id');

        $report = $this->buildReport($request, $agencyId, true, null, $today, $nowUtc, $reports, $empNames);

        return $pdf->generateFromView('agency.attendance.reports-pdf', [
            'report'    => $report,
            'agency'    => auth()->user()->agency,
            'generated' => now(),
        ], 'attendance-report-' . $report['filters']['from'] . '_to_' . $report['filters']['to']);
    }

    // ── Guards ────────────────────────────────────────────────────────────────

    /** Boolean form of the admin check (for view-feed decisions). */
    private function userIsAdmin(): bool
    {
        return auth()->user()->isAgencyAdmin();
    }

    /** Employee management is admin-only (mirrors StaffController). */
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->isAgencyAdmin(), 403);
    }

    /**
     * The target row must belong to the caller's agency. Blocks a staff/admin
     * from mutating another tenant's attendance config via a guessed id.
     */
    private function authorizeAgency(Model $model): void
    {
        abort_unless($model->agency_id === auth()->user()->agency_id, 403);
    }
}
