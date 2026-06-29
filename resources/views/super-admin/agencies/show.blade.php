@extends('layouts.super-admin')
@section('title', $agency->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-building me-1"></i> {{ $agency->name }}</h5>
    <div>
        <a href="{{ route('super-admin.agencies.edit', $agency) }}" class="btn btn-sm btn-warning me-1">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    {{-- Agency Details --}}
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-info-circle me-1"></i> Details</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th width="45%">Status</th><td><span class="badge badge-status-{{ $agency->status }}">{{ ucfirst($agency->status) }}</span></td></tr>
                    <tr><th>Owner / Contact</th><td>{{ $agency->owner_name ?? '—' }}</td></tr>
                    <tr><th>System License No.</th><td>{{ $agency->license_number ?? '—' }}</td></tr>
                    <tr><th>RL Number</th><td>{{ $agency->rl_number ?? '—' }}</td></tr>
                    <tr><th>Phone</th><td>{{ $agency->phone ?? '—' }}</td></tr>
                    <tr><th>Official Email</th><td>{{ $agency->email ?? '—' }}</td></tr>
                    <tr><th>Address</th><td>{{ $agency->address ?? '—' }}</td></tr>
                    <tr><th>Print Logo</th><td>
                        <span class="badge {{ $agency->print_logo ? 'bg-success' : 'bg-secondary' }}">{{ $agency->print_logo ? 'Yes' : 'No' }}</span>
                    </td></tr>
                    <tr><th>License Expiry</th>
                        <td class="{{ $agency->license_expiry_date?->isPast() ? 'text-danger fw-bold' : '' }}">
                            {{ $agency->license_expiry_date?->format('d M Y') ?? '—' }}
                        </td>
                    </tr>
                    <tr><th>Created</th><td>{{ $agency->created_at->format('d M Y') }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Subscription History --}}
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between">
                <span><i class="bi bi-credit-card me-1"></i> Subscription History</span>
                <a href="{{ route('super-admin.subscriptions.create') }}?agency_id={{ $agency->id }}"
                    class="btn btn-sm btn-outline-primary py-0">+ Add</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Plan</th><th>Period</th><th>Status</th><th>Payment</th></tr>
                    </thead>
                    <tbody>
                        @forelse($agency->subscriptions as $sub)
                        <tr>
                            <td>{{ $sub->plan->name }}</td>
                            <td>
                                <small>{{ $sub->start_date->format('d M Y') }}<br>→ {{ $sub->end_date->format('d M Y') }}</small>
                            </td>
                            <td><span class="badge badge-status-{{ $sub->status }}">{{ ucfirst($sub->status) }}</span></td>
                            <td>
                                @if($sub->payment_status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($sub->payment_status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    <form method="POST" action="{{ route('super-admin.subscriptions.approve', $sub) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-xs btn-success btn-sm py-0 ms-1">Approve</button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($sub->payment_status) }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No subscriptions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Users --}}
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-people me-1"></i> Users</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($agency->users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td><small>{{ $user->email }}</small></td>
                            <td><small>{{ $user->roles->pluck('name')->join(', ') }}</small></td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No users.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
