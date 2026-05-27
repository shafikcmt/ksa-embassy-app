<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ auth()->user()->agency->name ?? 'Agency' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-width: 240px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --topbar-height: 56px;
        }
        body { background: #f1f5f9; font-size: 0.875rem; }
        #sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width); background: var(--sidebar-bg);
            z-index: 1040; overflow-y: auto; display: flex; flex-direction: column;
        }
        #sidebar .brand {
            padding: .9rem 1.25rem; border-bottom: 1px solid #1e293b;
        }
        #sidebar .brand .agency-name {
            color: #fff; font-weight: 700; font-size: .85rem; line-height: 1.3;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        #sidebar .brand .sub-label { color: #64748b; font-size: .7rem; }
        #sidebar .nav-section { flex: 1; overflow-y: auto; }
        #sidebar .nav-label {
            padding: .5rem 1.25rem .2rem; color: #475569;
            font-size: .68rem; text-transform: uppercase; letter-spacing: .08em;
        }
        #sidebar .nav-link {
            color: #94a3b8; padding: .5rem 1.25rem;
            display: flex; align-items: center; gap: .6rem;
            transition: background .15s, color .15s; border-radius: 0;
        }
        #sidebar .nav-link:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        #sidebar .nav-link.active { background: var(--sidebar-active); color: #fff; }
        #sidebar .nav-link i { font-size: .95rem; width: 1.1rem; flex-shrink: 0; }
        #sidebar .sidebar-footer {
            padding: .75rem 1.25rem; border-top: 1px solid #1e293b;
            font-size: .72rem;
        }
        #topbar {
            position: fixed; top: 0; left: var(--sidebar-width);
            right: 0; height: var(--topbar-height); background: #fff;
            border-bottom: 1px solid #e2e8f0; z-index: 1030;
            display: flex; align-items: center; padding: 0 1.5rem;
            justify-content: space-between;
        }
        #topbar .page-title { font-weight: 600; font-size: .9rem; color: #1e293b; }
        #main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 1.5rem;
            min-height: calc(100vh - var(--topbar-height));
        }
        .card { border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        .table > thead > tr > th {
            font-size: .72rem; text-transform: uppercase;
            letter-spacing: .04em; color: #64748b; background: #f8fafc;
        }
        /* Status badge classes */
        .badge-status-active    { background: #d1fae5 !important; color: #065f46 !important; }
        .badge-status-trial     { background: #dbeafe !important; color: #1e40af !important; }
        .badge-status-expired   { background: #f3f4f6 !important; color: #6b7280 !important; }
        .badge-status-suspended { background: #fee2e2 !important; color: #991b1b !important; }
        .badge-status-inactive  { background: #f3f4f6 !important; color: #6b7280 !important; }
        .badge-status-draft     { background: #fef3c7 !important; color: #92400e !important; }
        .badge-status-finalized { background: #d1fae5 !important; color: #065f46 !important; }
        .badge-status-printed   { background: #dbeafe !important; color: #1e40af !important; }
        .badge-status-cancelled { background: #f3f4f6 !important; color: #6b7280 !important; }
        .badge-status-listed    { background: #ede9fe !important; color: #5b21b6 !important; }
        .badge-status-blacklisted { background: #fee2e2 !important; color: #991b1b !important; }
        /* Stat cards */
        .stat-card { border-left: 4px solid; }
        .stat-card.blue   { border-color: #3b82f6; }
        .stat-card.green  { border-color: #10b981; }
        .stat-card.orange { border-color: #f59e0b; }
        .stat-card.purple { border-color: #8b5cf6; }
        .stat-card.teal   { border-color: #14b8a6; }
        /* Flash messages */
        .flash-bar { border-radius: 8px; font-size: .82rem; }
        @media (max-width: 992px) {
            #sidebar { width: 200px; --sidebar-width: 200px; }
            #topbar { left: 200px; }
            #main-content { margin-left: 200px; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<div id="sidebar">
    <div class="brand">
        <div class="agency-name">
            <i class="bi bi-building text-primary"></i>
            {{ auth()->user()->agency->name ?? 'Agency' }}
        </div>
        <div class="sub-label">KSA Embassy File System</div>
    </div>

    <div class="nav-section mt-1">
        <div class="nav-label">Overview</div>
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="nav-label mt-2">Operations</div>
        <a href="{{ route('agents.index') }}"
           class="nav-link {{ request()->routeIs('agents.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Agents
        </a>
        <a href="{{ route('hr.index') }}"
           class="nav-link {{ request()->routeIs('hr.*') ? 'active' : '' }}">
            <i class="bi bi-person-vcard"></i> HR / Candidates
        </a>
        <a href="{{ route('embassy-lists.index') }}"
           class="nav-link {{ request()->routeIs('embassy-lists.*') ? 'active' : '' }}">
            <i class="bi bi-list-ol"></i> Embassy Lists
        </a>

        <div class="nav-label mt-2">Documents</div>
        <a href="{{ route('hr.index') }}"
           class="nav-link {{ request()->routeIs('hr.documents') || request()->routeIs('hr.print.*') || request()->routeIs('hr.download.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-pdf"></i> Print / PDF
        </a>

        <div class="nav-label mt-2">Account</div>
        <a href="{{ route('settings.index') }}"
           class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Settings
        </a>
    </div>

    {{-- Sidebar footer: subscription status --}}
    <div class="sidebar-footer">
        @php $sidebarSub = auth()->user()->agency?->activeSubscription; @endphp
        @if($sidebarSub)
            <div style="color:#64748b;">Plan: <span style="color:#e2e8f0;font-weight:600;">{{ $sidebarSub->plan->name ?? '—' }}</span></div>
            <div style="margin-top:2px;">
                @if($sidebarSub->daysRemaining() <= 7)
                    <span style="color:#f87171;font-weight:600;">
                        <i class="bi bi-clock"></i> {{ $sidebarSub->daysRemaining() }}d left
                    </span>
                @else
                    <span style="color:#34d399;">
                        <i class="bi bi-check-circle"></i> {{ $sidebarSub->daysRemaining() }}d left
                    </span>
                @endif
            </div>
        @else
            <span style="color:#f87171;"><i class="bi bi-exclamation-triangle"></i> No subscription</span>
        @endif
    </div>
</div>

{{-- Topbar --}}
<div id="topbar">
    <div class="page-title">@yield('page-title', 'Dashboard')</div>
    <div class="d-flex align-items-center gap-3">
        {{-- Alerts bell from notices (cached 5 min per agency) --}}
        @php
            $agencyId = auth()->user()->agency_id;
            $noticeCount = \Illuminate\Support\Facades\Cache::remember(
                "notices_count_{$agencyId}", 300,
                fn() => \App\Models\Notice::active()->forAgency($agencyId)->count()
            );
        @endphp
        @if($noticeCount > 0)
        <span class="position-relative" title="{{ $noticeCount }} active notice(s)">
            <i class="bi bi-bell text-warning" style="font-size:1.1rem;"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;">{{ $noticeCount }}</span>
        </span>
        @endif

        <span class="text-muted" style="font-size:.82rem;">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

{{-- Main --}}
<div id="main-content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 flash-bar mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 flash-bar mb-3">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2 flash-bar mb-3">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
