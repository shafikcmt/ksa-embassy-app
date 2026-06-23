@props(['label', 'used', 'limit', 'color' => 'primary'])

@php
$unlimited = ($limit >= 9999);
$pct       = $unlimited ? 0 : ($limit > 0 ? min(100, round(($used / $limit) * 100)) : 0);
$barColor  = $pct >= 100 ? '#ef4444' : ($pct >= 80 ? '#f59e0b' : match($color) {
    'success' => '#10b981',
    'warning' => '#f59e0b',
    'danger'  => '#ef4444',
    'info'    => '#06b6d4',
    default   => '#3b82f6',
});
@endphp

<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.78rem;">
        <span style="color:#475569;font-weight:500;">{{ $label }}</span>
        @if($unlimited)
            <span class="text-success fw-semibold"><i class="bi bi-infinity"></i> Unlimited</span>
        @else
            <span class="fw-semibold {{ $pct >= 100 ? 'text-danger' : ($pct >= 80 ? 'text-warning' : 'text-slate' ) }}" style="color:#334155;">
                {{ number_format($used) }} <span style="color:#94a3b8;font-weight:500;">/ {{ number_format($limit) }}</span>
            </span>
        @endif
    </div>
    @if(!$unlimited)
    <div style="height:7px;background:#eef2f7;border-radius:999px;overflow:hidden;">
        <div style="width:{{ max($pct, 2) }}%;height:100%;background:linear-gradient(90deg,{{ $barColor }},{{ $barColor }}cc);border-radius:999px;transition:width .4s ease;"></div>
    </div>
    @endif
</div>
