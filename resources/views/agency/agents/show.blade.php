@extends('layouts.agency')
@section('title', $agent->name)
@section('page-title', 'Agent Detail')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-1 text-primary"></i> {{ $agent->name }}</h5>
        <small class="text-muted">Agent Profile</small>
    </div>
    <div class="d-flex gap-1">
        @can('update', $agent)
        <a href="{{ route('agents.edit', $agent) }}" class="btn btn-sm btn-warning">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
        @endcan
        @can('delete', $agent)
        <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-trash me-1"></i> Delete
        </button>
        @endcan
        <a href="{{ route('agents.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">

    {{-- Agent Details --}}
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle me-1"></i> Agent Details</span>
                @if($agent->status === 'active')
                    <span class="badge" style="background:#d1fae5;color:#065f46;">Active</span>
                @else
                    <span class="badge" style="background:#fee2e2;color:#991b1b;">Inactive</span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th width="38%" class="ps-3 text-muted fw-normal">Name</th>
                        <td class="fw-semibold">{{ $agent->name }}</td>
                    </tr>
                    <tr>
                        <th class="ps-3 text-muted fw-normal">Phone</th>
                        <td>
                            <a href="tel:{{ $agent->phone }}" class="text-decoration-none">
                                <i class="bi bi-telephone me-1"></i>{{ $agent->phone }}
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th class="ps-3 text-muted fw-normal">Email</th>
                        <td>
                            @if($agent->email)
                                <a href="mailto:{{ $agent->email }}" class="text-decoration-none">
                                    {{ $agent->email }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="ps-3 text-muted fw-normal">Address</th>
                        <td>{{ $agent->address }}</td>
                    </tr>
                    @if($agent->notes)
                    <tr>
                        <th class="ps-3 text-muted fw-normal">Notes</th>
                        <td>{{ $agent->notes }}</td>
                    </tr>
                    @endif
                    <tr class="border-top">
                        <th class="ps-3 text-muted fw-normal">Added On</th>
                        <td>{{ $agent->created_at->format('d M Y') }}</td>
                    </tr>
                    @if($agent->createdBy)
                    <tr>
                        <th class="ps-3 text-muted fw-normal">Added By</th>
                        <td>{{ $agent->createdBy->name }}</td>
                    </tr>
                    @endif
                    @if($agent->updatedBy && $agent->updated_at != $agent->created_at)
                    <tr>
                        <th class="ps-3 text-muted fw-normal">Last Updated</th>
                        <td>{{ $agent->updated_at->format('d M Y') }} by {{ $agent->updatedBy->name }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- HR / Candidates --}}
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-vcard me-1"></i> Assigned HR / Candidates
                    @if($agent->hrProfiles->count())
                        <span class="badge bg-primary ms-1">{{ $agent->hrProfiles->count() }}</span>
                    @endif
                </span>
                <a href="{{ route('hr.index', ['agent_id' => $agent->id]) }}" class="btn btn-sm btn-outline-secondary py-0">
                    View All
                </a>
            </div>
            @if($agent->hrProfiles->count())
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Nationality</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agent->hrProfiles->take(5) as $hr)
                        <tr>
                            <td class="fw-semibold">{{ $hr->full_name_en }}</td>
                            <td>{{ $hr->nationality }}</td>
                            <td>
                                <span class="badge {{ $hr->status=='active' ? 'bg-success' : ($hr->status=='blacklisted' ? 'bg-danger' : 'bg-secondary') }}">
                                    {{ ucfirst($hr->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('hr.show', $hr) }}" class="btn btn-xs btn-outline-secondary btn-sm py-0">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="card-body text-center py-4 text-muted">
                <i class="bi bi-person-vcard fs-1 opacity-25 d-block mb-2"></i>
                No HR profiles assigned to this agent.
            </div>
            @endif
        </div>

        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-clock-history me-1"></i> Recent Activity
            </div>
            <div class="card-body text-center py-4 text-muted small">
                <i class="bi bi-clipboard-data opacity-25 d-block fs-3 mb-1"></i>
                Activity log will show create/update/assign events here.
            </div>
        </div>
    </div>

</div>

{{-- Delete Modal --}}
@can('delete', $agent)
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p>Are you sure you want to delete agent <strong>{{ $agent->name }}</strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('agents.destroy', $agent) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Delete Agent
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan

@endsection
