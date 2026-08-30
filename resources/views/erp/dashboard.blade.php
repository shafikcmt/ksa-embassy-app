@extends('layouts.erp-app')

@section('title', 'ERP Dashboard')
@section('page-title', 'ERP Dashboard')

@section('content')
    @php
        $money = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $openingBalance = $settings?->opening_balance ?? 0;

        // Placeholder KPI groups mirroring the target ERP dashboard layout. Values
        // are intentionally "—" until the operational trackers/ledgers land (E1+).
        $yearly = [
            ['label' => 'Total MOFA',     'icon' => 'bi-file-earmark-text', 'tone' => 'text-indigo-600 bg-indigo-50'],
            ['label' => 'Total Delivery', 'icon' => 'bi-truck',             'tone' => 'text-emerald-600 bg-emerald-50'],
            ['label' => 'Total Stamping', 'icon' => 'bi-stamp',             'tone' => 'text-amber-600 bg-amber-50'],
            ['label' => 'Total Expense',  'icon' => 'bi-cash-coin',         'tone' => 'text-rose-600 bg-rose-50'],
        ];
    @endphp

    {{-- Intro / status --}}
    <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Accounting &amp; Operations</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                The ERP suite is being rolled out in safe, ordered phases. Configuration is ready — operational
                trackers and financial ledgers arrive next.
            </p>
        </div>
        <a href="{{ route('erp.settings') }}"
           class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
            <i class="bi bi-sliders"></i> ERP Settings
        </a>
    </div>

    {{-- Opening balance (the one configured value we can show today) --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Opening Balance</span>
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-teal-50 text-teal-600"><i class="bi bi-wallet2"></i></span>
            </div>
            <div class="mt-3 text-2xl font-bold text-slate-900">{{ $money($openingBalance) }}</div>
            <div class="mt-1 text-xs text-slate-400">{{ $settings?->opening_balance_note ?: 'Set in ERP Settings' }}</div>
        </div>

        @foreach($yearly as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $card['label'] }}</span>
                    <span class="grid h-9 w-9 place-items-center rounded-lg {{ $card['tone'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                </div>
                <div class="mt-3 text-2xl font-bold text-slate-300">—</div>
                <div class="mt-1 text-xs text-slate-400">Available after data entry</div>
            </div>
        @endforeach
    </div>

    {{-- Placeholder chart / breakdown region --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-bold text-slate-900">Income vs Expense</h3>
            <div class="mt-4 grid h-48 place-items-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-center">
                <div>
                    <i class="bi bi-graph-up mb-2 block text-2xl text-slate-300"></i>
                    <p class="text-sm font-medium text-slate-500">Charts appear once ledgers are active</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="text-sm font-bold text-slate-900">This Month</h3>
            <div class="mt-4 grid h-48 place-items-center rounded-xl border border-dashed border-slate-200 bg-slate-50 text-center">
                <div>
                    <i class="bi bi-calendar3 mb-2 block text-2xl text-slate-300"></i>
                    <p class="text-sm font-medium text-slate-500">Monthly summary appears here</p>
                </div>
            </div>
        </div>
    </div>
@endsection
