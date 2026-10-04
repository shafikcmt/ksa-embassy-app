{{--
    ERP Add/Edit modal shell: header (icon + title + close), scrollable body,
    sticky footer (Cancel + Save/Update). Place it INSIDE the module's own
    x-data wrapper — every prop below is an Alpine expression evaluated there,
    so the module's JS (open/close/submit/validation) stays untouched.

    kind="overlay" → fixed overlay toggled by `show` (x-show); panel gets x-ref="panel".
    kind="dialog"  → native <dialog x-ref="dialog"> opened by the module via showModal().

    action="…"     → classic POST instead of fetch: a real <form method="POST"
                     x-bind:action> with @csrf (+ @method when `method` isn't POST),
                     browser `required` validation, and its own busy flag.

    size="wide"    → wider panel on tablet/desktop (max-w-5xl instead of max-w-3xl).
    dense          → tighter body padding/gap. Both are opt-in; omitted = unchanged.

    Extra attributes land on the panel/dialog element (e.g. x-on:keydown.tab).
    Slots: default = form body, `footer` = extra content above the buttons.
--}}
@props([
    'kind'     => 'overlay',
    'show'     => 'open',
    'icon'     => 'bi-pencil-square',
    'title'    => "''",
    'titleId'  => 'erp-modal-title',
    'submit'   => 'submit()',
    'close'    => 'close()',
    'busy'     => 'submitting',
    'disabled' => null,
    'edit'     => 'false',
    'loading'  => 'false',
    'action'   => null,
    'method'   => 'POST',
    'size'     => null,
    'dense'    => false,
])
@php
    // Classic POST forms have no JS save state, so the panel carries its own flag.
    $native       = $action !== null;
    $busy         = $native ? 'erpBusy' : $busy;
    $disabledExpr = $disabled ?? $busy;
    $maxW         = $size === 'wide' ? 'sm:max-w-5xl' : 'sm:max-w-3xl';
    $bodySpace    = $dense ? 'gap-3 overflow-y-auto bg-slate-50 p-3 sm:p-4' : 'gap-4 overflow-y-auto bg-slate-50 p-4 sm:p-5';
@endphp

@if($kind === 'dialog')
<dialog x-ref="dialog" x-on:cancel.prevent="{{ $close }}" aria-labelledby="{{ $titleId }}"
        {{ $attributes->class('m-0 h-[100dvh] max-h-[100dvh] w-full max-w-full flex-col overflow-hidden bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 open:flex sm:m-auto sm:h-auto sm:max-h-[90vh] '.$maxW.' sm:rounded-2xl') }}>
@else
<div x-show="{{ $show }}" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center sm:p-4" style="display:none">
    <div x-show="{{ $show }}" x-transition.opacity.duration.150ms class="absolute inset-0 bg-slate-900/50" x-on:click="{{ $close }}" aria-hidden="true"></div>
    <div x-show="{{ $show }}" x-ref="panel" role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
         @if($native) x-data="{ erpBusy: false }" @endif
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         {{ $attributes->class('relative flex h-[100dvh] w-full flex-col overflow-hidden bg-white shadow-2xl sm:h-auto sm:max-h-[90vh] '.$maxW.' sm:rounded-2xl') }}>
@endif

        {{-- Header --}}
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-4 py-3.5 sm:px-5">
            <div class="flex min-w-0 items-center gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600" aria-hidden="true"><i class="bi {{ $icon }} text-base"></i></span>
                <h2 id="{{ $titleId }}" class="truncate text-base font-semibold text-slate-900" x-text="{{ $title }}"></h2>
            </div>
            <button type="button" x-on:click="{{ $close }}" x-bind:disabled="{{ $busy }}" aria-label="Close dialog"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 disabled:opacity-50">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Loading an entry for Edit --}}
        <div x-show="{{ $loading }}" role="status" class="flex flex-1 items-center justify-center gap-3 bg-slate-50 py-24 text-sm text-slate-500">
            <i class="bi bi-arrow-repeat animate-spin text-xl text-brand-500" aria-hidden="true"></i> Loading entry…
        </div>

        @if($native)
        <form method="POST" x-bind:action="{{ $action }}" x-on:submit="erpBusy = true" x-bind:aria-busy="erpBusy ? 'true' : 'false'"
              class="flex min-h-0 flex-1 flex-col">
            @csrf
            @if(strtoupper($method) !== 'POST') @method(strtoupper($method)) @endif
        @else
        <form x-show="!({{ $loading }})" x-on:submit.prevent="{{ $submit }}" novalidate x-bind:aria-busy="{{ $busy }} ? 'true' : 'false'"
              class="flex min-h-0 flex-1 flex-col">
        @endif
            {{-- Body (scrolls; header and footer stay put) --}}
            <div class="flex min-h-0 flex-1 flex-col {{ $bodySpace }} [&>*]:shrink-0">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <div class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 sm:px-5">
                {{ $footer ?? '' }}
                <div class="flex justify-end gap-2">
                    <button type="button" x-on:click="{{ $close }}" x-bind:disabled="{{ $busy }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50">
                        Cancel
                    </button>
                    <button type="submit" x-bind:disabled="{{ $disabledExpr }}"
                            class="inline-flex min-w-[6.5rem] items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="bi bi-arrow-repeat animate-spin" x-show="{{ $busy }}" x-cloak aria-hidden="true"></i>
                        <span x-text="({{ $busy }}) ? 'Saving…' : (({{ $edit }}) ? 'Update' : 'Save')"></span>
                    </button>
                </div>
            </div>
        </form>

@if($kind === 'dialog')
</dialog>
@else
    </div>
</div>
@endif
