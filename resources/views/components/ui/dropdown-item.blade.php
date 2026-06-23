@props([
    'href'   => null,
    'icon'   => null,
    'tone'   => 'default',  // default | danger
])

@php
    $tone = $tone === 'danger'
        ? 'text-rose-600 hover:bg-rose-50'
        : 'text-slate-700 hover:bg-slate-50';
    $cls = "flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-sm $tone";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>
        @if($icon)<i class="bi {{ $icon }} text-base text-slate-400"></i>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $attributes->get('type', 'button') }}" {{ $attributes->merge(['class' => $cls]) }}>
        @if($icon)<i class="bi {{ $icon }} text-base text-slate-400"></i>@endif
        {{ $slot }}
    </button>
@endif
