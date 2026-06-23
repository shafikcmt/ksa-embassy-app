@extends('layouts.agency-app')
@section('title', 'Documents — ' . $hr->full_name_en)
@section('page-title', 'HR Documents')

@php
    $sub      = auth()->user()->agency?->activeSubscription;
    $pdfLimit = $sub?->plan->max_pdf_monthly ?? 0;
    $pdfUsed  = \App\Models\GeneratedDocument::where('agency_id', auth()->user()->agency_id)
        ->where('action', 'download')
        ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
    $readiness = $hr->documentReadiness();

    $docs = [
        ['title' => 'Saudi Embassy Application Form', 'icon' => 'bi-file-earmark-person', 'desc' => 'Bilingual A4 application form with personal details, passport, visa, travel information and barcode.', 'preview' => route('hr.print.application', $hr), 'download' => route('hr.download.application', $hr)],
        ['title' => 'Forwarding Letter', 'icon' => 'bi-envelope', 'desc' => 'Formal letter from agency to Chief of Consular Section with employee details and declaration.', 'preview' => route('hr.print.forwarding-letter', $hr), 'download' => route('hr.download.forwarding-letter', $hr)],
        ['title' => 'Employment Agreement', 'icon' => 'bi-file-earmark-text', 'desc' => 'Employment contract with 10 standard terms including salary, accommodation, leave, medical and repatriation.', 'preview' => route('hr.print.employment-agreement', $hr), 'download' => route('hr.download.employment-agreement', $hr)],
        ['title' => 'Attachment Checklist', 'icon' => 'bi-card-checklist', 'desc' => 'Document checklist with all required attachments: passport, visa, medical, police clearance, fingerprint, etc.', 'preview' => route('hr.print.checklist', $hr), 'download' => route('hr.download.checklist', $hr)],
    ];
@endphp

@section('content')

{{-- Header --}}
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('hr.show', $hr) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-slate-900">{{ $hr->full_name_en }}</h1>
            <x-ui.status-badge :status="$hr->status" />
        </div>
        <p class="text-sm text-slate-400">Document Centre</p>
    </div>
</div>

{{-- Readiness banner --}}
@if($readiness['ready'])
    <div class="mb-4 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <i class="bi bi-check-circle-fill text-emerald-500"></i>
        <span><strong>Ready to Print</strong> — all required fields are complete.</span>
    </div>
@else
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <div class="mb-2 flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill text-amber-500"></i>
            <strong>Missing fields — documents may be incomplete</strong>
            <x-ui.button :href="route('hr.edit', $hr)" size="sm" class="ml-auto !bg-amber-500 hover:!bg-amber-600"><i class="bi bi-pencil"></i> Edit Profile</x-ui.button>
        </div>
        <div class="flex flex-wrap gap-1.5">
            @foreach($readiness['missing'] as $field)
                <span class="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $field }}</span>
            @endforeach
        </div>
    </div>
@endif

{{-- PDF limit notice --}}
@if($pdfLimit < 9999)
    <div class="mb-4 flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
        <i class="bi bi-info-circle text-brand-500"></i>
        <span>PDF downloads this month: <strong>{{ $pdfUsed }}</strong> / {{ $pdfLimit }}</span>
        @if($pdfUsed >= $pdfLimit)<x-ui.badge tone="red">Limit reached</x-ui.badge>@endif
    </div>
@endif

{{-- Full file --}}
<x-ui.card class="mb-4 border-brand-200 bg-gradient-to-br from-brand-50/60 to-white p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-600 text-lg text-white"><i class="bi bi-file-earmark-zip"></i></span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Complete File (All 4 Documents)</h2>
                <p class="text-xs text-slate-500">Application + Forwarding Letter + Employment Agreement + Checklist in one PDF</p>
            </div>
        </div>
        <div class="flex gap-2">
            <x-ui.button :href="route('hr.print.full-file', $hr)" variant="secondary" size="sm" target="_blank"><i class="bi bi-eye"></i> Preview</x-ui.button>
            <x-ui.button :href="route('hr.download.full-file', $hr)" size="sm"><i class="bi bi-download"></i> Download Full File</x-ui.button>
        </div>
    </div>
</x-ui.card>

{{-- Individual documents --}}
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    @foreach($docs as $doc)
        <x-ui.card class="flex flex-col p-5">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi {{ $doc['icon'] }} text-brand-600"></i> {{ $doc['title'] }}</h2>
            <p class="mt-2 flex-1 text-xs leading-relaxed text-slate-500">{{ $doc['desc'] }}</p>
            <div class="mt-4 flex gap-2">
                <x-ui.button :href="$doc['preview']" variant="secondary" size="sm" target="_blank"><i class="bi bi-eye"></i> Preview</x-ui.button>
                <x-ui.button :href="$doc['download']" variant="outline" size="sm"><i class="bi bi-download"></i> Download PDF</x-ui.button>
            </div>
        </x-ui.card>
    @endforeach
</div>

{{-- Quick data summary --}}
<x-ui.card class="mt-4 p-5">
    <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-info-circle text-brand-600"></i> Quick Data Summary</h2>
    <div class="grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
        <div><span class="text-slate-400">Passport:</span> <span class="font-medium text-slate-700">{{ $hr->passport?->passport_number ?? '—' }}</span>@if($hr->passport?->expiry_date)<span class="text-xs text-slate-400"> (exp. {{ $hr->passport->expiry_date->format('d M Y') }})</span>@endif</div>
        <div><span class="text-slate-400">Visa:</span> <span class="font-medium text-slate-700">{{ $hr->visa?->visa_number ?? '—' }}</span>@if($hr->visa?->sponsor_name)<span class="text-xs text-slate-400"> · {{ $hr->visa->sponsor_name }}</span>@endif</div>
        <div><span class="text-slate-400">Profession:</span> <span class="font-medium text-slate-700">{{ $hr->visa?->profession_en ?? ($hr->occupation ?? '—') }}</span></div>
        <div><span class="text-slate-400">Medical:</span> @if($hr->clearance)<x-ui.badge :tone="$hr->clearance->medical_fit ? 'green' : 'slate'">{{ $hr->clearance->medical_fit ? 'Fit' : 'Unfit' }}</x-ui.badge>@else<span class="text-slate-400">—</span>@endif</div>
        <div><span class="text-slate-400">Police Clearance:</span> <span class="font-medium text-slate-700">{{ $hr->clearance?->police_clearance_number ?? '—' }}</span></div>
        <div><span class="text-slate-400">Musaned / Wakala:</span> <span class="font-medium text-slate-700">{{ $hr->visa?->musaned_no ?? '—' }} / {{ $hr->visa?->wakala_no ?? '—' }}</span></div>
    </div>
    @unless($readiness['ready'])
        <p class="mt-3 text-xs text-amber-700"><i class="bi bi-exclamation-triangle mr-1"></i>{{ count($readiness['missing']) }} field(s) missing — <a href="{{ route('hr.edit', $hr) }}" class="font-semibold underline">edit profile</a> to complete them.</p>
    @endunless
</x-ui.card>

<p class="mt-4 text-xs text-slate-400">
    <i class="bi bi-info-circle mr-1"></i>Previews open in the browser and do not count against your monthly PDF limit.
    Downloads are counted: {{ $pdfUsed }} used of {{ $pdfLimit < 9999 ? $pdfLimit : 'unlimited' }} this month.
</p>

{{-- Activity log --}}
@php
    $docLog = \App\Models\GeneratedDocument::where('hr_profile_id', $hr->id)
        ->with('generatedBy')->latest('created_at')->limit(15)->get();
@endphp
@if($docLog->isNotEmpty())
    <x-ui.card class="mt-4 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-clock-history text-brand-600"></i> Document Activity Log</h2>
            <span class="text-xs text-slate-400">Last 15 events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-2.5">Document</th><th class="px-5 py-2.5">Action</th><th class="px-5 py-2.5">By</th><th class="px-5 py-2.5">When</th><th class="px-5 py-2.5">IP</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($docLog as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-2.5 text-slate-600">{{ ucwords(str_replace('_', ' ', $log->document_type)) }}</td>
                            <td class="px-5 py-2.5">@if($log->action === 'download')<x-ui.badge tone="brand">Download</x-ui.badge>@else<x-ui.badge tone="slate">Preview</x-ui.badge>@endif</td>
                            <td class="px-5 py-2.5 text-slate-600">{{ $log->generatedBy?->name ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-slate-400">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-2.5 text-slate-400">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endif
@endsection
