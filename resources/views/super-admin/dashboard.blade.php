@extends('layouts.super-admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-bold">Welcome to VisaDeskPro</h5>
        <small class="text-muted">System Overview · {{ now()->format('l, d F Y') }}</small>
    </div>
</div>

{{-- ── ROW 1: Primary stats ───────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <a href="{{ route('super-admin.agencies.index') }}" class="text-decoration-none">
            <div class="card stat-card blue p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Total Agencies</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_agencies'] }}</div>
                        <small class="text-success">{{ $stats['active_agencies'] }} active</small>
                    </div>
                    <i class="bi bi-buildings fs-2 text-primary opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="{{ route('super-admin.subscriptions.index') }}" class="text-decoration-none">
            <div class="card stat-card green p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Active Subscriptions</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['active_subscriptions'] }}</div>
                        <small class="text-danger">{{ $stats['expired_subscriptions'] }} expired</small>
                    </div>
                    <i class="bi bi-credit-card fs-2 text-success opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card red p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Suspended</div>
                    <div class="fs-3 fw-bold text-dark">{{ $stats['suspended_agencies'] }}</div>
                    <small class="text-muted">agencies</small>
                </div>
                <i class="bi bi-slash-circle fs-2 text-danger opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="{{ route('super-admin.agents.index') }}" class="text-decoration-none">
            <div class="card stat-card orange p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Total Users</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_users'] }}</div>
                        <small class="text-muted">agency staff</small>
                    </div>
                    <i class="bi bi-people fs-2 text-warning opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- ── ROW 2: Data stats ──────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="{{ route('super-admin.agents.index') }}" class="text-decoration-none">
            <div class="card stat-card blue p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Total Agents</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_agents'] }}</div>
                        <small class="text-muted">all agencies</small>
                    </div>
                    <i class="bi bi-person-badge fs-2 text-primary opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('super-admin.hr.index') }}" class="text-decoration-none">
            <div class="card stat-card green p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">HR Profiles</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_hr'] }}</div>
                        <small class="text-muted">all agencies</small>
                    </div>
                    <i class="bi bi-person-vcard fs-2 text-success opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('super-admin.embassy-lists.index') }}" class="text-decoration-none">
            <div class="card stat-card orange p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">Embassy Lists</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_embassy_lists'] }}</div>
                        <small class="text-muted">active</small>
                    </div>
                    <i class="bi bi-list-ol fs-2 text-warning opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card purple p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:.68rem;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">PDFs Generated</div>
                    <div class="fs-3 fw-bold text-dark">{{ $stats['total_documents'] }}</div>
                    <small class="text-muted">{{ $stats['docs_this_month'] }} this month</small>
                </div>
                <i class="bi bi-file-earmark-pdf fs-2 opacity-25" style="color:#8b5cf6;"></i>
            </div>
        </div>
    </div>
</div>

{{-- ── ROW 3: Agencies + Expiring subs ────────────── --}}
<div class="row g-3 mb-3">

    {{-- Recent Agencies --}}
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span><i class="bi bi-buildings me-1"></i> Recent Agencies</span>
                <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-sm btn-outline-primary py-0">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Agency</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAgencies as $agency)
                        <tr>
                            <td>
                                <div class="fw-semibold" style="font-size:.82rem;">{{ $agency->name }}</div>
                                <small class="text-muted">{{ $agency->email }}</small>
                            </td>
                            <td>
                                @if($agency->activeSubscription)
                                    <span style="background:#eff6ff;color:#1e40af;font-size:.7rem;padding:2px 7px;border-radius:10px;font-weight:600;">
                                        {{ $agency->activeSubscription->plan->name ?? '—' }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-status-{{ $agency->status }}">{{ ucfirst($agency->status) }}</span>
                            </td>
                            <td class="text-muted" style="font-size:.78rem;">{{ $agency->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('super-admin.agencies.show', $agency) }}"
                                   class="btn btn-sm btn-outline-secondary py-0">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No agencies yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Expiring Subscriptions --}}
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span><i class="bi bi-exclamation-triangle text-warning me-1"></i> Expiring Soon</span>
                <small class="text-muted">Within 14 days</small>
            </div>
            @if($expiringSubscriptions->count())
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr><th>Agency</th><th>Plan</th><th>Days</th></tr>
                    </thead>
                    <tbody>
                        @foreach($expiringSubscriptions as $sub)
                        <tr>
                            <td>
                                <a href="{{ route('super-admin.agencies.show', $sub->agency) }}" class="text-decoration-none fw-semibold" style="font-size:.82rem;">
                                    {{ $sub->agency->name }}
                                </a>
                            </td>
                            <td style="font-size:.78rem;">{{ $sub->plan->name }}</td>
                            <td>
                                <span class="badge {{ $sub->daysRemaining() <= 3 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size:.7rem;">
                                    {{ $sub->daysRemaining() }}d
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <x-empty-state icon="bi-calendar-check" title="None expiring soon" size="sm" />
            @endif
        </div>
    </div>
</div>

{{-- ── ROW 4: Top Agencies + Recent Audit Logs ─────── --}}
<div class="row g-3">

    {{-- Top agencies by HR count --}}
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-trophy me-1 text-warning"></i> Top Agencies by HR Count
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr><th>Agency</th><th class="text-center">HR</th><th class="text-center">Lists</th><th>Plan</th></tr>
                    </thead>
                    <tbody>
                        @forelse($topAgencies as $agency)
                        <tr>
                            <td>
                                <a href="{{ route('super-admin.agencies.show', $agency) }}" class="text-decoration-none fw-semibold" style="font-size:.82rem;">
                                    {{ $agency->name }}
                                </a>
                            </td>
                            <td class="text-center fw-bold">{{ $agency->hr_profiles_count }}</td>
                            <td class="text-center text-muted">{{ $agency->embassy_lists_count }}</td>
                            <td>
                                @if($agency->activeSubscription)
                                <span style="font-size:.72rem;background:#eff6ff;color:#1e40af;padding:1px 6px;border-radius:8px;">
                                    {{ $agency->activeSubscription->plan->name ?? '—' }}
                                </span>
                                @else
                                <span class="text-muted" style="font-size:.72rem;">No plan</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No agency data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Audit Logs --}}
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span><i class="bi bi-journal-text me-1"></i> Recent Activity Log</span>
                <small class="text-muted">Last 10 events</small>
            </div>
            @if($recentAuditLogs->count())
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr><th>Action</th><th>Agency</th><th>User</th><th>When</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentAuditLogs as $log)
                        <tr>
                            <td>
                                <span style="background:#f1f5f9;color:#475569;font-size:.72rem;padding:2px 7px;border-radius:4px;font-weight:600;font-family:monospace;">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td style="font-size:.78rem;">{{ $log->agency?->name ?? '—' }}</td>
                            <td style="font-size:.78rem;">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="text-muted" style="font-size:.75rem;">{{ $log->created_at->format('d M, H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <x-empty-state icon="bi-journal" title="No activity yet" size="sm" />
            @endif
        </div>
    </div>
</div>

@endsection
