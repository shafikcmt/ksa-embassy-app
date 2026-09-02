@extends('layouts.agency-app')
@section('title', 'Attendance')
@section('page-title', 'Attendance')

@php
    // time column comes back as 'HH:MM:SS' — trim to 'HH:MM' for <input type=time>.
    $hm = fn ($v, $default = '') => $v ? substr((string) $v, 0, 5) : $default;

    $weekend = old('weekend_days', $settings->weekend_days ?? []);

    $tabs = [
        'dashboard' => ['Dashboard', 'bi-speedometer2', false],
        'employees' => ['Employees', 'bi-people',       true],
        'shifts'    => ['Shifts',    'bi-clock-history', true],
        'settings'  => ['Settings',  'bi-gear',          true],
        'leave'     => ['Leave',     'bi-calendar-minus',true],
        'holidays'  => ['Holidays',  'bi-calendar-event',true],
        'reports'   => ['Reports',   'bi-bar-chart',     false],
    ];

    $inputCls = 'h-10 w-full rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400';
@endphp

@section('content')
<div x-data="{
        tab: @js($tab),
        del: { open: false, title: '', action: '' },
        shift: { open: false, method: 'POST', action: @js(route('attendance.shifts.store')), heading: 'New Shift', name: '', start_time: '', end_time: '', is_default: false },
        holiday: { open: false, method: 'POST', action: @js(route('attendance.holidays.store')), heading: 'Add Holiday', title: '', holiday_date: '' },
        leave: { open: false, method: 'POST', action: @js(route('attendance.leave-types.store')), heading: 'New Leave Type', name: '', is_paid: true, default_days: '', color: '#6366f1' },
        employee: { open: false, method: 'POST', action: @js(route('attendance.employees.store')), heading: 'New Employee', name: '', user_id: '', shift_id: '', designation: '', phone: '', email: '', join_date: '', status: 'active' },
        newEmployee() { this.employee = { open: true, method: 'POST', action: @js(route('attendance.employees.store')), heading: 'New Employee', name: '', user_id: '', shift_id: '', designation: '', phone: '', email: '', join_date: '', status: 'active' }; },
        editEmployee(e) { this.employee = { open: true, method: 'PUT', action: e.action, heading: 'Edit Employee', name: e.name, user_id: e.user_id ?? '', shift_id: e.shift_id ?? '', designation: e.designation ?? '', phone: e.phone ?? '', email: e.email ?? '', join_date: e.join_date ?? '', status: e.status }; },
        newShift() { this.shift = { open: true, method: 'POST', action: @js(route('attendance.shifts.store')), heading: 'New Shift', name: '', start_time: '', end_time: '', is_default: false }; },
        editShift(s) { this.shift = { open: true, method: 'PUT', action: s.action, heading: 'Edit Shift', name: s.name, start_time: s.start_time, end_time: s.end_time, is_default: s.is_default }; },
        newHoliday() { this.holiday = { open: true, method: 'POST', action: @js(route('attendance.holidays.store')), heading: 'Add Holiday', title: '', holiday_date: '' }; },
        editHoliday(h) { this.holiday = { open: true, method: 'PUT', action: h.action, heading: 'Edit Holiday', title: h.title, holiday_date: h.holiday_date }; },
        newLeave() { this.leave = { open: true, method: 'POST', action: @js(route('attendance.leave-types.store')), heading: 'New Leave Type', name: '', is_paid: true, default_days: '', color: '#6366f1' }; },
        editLeave(l) { this.leave = { open: true, method: 'PUT', action: l.action, heading: 'Edit Leave Type', name: l.name, is_paid: l.is_paid, default_days: l.default_days, color: l.color || '#6366f1' }; }
    }">

    <x-ui.page-header
        title="Attendance"
        subtitle="Configure shifts, holidays, leave types and attendance rules for your agency"
        icon="bi-calendar-check" />

    {{-- Config summary --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <x-ui.stat icon="bi-people" tone="green" label="Employees"  :value="$employees->count()" />
        <x-ui.stat icon="bi-clock-history" tone="brand"  label="Shifts"      :value="$shifts->count()" />
        <x-ui.stat icon="bi-calendar-event" tone="amber" label="Holidays"    :value="$holidays->count()" />
        <x-ui.stat icon="bi-calendar-minus" tone="violet" label="Leave Types" :value="$leaveTypes->count()" />
        <x-ui.stat icon="bi-globe" tone="cyan" label="Timezone" :value="$settings->exists ? $settings->timezone : 'Not set'" />
    </div>

    {{-- ── Tab bar ───────────────────────────────────────────── --}}
    <div class="mb-5 border-b border-slate-200">
        <div class="flex gap-1 overflow-x-auto">
            @foreach($tabs as $key => [$label, $icon, $ready])
                <button type="button" x-on:click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'border-brand-500 text-brand-600'
                        : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="flex shrink-0 items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors">
                    <i class="bi {{ $icon }}"></i>
                    <span>{{ $label }}</span>
                    @unless($ready)
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide text-slate-400">Soon</span>
                    @endunless
                </button>
            @endforeach
        </div>
    </div>

    {{-- ══ Dashboard (placeholder) ══════════════════════════════ --}}
    <div x-show="tab === 'dashboard'" x-cloak>
        <x-ui.card>
            <x-ui.empty icon="bi-speedometer2" title="Attendance dashboard is coming soon"
                message="Today's present / late / absent overview and check-in activity will appear here once employee check-in is enabled." />
        </x-ui.card>
    </div>

    {{-- ══ Employees (placeholder) ═════════════════════════════ --}}
    <div x-show="tab === 'employees'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Employees</h2>
            @if(auth()->user()->isAgencyAdmin())
                <button type="button" x-on:click="newEmployee()"
                    class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                    <i class="bi bi-plus-lg"></i> New Employee
                </button>
            @endif
        </div>
        <x-ui.card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Login</th>
                            <th class="px-4 py-3">Shift</th>
                            <th class="px-4 py-3">Joined</th>
                            <th class="px-4 py-3">Status</th>
                            @if(auth()->user()->isAgencyAdmin())
                                <th class="px-4 py-3 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($employees as $employee)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $employee->name }}</div>
                                    @if($employee->designation)
                                        <div class="text-xs text-slate-400">{{ $employee->designation }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($employee->user)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700" title="{{ $employee->user->email }}"><i class="bi bi-person-check"></i> {{ $employee->user->name }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">No login</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $employee->shift->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $employee->join_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 py-3"><x-ui.status-badge :status="$employee->status" /></td>
                                @if(auth()->user()->isAgencyAdmin())
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" title="Edit" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"
                                                x-on:click="editEmployee(@js(['action' => route('attendance.employees.update', $employee), 'name' => $employee->name, 'user_id' => $employee->user_id, 'shift_id' => $employee->shift_id, 'designation' => $employee->designation, 'phone' => $employee->phone, 'email' => $employee->email, 'join_date' => $employee->join_date?->format('Y-m-d'), 'status' => $employee->status]))"><i class="bi bi-pencil"></i></button>
                                            <button type="button" title="Retire" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-600 transition-colors hover:bg-rose-50"
                                                x-on:click="del.open = true; del.title = @js('Employee: '.$employee->name); del.action = @js(route('attendance.employees.destroy', $employee))"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isAgencyAdmin() ? 6 : 5 }}" class="px-4 py-10">
                                    <x-ui.empty icon="bi-people" title="No employees yet"
                                        message="Add your office staff here. Link a login so they can check in, or add a login-less employee whose attendance you track manually." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    {{-- ══ Shifts ══════════════════════════════════════════════ --}}
    <div x-show="tab === 'shifts'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Work Shifts</h2>
            <button type="button" x-on:click="newShift()"
                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                <i class="bi bi-plus-lg"></i> New Shift
            </button>
        </div>
        <x-ui.card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Start</th>
                            <th class="px-4 py-3">End</th>
                            <th class="px-4 py-3">Default</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($shifts as $shift)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $shift->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $hm($shift->start_time) }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $hm($shift->end_time) }}</td>
                                <td class="px-4 py-3">
                                    @if($shift->is_default)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700"><i class="bi bi-star-fill"></i> Default</span>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"
                                            x-on:click="editShift(@js(['action' => route('attendance.shifts.update', $shift), 'name' => $shift->name, 'start_time' => $hm($shift->start_time), 'end_time' => $hm($shift->end_time), 'is_default' => (bool) $shift->is_default]))"><i class="bi bi-pencil"></i></button>
                                        <button type="button" title="Delete" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50"
                                            x-on:click="del.open = true; del.title = @js('Shift: '.$shift->name); del.action = @js(route('attendance.shifts.destroy', $shift))"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-0">
                                <x-ui.empty icon="bi-clock-history" title="No shifts yet"
                                    message="Add your company default shift and any alternates." />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    {{-- ══ Settings ════════════════════════════════════════════ --}}
    <div x-show="tab === 'settings'" x-cloak>
        <form method="POST" action="{{ route('attendance.settings.update') }}">
            @csrf
            @method('PUT')
            <x-ui.card class="p-5">
                <h2 class="mb-4 text-sm font-semibold text-slate-700">Attendance Rules</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <x-ui.field label="Office start" name="office_start" required>
                        <input type="time" name="office_start" value="{{ $hm(old('office_start', $settings->office_start), '09:00') }}" required class="{{ $inputCls }}">
                    </x-ui.field>
                    <x-ui.field label="Office end" name="office_end" required>
                        <input type="time" name="office_end" value="{{ $hm(old('office_end', $settings->office_end), '18:00') }}" required class="{{ $inputCls }}">
                    </x-ui.field>
                    <x-ui.field label="Grace period (minutes)" name="grace_minutes" required hint="Late allowed before it counts">
                        <input type="number" name="grace_minutes" min="0" max="600" value="{{ old('grace_minutes', $settings->grace_minutes ?? 15) }}" required class="{{ $inputCls }}">
                    </x-ui.field>
                    <x-ui.field label="Auto-absent time" name="auto_absent_time" hint="No check-in by this time = absent">
                        <input type="time" name="auto_absent_time" value="{{ $hm(old('auto_absent_time', $settings->auto_absent_time)) }}" class="{{ $inputCls }}">
                    </x-ui.field>
                    <x-ui.field label="Half-day after (minutes late)" name="half_day_after_minutes">
                        <input type="number" name="half_day_after_minutes" min="0" max="600" value="{{ old('half_day_after_minutes', $settings->half_day_after_minutes) }}" class="{{ $inputCls }}">
                    </x-ui.field>
                    <x-ui.field label="Overtime after (minutes)" name="overtime_after_minutes">
                        <input type="number" name="overtime_after_minutes" min="0" max="600" value="{{ old('overtime_after_minutes', $settings->overtime_after_minutes) }}" class="{{ $inputCls }}">
                    </x-ui.field>
                    <x-ui.field label="Timezone" name="timezone" required class="sm:col-span-2 lg:col-span-1">
                        <select name="timezone" required class="{{ $inputCls }}">
                            @foreach($timezones as $tz => $label)
                                <option value="{{ $tz }}" @selected(old('timezone', $settings->timezone ?? 'Asia/Dhaka') === $tz)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>

                <div class="mt-5">
                    <div class="mb-2 text-xs font-semibold text-slate-600">Weekend days</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($weekdays as $num => $day)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm transition hover:border-brand-300 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50">
                                <input type="checkbox" name="weekend_days[]" value="{{ $num }}" @checked(in_array($num, (array) $weekend)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-slate-700">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5">
                    <div class="mb-2 text-xs font-semibold text-slate-600">Owner email alerts</div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @php
                            $alerts = [
                                'alert_on_late'     => 'On late check-in',
                                'alert_on_absent'   => 'On absent',
                                'alert_on_checkin'  => 'On check-in',
                                'alert_on_checkout' => 'On check-out',
                            ];
                        @endphp
                        @foreach($alerts as $field => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 p-3 transition hover:border-brand-300 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50">
                                <input type="hidden" name="{{ $field }}" value="0">
                                <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $settings->$field ?? false)) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                <span class="text-sm font-medium text-slate-800">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                    <x-ui.button type="submit"><i class="bi bi-check-lg"></i> Save Settings</x-ui.button>
                </div>
            </x-ui.card>
        </form>
    </div>

    {{-- ══ Leave (types) ═══════════════════════════════════════ --}}
    <div x-show="tab === 'leave'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-700">Leave Types</h2>
                <p class="text-xs text-slate-400">Leave requests &amp; approvals arrive with employee check-in in a later phase.</p>
            </div>
            <button type="button" x-on:click="newLeave()"
                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                <i class="bi bi-plus-lg"></i> New Leave Type
            </button>
        </div>
        <x-ui.card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Paid</th>
                            <th class="px-4 py-3">Default days / yr</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($leaveTypes as $lt)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-800">
                                        @if($lt->color)<span class="h-2.5 w-2.5 rounded-full" style="background: {{ $lt->color }}"></span>@endif
                                        {{ $lt->name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($lt->is_paid)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Paid</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Unpaid</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $lt->default_days ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"
                                            x-on:click="editLeave(@js(['action' => route('attendance.leave-types.update', $lt), 'name' => $lt->name, 'is_paid' => (bool) $lt->is_paid, 'default_days' => $lt->default_days, 'color' => $lt->color]))"><i class="bi bi-pencil"></i></button>
                                        <button type="button" title="Delete" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50"
                                            x-on:click="del.open = true; del.title = @js('Leave type: '.$lt->name); del.action = @js(route('attendance.leave-types.destroy', $lt))"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-0">
                                <x-ui.empty icon="bi-calendar-minus" title="No leave types yet"
                                    message="Add the leave categories your agency uses (Paid, Sick, Unpaid, ...)." />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    {{-- ══ Holidays ════════════════════════════════════════════ --}}
    <div x-show="tab === 'holidays'" x-cloak>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Holiday Calendar</h2>
            <button type="button" x-on:click="newHoliday()"
                class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                <i class="bi bi-plus-lg"></i> Add Holiday
            </button>
        </div>
        <x-ui.card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($holidays as $holiday)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $holiday->holiday_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $holiday->title }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"
                                            x-on:click="editHoliday(@js(['action' => route('attendance.holidays.update', $holiday), 'title' => $holiday->title, 'holiday_date' => $holiday->holiday_date->format('Y-m-d')]))"><i class="bi bi-pencil"></i></button>
                                        <button type="button" title="Delete" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50"
                                            x-on:click="del.open = true; del.title = @js('Holiday: '.$holiday->title); del.action = @js(route('attendance.holidays.destroy', $holiday))"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-0">
                                <x-ui.empty icon="bi-calendar-event" title="No holidays yet"
                                    message="Add your agency's holiday dates." />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    {{-- ══ Reports (placeholder) ═══════════════════════════════ --}}
    <div x-show="tab === 'reports'" x-cloak>
        <x-ui.card>
            <x-ui.empty icon="bi-bar-chart" title="Reports are coming soon"
                message="Date-range attendance reports with Excel / PDF / CSV export will be available once check-in records exist." />
        </x-ui.card>
    </div>

    {{-- ── Shift modal ───────────────────────────────────────── --}}
    <div x-show="shift.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="shift.open = false" x-show="shift.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="shift.open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
            <form method="POST" :action="shift.action">
                @csrf
                <input type="hidden" name="_method" :value="shift.method">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900" x-text="shift.heading"></h3>
                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" x-on:click="shift.open = false"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Shift name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="shift.name" required maxlength="80" class="{{ $inputCls }}" placeholder="e.g. Company Default">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Start time <span class="text-rose-500">*</span></label>
                            <input type="time" name="start_time" x-model="shift.start_time" required class="{{ $inputCls }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">End time <span class="text-rose-500">*</span></label>
                            <input type="time" name="end_time" x-model="shift.end_time" required class="{{ $inputCls }}">
                        </div>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 p-3">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1" x-model="shift.is_default" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-medium text-slate-800">Set as default shift <span class="font-normal text-slate-400">(only one per agency)</span></span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="shift.open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" size="sm" class="cursor-pointer"><i class="bi bi-check-lg"></i> Save Shift</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Holiday modal ─────────────────────────────────────── --}}
    <div x-show="holiday.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="holiday.open = false" x-show="holiday.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="holiday.open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
            <form method="POST" :action="holiday.action">
                @csrf
                <input type="hidden" name="_method" :value="holiday.method">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900" x-text="holiday.heading"></h3>
                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" x-on:click="holiday.open = false"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="holiday_date" x-model="holiday.holiday_date" required class="{{ $inputCls }}">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" x-model="holiday.title" required maxlength="120" class="{{ $inputCls }}" placeholder="e.g. National Day">
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="holiday.open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" size="sm" class="cursor-pointer"><i class="bi bi-check-lg"></i> Save Holiday</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Leave type modal ──────────────────────────────────── --}}
    <div x-show="leave.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="leave.open = false" x-show="leave.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="leave.open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
            <form method="POST" :action="leave.action">
                @csrf
                <input type="hidden" name="_method" :value="leave.method">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900" x-text="leave.heading"></h3>
                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" x-on:click="leave.open = false"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="leave.name" required maxlength="80" class="{{ $inputCls }}" placeholder="e.g. Sick Leave">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Default days / year</label>
                            <input type="number" name="default_days" x-model="leave.default_days" min="0" max="365" class="{{ $inputCls }}" placeholder="e.g. 10">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Color</label>
                            <input type="color" name="color" x-model="leave.color" class="h-10 w-full rounded-lg border border-slate-300 bg-white p-1">
                        </div>
                    </div>
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 p-3">
                        <input type="hidden" name="is_paid" value="0">
                        <input type="checkbox" name="is_paid" value="1" x-model="leave.is_paid" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-medium text-slate-800">Paid leave</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="leave.open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" size="sm" class="cursor-pointer"><i class="bi bi-check-lg"></i> Save Leave Type</x-ui.button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Employee modal ────────────────────────────────────── --}}
    @if(auth()->user()->isAgencyAdmin())
    <div x-show="employee.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="employee.open = false" x-show="employee.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="employee.open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl bg-white shadow-xl">
            <form method="POST" :action="employee.action">
                @csrf
                <input type="hidden" name="_method" :value="employee.method">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900" x-text="employee.heading"></h3>
                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100" x-on:click="employee.open = false"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Employee name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="employee.name" required maxlength="120" class="{{ $inputCls }}" placeholder="e.g. Kamal Uddin">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Link to login <span class="font-normal text-slate-400">(optional)</span></label>
                            <select name="user_id" x-model="employee.user_id" class="{{ $inputCls }}">
                                <option value="">— No login —</option>
                                @foreach($linkableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Shift <span class="font-normal text-slate-400">(optional)</span></label>
                            <select name="shift_id" x-model="employee.shift_id" class="{{ $inputCls }}">
                                <option value="">— None —</option>
                                @foreach($shifts as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Designation</label>
                            <input type="text" name="designation" x-model="employee.designation" maxlength="120" class="{{ $inputCls }}" placeholder="e.g. Accountant">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Join date</label>
                            <input type="date" name="join_date" x-model="employee.join_date" class="{{ $inputCls }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Phone</label>
                            <input type="text" name="phone" x-model="employee.phone" maxlength="40" class="{{ $inputCls }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Email</label>
                            <input type="email" name="email" x-model="employee.email" maxlength="255" class="{{ $inputCls }}">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Status <span class="text-rose-500">*</span></label>
                        <select name="status" x-model="employee.status" required class="{{ $inputCls }}">
                            @foreach(\App\Models\Employee::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="employee.open = false">Cancel</x-ui.button>
                    <x-ui.button type="submit" size="sm" class="cursor-pointer"><i class="bi bi-check-lg"></i> Save Employee</x-ui.button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Delete dialog (shared) ────────────────────────────── --}}
    <div x-show="del.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="del.open = false" x-show="del.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="del.open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Confirm delete</h3>
                    <p class="mt-1 text-sm text-slate-500">Delete <strong x-text="del.title"></strong>? This can't be undone.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="del.open = false">Cancel</x-ui.button>
                <form :action="del.action" method="POST">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm" class="cursor-pointer"><i class="bi bi-trash"></i> Delete</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
