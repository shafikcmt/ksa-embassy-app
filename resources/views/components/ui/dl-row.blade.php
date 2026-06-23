@props([
    'label' => '',
    'rtl'   => false,
])

<div class="flex items-start justify-between gap-4 py-2">
    <dt class="shrink-0 text-slate-400">{{ $label }}</dt>
    <dd @class(['text-right font-medium text-slate-700', 'text-left' => $rtl]) @if($rtl) dir="rtl" @endif>{{ $slot }}</dd>
</div>
