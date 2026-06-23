@props([
    'title'    => '',
    'subtitle' => null,
    'icon'     => null,
])

<div {{ $attributes->merge(['class' => 'mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="flex items-start gap-3">
        @if($icon)
            <span class="hidden h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-lg text-brand-600 sm:grid">
                <i class="bi {{ $icon }}"></i>
            </span>
        @endif
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
