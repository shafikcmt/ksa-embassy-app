@extends('layouts.agency')
@section('title', 'HR / Candidates')
@section('page-title', 'HR / Candidates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">HR / Candidates</h5>
        <small class="text-muted">{{ $totalHr }} total
            @if($planLimit < 9999) · limit {{ $planLimit }} @endif
        </small>
    </div>
    @can('create', \App\Models\HrProfile::class)
    <a href="{{ route('hr.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add HR Profile
    </a>
    @endcan
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Total</div>
            <div class="fs-3 fw-bold text-primary">{{ $totalHr }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Active</div>
            <div class="fs-3 fw-bold text-success">{{ $hrProfiles->where('status','active')->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Inactive</div>
            <div class="fs-3 fw-bold text-secondary">{{ $hrProfiles->where('status','inactive')->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Blacklisted</div>
            <div class="fs-3 fw-bold text-danger">{{ $hrProfiles->where('status','blacklisted')->count() }}</div>
        </div>
    </div>
</div>

{{-- Search & Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('hr.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search name, file #, nationality, phone..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="blacklisted" {{ request('status')=='blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>
                        {{ $agent->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="{{ route('hr.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Nationality</th>
                    <th>File #</th>
                    <th>Agent</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($hrProfiles as $hr)
                <tr>
                    <td class="text-muted">{{ $hrProfiles->firstItem() + $loop->index }}</td>
                    <td>
                        <a href="{{ route('hr.show', $hr) }}" class="fw-semibold text-decoration-none">
                            {{ $hr->full_name_en }}
                        </a>
                        @if($hr->full_name_ar)
                        <div class="text-muted" style="font-size:.78rem;direction:rtl;">{{ $hr->full_name_ar }}</div>
                        @endif
                    </td>
                    <td>{{ $hr->nationality }}</td>
                    <td>
                        @if($hr->file_number)
                            <code>{{ $hr->file_number }}</code>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $hr->agent?->name ?? '—' }}</td>
                    <td>{{ $hr->phone ?? '—' }}</td>
                    <td>
                        @php
                            $badgeClass = match($hr->status) {
                                'active'     => 'bg-success',
                                'inactive'   => 'bg-secondary',
                                'blacklisted'=> 'bg-danger',
                                default      => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ ucfirst($hr->status) }}</span>
                    </td>
                    <td class="text-muted">{{ $hr->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary py-0">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('hr.documents', $hr) }}" class="btn btn-sm btn-outline-success py-0" title="Documents">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            @can('update', $hr)
                            <a href="{{ route('hr.edit', $hr) }}" class="btn btn-sm btn-outline-primary py-0">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            @can('delete', $hr)
                            <button type="button" class="btn btn-sm btn-outline-danger py-0"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-hr-id="{{ $hr->id }}"
                                data-hr-name="{{ $hr->full_name_en }}">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-person-vcard fs-1 d-block mb-2 opacity-25"></i>
                        No HR profiles found.
                        @can('create', \App\Models\HrProfile::class)
                            <a href="{{ route('hr.create') }}">Add the first one.</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($hrProfiles->hasPages())
    <div class="card-footer py-2">
        {{ $hrProfiles->links() }}
    </div>
    @endif
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Delete HR Profile</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Delete <strong id="deleteHrName"></strong>?</p>
                <small class="text-muted">This will permanently remove all associated passport, visa, clearance, and other data.</small>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
    document.getElementById('deleteHrName').textContent = btn.dataset.hrName;
    document.getElementById('deleteForm').action = '/hr/' + btn.dataset.hrId;
});
</script>
@endpush
