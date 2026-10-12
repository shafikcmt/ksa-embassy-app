@extends('layouts.super-admin-app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@php
    use App\Support\Bd;
    $greet = Bd::greeting();
    $adminName = trim(explode(' ', auth()->user()->name)[0] ?? '') ?: 'Admin';
    $th = 'px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500';
@endphp

{{-- ── Welcome banner (Bangla + English, Dhaka date) ── --}}
<div class="mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-indigo-600 to-violet-700 p-6 text-white shadow-lift sm:p-8">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-medium text-white/80">{{ $greet['en'] }} · {{ Bd::banglaDate() }}</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ $greet['bn'] }}, {{ $adminName }} 👋</h1>
            <p class="mt-1 text-base text-white/85">VisaDeskPro সিস্টেম ওভারভিউ · System overview · {{ Bd::dhaka()->format('h:i A') }} (ঢাকা)</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ route('super-admin.agencies.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-brand-700 shadow-sm transition hover:bg-brand-50"><i class="bi bi-plus-circle"></i> নতুন এজেন্সি · Add Agency</a>
            <a href="{{ route('super-admin.subscriptions.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25"><i class="bi bi-credit-card"></i> সাবস্ক্রিপশন</a>
            <a href="{{ route('super-admin.settings.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25"><i class="bi bi-gear"></i> পেমেন্ট সেটিংস</a>
        </div>
    </div>
</div>

{{-- ── Money (৳) ── --}}
<div class="mb-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-ui.stat icon="bi-cash-coin" tone="green" accentLeft label="Total collected" bn="মোট আদায়" :value="Bd::taka($money['total'])" sub="paid subscriptions" />
    <x-ui.stat icon="bi-calendar-check" tone="brand" accentLeft label="This month" bn="এই মাসের আয়" :value="Bd::taka($money['this_month'])" :sub="Bd::dhaka()->format('F Y')" />
    <x-ui.stat :href="route('super-admin.subscriptions.index', ['payment_status' => 'pending'])" icon="bi-hourglass-split" tone="amber" accentLeft label="Payment pending" bn="পেমেন্ট বাকি" :value="Bd::taka($money['pending_amount'])" :sub="$money['pending_count'].' subscription'.($money['pending_count'] === 1 ? '' : 's')" subTone="amber" />
    <x-ui.stat icon="bi-patch-exclamation" tone="red" accentLeft label="Licenses due ≤ 30 days" bn="লাইসেন্স নবায়ন বাকি" :value="$licenseDueCount" sub="renewal follow-up" subTone="red" />
</div>

{{-- ── Primary stats ── --}}
<div class="mb-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
    <x-ui.stat :href="route('super-admin.agencies.index')" icon="bi-buildings" tone="brand"
        label="Total Agencies" bn="মোট এজেন্সি" :value="$stats['total_agencies']" :sub="$stats['active_agencies'].' active'" subTone="green" />
    <x-ui.stat :href="route('super-admin.subscriptions.index')" icon="bi-credit-card" tone="green"
        label="Active Subscriptions" bn="সক্রিয় সাবস্ক্রিপশন" :value="$stats['active_subscriptions']" :sub="$stats['expired_subscriptions'].' expired'" subTone="red" />
    <x-ui.stat icon="bi-slash-circle" tone="red"
        label="Suspended" bn="স্থগিত" :value="$stats['suspended_agencies']" sub="agencies" />
    <x-ui.stat :href="route('super-admin.agents.index')" icon="bi-people" tone="amber"
        label="Total Users" bn="মোট ব্যবহারকারী" :value="$stats['total_users']" sub="agency staff" />
</div>

{{-- ── Data stats ── --}}
<div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
    <x-ui.stat :href="route('super-admin.agents.index')" icon="bi-person-badge" tone="brand"
        label="Total Agents" bn="মোট এজেন্ট" :value="$stats['total_agents']" sub="all agencies" />
    <x-ui.stat :href="route('super-admin.hr.index')" icon="bi-person-vcard" tone="green"
        label="HR Profiles" bn="এইচআর প্রোফাইল" :value="$stats['total_hr']" sub="all agencies" />
    <x-ui.stat :href="route('super-admin.embassy-lists.index')" icon="bi-list-ol" tone="amber"
        label="Embassy Lists" bn="দূতাবাস তালিকা" :value="$stats['total_embassy_lists']" sub="active" />
    <x-ui.stat icon="bi-file-earmark-pdf" tone="violet"
        label="PDFs Generated" bn="তৈরি পিডিএফ" :value="$stats['total_documents']" :sub="$stats['docs_this_month'].' this month'" />
</div>

{{-- ── Needs attention: pending payments + licenses to renew ── --}}
<div class="mb-6 grid grid-cols-1 gap-5 xl:grid-cols-2">
    <x-ui.card class="overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <span class="flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-hourglass-split text-amber-500"></i> পেমেন্ট বাকি <span class="text-sm font-medium text-slate-400">Pending payments</span></span>
            <a href="{{ route('super-admin.subscriptions.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">সব দেখুন</a>
        </div>
        @forelse($pendingPayments as $sub)
            <a href="{{ route('super-admin.subscriptions.index') }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 transition last:border-0 hover:bg-slate-50">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-slate-900">{{ $sub->agency?->name ?? '—' }}</div>
                    <div class="text-sm text-slate-500">{{ $sub->plan?->name ?? '—' }} · {{ Bd::dhaka($sub->created_at)->format('d M Y') }}</div>
                </div>
                <span class="shrink-0 text-base font-extrabold text-amber-600">{{ Bd::taka($sub->amount) }}</span>
            </a>
        @empty
            <x-ui.empty icon="bi-check2-circle" title="কোনো পেমেন্ট বাকি নেই · All payments are settled" />
        @endforelse
    </x-ui.card>

    <x-ui.card class="overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <span class="flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-patch-exclamation text-rose-500"></i> লাইসেন্স নবায়ন <span class="text-sm font-medium text-slate-400">Licenses to renew</span></span>
            <a href="{{ route('super-admin.agencies.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">সব এজেন্সি</a>
        </div>
        @forelse($licenseDue as $ag)
            @php $left = (int) now()->startOfDay()->diffInDays($ag->license_expiry_date, false); @endphp
            <a href="{{ route('super-admin.agencies.edit', $ag) }}" class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 transition last:border-0 hover:bg-slate-50">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-slate-900">{{ $ag->name }}</div>
                    <div class="text-sm text-slate-500">মেয়াদ: {{ $ag->license_expiry_date->format('d M Y') }}</div>
                </div>
                <x-ui.badge :tone="$left < 0 ? 'red' : ($left <= 7 ? 'red' : 'amber')">{{ $left < 0 ? 'মেয়াদ শেষ (' . abs($left) . 'দিন)' : $left . ' দিন বাকি' }}</x-ui.badge>
            </a>
        @empty
            <x-ui.empty icon="bi-shield-check" title="৩০ দিনের মধ্যে কোনো লাইসেন্স শেষ হচ্ছে না" />
        @endforelse
    </x-ui.card>
</div>

{{-- ── ROW 3: Agencies + Expiring subs ────────────── --}}
<div class="mb-5 grid grid-cols-1 gap-5 xl:grid-cols-12">

    {{-- Recent Agencies --}}
    <x-ui.card class="overflow-hidden xl:col-span-7">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <span class="flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-buildings text-brand-600"></i> সাম্প্রতিক এজেন্সি <span class="text-sm font-medium text-slate-400">Recent agencies</span></span>
            <a href="{{ route('super-admin.agencies.index') }}" class="text-sm font-semibold text-brand-600 transition-colors hover:text-brand-700">সব দেখুন</a>
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
                            <td class="px-5 py-2.5 text-slate-400">{{ Bd::dhaka($agency->created_at)->format('d M Y') }}</td>
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
            <span class="flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-exclamation-triangle text-amber-500"></i> শীঘ্র মেয়াদ শেষ <span class="text-sm font-medium text-slate-400">Expiring soon</span></span>
            <span class="text-sm text-slate-400">১৪ দিনের মধ্যে</span>
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
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-base font-bold text-slate-900">
            <i class="bi bi-trophy text-amber-500"></i> সেরা এজেন্সি <span class="text-sm font-medium text-slate-400">Top agencies by HR</span>
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
            <span class="flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-journal-text text-violet-500"></i> সাম্প্রতিক কার্যক্রম <span class="text-sm font-medium text-slate-400">Activity log</span></span>
            <span class="text-sm text-slate-400">শেষ ১০টি</span>
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
                                <td class="px-5 py-2.5 text-slate-400">{{ Bd::dhaka($log->created_at)->format('d M, h:i A') }}</td>
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
