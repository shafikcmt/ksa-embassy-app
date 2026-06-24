<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ auth()->user()->agency->name ?? 'Agency' }}</title>

    {{-- Icon font only (framework-agnostic), used across the app --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased">

@php
    $authUser   = auth()->user();
    $isAdmin    = method_exists($authUser, 'isAgencyAdmin') ? $authUser->isAgencyAdmin() : true;
    $sidebarSub = $authUser->agency?->activeSubscription;
    $initials   = collect(explode(' ', trim($authUser->name)))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');

    $navGroups = [
        'Overview' => [
            ['route' => 'dashboard', 'active' => request()->routeIs('dashboard'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        ],
        'Operations' => [
            ['route' => 'agents.index', 'active' => request()->routeIs('agents.*'), 'icon' => 'bi-people', 'label' => 'Agents'],
            ['route' => 'hr.index', 'active' => request()->routeIs('hr.index') || request()->routeIs('hr.create') || request()->routeIs('hr.edit') || request()->routeIs('hr.show'), 'icon' => 'bi-person-vcard', 'label' => 'HR / Candidates'],
            ['route' => 'embassy-lists.index', 'active' => request()->routeIs('embassy-lists.*'), 'icon' => 'bi-list-ol', 'label' => 'Embassy Lists'],
        ],
        'Documents' => [
            ['route' => 'hr.index', 'active' => request()->routeIs('hr.documents') || request()->routeIs('hr.print.*') || request()->routeIs('hr.download.*'), 'icon' => 'bi-file-earmark-pdf', 'label' => 'Print / PDF'],
        ],
    ];
@endphp

<div x-data="{ sidebar: false }" class="min-h-full">

    {{-- ── Mobile overlay ─────────────────────────────────────── --}}
    <div x-show="sidebar" x-cloak @click="sidebar = false"
         x-transition.opacity
         class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

    {{-- ── Sidebar ────────────────────────────────────────────── --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-gradient-to-b from-navy-700 to-navy-900 transition-transform duration-200 lg:translate-x-0">
        {{-- Brand --}}
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 via-indigo-500 to-violet-600 text-lg text-white shadow-lg shadow-indigo-600/30">
                <i class="bi bi-buildings"></i>
            </span>
            <div class="min-w-0">
                <div class="truncate text-sm font-bold text-white">{{ $authUser->agency->name ?? 'Agency' }}</div>
                <div class="text-[0.65rem] tracking-wide text-slate-400">VisaDeskPro</div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-3">
            @foreach($navGroups as $group => $links)
                <div class="px-3 pb-1.5 pt-4 text-[0.62rem] font-bold uppercase tracking-[0.13em] text-slate-500">{{ $group }}</div>
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}"
                       @click="sidebar = false"
                       @class([
                           'group mx-0.5 my-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                           'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-indigo-600/30' => $link['active'],
                           'text-slate-300 hover:bg-white/5 hover:text-white' => ! $link['active'],
                       ])>
                        <i class="bi {{ $link['icon'] }} w-5 text-center text-base"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            @endforeach

            <div class="px-3 pb-1.5 pt-4 text-[0.62rem] font-bold uppercase tracking-[0.13em] text-slate-500">Account</div>
            @if($isAdmin)
                <a href="{{ route('settings.index') }}"
                   @click="sidebar = false"
                   @class([
                       'group mx-0.5 my-0.5 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                       'bg-gradient-to-r from-brand-600 to-indigo-600 text-white shadow-lg shadow-indigo-600/30' => request()->routeIs('settings.*'),
                       'text-slate-300 hover:bg-white/5 hover:text-white' => ! request()->routeIs('settings.*'),
                   ])>
                    <i class="bi bi-gear w-5 text-center text-base"></i>
                    <span>Settings</span>
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="group mx-0.5 my-0.5 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                    <i class="bi bi-box-arrow-right w-5 text-center text-base"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>

        {{-- User profile --}}
        <div class="border-t border-white/10 px-3.5 pt-3.5">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-brand-500 to-indigo-600 text-sm font-bold text-white">{{ strtoupper($initials ?: 'U') }}</span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-white">{{ $authUser->name }}</div>
                    <div class="truncate text-[0.68rem] text-slate-400">{{ $isAdmin ? 'Agency Admin' : 'Agency Staff' }}</div>
                </div>
            </div>
        </div>

        {{-- Plan card --}}
        <div class="border-t-0 p-3.5 pt-2.5">
            @if($sidebarSub)
                @php
                    $daysLeft  = $sidebarSub->daysRemaining();
                    $lifeTotal = optional($sidebarSub->start_date)->diffInDays($sidebarSub->end_date) ?: 30;
                    $lifePct   = max(4, min(100, round(($daysLeft / max(1, $lifeTotal)) * 100)));
                    $barColor  = $daysLeft <= 3 ? 'bg-rose-500' : ($daysLeft <= 7 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-white">
                            <i class="bi bi-gem text-cyan-300"></i>{{ $sidebarSub->plan->name ?? 'Plan' }}
                        </span>
                        <span class="rounded-full bg-white/10 px-2 py-0.5 text-[0.6rem] font-semibold uppercase text-slate-200">{{ ucfirst($sidebarSub->status) }}</span>
                    </div>
                    <div class="mt-1 text-[0.68rem] text-slate-400">
                        <i class="bi bi-clock"></i> {{ $daysLeft }} days left
                        @if($sidebarSub->end_date) · {{ $sidebarSub->end_date->format('d M Y') }} @endif
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $lifePct }}%"></div>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-rose-500/20 bg-rose-500/10 p-3 text-xs text-rose-200">
                    <i class="bi bi-exclamation-triangle"></i> No active subscription
                </div>
            @endif
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
                <h1 class="truncate text-base font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
                <span class="hidden items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-500 xl:inline-flex">
                    <i class="bi bi-calendar3 text-slate-400"></i>{{ now()->format('D, d M Y') }}
                </span>
                <form method="GET" action="{{ route('hr.index') }}" class="hidden items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 ring-1 ring-transparent transition focus-within:bg-white focus-within:ring-brand-300 md:flex">
                    <i class="bi bi-search text-sm text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search candidates…"
                           class="w-44 border-0 bg-transparent p-0 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0 lg:w-56">
                </form>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                {{-- Quick action --}}
                @can('create', \App\Models\HrProfile::class)
                    <a href="{{ route('hr.create') }}"
                       class="hidden items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:shadow-md sm:inline-flex">
                        <i class="bi bi-plus-lg"></i><span class="hidden lg:inline">Add HR</span>
                    </a>
                @endcan

                {{-- Notification bell --}}
                <div x-data="{
                        open: false,
                        count: {{ $notificationCount ?? 0 }},
                        sig: @js($notificationSignature ?? ''),
                        readSig: localStorage.getItem('notif_read_sig') || '',
                        get unread() { return this.readSig === this.sig ? 0 : this.count },
                        markAllRead() { this.readSig = this.sig; localStorage.setItem('notif_read_sig', this.sig); }
                     }" class="relative">
                    <button @click="open = !open" class="relative grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200">
                        <i class="bi bi-bell text-lg"></i>
                        <span x-show="unread > 0" x-cloak
                              class="absolute -right-0.5 -top-0.5 grid h-4 min-w-[1rem] place-items-center rounded-full bg-rose-500 px-1 text-[0.6rem] font-bold text-white"
                              x-text="unread > 9 ? '9+' : unread"></span>
                    </button>

                    <div x-show="open" x-cloak @click.outside="open = false" @keydown.escape.window="open = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 z-50 mt-2 w-80 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg sm:w-96">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-900">Notifications</span>
                            <button @click="markAllRead()" x-show="unread > 0" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Mark all read</button>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @php
                                $notifTone = [
                                    'danger'  => ['bg-rose-50 text-rose-600', 'bi-exclamation-octagon'],
                                    'warning' => ['bg-amber-50 text-amber-600', 'bi-exclamation-triangle'],
                                    'info'    => ['bg-brand-50 text-brand-600', 'bi-info-circle'],
                                    'success' => ['bg-emerald-50 text-emerald-600', 'bi-check-circle'],
                                ];
                            @endphp
                            @forelse(($notifications ?? collect()) as $n)
                                @php [$toneCls, $fallbackIcon] = $notifTone[$n['type']] ?? $notifTone['info']; @endphp
                                <a @if($n['action']) href="{{ $n['action'] }}" @endif
                                   class="flex items-start gap-3 px-4 py-3 transition hover:bg-slate-50 @if(!$n['action']) cursor-default @endif">
                                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $toneCls }}">
                                        <i class="bi {{ $n['icon'] ?? $fallbackIcon }}"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-slate-800">{{ $n['title'] ?? 'Notification' }}</div>
                                        <div class="mt-0.5 text-xs leading-snug text-slate-500">{!! $n['message'] !!}</div>
                                        <div class="mt-1 flex items-center gap-2">
                                            @if(!empty($n['time']))<span class="text-[0.68rem] text-slate-400">{{ $n['time'] }}</span>@endif
                                            @if(!empty($n['action_label']))<span class="text-[0.68rem] font-semibold text-brand-600">{{ $n['action_label'] }} →</span>@endif
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="px-4 py-10 text-center">
                                    <i class="bi bi-check2-circle mb-2 block text-2xl text-emerald-400"></i>
                                    <p class="text-sm text-slate-500">You're all caught up</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <span class="hidden h-6 w-px bg-slate-200 sm:block"></span>

                {{-- User menu --}}
                <x-ui.dropdown align="right" width="w-60">
                    <x-slot:trigger>
                        <button class="flex items-center gap-2.5 rounded-lg p-1 pr-1.5 transition hover:bg-slate-100">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-brand-600 to-indigo-600 text-sm font-bold text-white">{{ strtoupper($initials ?: 'U') }}</span>
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
                    @if($isAdmin)
                        <x-ui.dropdown-item :href="route('settings.index')" icon="bi-gear">Settings</x-ui.dropdown-item>
                    @endif
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
