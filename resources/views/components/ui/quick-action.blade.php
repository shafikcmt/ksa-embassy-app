@props([
    'href'  => '#',
    'icon'  => 'bi-arrow-right',
    'title' => '',
    'sub'   => null,
    'tone'  => 'brand',   // brand | violet | green | amber | cyan | rose
])

@php
    $tones = [
        'brand'  => 'from-brand-50 to-cyan-50 text-brand-600 group-hover:from-brand-600 group-hover:to-cyan-500',
        'violet' => 'from-violet-50 to-indigo-50 text-violet-600 group-hover:from-violet-600 group-hover:to-indigo-500',
        'green'  => 'from-emerald-50 to-teal-50 text-emerald-600 group-hover:from-emerald-600 group-hover:to-teal-500',
        'amber'  => 'from-amber-50 to-orange-50 text-amber-600 group-hover:from-amber-500 group-hover:to-orange-500',
        'cyan'   => 'from-cyan-50 to-sky-50 text-cyan-600 group-hover:from-cyan-600 group-hover:to-sky-500',
        'rose'   => 'from-rose-50 to-pink-50 text-rose-600 group-hover:from-rose-600 group-hover:to-pink-500',
    ];
    $ic = $tones[$tone] ?? $tones['brand'];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'group flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-card']) }}>
    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br text-lg transition group-hover:text-white group-hover:shadow-lg group-hover:shadow-brand-500/20 {{ $ic }}">
        <i class="bi {{ $icon }}"></i>
    </span>
    <div class="min-w-0 flex-1">
        <div class="truncate text-sm font-semibold text-slate-800 transition group-hover:text-brand-700">{{ $title }}</div>
        @if($sub)<div class="truncate text-xs text-slate-400">{{ $sub }}</div>@endif
    </div>
    <i class="bi bi-arrow-right text-sm text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500"></i>
</a>
