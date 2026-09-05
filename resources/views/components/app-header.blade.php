{{--
    Persistent top app-header — shared by layouts.agency-app AND layouts.erp-app so
    the main nav (Dashboard · HR · Embassy · ERP · Attendance · More) stays visible
    everywhere, including inside the ERP section (which stacks its own sidebar BELOW
    this bar). Page-access gating is unchanged (same PagePermissions the sidebar used).
    The notification data is supplied by NotificationComposer bound to this view.
--}}
@php
    $authUser = auth()->user();
    $isAdmin  = method_exists($authUser, 'isAgencyAdmin') ? $authUser->isAgencyAdmin() : true;
    $initials = collect(explode(' ', trim($authUser->name)))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->implode('');
    $sub      = $authUser->agency?->activeSubscription;

    $gate = fn ($module) => $module === null
        || \App\Support\PagePermissions::userCanAccess($authUser, $module);

    $essentialNav = array_values(array_filter([
        ['route' => 'dashboard',           'module' => null,           'icon' => 'bi-grid-1x2',       'label' => 'Dashboard',       'active' => request()->routeIs('dashboard')],
        ['route' => 'hr.index',            'module' => 'hr',           'icon' => 'bi-person-vcard',   'label' => 'HR / Candidates', 'active' => request()->routeIs('hr.index') || request()->routeIs('hr.create') || request()->routeIs('hr.edit') || request()->routeIs('hr.show')],
        ['route' => 'embassy-lists.index', 'module' => 'embassy_list', 'icon' => 'bi-list-ol',        'label' => 'Embassy Lists',   'active' => request()->routeIs('embassy-lists.*')],
        ['route' => 'erp.dashboard',       'module' => 'erp',          'icon' => 'bi-cash-stack',     'label' => 'ERP',             'active' => request()->routeIs('erp.*')],
        ['route' => 'attendance.index',    'module' => 'attendance',   'icon' => 'bi-calendar-check', 'label' => 'Attendance',      'active' => request()->routeIs('attendance.*')],
    ], fn ($l) => $gate($l['module'])));

    // Secondary tabs — now top-level navbar items (were under the old More ▾).
    // "Documents / Print" was a redundant duplicate of HR (both → hr.index); removed.
    $secondaryNav = array_values(array_filter([
        ['route' => 'agents.index',  'module' => 'agents',  'icon' => 'bi-people',       'label' => 'Agents',      'active' => request()->routeIs('agents.*')],
        ['route' => 'license.index', 'module' => 'license', 'icon' => 'bi-patch-check',  'label' => 'License',     'active' => request()->routeIs('license.*')],
        // Smart Notes intentionally lives in the ERP sidebar (erp-app), not the main nav.
    ], fn ($l) => $gate($l['module'])));

    $qatarUrl = 'https://portal.moi.gov.qa/wps/portal/MOIInternet/services/inquiries/visaservices/enquiryandprinting';

    // Shared pill styling: compact items, soft lavender highlight when active.
    $navBase = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-medium transition-colors';
    $navOn   = 'bg-indigo-50 text-indigo-700';
    $navOff  = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
@endphp

<div x-data="{ mobileNav: false }">
    <header class="fixed inset-x-0 top-0 z-40 border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex h-16 max-w-[1600px] items-center gap-2 px-4 sm:px-6">

            {{-- Mobile hamburger --}}
            <button @click="mobileNav = true"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden">
                <i class="bi bi-list text-xl"></i>
            </button>

            {{-- Brand — app identity only; the agency name lives in the account menu (right) --}}
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 via-indigo-500 to-violet-600 text-white shadow-sm shadow-indigo-600/30">
                    <i class="bi bi-passport-fill"></i>
                </span>
                <span class="hidden text-base font-extrabold tracking-tight text-slate-900 sm:block">VisaDeskPro</span>
            </a>

            {{-- Mobile page title (nav hidden < lg) --}}
            <h1 class="min-w-0 flex-1 truncate text-center text-base font-bold text-slate-900 lg:hidden">@yield('page-title', 'Dashboard')</h1>

            {{-- Desktop primary nav --}}
            <nav class="hidden flex-1 items-center gap-0.5 pl-3 lg:flex">
                @foreach(array_merge($essentialNav, $secondaryNav) as $link)
                    <a href="{{ route($link['route']) }}"
                       @class([$navBase, $navOn => $link['active'], $navOff => ! $link['active']])>
                        <i class="bi {{ $link['icon'] }} text-base {{ $link['active'] ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
                <a href="{{ $qatarUrl }}" target="_blank" rel="noopener noreferrer" @class([$navBase, $navOff])>
                    <i class="bi bi-box-arrow-up-right text-base text-slate-400"></i>
                    <span>Qatar Visa Check</span>
                </a>
            </nav>

            {{-- Right utilities — kept minimal: notifications + account only --}}
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
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
                                    <p class="text-sm font-medium text-slate-600">No important alerts</p>
                                    <p class="mt-0.5 text-xs text-slate-400">You're all caught up</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <span class="hidden h-6 w-px bg-slate-200 sm:block"></span>

                {{-- User menu — also hosts secondary nav (relocated from the old More ▾) --}}
                <x-ui.dropdown align="right" width="w-64">
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

                    {{-- 1 · identity (read-only) --}}
                    <div class="border-b border-slate-100 px-3.5 py-2.5">
                        <div class="text-sm font-semibold text-slate-900">{{ $authUser->name }}</div>
                        <div class="truncate text-xs text-slate-400">{{ $authUser->email }}</div>
                    </div>
                    {{-- 2·3 · admin-only account management --}}
                    @if($isAdmin)
                        <x-ui.dropdown-item :href="route('settings.index')" icon="bi-gear"
                            @class(['bg-indigo-50 font-semibold' => request()->routeIs('settings.*')])>Settings</x-ui.dropdown-item>
                        <x-ui.dropdown-item :href="route('staff.index')" icon="bi-people-fill"
                            @class(['bg-indigo-50 font-semibold' => request()->routeIs('staff.*')])>Staff Accounts</x-ui.dropdown-item>
                    @endif
                    {{-- 4 · change password (all users) --}}
                    <x-ui.dropdown-item :href="route('password.edit')" icon="bi-key"
                        @class(['bg-indigo-50 font-semibold' => request()->routeIs('password.edit')])>Change Password</x-ui.dropdown-item>
                    {{-- 5 · sign out --}}
                    <div class="my-1 border-t border-slate-100"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.dropdown-item type="submit" icon="bi-box-arrow-right" tone="danger">Sign out</x-ui.dropdown-item>
                    </form>
                </x-ui.dropdown>
            </div>
        </div>
    </header>

    {{-- Spacer: offsets page content below the fixed header (both layouts inherit this) --}}
    <div class="h-16" aria-hidden="true"></div>

    {{-- Mobile nav drawer (< lg) --}}
    <div x-show="mobileNav" x-cloak @click="mobileNav = false" x-transition.opacity
         class="fixed inset-0 z-50 bg-slate-900/50 lg:hidden"></div>
    <aside x-show="mobileNav" x-cloak
           x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-white shadow-xl lg:hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-500 via-indigo-500 to-violet-600 text-white"><i class="bi bi-passport-fill"></i></span>
                <span class="text-base font-extrabold tracking-tight text-slate-900">VisaDeskPro</span>
            </div>
            <button @click="mobileNav = false" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100"><i class="bi bi-x-lg"></i></button>
        </div>
        @php
            $drawerItem = 'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors';
            $drawerOn   = 'bg-indigo-50 text-indigo-700';
            $drawerOff  = 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
        @endphp
        <nav class="flex-1 overflow-y-auto px-3 py-3">
            @foreach(array_merge($essentialNav, $secondaryNav) as $link)
                <a href="{{ route($link['route']) }}" @click="mobileNav = false"
                   @class([$drawerItem, $drawerOn => $link['active'], $drawerOff => ! $link['active']])>
                    <i class="bi {{ $link['icon'] }} w-5 text-center text-base {{ $link['active'] ? 'text-indigo-600' : 'text-slate-400' }}"></i>
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach

            <a href="{{ $qatarUrl }}" target="_blank" rel="noopener noreferrer" @click="mobileNav = false" @class([$drawerItem, $drawerOff])>
                <i class="bi bi-box-arrow-up-right w-5 text-center text-base text-slate-400"></i>
                <span>Qatar Visa Check</span>
            </a>

            @if($isAdmin)
                <div class="my-2 border-t border-slate-100"></div>
                <div class="px-3 pb-1 text-[0.65rem] font-semibold uppercase tracking-wider text-slate-400">Admin</div>
                <a href="{{ route('settings.index') }}" @click="mobileNav = false" @class([$drawerItem, $drawerOn => request()->routeIs('settings.*'), $drawerOff => ! request()->routeIs('settings.*')])>
                    <i class="bi bi-gear w-5 text-center text-base {{ request()->routeIs('settings.*') ? 'text-indigo-600' : 'text-slate-400' }}"></i><span>Settings</span>
                </a>
                <a href="{{ route('staff.index') }}" @click="mobileNav = false" @class([$drawerItem, $drawerOn => request()->routeIs('staff.*'), $drawerOff => ! request()->routeIs('staff.*')])>
                    <i class="bi bi-people-fill w-5 text-center text-base {{ request()->routeIs('staff.*') ? 'text-indigo-600' : 'text-slate-400' }}"></i><span>Staff Accounts</span>
                </a>
            @endif

            <a href="{{ route('password.edit') }}" @click="mobileNav = false" @class([$drawerItem, $drawerOn => request()->routeIs('password.edit'), $drawerOff => ! request()->routeIs('password.edit')])>
                <i class="bi bi-key w-5 text-center text-base {{ request()->routeIs('password.edit') ? 'text-indigo-600' : 'text-slate-400' }}"></i><span>Change Password</span>
            </a>

            <div class="my-2 border-t border-slate-100"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="{{ $drawerItem }} w-full text-left text-rose-600 hover:bg-rose-50">
                    <i class="bi bi-box-arrow-right w-5 text-center text-base text-rose-500"></i><span>Sign out</span>
                </button>
            </form>
        </nav>
    </aside>
</div>
