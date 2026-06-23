@extends('layouts.agency-app')
@section('title', 'Embassy Lists')
@section('page-title', 'Embassy Lists')

@section('content')
<div x-data="{ cancel: { open: false, no: '', action: '', finalized: false } }">

    <x-ui.page-header title="Embassy Lists"
        subtitle="{{ $monthlyCount }} this month{{ $monthlyLimit > 0 && $monthlyLimit < 999 ? ' · limit '.$monthlyLimit : '' }}"
        icon="bi-list-ol">
        <x-slot:actions>
            @can('create', \App\Models\EmbassyList::class)
                <x-ui.button :href="route('embassy-lists.create')"><i class="bi bi-plus-lg"></i> Create Embassy List</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Stat cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-ui.stat icon="bi-collection" tone="brand" label="Total Lists" :value="$totalCount" />
        <x-ui.stat icon="bi-hourglass-split" :tone="$draftCount > 0 ? 'amber' : 'slate'" label="Draft" :value="$draftCount" />
        <x-ui.stat icon="bi-check-circle" tone="green" label="Finalized" :value="$finalizedCount" />
        <x-ui.stat icon="bi-calendar3" tone="slate" label="This Month" :value="$monthlyCount" />
    </div>

    {{-- Filter bar --}}
    <x-ui.card class="mb-5">
        <form method="GET" action="{{ route('embassy-lists.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400">
                    <i class="bi bi-search text-sm text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="List no, candidate, passport…" class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                </div>
            </div>
            <select name="status" class="h-10 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
                <option value="">All status</option>
                @foreach(['draft'=>'Draft','finalized'=>'Finalized','printed'=>'Printed','cancelled'=>'Cancelled'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="From date" class="h-10 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
            <input type="date" name="date_to" value="{{ request('date_to') }}" title="To date" class="h-10 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
            <div class="flex gap-2 lg:col-span-2">
                <x-ui.button type="submit" class="flex-1"><i class="bi bi-funnel"></i> Filter</x-ui.button>
                <x-ui.button :href="route('embassy-lists.index')" variant="secondary" size="icon" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- ── Desktop table ─────────────────────────────────────── --}}
    <x-ui.card class="hidden overflow-hidden lg:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">List No</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3 text-center">Total</th>
                        <th class="px-4 py-3 text-center">New</th>
                        <th class="px-4 py-3 text-center">Re-stamp</th>
                        <th class="px-4 py-3 text-center">Cancel</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created By</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($embassyLists as $list)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-3"><a href="{{ route('embassy-lists.show', $list) }}" class="font-mono font-semibold text-slate-800 hover:text-brand-600">{{ $list->list_no }}</a></td>
                            <td class="px-4 py-3 text-slate-500">{{ $list->list_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $list->title ?: '—' }}</td>
                            <td class="px-4 py-3 text-center font-bold text-slate-800">{{ $list->total_items }}</td>
                            <td class="px-4 py-3 text-center">@if($list->total_new > 0)<span class="font-semibold text-emerald-600">{{ $list->total_new }}</span>@else<span class="text-slate-300">0</span>@endif</td>
                            <td class="px-4 py-3 text-center">@if($list->total_restamping > 0)<span class="font-semibold text-brand-600">{{ $list->total_restamping }}</span>@else<span class="text-slate-300">0</span>@endif</td>
                            <td class="px-4 py-3 text-center">@if($list->total_cancellation > 0)<span class="font-semibold text-rose-600">{{ $list->total_cancellation }}</span>@else<span class="text-slate-300">0</span>@endif</td>
                            <td class="px-4 py-3"><x-ui.status-badge :status="$list->status" /></td>
                            <td class="px-4 py-3 text-slate-400">{{ $list->createdBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('embassy-lists.show', $list) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                                    @if($list->isDraft())
                                        @can('update', $list)<a href="{{ route('embassy-lists.edit', $list) }}" title="Edit" class="grid h-8 w-8 place-items-center rounded-lg text-brand-600 hover:bg-brand-50"><i class="bi bi-pencil"></i></a>@endcan
                                        @can('finalize', $list)
                                            <form method="POST" action="{{ route('embassy-lists.finalize', $list) }}" onsubmit="return confirm('Finalize list {{ $list->list_no }}? This will mark all {{ $list->total_items }} candidates as listed.')">
                                                @csrf
                                                <button type="submit" title="Finalize" class="grid h-8 w-8 place-items-center rounded-lg text-emerald-600 hover:bg-emerald-50"><i class="bi bi-check-circle"></i></button>
                                            </form>
                                        @endcan
                                    @endif
                                    @if($list->isFinalized() || $list->status === 'printed')
                                        <a href="{{ route('embassy-lists.print', $list) }}" target="_blank" title="Print" class="grid h-8 w-8 place-items-center rounded-lg text-cyan-600 hover:bg-cyan-50"><i class="bi bi-printer"></i></a>
                                        <a href="{{ route('embassy-lists.download-pdf', $list) }}" title="Download PDF" class="grid h-8 w-8 place-items-center rounded-lg text-brand-600 hover:bg-brand-50"><i class="bi bi-file-earmark-pdf"></i></a>
                                    @endif
                                    @if(!$list->isCancelled())
                                        @can('cancel', $list)
                                            <button type="button" title="Cancel" class="grid h-8 w-8 place-items-center rounded-lg text-rose-500 hover:bg-rose-50"
                                                x-on:click="cancel.open = true; cancel.no = @js($list->list_no); cancel.action = '{{ route('embassy-lists.cancel', $list) }}'; cancel.finalized = {{ $list->isFinalized() ? 'true' : 'false' }}"><i class="bi bi-x-circle"></i></button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-0">
                            <x-ui.empty icon="bi-list-ol" title="No embassy lists found"
                                message="Adjust your filters, or create your first embassy list."
                                :actionUrl="auth()->user()->can('create', \App\Models\EmbassyList::class) ? route('embassy-lists.create') : null"
                                actionLabel="Create Embassy List" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($embassyLists->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $embassyLists->withQueryString()->links() }}</div>
        @endif
    </x-ui.card>

    {{-- ── Mobile cards ──────────────────────────────────────── --}}
    <div class="space-y-3 lg:hidden">
        @forelse($embassyLists as $list)
            <x-ui.card class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('embassy-lists.show', $list) }}" class="font-mono font-semibold text-slate-800">{{ $list->list_no }}</a>
                        <div class="text-xs text-slate-400">{{ $list->list_date->format('d M Y') }}{{ $list->title ? ' · '.$list->title : '' }}</div>
                    </div>
                    <x-ui.status-badge :status="$list->status" />
                </div>
                <div class="mt-3 flex gap-4 text-xs">
                    <span class="text-slate-500">Total <strong class="text-slate-800">{{ $list->total_items }}</strong></span>
                    <span class="text-emerald-600">New <strong>{{ $list->total_new }}</strong></span>
                    <span class="text-brand-600">Re-stamp <strong>{{ $list->total_restamping }}</strong></span>
                    <span class="text-rose-600">Cancel <strong>{{ $list->total_cancellation }}</strong></span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                    <x-ui.button :href="route('embassy-lists.show', $list)" variant="secondary" size="sm"><i class="bi bi-eye"></i> View</x-ui.button>
                    @if($list->isDraft())
                        @can('update', $list)<x-ui.button :href="route('embassy-lists.edit', $list)" variant="secondary" size="sm"><i class="bi bi-pencil"></i> Edit</x-ui.button>@endcan
                    @endif
                    @if($list->isFinalized() || $list->status === 'printed')
                        <x-ui.button :href="route('embassy-lists.download-pdf', $list)" variant="secondary" size="sm"><i class="bi bi-file-earmark-pdf"></i> PDF</x-ui.button>
                    @endif
                </div>
            </x-ui.card>
        @empty
            <x-ui.card><x-ui.empty icon="bi-list-ol" title="No embassy lists found"
                :actionUrl="auth()->user()->can('create', \App\Models\EmbassyList::class) ? route('embassy-lists.create') : null"
                actionLabel="Create Embassy List" /></x-ui.card>
        @endforelse
        @if($embassyLists->hasPages())<div class="pt-1">{{ $embassyLists->withQueryString()->links() }}</div>@endif
    </div>

    {{-- ── Cancel dialog ─────────────────────────────────────── --}}
    <div x-show="cancel.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="cancel.open = false" x-show="cancel.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="cancel.open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bi bi-x-circle text-lg"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Cancel List</h3>
                    <p class="mt-1 text-sm text-slate-500">Cancel <strong x-text="cancel.no"></strong>?</p>
                    <p x-show="cancel.finalized" class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">This list is finalized. Cancelling resets candidate statuses back to active (where not in another finalized list).</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" x-on:click="cancel.open = false">Back</x-ui.button>
                <form :action="cancel.action" method="POST">
                    @csrf
                    <x-ui.button type="submit" variant="danger" size="sm"><i class="bi bi-x-circle"></i> Cancel List</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
