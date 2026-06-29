@extends('layouts.super-admin')
@section('title', 'Plans')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap me-1"></i> Subscription Plans</h5>
    <a href="{{ route('super-admin.plans.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Plan
    </a>
</div>

<div class="row g-3">
    @forelse($plans as $plan)
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 {{ !$plan->is_active ? 'opacity-75' : '' }}">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold">{{ $plan->name }}</span>
                <span class="badge {{ $plan->is_active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="card-body">
                <div class="fs-4 fw-bold text-primary mb-1">
                    {{ $plan->priceLabel('') }}
                    <small class="fs-6 text-muted fw-normal">/ {{ $plan->duration_days }}d</small>
                </div>
                @if($plan->description)
                    <p class="text-muted small mb-2">{{ $plan->description }}</p>
                @endif
                <table class="table table-sm table-borderless mb-0 small">
                    <tr><td class="text-muted">Max HR</td><td class="fw-semibold">{{ $plan->max_hr == 9999 ? 'Unlimited' : number_format($plan->max_hr) }}</td></tr>
                    <tr><td class="text-muted">Max Users</td><td class="fw-semibold">{{ $plan->max_users }}</td></tr>
                    <tr><td class="text-muted">Max Agents</td><td class="fw-semibold">{{ $plan->max_agents == 999 ? 'Unlimited' : $plan->max_agents }}</td></tr>
                    <tr><td class="text-muted">Embassy Lists/mo</td><td class="fw-semibold">{{ $plan->max_embassy_lists_monthly == 999 ? 'Unlimited' : $plan->max_embassy_lists_monthly }}</td></tr>
                    <tr><td class="text-muted">PDF/mo</td><td class="fw-semibold">{{ $plan->max_pdf_monthly == 9999 ? 'Unlimited' : $plan->max_pdf_monthly }}</td></tr>
                    <tr><td class="text-muted">Storage</td><td class="fw-semibold">{{ $plan->storage_limit_mb >= 1024 ? round($plan->storage_limit_mb/1024).'GB' : $plan->storage_limit_mb.'MB' }}</td></tr>
                </table>
            </div>
            <div class="card-footer bg-white d-flex gap-2 py-2">
                <a href="{{ route('super-admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-warning flex-fill">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <form method="POST" action="{{ route('super-admin.plans.destroy', $plan) }}">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Delete this plan? Agencies with active subscriptions on this plan will be affected.')"
                        title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
                <small class="text-muted ms-auto align-self-center">{{ $plan->subscriptions_count }} subs</small>
            </div>
        </div>
    </div>
    @empty
    <div class="col">
        <div class="alert alert-info">No plans created yet. <a href="{{ route('super-admin.plans.create') }}">Create the first plan</a>.</div>
    </div>
    @endforelse
</div>
@endsection
