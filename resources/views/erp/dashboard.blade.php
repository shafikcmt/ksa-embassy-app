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

    {{-- Passenger status — links to the EXISTING search on the main dashboard --}}
    <a href="{{ route('dashboard') }}#passenger-status"
       class="mb-6 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-emerald-300 hover:shadow-sm">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600"><i class="bi bi-person-vcard"></i></span>
            <div>
                <div class="text-sm font-semibold text-slate-800">Passenger Status</div>
                <div class="text-xs text-slate-500">Search a candidate by name, passport, visa or MOFA on the main dashboard.</div>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700"><i class="bi bi-search"></i> Open Search</span>
    </a>

    {{-- Quick-view cards (operational snapshot) --}}
    @php
        $quick = [
            ['label' => 'Pending Delivery', 'value' => number_format($summary['pendingDelivery']), 'icon' => 'bi-truck',          'tone' => 'text-sky-600 bg-sky-50',        'money' => false],
            ['label' => 'Income (collected)','value' => $money($summary['collected']),             'icon' => 'bi-arrow-down-circle','tone' => 'text-emerald-600 bg-emerald-50', 'money' => true],
            ['label' => 'Expense (all-time)','value' => $money($summary['expenseAllTime']),         'icon' => 'bi-arrow-up-circle',  'tone' => 'text-amber-600 bg-amber-50',    'money' => true],
            ['label' => 'Total Due',         'value' => $money($summary['outstandingDue']),         'icon' => 'bi-hourglass-split',  'tone' => 'text-rose-600 bg-rose-50',      'money' => true],
        ];
    @endphp
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($quick as $c)
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $c['tone'] }} text-lg"><i class="bi {{ $c['icon'] }}"></i></span>
                <div class="min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $c['label'] }}</div>
                    <div class="mt-0.5 truncate text-lg font-bold text-slate-900">{{ $c['value'] }}</div>
                </div>
            </div>
        @endforeach
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

    {{-- Yearly summary strip (operational counts + expense) with year selector --}}
    @php
        $yearTiles = [
            ['label' => 'Total MOFA',     'value' => number_format($yearly['mofa']),     'icon' => 'bi-file-earmark-text', 'tone' => 'text-indigo-600'],
            ['label' => 'Total Delivery', 'value' => number_format($yearly['delivery']), 'icon' => 'bi-truck',             'tone' => 'text-sky-600'],
            ['label' => 'Total Stamping', 'value' => number_format($yearly['stamping']), 'icon' => 'bi-stamp',             'tone' => 'text-amber-600'],
            ['label' => 'Total Expense',  'value' => $money($yearly['expense']),         'icon' => 'bi-cash-coin',         'tone' => 'text-rose-600'],
        ];
    @endphp
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-sm font-bold text-slate-900"><i class="bi bi-calendar3 mr-1 text-slate-400"></i>Yearly Summary — {{ $yearly['year'] }}</h3>
            <form method="GET" action="{{ route('erp.dashboard') }}" class="flex items-center gap-2">
                <label class="text-xs font-semibold text-slate-500">Year</label>
                <select name="year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach($yearOptions as $y)
                        <option value="{{ $y }}" @selected($y === $yearly['year'])>{{ $y }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">Go</button></noscript>
            </form>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($yearTiles as $t)
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $t['label'] }}</span>
                        <i class="bi {{ $t['icon'] }} {{ $t['tone'] }}"></i>
                    </div>
                    <div class="mt-2 text-xl font-bold text-slate-900">{{ $t['value'] }}</div>
                </div>
            @endforeach
        </div>
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

    {{-- Smart Notes widget (Today / Pinned / Reminders) — reuses SmartNote, honours is_private --}}
    @php
        $noteCols = [
            ['key' => 'today',     'label' => 'Today',     'icon' => 'bi-calendar-day', 'tone' => 'text-sky-500',     'empty' => 'No notes today.'],
            ['key' => 'pinned',    'label' => 'Pinned',    'icon' => 'bi-pin-angle',    'tone' => 'text-amber-500',   'empty' => 'No pinned notes.'],
            ['key' => 'reminders', 'label' => 'Reminders', 'icon' => 'bi-alarm',        'tone' => 'text-rose-500',    'empty' => 'No upcoming reminders.'],
        ];
    @endphp
    <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900"><i class="bi bi-journal-text mr-1 text-slate-400"></i>Smart Notes</h3>
            <a href="{{ route('notes.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Open Notes →</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach($noteCols as $col)
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <div class="mb-2 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <i class="bi {{ $col['icon'] }} {{ $col['tone'] }}"></i> {{ $col['label'] }}
                    </div>
                    <ul class="space-y-2 text-sm">
                        @forelse($notesWidget[$col['key']] as $note)
                            <li class="flex items-start gap-2">
                                <i class="bi bi-dot text-slate-300"></i>
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-slate-800">{{ $note->title ?: \Illuminate\Support\Str::limit(strip_tags($note->body), 40) ?: 'Untitled' }}</div>
                                    @if($col['key'] === 'reminders' && $note->reminder_at)
                                        <div class="text-[0.7rem] text-rose-500">{{ $note->reminder_at->format('d M, h:i A') }}</div>
                                    @else
                                        <div class="text-[0.7rem] text-slate-400">{{ $note->categoryLabel() }}</div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="py-3 text-center text-xs text-slate-400">{{ $col['empty'] }}</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
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
