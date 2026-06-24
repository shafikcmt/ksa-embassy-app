<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — VisaDeskPro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #1a1f2e;
            --sidebar-hover: #252b3b;
            --sidebar-active: #0d6efd;
            --topbar-height: 56px;
        }
        body { background: #f0f2f5; font-size: 0.875rem; }
        #sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width); background: var(--sidebar-bg);
            z-index: 1040; overflow-y: auto; display: flex; flex-direction: column;
        }
        #sidebar .brand {
            padding: 1rem 1.25rem; border-bottom: 1px solid #2d3347;
            color: #fff; font-weight: 700; font-size: 0.95rem; flex-shrink: 0;
        }
        #sidebar .brand span { color: #4da6ff; }
        #sidebar .nav-section { flex: 1; overflow-y: auto; }
        #sidebar .nav-label {
            padding: .5rem 1.25rem .25rem; color: #5a6279;
            font-size: 0.7rem; text-transform: uppercase; letter-spacing: .08em;
        }
        #sidebar .nav-link {
            color: #9aa3b8; padding: .55rem 1.25rem;
            border-radius: 0; display: flex; align-items: center; gap: .6rem;
            transition: background .15s, color .15s;
        }
        #sidebar .nav-link:hover { background: var(--sidebar-hover); color: #fff; }
        #sidebar .nav-link.active { background: var(--sidebar-active); color: #fff; }
        #sidebar .nav-link i { font-size: 1rem; width: 1.1rem; flex-shrink: 0; }
        #sidebar .sidebar-footer {
            padding: .75rem 1.25rem; border-top: 1px solid #2d3347;
            font-size: .72rem; color: #5a6279; flex-shrink: 0;
        }
        #topbar {
            position: fixed; top: 0; left: var(--sidebar-width);
            right: 0; height: var(--topbar-height); background: #fff;
            border-bottom: 1px solid #e2e6ef; z-index: 1030;
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
        /* Status badges */
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
        .card { border: 1px solid #e2e6ef; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        .card-header { background: #f8f9fc; border-bottom: 1px solid #e2e6ef; font-weight: 600; }
        .table > thead > tr > th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; background: #f8f9fc; }
        .stat-card { border-left: 4px solid; }
        .stat-card.blue   { border-color: #3b82f6; }
        .stat-card.green  { border-color: #10b981; }
        .stat-card.red    { border-color: #ef4444; }
        .stat-card.orange { border-color: #f59e0b; }
        .stat-card.purple { border-color: #8b5cf6; }
        .stat-card.teal   { border-color: #14b8a6; }
        .flash-bar { border-radius: 8px; font-size: .82rem; }
        @media (max-width: 992px) {
            #sidebar { width: 210px; --sidebar-width: 210px; }
            #topbar { left: 210px; }
            #main-content { margin-left: 210px; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<div id="sidebar">
    <div class="brand">
        <i class="bi bi-passport"></i> VisaDesk<span>Pro</span><br>
        <small style="font-size:.68rem;font-weight:400;color:#5a6279;">Super Admin Panel</small>
    </div>

    <div class="nav-section mt-1">
        <div class="nav-label">Main</div>
        <a href="{{ route('super-admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('super-admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="nav-label mt-2">Agencies</div>
        <a href="{{ route('super-admin.agencies.index') }}"
           class="nav-link {{ request()->routeIs('super-admin.agencies.*') ? 'active' : '' }}">
            <i class="bi bi-buildings"></i> Agencies
        </a>

        <div class="nav-label mt-2">Data</div>
        <a href="{{ route('super-admin.agents.index') }}"
           class="nav-link {{ request()->routeIs('super-admin.agents.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Agents
        </a>
        <a href="{{ route('super-admin.hr.index') }}"
           class="nav-link {{ request()->routeIs('super-admin.hr.*') ? 'active' : '' }}">
            <i class="bi bi-person-vcard"></i> HR Profiles
        </a>
        <a href="{{ route('super-admin.embassy-lists.index') }}"
           class="nav-link {{ request()->routeIs('super-admin.embassy-lists.*') ? 'active' : '' }}">
            <i class="bi bi-list-ol"></i> Embassy Lists
        </a>

        <div class="nav-label mt-2">Subscriptions</div>
        <a href="{{ route('super-admin.plans.index') }}"
           class="nav-link {{ request()->routeIs('super-admin.plans.*') ? 'active' : '' }}">
            <i class="bi bi-grid-3x3-gap"></i> Plans
        </a>
        <a href="{{ route('super-admin.subscriptions.index') }}"
           class="nav-link {{ request()->routeIs('super-admin.subscriptions.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i> Subscriptions
        </a>

        <div class="nav-label mt-2">System</div>
        <a href="{{ route('super-admin.settings.index') }}"
           class="nav-link {{ request()->routeIs('super-admin.settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Settings
        </a>
    </div>

    <div class="sidebar-footer">
        <i class="bi bi-shield-check me-1" style="color:#4da6ff;"></i>
        {{ auth()->user()->name }}
    </div>
</div>

{{-- Topbar --}}
<div id="topbar">
    <div class="page-title">@yield('page-title', 'Super Admin')</div>
    <div class="d-flex align-items-center gap-3">
        <span class="badge" style="background:#fee2e2;color:#991b1b;font-size:.7rem;font-weight:600;padding:4px 10px;">SUPER ADMIN</span>
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
        <div class="alert alert-success alert-dismissible fade show py-2 flash-bar mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 flash-bar mb-3" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2 flash-bar mb-3" role="alert">
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
