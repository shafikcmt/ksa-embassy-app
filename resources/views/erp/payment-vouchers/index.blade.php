@extends('layouts.erp-app')

@section('title', 'Payment Vouchers')
@section('page-title', 'Payment Vouchers')

@section('content')
@php
    $inp  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl  = 'mb-1.5 block text-sm font-semibold text-slate-700';
    $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1 text-xs font-semibold ring-1 ring-inset transition';
    $stat = fn (string $s) => [
        'count'  => (int) ($summary[$s]->cnt ?? 0),
        'amount' => 'BDT ' . number_format((float) ($summary[$s]->total ?? 0), 2),
    ];
    $draft = $stat('draft'); $approved = $stat('approved'); $paid = $stat('paid');
    $hasFilters = $filters['q'] !== '' || $filters['status'] !== '' || $filters['from'] !== '' || $filters['to'] !== '';
@endphp

<x-ui.page-header title="Payment Vouchers" subtitle="Money paid out to parties, manpower providers & vendors" icon="bi-wallet2">
    <x-slot:actions>
        @can('create', \App\Models\PaymentVoucher::class)
            <a href="{{ route('erp.payment-vouchers.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-plus-lg"></i> New Voucher
            </a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<div data-ajax-region="stats" data-ajax-group="erp-payment-vouchers">
{{-- Summary --}}
<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <a data-ajax-link data-ajax-group="erp-payment-vouchers" href="{{ route('erp.payment-vouchers.index', ['status' => 'draft']) }}" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500"><span>Drafts</span><i class="bi bi-pencil-square"></i></div>
        <div class="mt-1 text-lg font-bold text-slate-900">{{ $draft['count'] }}</div>
        <div class="text-xs text-slate-500">{{ $draft['amount'] }} awaiting approval</div>
    </a>
    <a data-ajax-link data-ajax-group="erp-payment-vouchers" href="{{ route('erp.payment-vouchers.index', ['status' => 'approved']) }}" class="rounded-2xl border border-sky-200 bg-sky-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-sky-700"><span>Approved · to pay</span><i class="bi bi-patch-check"></i></div>
        <div class="mt-1 text-lg font-bold text-sky-800">{{ $approved['amount'] }}</div>
        <div class="text-xs text-sky-700/80">{{ $approved['count'] }} voucher{{ $approved['count'] === 1 ? '' : 's' }}</div>
    </a>
    <a data-ajax-link data-ajax-group="erp-payment-vouchers" href="{{ route('erp.payment-vouchers.index', ['status' => 'paid']) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-emerald-700"><span>Paid out</span><i class="bi bi-check-circle"></i></div>
        <div class="mt-1 text-lg font-bold text-emerald-800">{{ $paid['amount'] }}</div>
        <div class="text-xs text-emerald-700/80">{{ $paid['count'] }} voucher{{ $paid['count'] === 1 ? '' : 's' }}</div>
    </a>
</div>

</div>
{{-- Selection lives OUTSIDE the AJAX region, so picked vouchers survive filtering and paging. --}}
<div x-data="{
        sel: [],
        limit: 50,
        url: @js(route('erp.payment-vouchers.print-selected')),
        pageIds() { return [...new Set([...document.querySelectorAll('input[data-voucher-check]')].map(i => Number(i.value)))]; },
        toggle(id, on) { this.sel = on ? [...new Set([...this.sel, id])] : this.sel.filter(x => x !== id); },
        togglePage(on) { const ids = this.pageIds(); this.sel = on ? [...new Set([...this.sel, ...ids])] : this.sel.filter(x => !ids.includes(x)); },
        get allOnPage() { const ids = this.pageIds(); return ids.length > 0 && ids.every(id => this.sel.includes(id)); },
        printSelected() {
            if (!this.sel.length) return;
            if (this.sel.length > this.limit) { alert('You can print up to ' + this.limit + ' vouchers at once.'); return; }
            window.open(this.url + '?ids=' + this.sel.join(','), '_blank');
        },
    }">

{{-- Filters --}}
<form data-ajax-filter data-ajax-group="erp-payment-vouchers" method="GET" action="{{ route('erp.payment-vouchers.index') }}" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <label class="{{ $lbl }}">Search</label>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Voucher no., payee, description…" class="{{ $inp }} pl-9">
            </div>
        </div>
        <div class="lg:col-span-2">
            <label class="{{ $lbl }}">Status</label>
            <select name="status" class="{{ $inp }}">
                <option value="">All</option>
                @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="lg:col-span-2"><label class="{{ $lbl }}">Month</label><input type="month" name="month" value="{{ $filters['month'] }}" title="Show one whole month (overrides From / To)" class="{{ $inp }}"></div>
        <div class="lg:col-span-2"><label class="{{ $lbl }}">From</label><input type="date" name="from" value="{{ $filters['month'] !== '' ? '' : $filters['from'] }}" class="{{ $inp }}"></div>
        <div class="lg:col-span-2"><label class="{{ $lbl }}">To</label><input type="date" name="to" value="{{ $filters['month'] !== '' ? '' : $filters['to'] }}" class="{{ $inp }}"></div>
        <div class="lg:col-span-2">
            <label class="{{ $lbl }}">Sort</label>
            <select name="sort" class="{{ $inp }}">
                @foreach($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
        @if($hasFilters)
            <a data-ajax-link data-ajax-group="erp-payment-vouchers" href="{{ route('erp.payment-vouchers.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"><i class="bi bi-x-lg"></i> Clear</a>
        @endif
        <noscript><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"><i class="bi bi-funnel"></i> Apply</button></noscript>
    </div>
</form>
<div data-ajax-region="results" data-ajax-group="erp-payment-vouchers">
    {{-- Whole-month print: every voucher of the chosen month in one go (2 per A4 page). --}}
    @if(($filters['month'] ?? '') !== '' && count($printIds))
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm">
            <span class="font-medium text-slate-700">
                <i class="bi bi-calendar-month text-brand-600"></i>
                <strong>{{ \Carbon\Carbon::createFromFormat('!Y-m', $filters['month'])->format('F Y') }}</strong>
                · {{ count($printIds) > 50 ? 'more than 50' : count($printIds) }} voucher{{ count($printIds) === 1 ? '' : 's' }}
            </span>
            @if(count($printIds) <= 50)
                <button type="button" x-on:click="window.open(url + '?ids=' + @js(implode(',', $printIds)), '_blank')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-1.5 font-semibold text-white transition hover:bg-brand-700">
                    <i class="bi bi-printer"></i> Print all {{ count($printIds) }}
                </button>
            @else
                <span class="text-xs text-slate-500">Up to 50 can be printed at once — narrow it with Search / Status, or tick rows.</span>
            @endif
        </div>
    @endif


@if($vouchers->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
        <i class="bi bi-wallet2 mb-2 block text-3xl text-slate-300"></i>
        <div class="text-sm font-semibold text-slate-700">{{ $hasFilters ? 'No vouchers match these filters.' : 'No payment vouchers yet.' }}</div>
        @if(! $hasFilters)
            @can('create', \App\Models\PaymentVoucher::class)
                <a href="{{ route('erp.payment-vouchers.create') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"><i class="bi bi-plus-lg"></i> New Voucher</a>
            @endcan
        @endif
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="block w-full xl:table">
            <caption class="sr-only">Payment vouchers</caption>
            <thead class="hidden border-b border-slate-200 bg-slate-50 xl:table-header-group">
                <tr>
                    <th scope="col" class="w-10 px-3 py-3 text-left">
                        <input type="checkbox" x-bind:checked="allOnPage" x-on:change="togglePage($event.target.checked)" aria-label="Select all vouchers on this page"
                               class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    </th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-600">Voucher No</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-600">Paid To / Recipient</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-600">Expense Head</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-600">Date</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-600">Payment Method</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-600">Status</th>
                    <th scope="col" class="px-3 py-3 text-right text-xs font-semibold text-slate-600">Amount</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-600">Actions</th>
                </tr>
            </thead>
            <tbody class="block divide-y divide-slate-200 xl:table-row-group">
            @foreach($vouchers as $voucher)
                <tr class="grid grid-cols-2 py-2 hover:bg-slate-50 xl:table-row xl:py-0" x-bind:class="sel.includes({{ $voucher->id }}) ? '!bg-brand-50/60' : ''">
                <td class="col-span-2 block px-3 py-2 align-top xl:table-cell xl:w-10 xl:py-3">
                    <label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-500">
                        <input type="checkbox" data-voucher-check value="{{ $voucher->id }}" x-bind:checked="sel.includes({{ $voucher->id }})" x-on:change="toggle({{ $voucher->id }}, $event.target.checked)"
                               aria-label="Select {{ $voucher->voucher_number }}" class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span class="xl:hidden">Select</span>
                    </label>
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Voucher No</span>
                    <a href="{{ route('erp.payment-vouchers.show', $voucher) }}" class="whitespace-nowrap font-mono font-bold text-slate-900 hover:text-brand-700">{{ $voucher->voucher_number }}</a>
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Paid To / Recipient</span>
                    <div class="font-medium text-slate-900">{{ $voucher->payee_name }}</div><div class="mt-0.5 text-xs text-slate-500">{{ $voucher->payeeTypeLabel() }}</div>
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Expense Head</span>
                    {{ $voucher->expenseHeadLabel() }}
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Date</span>
                    <span class="whitespace-nowrap">{{ $voucher->voucher_date->format('d M Y') }}</span>
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Payment Method</span>
                    {{ $voucher->paymentMethodLabel() }}
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Status</span>
                    @include('erp.payment-vouchers._status', ['voucher' => $voucher])
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3 text-right">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Amount</span>
                    <span class="whitespace-nowrap font-semibold tabular-nums text-slate-900">{{ $voucher->money($voucher->total_amount) }}</span>
                </td>
                <td class="block min-w-0 break-words px-3 py-2 align-top text-sm text-slate-600 xl:table-cell xl:py-3 col-span-2">
                    <span class="mb-1 block text-xs font-semibold text-slate-500 xl:hidden">Actions</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('erp.payment-vouchers.show', $voucher) }}" class="{{ $pill }} bg-slate-50 text-slate-700 ring-slate-200 hover:bg-slate-100"><i class="bi bi-eye"></i> View</a>
                    @can('update', $voucher)
                        <a href="{{ route('erp.payment-vouchers.edit', $voucher) }}" title="Edit" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</a>
                    @endcan
                    <a href="{{ route('erp.payment-vouchers.preview-pdf', $voucher) }}" target="_blank" class="{{ $pill }} bg-brand-50 text-brand-700 ring-brand-200 hover:bg-brand-100"><i class="bi bi-printer"></i> Print</a>
                    <a href="{{ route('erp.payment-vouchers.download-pdf', $voucher) }}" class="{{ $pill }} bg-indigo-50 text-indigo-700 ring-indigo-200 hover:bg-indigo-100"><i class="bi bi-download"></i> PDF</a>
                    @can('delete', $voucher)
                        <form method="POST" action="{{ route('erp.payment-vouchers.destroy', $voucher) }}" onsubmit="return confirm('Delete draft {{ $voucher->voucher_number }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash" aria-hidden="true"></i> Delete</button>
                        </form>
                    @elseif(in_array($voucher->status, ['paid', 'cancelled'], true))
                        <span class="inline-flex items-center gap-1 text-xs text-slate-400"><i class="bi bi-lock-fill"></i> Locked</span>
                    @endcan
                    </div>
                </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($vouchers->hasPages())<div class="mt-6 rounded-2xl border border-slate-200 bg-white px-4 py-3">{{ $vouchers->links() }}</div>@endif
@endif
</div>

{{-- Floating bar: appears once something is ticked --}}
<div x-show="sel.length" x-cloak x-transition
     class="fixed inset-x-0 bottom-4 z-40 mx-auto flex w-max max-w-[calc(100%-2rem)] items-center gap-3 rounded-2xl bg-slate-900 px-4 py-3 text-sm text-white shadow-2xl">
    <span><strong x-text="sel.length"></strong> selected</span>
    <button type="button" x-on:click="printSelected()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3.5 py-1.5 font-semibold text-white transition hover:bg-brand-500">
        <i class="bi bi-printer"></i> Print selected
        <span class="hidden text-xs font-normal text-brand-100 sm:inline">· 2 per A4 page</span>
    </button>
    <button type="button" x-on:click="sel = []" class="rounded-lg px-2 py-1.5 font-semibold text-slate-300 transition hover:text-white">Clear</button>
</div>

</div>
@endsection
