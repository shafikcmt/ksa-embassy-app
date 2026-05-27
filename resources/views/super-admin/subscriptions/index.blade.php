@extends('layouts.super-admin')
@section('title', 'Subscriptions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-credit-card me-1"></i> Subscriptions</h5>
    <a href="{{ route('super-admin.subscriptions.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Assign Subscription
    </a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <select name="agency_id" class="form-select form-select-sm">
                    <option value="">All Agencies</option>
                    @foreach($agencies as $ag)
                        <option value="{{ $ag->id }}" @selected(request('agency_id') == $ag->id)>{{ $ag->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['trial','active','expired','suspended'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i> Filter</button>
                @if(request()->hasAny(['status','agency_id']))
                    <a href="{{ route('super-admin.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                    <th>Plan</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                <tr>
                    <td class="text-muted">{{ $loop->iteration + ($subscriptions->currentPage() - 1) * $subscriptions->perPage() }}</td>
                    <td class="fw-semibold">{{ $sub->agency->name }}</td>
                    <td>{{ $sub->plan->name }}</td>
                    <td>
                        <small>{{ $sub->start_date->format('d M Y') }}<br>→ {{ $sub->end_date->format('d M Y') }}</small>
                        @if($sub->isActive())
                            <div style="font-size:.7rem;" class="text-success">{{ $sub->daysRemaining() }} days left</div>
                        @endif
                    </td>
                    <td><span class="badge badge-status-{{ $sub->status }}">{{ ucfirst($sub->status) }}</span></td>
                    <td>
                        @php $pc = $sub->payment_status; @endphp
                        <span class="badge {{ $pc === 'paid' ? 'bg-success' : ($pc === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ ucfirst($pc) }}
                        </span>
                    </td>
                    <td>${{ number_format($sub->amount, 2) }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            @if($sub->payment_status === 'pending')
                            <form method="POST" action="{{ route('super-admin.subscriptions.approve', $sub) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-success py-0" title="Approve Payment">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('super-admin.subscriptions.edit', $sub) }}"
                                class="btn btn-sm btn-outline-warning py-0" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('super-admin.subscriptions.destroy', $sub) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0"
                                    onclick="return confirm('Delete this subscription?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No subscriptions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())
    <div class="card-footer bg-white">{{ $subscriptions->links() }}</div>
    @endif
</div>
@endsection
