{{--
    ERP form section card: small icon + UPPERCASE title, then a responsive grid
    (cols=3 → 1/2/3 columns on mobile/tablet/desktop; cols=2 → 1/2).
    Optional `actions` slot renders at the right of the title row.
--}}
@props([
    'icon'  => 'bi-circle',
    'title' => '',
    'id'    => null,
    'cols'  => 3,
])
@php
    $grid = [
        2 => 'grid grid-cols-1 gap-4 sm:grid-cols-2',
        3 => 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3',
    ][(int) $cols] ?? 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3';
@endphp
<section {{ $attributes->class('rounded-xl border border-slate-200 bg-white p-4 sm:p-5') }} @if($id) aria-labelledby="{{ $id }}" @endif>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <h3 @if($id) id="{{ $id }}" @endif class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
            <i class="bi {{ $icon }} text-sm text-brand-500" aria-hidden="true"></i> {{ $title }}
        </h3>
        @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
    </div>
    <div class="{{ $grid }}">
        {{ $slot }}
    </div>
</section>
