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

<div class="mb-2">
    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.78rem;">
        <span class="text-muted">{{ $label }}</span>
        @if($unlimited)
            <span class="text-success fw-semibold"><i class="bi bi-infinity"></i> Unlimited</span>
        @else
            <span class="fw-semibold {{ $pct >= 100 ? 'text-danger' : ($pct >= 80 ? 'text-warning' : '') }}">
                {{ number_format($used) }} / {{ number_format($limit) }}
            </span>
        @endif
    </div>
    @if(!$unlimited)
    <div style="height:5px;background:#e5e7eb;border-radius:3px;overflow:hidden;">
        <div style="width:{{ $pct }}%;height:100%;background:{{ $barColor }};border-radius:3px;transition:width .3s;"></div>
    </div>
    @endif
</div>
