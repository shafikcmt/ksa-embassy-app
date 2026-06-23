@props([
    'padded' => true,   // apply default padding to the body
])

{{-- shadcn-style surface card --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-soft']) }}>
    {{ $slot }}
</div>
