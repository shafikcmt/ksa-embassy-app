@extends('layouts.super-admin')
@section('title', $hr->full_name_en)

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('super-admin.hr.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">{{ $hr->full_name_en }}</h5>
    @php
        $badgeClass = match($hr->status) {
            'active'     => 'bg-success',
            'inactive'   => 'bg-secondary',
            'blacklisted'=> 'bg-danger',
            default      => 'bg-secondary',
        };
    @endphp
    <span class="badge {{ $badgeClass }}">{{ ucfirst($hr->status) }}</span>
    <div class="ms-auto">
        <a href="{{ route('super-admin.hr.documents', $hr) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-pdf me-1"></i> Documents
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Personal --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-person me-1"></i> Personal Information</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:.875rem;">
                    <dt class="col-5 text-muted">Agency</dt>
                    <dd class="col-7">
                        <a href="{{ route('super-admin.agencies.show', $hr->agency_id) }}">
                            {{ $hr->agency?->name ?? '—' }}
                        </a>
                    </dd>

                    <dt class="col-5 text-muted">File Number</dt>
                    <dd class="col-7">{{ $hr->file_number ? '<code>'.$hr->file_number.'</code>' : '—' }}</dd>

                    <dt class="col-5 text-muted">Full Name (EN)</dt>
                    <dd class="col-7 fw-semibold">{{ $hr->full_name_en }}</dd>

                    @if($hr->full_name_ar)
                    <dt class="col-5 text-muted">Full Name (AR)</dt>
                    <dd class="col-7" dir="rtl">{{ $hr->full_name_ar }}</dd>
                    @endif

                    <dt class="col-5 text-muted">Nationality</dt>
                    <dd class="col-7">{{ $hr->nationality }}</dd>

                    <dt class="col-5 text-muted">Date of Birth</dt>
                    <dd class="col-7">{{ $hr->date_of_birth->format('d M Y') }}</dd>

                    <dt class="col-5 text-muted">Gender</dt>
                    <dd class="col-7">{{ ucfirst($hr->gender) }}</dd>

                    <dt class="col-5 text-muted">Religion</dt>
                    <dd class="col-7">{{ $hr->religion ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Marital Status</dt>
                    <dd class="col-7">{{ $hr->marital_status ? ucfirst($hr->marital_status) : '—' }}</dd>

                    <dt class="col-5 text-muted">Occupation</dt>
                    <dd class="col-7">{{ $hr->occupation ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Phone</dt>
                    <dd class="col-7">{{ $hr->phone ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">{{ $hr->email ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Agent</dt>
                    <dd class="col-7">{{ $hr->agent?->name ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Passport --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-passport me-1"></i> Passport</div>
            <div class="card-body">
                @if($hr->passport && $hr->passport->passport_number)
                <dl class="row mb-0" style="font-size:.875rem;">
                    <dt class="col-5 text-muted">Passport #</dt>
                    <dd class="col-7 fw-semibold">{{ $hr->passport->passport_number }}</dd>
                    <dt class="col-5 text-muted">Type</dt>
                    <dd class="col-7">{{ ucfirst($hr->passport->passport_type) }}</dd>
                    <dt class="col-5 text-muted">Issue Place</dt>
                    <dd class="col-7">{{ $hr->passport->issue_place ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Issue Date</dt>
                    <dd class="col-7">{{ $hr->passport->issue_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Expiry Date</dt>
                    <dd class="col-7 {{ $hr->passport->expiry_date?->isPast() ? 'text-danger' : '' }}">
                        {{ $hr->passport->expiry_date?->format('d M Y') ?? '—' }}
                    </dd>
                </dl>
                @else
                <div class="text-muted text-center py-3">No passport data.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Visa --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-globe me-1"></i> Visa</div>
            <div class="card-body">
                @if($hr->visa && ($hr->visa->visa_number || $hr->visa->sponsor_name))
                <dl class="row mb-0" style="font-size:.875rem;">
                    <dt class="col-5 text-muted">Visa #</dt>
                    <dd class="col-7">{{ $hr->visa->visa_number ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Type</dt>
                    <dd class="col-7">{{ $hr->visa->visa_type ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Issue Place</dt>
                    <dd class="col-7">{{ $hr->visa->issue_place ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Issue Date</dt>
                    <dd class="col-7">{{ $hr->visa->issue_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Expiry Date</dt>
                    <dd class="col-7">{{ $hr->visa->expiry_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Sponsor Name</dt>
                    <dd class="col-7">{{ $hr->visa->sponsor_name ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Sponsor ID</dt>
                    <dd class="col-7">{{ $hr->visa->sponsor_id ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Border #</dt>
                    <dd class="col-7">{{ $hr->visa->border_number ?? '—' }}</dd>
                </dl>
                @else
                <div class="text-muted text-center py-3">No visa data.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Clearance --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-shield-check me-1"></i> Police Clearance & Medical</div>
            <div class="card-body">
                @if($hr->clearance)
                <dl class="row mb-0" style="font-size:.875rem;">
                    <dt class="col-5 text-muted">Clearance #</dt>
                    <dd class="col-7">{{ $hr->clearance->police_clearance_number ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Issue Date</dt>
                    <dd class="col-7">{{ $hr->clearance->clearance_issue_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Expiry Date</dt>
                    <dd class="col-7">{{ $hr->clearance->clearance_expiry_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Country</dt>
                    <dd class="col-7">{{ $hr->clearance->clearance_country ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Medical Fit</dt>
                    <dd class="col-7">
                        <span class="badge {{ $hr->clearance->medical_fit ? 'bg-success' : 'bg-secondary' }}">
                            {{ $hr->clearance->medical_fit ? 'Yes' : 'No' }}
                        </span>
                    </dd>
                    <dt class="col-5 text-muted">Medical Date</dt>
                    <dd class="col-7">{{ $hr->clearance->medical_date?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Medical Center</dt>
                    <dd class="col-7">{{ $hr->clearance->medical_center ?? '—' }}</dd>
                </dl>
                @else
                <div class="text-muted text-center py-3">No clearance data.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Other Info --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-file-text me-1"></i> Contract & Other Info</div>
            <div class="card-body">
                @if($hr->otherInfo)
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0" style="font-size:.875rem;">
                            <dt class="col-5 text-muted">Contract Period</dt>
                            <dd class="col-7">{{ $hr->otherInfo->contract_period ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Salary</dt>
                            <dd class="col-7">{{ $hr->otherInfo->salary ? 'SAR '.number_format($hr->otherInfo->salary, 2) : '—' }}</dd>
                            <dt class="col-5 text-muted">Work City</dt>
                            <dd class="col-7">{{ $hr->otherInfo->work_city ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Employer</dt>
                            <dd class="col-7">{{ $hr->otherInfo->employer_name ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Employer Phone</dt>
                            <dd class="col-7">{{ $hr->otherInfo->employer_phone ?? '—' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0" style="font-size:.875rem;">
                            <dt class="col-5 text-muted">Arrival Date</dt>
                            <dd class="col-7">{{ $hr->otherInfo->arrival_date?->format('d M Y') ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Departure Date</dt>
                            <dd class="col-7">{{ $hr->otherInfo->departure_date?->format('d M Y') ?? '—' }}</dd>
                            @if($hr->otherInfo->remarks)
                            <dt class="col-5 text-muted">Remarks</dt>
                            <dd class="col-7">{{ $hr->otherInfo->remarks }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
                @else
                <div class="text-muted text-center py-3">No contract/other data.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="text-muted mt-3" style="font-size:.78rem;">
    Created by {{ $hr->createdBy?->name ?? '—' }} on {{ $hr->created_at->format('d M Y H:i') }}
    @if($hr->updatedBy)
        · Updated by {{ $hr->updatedBy->name }} on {{ $hr->updated_at->format('d M Y H:i') }}
    @endif
</div>
@endsection
