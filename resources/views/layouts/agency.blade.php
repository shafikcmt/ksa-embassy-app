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
            --sidebar-width: 248px;
            --sidebar-bg: #0f172a;
            --sidebar-bg2: #0b1120;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --topbar-height: 60px;
            --brand-blue: #2563eb;
        }
        * { scrollbar-width: thin; scrollbar-color: #334155 transparent; }
        body { background: #f1f5f9; font-size: 0.875rem; color: #1e293b; }

        /* ── Sidebar ───────────────────────────────────────────── */
        #sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, var(--sidebar-bg2) 100%);
            z-index: 1045; overflow-y: auto; display: flex; flex-direction: column;
            transition: transform .25s ease;
        }
        #sidebar .brand {
            padding: 1rem 1.25rem; border-bottom: 1px solid #1e293b;
            display: flex; align-items: center; gap: .65rem;
        }
        #sidebar .brand .brand-icon {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, #2563eb, #10b981);
            display: grid; place-items: center; color: #fff; font-size: 1.1rem;
        }
        #sidebar .brand .agency-name {
            color: #fff; font-weight: 700; font-size: .85rem; line-height: 1.25;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        #sidebar .brand .sub-label { color: #64748b; font-size: .68rem; }
        #sidebar .nav-section { flex: 1; overflow-y: auto; padding: .5rem 0 1rem; }
        #sidebar .nav-label {
            padding: .85rem 1.25rem .35rem; color: #475569;
            font-size: .66rem; text-transform: uppercase; letter-spacing: .1em; font-weight: 600;
        }
        #sidebar .nav-link {
            color: #94a3b8; padding: .55rem 1.25rem; margin: 1px .6rem;
            display: flex; align-items: center; gap: .7rem; border-radius: 8px;
            transition: background .15s, color .15s; font-size: .82rem; position: relative;
        }
        #sidebar .nav-link:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        #sidebar .nav-link.active { background: var(--sidebar-active); color: #fff; box-shadow: 0 4px 12px -4px rgba(37,99,235,.7); }
        #sidebar .nav-link.active::before {
            content: ''; position: absolute; left: -.6rem; top: 20%; bottom: 20%;
            width: 3px; border-radius: 3px; background: #60a5fa;
        }
        #sidebar .nav-link i { font-size: 1rem; width: 1.2rem; flex-shrink: 0; text-align: center; }
        #sidebar .nav-link .nav-count {
            margin-left: auto; font-size: .66rem; background: #1e293b; color: #cbd5e1;
            padding: .05rem .4rem; border-radius: 999px; min-width: 1.3rem; text-align: center;
        }
        #sidebar .nav-link.active .nav-count { background: rgba(255,255,255,.25); color: #fff; }
        /* Sidebar plan card */
        #sidebar .sidebar-footer { padding: .9rem 1rem 1rem; border-top: 1px solid #1e293b; }
        .plan-card {
            background: #1e293b; border-radius: 10px; padding: .7rem .8rem;
        }
        .plan-card .plan-name { color: #fff; font-weight: 600; font-size: .78rem; }
        .plan-card .plan-meta { color: #94a3b8; font-size: .68rem; }
        .plan-bar { height: 4px; background: #334155; border-radius: 3px; overflow: hidden; margin-top: .45rem; }
        .plan-bar > div { height: 100%; border-radius: 3px; }

        /* ── Topbar ───────────────────────────────────────────── */
        #topbar {
            position: fixed; top: 0; left: var(--sidebar-width); right: 0;
            height: var(--topbar-height); background: #fff;
            border-bottom: 1px solid #e2e8f0; z-index: 1030;
            display: flex; align-items: center; padding: 0 1.25rem; gap: 1rem;
            justify-content: space-between;
        }
        #topbar .tb-left { display: flex; align-items: center; gap: .85rem; min-width: 0; }
        #topbar .page-title { font-weight: 700; font-size: .98rem; color: #0f172a; white-space: nowrap; }
        #topbar .tb-search {
            display: flex; align-items: center; gap: .4rem; background: #f1f5f9;
            border: 1px solid #e2e8f0; border-radius: 8px; padding: .3rem .65rem; width: 240px;
        }
        #topbar .tb-search input { border: 0; background: transparent; outline: none; font-size: .8rem; width: 100%; }
        #topbar .tb-right { display: flex; align-items: center; gap: .9rem; }
        .user-chip { display: flex; align-items: center; gap: .55rem; }
        .user-chip .avatar {
            width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
            background: linear-gradient(135deg, #2563eb, #10b981); color: #fff;
            display: grid; place-items: center; font-weight: 700; font-size: .8rem;
        }
        .user-chip .u-name { font-weight: 600; font-size: .8rem; line-height: 1.1; color: #0f172a; }
        .user-chip .u-role { font-size: .68rem; color: #64748b; }
        .role-badge { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
            padding: .1rem .4rem; border-radius: 5px; background: #dbeafe; color: #1e40af; }
        .role-badge.staff { background: #ede9fe; color: #5b21b6; }
        .hamburger { display: none; border: 0; background: #f1f5f9; border-radius: 8px;
            width: 38px; height: 38px; align-items: center; justify-content: center; cursor: pointer; color: #334155; font-size: 1.15rem; }

        /* ── Layout ───────────────────────────────────────────── */
        #main-content {
            margin-left: var(--sidebar-width); margin-top: var(--topbar-height);
            padding: 1.5rem; min-height: calc(100vh - var(--topbar-height));
        }
        #sidebarOverlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 1040; }
        #navToggle { position: absolute; opacity: 0; pointer-events: none; }

        /* ── Cards / tables shared ───────────────────────────── */
        .card { border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.05); border-radius: 12px; }
        .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        .table > thead > tr > th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; background: #f8fafc; }
        /* Status badges */
        .badge-status-active{background:#d1fae5!important;color:#065f46!important}
        .badge-status-trial{background:#dbeafe!important;color:#1e40af!important}
        .badge-status-expired{background:#f3f4f6!important;color:#6b7280!important}
        .badge-status-suspended{background:#fee2e2!important;color:#991b1b!important}
        .badge-status-inactive{background:#f3f4f6!important;color:#6b7280!important}
        .badge-status-draft{background:#fef3c7!important;color:#92400e!important}
        .badge-status-finalized{background:#d1fae5!important;color:#065f46!important}
        .badge-status-printed{background:#dbeafe!important;color:#1e40af!important}
        .badge-status-cancelled{background:#f3f4f6!important;color:#6b7280!important}
        .badge-status-listed{background:#ede9fe!important;color:#5b21b6!important}
        .badge-status-blacklisted{background:#fee2e2!important;color:#991b1b!important}
        .flash-bar { border-radius: 10px; font-size: .82rem; }

        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); box-shadow: 4px 0 24px rgba(0,0,0,.25); }
            #navToggle:checked ~ #sidebar { transform: translateX(0); }
            #navToggle:checked ~ #sidebarOverlay { display: block; }
            #topbar { left: 0; }
            #main-content { margin-left: 0; }
            .hamburger { display: inline-flex; }
            #topbar .tb-search { display: none; }
            #topbar .page-title { font-size: .9rem; }
        }
        @media (max-width: 576px) {
            #main-content { padding: 1rem; }
            .user-chip .u-name, .user-chip .u-role { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>

@php
    $authUser   = auth()->user();
    $isAdmin    = method_exists($authUser, 'isAgencyAdmin') ? $authUser->isAgencyAdmin() : true;
    $sidebarSub = $authUser->agency?->activeSubscription;
    $initials   = collect(explode(' ', trim($authUser->name)))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
@endphp

{{-- Mobile drawer toggle (pure CSS, no JS) --}}
<input type="checkbox" id="navToggle" aria-hidden="true">

{{-- ── Sidebar ──────────────────────────────────────────────── --}}
<div id="sidebar">
    <div class="brand">
        <span class="brand-icon"><i class="bi bi-buildings"></i></span>
        <div style="min-width:0;">
            <div class="agency-name">{{ $authUser->agency->name ?? 'Agency' }}</div>
            <div class="sub-label">KSA Embassy File System</div>
        </div>
    </div>

    <div class="nav-section">
        <div class="nav-label">Overview</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="nav-label">Operations</div>
        <a href="{{ route('agents.index') }}" class="nav-link {{ request()->routeIs('agents.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Agents
        </a>
        <a href="{{ route('hr.index') }}" class="nav-link {{ request()->routeIs('hr.index') || request()->routeIs('hr.create') || request()->routeIs('hr.edit') || request()->routeIs('hr.show') ? 'active' : '' }}">
            <i class="bi bi-person-vcard"></i> HR / Candidates
        </a>
        <a href="{{ route('embassy-lists.index') }}" class="nav-link {{ request()->routeIs('embassy-lists.*') ? 'active' : '' }}">
            <i class="bi bi-list-ol"></i> Embassy Lists
        </a>

        <div class="nav-label">Documents</div>
        <a href="{{ route('hr.index') }}" class="nav-link {{ request()->routeIs('hr.documents') || request()->routeIs('hr.print.*') || request()->routeIs('hr.download.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-pdf"></i> Print / PDF
        </a>

        <div class="nav-label">Account</div>
        @if($isAdmin)
        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <i class="bi bi-gear"></i> Settings
        </a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="nav-link w-100 border-0 bg-transparent text-start">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>

    {{-- Plan status --}}
    <div class="sidebar-footer">
        @if($sidebarSub)
            @php
                $daysLeft = $sidebarSub->daysRemaining();
                $lifeTotal = optional($sidebarSub->start_date)->diffInDays($sidebarSub->end_date) ?: 30;
                $lifePct = max(4, min(100, round(($daysLeft / max(1, $lifeTotal)) * 100)));
                $barColor = $daysLeft <= 3 ? '#ef4444' : ($daysLeft <= 7 ? '#f59e0b' : '#10b981');
            @endphp
            <div class="plan-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="plan-name">{{ $sidebarSub->plan->name ?? 'Plan' }}</span>
                    <span class="badge badge-status-{{ $sidebarSub->status }}" style="font-size:.6rem;">{{ ucfirst($sidebarSub->status) }}</span>
                </div>
                <div class="plan-meta mt-1">
                    <i class="bi bi-clock"></i> {{ $daysLeft }} days left
                    @if($sidebarSub->end_date) · {{ $sidebarSub->end_date->format('d M Y') }} @endif
                </div>
                <div class="plan-bar"><div style="width:{{ $lifePct }}%;background:{{ $barColor }};"></div></div>
            </div>
        @else
            <div class="plan-card" style="background:#3f1d1d;">
                <span style="color:#fca5a5;font-size:.74rem;"><i class="bi bi-exclamation-triangle"></i> No active subscription</span>
            </div>
        @endif
    </div>
</div>

{{-- Overlay (click to close drawer on mobile) --}}
<label for="navToggle" id="sidebarOverlay" aria-label="Close menu"></label>

{{-- ── Topbar ───────────────────────────────────────────────── --}}
<div id="topbar">
    <div class="tb-left">
        <label for="navToggle" class="hamburger" aria-label="Toggle menu"><i class="bi bi-list"></i></label>
        <div class="page-title">@yield('page-title', 'Dashboard')</div>
        <form method="GET" action="{{ route('hr.index') }}" class="tb-search">
            <i class="bi bi-search text-muted" style="font-size:.8rem;"></i>
            <input type="text" name="search" placeholder="Search candidates…" value="{{ request('search') }}">
        </form>
    </div>
    <div class="tb-right">
        @php
            $agencyId = $authUser->agency_id;
            $noticeCount = \Illuminate\Support\Facades\Cache::remember(
                "notices_count_{$agencyId}", 300,
                fn() => \App\Models\Notice::active()->forAgency($agencyId)->count()
            );
        @endphp
        @if($noticeCount > 0)
        <span class="position-relative" title="{{ $noticeCount }} active notice(s)">
            <i class="bi bi-bell text-warning" style="font-size:1.15rem;"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.55rem;">{{ $noticeCount }}</span>
        </span>
        @endif

        <div class="user-chip">
            <span class="avatar">{{ strtoupper($initials ?: 'U') }}</span>
            <div>
                <div class="u-name">{{ $authUser->name }}</div>
                <div class="u-role">
                    <span class="role-badge {{ $isAdmin ? '' : 'staff' }}">{{ $isAdmin ? 'Agency Admin' : 'Agency Staff' }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

{{-- ── Main ─────────────────────────────────────────────────── --}}
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
<script>
    // Close mobile drawer after navigating
    document.querySelectorAll('#sidebar .nav-link').forEach(function(a){
        a.addEventListener('click', function(){
            var t = document.getElementById('navToggle'); if (t) t.checked = false;
        });
    });
</script>
@stack('scripts')
</body>
</html>
