@extends('layouts.agency-app')
@section('title', $embassyList->list_no)
@section('page-title', 'Embassy List')

@section('content')
<div x-data="{ cancelOpen: false }">

    {{-- Header --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('embassy-lists.index') }}" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50"><i class="bi bi-arrow-left"></i></a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-mono text-xl font-bold text-slate-900">{{ $embassyList->list_no }}</h1>
                    <x-ui.status-badge :status="$embassyList->status" />
                </div>
                <p class="text-sm text-slate-500">{{ $embassyList->list_date->format('d F Y') }}@if($embassyList->title) · {{ $embassyList->title }}@endif</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($embassyList->isDraft())
                @can('update', $embassyList)<x-ui.button :href="route('embassy-lists.edit', $embassyList)" variant="secondary" size="sm"><i class="bi bi-pencil"></i> Edit</x-ui.button>@endcan
                @can('finalize', $embassyList)
                    @if($embassyList->total_items > 0)
                        <form method="POST" action="{{ route('embassy-lists.finalize', $embassyList) }}" onsubmit="return confirm('Finalize this list? All {{ $embassyList->total_items }} candidates will be marked as listed.')">
                            @csrf
                            <x-ui.button type="submit" variant="success" size="sm"><i class="bi bi-check-circle"></i> Finalize</x-ui.button>
                        </form>
                    @endif
                @endcan
            @endif
            @if($embassyList->isFinalized() || $embassyList->status === 'printed')
                <x-ui.button :href="route('embassy-lists.print', $embassyList)" variant="secondary" size="sm" target="_blank"><i class="bi bi-printer"></i> Print Preview</x-ui.button>
                <x-ui.button :href="route('embassy-lists.download-pdf', $embassyList)" size="sm"><i class="bi bi-file-earmark-pdf"></i> Download PDF</x-ui.button>
            @endif
            @if(!$embassyList->isCancelled())
                @can('cancel', $embassyList)<x-ui.button type="button" variant="secondary" size="sm" x-on:click="cancelOpen = true" class="!text-rose-600"><i class="bi bi-x-circle"></i> Cancel</x-ui.button>@endcan
            @endif
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-ui.stat icon="bi-people-fill" tone="slate" label="Total Candidates" :value="$embassyList->total_items" />
        <x-ui.stat icon="bi-plus-circle" tone="green" label="New" :value="$embassyList->total_new" />
        <x-ui.stat icon="bi-arrow-repeat" tone="brand" label="Re-stamping" :value="$embassyList->total_restamping" />
        <x-ui.stat icon="bi-x-circle" tone="red" label="Cancellation" :value="$embassyList->total_cancellation" />
    </div>

    {{-- Category tables --}}
    @php
        $categoryOrder  = ['restamping', 'new', 'cancellation'];
        $categoryLabels = ['restamping' => 'Re-stamping', 'new' => 'New', 'cancellation' => 'Cancellation'];
        $categoryTones  = ['restamping' => 'brand', 'new' => 'green', 'cancellation' => 'red'];
        $categoryDots   = ['restamping' => 'bg-brand-500', 'new' => 'bg-emerald-500', 'cancellation' => 'bg-rose-500'];
    @endphp

    @foreach($categoryOrder as $category)
        @if(isset($itemsByCategory[$category]) && $itemsByCategory[$category]->count() > 0)
            @php $catCount = $itemsByCategory[$category]->count(); @endphp
            <x-ui.card class="mb-4 overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <span class="h-2.5 w-2.5 rounded-full {{ $categoryDots[$category] }}"></span>
                        {{ $categoryLabels[$category] }} Applications
                    </h2>
                    <x-ui.badge :tone="$categoryTones[$category]">{{ $catCount }} {{ \Illuminate\Support\Str::plural('candidate', $catCount) }}</x-ui.badge>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="w-12 px-4 py-2.5">SL</th>
                                <th class="px-4 py-2.5">Agent Name</th>
                                <th class="px-4 py-2.5">Candidate Name</th>
                                <th class="px-4 py-2.5">Passport No.</th>
                                <th class="px-4 py-2.5">Visa No.</th>
                                <th class="px-4 py-2.5">Profession</th>
                                <th class="px-4 py-2.5">Sponsor ID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($itemsByCategory[$category] as $item)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2.5 font-semibold text-slate-400">{{ $item->serial_no ?: '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-600">{{ $item->snapshot_agent_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-semibold text-slate-800">{{ $item->snapshot_candidate_name }}</div>
                                        @if($item->snapshot_candidate_name_ar)<div class="text-xs text-slate-400" dir="rtl">{{ $item->snapshot_candidate_name_ar }}</div>@endif
                                    </td>
                                    <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $item->snapshot_passport_no ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $item->snapshot_visa_no ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-600">{{ $item->snapshot_profession_en ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $item->snapshot_sponsor_id ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif
    @endforeach

    @if($embassyList->total_items === 0)
        <x-ui.card>
            <x-ui.empty icon="bi-person-x" title="No candidates in this list"
                :message="$embassyList->isDraft() ? 'Add candidates to build this embassy list.' : null"
                :actionUrl="$embassyList->isDraft() ? route('embassy-lists.edit', $embassyList) : null"
                :actionLabel="$embassyList->isDraft() ? 'Add Candidates' : null" />
        </x-ui.card>
    @endif

    @if($embassyList->notes)
        <x-ui.card class="mt-4 p-4 text-sm text-slate-600"><strong class="text-slate-700">Notes:</strong> {{ $embassyList->notes }}</x-ui.card>
    @endif

    <p class="mt-4 text-xs text-slate-400">
        Created by {{ $embassyList->createdBy?->name ?? '—' }} on {{ $embassyList->created_at->format('d M Y H:i') }}
        @if($embassyList->finalized_at) · Finalized {{ $embassyList->finalized_at->format('d M Y H:i') }}@endif
        @if($embassyList->printed_at) · Printed {{ $embassyList->printed_at->format('d M Y H:i') }}@endif
    </p>

    {{-- Cancel dialog --}}
    @can('cancel', $embassyList)
        <div x-show="cancelOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
            <div @click="cancelOpen = false" x-show="cancelOpen" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
            <div x-show="cancelOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="text-base font-semibold text-slate-900">Cancel list {{ $embassyList->list_no }}?</h3>
                @if($embassyList->isFinalized())
                    <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700"><strong>Warning:</strong> This is a finalized list. Cancelling will reset candidate statuses back to active (where not in another finalized list).</p>
                @endif
                <div class="mt-5 flex justify-end gap-2">
                    <x-ui.button type="button" variant="secondary" size="sm" x-on:click="cancelOpen = false">Back</x-ui.button>
                    <form method="POST" action="{{ route('embassy-lists.cancel', $embassyList) }}">
                        @csrf
                        <x-ui.button type="submit" variant="danger" size="sm"><i class="bi bi-x-circle"></i> Cancel List</x-ui.button>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection
