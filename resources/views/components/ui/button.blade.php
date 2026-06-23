@props([
    'href'    => null,
    'variant' => 'primary',  // primary | secondary | ghost | danger | success | outline
    'size'    => 'md',       // sm | md | icon
    'type'    => 'button',
])

@php
    $variants = [
        'primary'   => 'bg-brand-600 text-white hover:bg-brand-700 shadow-sm',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50',
        'outline'   => 'bg-transparent text-brand-700 border border-brand-200 hover:bg-brand-50',
        'ghost'     => 'bg-transparent text-slate-600 hover:bg-slate-100',
        'danger'    => 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm',
        'success'   => 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm',
    ];
    $sizes = [
        'sm'   => 'h-8 px-3 text-xs gap-1.5',
        'md'   => 'h-10 px-4 text-sm gap-2',
        'icon' => 'h-9 w-9 text-sm justify-center',
    ];
    $base = 'inline-flex items-center justify-center rounded-lg font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:opacity-50 disabled:pointer-events-none';
    $cls  = trim("$base " . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif
