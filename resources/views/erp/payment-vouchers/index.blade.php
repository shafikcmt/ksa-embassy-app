@extends('layouts.erp-app')

@section('title', 'Payment Vouchers')
@section('page-title', 'Payment Vouchers')

@section('content')
@php
    $inp  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl  = 'mb-1 block text-xs font-semibold text-slate-600';
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
            <a data-ajax-link data-ajax-group="erp-payment-vouchers" href="{{ route('erp.payment-vouchers.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"><i class="bi bi-x-lg"></i> Clear</a>
        @endif
        <noscript><button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"><i class="bi bi-funnel"></i> Apply</button></noscript>
    </div>
</form>
<div data-ajax-region="results" data-ajax-group="erp-payment-vouchers">

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
                <tr class="grid grid-cols-2 py-2 hover:bg-slate-50 xl:table-row xl:py-0">
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
@endsection
