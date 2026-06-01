@extends('layouts.agency')
@section('title', 'Documents — ' . $hr->full_name_en)
@section('page-title', 'HR Documents')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">{{ $hr->full_name_en }}</h5>
        <small class="text-muted">Document Centre</small>
    </div>
    @php
        $badgeClass = match($hr->status) {
            'active'     => 'bg-success',
            'inactive'   => 'bg-secondary',
            'blacklisted'=> 'bg-danger',
            'listed'     => 'bg-info',
            default      => 'bg-secondary',
        };
    @endphp
    <span class="badge {{ $badgeClass }} ms-1">{{ ucfirst($hr->status) }}</span>
</div>

{{-- PDF limit notice + Document Readiness --}}
@php
    $sub = auth()->user()->agency?->activeSubscription;
    $pdfLimit = $sub?->plan->max_pdf_monthly ?? 0;
    $pdfUsed = \App\Models\GeneratedDocument::where('agency_id', auth()->user()->agency_id)
        ->where('action', 'download')
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->count();
    $readiness = $hr->documentReadiness();
@endphp

{{-- Document Readiness Banner --}}
@if($readiness['ready'])
<div class="alert alert-success py-2 mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
        <strong>Ready to Print</strong> — All required fields are complete.
    </div>
</div>
@else
<div class="alert alert-warning py-2 mb-3">
    <div class="d-flex align-items-center gap-2 mb-1">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <strong>Missing Fields — Documents may be incomplete</strong>
        <a href="{{ route('hr.edit', $hr) }}" class="btn btn-sm btn-warning ms-auto">
            <i class="bi bi-pencil me-1"></i> Edit Profile
        </a>
    </div>
    <div style="font-size:.85rem;">
        @foreach($readiness['missing'] as $field)
        <span class="badge bg-warning text-dark me-1 mb-1">{{ $field }}</span>
        @endforeach
    </div>
</div>
@endif

@if($pdfLimit < 9999)
<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-info-circle me-1"></i>
    PDF Downloads this month: <strong>{{ $pdfUsed }}</strong> / {{ $pdfLimit }}
    @if($pdfUsed >= $pdfLimit)
        &nbsp; <span class="badge bg-danger">Limit Reached</span>
    @endif
</div>
@endif

<div class="row g-3">

    {{-- Full File --}}
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h6 class="mb-1 fw-bold"><i class="bi bi-file-earmark-zip me-2 text-primary"></i>Complete File (All 4 Documents)</h6>
                    <small class="text-muted">Application + Forwarding Letter + Employment Agreement + Checklist in one PDF</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.print.full-file', $hr) }}" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> Preview
                    </a>
                    <a href="{{ route('hr.download.full-file', $hr) }}" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Download Full File PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Application Form --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-file-earmark-person me-1"></i> Saudi Embassy Application Form
            </div>
            <div class="card-body py-3">
                <p class="text-muted small mb-3">
                    Bilingual A4 application form with personal details, passport, visa, travel information and barcode.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.print.application', $hr) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> Preview
                    </a>
                    <a href="{{ route('hr.download.application', $hr) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Forwarding Letter --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-envelope me-1"></i> Forwarding Letter
            </div>
            <div class="card-body py-3">
                <p class="text-muted small mb-3">
                    Formal letter from agency to Chief of Consular Section with employee details and declaration.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.print.forwarding-letter', $hr) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> Preview
                    </a>
                    <a href="{{ route('hr.download.forwarding-letter', $hr) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Employment Agreement --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-file-earmark-text me-1"></i> Employment Agreement
            </div>
            <div class="card-body py-3">
                <p class="text-muted small mb-3">
                    Employment contract with 10 standard terms including salary, accommodation, leave, medical and repatriation.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.print.employment-agreement', $hr) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> Preview
                    </a>
                    <a href="{{ route('hr.download.employment-agreement', $hr) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Checklist --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-card-checklist me-1"></i> Attachment Checklist
            </div>
            <div class="card-body py-3">
                <p class="text-muted small mb-3">
                    Document checklist with all required attachments: passport, visa, medical, police clearance, fingerprint, etc.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('hr.print.checklist', $hr) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-eye me-1"></i> Preview
                    </a>
                    <a href="{{ route('hr.download.checklist', $hr) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i> Download PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Quick info summary --}}
<div class="card mt-3">
    <div class="card-header py-2"><i class="bi bi-info-circle me-1"></i> Quick Data Summary</div>
    <div class="card-body py-2">
        <div class="row g-2" style="font-size:.85rem;">
            <div class="col-md-4">
                <strong>Passport:</strong>
                {{ $hr->passport?->passport_number ?? '—' }}
                @if($hr->passport?->expiry_date)
                    <small class="text-muted ms-1">(exp. {{ $hr->passport->expiry_date->format('d M Y') }})</small>
                @endif
            </div>
            <div class="col-md-4">
                <strong>Visa:</strong>
                {{ $hr->visa?->visa_number ?? '—' }}
                @if($hr->visa?->sponsor_name)
                    <small class="text-muted ms-1">· {{ $hr->visa->sponsor_name }}</small>
                @endif
            </div>
            <div class="col-md-4">
                <strong>Profession:</strong>
                {{ $hr->visa?->profession_en ?? ($hr->occupation ?? '—') }}
            </div>
            <div class="col-md-4">
                <strong>Medical:</strong>
                @if($hr->clearance)
                    <span class="badge {{ $hr->clearance->medical_fit ? 'bg-success' : 'bg-secondary' }}">
                        {{ $hr->clearance->medical_fit ? 'Fit' : 'Unfit' }}
                    </span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
            <div class="col-md-4">
                <strong>Police Clearance:</strong>
                {{ $hr->clearance?->police_clearance_number ?? '—' }}
            </div>
            <div class="col-md-4">
                <strong>Musaned / Wakala:</strong>
                {{ $hr->visa?->musaned_no ?? '—' }} / {{ $hr->visa?->wakala_no ?? '—' }}
            </div>
        </div>
        @if(!$readiness['ready'])
        <div class="mt-2 mb-0" style="font-size:.82rem;color:#856404;">
            <i class="bi bi-exclamation-triangle me-1"></i>
            {{ count($readiness['missing']) }} field(s) missing —
            <a href="{{ route('hr.edit', $hr) }}">edit profile</a> to complete them.
        </div>
        @endif
    </div>
</div>

<div class="text-muted mt-3" style="font-size:.78rem;">
    <i class="bi bi-info-circle me-1"></i>
    Previews open in browser and do not count against your monthly PDF limit.
    Downloads are counted: {{ $pdfUsed }} used of {{ $pdfLimit < 9999 ? $pdfLimit : 'unlimited' }} this month.
</div>

{{-- Document Generation Log --}}
@php
    $docLog = \App\Models\GeneratedDocument::where('hr_profile_id', $hr->id)
        ->with('generatedBy')
        ->latest('created_at')
        ->limit(15)
        ->get();
@endphp
@if($docLog->isNotEmpty())
<div class="card mt-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-1"></i> Document Activity Log</span>
        <small class="text-muted">Last 15 events</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
            <thead class="table-light">
                <tr>
                    <th>Document</th>
                    <th>Action</th>
                    <th>By</th>
                    <th>When</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($docLog as $log)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $log->document_type)) }}</td>
                    <td>
                        @if($log->action === 'download')
                            <span class="badge bg-primary">Download</span>
                        @else
                            <span class="badge bg-secondary">Preview</span>
                        @endif
                    </td>
                    <td>{{ $log->generatedBy?->name ?? '—' }}</td>
                    <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td class="text-muted">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
