@extends('layouts.agency-app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@php
    $authUser  = auth()->user();
    $isAdmin   = method_exists($authUser, 'isAgencyAdmin') ? $authUser->isAgencyAdmin() : true;
    $firstName = \Illuminate\Support\Str::of($authUser->name)->trim()->explode(' ')->first() ?: 'there';
    $hour      = (int) now()->format('H');
    $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $canHr   = $authUser->can('create', \App\Models\HrProfile::class);
    $canList = $authUser->can('create', \App\Models\EmbassyList::class);

    // ── Alerts (compact) ──────────────────────────────────────────
    // Normal staff don't need billing/license notices — keep those for admins only.
    $alertItems = collect($alerts)
        ->filter(fn($a) => $isAdmin || ($a['scope'] ?? 'operations') !== 'billing')
        ->map(fn($a) => [
            'type'    => $a['type'] ?? 'info',
            'icon'    => $a['icon'] ?? 'bi-info-circle',
            'title'   => $a['title'] ?? 'Notice',
            'message' => $a['message'] ?? '',
            'action'  => $a['action'] ?? null,
            'label'   => $a['action_label'] ?? null,
        ]);

    foreach (($agency?->notices ?? []) as $notice) {
        $alertItems->push([
            'type'    => in_array($notice->type, ['danger','warning','info','success']) ? $notice->type : 'info',
            'icon'    => 'bi-megaphone',
            'title'   => $notice->title,
            'message' => $notice->body,
            'action'  => null,
            'label'   => null,
        ]);
    }
    // Most urgent first: danger → warning → info/success.
    $tonePriority = ['danger' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];
    $alertItems   = $alertItems->sortBy(fn($a) => $tonePriority[$a['type']] ?? 9)->values();

    $alertTone = [
        'danger'  => 'bg-rose-50 text-rose-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'info'    => 'bg-brand-50 text-brand-600',
        'success' => 'bg-emerald-50 text-emerald-600',
    ];

    // ── Upcoming reminders (real dates only) ──────────────────────
    $reminders = [];
    if ($isAdmin && $subscription && $subscription->end_date) {
        $d = (int) now()->startOfDay()->diffInDays($subscription->end_date, false);
        $reminders[] = ['icon' => 'bi-gem', 'dot' => $d <= 3 ? 'bg-rose-500' : ($d <= 7 ? 'bg-amber-500' : 'bg-emerald-500'),
            'title' => 'Subscription '.($d < 0 ? 'expired' : 'renewal'), 'date' => $subscription->end_date, 'days' => $d, 'href' => route('subscription.expired')];
    }
    foreach (($upcomingExpiries ?? collect()) as $p) {
        $d = (int) now()->startOfDay()->diffInDays($p->expiry_date, false);
        $reminders[] = ['icon' => 'bi-passport', 'dot' => $d <= 30 ? 'bg-rose-500' : 'bg-amber-500',
            'title' => 'Passport · '.($p->hrProfile?->full_name_en ?? 'Candidate'), 'date' => $p->expiry_date, 'days' => $d,
            'href' => route('hr.index', ['filter' => 'passport_expiring'])];
    }
    $reminders = collect($reminders)->sortBy('days')->take(4)->values();
@endphp

{{-- ════════ HERO ════════ --}}
<div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-r from-brand-50 via-white to-violet-50 p-5 shadow-soft sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">{{ $greeting }},</p>
            <h2 class="mt-0.5 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $firstName }} 👋</h2>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                <span class="inline-flex items-center gap-1.5 font-medium text-slate-700"><i class="bi bi-buildings text-brand-500"></i>{{ $agency?->name }}</span>
                <span class="hidden text-slate-300 sm:inline">·</span>
                <span>Here’s a quick overview of your agency today.</span>
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if($canHr)
                <a href="{{ route('hr.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-4 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:shadow-md"><i class="bi bi-plus-lg"></i> Add HR</a>
            @endif
            @if($canList)
                <a href="{{ route('embassy-lists.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:text-brand-700"><i class="bi bi-list-ol"></i> Embassy List</a>
            @endif
        </div>
    </div>
</div>

{{-- ════════ IMPORTANT ALERTS (compact, max 3 + view all) ════════ --}}
@if($alertItems->count())
    <div x-data="{ all: false }" class="mb-5 rounded-2xl border border-slate-200 bg-white shadow-soft">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                <i class="bi bi-bell text-amber-500"></i> Important Alerts
                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-bold text-slate-600">{{ $alertItems->count() }}</span>
            </h2>
            @if($alertItems->count() > 3)
                <button @click="all = !all" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                    <span x-show="!all">View all</span><span x-show="all" x-cloak>Show less</span>
                </button>
            @endif
        </div>
        <ul class="divide-y divide-slate-100">
            @foreach($alertItems as $i => $a)
                <li @if($i >= 3) x-show="all" x-cloak @endif>
                    <a @if($a['action']) href="{{ $a['action'] }}" @endif
                       class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50 @if(!$a['action']) cursor-default @endif">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $alertTone[$a['type']] ?? $alertTone['info'] }}">
                            <i class="bi {{ $a['icon'] }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-slate-800">{{ $a['title'] }}</div>
                            <div class="truncate text-xs text-slate-500">{!! $a['message'] !!}</div>
                        </div>
                        @if($a['label'])
                            <span class="hidden shrink-0 text-xs font-semibold text-brand-600 sm:inline">{{ $a['label'] }} →</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ════════ OVERVIEW (4 cards) ════════ --}}
<div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
    <x-ui.stat :href="route('hr.index')" icon="bi-person-vcard" tone="brand" label="Total HR Records" :value="$stats['total_hr']" :sub="$stats['active_hr'].' active'" />
    <x-ui.stat :href="route('hr.index', ['status' => 'active'])" icon="bi-person-check" tone="green" label="Active Candidates" :value="$stats['active_hr']"
        :sub="$stats['total_hr'] > 0 ? round($stats['active_hr'] / max(1,$stats['total_hr']) * 100).'% of total' : 'No records'" subTone="green" />
    <x-ui.stat :href="route('embassy-lists.index')" icon="bi-list-ol" tone="violet" label="Embassy Lists" :value="$stats['total_embassy_lists']" :sub="$stats['embassy_lists_month'].' this month'" />
    <x-ui.stat :href="route('embassy-lists.index', ['status' => 'draft'])" icon="bi-hourglass-split" tone="amber" label="Pending Drafts" :value="$stats['hr_draft_embassy']"
        :sub="$stats['hr_draft_embassy'] > 0 ? 'awaiting finalize' : 'all clear'" :subTone="$stats['hr_draft_embassy'] > 0 ? 'amber' : 'green'" />
</div>

{{-- ════════ MAIN GRID ════════ --}}
<div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

    {{-- ──── LEFT: quick actions + recent records ──── --}}
    <div class="space-y-5 lg:col-span-8">

        {{-- Quick Actions (3 cards) --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-lightning-charge-fill text-amber-500"></i> Quick Actions</h2>
            </div>
            <div class="grid grid-cols-1 gap-2.5 p-4 sm:grid-cols-3">
                @if($canHr)
                    <x-ui.quick-action :href="route('hr.create')" icon="bi-person-plus" title="Add New HR" sub="Create a candidate profile" tone="brand" />
                @endif
                @if($canList)
                    <x-ui.quick-action :href="route('embassy-lists.create')" icon="bi-list-ol" title="Create Embassy List" sub="Build a submission list" tone="violet" />
                @endif
                <x-ui.quick-action :href="route('hr.index')" icon="bi-printer" title="Print / Download" sub="Generate documents" tone="cyan" />
            </div>
        </x-ui.card>

        {{-- Recent HR Records --}}
        <x-ui.card class="overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-person-vcard text-violet-500"></i> Recent HR Records</h2>
                <a href="{{ route('hr.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all</a>
            </div>
            @if($recentHr->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-slate-100 bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2.5">Name</th>
                            <th class="px-5 py-2.5">Passport No</th>
                            <th class="px-5 py-2.5">Status</th>
                            <th class="hidden px-5 py-2.5 sm:table-cell">Updated</th>
                            <th class="px-5 py-2.5 text-right">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentHr as $hr)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-2.5">
                                        <a href="{{ route('hr.show', $hr) }}" class="font-semibold text-slate-800 hover:text-brand-600">{{ $hr->full_name_en }}</a>
                                        <div class="text-xs text-slate-400">{{ $hr->nationality }}</div>
                                    </td>
                                    <td class="px-5 py-2.5">
                                        @if($hr->passport?->passport_number)
                                            <span class="font-mono text-xs text-slate-600">{{ $hr->passport->passport_number }}</span>
                                            @if($hr->passport->expiry_date?->isPast())
                                                <i class="bi bi-exclamation-triangle-fill ml-1 text-rose-500" title="Passport expired"></i>
                                            @elseif($hr->passport->expiry_date && $hr->passport->expiry_date->isBefore(now()->addMonths(6)))
                                                <i class="bi bi-exclamation-triangle ml-1 text-amber-500" title="Expiring soon"></i>
                                            @endif
                                        @else <span class="text-slate-300">—</span> @endif
                                    </td>
                                    <td class="px-5 py-2.5"><x-ui.status-badge :status="$hr->status" /></td>
                                    <td class="hidden px-5 py-2.5 text-slate-400 sm:table-cell">{{ optional($hr->updated_at)->format('d M Y') }}</td>
                                    <td class="px-5 py-2.5">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('hr.show', $hr) }}" title="View" class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('hr.documents', $hr) }}" title="Documents" class="grid h-7 w-7 place-items-center rounded-lg text-emerald-600 hover:bg-emerald-50"><i class="bi bi-file-earmark-pdf"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-ui.empty icon="bi-person-vcard" title="No HR profiles yet" :actionUrl="route('hr.create')" actionLabel="Add First Profile" />
            @endif
        </x-ui.card>
    </div>

    {{-- ──── RIGHT: subscription + reminders ──── --}}
    <div class="space-y-5 lg:col-span-4">

        {{-- Subscription (admins) — compact --}}
        @if($isAdmin)
            @if($subscription)
                @php
                    $daysLeft  = $subscription->daysRemaining();
                    $accent    = $daysLeft <= 3 ? 'bg-rose-500' : ($daysLeft <= 7 ? 'bg-amber-500' : 'bg-emerald-500');
                    $accentTxt = $daysLeft <= 3 ? 'text-rose-600' : ($daysLeft <= 7 ? 'text-amber-600' : 'text-emerald-600');
                @endphp
                <x-ui.card class="overflow-hidden">
                    <div class="h-1 {{ $accent }}"></div>
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-credit-card text-brand-600"></i> Subscription</h2>
                        <x-ui.status-badge :status="$subscription->status" />
                    </div>
                    <div class="p-5">
                        <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
                            <div>
                                <div class="text-base font-bold text-slate-900">{{ $subscription->plan->name ?? '—' }}</div>
                                <div class="mt-0.5 text-xs text-slate-400"><i class="bi bi-calendar-event mr-1"></i>Expires {{ optional($subscription->end_date)->format('d M Y') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-extrabold leading-none {{ $accentTxt }}">{{ $daysLeft }}</div>
                                <div class="text-xs text-slate-400">days left</div>
                            </div>
                        </div>
                        <x-ui.usage-meter label="HR Profiles" :used="$stats['total_hr']" :limit="$subscription->plan->max_hr ?? 9999" color="brand" />
                        <x-ui.usage-meter label="PDFs (month)" :used="$stats['pdf_downloads_month']" :limit="$subscription->plan->max_pdf_monthly ?? 9999" color="cyan" />
                    </div>
                </x-ui.card>
            @else
                <x-ui.card class="flex flex-col items-center justify-center p-6 text-center">
                    <i class="bi bi-credit-card-2-front mb-2 text-3xl text-amber-400"></i>
                    <div class="font-semibold text-slate-900">No Active Subscription</div>
                    <p class="mt-1 text-xs text-slate-400">Subscribe to create records and generate PDFs.</p>
                    <x-ui.button :href="route('subscription.expired')" variant="success" size="sm" class="mt-3"><i class="bi bi-send"></i> Request Renewal</x-ui.button>
                </x-ui.card>
            @endif
        @endif

        {{-- Upcoming reminders --}}
        <x-ui.card class="overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-bell text-amber-500"></i> Upcoming &amp; Reminders</h2>
            </div>
            <div class="p-3">
                @if($reminders->count())
                    <ul class="space-y-1">
                        @foreach($reminders as $r)
                            <li>
                                <a @if($r['href']) href="{{ $r['href'] }}" @endif class="flex items-center gap-3 rounded-lg px-2.5 py-2 transition hover:bg-slate-50 @if(!$r['href']) cursor-default @endif">
                                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                                        <span class="absolute inline-flex h-full w-full rounded-full {{ $r['dot'] }} opacity-40"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $r['dot'] }}"></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-slate-700"><i class="bi {{ $r['icon'] }} mr-1 text-slate-400"></i>{{ $r['title'] }}</span>
                                        <span class="block text-xs text-slate-400">{{ $r['date']->format('d M Y') }}</span>
                                    </span>
                                    <span @class([
                                        'shrink-0 rounded-full px-2 py-0.5 text-[0.68rem] font-semibold',
                                        'bg-rose-50 text-rose-600'    => $r['days'] < 0 || $r['days'] <= 7,
                                        'bg-amber-50 text-amber-600'  => $r['days'] > 7 && $r['days'] <= 30,
                                        'bg-slate-100 text-slate-500' => $r['days'] > 30,
                                    ])>
                                        {{ $r['days'] < 0 ? abs($r['days']).'d ago' : 'in '.$r['days'].'d' }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-4 py-8 text-center">
                        <i class="bi bi-calendar-check mb-2 block text-2xl text-emerald-400"></i>
                        <p class="text-sm text-slate-500">No upcoming reminders.</p>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</div>

@endsection
