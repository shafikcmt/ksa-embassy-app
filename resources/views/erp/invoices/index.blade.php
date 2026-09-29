@extends('layouts.erp-app')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
@php
    $inp  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl  = 'mb-1 block text-xs font-semibold text-slate-600';
    $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition';
    $isAdmin = auth()->user()->isAgencyAdmin();

    // Summary per status → "৳ 1,200.00 · SAR 300.00" (currencies never added together).
    $symbols = \App\Models\Invoice::CURRENCIES;
    $sumFor = function (string $status) use ($summary, $symbols) {
        $rows = $summary->where('status', $status);
        if ($rows->isEmpty()) return ['count' => 0, 'amount' => '—'];
        return [
            'count'  => (int) $rows->sum('cnt'),
            'amount' => $rows->map(fn ($r) => ($symbols[$r->currency] ?? $r->currency) . ' ' . number_format((float) $r->total, 2))->implode(' · '),
        ];
    };
    $pending = $sumFor('pending');
    $paid    = $sumFor('paid');
    $draft   = $sumFor('draft');
    $hasFilters = $filters['q'] !== '' || $filters['status'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';
@endphp

<x-ui.page-header title="Invoices" subtitle="Bill passengers & agents with multi-line invoices" icon="bi-receipt">
        <x-slot:actions>
            <a href="{{ route('erp.invoices.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-plus-lg"></i> New Invoice
            </a>
        </x-slot:actions>
</x-ui.page-header>

{{-- Summary --}}
<div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('erp.invoices.index', ['status' => 'pending']) }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-amber-700"><span>Outstanding</span><i class="bi bi-hourglass-split"></i></div>
        <div class="mt-1 text-lg font-bold text-amber-800">{{ $pending['amount'] }}</div>
        <div class="text-xs text-amber-700/80">{{ $pending['count'] }} pending invoice{{ $pending['count'] === 1 ? '' : 's' }}</div>
    </a>
    <a href="{{ route('erp.invoices.index', ['status' => 'paid']) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-emerald-700"><span>Paid</span><i class="bi bi-check-circle"></i></div>
        <div class="mt-1 text-lg font-bold text-emerald-800">{{ $paid['amount'] }}</div>
        <div class="text-xs text-emerald-700/80">{{ $paid['count'] }} paid invoice{{ $paid['count'] === 1 ? '' : 's' }}</div>
    </a>
    <a href="{{ route('erp.invoices.index', ['status' => 'draft']) }}" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500"><span>Drafts</span><i class="bi bi-pencil-square"></i></div>
        <div class="mt-1 text-lg font-bold text-slate-900">{{ $draft['count'] }}</div>
        <div class="text-xs text-slate-500">{{ $draft['amount'] }}</div>
    </a>
    <a href="{{ route('erp.invoices.index', ['status' => 'overdue']) }}" class="rounded-2xl border {{ $overdueCount ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white' }} p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide {{ $overdueCount ? 'text-rose-700' : 'text-slate-500' }}"><span>Overdue</span><i class="bi bi-alarm"></i></div>
        <div class="mt-1 text-lg font-bold {{ $overdueCount ? 'text-rose-700' : 'text-slate-900' }}">{{ $overdueCount }}</div>
        <div class="text-xs {{ $overdueCount ? 'text-rose-600' : 'text-slate-500' }}">Pending past due date</div>
    </a>
</div>

{{-- Filters (server-side, agency-scoped) --}}
<form method="GET" action="{{ route('erp.invoices.index') }}" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <label class="{{ $lbl }}">Search</label>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Invoice no., bill-to, passenger, item…" class="{{ $inp }} pl-9">
            </div>
        </div>
        <div class="lg:col-span-2">
            <label class="{{ $lbl }}">Status</label>
            <select name="status" class="{{ $inp }}">
                <option value="">All</option>
                @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach
                <option value="overdue" @selected($filters['status'] === 'overdue')>Overdue</option>
            </select>
        </div>
        <div class="lg:col-span-2"><label class="{{ $lbl }}">From</label><input type="date" name="from" value="{{ $filters['from'] }}" class="{{ $inp }}"></div>
        <div class="lg:col-span-2"><label class="{{ $lbl }}">To</label><input type="date" name="to" value="{{ $filters['to'] }}" class="{{ $inp }}"></div>
        <div class="lg:col-span-2">
            <label class="{{ $lbl }}">Sort</label>
            <select name="sort" class="{{ $inp }}" onchange="this.form.submit()">
                @foreach($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
        @if($hasFilters)
            <a href="{{ route('erp.invoices.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"><i class="bi bi-x-lg"></i> Clear</a>
        @endif
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"><i class="bi bi-funnel"></i> Apply</button>
    </div>
</form>

{{-- Invoice cards --}}
@if($invoices->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
        <i class="bi bi-receipt mb-2 block text-3xl text-slate-300"></i>
        <div class="text-sm font-semibold text-slate-700">{{ $hasFilters ? 'No invoices match these filters.' : 'No invoices yet.' }}</div>
        @unless($hasFilters)
            <p class="mt-1 text-sm text-slate-500">Create your first invoice to bill a passenger or agent.</p>
            <a href="{{ route('erp.invoices.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"><i class="bi bi-plus-lg"></i> New Invoice</a>
        @endunless
    </div>
@else
    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @foreach($invoices as $invoice)
            <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand-200 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('erp.invoices.show', $invoice) }}" class="block truncate font-mono text-sm font-bold text-slate-900 hover:text-brand-700">{{ $invoice->invoice_number }}</a>
                        <div class="mt-0.5 truncate text-sm text-slate-600"><i class="bi bi-person text-slate-400"></i> {{ $invoice->billToLabel() }}</div>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">@include('erp.invoices._status', ['invoice' => $invoice])</div>
                </div>

                <div class="mt-4 flex items-end justify-between gap-3">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                        <dt class="text-slate-400">Date</dt><dd class="font-medium text-slate-700">{{ $invoice->invoice_date->format('d M Y') }}</dd>
                        <dt class="text-slate-400">Due</dt><dd class="font-medium {{ $invoice->isOverdue() ? 'text-rose-600' : 'text-slate-700' }}">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="text-slate-400">Items</dt><dd class="font-medium text-slate-700">{{ $invoice->items_count }}</dd>
                    </dl>
                    <div class="text-right">
                        <div class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Total</div>
                        <div class="whitespace-nowrap text-xl font-bold text-slate-900">{{ $invoice->money($invoice->total_amount) }}</div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-slate-100 pt-3">
                    <a href="{{ route('erp.invoices.show', $invoice) }}" class="{{ $pill }} bg-slate-50 text-slate-700 ring-slate-200 hover:bg-slate-100"><i class="bi bi-eye"></i> View</a>
                    @if($invoice->isEditable())
                        <a href="{{ route('erp.invoices.edit', $invoice) }}" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"><i class="bi bi-pencil"></i> Edit</a>
                    @endif
                    <a href="{{ route('erp.invoices.preview-pdf', $invoice) }}" target="_blank" class="{{ $pill }} bg-brand-50 text-brand-700 ring-brand-200 hover:bg-brand-100"><i class="bi bi-printer"></i> Print</a>
                    <a href="{{ route('erp.invoices.download-pdf', $invoice) }}" class="{{ $pill }} bg-indigo-50 text-indigo-700 ring-indigo-200 hover:bg-indigo-100"><i class="bi bi-download"></i> PDF</a>
                    @if($isAdmin && $invoice->isDeletable())
                        <form method="POST" action="{{ route('erp.invoices.destroy', $invoice) }}" class="ml-auto" onsubmit="return confirm('Delete draft {{ $invoice->invoice_number }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    @elseif($invoice->isLocked())
                        <span class="ml-auto inline-flex items-center gap-1 text-xs text-slate-400" title="Paid/cancelled invoices are locked"><i class="bi bi-lock-fill"></i> Locked</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $invoices->links() }}</div>
@endif
@endsection
