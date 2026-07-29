@extends('layouts.super-admin-app')
@section('title', 'Embassy Lists')
@section('page-title', 'Embassy Lists')

@section('content')

<x-ui.page-header title="Embassy Lists" subtitle="All lists across agencies" icon="bi-list-ol" />

{{-- Search & Filter --}}
<x-ui.card class="mb-5">
    <form method="GET" action="{{ route('super-admin.embassy-lists.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 transition-colors focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400">
                <i class="bi bi-search text-sm text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="List no, agency, candidate…"
                       class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
            </div>
        </div>
        <select name="agency_id" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
            <option value="">All Agencies</option>
            @foreach($agencies as $agency)
                <option value="{{ $agency->id }}" @selected(request('agency_id') == $agency->id)>{{ $agency->name }}</option>
            @endforeach
        </select>
        <select name="status" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
            <option value="">All Status</option>
            <option value="draft"     @selected(request('status') == 'draft')>Draft</option>
            <option value="finalized" @selected(request('status') == 'finalized')>Finalized</option>
            <option value="printed"   @selected(request('status') == 'printed')>Printed</option>
            <option value="cancelled" @selected(request('status') == 'cancelled')>Cancelled</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
            class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
        <input type="date" name="date_to" value="{{ request('date_to') }}"
            class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
        <div class="flex gap-2 lg:col-span-1">
            <x-ui.button type="submit" size="icon" title="Filter" class="cursor-pointer"><i class="bi bi-funnel"></i></x-ui.button>
            @if(request()->hasAny(['search','agency_id','status','date_from','date_to']))
                <x-ui.button :href="route('super-admin.embassy-lists.index')" variant="secondary" size="icon" title="Clear filters" class="cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
            @endif
        </div>
    </form>
</x-ui.card>

<x-ui.card class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">List No</th>
                    <th class="px-4 py-3">Agency</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3 text-center">Total</th>
                    <th class="px-4 py-3 text-center">New</th>
                    <th class="px-4 py-3 text-center">Re-stamp</th>
                    <th class="px-4 py-3 text-center">Cancel</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($embassyLists as $list)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-400">{{ $embassyLists->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('super-admin.embassy-lists.show', $list) }}" class="font-mono font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $list->list_no }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('super-admin.agencies.show', $list->agency_id) }}" class="text-slate-500 transition-colors hover:text-brand-600">{{ $list->agency?->name ?? '—' }}</a>
                        </td>
                        <td class="px-4 py-3 text-slate-400">{{ $list->list_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $list->title ?? '—' }}</td>
                        <td class="px-4 py-3 text-center font-bold text-slate-800">{{ $list->total_items }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($list->total_new > 0)<x-ui.badge tone="green">{{ $list->total_new }}</x-ui.badge>@else<span class="text-slate-300">0</span>@endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($list->total_restamping > 0)<x-ui.badge tone="brand">{{ $list->total_restamping }}</x-ui.badge>@else<span class="text-slate-300">0</span>@endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($list->total_cancellation > 0)<x-ui.badge tone="red">{{ $list->total_cancellation }}</x-ui.badge>@else<span class="text-slate-300">0</span>@endif
                        </td>
                        <td class="px-4 py-3"><x-ui.status-badge :status="$list->status" /></td>
                        <td class="px-4 py-3 text-slate-400">{{ $list->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <a href="{{ route('super-admin.embassy-lists.show', $list) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="p-0"><x-ui.empty icon="bi-list-ol" title="No embassy lists found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($embassyLists->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $embassyLists->withQueryString()->links() }}</div>
    @endif
</x-ui.card>
@endsection
