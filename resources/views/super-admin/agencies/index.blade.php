@extends('layouts.super-admin')
@section('title', 'Agencies')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-buildings me-1"></i> Agencies</h5>
    <a href="{{ route('super-admin.agencies.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Agency
    </a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search by name, email, or license number..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-search"></i> Filter
                </button>
                @if(request()->hasAny(['search','status']))
                    <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Agency</th>
                    <th>License / RL</th>
                    <th>Contact</th>
                    <th>Subscription</th>
                    <th>Users</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agencies as $agency)
                <tr>
                    <td class="text-muted">{{ $loop->iteration + ($agencies->currentPage() - 1) * $agencies->perPage() }}</td>
                    <td>
                        <div class="fw-semibold">{{ $agency->name }}</div>
                        @if($agency->license_expiry_date)
                            <small class="{{ $agency->license_expiry_date->isPast() ? 'text-danger' : 'text-muted' }}">
                                <i class="bi bi-calendar3"></i> License exp: {{ $agency->license_expiry_date->format('d M Y') }}
                            </small>
                        @endif
                    </td>
                    <td>
                        <small>{{ $agency->license_number ?? '—' }}</small><br>
                        <small class="text-muted">{{ $agency->rl_number ?? '' }}</small>
                    </td>
                    <td>
                        <small>{{ $agency->phone ?? '—' }}</small><br>
                        <small class="text-muted">{{ $agency->email ?? '' }}</small>
                    </td>
                    <td>
                        @if($agency->activeSubscription)
                            <span class="badge badge-status-{{ $agency->activeSubscription->status }}">
                                {{ $agency->activeSubscription->plan->name ?? '—' }}
                            </span>
                            <div style="font-size:.7rem;" class="text-muted">
                                Exp: {{ $agency->activeSubscription->end_date->format('d M Y') }}
                            </div>
                        @else
                            <span class="badge bg-light text-secondary">No Subscription</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $agency->users_count }}</td>
                    <td>
                        <span class="badge badge-status-{{ $agency->status }}">{{ ucfirst($agency->status) }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('super-admin.agencies.show', $agency) }}"
                                class="btn btn-sm btn-outline-info py-0" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('super-admin.agencies.edit', $agency) }}"
                                class="btn btn-sm btn-outline-warning py-0" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('super-admin.agencies.toggle-status', $agency) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm py-0 {{ $agency->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    title="{{ $agency->status === 'active' ? 'Suspend' : 'Activate' }}"
                                    onclick="return confirm('Change agency status?')">
                                    <i class="bi bi-{{ $agency->status === 'active' ? 'pause-circle' : 'play-circle' }}"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('super-admin.agencies.destroy', $agency) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0" title="Delete"
                                    onclick="return confirm('Delete this agency? This cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i> No agencies found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($agencies->hasPages())
    <div class="card-footer bg-white">
        {{ $agencies->links() }}
    </div>
    @endif
</div>
@endsection
