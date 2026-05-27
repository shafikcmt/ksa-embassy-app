@extends('layouts.super-admin')
@section('title', $agent->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-1"></i> {{ $agent->name }}</h5>
    <div class="d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-danger"
            data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-trash me-1"></i> Delete
        </button>
        <a href="{{ route('super-admin.agents.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between">
                <span>Agent Details</span>
                @if($agent->status === 'active')
                    <span class="badge badge-status-active">Active</span>
                @else
                    <span class="badge badge-status-suspended">Inactive</span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="38%" class="ps-3 text-muted fw-normal">Agency</th>
                        <td><a href="{{ route('super-admin.agencies.show', $agent->agency_id) }}">{{ $agent->agency->name }}</a></td></tr>
                    <tr><th class="ps-3 text-muted fw-normal">Phone</th><td>{{ $agent->phone }}</td></tr>
                    <tr><th class="ps-3 text-muted fw-normal">Email</th><td>{{ $agent->email ?? '—' }}</td></tr>
                    <tr><th class="ps-3 text-muted fw-normal">Address</th><td>{{ $agent->address }}</td></tr>
                    @if($agent->notes)
                    <tr><th class="ps-3 text-muted fw-normal">Notes</th><td>{{ $agent->notes }}</td></tr>
                    @endif
                    <tr class="border-top">
                        <th class="ps-3 text-muted fw-normal">Created</th>
                        <td>{{ $agent->created_at->format('d M Y') }}
                            @if($agent->createdBy) <br><small class="text-muted">by {{ $agent->createdBy->name }}</small>@endif
                        </td>
                    </tr>
                    @if($agent->updatedBy && $agent->updated_at != $agent->created_at)
                    <tr>
                        <th class="ps-3 text-muted fw-normal">Updated</th>
                        <td>{{ $agent->updated_at->format('d M Y') }} by {{ $agent->updatedBy->name }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-person-vcard me-1"></i> Assigned HR / Candidates</div>
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-person-vcard fs-1 opacity-25 d-block mb-2"></i>
                HR module — Phase 3
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete Agent</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p>Delete <strong>{{ $agent->name }}</strong> from <strong>{{ $agent->agency->name }}</strong>?</p><p class="text-muted small mb-0">This cannot be undone.</p></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('super-admin.agents.destroy', $agent) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
