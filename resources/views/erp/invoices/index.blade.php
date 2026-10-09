@extends('layouts.erp-app')

@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('content')
@php
    $inp  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl  = 'mb-1 block text-xs font-semibold text-slate-600';

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
    $hasFilters = $filters['q'] !== '' || $filters['status'] !== '' || $filters['agent'] > 0 || $filters['from'] !== '' || $filters['to'] !== '';
@endphp

<x-ui.page-header title="Invoices" subtitle="Bill passengers & agents with multi-line invoices" icon="bi-receipt">
        <x-slot:actions>
            <a href="{{ route('erp.invoices.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-plus-lg"></i> New Invoice
            </a>
        </x-slot:actions>
</x-ui.page-header>

<div data-ajax-region="stats" data-ajax-group="erp-invoices">
{{-- Summary --}}
<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <a data-ajax-link data-ajax-group="erp-invoices" href="{{ route('erp.invoices.index', ['status' => 'pending']) }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-amber-700"><span>Outstanding</span><i class="bi bi-hourglass-split"></i></div>
        <div class="mt-1 text-lg font-bold text-amber-800">{{ $pending['amount'] }}</div>
        <div class="text-xs text-amber-700/80">{{ $pending['count'] }} pending invoice{{ $pending['count'] === 1 ? '' : 's' }}</div>
    </a>
    <a data-ajax-link data-ajax-group="erp-invoices" href="{{ route('erp.invoices.index', ['status' => 'paid']) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-emerald-700"><span>Paid</span><i class="bi bi-check-circle"></i></div>
        <div class="mt-1 text-lg font-bold text-emerald-800">{{ $paid['amount'] }}</div>
        <div class="text-xs text-emerald-700/80">{{ $paid['count'] }} paid invoice{{ $paid['count'] === 1 ? '' : 's' }}</div>
    </a>
    <a data-ajax-link data-ajax-group="erp-invoices" href="{{ route('erp.invoices.index', ['status' => 'draft']) }}" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500"><span>Drafts</span><i class="bi bi-pencil-square"></i></div>
        <div class="mt-1 text-lg font-bold text-slate-900">{{ $draft['count'] }}</div>
        <div class="text-xs text-slate-500">{{ $draft['amount'] }}</div>
    </a>
</div>

</div>
{{-- Filters (server-side, agency-scoped) --}}
<form data-ajax-filter data-ajax-group="erp-invoices" method="GET" action="{{ route('erp.invoices.index') }}" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-2">
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
                @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="{{ $lbl }}">Agent</label>
            <select name="agent_id" class="{{ $inp }}">
                <option value="">All agents</option>
                @foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($filters['agent'] === $agent->id)>{{ $agent->name }}</option>@endforeach
            </select>
        </div>
        <div class="lg:col-span-2"><label class="{{ $lbl }}">From</label><input type="date" name="from" value="{{ $filters['from'] }}" class="{{ $inp }}"></div>
        <div class="lg:col-span-2"><label class="{{ $lbl }}">To</label><input type="date" name="to" value="{{ $filters['to'] }}" class="{{ $inp }}"></div>
        <div class="lg:col-span-2">
            <label class="{{ $lbl }}">Sort</label>
            <select name="sort" class="{{ $inp }}">
                @foreach($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
        @if($hasFilters)
            <a data-ajax-link data-ajax-group="erp-invoices" href="{{ route('erp.invoices.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"><i class="bi bi-x-lg"></i> Clear</a>
        @endif
        <noscript><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"><i class="bi bi-funnel"></i> Apply</button></noscript>
    </div>
</form>
<div data-ajax-region="results" data-ajax-group="erp-invoices">

{{-- Invoice list --}}
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
    @php $th = 'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500'; @endphp
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- Desktop / tablet: table --}}
        <table class="hidden w-full text-sm md:table">
            <thead class="border-b border-slate-200 bg-slate-50/80">
                <tr>
                    <th class="{{ $th }} rounded-tl-2xl">Invoice No</th>
                    <th class="{{ $th }}">Bill To</th>
                    <th class="{{ $th }}">Date</th>
                    <th class="{{ $th }} text-center">Items</th>
                    <th class="{{ $th }} !text-right">Total</th>
                    <th class="{{ $th }}">Status</th>
                    <th class="{{ $th }} rounded-tr-2xl !text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($invoices as $invoice)
                    <tr class="transition hover:bg-slate-50/70">
                        <td class="whitespace-nowrap px-4 py-3">
                            <a href="{{ route('erp.invoices.show', $invoice) }}" class="font-mono text-sm font-bold text-slate-900 hover:text-brand-700">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="max-w-[16rem] px-4 py-3">
                            <div class="truncate text-slate-700" title="{{ $invoice->billToLabel() }}">{{ $invoice->billToLabel() }}</div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $invoice->invoice_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $invoice->items_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">{{ $invoice->money($invoice->total_amount) }}</td>
                        <td class="px-4 py-3">@include('erp.invoices._status', ['invoice' => $invoice])</td>
                        <td class="px-4 py-3">@include('erp.invoices._row_actions', ['invoice' => $invoice])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Mobile: compact stacked rows --}}
        <ul class="divide-y divide-slate-100 md:hidden">
            @foreach($invoices as $invoice)
                <li class="px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('erp.invoices.show', $invoice) }}" class="block truncate font-mono text-sm font-bold text-slate-900 hover:text-brand-700">{{ $invoice->invoice_number }}</a>
                            <div class="truncate text-sm text-slate-600">{{ $invoice->billToLabel() }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="whitespace-nowrap text-sm font-bold text-slate-900">{{ $invoice->money($invoice->total_amount) }}</div>
                            <div class="mt-1">@include('erp.invoices._status', ['invoice' => $invoice])</div>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <div class="text-xs text-slate-500">{{ $invoice->invoice_date->format('d M Y') }} · {{ $invoice->items_count }} item{{ $invoice->items_count === 1 ? '' : 's' }}</div>
                        @include('erp.invoices._row_actions', ['invoice' => $invoice])
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    @if($invoices->hasPages())<div class="mt-6 rounded-2xl border border-slate-200 bg-white px-4 py-3">{{ $invoices->links() }}</div>@endif

@endif
</div>
@include('erp.invoices._delete_modal')
@endsection
