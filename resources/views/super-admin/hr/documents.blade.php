@extends('layouts.super-admin')
@section('title', 'Documents — ' . $hr->full_name_en)

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('super-admin.hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold">{{ $hr->full_name_en }} — Documents</h5>
        <small class="text-muted">
            {{ $hr->agency?->name ?? '—' }} · Read-only preview
        </small>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-file-earmark-person me-1"></i> Application Form</div>
            <div class="card-body py-3">
                <a href="{{ route('hr.print.application', $hr) }}" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                    <i class="bi bi-eye me-1"></i> Preview
                </a>
                <a href="{{ route('hr.download.application', $hr) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-envelope me-1"></i> Forwarding Letter</div>
            <div class="card-body py-3">
                <a href="{{ route('hr.print.forwarding-letter', $hr) }}" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                    <i class="bi bi-eye me-1"></i> Preview
                </a>
                <a href="{{ route('hr.download.forwarding-letter', $hr) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-file-earmark-text me-1"></i> Employment Agreement</div>
            <div class="card-body py-3">
                <a href="{{ route('hr.print.employment-agreement', $hr) }}" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                    <i class="bi bi-eye me-1"></i> Preview
                </a>
                <a href="{{ route('hr.download.employment-agreement', $hr) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-card-checklist me-1"></i> Checklist</div>
            <div class="card-body py-3">
                <a href="{{ route('hr.print.checklist', $hr) }}" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                    <i class="bi bi-eye me-1"></i> Preview
                </a>
                <a href="{{ route('hr.download.checklist', $hr) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-body d-flex justify-content-between align-items-center py-3">
                <div>
                    <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-zip me-2 text-primary"></i>Complete File (All Documents)</h6>
                </div>
                <a href="{{ route('hr.download.full-file', $hr) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-download me-1"></i> Download Full File PDF
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Document Generation Log --}}
@php
    $docLog = \App\Models\GeneratedDocument::where('hr_profile_id', $hr->id)
        ->with(['generatedBy', 'agency'])
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
                    <th>Agency</th>
                    <th>By</th>
                    <th>When</th>
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
                    <td>{{ $log->agency?->name ?? '—' }}</td>
                    <td>{{ $log->generatedBy?->name ?? '—' }}</td>
                    <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
