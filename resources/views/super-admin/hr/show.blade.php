@extends('layouts.super-admin-app')
@section('title', $hr->full_name_en)
@section('page-title', 'HR Profile')

@section('content')

<x-ui.page-header :title="$hr->full_name_en" icon="bi-person-vcard">
    <x-slot:actions>
        <x-ui.status-badge :status="$hr->status" />
        <x-ui.button :href="route('super-admin.hr.documents', $hr)" variant="success" class="cursor-pointer"><i class="bi bi-file-earmark-pdf"></i> Documents</x-ui.button>
        <x-ui.button :href="route('super-admin.hr.index')" variant="secondary" class="cursor-pointer"><i class="bi bi-arrow-left"></i> Back</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

    {{-- Personal --}}
    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800"><i class="bi bi-person text-brand-600"></i> Personal Information</div>
        <div class="divide-y divide-slate-100 px-5 py-2 text-sm">
            <x-ui.dl-row label="Agency">
                <a href="{{ route('super-admin.agencies.show', $hr->agency_id) }}" class="text-brand-600 transition-colors hover:text-brand-700">{{ $hr->agency?->name ?? '—' }}</a>
            </x-ui.dl-row>
            <x-ui.dl-row label="File Number">
                @if($hr->file_number)
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">{{ $hr->file_number }}</span>
                @else — @endif
            </x-ui.dl-row>
            <x-ui.dl-row label="Full Name (EN)"><span class="font-semibold text-slate-800">{{ $hr->full_name_en }}</span></x-ui.dl-row>
            @if($hr->full_name_ar)
                <x-ui.dl-row label="Full Name (AR)" :rtl="true">{{ $hr->full_name_ar }}</x-ui.dl-row>
            @endif
            <x-ui.dl-row label="Nationality">{{ $hr->nationality }}</x-ui.dl-row>
            <x-ui.dl-row label="Date of Birth">{{ $hr->date_of_birth->format('d M Y') }}</x-ui.dl-row>
            <x-ui.dl-row label="Gender">{{ ucfirst($hr->gender) }}</x-ui.dl-row>
            <x-ui.dl-row label="Religion">{{ $hr->religion ?? '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="Marital Status">{{ $hr->marital_status ? ucfirst($hr->marital_status) : '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="Occupation">{{ $hr->occupation ?? '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="Phone">{{ $hr->phone ?? '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="Email">{{ $hr->email ?? '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="Agent">{{ $hr->agent?->name ?? '—' }}</x-ui.dl-row>
        </div>
    </x-ui.card>

    {{-- Passport --}}
    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800"><i class="bi bi-passport text-brand-600"></i> Passport</div>
        @if($hr->passport && $hr->passport->passport_number)
            <div class="divide-y divide-slate-100 px-5 py-2 text-sm">
                <x-ui.dl-row label="Passport #"><span class="font-semibold text-slate-800">{{ $hr->passport->passport_number }}</span></x-ui.dl-row>
                <x-ui.dl-row label="Type">{{ ucfirst($hr->passport->passport_type) }}</x-ui.dl-row>
                <x-ui.dl-row label="Issue Place">{{ $hr->passport->issue_place ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Issue Date">{{ $hr->passport->issue_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Expiry Date">
                    <span class="{{ $hr->passport->expiry_date?->isPast() ? 'text-rose-600' : '' }}">{{ $hr->passport->expiry_date?->format('d M Y') ?? '—' }}</span>
                </x-ui.dl-row>
            </div>
        @else
            <div class="px-5 py-6 text-center text-sm text-slate-400">No passport data.</div>
        @endif
    </x-ui.card>

    {{-- Visa --}}
    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800"><i class="bi bi-globe text-brand-600"></i> Visa</div>
        @if($hr->visa && ($hr->visa->visa_number || $hr->visa->sponsor_name))
            <div class="divide-y divide-slate-100 px-5 py-2 text-sm">
                <x-ui.dl-row label="Visa #">{{ $hr->visa->visa_number ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Type">{{ $hr->visa->visa_type ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Issue Place">{{ $hr->visa->issue_place ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Issue Date">{{ $hr->visa->issue_date ?: '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Expiry Date">{{ $hr->visa->expiry_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Sponsor Name">{{ $hr->visa->sponsor_name ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Sponsor ID">{{ $hr->visa->sponsor_id ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Border #">{{ $hr->visa->border_number ?? '—' }}</x-ui.dl-row>
            </div>
        @else
            <div class="px-5 py-6 text-center text-sm text-slate-400">No visa data.</div>
        @endif
    </x-ui.card>

    {{-- Clearance --}}
    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800"><i class="bi bi-shield-check text-brand-600"></i> Police Clearance &amp; Medical</div>
        @if($hr->clearance)
            <div class="divide-y divide-slate-100 px-5 py-2 text-sm">
                <x-ui.dl-row label="Clearance #">{{ $hr->clearance->police_clearance_number ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Issue Date">{{ $hr->clearance->clearance_issue_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Expiry Date">{{ $hr->clearance->clearance_expiry_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Country">{{ $hr->clearance->clearance_country ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Medical Fit">
                    <x-ui.badge :tone="$hr->clearance->medical_fit ? 'green' : 'slate'">{{ $hr->clearance->medical_fit ? 'Yes' : 'No' }}</x-ui.badge>
                </x-ui.dl-row>
                <x-ui.dl-row label="Medical Date">{{ $hr->clearance->medical_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Medical Center">{{ $hr->clearance->medical_center ?? '—' }}</x-ui.dl-row>
            </div>
        @else
            <div class="px-5 py-6 text-center text-sm text-slate-400">No clearance data.</div>
        @endif
    </x-ui.card>

    {{-- Other Info --}}
    <x-ui.card class="lg:col-span-2">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800"><i class="bi bi-file-text text-brand-600"></i> Contract &amp; Other Info</div>
        @if($hr->otherInfo)
            <div class="grid grid-cols-1 gap-x-8 px-5 py-2 md:grid-cols-2">
                <div class="divide-y divide-slate-100 text-sm">
                    <x-ui.dl-row label="Contract Period">{{ $hr->otherInfo->contract_period ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Salary">{{ $hr->otherInfo->salary ? 'SAR '.number_format($hr->otherInfo->salary, 2) : '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Work City">{{ $hr->otherInfo->work_city ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Employer">{{ $hr->otherInfo->employer_name ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Employer Phone">{{ $hr->otherInfo->employer_phone ?? '—' }}</x-ui.dl-row>
                </div>
                <div class="divide-y divide-slate-100 text-sm">
                    <x-ui.dl-row label="Arrival Date">{{ $hr->otherInfo->arrival_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Departure Date">{{ $hr->otherInfo->departure_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                    @if($hr->otherInfo->remarks)
                        <x-ui.dl-row label="Remarks">{{ $hr->otherInfo->remarks }}</x-ui.dl-row>
                    @endif
                </div>
            </div>
        @else
            <div class="px-5 py-6 text-center text-sm text-slate-400">No contract/other data.</div>
        @endif
    </x-ui.card>
</div>

<div class="mt-4 text-xs text-slate-400">
    Created by {{ $hr->createdBy?->name ?? '—' }} on {{ $hr->created_at->format('d M Y H:i') }}
    @if($hr->updatedBy)
        · Updated by {{ $hr->updatedBy->name }} on {{ $hr->updated_at->format('d M Y H:i') }}
    @endif
</div>
@endsection
