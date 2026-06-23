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

    // ── Onboarding workflow state (drives hero progress + steps) ──
    $wf = [
        ['num' => 1, 'icon' => 'bi-person-plus',     'title' => 'Add HR Profile',      'sub' => $stats['total_hr'].' profiles',           'done' => $stats['total_hr'] > 0,                                          'href' => $canHr ? route('hr.create') : route('hr.index')],
        ['num' => 2, 'icon' => 'bi-clipboard-check', 'title' => 'Complete Information', 'sub' => $stats['hr_no_passport'] > 0 ? $stats['hr_no_passport'].' need passport' : 'Profiles ready', 'done' => $stats['total_hr'] > 0 && $stats['hr_no_passport'] === 0, 'href' => route('hr.index')],
        ['num' => 3, 'icon' => 'bi-list-ol',         'title' => 'Create Embassy List', 'sub' => $stats['total_embassy_lists'].' lists',   'done' => $stats['total_embassy_lists'] > 0,                               'href' => $canList ? route('embassy-lists.create') : route('embassy-lists.index')],
        ['num' => 4, 'icon' => 'bi-file-earmark-pdf','title' => 'Generate Documents',  'sub' => $stats['pdf_downloads_month'].' this month', 'done' => $stats['pdf_downloads_month'] > 0,                            'href' => route('hr.index')],
        ['num' => 5, 'icon' => 'bi-printer',         'title' => 'Print / Download',    'sub' => 'Final output',                            'done' => $stats['pdf_downloads_month'] > 0,                               'href' => route('hr.index')],
    ];
    $wfTotal     = count($wf);
    $wfDone      = collect($wf)->where('done', true)->count();
    $wfActiveIdx = collect($wf)->search(fn($w) => ! $w['done']);   // false when all done
    $wfPct       = (int) round($wfDone / $wfTotal * 100);

    // ── Application (embassy list) status breakdown ──
    $esc        = $embassyStatusCounts ?? collect();
    $statusMeta = [
        'draft'     => ['label' => 'Draft',     'tone' => 'amber',  'bar' => 'bg-amber-400',   'chip' => 'bg-amber-50 text-amber-700 ring-amber-200',     'icon' => 'bi-pencil-square'],
        'finalized' => ['label' => 'Finalized', 'tone' => 'green',  'bar' => 'bg-emerald-400', 'chip' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'icon' => 'bi-check2-circle'],
        'printed'   => ['label' => 'Printed',   'tone' => 'brand',  'bar' => 'bg-brand-400',   'chip' => 'bg-brand-50 text-brand-700 ring-brand-200',     'icon' => 'bi-printer'],
        'cancelled' => ['label' => 'Cancelled', 'tone' => 'slate',  'bar' => 'bg-slate-300',   'chip' => 'bg-slate-100 text-slate-600 ring-slate-200',    'icon' => 'bi-x-circle'],
    ];
    $escTotal = collect($statusMeta)->keys()->sum(fn($k) => (int) ($esc[$k] ?? 0));

    // ── Upcoming reminders (real dates only) ──
    $reminders = [];
    if ($subscription && $subscription->end_date) {
        $d = (int) now()->startOfDay()->diffInDays($subscription->end_date, false);
        $reminders[] = ['icon' => 'bi-gem', 'dot' => $d <= 3 ? 'bg-rose-500' : ($d <= 7 ? 'bg-amber-500' : 'bg-emerald-500'),
            'title' => 'Subscription '.($d < 0 ? 'expired' : 'renewal'), 'date' => $subscription->end_date, 'days' => $d, 'href' => route('subscription.expired')];
    }
    if ($agency?->license_expiry_date) {
        $d = (int) now()->startOfDay()->diffInDays($agency->license_expiry_date, false);
        if ($d <= 60) {
            $reminders[] = ['icon' => 'bi-patch-check', 'dot' => $d < 0 ? 'bg-rose-500' : 'bg-amber-500',
                'title' => 'Agency license '.($d < 0 ? 'expired' : 'expiry'), 'date' => $agency->license_expiry_date, 'days' => $d, 'href' => null];
        }
    }
    foreach (($upcomingExpiries ?? collect()) as $p) {
        $d = (int) now()->startOfDay()->diffInDays($p->expiry_date, false);
        $reminders[] = ['icon' => 'bi-passport', 'dot' => $d <= 30 ? 'bg-rose-500' : 'bg-amber-500',
            'title' => 'Passport · '.($p->hrProfile?->full_name_en ?? 'Candidate'), 'date' => $p->expiry_date, 'days' => $d,
            'href' => route('hr.index', ['filter' => 'passport_expiring'])];
    }
    $reminders = collect($reminders)->sortBy('days')->take(6)->values();

    // ── Plan-aware progress for summary cards ──
    $maxHr  = $subscription->plan->max_hr ?? 0;
    $maxPdf = $subscription->plan->max_pdf_monthly ?? 0;
    $hrPct  = ($maxHr > 0 && $maxHr < 9999) ? round($stats['total_hr'] / $maxHr * 100) : null;
    $pdfPct = ($maxPdf > 0 && $maxPdf < 9999) ? round($stats['pdf_downloads_month'] / $maxPdf * 100) : null;
@endphp

{{-- ════════ HERO ════════ --}}
<div class="relative mb-5 overflow-hidden rounded-3xl bg-gradient-to-br from-violet-600 via-brand-600 to-cyan-500 p-6 text-white shadow-lift sm:p-7">
    <div class="pointer-events-none absolute -right-12 -top-20 h-72 w-72 rounded-full bg-white/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-fuchsia-300/20 blur-3xl"></div>
    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-medium text-white/80">{{ $greeting }},</p>
            <h2 class="mt-0.5 truncate text-2xl font-bold tracking-tight sm:text-3xl">{{ $firstName }} 👋</h2>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 backdrop-blur"><i class="bi bi-buildings"></i>{{ $agency?->name }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 backdrop-blur"><i class="bi bi-person-badge"></i>{{ $isAdmin ? 'Agency Admin' : 'Agency Staff' }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 backdrop-blur"><i class="bi bi-calendar3"></i>{{ now()->format('l, d M Y') }}</span>
            </div>

            {{-- Setup progress ribbon --}}
            <div class="mt-5 max-w-md">
                <div class="mb-1.5 flex items-center justify-between text-xs text-white/80">
                    <span class="font-medium">{{ $wfDone === $wfTotal ? 'Workflow complete 🎉' : 'Setup progress' }}</span>
                    <span class="font-semibold text-white">{{ $wfDone }}/{{ $wfTotal }} steps</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-white/20">
                    <div class="h-full rounded-full bg-white transition-all duration-700" style="width: {{ max($wfPct, 4) }}%"></div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('create', \App\Models\HrProfile::class)
                <a href="{{ route('hr.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-white px-4 text-sm font-semibold text-violet-700 shadow-lg shadow-violet-950/20 transition hover:-translate-y-0.5 hover:shadow-xl"><i class="bi bi-plus-lg"></i> Add HR</a>
            @endcan
            @can('create', \App\Models\EmbassyList::class)
                <a href="{{ route('embassy-lists.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20"><i class="bi bi-list-ol"></i> Embassy List</a>
            @endcan
            <a href="{{ route('hr.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20"><i class="bi bi-file-earmark-pdf"></i> Documents</a>
        </div>
    </div>
</div>

{{-- ════════ IMPORTANT ALERTS ════════ --}}
@php
    $noticeCount = $agency?->notices?->count() ?? 0;
    $alertTotal  = count($alerts) + $noticeCount;
    $hasUrgent   = collect($alerts)->contains(fn($a) => ($a['type'] ?? null) === 'danger');
@endphp
@if($alertTotal)
    <x-ui.card class="mb-5 overflow-hidden">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                <span @class([
                    'grid h-6 w-6 place-items-center rounded-lg',
                    'bg-rose-50 text-rose-500'   => $hasUrgent,
                    'bg-brand-50 text-brand-500' => ! $hasUrgent,
                ])><i class="bi bi-bell-fill text-xs"></i></span>
                Important Alerts
                <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-slate-100 px-1.5 text-xs font-bold text-slate-600">{{ $alertTotal }}</span>
            </h2>
            <span class="hidden text-xs font-medium text-slate-400 sm:inline">Action needed</span>
        </div>
        <div @class([
            'space-y-2.5 p-4',
            'max-h-[28rem] overflow-y-auto' => $alertTotal > 4,
        ])>
            @foreach($alerts as $alert)
                <x-ui.alert :type="$alert['type']" :icon="$alert['icon']" :title="$alert['title'] ?? null"
                    :message="$alert['message']" :action="$alert['action'] ?? null" :actionLabel="$alert['action_label'] ?? null" />
            @endforeach
            @foreach(($agency?->notices ?? []) as $notice)
                @php $nt = in_array($notice->type, ['danger','warning','info','success']) ? $notice->type : 'info'; @endphp
                <x-ui.alert :type="$nt" icon="bi-megaphone-fill" :title="$notice->title" badge="Notice" dismissible>{{ $notice->body }}</x-ui.alert>
            @endforeach
        </div>
    </x-ui.card>
@endif

{{-- ════════ SUMMARY CARDS ════════ --}}
<div class="mb-2.5 ml-0.5 flex items-center gap-2">
    <span class="h-3.5 w-1 rounded-full bg-gradient-to-b from-violet-500 to-cyan-500"></span>
    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Overview</span>
</div>
<div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
    <x-ui.stat accent :href="route('hr.index')" icon="bi-person-vcard" tone="brand" label="Total HR Records" :value="$stats['total_hr']"
        :sub="$hrPct !== null ? $hrPct.'% of plan' : $stats['active_hr'].' active'" :progress="$hrPct" />
    <x-ui.stat accent :href="route('hr.index')" icon="bi-person-check" tone="green" label="Active Candidates" :value="$stats['active_hr']"
        :sub="$stats['total_hr'] > 0 ? round($stats['active_hr'] / max(1,$stats['total_hr']) * 100).'% of total' : 'No records'" subTone="green" />
    <x-ui.stat accent :href="route('embassy-lists.index')" icon="bi-list-ol" tone="violet" label="Embassy Lists" :value="$stats['embassy_lists_month']" sub="this month" />
    <x-ui.stat accent :href="route('hr.index')" icon="bi-file-earmark-pdf" tone="cyan" label="Documents" :value="$stats['pdf_downloads_month']"
        :sub="$pdfPct !== null ? $pdfPct.'% of plan' : 'this month'" :progress="$pdfPct" />
    <x-ui.stat accent :href="route('embassy-lists.index', ['status' => 'draft'])" icon="bi-hourglass-split" tone="amber" label="Pending Drafts" :value="$stats['hr_draft_embassy']"
        :sub="$stats['hr_draft_embassy'] > 0 ? 'awaiting finalize' : 'all clear'" :subTone="$stats['hr_draft_embassy'] > 0 ? 'amber' : 'green'" />
    <x-ui.stat accent :href="route('hr.index', ['filter' => 'passport_expiring'])" icon="bi-passport" :tone="$stats['passports_expiring'] > 0 ? 'red' : 'slate'" label="Passport Expiring" :value="$stats['passports_expiring']"
        sub="within 6 months" :subTone="$stats['passports_expiring'] > 0 ? 'red' : 'muted'" />
</div>

{{-- ════════ ONBOARDING WORKFLOW (only while incomplete) ════════ --}}
@if($wfActiveIdx !== false)
    <x-ui.card class="mb-5 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-compass text-violet-500"></i> Get Started — Workflow Steps</h2>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                <i class="bi bi-flag"></i> Next: {{ $wf[$wfActiveIdx]['title'] }}
            </span>
        </div>
        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach($wf as $i => $w)
                <x-ui.workflow-step :num="$w['num']" :icon="$w['icon']" :title="$w['title']" :sub="$w['sub']" :href="$w['href']"
                    :state="$w['done'] ? 'done' : ($i === $wfActiveIdx ? 'active' : 'todo')" />
            @endforeach
        </div>
    </x-ui.card>
@endif

{{-- ════════ APPLICATION STATUS OVERVIEW ════════ --}}
<x-ui.card class="mb-5">
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-bar-chart-line text-violet-500"></i> Application Status Overview</h2>
        <a href="{{ route('embassy-lists.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all</a>
    </div>
    <div class="p-5">
        @if($escTotal > 0)
            {{-- stacked bar --}}
            <div class="mb-4 flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                @foreach($statusMeta as $key => $m)
                    @php $c = (int) ($esc[$key] ?? 0); @endphp
                    @if($c > 0)
                        <div class="{{ $m['bar'] }}" style="width: {{ $c / $escTotal * 100 }}%" title="{{ $m['label'] }}: {{ $c }}"></div>
                    @endif
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($statusMeta as $key => $m)
                    @php $c = (int) ($esc[$key] ?? 0); @endphp
                    <a href="{{ route('embassy-lists.index', ['status' => $key]) }}"
                       class="rounded-xl border border-slate-200 p-3 transition hover:border-slate-300 hover:shadow-soft">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[0.68rem] font-semibold ring-1 ring-inset {{ $m['chip'] }}">
                                <i class="bi {{ $m['icon'] }}"></i>{{ $m['label'] }}
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-extrabold leading-none text-slate-900">{{ $c }}</div>
                        <div class="mt-0.5 text-xs text-slate-400">{{ $escTotal > 0 ? round($c / $escTotal * 100) : 0 }}% of lists</div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="py-8 text-center">
                <i class="bi bi-bar-chart-line mb-2 block text-3xl text-slate-300"></i>
                <p class="text-sm text-slate-500">No embassy applications yet.</p>
                @can('create', \App\Models\EmbassyList::class)
                    <a href="{{ route('embassy-lists.create') }}" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700"><i class="bi bi-plus-lg"></i> Create first list</a>
                @endcan
            </div>
        @endif
    </div>
</x-ui.card>

{{-- ════════ MAIN GRID: records (left) + activity panel (right) ════════ --}}
<div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

    {{-- ──── LEFT: records + quick actions ──── --}}
    <div class="space-y-5 lg:col-span-8">

        {{-- Quick Actions --}}
        <x-ui.card>
            <div class="border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-lightning-charge-fill text-amber-500"></i> Quick Actions</h2>
            </div>
            <div class="grid grid-cols-1 gap-2 p-4 sm:grid-cols-2">
                @php
                    $qa = [];
                    if ($canHr)   $qa[] = ['route' => route('hr.create'),            'icon' => 'bi-person-vcard', 'title' => 'Add HR Profile',        'sub' => $stats['total_hr'].' total',                    'tone' => 'brand'];
                    if ($canList) $qa[] = ['route' => route('embassy-lists.create'), 'icon' => 'bi-list-ol',      'title' => 'Create Embassy List',   'sub' => $stats['embassy_lists_month'].' this month',    'tone' => 'violet'];
                    $qa[] = ['route' => route('hr.index'),            'icon' => 'bi-file-earmark-pdf', 'title' => 'Generate Documents', 'sub' => $stats['pdf_downloads_month'].' PDFs this month', 'tone' => 'cyan'];
                    $qa[] = ['route' => route('embassy-lists.index'), 'icon' => 'bi-collection',       'title' => 'Embassy Lists',      'sub' => $stats['total_embassy_lists'].' total',          'tone' => 'green'];
                    if ($authUser->can('create', \App\Models\Agent::class)) $qa[] = ['route' => route('agents.create'), 'icon' => 'bi-person-plus', 'title' => 'Add Staff / Agent', 'sub' => $stats['active_agents'].' active', 'tone' => 'rose'];
                    if ($isAdmin) $qa[] = ['route' => route('settings.index'), 'icon' => 'bi-gear', 'title' => 'Agency Settings', 'sub' => 'Profile & preferences', 'tone' => 'amber'];
                @endphp
                @foreach($qa as $a)
                    <x-ui.quick-action :href="$a['route']" :icon="$a['icon']" :title="$a['title']" :sub="$a['sub']" :tone="$a['tone']" />
                @endforeach
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
                            <th class="px-5 py-2.5">Name</th><th class="px-5 py-2.5">Passport</th><th class="px-5 py-2.5">Status</th><th class="hidden px-5 py-2.5 sm:table-cell">Created</th><th class="px-5 py-2.5 text-right">Action</th>
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
                                    <td class="hidden px-5 py-2.5 text-slate-400 sm:table-cell">{{ optional($hr->created_at)->format('d M Y') }}</td>
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

        {{-- Recent Embassy Lists --}}
        <x-ui.card class="overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-list-ol text-violet-500"></i> Recent Embassy Lists</h2>
                <a href="{{ route('embassy-lists.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">View all</a>
            </div>
            @if($recentEmbassyLists->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b border-slate-100 bg-slate-50/70 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2.5">List No</th><th class="px-5 py-2.5">Date</th><th class="px-5 py-2.5 text-center">Total</th><th class="px-5 py-2.5">Status</th><th class="px-5 py-2.5 text-right">Action</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentEmbassyLists as $list)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-2.5"><a href="{{ route('embassy-lists.show', $list) }}" class="font-mono font-semibold text-slate-700 hover:text-brand-600">{{ $list->list_no }}</a></td>
                                    <td class="px-5 py-2.5 text-slate-400">{{ optional($list->list_date)->format('d M Y') }}</td>
                                    <td class="px-5 py-2.5 text-center font-bold text-slate-700">{{ $list->total_items }}</td>
                                    <td class="px-5 py-2.5"><x-ui.status-badge :status="$list->status" /></td>
                                    <td class="px-5 py-2.5">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('embassy-lists.show', $list) }}" title="View" class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bi bi-eye"></i></a>
                                            @if($list->isFinalized() || $list->status === 'printed')
                                                <a href="{{ route('embassy-lists.download-pdf', $list) }}" title="PDF" class="grid h-7 w-7 place-items-center rounded-lg text-brand-600 hover:bg-brand-50"><i class="bi bi-file-earmark-pdf"></i></a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-ui.empty icon="bi-list-ol" title="No embassy lists yet" :actionUrl="route('embassy-lists.create')" actionLabel="Create First List" />
            @endif
        </x-ui.card>
    </div>

    {{-- ──── RIGHT: calendar / reminders / activity panel ──── --}}
    <div class="space-y-5 lg:col-span-4">

        {{-- Today card --}}
        <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-navy-800 p-5 text-white shadow-soft">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium uppercase tracking-wider text-white/60">{{ now()->format('l') }}</div>
                    <div class="mt-1 text-4xl font-extrabold leading-none">{{ now()->format('d') }}</div>
                    <div class="mt-1 text-sm text-white/70">{{ now()->format('F Y') }}</div>
                </div>
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-2xl ring-1 ring-white/15"><i class="bi bi-calendar-event"></i></span>
            </div>
        </div>

        {{-- Subscription & Usage --}}
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
                    <x-ui.usage-meter label="Agents" :used="$stats['total_agents']" :limit="$subscription->plan->max_agents ?? 9999" color="green" />
                    <x-ui.usage-meter label="Lists (month)" :used="$stats['embassy_lists_month']" :limit="$subscription->plan->max_embassy_lists_monthly ?? 9999" color="amber" />
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

        {{-- Recent activity timeline --}}
        <x-ui.card class="overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-clock-history text-violet-500"></i> Recent Activity</h2>
            </div>
            <div class="p-4">
                @if($recentDocActivity->count())
                    <ol class="relative ml-2 space-y-4 border-l border-slate-200 pl-5">
                        @foreach($recentDocActivity->take(6) as $doc)
                            @php $isDl = $doc->action === 'download'; @endphp
                            <li class="relative">
                                <span class="absolute -left-[1.45rem] top-0.5 grid h-5 w-5 place-items-center rounded-full text-[0.6rem] text-white ring-4 ring-white {{ $isDl ? 'bg-brand-500' : 'bg-slate-400' }}">
                                    <i class="bi {{ $isDl ? 'bi-download' : 'bi-eye' }}"></i>
                                </span>
                                <div class="text-sm font-medium text-slate-700">
                                    {{ ucwords(str_replace('_', ' ', $doc->document_type)) }}
                                    <span class="font-normal text-slate-400">{{ $isDl ? 'downloaded' : 'previewed' }}</span>
                                </div>
                                <div class="mt-0.5 text-xs text-slate-400">
                                    @if($doc->hrProfile)
                                        <a href="{{ route('hr.show', $doc->hrProfile) }}" class="font-medium text-slate-500 hover:text-brand-600">{{ $doc->hrProfile->full_name_en }}</a> ·
                                    @endif
                                    {{ $doc->generatedBy?->name ?? 'System' }} · {{ $doc->created_at->diffForHumans() }}
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="px-4 py-8 text-center">
                        <i class="bi bi-activity mb-2 block text-2xl text-slate-300"></i>
                        <p class="text-sm text-slate-500">No document activity yet.</p>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</div>

@endsection
