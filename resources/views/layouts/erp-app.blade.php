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

    // ERP secondary nav. Built pages carry a 'route'; not-yet-built pages carry
    // 'soon' => true and render disabled with a "Soon" badge. Smart Notes reuses
    // the EXISTING agency notes module (no duplicate ERP notes screen).
    $erpNav = [
        ['route' => 'erp.dashboard',   'active' => request()->routeIs('erp.dashboard'),   'icon' => 'bi-speedometer2',      'label' => 'ERP Dashboard'],
        ['route' => 'erp.mofa',        'active' => request()->routeIs('erp.mofa*'),        'icon' => 'bi-file-earmark-text', 'label' => 'MOFA Entry'],
        ['route' => 'erp.double-mofa', 'active' => request()->routeIs('erp.double-mofa*'), 'icon' => 'bi-files',             'label' => 'Double MOFA'],
        ['route' => 'erp.stamping',    'active' => request()->routeIs('erp.stamping*'),    'icon' => 'bi-stamp',             'label' => 'Stamping'],
        ['route' => 'erp.manpower',    'active' => request()->routeIs('erp.manpower*'),    'icon' => 'bi-person-check',      'label' => 'Manpower Complete'],
        ['route' => 'erp.delivery',    'active' => request()->routeIs('erp.delivery*'),    'icon' => 'bi-truck',             'label' => 'Delivery'],
        ['route' => 'erp.agent-khata', 'active' => request()->routeIs('erp.agent-khata*'), 'icon' => 'bi-journal-bookmark',  'label' => 'Agent Khata'],
        ['route' => 'erp.expenses',    'active' => request()->routeIs('erp.expenses*'),    'icon' => 'bi-cash-coin',         'label' => 'Expenses'],
        ['route' => 'erp.due-list',    'active' => request()->routeIs('erp.due-list*'),    'icon' => 'bi-hourglass-split',   'label' => 'Due List'],
        $isAdmin
            ? ['route' => 'erp.profit-loss', 'active' => request()->routeIs('erp.profit-loss*'), 'icon' => 'bi-graph-up-arrow', 'label' => 'Profit / Loss']
            : ['route' => null, 'soon' => true, 'icon' => 'bi-graph-up-arrow', 'label' => 'Profit / Loss'],
        ['route' => 'erp.reports',     'active' => request()->routeIs('erp.reports*'),     'icon' => 'bi-bar-chart',         'label' => 'Reports'],
        ['route' => 'erp.settings',    'active' => request()->routeIs('erp.settings*'),    'icon' => 'bi-sliders',           'label' => 'ERP Settings'],
        ['route' => 'notes.index',     'active' => false,                                  'icon' => 'bi-journal-text',      'label' => 'Smart Notes'],
    ];

    $erpItem = 'group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
    $erpOn   = 'bg-emerald-50 text-emerald-700';
    $erpOff  = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
@endphp

    {{-- Persistent top app-header (SAME component as the agency layout) --}}
    <x-app-header />

    <div x-data="{ erpNav: false }" class="min-h-full">

        {{-- Mobile: toggle for the ERP secondary sidebar (sits under the sticky header) --}}
        <div class="sticky top-16 z-30 flex items-center gap-2 border-b border-slate-200 bg-white px-4 py-2 lg:hidden">
            <button @click="erpNav = true" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">
                <i class="bi bi-grid"></i> ERP Sections
            </button>
        </div>

        {{-- Mobile overlay --}}
        <div x-show="erpNav" x-cloak @click="erpNav = false" x-transition.opacity
             class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

        {{-- ERP secondary sidebar — BELOW the persistent header (top-16), light theme --}}
        <aside :class="erpNav ? 'translate-x-0' : '-translate-x-full'"
               class="fixed left-0 top-16 z-40 flex h-[calc(100vh-4rem)] w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0">
            <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3.5">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-600 text-white">
                    <i class="bi bi-cash-stack text-sm"></i>
                </span>
                <div class="truncate text-sm font-bold text-slate-900">ERP Suite</div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-3">
                @foreach($erpNav as $link)
                    @if(($link['soon'] ?? false) || empty($link['route']))
                        <span @class([$erpItem, 'cursor-not-allowed text-slate-400']) aria-disabled="true">
                            <i class="bi {{ $link['icon'] }} w-5 text-center text-base text-slate-300"></i>
                            <span class="flex-1">{{ $link['label'] }}</span>
                            <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[0.55rem] font-bold uppercase tracking-wide text-slate-400">Soon</span>
                        </span>
                    @else
                        <a href="{{ route($link['route']) }}" @click="erpNav = false"
                           @class([$erpItem, $erpOn => ($link['active'] ?? false), $erpOff => ! ($link['active'] ?? false)])>
                            <i class="bi {{ $link['icon'] }} w-5 text-center text-base {{ ($link['active'] ?? false) ? 'text-emerald-600' : 'text-slate-400' }}"></i>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        {{-- Content column --}}
        <div class="lg:pl-64">
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
