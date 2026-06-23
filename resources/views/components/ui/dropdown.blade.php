@props([
    'align' => 'right',   // right | left
    'width' => 'w-56',
])

@php
    $origin = $align === 'left' ? 'left-0 origin-top-left' : 'right-0 origin-top-right';
@endphp

<div x-data="{ open: false }" class="relative" {{ $attributes }}>
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    <div x-show="open"
         x-cloak
         @click.outside="open = false"
         @keydown.escape.window="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute {{ $origin }} {{ $width }} z-50 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
        {{ $slot }}
    </div>
</div>
