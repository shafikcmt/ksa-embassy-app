@extends('layouts.erp-app')

@section('title', 'Expenses')
@section('page-title', 'Expenses')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
@endphp

<div x-data="expensePage()">

    {{-- Summary --}}
    <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">This Month</div>
            <div class="mt-1 text-xl font-bold text-rose-700">৳{{ number_format($monthTotal, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">All-Time Total</div>
            <div class="mt-1 text-xl font-bold text-slate-900">৳{{ number_format($allTimeTotal, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Top Category</div>
            @if($byCategory->isNotEmpty())
                <div class="mt-1 text-sm font-bold text-slate-800">{{ $categories[$byCategory->keys()->first()] ?? $byCategory->keys()->first() }}</div>
                <div class="text-xs text-slate-500">৳{{ number_format($byCategory->first(), 2) }}</div>
            @else
                <div class="mt-1 text-sm text-slate-400">—</div>
            @endif
        </div>
    </div>

    {{-- Print (full list PDF, E7a) --}}
    <div class="mb-4 flex justify-end">
        <a href="{{ route('erp.expenses.print') }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
    </div>

    {{-- Add entry --}}
    <form method="POST" action="{{ route('erp.expenses.store') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-plus-circle text-emerald-600"></i> Add Expense</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div><label class="{{ $lbl }}">Date <span class="text-rose-500">*</span></label><input type="date" name="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required class="{{ $inp }}"></div>
            <div>
                <label class="{{ $lbl }}">Category <span class="text-rose-500">*</span></label>
                <select name="category" required class="{{ $inp }}">
                    <option value="">—</option>
                    @foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div><label class="{{ $lbl }}">Amount (৳) <span class="text-rose-500">*</span></label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="{{ $inp }}"></div>
            <div>
                <label class="{{ $lbl }}">Paid via</label>
                <select name="paid_via" class="{{ $inp }}">
                    <option value="">—</option>
                    @foreach($paidVia as $key => $label)<option value="{{ $key }}" @selected(old('paid_via') === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div><label class="{{ $lbl }}">Note</label><input type="text" name="note" value="{{ old('note') }}" maxlength="255" class="{{ $inp }}"></div>
        </div>
        <div class="mt-4 flex justify-start">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-plus-lg"></i> Add Expense</button>
        </div>
    </form>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Paid via</th>
                        <th class="px-4 py-3">Note</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->expense_date->format('d M Y') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $e->categoryLabel() }}</span></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-600">৳{{ number_format((float) $e->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $e->paidViaLabel() ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $e->note ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" title="Edit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-emerald-600"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'expense_date' => $e->expense_date->format('Y-m-d'),
                                                'category' => $e->category,
                                                'amount' => number_format((float) $e->amount, 2, '.', ''),
                                                'paid_via' => $e->paid_via,
                                                'note' => $e->note,
                                            ]))"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('erp.expenses.destroy', $e) }}" onsubmit="return confirm('Delete this expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit modal --}}
    <div x-show="editing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="editing = false"></div>
        <form method="POST" x-bind:action="updateBase + '/' + form.id" class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            @csrf @method('PUT')
            <h3 class="mb-4 text-base font-bold text-slate-900">Edit Expense</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="{{ $lbl }}">Date <span class="text-rose-500">*</span></label><input type="date" name="expense_date" x-model="form.expense_date" required class="{{ $inp }}"></div>
                <div>
                    <label class="{{ $lbl }}">Category <span class="text-rose-500">*</span></label>
                    <select name="category" x-model="form.category" required class="{{ $inp }}">
                        @foreach($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div><label class="{{ $lbl }}">Amount (৳) <span class="text-rose-500">*</span></label><input type="number" step="0.01" min="0.01" name="amount" x-model="form.amount" required class="{{ $inp }}"></div>
                <div>
                    <label class="{{ $lbl }}">Paid via</label>
                    <select name="paid_via" x-model="form.paid_via" class="{{ $inp }}">
                        <option value="">—</option>
                        @foreach($paidVia as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2"><label class="{{ $lbl }}">Note</label><input type="text" name="note" x-model="form.note" maxlength="255" class="{{ $inp }}"></div>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" x-on:click="editing = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function expensePage() {
        return {
            editing: false,
            updateBase: '{{ url('erp/expenses') }}',
            form: {},
            openEdit(row) {
                this.form = Object.assign({}, row);
                for (const k in this.form) if (this.form[k] === null) this.form[k] = '';
                this.editing = true;
            },
        };
    }
</script>
@endpush
@endsection
