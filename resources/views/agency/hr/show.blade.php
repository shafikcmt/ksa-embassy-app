@extends('layouts.agency-app')
@section('title', $hr->full_name_en)
@section('page-title', 'HR Profile')

@section('content')
<div x-data="{ delOpen: false }">

    {{-- Header --}}
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('hr.index') }}" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50"><i class="bi bi-arrow-left"></i></a>
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-50 text-base font-bold text-brand-700">{{ strtoupper(mb_substr($hr->full_name_en, 0, 1)) }}</span>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-slate-900">{{ $hr->full_name_en }}</h1>
                    <x-ui.status-badge :status="$hr->status" />
                </div>
                @if($hr->full_name_ar)<p class="text-sm text-slate-400" dir="rtl">{{ $hr->full_name_ar }}</p>@endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button :href="route('hr.documents', $hr)" variant="secondary" size="sm"><i class="bi bi-file-earmark-pdf"></i> Documents</x-ui.button>
            @can('update', $hr)<x-ui.button :href="route('hr.edit', $hr)" size="sm"><i class="bi bi-pencil"></i> Edit</x-ui.button>@endcan
            @can('delete', $hr)<x-ui.button type="button" variant="secondary" size="sm" class="!text-rose-600" x-on:click="delOpen = true"><i class="bi bi-trash"></i> Delete</x-ui.button>@endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        {{-- Personal --}}
        <x-ui.card class="p-5">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-person text-brand-600"></i> Personal Information</h2>
            <dl class="divide-y divide-slate-100 text-sm">
                <x-ui.dl-row label="Name">{{ $hr->full_name_en }}</x-ui.dl-row>
                @if($hr->full_name_ar)<x-ui.dl-row label="Name (AR)" :rtl="true">{{ $hr->full_name_ar }}</x-ui.dl-row>@endif
                <x-ui.dl-row label="Father">{{ $hr->father_name ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Mother">{{ $hr->mother_name ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="MOFA Application ID">{{ $hr->mofa_new ?: '—' }}{{ $hr->mofa_old ? ' / '.$hr->mofa_old : '' }}</x-ui.dl-row>
                <x-ui.dl-row label="Date of Birth">{{ $hr->date_of_birth->format('d M Y') }}</x-ui.dl-row>
                <x-ui.dl-row label="Place of Birth">{{ $hr->place_of_birth ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Previous Nationality">{{ $hr->previous_nationality ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Present Nationality">{{ $hr->nationality }}</x-ui.dl-row>
                <x-ui.dl-row label="Sex">{{ ucfirst($hr->gender) }}</x-ui.dl-row>
                <x-ui.dl-row label="Marital Status">{{ $hr->marital_status ? ($hr->marital_status === 'married' ? 'Married' : 'Unmarried') : '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Sect">{{ $hr->sect ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Religion">{{ $hr->religion ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Home Address & Phone">{{ $hr->home_address ?? ($hr->phone ?? '—') }}</x-ui.dl-row>
                @if($hr->file_number)<x-ui.dl-row label="File Number"><span class="font-mono text-xs">{{ $hr->file_number }}</span></x-ui.dl-row>@endif
                <x-ui.dl-row label="Agent">
                    @if($hr->agent)<a href="{{ route('agents.show', $hr->agent) }}" class="text-brand-600 hover:underline">{{ $hr->agent->name }}</a>@else — @endif
                </x-ui.dl-row>
                @if($hr->notes)<x-ui.dl-row label="Notes">{{ $hr->notes }}</x-ui.dl-row>@endif
            </dl>
        </x-ui.card>

        {{-- Passport --}}
        <x-ui.card class="p-5">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-passport text-brand-600"></i> Passport</h2>
            @if($hr->passport && $hr->passport->passport_number)
                <dl class="divide-y divide-slate-100 text-sm">
                    <x-ui.dl-row label="Passport No"><span class="font-mono">{{ $hr->passport->passport_number }}</span></x-ui.dl-row>
                    <x-ui.dl-row label="Issue Place">{{ $hr->passport->issue_place ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Issue Date">{{ $hr->passport->issue_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                    @if($hr->passport->validity_years)<x-ui.dl-row label="Validity Type">{{ $hr->passport->validity_years }} Years</x-ui.dl-row>@endif
                    <x-ui.dl-row label="Validity Date">
                        @if($hr->passport->expiry_date)
                            <span class="{{ $hr->passport->expiry_date->isPast() ? 'text-rose-600' : '' }}">{{ $hr->passport->expiry_date->format('d M Y') }}</span>
                            @if($hr->passport->expiry_date->isPast())<x-ui.badge tone="red" class="ml-1">Expired</x-ui.badge>@endif
                        @else — @endif
                    </x-ui.dl-row>
                </dl>
            @else
                <p class="py-6 text-center text-sm text-slate-400">No passport data.</p>
            @endif
        </x-ui.card>

        {{-- Visa --}}
        <x-ui.card class="p-5">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-globe text-brand-600"></i> Visa</h2>
            @if($hr->visa && ($hr->visa->visa_number || $hr->visa->sponsor_name))
                <dl class="divide-y divide-slate-100 text-sm">
                    <x-ui.dl-row label="Visa No">{{ $hr->visa->visa_number ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Visa Date">{{ $hr->visa->issue_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Sponsor Name">{{ $hr->visa->sponsor_name ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Sponsor ID">{{ $hr->visa->sponsor_id ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Place of Issue">{{ $hr->visa->issue_place ?? '—' }}{{ $hr->visa->issue_place_ar ? ' / '.$hr->visa->issue_place_ar : '' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Qualification">{{ $hr->visa->qualification_en ?? '—' }}{{ $hr->visa->qualification_ar ? ' / '.$hr->visa->qualification_ar : '' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Profession">{{ $hr->visa->profession_en ?? '—' }}{{ $hr->visa->profession_ar ? ' / '.$hr->visa->profession_ar : '' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Travel Purpose">{{ $hr->visa->travel_purpose ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Musaned No">{{ $hr->visa->musaned_no ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Wakala No">{{ $hr->visa->wakala_no ?? '—' }}</x-ui.dl-row>
                </dl>
            @else
                <p class="py-6 text-center text-sm text-slate-400">No visa data.</p>
            @endif
        </x-ui.card>

        {{-- Clearance --}}
        <x-ui.card class="p-5">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-shield-check text-brand-600"></i> Police Clearance &amp; Driving License</h2>
            @if($hr->clearance)
                <dl class="divide-y divide-slate-100 text-sm">
                    <x-ui.dl-row label="P.C Reference No.">{{ $hr->clearance->police_clearance_number ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="P.C QR Code"><span class="break-all">{{ $hr->clearance->pc_qr_code ?? '—' }}</span></x-ui.dl-row>
                    <x-ui.dl-row label="License Type">{{ $hr->clearance->license_type ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Fingerprint">{{ $hr->clearance->fingerprint ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Clearance Country">{{ $hr->clearance->clearance_country ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Medical Fit">
                        @if($hr->clearance->medical_fit)<x-ui.badge tone="green"><i class="bi bi-check-circle"></i> Yes</x-ui.badge>@else<x-ui.badge tone="slate">No</x-ui.badge>@endif
                    </x-ui.dl-row>
                    <x-ui.dl-row label="Medical Date">{{ $hr->clearance->medical_date?->format('d M Y') ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Medical Center">{{ $hr->clearance->medical_center ?? '—' }}</x-ui.dl-row>
                </dl>
            @else
                <p class="py-6 text-center text-sm text-slate-400">No clearance data.</p>
            @endif
        </x-ui.card>
    </div>

    {{-- Others --}}
    <x-ui.card class="mt-4 p-5">
        <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-info-circle text-brand-600"></i> Others Info</h2>
        @if($hr->otherInfo)
            @php $o = $hr->otherInfo; @endphp
            <div class="grid grid-cols-1 gap-x-8 md:grid-cols-2">
                <dl class="divide-y divide-slate-100 text-sm">
                    <x-ui.dl-row label="Duration of Stay">{{ $o->duration_stay_en ?? '—' }}{{ $o->duration_stay_ar ? ' / '.$o->duration_stay_ar : '' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Date of Arrival">{{ $o->arrival_date?->format('d M Y') ?? '—' }}{{ $o->arrival_date_ar ? ' / '.$o->arrival_date_ar : '' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Date of Departure">{{ $o->departure_date?->format('d M Y') ?? '—' }}{{ $o->departure_date_ar ? ' / '.$o->departure_date_ar : '' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Fingerprint">{{ $hr->clearance?->fingerprint ?? '—' }}</x-ui.dl-row>
                </dl>
                <dl class="divide-y divide-slate-100 text-sm">
                    <x-ui.dl-row label="Contract Period">{{ $o->contract_period ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Salary">{{ $o->salary ? 'SAR '.number_format($o->salary, 2) : '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Work City">{{ $o->work_city ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Employer">{{ $o->employer_name ?? '—' }}</x-ui.dl-row>
                    @if($o->remarks)<x-ui.dl-row label="Remarks">{{ $o->remarks }}</x-ui.dl-row>@endif
                </dl>
            </div>
        @else
            <p class="py-6 text-center text-sm text-slate-400">No other data.</p>
        @endif
    </x-ui.card>

    {{-- Embassy List History --}}
    @if($hr->embassyListItems && $hr->embassyListItems->count())
        <x-ui.card class="mt-4 overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3"><h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-list-ol text-brand-600"></i> Embassy List History</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-2.5">List No</th><th class="px-5 py-2.5">Date</th><th class="px-5 py-2.5">Category</th><th class="px-5 py-2.5">Passport</th><th class="px-5 py-2.5">Visa</th><th class="px-5 py-2.5">Status</th><th class="px-5 py-2.5 text-right">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($hr->embassyListItems->sortByDesc('created_at') as $item)
                            @php $catTone = ['new' => 'green', 'restamping' => 'brand', 'cancellation' => 'red'][$item->category] ?? 'slate'; @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-2.5"><a href="{{ route('embassy-lists.show', $item->embassyList) }}" class="font-mono font-semibold text-slate-700 hover:text-brand-600">{{ $item->embassyList->list_no }}</a></td>
                                <td class="px-5 py-2.5 text-slate-400">{{ $item->embassyList->list_date->format('d M Y') }}</td>
                                <td class="px-5 py-2.5"><x-ui.badge :tone="$catTone">{{ $item->categoryLabel() }}</x-ui.badge></td>
                                <td class="px-5 py-2.5 font-mono text-xs text-slate-600">{{ $item->snapshot_passport_no ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-slate-500">{{ $item->snapshot_visa_no ?? '—' }}</td>
                                <td class="px-5 py-2.5"><x-ui.status-badge :status="$item->embassyList->status" /></td>
                                <td class="px-5 py-2.5"><div class="flex justify-end"><a href="{{ route('embassy-lists.show', $item->embassyList) }}" class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bi bi-eye"></i></a></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <p class="mt-4 text-xs text-slate-400">
        Created by {{ $hr->createdBy?->name ?? '—' }} on {{ $hr->created_at->format('d M Y H:i') }}
        @if($hr->updatedBy) · Updated by {{ $hr->updatedBy->name }} on {{ $hr->updated_at->format('d M Y H:i') }}@endif
    </p>

    {{-- Delete dialog --}}
    @can('delete', $hr)
        <div x-show="delOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
            <div @click="delOpen = false" x-show="delOpen" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
            <div x-show="delOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Delete Profile</h3>
                        <p class="mt-1 text-sm text-slate-500">Delete <strong>{{ $hr->full_name_en }}</strong>? All passport, visa, clearance and contract data will be permanently removed.</p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <x-ui.button type="button" variant="secondary" size="sm" x-on:click="delOpen = false">Cancel</x-ui.button>
                    <form method="POST" action="{{ route('hr.destroy', $hr) }}">
                        @csrf @method('DELETE')
                        <x-ui.button type="submit" variant="danger" size="sm"><i class="bi bi-trash"></i> Delete</x-ui.button>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection
