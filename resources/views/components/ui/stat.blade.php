@props([
    'icon'  => 'bi-bar-chart',
    'label' => '',
    'value' => '',
    'sub'   => null,
    'href'  => null,
    'tone'  => 'slate',     // slate | brand | green | amber | red | violet | cyan
    'subTone' => 'muted',   // muted | green | amber | red
    'accent' => false,      // tint the whole card with the tone colour
    'progress' => null,     // 0-100 -> thin progress bar at the bottom
])

@php
    // Soft icon chip (used when accent is off)
    $tones = [
        'slate'  => 'bg-slate-100 text-slate-600',
        'brand'  => 'bg-brand-50 text-brand-600',
        'green'  => 'bg-emerald-50 text-emerald-600',
        'amber'  => 'bg-amber-50 text-amber-600',
        'red'    => 'bg-rose-50 text-rose-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'cyan'   => 'bg-cyan-50 text-cyan-600',
    ];
    // Tinted card surface (used when accent is on)
    $accentCard = [
        'slate'  => 'border-slate-200 bg-slate-50',
        'brand'  => 'border-brand-100 bg-brand-50/70',
        'green'  => 'border-emerald-100 bg-emerald-50/70',
        'amber'  => 'border-amber-100 bg-amber-50/70',
        'red'    => 'border-rose-100 bg-rose-50/70',
        'violet' => 'border-violet-100 bg-violet-50/70',
        'cyan'   => 'border-cyan-100 bg-cyan-50/70',
    ];
    // Solid icon chip (used when accent is on)
    $accentIcon = [
        'slate'  => 'bg-slate-500 text-white',
        'brand'  => 'bg-brand-600 text-white',
        'green'  => 'bg-emerald-600 text-white',
        'amber'  => 'bg-amber-500 text-white',
        'red'    => 'bg-rose-500 text-white',
        'violet' => 'bg-violet-600 text-white',
        'cyan'   => 'bg-cyan-600 text-white',
    ];
    $barCls = [
        'slate'  => 'bg-slate-400', 'brand' => 'bg-brand-500', 'green' => 'bg-emerald-500',
        'amber'  => 'bg-amber-500', 'red'   => 'bg-rose-500',  'violet' => 'bg-violet-500',
        'cyan'   => 'bg-cyan-500',
    ];

    $iconCls = $accent ? ($accentIcon[$tone] ?? $accentIcon['slate']) : ($tones[$tone] ?? $tones['slate']);
    $cardCls = $accent
        ? ($accentCard[$tone] ?? $accentCard['slate'])
        : 'border-slate-200 bg-white';

    $subTones = ['muted' => 'text-slate-400', 'green' => 'text-emerald-600', 'amber' => 'text-amber-600', 'red' => 'text-rose-600'];
    $subCls   = $subTones[$subTone] ?? $subTones['muted'];

    $tag   = $href ? 'a' : 'div';
    $hover = $href ? ' transition hover:-translate-y-0.5 hover:shadow-card' : '';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => "group block rounded-2xl border $cardCls p-4 shadow-soft$hover"]) }}>
    <div class="mb-3 flex items-center justify-between">
        <span class="grid h-10 w-10 place-items-center rounded-xl text-lg shadow-sm {{ $iconCls }}">
            <i class="bi {{ $icon }}"></i>
        </span>
        @if($href)
            <i class="bi bi-chevron-right text-sm text-slate-300 transition group-hover:text-slate-500"></i>
        @endif
    </div>
    <div class="text-2xl font-extrabold leading-none tracking-tight text-slate-900">{{ $value }}</div>
    <div class="mt-1.5 text-sm font-medium text-slate-500">{{ $label }}</div>
    @if(!is_null($sub) && $sub !== '')
        <div class="mt-0.5 text-xs font-medium {{ $subCls }}">{{ $sub }}</div>
    @endif
    @if(!is_null($progress))
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-200/70">
            <div class="h-full rounded-full {{ $barCls[$tone] ?? $barCls['slate'] }}" style="width: {{ max(2, min(100, (int) $progress)) }}%"></div>
        </div>
    @endif
</{{ $tag }}>
