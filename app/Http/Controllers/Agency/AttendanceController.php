<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function index(Request $request)
    {
        $agencyId = auth()->user()->agency_id;

        $tab = in_array($request->input('tab'), self::TABS, true) ? $request->input('tab') : 'dashboard';

        $settings   = AttendanceSetting::forAgency($agencyId)->first() ?? new AttendanceSetting();
        $shifts     = Shift::forAgency($agencyId)->orderByDesc('is_default')->orderBy('name')->get();
        $holidays   = Holiday::forAgency($agencyId)->orderBy('holiday_date')->get();
        $leaveTypes = LeaveType::forAgency($agencyId)->orderBy('name')->get();

        return view('agency.attendance.index', [
            'tab'        => $tab,
            'settings'   => $settings,
            'shifts'     => $shifts,
            'holidays'   => $holidays,
            'leaveTypes' => $leaveTypes,
            'timezones'  => AttendanceSetting::TIMEZONES,
            'weekdays'   => AttendanceSetting::WEEKDAYS,
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

    // ── Tenancy guard ─────────────────────────────────────────────────────────

    /**
     * The target row must belong to the caller's agency. Blocks a staff/admin
     * from mutating another tenant's attendance config via a guessed id.
     */
    private function authorizeAgency(Model $model): void
    {
        abort_unless($model->agency_id === auth()->user()->agency_id, 403);
    }
}
