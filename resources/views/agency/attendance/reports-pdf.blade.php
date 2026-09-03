<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 10mm; }
    * { font-family: DejaVu Sans, sans-serif; }
    body { color: #1e293b; font-size: 10px; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    .muted { color: #64748b; font-size: 9px; }
    .meta { margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { border: 1px solid #cbd5e1; padding: 4px 6px; }
    th { background: #f1f5f9; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; }
    td.num, th.num { text-align: center; }
    td.name { font-weight: bold; }
    .section { margin-top: 14px; font-size: 12px; font-weight: bold; }
    .foot { margin-top: 14px; color: #94a3b8; font-size: 8px; }
</style>
</head>
<body>
    @php
        $rf = $report['filters'];
        $sumCols = [
            'present' => 'Present', 'late' => 'Late', 'half_day' => 'Half', 'excused' => 'Excused',
            'on_leave' => 'Leave', 'weekend' => 'W/end', 'holiday' => 'Hol', 'absent' => 'Absent', 'pending' => 'Pending',
        ];
    @endphp

    <h1>{{ $agency->name ?? 'Attendance' }} — Attendance Report</h1>
    <div class="meta muted">
        Range: {{ $rf['from'] }} → {{ $rf['to'] }}
        · Generated: {{ $generated->format('d M Y, H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee</th>
                @foreach($sumCols as $label)<th class="num">{{ $label }}</th>@endforeach
                <th class="num">On-time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['summary'] as $empId => $c)
                <tr>
                    <td class="name">{{ $report['empNames'][$empId] ?? ('#'.$empId) }}</td>
                    @foreach($sumCols as $key => $label)<td class="num">{{ $c[$key] }}</td>@endforeach
                    <td class="num">{{ $c['on_time_pct'] === null ? '—' : $c['on_time_pct'].'%' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ count($sumCols) + 2 }}" class="muted">No employees in scope for this range.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($report['dayMatrix'] !== null)
        <div class="section">Day-by-day — {{ $report['empNames'][$report['singleId']] ?? '' }}</div>
        <table>
            <thead><tr><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($report['dayMatrix'] as $date => $status)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($date)->format('D, d M Y') }}</td>
                        <td>{{ \App\Models\AttendanceRecord::STATUSES[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="foot">On-time % = present ÷ (present + late + half-day). Absent/weekend/holiday/leave days derived read-only; no attendance data was modified by this report.</div>
</body>
</html>
