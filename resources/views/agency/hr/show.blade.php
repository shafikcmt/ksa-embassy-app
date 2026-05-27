@extends('layouts.agency')
@section('title', $hr->full_name_en)
@section('page-title', 'HR Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('hr.index') }}" class="btn btn-sm btn-outline-secondary">
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
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('hr.documents', $hr) }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-pdf me-1"></i> Documents
        </a>
        @can('update', $hr)
        <a href="{{ route('hr.edit', $hr) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endcan
        @can('delete', $hr)
        <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-trash me-1"></i> Delete
        </button>
        @endcan
    </div>
</div>

<div class="row g-3">
    {{-- Personal Info --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-person me-1"></i> Personal Information
            </div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:.875rem;">
                    <dt class="col-5 text-muted">Full Name (EN)</dt>
                    <dd class="col-7 fw-semibold">{{ $hr->full_name_en }}</dd>

                    @if($hr->full_name_ar)
                    <dt class="col-5 text-muted">Full Name (AR)</dt>
                    <dd class="col-7" dir="rtl">{{ $hr->full_name_ar }}</dd>
                    @endif

                    <dt class="col-5 text-muted">File Number</dt>
                    <dd class="col-7">{{ $hr->file_number ? '<code>'.$hr->file_number.'</code>' : '—' }}</dd>

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
                    <dd class="col-7">
                        @if($hr->agent)
                            <a href="{{ route('agents.show', $hr->agent) }}">{{ $hr->agent->name }}</a>
                        @else
                            —
                        @endif
                    </dd>

                    @if($hr->notes)
                    <dt class="col-5 text-muted">Notes</dt>
                    <dd class="col-7">{{ $hr->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Passport --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-passport me-1"></i> Passport
            </div>
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
                    <dd class="col-7">
                        @if($hr->passport->expiry_date)
                            <span class="{{ $hr->passport->expiry_date->isPast() ? 'text-danger' : '' }}">
                                {{ $hr->passport->expiry_date->format('d M Y') }}
                                @if($hr->passport->expiry_date->isPast())
                                    <span class="badge bg-danger ms-1">Expired</span>
                                @endif
                            </span>
                        @else
                            —
                        @endif
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
            <div class="card-header py-2">
                <i class="bi bi-globe me-1"></i> Visa
            </div>
            <div class="card-body">
                @if($hr->visa && ($hr->visa->visa_number || $hr->visa->sponsor_name))
                <dl class="row mb-0" style="font-size:.875rem;">
                    <dt class="col-5 text-muted">Visa #</dt>
                    <dd class="col-7">{{ $hr->visa->visa_number ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Visa Type</dt>
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

    {{-- Clearance & Medical --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-shield-check me-1"></i> Police Clearance & Medical
            </div>
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
                        @if($hr->clearance->medical_fit)
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
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

    {{-- Contract & Other Info --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-file-text me-1"></i> Contract & Other Info
            </div>
            <div class="card-body">
                @if($hr->otherInfo && ($hr->otherInfo->employer_name || $hr->otherInfo->contract_period || $hr->otherInfo->remarks))
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0" style="font-size:.875rem;">
                            <dt class="col-5 text-muted">Contract Period</dt>
                            <dd class="col-7">{{ $hr->otherInfo->contract_period ?? '—' }}</dd>

                            <dt class="col-5 text-muted">Salary</dt>
                            <dd class="col-7">
                                {{ $hr->otherInfo->salary ? 'SAR '.number_format($hr->otherInfo->salary, 2) : '—' }}
                            </dd>

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

{{-- Embassy List History --}}
@if($hr->embassyListItems && $hr->embassyListItems->count())
<div class="card mt-3">
    <div class="card-header py-2">
        <i class="bi bi-list-ol me-1"></i> Embassy List History
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>List No</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Passport Used</th>
                    <th>Visa Used</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($hr->embassyListItems->sortByDesc('created_at') as $item)
                <tr>
                    <td class="font-monospace fw-semibold">{{ $item->embassyList->list_no }}</td>
                    <td class="text-muted">{{ $item->embassyList->list_date->format('d M Y') }}</td>
                    <td>
                        @php
                            $catColor = match($item->category) {
                                'new'          => 'bg-success',
                                'restamping'   => 'bg-primary',
                                'cancellation' => 'bg-danger',
                                default        => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $catColor }}">{{ $item->categoryLabel() }}</span>
                    </td>
                    <td><code style="font-size:.8rem;">{{ $item->snapshot_passport_no ?? '—' }}</code></td>
                    <td><small>{{ $item->snapshot_visa_no ?? '—' }}</small></td>
                    <td>
                        <span class="badge {{ $item->embassyList->statusBadgeClass() }}">
                            {{ ucfirst($item->embassyList->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('embassy-lists.show', $item->embassyList) }}"
                           class="btn btn-xs btn-outline-secondary btn-sm py-0">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Meta --}}
<div class="text-muted mt-3" style="font-size:.78rem;">
    Created by {{ $hr->createdBy?->name ?? '—' }} on {{ $hr->created_at->format('d M Y H:i') }}
    @if($hr->updatedBy)
        · Updated by {{ $hr->updatedBy->name }} on {{ $hr->updated_at->format('d M Y H:i') }}
    @endif
</div>

{{-- Delete Modal --}}
@can('delete', $hr)
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete Profile</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete <strong>{{ $hr->full_name_en }}</strong>?</p>
                <small class="text-muted">All passport, visa, clearance, and contract data will be permanently removed.</small>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('hr.destroy', $hr) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection
