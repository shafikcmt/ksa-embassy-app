@props([
    'tone' => 'slate',  // slate | brand | green | amber | red | violet | cyan
])

@php
    $tones = [
        'slate'  => 'bg-slate-100 text-slate-700 ring-slate-200',
        'brand'  => 'bg-brand-50 text-brand-700 ring-brand-200',
        'green'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber'  => 'bg-amber-50 text-amber-700 ring-amber-200',
        'red'    => 'bg-rose-50 text-rose-700 ring-rose-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'cyan'   => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
    ];
    $cls = $tones[$tone] ?? $tones['slate'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset $cls"]) }}>
    {{ $slot }}
</span>
