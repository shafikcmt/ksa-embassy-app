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
            --sidebar-width: 256px;
            --sidebar-bg: #101a33;
            --sidebar-bg2: #0a1124;
            --sidebar-hover: rgba(255,255,255,.06);
            --sidebar-active: #2563eb;
            --topbar-height: 62px;
            --brand-blue: #2563eb;
            --brand-deep: #1e40af;
            --navy: #0f172a;
            --accent: #0ea5e9;
            --app-bg: #f5f7fb;
            --line: #e7ebf1;
            --shadow-sm: 0 1px 2px rgba(15,23,42,.04), 0 1px 3px rgba(15,23,42,.06);
            --shadow-md: 0 4px 16px -6px rgba(15,23,42,.12);
            --shadow-lg: 0 18px 40px -20px rgba(15,23,42,.28);
        }
        * { scrollbar-width: thin; scrollbar-color: #334155 transparent; }
        body { background: var(--app-bg); font-size: 0.875rem; color: #1e293b;
            -webkit-font-smoothing: antialiased; }

        /* ── Sidebar ───────────────────────────────────────────── */
        #sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(195deg, var(--sidebar-bg) 0%, var(--sidebar-bg2) 100%);
            z-index: 1045; overflow-y: auto; display: flex; flex-direction: column;
            transition: transform .25s ease; border-right: 1px solid rgba(255,255,255,.04);
        }
        #sidebar .brand {
            padding: 1.1rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.07);
            display: flex; align-items: center; gap: .7rem;
        }
        #sidebar .brand .brand-icon {
            width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            display: grid; place-items: center; color: #fff; font-size: 1.15rem;
            box-shadow: 0 6px 16px -6px rgba(37,99,235,.7);
        }
        #sidebar .brand .agency-name {
            color: #fff; font-weight: 700; font-size: .88rem; line-height: 1.25;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        #sidebar .brand .sub-label { color: #7d8aa3; font-size: .66rem; letter-spacing: .02em; }
        #sidebar .nav-section { flex: 1; overflow-y: auto; padding: .65rem 0 1rem; }
        #sidebar .nav-label {
            padding: 1.05rem 1.4rem .4rem; color: #5b6884;
            font-size: .64rem; text-transform: uppercase; letter-spacing: .13em; font-weight: 700;
        }
        #sidebar .nav-link {
            color: #9aa6bd; padding: .58rem .85rem; margin: 2px .7rem;
            display: flex; align-items: center; gap: .75rem; border-radius: 10px;
            transition: background .15s, color .15s; font-size: .83rem; font-weight: 500; position: relative;
        }
        #sidebar .nav-link:hover { background: var(--sidebar-hover); color: #f1f5f9; }
        #sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--sidebar-active), #1d4ed8);
            color: #fff; font-weight: 600; box-shadow: 0 8px 18px -8px rgba(37,99,235,.8);
        }
        #sidebar .nav-link.active::before {
            content: ''; position: absolute; left: -.7rem; top: 22%; bottom: 22%;
            width: 3px; border-radius: 3px; background: #7dd3fc;
        }
        #sidebar .nav-link i { font-size: 1.02rem; width: 1.25rem; flex-shrink: 0; text-align: center; }
        #sidebar .nav-link .nav-count {
            margin-left: auto; font-size: .66rem; background: rgba(255,255,255,.08); color: #cbd5e1;
            padding: .05rem .45rem; border-radius: 999px; min-width: 1.3rem; text-align: center;
        }
        #sidebar .nav-link.active .nav-count { background: rgba(255,255,255,.25); color: #fff; }
        /* Sidebar plan card */
        #sidebar .sidebar-footer { padding: .9rem 1rem 1.1rem; border-top: 1px solid rgba(255,255,255,.07); }
        .plan-card {
            background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.07);
            border-radius: 12px; padding: .8rem .9rem;
        }
        .plan-card .plan-name { color: #fff; font-weight: 600; font-size: .8rem; display: flex; align-items: center; gap: .4rem; }
        .plan-card .plan-name i { color: #7dd3fc; }
        .plan-card .plan-meta { color: #94a3b8; font-size: .68rem; }
        .plan-bar { height: 5px; background: rgba(255,255,255,.1); border-radius: 4px; overflow: hidden; margin-top: .5rem; }
        .plan-bar > div { height: 100%; border-radius: 4px; transition: width .4s ease; }

        /* ── Topbar ───────────────────────────────────────────── */
        #topbar {
            position: fixed; top: 0; left: var(--sidebar-width); right: 0;
            height: var(--topbar-height); background: rgba(255,255,255,.85);
            backdrop-filter: saturate(180%) blur(10px); -webkit-backdrop-filter: saturate(180%) blur(10px);
            border-bottom: 1px solid var(--line); z-index: 1030;
            display: flex; align-items: center; padding: 0 1.4rem; gap: 1rem;
            justify-content: space-between;
        }
        #topbar .tb-left { display: flex; align-items: center; gap: .9rem; min-width: 0; }
        #topbar .page-title { font-weight: 700; font-size: 1rem; color: var(--navy); white-space: nowrap; letter-spacing: -.01em; }
        #topbar .tb-search {
            display: flex; align-items: center; gap: .5rem; background: #f1f4f9;
            border: 1px solid transparent; border-radius: 10px; padding: .4rem .8rem; width: 250px;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        #topbar .tb-search:focus-within { background: #fff; border-color: #93b4f3; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        #topbar .tb-search input { border: 0; background: transparent; outline: none; font-size: .8rem; width: 100%; }
        #topbar .tb-right { display: flex; align-items: center; gap: .85rem; }
        .tb-bell { position: relative; width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center;
            color: #475569; background: #f1f4f9; transition: background .15s, color .15s; }
        .tb-bell:hover { background: #e6ebf3; color: var(--navy); }
        .tb-divider { width: 1px; height: 26px; background: var(--line); }
        .user-chip { display: flex; align-items: center; gap: .6rem; padding: .25rem .35rem; border-radius: 10px; }
        .user-chip .avatar {
            width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff;
            display: grid; place-items: center; font-weight: 700; font-size: .82rem;
            box-shadow: 0 5px 12px -5px rgba(37,99,235,.6);
        }
        .user-chip .u-name { font-weight: 600; font-size: .82rem; line-height: 1.15; color: var(--navy); }
        .user-chip .u-role { font-size: .68rem; color: #64748b; }
        .role-badge { font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
            padding: .12rem .45rem; border-radius: 6px; background: #dbeafe; color: #1e40af; }
        .role-badge.staff { background: #ede9fe; color: #5b21b6; }
        .hamburger { display: none; border: 0; background: #f1f4f9; border-radius: 10px;
            width: 38px; height: 38px; align-items: center; justify-content: center; cursor: pointer; color: #334155; font-size: 1.15rem; }

        /* ── Layout ───────────────────────────────────────────── */
        #main-content {
            margin-left: var(--sidebar-width); margin-top: var(--topbar-height);
            padding: 1.5rem; min-height: calc(100vh - var(--topbar-height));
        }
        #sidebarOverlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 1040; }
        #navToggle { position: absolute; opacity: 0; pointer-events: none; }

        /* ── Cards / tables shared ───────────────────────────── */
        .card { border: 1px solid var(--line); box-shadow: var(--shadow-sm); border-radius: 16px; background: #fff; }
        .card-header { background: #fff; border-bottom: 1px solid var(--line); font-weight: 600; color: var(--navy);
            border-top-left-radius: 16px; border-top-right-radius: 16px; padding: .85rem 1.1rem; }
        .table > thead > tr > th { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #7382a0; background: #f8fafc; font-weight: 600; border-bottom: 1px solid var(--line); }
        .table > tbody > tr > td { border-color: #eef2f7; }
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

        /* ── Shared UI kit (reused across dashboard + embassy pages) ───────── */
        /* Page section heading */
        .section-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin: 0 0 .75rem; }
        .section-head .sh-title { font-size: .95rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: .5rem; margin: 0; }
        .section-head .sh-title i { color: #475569; }
        .card-title-sm { font-size: .82rem; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: .45rem; }
        .card-title-sm i { color: #64748b; }

        /* Hero / agency overview */
        .hero { position: relative; border-radius: 18px; overflow: hidden; color: #fff;
            background: linear-gradient(125deg, #0b1430 0%, #1e3a8a 55%, #2563eb 110%);
            padding: 1.3rem 1.5rem; box-shadow: var(--shadow-md); }
        .hero::after { content: ''; position: absolute; top: -40%; right: -8%; width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(14,165,233,.45) 0%, rgba(14,165,233,0) 70%); pointer-events: none; }
        .hero > * { position: relative; z-index: 1; }
        .hero .hero-name { font-size: 1.25rem; font-weight: 700; letter-spacing: -.01em; display: flex; align-items: center; gap: .55rem; }
        .hero .hero-name .h-logo { width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,.14);
            display: grid; place-items: center; font-size: 1.1rem; flex-shrink: 0; }
        .hero .meta-chip { font-size: .72rem; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.1);
            padding: .25rem .6rem; border-radius: 999px; color: #eaf1ff; display: inline-flex; align-items: center; gap: .35rem; }
        .hero .btn-hero { background: #fff; color: #1e3a8a; font-weight: 600; border: 0; border-radius: 10px;
            padding: .5rem .9rem; font-size: .8rem; transition: transform .15s, box-shadow .15s; display: inline-flex; align-items: center; gap: .4rem; }
        .hero .btn-hero:hover { transform: translateY(-1px); box-shadow: 0 10px 22px -10px rgba(0,0,0,.5); }
        .hero .btn-hero-ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.22); }
        .hero .btn-hero-ghost:hover { background: rgba(255,255,255,.2); }

        /* Stat card */
        .stat-card { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px;
            padding: 1rem 1.05rem; height: 100%; box-shadow: var(--shadow-sm); display: block; overflow: hidden; }
        .stat-card .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: .65rem; }
        .stat-ic { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; font-size: 1.15rem; flex-shrink: 0; }
        .stat-value { font-size: 1.7rem; font-weight: 800; line-height: 1; color: var(--navy); letter-spacing: -.02em; }
        .stat-label { font-size: .76rem; color: #64748b; font-weight: 600; margin-top: .4rem; }
        .stat-sub { font-size: .72rem; margin-top: .2rem; font-weight: 500; }
        .stat-link { text-decoration: none; transition: border-color .15s, box-shadow .15s, transform .15s; }
        .stat-link:hover { border-color: #cdd8e8; box-shadow: var(--shadow-lg); transform: translateY(-3px); }
        .stat-go { color: #cbd5e1; font-size: .8rem; }
        .stat-link:hover .stat-go { color: var(--brand-blue); }

        /* Quick-action tile (calm, uniform) */
        .qa { display: flex; align-items: center; gap: .8rem; padding: .8rem .9rem; border-radius: 13px; text-decoration: none;
            border: 1px solid var(--line); background: #fff; transition: border-color .15s, background .15s, transform .15s, box-shadow .15s; height: 100%; }
        .qa:hover { border-color: #cdd8e8; background: #fafbfe; transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .qa .qa-ic { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; flex-shrink: 0; font-size: 1.05rem;
            background: linear-gradient(135deg, #e8effd, #e0f2fe); color: #1d4ed8; }
        .qa:hover .qa-ic { background: linear-gradient(135deg, #2563eb, #0ea5e9); color: #fff; }
        .qa .qa-title { font-weight: 600; font-size: .84rem; color: var(--navy); }
        .qa .qa-sub { font-size: .72rem; color: #94a3b8; }

        /* Soft count badge */
        .soft-badge { font-size: .72rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px; background: #eef2f7; color: #475569; }
        .soft-badge.blue { background: #e8effd; color: #1d4ed8; }
        .soft-badge.green { background: #e7f8f0; color: #047857; }
        .soft-badge.red { background: #fdeced; color: #be123c; }
        .soft-badge.amber { background: #fdf4e3; color: #b45309; }

        /* Section accent bar (category headers) */
        .accent-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .6rem .9rem; }
        .accent-head .ah-title { font-weight: 600; font-size: .85rem; color: #0f172a; display: flex; align-items: center; gap: .55rem; }
        .accent-dot { width: 9px; height: 9px; border-radius: 3px; flex-shrink: 0; }

        /* Readable tables */
        .table-clean > tbody > tr > td { padding-top: .55rem; padding-bottom: .55rem; vertical-align: middle; border-color: #eef2f7; }
        .table-clean > thead > tr > th { border-color: #e2e8f0; }
        .table-clean > tbody > tr:hover { background: #f8fafc; }

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
        <span class="tb-bell" title="{{ $noticeCount }} active notice(s)">
            <i class="bi bi-bell" style="font-size:1.05rem;"></i>
            @if($noticeCount > 0)
            <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="top:7px;left:calc(100% - 7px);font-size:.55rem;">{{ $noticeCount }}</span>
            @endif
        </span>

        <span class="tb-divider"></span>

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
