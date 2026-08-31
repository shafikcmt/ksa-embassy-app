<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ERP') — {{ auth()->user()->agency->name ?? 'Agency' }}</title>

    {{-- Icon font only (framework-agnostic), used across the app --}}
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
    $isAdmin  = method_exists($authUser, 'isAgencyAdmin') ? $authUser->isAgencyAdmin() : true;
    $initials = collect(explode(' ', trim($authUser->name)))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');

    // ERP left sidebar. Built pages carry a 'route'; not-yet-built pages carry
    // 'soon' => true and render disabled with a "Soon" badge, so the full 13-page
    // map is visible while staying honest about what works today. Smart Notes
    // reuses the EXISTING agency notes module (no duplicate ERP notes screen).
    $erpNav = [
        ['route' => 'erp.dashboard', 'active' => request()->routeIs('erp.dashboard'), 'icon' => 'bi-speedometer2',    'label' => 'ERP Dashboard'],
        ['route' => 'erp.mofa',        'active' => request()->routeIs('erp.mofa*'),        'icon' => 'bi-file-earmark-text', 'label' => 'MOFA Entry'],
        ['route' => 'erp.double-mofa', 'active' => request()->routeIs('erp.double-mofa*'), 'icon' => 'bi-files',             'label' => 'Double MOFA'],
        ['route' => 'erp.stamping',    'active' => request()->routeIs('erp.stamping*'),    'icon' => 'bi-stamp',             'label' => 'Stamping'],
        ['route' => 'erp.manpower',    'active' => request()->routeIs('erp.manpower*'),    'icon' => 'bi-person-check',      'label' => 'Manpower Complete'],
        ['route' => 'erp.delivery',    'active' => request()->routeIs('erp.delivery*'),    'icon' => 'bi-truck',            'label' => 'Delivery'],
        ['route' => null,            'soon' => true,                                  'icon' => 'bi-journal-bookmark',  'label' => 'Agent Khata'],
        ['route' => 'erp.expenses',  'active' => request()->routeIs('erp.expenses*'), 'icon' => 'bi-cash-coin',         'label' => 'Expenses'],
        ['route' => 'erp.due-list',  'active' => request()->routeIs('erp.due-list*'), 'icon' => 'bi-hourglass-split',   'label' => 'Due List'],
        ['route' => null,            'soon' => true,                                  'icon' => 'bi-graph-up-arrow',    'label' => 'Profit / Loss'],
        ['route' => null,            'soon' => true,                                  'icon' => 'bi-bar-chart',         'label' => 'Reports'],
        ['route' => 'erp.settings',  'active' => request()->routeIs('erp.settings*'), 'icon' => 'bi-sliders',           'label' => 'ERP Settings'],
        ['route' => 'notes.index',   'active' => false,                               'icon' => 'bi-journal-text',      'label' => 'Smart Notes'],
    ];

    $navItem = 'group relative mx-0.5 my-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition';
    $navOn   = 'bg-white/10 text-white';
    $navOff  = 'text-slate-300 hover:bg-white/5 hover:text-white';
    $navSoon = 'cursor-not-allowed text-slate-500';
@endphp

<div x-data="{ sidebar: false }" class="min-h-full">

    {{-- ── Mobile overlay ─────────────────────────────────────── --}}
    <div x-show="sidebar" x-cloak @click="sidebar = false"
         x-transition.opacity
         class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

    {{-- ── ERP Sidebar ────────────────────────────────────────── --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-gradient-to-b from-navy-700 to-navy-900 transition-transform duration-200 lg:translate-x-0">
        {{-- Brand --}}
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 text-lg text-white shadow-lg shadow-emerald-600/30">
                <i class="bi bi-cash-stack"></i>
            </span>
            <div class="min-w-0">
                <div class="truncate text-sm font-bold text-white">ERP Suite</div>
                <div class="truncate text-[0.65rem] tracking-wide text-slate-400">{{ $authUser->agency->name ?? 'Agency' }}</div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <div class="px-3 pb-1.5 text-[0.62rem] font-bold uppercase tracking-[0.13em] text-slate-500">ERP Menu</div>
            @foreach($erpNav as $link)
                @if(($link['soon'] ?? false) || empty($link['route']))
                    <span @class([$navItem, $navSoon]) aria-disabled="true">
                        <i class="bi {{ $link['icon'] }} w-5 text-center text-base text-slate-600"></i>
                        <span class="flex-1">{{ $link['label'] }}</span>
                        <span class="rounded-full bg-white/5 px-1.5 py-0.5 text-[0.55rem] font-bold uppercase tracking-wide text-slate-400">Soon</span>
                    </span>
                @else
                    <a href="{{ route($link['route']) }}" @click="sidebar = false"
                       @class([$navItem, $navOn => ($link['active'] ?? false), $navOff => ! ($link['active'] ?? false)])>
                        @if($link['active'] ?? false)<span class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-emerald-400"></span>@endif
                        <i class="bi {{ $link['icon'] }} w-5 text-center text-base {{ ($link['active'] ?? false) ? 'text-emerald-300' : 'text-slate-400 group-hover:text-slate-200' }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endif
            @endforeach

            <div class="my-3 border-t border-white/10"></div>

            {{-- Back to the main agency app --}}
            <a href="{{ route('dashboard') }}" @click="sidebar = false" @class([$navItem, $navOff])>
                <i class="bi bi-arrow-left-circle w-5 text-center text-base text-slate-400 group-hover:text-slate-200"></i>
                <span>&larr; Back to main app</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="group mx-0.5 my-0.5 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                    <i class="bi bi-box-arrow-right w-5 text-center text-base text-slate-400 group-hover:text-slate-200"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>

        {{-- User profile --}}
        <div class="border-t border-white/10 px-3.5 py-3.5">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-sm font-bold text-white">{{ strtoupper($initials ?: 'U') }}</span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-white">{{ $authUser->name }}</div>
                    <div class="truncate text-[0.68rem] text-slate-400">{{ $isAdmin ? 'Agency Admin' : 'Agency Staff' }}</div>
                </div>
            </div>
        </div>
    </aside>

    {{-- ── Content column ─────────────────────────────────────── --}}
    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur-md sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button @click="sidebar = true" class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 lg:hidden">
                    <i class="bi bi-list text-xl"></i>
                </button>
                <h1 class="truncate text-base font-bold text-slate-900">@yield('page-title', 'ERP')</h1>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    <i class="bi bi-arrow-left"></i><span class="hidden sm:inline">Back to main app</span>
                </a>

                <span class="hidden h-6 w-px bg-slate-200 sm:block"></span>

                {{-- User menu --}}
                <x-ui.dropdown align="right" width="w-60">
                    <x-slot:trigger>
                        <button class="flex items-center gap-2.5 rounded-lg p-1 pr-1.5 transition hover:bg-slate-100">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-emerald-600 to-teal-600 text-sm font-bold text-white">{{ strtoupper($initials ?: 'U') }}</span>
                            <span class="hidden text-left sm:block">
                                <span class="block text-sm font-semibold leading-tight text-slate-900">{{ $authUser->name }}</span>
                                <span class="block text-[0.68rem] {{ $isAdmin ? 'text-brand-600' : 'text-violet-600' }}">{{ $isAdmin ? 'Agency Admin' : 'Agency Staff' }}</span>
                            </span>
                            <i class="bi bi-chevron-down hidden text-xs text-slate-400 sm:block"></i>
                        </button>
                    </x-slot:trigger>

                    <div class="border-b border-slate-100 px-3.5 py-2.5">
                        <div class="text-sm font-semibold text-slate-900">{{ $authUser->name }}</div>
                        <div class="truncate text-xs text-slate-400">{{ $authUser->email }}</div>
                    </div>
                    <x-ui.dropdown-item :href="route('dashboard')" icon="bi-grid-1x2">Main Dashboard</x-ui.dropdown-item>
                    <div class="my-1 border-t border-slate-100"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.dropdown-item type="submit" icon="bi-box-arrow-right" tone="danger">Logout</x-ui.dropdown-item>
                    </form>
                </x-ui.dropdown>
            </div>
        </header>

        {{-- Flash + page --}}
        <main class="p-4 sm:p-6">
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" class="mb-4 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <i class="bi bi-check-circle-fill mt-0.5 text-emerald-500"></i>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-700"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <i class="bi bi-exclamation-circle-fill mt-0.5 text-rose-500"></i>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button @click="show = false" class="text-rose-500 hover:text-rose-700"><i class="bi bi-x-lg"></i></button>
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
