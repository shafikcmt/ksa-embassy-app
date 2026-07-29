<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — VisaDeskPro</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        @media (prefers-reduced-motion: reduce){
            *,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased">

@php
    $authUser = auth()->user();
    $initials = collect(explode(' ', trim($authUser->name)))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');

    // Super-admin navigation, grouped by section (mirrors the old Bootstrap sidebar).
    $navGroups = [
        'Main' => [
            ['route' => 'super-admin.dashboard', 'active' => request()->routeIs('super-admin.dashboard'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        ],
        'Agencies' => [
            ['route' => 'super-admin.agencies.index', 'active' => request()->routeIs('super-admin.agencies.*'), 'icon' => 'bi-buildings', 'label' => 'Agencies'],
        ],
        'Data' => [
            ['route' => 'super-admin.agents.index', 'active' => request()->routeIs('super-admin.agents.*'), 'icon' => 'bi-people', 'label' => 'Agents'],
            ['route' => 'super-admin.hr.index', 'active' => request()->routeIs('super-admin.hr.*'), 'icon' => 'bi-person-vcard', 'label' => 'HR Profiles'],
            ['route' => 'super-admin.embassy-lists.index', 'active' => request()->routeIs('super-admin.embassy-lists.*'), 'icon' => 'bi-list-ol', 'label' => 'Embassy Lists'],
        ],
        'Subscriptions' => [
            ['route' => 'super-admin.plans.index', 'active' => request()->routeIs('super-admin.plans.*'), 'icon' => 'bi-grid-3x3-gap', 'label' => 'Plans'],
            ['route' => 'super-admin.subscriptions.index', 'active' => request()->routeIs('super-admin.subscriptions.*'), 'icon' => 'bi-credit-card', 'label' => 'Subscriptions'],
        ],
        'System' => [
            ['route' => 'super-admin.settings.index', 'active' => request()->routeIs('super-admin.settings.*'), 'icon' => 'bi-gear', 'label' => 'Settings'],
        ],
    ];

    $navItem = 'group relative mx-0.5 my-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors';
    $navOn   = 'bg-white/10 text-white';
    $navOff  = 'text-slate-300 hover:bg-white/5 hover:text-white';
@endphp

<div x-data="{ sidebar: false }" class="min-h-full">

    {{-- ── Mobile overlay ─────────────────────────────────────── --}}
    <div x-show="sidebar" x-cloak @click="sidebar = false" x-transition.opacity
         class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

    {{-- ── Sidebar ────────────────────────────────────────────── --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-gradient-to-b from-navy-700 to-navy-900 transition-transform duration-200 lg:translate-x-0">
        {{-- Brand --}}
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 via-indigo-500 to-violet-600 text-lg text-white shadow-lg shadow-indigo-600/30">
                <i class="bi bi-passport"></i>
            </span>
            <div class="min-w-0">
                <div class="truncate text-sm font-bold text-white">VisaDesk<span class="text-brand-300">Pro</span></div>
                <div class="text-[0.65rem] tracking-wide text-slate-400">Super Admin Panel</div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            @foreach($navGroups as $label => $links)
                <div class="px-3 pb-1.5 pt-2 text-[0.62rem] font-bold uppercase tracking-[0.13em] text-slate-500">{{ $label }}</div>
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}" @click="sidebar = false"
                       @class([$navItem, $navOn => $link['active'], $navOff => ! $link['active']])>
                        @if($link['active'])<span class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-brand-400"></span>@endif
                        <i class="bi {{ $link['icon'] }} w-5 text-center text-base {{ $link['active'] ? 'text-brand-300' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            @endforeach

            <div class="my-3 border-t border-white/10"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="group mx-0.5 my-0.5 flex w-full cursor-pointer items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-300 transition-colors hover:bg-white/5 hover:text-white">
                    <i class="bi bi-box-arrow-right w-5 text-center text-base text-slate-400 group-hover:text-slate-200"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>

        {{-- User footer --}}
        <div class="border-t border-white/10 px-3.5 py-3.5">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-rose-500 to-rose-700 text-sm font-bold text-white">{{ strtoupper($initials ?: 'S') }}</span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-white">{{ $authUser->name }}</div>
                    <div class="truncate text-[0.68rem] text-rose-300">Super Admin</div>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── Content column ─────────────────────────────────────── --}}
    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur-md sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button @click="sidebar = true" class="grid h-9 w-9 cursor-pointer place-items-center rounded-lg bg-slate-100 text-slate-600 transition-colors hover:bg-slate-200 lg:hidden">
                    <i class="bi bi-list text-xl"></i>
                </button>
                <h1 class="truncate text-base font-bold text-slate-900">@yield('page-title', 'Super Admin')</h1>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-wide text-rose-600 ring-1 ring-inset ring-rose-200">Super Admin</span>
                <span class="hidden h-6 w-px bg-slate-200 sm:block"></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="grid h-9 w-9 cursor-pointer place-items-center rounded-lg bg-slate-100 text-slate-600 transition-colors hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                        <i class="bi bi-box-arrow-right text-lg"></i>
                    </button>
                </form>
            </div>
        </header>

        {{-- Flash + page --}}
        <main class="p-4 sm:p-6">
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" class="mb-4 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <i class="bi bi-check-circle-fill mt-0.5 text-emerald-500"></i>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button @click="show = false" class="cursor-pointer text-emerald-500 transition-colors hover:text-emerald-700"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <i class="bi bi-exclamation-circle-fill mt-0.5 text-rose-500"></i>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button @click="show = false" class="cursor-pointer text-rose-500 transition-colors hover:text-rose-700"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <i class="bi bi-exclamation-circle-fill mr-1.5 text-rose-500"></i>{{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
