@props([
    'icon'        => 'bi-inbox',
    'title'       => 'Nothing here yet',
    'message'     => null,
    'actionUrl'   => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <span class="mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
        <i class="bi {{ $icon }}"></i>
    </span>
    <h3 class="text-sm font-semibold text-slate-700">{{ $title }}</h3>
    @if($message)
        <p class="mt-1 max-w-sm text-sm text-slate-400">{{ $message }}</p>
    @endif
    @if($actionUrl && $actionLabel)
        <x-ui.button :href="$actionUrl" size="sm" class="mt-4">
            <i class="bi bi-plus-lg"></i> {{ $actionLabel }}
        </x-ui.button>
    @endif
</div>
