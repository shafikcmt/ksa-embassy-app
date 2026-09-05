@extends('layouts.erp-app')

@section('title', 'Reports')
@section('page-title', 'Reports')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $money = fn ($v) => '৳' . number_format((float) $v, 2);
    $isAdmin = auth()->user()->isAgencyAdmin();
    $rangeLabel = ($filters['from'] ?: 'beginning') . ' → ' . ($filters['to'] ?: 'today');
    $srcChip = [
        'delivery'    => 'bg-sky-100 text-sky-700',
        'double_mofa' => 'bg-violet-100 text-violet-700',
    ];
    // Preserve current filters on export links.
    $exportQuery = array_filter([
        'from' => $filters['from'], 'to' => $filters['to'], 'q' => $filters['q'] ?: null,
    ]);
@endphp

<x-ui.page-header title="Reports" subtitle="Financial summary & exports" icon="bi-bar-chart" />

{{-- Filters + export --}}
<form method="GET" action="{{ route('erp.reports') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div><label class="{{ $lbl }}">From</label><input type="date" name="from" value="{{ $filters['from'] }}" class="{{ $inp }}"></div>
        <div><label class="{{ $lbl }}">To</label><input type="date" name="to" value="{{ $filters['to'] }}" class="{{ $inp }}"></div>
        <div><label class="{{ $lbl }}">Search</label><input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Name or passport (dues)" class="{{ $inp }}"></div>
        <div class="flex items-end gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('erp.reports') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
        </div>
    </div>

    @if($isAdmin)
        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Export</span>
            <a href="{{ route('erp.reports.export.pdf', $exportQuery) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
            <a href="{{ route('erp.reports.export.csv', $exportQuery) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><i class="bi bi-filetype-csv"></i> CSV</a>
            <span class="text-xs text-slate-400">Range: {{ $rangeLabel }}</span>
        </div>
    @else
        <div class="mt-4 border-t border-slate-100 pt-4 text-xs text-slate-400">
            <i class="bi bi-lock"></i> Exports are available to agency admins.
        </div>
    @endif
</form>

{{-- Summary cards --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Collected (in range)</div>
        <div class="mt-1 text-xl font-bold text-emerald-700">{{ $money($collectedInRange) }}</div>
        <div class="mt-0.5 text-[0.7rem] text-emerald-600/70">All-time: {{ $money($summary['collected']) }}</div>
    </div>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Outstanding Due</div>
        <div class="mt-1 text-xl font-bold text-rose-700">{{ $money($summary['outstandingDue']) }}</div>
        <div class="mt-0.5 text-[0.7rem] text-rose-600/70">{{ $dues['count'] }} item(s)</div>
    </div>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-amber-600">Expenses (in range)</div>
        <div class="mt-1 text-xl font-bold text-amber-700">{{ $money($expenses['rangeTotal']) }}</div>
        <div class="mt-0.5 text-[0.7rem] text-amber-600/70">All-time: {{ $money($expenses['allTime']) }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Agent Net</div>
        <div class="mt-1 text-xl font-bold text-slate-900">{{ $money($summary['agentNet']) }}</div>
        <div class="mt-0.5 text-[0.7rem] text-slate-400">Recv {{ $money($summary['agentReceivable']) }} · Pay {{ $money($summary['agentPayable']) }}</div>
    </div>
</div>

{{-- Outstanding dues detail --}}
<div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="text-sm font-bold text-slate-900">Outstanding Dues</h3>
        <span class="text-xs text-slate-400">{{ $dues['count'] }} item(s) · {{ $money($dues['combinedDue']) }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Full Name</th>
                    <th class="px-4 py-3">Passport</th>
                    <th class="px-4 py-3 text-right">Billed</th>
                    <th class="px-4 py-3 text-right">Paid</th>
                    <th class="px-4 py-3 text-right">Due</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($dues['rows'] as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $srcChip[$r['source']] ?? 'bg-slate-100 text-slate-600' }}">{{ $r['source_label'] }}</span></td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $r['date'] }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $r['full_name'] }}</td>
                        <td class="px-4 py-3">{{ $r['passport_no'] }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">{{ $money($r['billed']) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap text-emerald-700">{{ $money($r['paid']) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-600">{{ $money($r['due']) }}</td>
                        <td class="px-4 py-3">{{ $r['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">No outstanding dues for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Expenses detail (in range) --}}
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h3 class="text-sm font-bold text-slate-900">Expenses <span class="font-normal text-slate-400">(in range)</span></h3>
        <span class="text-xs text-slate-400">{{ $expenses['rows']->count() }} item(s) · {{ $money($expenses['rangeTotal']) }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Paid Via</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                    <th class="px-4 py-3">Note</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($expenses['rows'] as $e)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $e->expense_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $e->categoryLabel() }}</td>
                        <td class="px-4 py-3">{{ $e->paidViaLabel() ?? '—' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-600">{{ $money($e->amount) }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $e->note ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No expenses for this range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
