@extends('layouts.erp-app')

@section('title', 'Expenses')
@section('page-title', 'Expenses')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
@endphp

<div x-data="expensePage()">

    <x-ui.page-header title="Expenses" subtitle="Track agency expenses" icon="bi-cash-coin" />

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

    {{-- Collapsible Add form: opened by the "Add …" button; starts OPEN after a
         validation error, a duplicate warning or old input so nothing is lost. --}}
    @php
        $addOpen = ($errors->any() && old('_method') === null)
            || session('duplicate_warning') || session('passport_matches')
            || (session()->hasOldInput() && old('_method') === null);
    @endphp
    <div x-data="{ addOpen: @js((bool) $addOpen) }">
    {{-- E7a Print · E7d Export/Import --}}
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <button type="button" x-on:click="addOpen = !addOpen; if (addOpen) $nextTick(() => document.getElementById('erpAddForm')?.querySelector('input:not([type=hidden]), select')?.focus())"
                class="mr-auto inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
            <i class="bi" x-bind:class="addOpen ? 'bi-dash-lg' : 'bi-plus-lg'"></i> <span x-text="addOpen ? 'Close form' : 'Add Expense'">Add Expense</span>
        </button>
        <a href="{{ route('erp.expenses.export') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-filetype-csv text-emerald-600"></i> Export CSV
        </a>
        <a href="{{ route('erp.expenses.print') }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
        @if(auth()->user()->isAgencyAdmin())
            <a href="{{ route('erp.expenses.import.form') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-upload"></i> Import CSV
            </a>
        @endif
    </div>

    {{-- Add entry --}}
    @php
        // Add + Edit share the default error bag; a failed Edit comes back with _method=PUT,
        // so only show inline errors here when the Add form was the one submitted.
        $addErr = $errors->any() && old('_method') === null;
        $fi = \App\Support\ErpForm::INPUT;
        $bd = fn (string $k) => $addErr && $errors->has($k) ? \App\Support\ErpForm::BORDER_ERROR : \App\Support\ErpForm::BORDER_OK;
    @endphp
    <form method="POST" action="{{ route('erp.expenses.store') }}" class="mb-6" id="erpAddForm" x-show="addOpen" x-cloak x-data="{ busy: false }" x-on:submit="busy = true">
        @csrf
        <x-erp.section icon="bi-plus-circle" title="Add Expense">
            <x-erp.field label="Date" for="ex_expense_date" required :name="$addErr ? 'expense_date' : null">
                <input id="ex_expense_date" type="date" name="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required class="{{ $fi }} {{ $bd('expense_date') }}">
            </x-erp.field>
            <x-erp.field label="Category" for="ex_category" required :name="$addErr ? 'category' : null">
                <select id="ex_category" name="category" required class="{{ $fi }} {{ $bd('category') }}">
                    <option value="">—</option>
                    @foreach($categories as $key => $label)<option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
            <x-erp.field label="Amount (৳)" for="ex_amount" required :name="$addErr ? 'amount' : null">
                <input id="ex_amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="{{ $fi }} {{ $bd('amount') }}">
            </x-erp.field>
            <x-erp.field label="Paid via" for="ex_paid_via" :name="$addErr ? 'paid_via' : null">
                <select id="ex_paid_via" name="paid_via" class="{{ $fi }} {{ $bd('paid_via') }}">
                    <option value="">—</option>
                    @foreach($paidVia as $key => $label)<option value="{{ $key }}" @selected(old('paid_via') === $key)>{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
            <x-erp.field label="Note" for="ex_note" :name="$addErr ? 'note' : null" class="sm:col-span-2 lg:col-span-2">
                <input id="ex_note" type="text" name="note" value="{{ old('note') }}" maxlength="255" class="{{ $fi }} {{ $bd('note') }}">
            </x-erp.field>
            <div class="col-span-full flex justify-end">
                <button type="submit" x-bind:disabled="busy"
                        class="inline-flex min-w-[6.5rem] items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak aria-hidden="true"></i>
                    <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
                </button>
            </div>
        </x-erp.section>
    </form>
    </div>

    {{-- Live search (client-side; filters only the already-loaded, agency-scoped rows) --}}
    <div class="mb-4 max-w-md">
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-emerald-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
            <i class="bi bi-search text-sm text-slate-400"></i>
            <input type="text" x-model.debounce.200ms="q" placeholder="Search category, note, paid via…"
                   class="h-11 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
            <button type="button" x-show="q" x-cloak @click="q = ''" title="Clear search"
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-slate-400 transition hover:bg-slate-200 hover:text-slate-600">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    </div>

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
                        <tr class="hover:bg-slate-50" x-show="q === '' || $el.dataset.s.includes(q.toLowerCase())"
                            data-s="{{ \Illuminate\Support\Str::lower(trim(($e->categoryLabel() ?? '').' '.($e->note ?? '').' '.($e->paidViaLabel() ?? ''))) }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->expense_date->format('d M Y') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $e->categoryLabel() }}</span></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-rose-600">৳{{ number_format((float) $e->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $e->paidViaLabel() ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $e->note ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @php $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition'; @endphp
                                <div class="flex flex-nowrap items-center justify-end gap-1.5">
                                    @if($e->isSystemGenerated())
                                    {{-- Booked from a paid Payment Voucher: read-only here, managed on the voucher. --}}
                                    <a href="{{ route('erp.payment-vouchers.show', $e->payment_voucher_id) }}" title="Created automatically when this voucher was paid"
                                       class="{{ $pill }} bg-brand-50 text-brand-700 ring-brand-200 hover:bg-brand-100"><i class="bi bi-wallet2"></i> {{ $e->paymentVoucher?->voucher_number ?? 'Voucher' }}</a>
                                    @else
                                    <button type="button" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'expense_date' => $e->expense_date->format('Y-m-d'),
                                                'category' => $e->category,
                                                'amount' => number_format((float) $e->amount, 2, '.', ''),
                                                'paid_via' => $e->paid_via,
                                                'note' => $e->note,
                                            ]))"><i class="bi bi-pencil"></i> Edit</button>
                                    <form method="POST" action="{{ route('erp.expenses.destroy', $e) }}" onsubmit="return confirm('Delete this expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No expenses yet.</td></tr>
                    @endforelse
                    <tr x-show="q !== '' && ![...$root.querySelectorAll('tr[data-s]')].some(r => r.dataset.s.includes(q.toLowerCase()))" x-cloak>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-search mb-2 block text-2xl"></i>No records match “<span x-text="q"></span>”.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit modal (classic POST → PUT erp.expenses.update) --}}
    @php $eb = \App\Support\ErpForm::INPUT . ' ' . \App\Support\ErpForm::BORDER_OK; @endphp
    <x-erp.modal show="editing" close="editing = false" icon="bi-cash-coin" title-id="expense-edit-title"
                 title="'Edit Expense'" edit="true" action="updateBase + '/' + form.id" method="PUT">
        <x-erp.section icon="bi-cash-coin" title="Expense Details">
            <x-erp.field label="Date" for="exe_expense_date" required>
                <input id="exe_expense_date" type="date" name="expense_date" x-model="form.expense_date" required class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Category" for="exe_category" required>
                <select id="exe_category" name="category" x-model="form.category" required class="{{ $eb }}">
                    @foreach($categories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
            <x-erp.field label="Amount (৳)" for="exe_amount" required>
                <input id="exe_amount" type="number" step="0.01" min="0.01" name="amount" x-model="form.amount" required class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Paid via" for="exe_paid_via">
                <select id="exe_paid_via" name="paid_via" x-model="form.paid_via" class="{{ $eb }}">
                    <option value="">—</option>
                    @foreach($paidVia as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
        </x-erp.section>
        <x-erp.section icon="bi-journal-text" title="Additional Info" cols="2">
            <x-erp.field label="Note" for="exe_note" class="col-span-full">
                <input id="exe_note" type="text" name="note" x-model="form.note" maxlength="255" class="{{ $eb }}">
            </x-erp.field>
        </x-erp.section>
    </x-erp.modal>
</div>

@push('scripts')
<script>
    function expensePage() {
        return {
            q: '',
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
