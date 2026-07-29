@extends('layouts.super-admin-app')
@section('title', $embassyList->list_no)
@section('page-title', 'Embassy List')

@section('content')

<x-ui.page-header icon="bi-list-ol">
    <x-slot:title>
        <span class="font-mono">{{ $embassyList->list_no }}</span>
    </x-slot:title>
    <x-slot:subtitle>
        {{ $embassyList->list_date->format('d F Y') }}
        @if($embassyList->title) · {{ $embassyList->title }} @endif
        · <a href="{{ route('super-admin.agencies.show', $embassyList->agency_id) }}" class="text-brand-600 transition-colors hover:text-brand-700">{{ $embassyList->agency?->name }}</a>
    </x-slot:subtitle>
    <x-slot:actions>
        <x-ui.status-badge :status="$embassyList->status" />
        @if($embassyList->isFinalized() || $embassyList->status === 'printed')
            <x-ui.button :href="route('embassy-lists.print', $embassyList)" variant="secondary" class="cursor-pointer" target="_blank"><i class="bi bi-printer"></i> Print Preview</x-ui.button>
            <x-ui.button :href="route('super-admin.embassy-lists.download-pdf', $embassyList)" class="cursor-pointer"><i class="bi bi-file-earmark-pdf"></i> Download PDF</x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

{{-- Summary Cards --}}
<div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
    <x-ui.stat icon="bi-people" tone="slate" label="Total Candidates" :value="$embassyList->total_items" />
    <x-ui.stat icon="bi-plus-circle" tone="green" label="New" :value="$embassyList->total_new" />
    <x-ui.stat icon="bi-arrow-repeat" tone="brand" label="Re-stamping" :value="$embassyList->total_restamping" />
    <x-ui.stat icon="bi-x-circle" tone="red" label="Cancellation" :value="$embassyList->total_cancellation" />
</div>

{{-- Candidate Tables by Category --}}
@php
    $categoryOrder  = ['restamping', 'new', 'cancellation'];
    $categoryLabels = ['restamping' => 'Re-stamping', 'new' => 'New', 'cancellation' => 'Cancellation'];
    $categoryTones  = ['restamping' => 'brand', 'new' => 'green', 'cancellation' => 'red'];
@endphp

@foreach($categoryOrder as $category)
    @if(isset($itemsByCategory[$category]) && $itemsByCategory[$category]->count() > 0)
        <x-ui.card class="mb-5 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-800">
                    <x-ui.badge :tone="$categoryTones[$category]">{{ $categoryLabels[$category] }}</x-ui.badge>
                    {{ $categoryLabels[$category] }} Applications
                </span>
                <x-ui.badge :tone="$categoryTones[$category]">{{ $itemsByCategory[$category]->count() }} candidates</x-ui.badge>
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
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-4 py-2.5 font-semibold text-slate-400">{{ $item->serial_no ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $item->snapshot_agent_name ?? '—' }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold text-slate-800">{{ $item->snapshot_candidate_name }}</div>
                                    @if($item->snapshot_candidate_name_ar)
                                        <div class="text-xs text-slate-400" dir="rtl">{{ $item->snapshot_candidate_name_ar }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    @if($item->snapshot_passport_no)
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">{{ $item->snapshot_passport_no }}</span>
                                    @else <span class="text-slate-300">—</span> @endif
                                </td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $item->snapshot_visa_no ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $item->snapshot_profession_en ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-400">{{ $item->snapshot_sponsor_id ?? '—' }}</td>
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
        <x-ui.empty icon="bi-person-x" title="No candidates in this list" />
    </x-ui.card>
@endif

@if($embassyList->notes)
    <x-ui.card class="mt-3">
        <div class="px-5 py-3 text-sm text-slate-500"><strong class="text-slate-700">Notes:</strong> {{ $embassyList->notes }}</div>
    </x-ui.card>
@endif

<div class="mt-4 text-xs text-slate-400">
    Created by {{ $embassyList->createdBy?->name ?? '—' }} on {{ $embassyList->created_at->format('d M Y H:i') }}
    @if($embassyList->finalized_at) · Finalized {{ $embassyList->finalized_at->format('d M Y H:i') }} @endif
    @if($embassyList->printed_at) · Printed {{ $embassyList->printed_at->format('d M Y H:i') }} @endif
</div>
@endsection
