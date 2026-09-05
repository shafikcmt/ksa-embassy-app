@extends('layouts.erp-app')

@section('title', 'Due List')
@section('page-title', 'Due List')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $srcChip = [
        'delivery'    => 'bg-sky-100 text-sky-700',
        'double_mofa' => 'bg-violet-100 text-violet-700',
    ];
@endphp

<div x-data="dueListPage()">

    <x-ui.page-header title="Due List" subtitle="Outstanding payments across Delivery & Double MOFA" icon="bi-hourglass-split" />

    {{-- Summary --}}
    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-sky-600">Delivery Due</div>
            <div class="mt-1 text-xl font-bold text-sky-700">৳{{ number_format($deliveryDue, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-violet-600">Double MOFA Unpaid</div>
            <div class="mt-1 text-xl font-bold text-violet-700">৳{{ number_format($doubleMofaDue, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Combined Outstanding</div>
            <div class="mt-1 text-xl font-bold text-rose-700">৳{{ number_format($combinedDue, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Due Items</div>
            <div class="mt-1 text-xl font-bold text-slate-900">{{ $count }}</div>
        </div>
    </div>

    {{-- Filters (GET, bookmarkable) --}}
    <form method="GET" action="{{ route('erp.due-list') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="{{ $lbl }}">Type</label>
                <select name="type" class="{{ $inp }}">
                    <option value="all" @selected($filters['type'] === 'all')>All</option>
                    <option value="delivery" @selected($filters['type'] === 'delivery')>Delivery</option>
                    <option value="double_mofa" @selected($filters['type'] === 'double_mofa')>Double MOFA</option>
                </select>
            </div>
            <div><label class="{{ $lbl }}">From</label><input type="date" name="from" value="{{ $filters['from'] }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">To</label><input type="date" name="to" value="{{ $filters['to'] }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Search</label><input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Name or passport" class="{{ $inp }}"></div>
            <div>
                <label class="{{ $lbl }}">Sort</label>
                <select name="sort" class="{{ $inp }}">
                    <option value="due" @selected($filters['sort'] === 'due')>Due (high → low)</option>
                    <option value="date" @selected($filters['sort'] === 'date')>Date (newest)</option>
                    <option value="name" @selected($filters['sort'] === 'name')>Name (A → Z)</option>
                </select>
            </div>
        </div>
        <div class="mt-4 flex justify-start gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-funnel"></i> Apply Filters</button>
            <a href="{{ route('erp.due-list') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
        </div>
    </form>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
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
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $srcChip[$r['source']] ?? 'bg-slate-100 text-slate-600' }}">{{ $r['source_label'] }}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $r['date'] }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $r['full_name'] }}</td>
                            <td class="px-4 py-3">{{ $r['passport_no'] }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">৳{{ number_format($r['billed'], 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-emerald-700">৳{{ number_format($r['paid'], 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-600">৳{{ number_format($r['due'], 2) }}</td>
                            <td class="px-4 py-3">{{ $r['status'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end">
                                    <button type="button" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100"
                                            x-on:click="openPay(@js([
                                                'url' => $r['pay_url'],
                                                'name' => $r['full_name'],
                                                'label' => $r['source_label'],
                                                'due' => number_format($r['due'], 2, '.', ''),
                                            ]))"><i class="bi bi-cash-coin"></i> Receive Payment</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-check2-circle mb-2 block text-2xl text-emerald-400"></i>No outstanding dues for this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Receive Payment modal → posts to the EXISTING E2 endpoint (ErpPaymentService) --}}
    <div x-show="paying" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="paying = false"></div>
        <form method="POST" x-bind:action="payForm.url" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            @csrf
            <h3 class="mb-1 text-base font-bold text-slate-900">Receive Payment</h3>
            <p class="mb-4 text-sm text-slate-500">
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600" x-text="payForm.label"></span>
                <span x-text="payForm.name"></span> — due <span class="font-semibold text-rose-600">৳<span x-text="payForm.due"></span></span>
            </p>
            <div class="space-y-3">
                <div><label class="{{ $lbl }}">Amount (৳) <span class="text-rose-500">*</span></label><input type="number" step="0.01" min="0.01" name="amount" x-model="payForm.amount" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Note <span class="font-normal text-slate-400">(optional)</span></label><input type="text" name="note" x-model="payForm.note" maxlength="255" class="{{ $inp }}"></div>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" x-on:click="paying = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white"><i class="bi bi-check-lg"></i> Record Payment</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function dueListPage() {
        return {
            paying: false,
            payForm: {},
            openPay(row) {
                this.payForm = { url: row.url, name: row.name, label: row.label, due: row.due, amount: '', note: '' };
                this.paying = true;
            },
        };
    }
</script>
@endpush
@endsection
