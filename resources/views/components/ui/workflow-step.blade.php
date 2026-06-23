@props([
    'num'   => 1,
    'icon'  => 'bi-circle',
    'title' => '',
    'sub'   => null,
    'href'  => null,
    'state' => 'todo',   // done | active | todo
])

@php
    $tag = $href ? 'a' : 'div';
    $cfg = [
        'done'   => ['ring-emerald-200 bg-emerald-50/50', 'bg-emerald-500 text-white',                          'text-emerald-800', 'text-emerald-400'],
        'active' => ['ring-brand-300 bg-white shadow-card', 'bg-gradient-to-br from-brand-600 to-indigo-600 text-white', 'text-brand-700',   'text-brand-500'],
        'todo'   => ['ring-slate-200 bg-white',            'bg-slate-100 text-slate-400',                        'text-slate-700',   'text-slate-300'],
    ];
    [$cardCls, $badgeCls, $titleCls, $iconCls] = $cfg[$state] ?? $cfg['todo'];
    $hover = $href ? ' transition hover:-translate-y-0.5 hover:shadow-card' : '';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => "group relative flex flex-col gap-3 rounded-2xl border border-transparent p-4 ring-1 $cardCls$hover"]) }}>
    <div class="flex items-center justify-between">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl text-sm font-bold {{ $badgeCls }}">
            @if($state === 'done')<i class="bi bi-check-lg"></i>@else{{ $num }}@endif
        </span>
        <i class="bi {{ $icon }} text-lg {{ $iconCls }}"></i>
    </div>
    <div class="min-w-0">
        <div class="truncate text-sm font-semibold {{ $titleCls }}">{{ $title }}</div>
        @if($sub)<div class="mt-0.5 truncate text-xs text-slate-400">{{ $sub }}</div>@endif
    </div>
</{{ $tag }}>
