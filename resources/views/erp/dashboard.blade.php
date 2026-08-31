@extends('layouts.erp-app')

@section('title', 'ERP Dashboard')
@section('page-title', 'ERP Dashboard')

@section('content')
    @php
        $money = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $openingBalance = $settings?->opening_balance ?? 0;
        $categoryLabels = \App\Models\Expense::CATEGORIES;

        // Primary KPI cards — every value is a read-only aggregate from
        // ErpReportService (verified E1–E3 sources). No math in the view.
        $cards = [
            ['label' => 'Total Collected', 'value' => $summary['collected'],       'icon' => 'bi-cash-stack',      'tone' => 'text-emerald-600 bg-emerald-50', 'sub' => 'All-time money in'],
            ['label' => 'Outstanding Due',  'value' => $summary['outstandingDue'],  'icon' => 'bi-hourglass-split', 'tone' => 'text-rose-600 bg-rose-50',       'sub' => 'Delivery + Double MOFA'],
            ['label' => 'Total Billed',     'value' => $summary['totalBilled'],     'icon' => 'bi-receipt',         'tone' => 'text-indigo-600 bg-indigo-50',   'sub' => 'Billed across modules'],
            ['label' => 'Expenses (Month)', 'value' => $summary['expenseMonth'],    'icon' => 'bi-cash-coin',       'tone' => 'text-amber-600 bg-amber-50',     'sub' => 'This calendar month'],
        ];
    @endphp

    {{-- Intro / status --}}
    <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Accounting &amp; Operations</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                Live overview of collections, dues, expenses and agent balances — all read-only, drawn from the
                same verified records as each module screen.
            </p>
        </div>
        <div class="flex shrink-0 gap-2">
            <a href="{{ route('erp.reports') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="bi bi-bar-chart"></i> Reports
            </a>
            <a href="{{ route('erp.settings') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-sliders"></i> ERP Settings
            </a>
        </div>
    </div>

    {{-- Primary KPI cards --}}
    <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($cards as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $card['label'] }}</span>
                    <span class="grid h-9 w-9 place-items-center rounded-lg {{ $card['tone'] }}"><i class="bi {{ $card['icon'] }}"></i></span>
                </div>
                <div class="mt-3 text-2xl font-bold text-slate-900">{{ $money($card['value']) }}</div>
                <div class="mt-1 text-xs text-slate-400">{{ $card['sub'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Secondary strip: agent balances + opening balance --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Agent Receivable</div>
            <div class="mt-1 text-xl font-bold text-emerald-700">{{ $money($summary['agentReceivable']) }}</div>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Agent Payable</div>
            <div class="mt-1 text-xl font-bold text-rose-700">{{ $money($summary['agentPayable']) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Agent Net</div>
            <div class="mt-1 text-xl font-bold text-slate-900">{{ $money($summary['agentNet']) }}</div>
        </div>
        <div class="rounded-2xl border border-teal-200 bg-teal-50 p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-teal-600">Opening Balance</span>
                <i class="bi bi-wallet2 text-teal-500"></i>
            </div>
            <div class="mt-1 text-xl font-bold text-teal-700">{{ $money($openingBalance) }}</div>
            <div class="mt-0.5 text-[0.7rem] text-teal-600/70">{{ $settings?->opening_balance_note ?: 'Set in ERP Settings' }}</div>
        </div>
    </div>

    {{-- Recent activity + expense breakdown --}}
    <div class="grid gap-4 lg:grid-cols-3">
        {{-- Recent payments --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Recent Payments</h3>
                <i class="bi bi-cash-coin text-emerald-500"></i>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($recentPayments as $p)
                    <li class="flex items-center justify-between py-2.5">
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-800">{{ $p->payable->full_name ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ optional($p->received_at)->format('d M Y') }} · {{ $p->isReversal() ? 'Reversal' : 'Payment' }}</div>
                        </div>
                        <span class="shrink-0 font-semibold {{ $p->isReversal() ? 'text-rose-600' : 'text-emerald-700' }}">
                            {{ $p->isReversal() ? '−' : '+' }}৳{{ number_format((float) $p->amount, 2) }}
                        </span>
                    </li>
                @empty
                    <li class="py-8 text-center text-slate-400">No payments yet.</li>
                @endforelse
            </ul>
        </div>

        {{-- Recent expenses --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Recent Expenses</h3>
                <i class="bi bi-receipt-cutoff text-amber-500"></i>
            </div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse($recentExpenses as $e)
                    <li class="flex items-center justify-between py-2.5">
                        <div class="min-w-0">
                            <div class="truncate font-medium text-slate-800">{{ $e->categoryLabel() }}</div>
                            <div class="text-xs text-slate-400">{{ $e->expense_date->format('d M Y') }}{{ $e->paidViaLabel() ? ' · ' . $e->paidViaLabel() : '' }}</div>
                        </div>
                        <span class="shrink-0 font-semibold text-rose-600">−৳{{ number_format((float) $e->amount, 2) }}</span>
                    </li>
                @empty
                    <li class="py-8 text-center text-slate-400">No expenses yet.</li>
                @endforelse
            </ul>
        </div>

        {{-- Expense by category (all-time) --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Expenses by Category</h3>
                <i class="bi bi-pie-chart text-indigo-500"></i>
            </div>
            @php $catTotal = (float) $byCategory->sum(); @endphp
            <ul class="space-y-2.5 text-sm">
                @forelse($byCategory->take(6) as $key => $amount)
                    @php $pct = $catTotal > 0 ? round($amount / $catTotal * 100) : 0; @endphp
                    <li>
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-slate-700">{{ $categoryLabels[$key] ?? ucfirst($key) }}</span>
                            <span class="font-semibold text-slate-800">৳{{ number_format($amount, 2) }}</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-400 to-violet-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-slate-400">No expenses to break down.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Recent agent transactions --}}
    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Recent Agent Khata Activity</h3>
            <a href="{{ route('erp.agent-khata') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-2 pr-4">Date</th>
                        <th class="py-2 pr-4">Agent</th>
                        <th class="py-2 pr-4">Type</th>
                        <th class="py-2 pr-4 text-right">Amount</th>
                        <th class="py-2">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentAgentTxns as $t)
                        <tr>
                            <td class="py-2.5 pr-4 whitespace-nowrap text-slate-500">{{ $t->txn_date->format('d M Y') }}</td>
                            <td class="py-2.5 pr-4 font-medium text-slate-800">{{ $t->agent->name ?? '—' }}</td>
                            <td class="py-2.5 pr-4">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $t->type === 'debit' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ ucfirst($t->type) }}{{ $t->isReversal() ? ' (rev)' : '' }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-4 text-right font-semibold text-slate-800">৳{{ number_format((float) $t->amount, 2) }}</td>
                            <td class="py-2.5 truncate text-slate-500">{{ $t->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-400">No agent activity yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
