@extends('layouts.super-admin')
@section('title', 'HR Profiles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-bold">HR / Candidates</h5>
        <small class="text-muted">All profiles across agencies</small>
    </div>
</div>

{{-- Search & Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('super-admin.hr.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search name, file #, nationality..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="agency_id" class="form-select form-select-sm">
                    <option value="">All Agencies</option>
                    @foreach($agencies as $agency)
                    <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                        {{ $agency->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="blacklisted" {{ request('status')=='blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="{{ route('super-admin.hr.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Agency</th>
                    <th>Nationality</th>
                    <th>File #</th>
                    <th>Agent</th>
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
                        <a href="{{ route('super-admin.hr.show', $hr) }}" class="fw-semibold text-decoration-none">
                            {{ $hr->full_name_en }}
                        </a>
                        @if($hr->full_name_ar)
                        <div class="text-muted" style="font-size:.75rem;direction:rtl;">{{ $hr->full_name_ar }}</div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('super-admin.agencies.show', $hr->agency_id) }}" class="text-decoration-none text-muted">
                            {{ $hr->agency?->name ?? '—' }}
                        </a>
                    </td>
                    <td>{{ $hr->nationality }}</td>
                    <td>{{ $hr->file_number ? '<code>'.$hr->file_number.'</code>' : '—' }}</td>
                    <td>{{ $hr->agent?->name ?? '—' }}</td>
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
                        <a href="{{ route('super-admin.hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary py-0">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">No HR profiles found.</td>
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
@endsection
