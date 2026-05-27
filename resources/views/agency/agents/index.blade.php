@extends('layouts.agency')
@section('title', 'Agents')
@section('page-title', 'Agent Management')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-people me-1 text-primary"></i> Agents</h5>
        <small class="text-muted">Manage your agency's recruitment agents</small>
    </div>
    @can('create', \App\Models\Agent::class)
        @if($planLimit !== null && $totalAgents >= $planLimit && $planLimit < 999)
            <button class="btn btn-sm btn-secondary" disabled title="Plan limit reached">
                <i class="bi bi-slash-circle me-1"></i> Limit Reached ({{ $totalAgents }}/{{ $planLimit }})
            </button>
        @else
            <a href="{{ route('agents.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add New Agent
            </a>
        @endif
    @endcan
</div>

{{-- Summary cards --}}
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div style="width:40px;height:40px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-people-fill text-primary"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;">Total Agents</div>
                <div class="fw-bold fs-5">{{ $totalAgents }}
                    @if($planLimit && $planLimit < 999)
                        <small class="text-muted fw-normal fs-6">/ {{ $planLimit }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div style="width:40px;height:40px;background:#d1fae5;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-check text-success"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;">Active</div>
                <div class="fw-bold fs-5">{{ $activeAgents }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div style="width:40px;height:40px;background:#fee2e2;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-dash text-danger"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;">Inactive</div>
                <div class="fw-bold fs-5">{{ $totalAgents - $activeAgents }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('agents.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                        placeholder="Search by name, phone, or email..."
                        value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active"   @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('agents.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x"></i> Clear
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th width="110">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr>
                    <td class="text-muted">
                        {{ $loop->iteration + ($agents->currentPage() - 1) * $agents->perPage() }}
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $agent->name }}</div>
                        @if($agent->createdBy)
                            <small class="text-muted">by {{ $agent->createdBy->name }}</small>
                        @endif
                    </td>
                    <td>{{ $agent->phone }}</td>
                    <td>
                        @if($agent->email)
                            <a href="mailto:{{ $agent->email }}" class="text-decoration-none">{{ $agent->email }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <span title="{{ $agent->address }}" class="d-inline-block text-truncate" style="max-width:160px;">
                            {{ $agent->address }}
                        </span>
                    </td>
                    <td>
                        @if($agent->status === 'active')
                            <span class="badge" style="background:#d1fae5;color:#065f46;">Active</span>
                        @else
                            <span class="badge" style="background:#fee2e2;color:#991b1b;">Inactive</span>
                        @endif
                    </td>
                    <td class="text-muted">{{ $agent->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('agents.show', $agent) }}"
                                class="btn btn-sm btn-outline-info py-0 px-2" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @can('update', $agent)
                            <a href="{{ route('agents.edit', $agent) }}"
                                class="btn btn-sm btn-outline-warning py-0 px-2" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            @can('delete', $agent)
                            <button type="button"
                                class="btn btn-sm btn-outline-danger py-0 px-2"
                                title="Delete"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal"
                                data-agent-id="{{ $agent->id }}"
                                data-agent-name="{{ $agent->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-people fs-1 opacity-25 d-block mb-2"></i>
                        @if(request()->hasAny(['search','status']))
                            No agents match your filters.
                            <a href="{{ route('agents.index') }}">Clear filters</a>
                        @else
                            No agents added yet.
                            @can('create', \App\Models\Agent::class)
                                <a href="{{ route('agents.create') }}">Add your first agent</a>
                            @endcan
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($agents->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
        <small class="text-muted">
            Showing {{ $agents->firstItem() }}–{{ $agents->lastItem() }} of {{ $agents->total() }} agents
        </small>
        {{ $agents->links() }}
    </div>
    @endif
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p>Are you sure you want to delete agent <strong id="deleteAgentName"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Delete Agent
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('deleteAgentName').textContent = btn.dataset.agentName;
    document.getElementById('deleteForm').action = '/agents/' + btn.dataset.agentId;
});
</script>
@endpush
