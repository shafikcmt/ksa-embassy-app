@extends('layouts.erp-app')

@section('title', 'Profit / Loss')
@section('page-title', 'Profit / Loss')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $money = fn ($v) => '৳' . number_format((float) $v, 2);
    $profit = (float) $summary['profit'];
    $profitPositive = $profit >= 0;
    $exportQuery = array_filter(['period' => $period, 'from' => $from, 'to' => $to]);
    $unlockLabel = $unlockedUntil > 0 ? \Illuminate\Support\Carbon::createFromTimestamp($unlockedUntil)->format('h:i A') : null;
@endphp

{{-- Unlock status + lock control --}}
<div class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-2 text-sm text-slate-500">
        <i class="bi bi-unlock-fill text-emerald-500"></i>
        @if($unlockLabel)
            Unlocked until <span class="font-semibold text-slate-700">{{ $unlockLabel }}</span> (auto-locks after 15 min).
        @else
            Profit / Loss is currently accessible.
        @endif
    </div>
    <form method="POST" action="{{ route('erp.profit-loss.lock') }}">
        @csrf
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <i class="bi bi-lock"></i> Lock now
        </button>
    </form>
</div>

{{-- Period filter --}}
<form method="GET" action="{{ route('erp.profit-loss') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5" x-data="{ period: '{{ $period }}' }">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="{{ $lbl }}">Period</label>
            <select name="period" x-model="period" class="{{ $inp }}">
                <option value="all">All-time</option>
                <option value="month">This month</option>
                <option value="custom">Custom range</option>
            </select>
        </div>
        <div x-show="period === 'custom'" x-cloak><label class="{{ $lbl }}">From</label><input type="date" name="from" value="{{ $from }}" class="{{ $inp }}"></div>
        <div x-show="period === 'custom'" x-cloak><label class="{{ $lbl }}">To</label><input type="date" name="to" value="{{ $to }}" class="{{ $inp }}"></div>
        <div class="flex items-end">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-funnel"></i> Apply</button>
        </div>
    </div>
    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Export</span>
        <a href="{{ route('erp.profit-loss.export.pdf', $exportQuery) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        <a href="{{ route('erp.profit-loss.export.csv', $exportQuery) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><i class="bi bi-filetype-csv"></i> CSV</a>
    </div>
</form>

{{-- Headline profit --}}
<div class="mb-6 grid gap-4 lg:grid-cols-3">
    <div class="rounded-2xl border {{ $profitPositive ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50' : 'border-rose-200 bg-gradient-to-br from-rose-50 to-orange-50' }} p-6 lg:col-span-1">
        <div class="text-xs font-semibold uppercase tracking-wide {{ $profitPositive ? 'text-emerald-600' : 'text-rose-600' }}">
            Net {{ $profitPositive ? 'Profit' : 'Loss' }} ({{ $period === 'custom' ? 'custom' : ($period === 'month' ? 'this month' : 'all-time') }})
        </div>
        <div class="mt-2 text-3xl font-extrabold {{ $profitPositive ? 'text-emerald-700' : 'text-rose-700' }}">{{ $money(abs($profit)) }}</div>
        <div class="mt-1 text-xs {{ $profitPositive ? 'text-emerald-600/70' : 'text-rose-600/70' }}">Revenue − Cost (cash basis)</div>
    </div>

    {{-- Revenue vs cost breakdown --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2">
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <span class="font-medium text-slate-700"><i class="bi bi-arrow-down-circle mr-1.5 text-emerald-500"></i>Revenue <span class="text-slate-400">(collected)</span></span>
                <span class="font-bold text-emerald-700">{{ $money($summary['revenue']) }}</span>
            </div>
            <div class="border-t border-slate-100"></div>
            <div class="flex items-center justify-between pl-5">
                <span class="text-slate-500">Cost — Expenses</span>
                <span class="text-rose-600">− {{ $money($summary['expenseCost']) }}</span>
            </div>
            <div class="flex items-center justify-between pl-5">
                <span class="text-slate-500">Cost — Agent payouts</span>
                <span class="text-rose-600">− {{ $money($summary['agentPayoutCost']) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="font-medium text-slate-700"><i class="bi bi-arrow-up-circle mr-1.5 text-rose-500"></i>Total Cost</span>
                <span class="font-bold text-rose-700">− {{ $money($summary['totalCost']) }}</span>
            </div>
            <div class="border-t-2 border-slate-200"></div>
            <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900">Net {{ $profitPositive ? 'Profit' : 'Loss' }}</span>
                <span class="font-extrabold {{ $profitPositive ? 'text-emerald-700' : 'text-rose-700' }}">{{ $money($profit) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Context: opening balance (NOT part of the profit calc) --}}
<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm">
    <div class="flex items-center justify-between">
        <span class="text-slate-500"><i class="bi bi-wallet2 mr-1.5"></i>Opening Balance <span class="text-slate-400">(starting cash — context only, excluded from profit)</span></span>
        <span class="font-semibold text-slate-700">{{ $money($summary['openingBalance']) }}</span>
    </div>
</div>

<p class="mt-4 text-center text-[0.7rem] text-slate-400">
    Cash basis: revenue is money actually collected; agent repayments (credits) and unpaid dues are not counted as income.
</p>
@endsection
