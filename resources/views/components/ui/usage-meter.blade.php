@props([
    'label' => '',
    'used'  => 0,
    'limit' => 0,
    'color' => 'brand',  // brand | green | amber | cyan
])

@php
    $unlimited = ($limit >= 9999);
    $pct = $unlimited ? 0 : ($limit > 0 ? min(100, round(($used / $limit) * 100)) : 0);
    $bar = $pct >= 100 ? 'bg-rose-500'
        : ($pct >= 80 ? 'bg-amber-500'
        : ['brand' => 'bg-brand-500', 'green' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'cyan' => 'bg-cyan-500'][$color] ?? 'bg-brand-500');
@endphp

<div class="mb-3 last:mb-0">
    <div class="mb-1 flex items-center justify-between text-xs">
        <span class="font-medium text-slate-500">{{ $label }}</span>
        @if($unlimited)
            <span class="font-semibold text-emerald-600"><i class="bi bi-infinity"></i> Unlimited</span>
        @else
            <span class="font-semibold {{ $pct >= 100 ? 'text-rose-600' : ($pct >= 80 ? 'text-amber-600' : 'text-slate-700') }}">
                {{ number_format($used) }} <span class="font-medium text-slate-400">/ {{ number_format($limit) }}</span>
            </span>
        @endif
    </div>
    @unless($unlimited)
        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full {{ $bar }} transition-all duration-500" style="width: {{ max($pct, 2) }}%"></div>
        </div>
    @endunless
</div>
