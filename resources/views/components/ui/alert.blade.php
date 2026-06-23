@props([
    'type'        => 'info',   // danger | warning | info | success
    'icon'        => null,     // override the default icon for the type
    'title'       => null,     // bold lead line
    'message'     => null,     // raw HTML allowed; falls back to slot (used as supporting text)
    'action'      => null,     // url for the action button
    'actionLabel' => null,     // button label e.g. Renew / View / Review
    'badge'       => null,     // override the pill label (defaults to the type label)
    'dismissible' => false,
])

@php
    $tones = [
        'danger'  => [
            'card'    => 'border-rose-200/70 bg-rose-50/60 hover:bg-rose-50',
            'iconBox' => 'bg-rose-100 text-rose-600 ring-rose-200/80',
            'icon'    => 'bi-exclamation-octagon-fill',
            'pill'    => 'bg-rose-100 text-rose-700',
            'label'   => 'Urgent',
            'title'   => 'text-rose-950',
            'body'    => 'text-rose-700/90 [&_strong]:font-semibold [&_strong]:text-rose-900',
            'btn'     => 'bg-rose-600 text-white shadow-sm shadow-rose-600/25 hover:bg-rose-700',
            'close'   => 'text-rose-400 hover:bg-rose-100 hover:text-rose-600',
        ],
        'warning' => [
            'card'    => 'border-amber-200/70 bg-amber-50/60 hover:bg-amber-50',
            'iconBox' => 'bg-amber-100 text-amber-600 ring-amber-200/80',
            'icon'    => 'bi-exclamation-triangle-fill',
            'pill'    => 'bg-amber-100 text-amber-700',
            'label'   => 'Warning',
            'title'   => 'text-amber-950',
            'body'    => 'text-amber-700/90 [&_strong]:font-semibold [&_strong]:text-amber-900',
            'btn'     => 'bg-amber-500 text-white shadow-sm shadow-amber-500/25 hover:bg-amber-600',
            'close'   => 'text-amber-400 hover:bg-amber-100 hover:text-amber-600',
        ],
        'info'    => [
            'card'    => 'border-brand-200/70 bg-brand-50/60 hover:bg-brand-50',
            'iconBox' => 'bg-brand-100 text-brand-600 ring-brand-200/80',
            'icon'    => 'bi-info-circle-fill',
            'pill'    => 'bg-brand-100 text-brand-700',
            'label'   => 'Info',
            'title'   => 'text-brand-950',
            'body'    => 'text-slate-600 [&_strong]:font-semibold [&_strong]:text-brand-900',
            'btn'     => 'bg-white text-brand-700 ring-1 ring-inset ring-brand-200 hover:bg-brand-100',
            'close'   => 'text-brand-400 hover:bg-brand-100 hover:text-brand-600',
        ],
        'success' => [
            'card'    => 'border-emerald-200/70 bg-emerald-50/60 hover:bg-emerald-50',
            'iconBox' => 'bg-emerald-100 text-emerald-600 ring-emerald-200/80',
            'icon'    => 'bi-check-circle-fill',
            'pill'    => 'bg-emerald-100 text-emerald-700',
            'label'   => 'Done',
            'title'   => 'text-emerald-950',
            'body'    => 'text-slate-600 [&_strong]:font-semibold [&_strong]:text-emerald-900',
            'btn'     => 'bg-white text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100',
            'close'   => 'text-emerald-400 hover:bg-emerald-100 hover:text-emerald-600',
        ],
    ];
    $t = $tones[$type] ?? $tones['info'];
@endphp

<div @if($dismissible) x-data="{ show: true }" x-show="show" x-transition.opacity @endif
     {{ $attributes->merge(['class' => "group flex flex-col gap-3 rounded-2xl border p-3.5 transition-colors sm:flex-row sm:items-center {$t['card']}"]) }}>

    {{-- icon + message --}}
    <div class="flex min-w-0 flex-1 items-start gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl text-lg ring-1 ring-inset {{ $t['iconBox'] }}">
            <i class="bi {{ $icon ?? $t['icon'] }}"></i>
        </span>
        <div class="min-w-0 flex-1 pt-0.5">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                @if($title)
                    <span class="text-sm font-semibold leading-snug {{ $t['title'] }}">{{ $title }}</span>
                @endif
                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wide {{ $t['pill'] }}">{{ $badge ?? $t['label'] }}</span>
            </div>
            <div class="mt-1 text-[0.82rem] leading-snug {{ $t['body'] }}">
                @if($slot->isNotEmpty()){{ $slot }}@else{!! $message !!}@endif
            </div>
        </div>
    </div>

    {{-- actions --}}
    @if(($action && $actionLabel) || $dismissible)
        <div class="flex shrink-0 items-center gap-1.5 pl-[3.25rem] sm:pl-0">
            @if($action && $actionLabel)
                <a href="{{ $action }}" class="inline-flex h-8 items-center gap-1.5 whitespace-nowrap rounded-lg px-3 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 {{ $t['btn'] }}">
                    {{ $actionLabel }} <i class="bi bi-arrow-right text-[0.7rem] transition group-hover:translate-x-0.5"></i>
                </a>
            @endif
            @if($dismissible)
                <button type="button" @click="show = false" aria-label="Dismiss" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg transition {{ $t['close'] }}">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            @endif
        </div>
    @endif
</div>
