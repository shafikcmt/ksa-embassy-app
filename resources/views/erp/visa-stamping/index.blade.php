@extends('layouts.erp-app')

@section('title', 'Visa Stamping')
@section('page-title', 'Visa Stamping')

@section('content')
@php
    $isAdmin   = auth()->user()->isAgencyAdmin();
    $highlight = (int) session('stamping_highlight');
    $activeFilters = collect([$filters['q'] !== '', $filters['status'] !== '', (bool) $filters['agent_id'], $filters['from'] !== '', $filters['to'] !== ''])->filter()->count();
    $hasFilters = $activeFilters > 0 || $filters['trashed'];
    $listQuery = array_filter([
        'q' => $filters['q'], 'status' => $filters['status'], 'agent_id' => $filters['agent_id'],
        'date_field' => $filters['date_field'], 'from' => $filters['from'], 'to' => $filters['to'],
        'sort' => $filters['sort'], 'dir' => $filters['dir'], 'trashed' => $filters['trashed'] ? 1 : null,
    ]);
    $d = fn ($date) => $date?->format('d-M-Y') ?? '—';

    $ctl   = 'block w-full rounded-md border border-gray-300 bg-white py-2.5 text-sm text-gray-800 shadow-sm transition duration-150 focus:border-blue-500 focus:outline-none focus:ring-[3px] focus:ring-blue-500/10';
    $lbl   = 'mb-1.5 block text-[13px] font-semibold text-gray-700';
    $ghost = 'inline-flex items-center justify-center gap-1.5 rounded-md border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition duration-200 hover:bg-gray-50 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500';
    $addBtn = 'inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition duration-200 hover:bg-emerald-600 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2';
    $icon  = 'grid h-8 w-8 place-items-center rounded-md transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500';
    // Column visibility: mobile = Name, PP No, Status, Actions; tablet adds Exp. Date + Left Day; desktop = all.
    $xl = 'hidden xl:table-cell';
    $md = 'hidden md:table-cell';

    $cards = [
        ['Total Entries', $stats['total'],   'bi-postage',         '',        'bg-blue-50 text-blue-600',       'text-blue-950',    'border-blue-100'],
        ['Stamped',       $stats['stamped'], 'bi-patch-check',     'stamped', 'bg-emerald-50 text-emerald-600', 'text-emerald-900', 'border-emerald-100'],
        ['Pending',       $stats['pending'], 'bi-hourglass-split', 'pending', 'bg-amber-50 text-amber-600',     'text-amber-900',   'border-amber-100'],
        ['Expired',       $stats['expired'], 'bi-calendar-x',      'expired', 'bg-red-50 text-red-600',         'text-red-900',     'border-red-100'],
    ];
@endphp

{{-- Header --}}
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-blue-950 sm:text-3xl">Visa Stamping</h1>
        <p class="mt-1 text-sm text-gray-500">Manage visa stamping records and compliance</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-xs font-medium text-gray-500"><i class="bi bi-calendar3 mr-1" aria-hidden="true"></i>Generated: {{ now()->format('d-M-Y') }}</span>
        <button type="button" x-data x-on:click="$dispatch('stamping-add')" class="{{ $addBtn }} hidden sm:inline-flex">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Add New
        </button>
    </div>
</div>

{{-- Stats --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach($cards as [$label, $count, $ico, $status, $chip, $num, $border])
        @php $active = $status !== '' && $filters['status'] === $status; @endphp
        <a href="{{ route('erp.visa-stamping.index', $status ? ['status' => $status] : []) }}"
           class="flex items-center gap-4 rounded-xl border bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {{ $active ? 'ring-2 ring-blue-500' : '' }} {{ $border }}">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl text-xl {{ $chip }}" aria-hidden="true"><i class="bi {{ $ico }}"></i></span>
            <span>
                <span class="block text-[13px] font-semibold text-gray-500">{{ $label }}</span>
                <span class="block text-2xl font-bold {{ $num }}">{{ number_format($count) }}</span>
            </span>
        </a>
    @endforeach
</div>

{{-- Search & filters (search submits 300ms after typing stops) --}}
<form method="GET" action="{{ route('erp.visa-stamping.index') }}" role="search" x-data="{ t: null }"
      class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    @if($filters['trashed'])<input type="hidden" name="trashed" value="1">@endif
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12 xl:items-end">
        <div class="xl:col-span-4">
            <label for="vf_q" class="{{ $lbl }}">Search</label>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400" aria-hidden="true"></i>
                <input id="vf_q" type="search" name="q" value="{{ $filters['q'] }}" @if($filters['q'] !== '') autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)" @endif
                       x-on:input="clearTimeout(t); t = setTimeout(() => $el.form.requestSubmit(), 300)"
                       placeholder="Name, passport, visa no, MOFA no, reference…" class="{{ $ctl }} pl-9 pr-3">
            </div>
        </div>
        <div class="xl:col-span-2">
            <label for="vf_status" class="{{ $lbl }}">Status</label>
            <select id="vf_status" name="status" class="{{ $ctl }} px-3" onchange="this.form.requestSubmit()">
                <option value="">All</option>
                @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="xl:col-span-2">
            <label for="vf_agent" class="{{ $lbl }}">Agent</label>
            <select id="vf_agent" name="agent_id" class="{{ $ctl }} px-3" onchange="this.form.requestSubmit()">
                <option value="">All agents</option>
                @foreach($agents as $a)<option value="{{ $a->id }}" @selected($filters['agent_id'] === $a->id)>{{ $a->name }}</option>@endforeach
            </select>
        </div>
        <div class="xl:col-span-4">
            <span class="{{ $lbl }}" id="vf_range_lbl">Date range</span>
            <div class="grid grid-cols-3 gap-2" role="group" aria-labelledby="vf_range_lbl">
                <select name="date_field" aria-label="Date field" class="{{ $ctl }} px-2">
                    @foreach($dateFields as $key => $label)<option value="{{ $key }}" @selected($filters['date_field'] === $key)>{{ $label }}</option>@endforeach
                </select>
                <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="From date" class="{{ $ctl }} px-2">
                <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="To date" class="{{ $ctl }} px-2">
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md bg-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-200 hover:bg-blue-600 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                <i class="bi bi-funnel" aria-hidden="true"></i> Filter
                @if($activeFilters)<span class="grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-white px-1 text-[11px] font-bold text-blue-600" aria-label="{{ $activeFilters }} active filters">{{ $activeFilters }}</span>@endif
            </button>
            <label for="vf_sort" class="sr-only">Sort by</label>
            <select id="vf_sort" name="sort" onchange="this.form.requestSubmit()"
                    class="rounded-md border border-gray-300 bg-white py-2.5 pl-3 pr-8 text-sm text-gray-800 shadow-sm transition duration-150 focus:border-blue-500 focus:outline-none focus:ring-[3px] focus:ring-blue-500/10">
                @foreach($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>Sort: {{ $label }}</option>@endforeach
            </select>
            {{-- Current direction rides along on every submit; the toggle's own dir (sent later) wins when clicked. --}}
            <input type="hidden" name="dir" value="{{ $filters['dir'] }}">
            <button type="submit" name="dir" value="{{ $filters['dir'] === 'asc' ? 'desc' : 'asc' }}" class="{{ $ghost }} px-3"
                    title="{{ $filters['dir'] === 'asc' ? 'Ascending — switch to descending' : 'Descending — switch to ascending' }}"
                    aria-label="{{ $filters['dir'] === 'asc' ? 'Sorted ascending, switch to descending' : 'Sorted descending, switch to ascending' }}">
                <i class="bi {{ $filters['dir'] === 'asc' ? 'bi-sort-up' : 'bi-sort-down' }}" aria-hidden="true"></i>
            </button>
            @if($hasFilters)
                <a href="{{ route('erp.visa-stamping.index') }}" class="inline-flex items-center gap-1.5 rounded-md px-3 py-2.5 text-sm font-semibold text-gray-500 transition hover:text-gray-800"><i class="bi bi-x-lg" aria-hidden="true"></i> Clear</a>
            @endif
            @if($isAdmin)
                @if($filters['trashed'])
                    <a href="{{ route('erp.visa-stamping.index') }}" class="inline-flex items-center gap-1.5 px-2 text-sm font-semibold text-blue-600 hover:underline"><i class="bi bi-arrow-left" aria-hidden="true"></i> Active entries</a>
                @else
                    <a href="{{ route('erp.visa-stamping.index', ['trashed' => 1]) }}" class="inline-flex items-center gap-1.5 px-2 text-sm font-semibold text-gray-500 hover:text-gray-800"><i class="bi bi-trash3" aria-hidden="true"></i> Deleted</a>
                @endif
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($isAdmin)
                <a href="{{ route('erp.stamping.import.form') }}" class="{{ $ghost }}"><i class="bi bi-upload" aria-hidden="true"></i> Import</a>
            @endif
            <a href="{{ route('erp.visa-stamping.print', $listQuery) }}" target="_blank" class="{{ $ghost }}"><i class="bi bi-printer" aria-hidden="true"></i> Print</a>
            <a href="{{ route('erp.visa-stamping.export', $listQuery) }}" class="{{ $ghost }}"><i class="bi bi-filetype-csv text-emerald-600" aria-hidden="true"></i> Export CSV</a>
            <button type="button" x-on:click="$dispatch('stamping-add')" class="{{ $addBtn }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add New</button>
        </div>
    </div>
</form>

@if($filters['trashed'])
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <i class="bi bi-trash3" aria-hidden="true"></i> Showing deleted entries. Restore one to bring it back into the list.
    </div>
@endif

@if($entries->isEmpty())
    <div class="flex flex-col items-center rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm">
        <svg class="mb-5 h-36 w-36" viewBox="0 0 160 160" fill="none" aria-hidden="true">
            <circle cx="80" cy="80" r="72" fill="#eff6ff"/>
            <rect x="40" y="36" width="64" height="88" rx="8" fill="#fff" stroke="#bfdbfe" stroke-width="3"/>
            <rect x="52" y="50" width="40" height="5" rx="2.5" fill="#dbeafe"/>
            <rect x="52" y="62" width="28" height="5" rx="2.5" fill="#dbeafe"/>
            <rect x="52" y="74" width="34" height="5" rx="2.5" fill="#dbeafe"/>
            <g transform="rotate(-14 104 100)">
                <rect x="78" y="82" width="52" height="34" rx="6" fill="#fff" stroke="#10b981" stroke-width="3" stroke-dasharray="5 3"/>
                <text x="104" y="104" text-anchor="middle" font-family="sans-serif" font-size="11" font-weight="700" fill="#10b981">VISA</text>
            </g>
        </svg>
        @if($hasFilters)
            <h2 class="text-lg font-bold text-blue-950">No entries match these filters</h2>
            <p class="mt-1 text-sm text-gray-500">Try a different search, status, agent or date range.</p>
            <a href="{{ route('erp.visa-stamping.index') }}" class="{{ $ghost }} mt-5"><i class="bi bi-x-lg" aria-hidden="true"></i> Clear filters</a>
        @else
            <h2 class="text-lg font-bold text-blue-950">No visa stamping entries yet</h2>
            <p class="mt-1 text-sm text-gray-500">Add your first stamping record to start tracking.</p>
            <button type="button" x-data x-on:click="$dispatch('stamping-add')" class="{{ $addBtn }} mt-5"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Entry</button>
        @endif
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[13px] text-gray-800">
                <caption class="sr-only">Visa stamping entries</caption>
                <thead class="border-b border-gray-200 bg-gray-50 text-left text-sm font-semibold text-gray-700">
                    <tr class="whitespace-nowrap">
                        <th scope="col" class="{{ $md }} p-3">SL</th>
                        <th scope="col" class="p-3">Passenger Name</th>
                        <th scope="col" class="{{ $xl }} p-3">Father's Name</th>
                        <th scope="col" class="{{ $xl }} p-3">Mother's Name</th>
                        <th scope="col" class="p-3">PP No</th>
                        <th scope="col" class="{{ $xl }} p-3">D.O.B</th>
                        <th scope="col" class="{{ $xl }} p-3 text-center">Age</th>
                        <th scope="col" class="{{ $xl }} p-3">Visa No</th>
                        <th scope="col" class="{{ $xl }} p-3">Id No</th>
                        <th scope="col" class="{{ $xl }} p-3">Mofa No</th>
                        <th scope="col" class="{{ $xl }} p-3">Mofa Date</th>
                        <th scope="col" class="{{ $xl }} p-3">Issu Visa No</th>
                        <th scope="col" class="{{ $xl }} p-3">Issu Date</th>
                        <th scope="col" class="{{ $md }} p-3">Exp. Date</th>
                        <th scope="col" class="{{ $md }} p-3 text-center">Left Day</th>
                        <th scope="col" class="p-3">Status</th>
                        <th scope="col" class="{{ $xl }} p-3">Reference</th>
                        <th scope="col" class="sticky right-0 bg-gray-50 p-3 text-right shadow-[-8px_0_8px_-8px_rgba(15,23,42,0.15)]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $e)
                        @php
                            $isNew = $highlight === $e->id;
                            $left  = $e->left_day;
                        @endphp
                        <tr class="border-b-[0.5px] border-gray-200 transition-colors duration-150 last:border-0 odd:bg-white even:bg-gray-100/60 hover:bg-blue-50/60 {{ $isNew ? '!bg-emerald-50' : '' }}">
                            <td class="{{ $md }} p-3 text-gray-400">{{ $entries->firstItem() + $loop->index }}</td>
                            <td class="p-3 font-semibold text-gray-900">
                                @if($filters['trashed'])
                                    {{ $e->full_name }}
                                @else
                                    <a href="{{ route('erp.visa-stamping.show', $e) }}" class="hover:text-blue-600 hover:underline">{{ $e->full_name }}</a>
                                @endif
                            </td>
                            <td class="{{ $xl }} p-3">{{ $e->father_name ?: '—' }}</td>
                            <td class="{{ $xl }} p-3">{{ $e->mother_name ?: '—' }}</td>
                            <td class="whitespace-nowrap p-3 font-mono text-xs">{{ $e->passport_no }}</td>
                            <td class="{{ $xl }} whitespace-nowrap p-3">{{ $d($e->date_of_birth) }}</td>
                            <td class="{{ $xl }} p-3 text-center">{{ $e->age ?? '—' }}</td>
                            <td class="{{ $xl }} whitespace-nowrap p-3">{{ $e->visa_number ?: '—' }}</td>
                            <td class="{{ $xl }} whitespace-nowrap p-3">{{ $e->id_number ?: '—' }}</td>
                            <td class="{{ $xl }} whitespace-nowrap p-3">{{ $e->mofa_number ?: '—' }}</td>
                            <td class="{{ $xl }} whitespace-nowrap p-3">{{ $d($e->mofa_date) }}</td>
                            <td class="{{ $xl }} whitespace-nowrap p-3">{{ $e->issued_visa_number ?: '—' }}</td>
                            <td class="{{ $xl }} whitespace-nowrap p-3">{{ $d($e->issued_date) }}</td>
                            <td class="{{ $md }} whitespace-nowrap p-3 {{ $e->expiry_date?->isPast() ? 'font-semibold text-red-600' : '' }}">{{ $d($e->expiry_date) }}</td>
                            <td class="{{ $md }} p-3 text-center">
                                @if($left === null)
                                    <span class="text-gray-400">—</span>
                                @else
                                    <span class="inline-flex items-center gap-1 font-semibold {{ $e->leftDayIsLow() ? 'text-red-600' : 'text-gray-800' }}">
                                        @if($e->leftDayIsLow())<i class="bi bi-exclamation-triangle-fill text-xs" aria-hidden="true"></i><span class="sr-only">Low:</span>@endif{{ $left }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3">
                                <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $e->statusTone() }} {{ $isNew ? 'animate-[pulse_2s_ease-in-out_3]' : '' }}">
                                    <i class="bi {{ $e->statusIcon() }}" aria-hidden="true"></i>{{ $e->statusLabel() }}
                                </span>
                            </td>
                            <td class="{{ $xl }} p-3">{{ $e->reference ?: '—' }}</td>
                            <td class="sticky right-0 bg-inherit p-3 shadow-[-8px_0_8px_-8px_rgba(15,23,42,0.15)]">
                                <div class="flex flex-nowrap items-center justify-end gap-1">
                                    @if($filters['trashed'])
                                        <form method="POST" action="{{ route('erp.visa-stamping.restore', $e->id) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 transition hover:bg-emerald-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Restore</button>
                                        </form>
                                    @else
                                        <a href="{{ route('erp.visa-stamping.show', $e) }}" class="{{ $icon }} text-gray-500 hover:bg-gray-100 hover:text-gray-800" title="View" aria-label="View {{ $e->full_name }}"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                        <button type="button" x-data x-on:click="$dispatch('stamping-edit', { id: {{ $e->id }} })" class="{{ $icon }} text-blue-500 hover:bg-blue-50 hover:text-blue-700" title="Edit" aria-label="Edit {{ $e->full_name }}"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                                        <a href="{{ route('erp.visa-stamping.print-pdf', $e) }}" target="_blank" class="{{ $icon }} text-gray-500 hover:bg-gray-100 hover:text-gray-800" title="Print" aria-label="Print {{ $e->full_name }}"><i class="bi bi-printer" aria-hidden="true"></i></a>
                                        <form method="POST" action="{{ route('erp.visa-stamping.destroy', $e) }}" onsubmit="return confirm('Delete this visa stamping entry? An admin can restore it later.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="{{ $icon }} text-red-500 hover:bg-red-50 hover:text-red-700" title="Delete" aria-label="Delete {{ $e->full_name }}"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="border-t border-gray-200 bg-white px-4 py-3">{{ $entries->onEachSide(1)->links() }}</div>
        @endif
    </div>
@endif

{{-- Floating Add button --}}
<button type="button" x-data x-on:click="$dispatch('stamping-add')" aria-label="Add visa stamping entry" title="Add visa stamping entry"
        class="fixed bottom-6 right-6 z-40 grid h-14 w-14 place-items-center rounded-full bg-emerald-500 text-2xl text-white shadow-lg shadow-emerald-500/30 transition duration-200 hover:scale-105 hover:bg-emerald-600 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/50">
    <i class="bi bi-plus-lg" aria-hidden="true"></i>
</button>

{{-- Toasts --}}
@php
    $initialToasts = array_values(array_filter([
        session('stamping_toast') ? ['type' => 'success', 'message' => session('stamping_toast')] : null,
        session('stamping_toast_error') ? ['type' => 'error', 'message' => session('stamping_toast_error')] : null,
    ]));
@endphp
<script type="application/json" id="stamping-toast-initial">{!! json_encode($initialToasts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
<div x-data="stampingToasts()" x-on:stamping-toast.window="push($event.detail)"
     class="pointer-events-none fixed right-4 top-4 z-[80] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2 sm:bottom-24 sm:right-6 sm:top-auto" aria-live="polite">
    <template x-for="t in items" :key="t.id">
        <div x-show="t.visible"
             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             x-bind:role="t.type === 'error' ? 'alert' : 'status'"
             class="pointer-events-auto flex items-start gap-3 rounded-lg border-l-4 px-4 py-3 text-sm shadow-lg"
             x-bind:class="t.type === 'error' ? 'border-red-500 bg-red-100 text-red-900' : 'border-emerald-500 bg-emerald-100 text-emerald-900'">
            <i class="bi mt-0.5 text-base" aria-hidden="true" x-bind:class="t.type === 'error' ? 'bi-x-octagon-fill text-red-500' : 'bi-check-circle-fill text-emerald-500'"></i>
            <span class="flex-1 font-medium" x-text="t.message"></span>
            <button type="button" x-on:click="dismiss(t)" aria-label="Dismiss notification" class="opacity-60 transition hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"><i class="bi bi-x-lg text-xs" aria-hidden="true"></i></button>
        </div>
    </template>
</div>

@include('erp.visa-stamping._modal')

@push('scripts')
<script>
    function stampingToasts() {
        let seq = 0;
        return {
            items: [],
            init() {
                JSON.parse(document.getElementById('stamping-toast-initial').textContent).forEach(t => this.push(t));
            },
            push({ type = 'success', message = '' }) {
                const id = ++seq;
                this.items.push({ id, type, message, visible: true });
                setTimeout(() => this.dismiss({ id }), type === 'error' ? 5000 : 3000);
            },
            dismiss({ id }) {
                const t = this.items.find(i => i.id === id);
                if (!t) return;
                t.visible = false;
                setTimeout(() => { this.items = this.items.filter(i => i.id !== id); }, 200);
            },
        };
    }
</script>
@endpush
@endsection
