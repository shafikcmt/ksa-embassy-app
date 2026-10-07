@extends('layouts.erp-app')

@section('title', 'Payment Vouchers')
@section('page-title', 'Payment Vouchers')

@section('content')
@php
    $inp  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl  = 'mb-1 block text-xs font-semibold text-slate-600';
    $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition';
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

{{-- Summary --}}
<div class="mb-5 grid gap-3 sm:grid-cols-3">
    <a href="{{ route('erp.payment-vouchers.index', ['status' => 'draft']) }}" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500"><span>Drafts</span><i class="bi bi-pencil-square"></i></div>
        <div class="mt-1 text-lg font-bold text-slate-900">{{ $draft['count'] }}</div>
        <div class="text-xs text-slate-500">{{ $draft['amount'] }} awaiting approval</div>
    </a>
    <a href="{{ route('erp.payment-vouchers.index', ['status' => 'approved']) }}" class="rounded-2xl border border-sky-200 bg-sky-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-sky-700"><span>Approved · to pay</span><i class="bi bi-patch-check"></i></div>
        <div class="mt-1 text-lg font-bold text-sky-800">{{ $approved['amount'] }}</div>
        <div class="text-xs text-sky-700/80">{{ $approved['count'] }} voucher{{ $approved['count'] === 1 ? '' : 's' }}</div>
    </a>
    <a href="{{ route('erp.payment-vouchers.index', ['status' => 'paid']) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 transition hover:shadow-sm">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-emerald-700"><span>Paid out</span><i class="bi bi-check-circle"></i></div>
        <div class="mt-1 text-lg font-bold text-emerald-800">{{ $paid['amount'] }}</div>
        <div class="text-xs text-emerald-700/80">{{ $paid['count'] }} voucher{{ $paid['count'] === 1 ? '' : 's' }}</div>
    </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('erp.payment-vouchers.index') }}" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4">
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
            <select name="sort" class="{{ $inp }}" onchange="this.form.submit()">
                @foreach($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
        @if($hasFilters)
            <a href="{{ route('erp.payment-vouchers.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:text-slate-700"><i class="bi bi-x-lg"></i> Clear</a>
        @endif
        <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"><i class="bi bi-funnel"></i> Apply</button>
    </div>
</form>

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
    <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
        @foreach($vouchers as $voucher)
            <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-brand-200 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('erp.payment-vouchers.show', $voucher) }}" class="block truncate font-mono text-sm font-bold text-slate-900 hover:text-brand-700">{{ $voucher->voucher_number }}</a>
                        <div class="mt-0.5 truncate text-sm text-slate-600"><i class="bi bi-person text-slate-400"></i> {{ $voucher->payee_name }} <span class="text-xs text-slate-400">· {{ $voucher->payeeTypeLabel() }}</span></div>
                    </div>
                    @include('erp.payment-vouchers._status', ['voucher' => $voucher])
                </div>
                <p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $voucher->description }}</p>

                <div class="mt-3 flex items-end justify-between gap-3">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                        <dt class="text-slate-400">Date</dt><dd class="font-medium text-slate-700">{{ $voucher->voucher_date->format('d M Y') }}</dd>
                        <dt class="text-slate-400">Method</dt><dd class="font-medium text-slate-700">{{ $voucher->paymentMethodLabel() }}</dd>
                        <dt class="text-slate-400">Items</dt><dd class="font-medium text-slate-700">{{ $voucher->items_count }}</dd>
                    </dl>
                    <div class="text-right">
                        <div class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-400">Total</div>
                        <div class="whitespace-nowrap text-xl font-bold text-slate-900">{{ $voucher->money($voucher->total_amount) }}</div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-slate-100 pt-3">
                    <a href="{{ route('erp.payment-vouchers.show', $voucher) }}" class="{{ $pill }} bg-slate-50 text-slate-700 ring-slate-200 hover:bg-slate-100"><i class="bi bi-eye"></i> View</a>
                    @can('update', $voucher)
                        <a href="{{ route('erp.payment-vouchers.edit', $voucher) }}" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"><i class="bi bi-pencil"></i> Edit</a>
                    @endcan
                    <a href="{{ route('erp.payment-vouchers.preview-pdf', $voucher) }}" target="_blank" class="{{ $pill }} bg-brand-50 text-brand-700 ring-brand-200 hover:bg-brand-100"><i class="bi bi-printer"></i> Print</a>
                    <a href="{{ route('erp.payment-vouchers.download-pdf', $voucher) }}" class="{{ $pill }} bg-indigo-50 text-indigo-700 ring-indigo-200 hover:bg-indigo-100"><i class="bi bi-download"></i> PDF</a>
                    @can('delete', $voucher)
                        <form method="POST" action="{{ route('erp.payment-vouchers.destroy', $voucher) }}" class="ml-auto" onsubmit="return confirm('Delete draft {{ $voucher->voucher_number }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    @elseif(in_array($voucher->status, ['paid', 'cancelled'], true))
                        <span class="ml-auto inline-flex items-center gap-1 text-xs text-slate-400"><i class="bi bi-lock-fill"></i> Locked</span>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

    @if($vouchers->hasPages())<div class="mt-6 rounded-2xl border border-slate-200 bg-white px-4 py-3">{{ $vouchers->links() }}</div>@endif
@endif
@endsection
