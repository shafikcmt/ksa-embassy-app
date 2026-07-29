@extends('layouts.super-admin-app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<x-ui.page-header title="Welcome to VisaDeskPro" subtitle="System Overview · {{ now()->format('l, d F Y') }}" icon="bi-speedometer2" />

{{-- ── ROW 1: Primary stats ───────────────────────── --}}
<div class="mb-3 grid grid-cols-2 gap-3 xl:grid-cols-4">
    <x-ui.stat :href="route('super-admin.agencies.index')" icon="bi-buildings" tone="brand"
        label="Total Agencies" :value="$stats['total_agencies']" :sub="$stats['active_agencies'].' active'" subTone="green" />
    <x-ui.stat :href="route('super-admin.subscriptions.index')" icon="bi-credit-card" tone="green"
        label="Active Subscriptions" :value="$stats['active_subscriptions']" :sub="$stats['expired_subscriptions'].' expired'" subTone="red" />
    <x-ui.stat icon="bi-slash-circle" tone="red"
        label="Suspended" :value="$stats['suspended_agencies']" sub="agencies" />
    <x-ui.stat :href="route('super-admin.agents.index')" icon="bi-people" tone="amber"
        label="Total Users" :value="$stats['total_users']" sub="agency staff" />
</div>

{{-- ── ROW 2: Data stats ──────────────────────────── --}}
<div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
    <x-ui.stat :href="route('super-admin.agents.index')" icon="bi-person-badge" tone="brand"
        label="Total Agents" :value="$stats['total_agents']" sub="all agencies" />
    <x-ui.stat :href="route('super-admin.hr.index')" icon="bi-person-vcard" tone="green"
        label="HR Profiles" :value="$stats['total_hr']" sub="all agencies" />
    <x-ui.stat :href="route('super-admin.embassy-lists.index')" icon="bi-list-ol" tone="amber"
        label="Embassy Lists" :value="$stats['total_embassy_lists']" sub="active" />
    <x-ui.stat icon="bi-file-earmark-pdf" tone="violet"
        label="PDFs Generated" :value="$stats['total_documents']" :sub="$stats['docs_this_month'].' this month'" />
</div>

{{-- ── ROW 3: Agencies + Expiring subs ────────────── --}}
<div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-12">

    {{-- Recent Agencies --}}
    <x-ui.card class="overflow-hidden xl:col-span-7">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <span class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-buildings text-brand-600"></i> Recent Agencies</span>
            <a href="{{ route('super-admin.agencies.index') }}" class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-2.5">Agency</th>
                        <th class="px-5 py-2.5">Plan</th>
                        <th class="px-5 py-2.5">Status</th>
                        <th class="px-5 py-2.5">Created</th>
                        <th class="px-5 py-2.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentAgencies as $agency)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-2.5">
                                <a href="{{ route('super-admin.agencies.show', $agency) }}" class="font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $agency->name }}</a>
                                <div class="text-xs text-slate-400">{{ $agency->email }}</div>
                            </td>
                            <td class="px-5 py-2.5">
                                @if($agency->activeSubscription)
                                    <x-ui.badge tone="brand">{{ $agency->activeSubscription->plan->name ?? '—' }}</x-ui.badge>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5"><x-ui.status-badge :status="$agency->status" /></td>
                            <td class="px-5 py-2.5 text-slate-400">{{ $agency->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-2.5">
                                <div class="flex justify-end">
                                    <a href="{{ route('super-admin.agencies.show', $agency) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-0"><x-ui.empty icon="bi-buildings" title="No agencies yet" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Expiring Subscriptions --}}
    <x-ui.card class="overflow-hidden xl:col-span-5">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <span class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-exclamation-triangle text-amber-500"></i> Expiring Soon</span>
            <span class="text-xs text-slate-400">Within 14 days</span>
        </div>
        @if($expiringSubscriptions->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2.5">Agency</th>
                            <th class="px-5 py-2.5">Plan</th>
                            <th class="px-5 py-2.5 text-right">Days</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($expiringSubscriptions as $sub)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-5 py-2.5">
                                    <a href="{{ route('super-admin.agencies.show', $sub->agency) }}" class="font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $sub->agency->name }}</a>
                                </td>
                                <td class="px-5 py-2.5 text-slate-600">{{ $sub->plan->name }}</td>
                                <td class="px-5 py-2.5 text-right">
                                    <x-ui.badge :tone="$sub->daysRemaining() <= 3 ? 'red' : 'amber'">{{ $sub->daysRemaining() }}d</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-ui.empty icon="bi-calendar-check" title="None expiring soon" />
        @endif
    </x-ui.card>
</div>

{{-- ── ROW 4: Top Agencies + Recent Audit Logs ─────── --}}
<div class="grid grid-cols-1 gap-5 xl:grid-cols-12">

    {{-- Top agencies by HR count --}}
    <x-ui.card class="overflow-hidden xl:col-span-5">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
            <i class="bi bi-trophy text-amber-500"></i> Top Agencies by HR Count
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-2.5">Agency</th>
                        <th class="px-5 py-2.5 text-center">HR</th>
                        <th class="px-5 py-2.5 text-center">Lists</th>
                        <th class="px-5 py-2.5">Plan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($topAgencies as $agency)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-2.5">
                                <a href="{{ route('super-admin.agencies.show', $agency) }}" class="font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $agency->name }}</a>
                            </td>
                            <td class="px-5 py-2.5 text-center font-bold text-slate-800">{{ $agency->hr_profiles_count }}</td>
                            <td class="px-5 py-2.5 text-center text-slate-500">{{ $agency->embassy_lists_count }}</td>
                            <td class="px-5 py-2.5">
                                @if($agency->activeSubscription)
                                    <x-ui.badge tone="brand">{{ $agency->activeSubscription->plan->name ?? '—' }}</x-ui.badge>
                                @else
                                    <span class="text-xs text-slate-400">No plan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-0"><x-ui.empty icon="bi-trophy" title="No agency data" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Recent Audit Logs --}}
    <x-ui.card class="overflow-hidden xl:col-span-7">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <span class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-journal-text text-violet-500"></i> Recent Activity Log</span>
            <span class="text-xs text-slate-400">Last 10 events</span>
        </div>
        @if($recentAuditLogs->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2.5">Action</th>
                            <th class="px-5 py-2.5">Agency</th>
                            <th class="px-5 py-2.5">User</th>
                            <th class="px-5 py-2.5">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentAuditLogs as $log)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-5 py-2.5">
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-600">{{ $log->action }}</span>
                                </td>
                                <td class="px-5 py-2.5 text-slate-600">{{ $log->agency?->name ?? '—' }}</td>
                                <td class="px-5 py-2.5 text-slate-600">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-5 py-2.5 text-slate-400">{{ $log->created_at->format('d M, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-ui.empty icon="bi-journal" title="No activity yet" />
        @endif
    </x-ui.card>
</div>

@endsection
