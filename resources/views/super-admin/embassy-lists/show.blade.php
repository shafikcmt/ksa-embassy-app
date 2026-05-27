@extends('layouts.super-admin')
@section('title', $embassyList->list_no)

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('super-admin.embassy-lists.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h5 class="mb-0 fw-bold font-monospace">{{ $embassyList->list_no }}</h5>
        <small class="text-muted">
            {{ $embassyList->list_date->format('d F Y') }}
            @if($embassyList->title) · {{ $embassyList->title }} @endif
            · <a href="{{ route('super-admin.agencies.show', $embassyList->agency_id) }}" class="text-decoration-none">
                {{ $embassyList->agency?->name }}
              </a>
        </small>
    </div>
    @php
        $sc = match($embassyList->status) {
            'draft'      => 'bg-warning text-dark',
            'finalized'  => 'bg-success',
            'printed'    => 'bg-info',
            'cancelled'  => 'bg-secondary',
            default      => 'bg-secondary',
        };
    @endphp
    <span class="badge {{ $sc }} ms-1">{{ ucfirst($embassyList->status) }}</span>

    @if($embassyList->isFinalized() || $embassyList->status === 'printed')
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('embassy-lists.print', $embassyList) }}"
           class="btn btn-sm btn-outline-info" target="_blank">
            <i class="bi bi-printer me-1"></i> Print Preview
        </a>
        <a href="{{ route('super-admin.embassy-lists.download-pdf', $embassyList) }}"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
    </div>
    @endif
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Total Candidates</div>
            <div class="fs-2 fw-bold text-dark">{{ $embassyList->total_items }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">New</div>
            <div class="fs-2 fw-bold text-success">{{ $embassyList->total_new }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Re-stamping</div>
            <div class="fs-2 fw-bold text-primary">{{ $embassyList->total_restamping }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Cancellation</div>
            <div class="fs-2 fw-bold text-danger">{{ $embassyList->total_cancellation }}</div>
        </div>
    </div>
</div>

{{-- Candidate Tables by Category --}}
@php
    $categoryOrder  = ['restamping', 'new', 'cancellation'];
    $categoryLabels = ['restamping' => 'Re-stamping', 'new' => 'New', 'cancellation' => 'Cancellation'];
    $categoryColors = ['restamping' => 'primary', 'new' => 'success', 'cancellation' => 'danger'];
@endphp

@foreach($categoryOrder as $category)
@if(isset($itemsByCategory[$category]) && $itemsByCategory[$category]->count() > 0)
<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span>
            <span class="badge bg-{{ $categoryColors[$category] }} me-2">{{ $categoryLabels[$category] }}</span>
            {{ $categoryLabels[$category] }} Applications
        </span>
        <span class="badge bg-{{ $categoryColors[$category] }} bg-opacity-10 text-{{ $categoryColors[$category] }}">
            {{ $itemsByCategory[$category]->count() }} candidates
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th width="50">SL</th>
                    <th>Agent Name</th>
                    <th>Candidate Name</th>
                    <th>Passport No.</th>
                    <th>Visa No.</th>
                    <th>Profession</th>
                    <th>Sponsor ID</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itemsByCategory[$category] as $item)
                <tr>
                    <td class="text-muted fw-semibold">{{ $item->serial_no ?: '—' }}</td>
                    <td>{{ $item->snapshot_agent_name ?? '—' }}</td>
                    <td>
                        <div class="fw-semibold">{{ $item->snapshot_candidate_name }}</div>
                        @if($item->snapshot_candidate_name_ar)
                        <small class="text-muted" dir="rtl">{{ $item->snapshot_candidate_name_ar }}</small>
                        @endif
                    </td>
                    <td><code style="font-size:.8rem;">{{ $item->snapshot_passport_no ?? '—' }}</code></td>
                    <td><small>{{ $item->snapshot_visa_no ?? '—' }}</small></td>
                    <td>{{ $item->snapshot_profession_en ?? '—' }}</td>
                    <td><small class="text-muted">{{ $item->snapshot_sponsor_id ?? '—' }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach

@if($embassyList->total_items === 0)
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-person-x fs-1 opacity-25 d-block mb-2"></i>
        No candidates in this list.
    </div>
</div>
@endif

@if($embassyList->notes)
<div class="card mt-3">
    <div class="card-body py-2">
        <small class="text-muted"><strong>Notes:</strong> {{ $embassyList->notes }}</small>
    </div>
</div>
@endif

<div class="text-muted mt-3" style="font-size:.78rem;">
    Created by {{ $embassyList->createdBy?->name ?? '—' }} on {{ $embassyList->created_at->format('d M Y H:i') }}
    @if($embassyList->finalized_at)
        · Finalized {{ $embassyList->finalized_at->format('d M Y H:i') }}
    @endif
    @if($embassyList->printed_at)
        · Printed {{ $embassyList->printed_at->format('d M Y H:i') }}
    @endif
</div>
@endsection
