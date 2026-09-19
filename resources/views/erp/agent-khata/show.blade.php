@extends('layouts.erp-app')

@section('title', 'Agent Khata — ' . $agent->name)
@section('page-title', 'Agent Khata')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    // Display-only label context (does NOT change AgentTransaction::TYPES or any
    // stored key/calculation). debit = "Paid" (advance out to the agent, balance ↑),
    // credit = "Received" (repayment in from the agent, balance ↓).
    $typeHint = ['debit' => 'Paid (Advance to agent)', 'credit' => 'Received (Repayment)'];
@endphp

<div x-data="khataPage()">

    <a href="{{ route('erp.agent-khata') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-700"><i class="bi bi-arrow-left"></i> All agents</a>

    {{-- Agent header + balance --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5">
        <div>
            <div class="text-lg font-bold text-slate-900">{{ $agent->name }}</div>
            <div class="text-sm text-slate-500">{{ $agent->phone ?: '—' }} · Opening: ৳{{ number_format((float) $agent->opening_balance, 2) }}</div>
        </div>
        <div class="text-right">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current Balance</div>
            <div class="text-2xl font-bold {{ $balance > 0 ? 'text-emerald-700' : ($balance < 0 ? 'text-rose-600' : 'text-slate-500') }}">৳{{ number_format($balance, 2) }}</div>
            <div class="text-[0.7rem] text-slate-400">{{ $balance > 0 ? 'agent owes agency' : ($balance < 0 ? 'agency owes agent' : 'settled') }}</div>
        </div>
    </div>

    @if($isAdmin)
        {{-- Add transaction (admin-only) --}}
        <form method="POST" action="{{ route('erp.agent-khata.store', $agent) }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
            @csrf
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-plus-circle text-emerald-600"></i> Add Transaction</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="{{ $lbl }}">Type <span class="text-rose-500">*</span></label>
                    <select name="type" required class="{{ $inp }}">
                        <option value="">—</option>
                        @foreach($types as $key => $label)<option value="{{ $key }}" @selected(old('type') === $key)>{{ $typeHint[$key] ?? $label }}</option>@endforeach
                    </select>
                </div>
                <div><label class="{{ $lbl }}">Amount (৳) <span class="text-rose-500">*</span></label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Note</label><input type="text" name="note" value="{{ old('note') }}" maxlength="255" class="{{ $inp }}"></div>
            </div>
            <div class="mt-4 flex justify-start">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-plus-lg"></i> Save</button>
            </div>
        </form>
    @else
        <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500"><i class="bi bi-lock"></i> Only agency admins can add or reverse khata transactions.</div>
    @endif

    {{-- Live search (client-side; filters only the already-loaded, agency-scoped rows) --}}
    <div class="mb-4 max-w-md">
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-emerald-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
            <i class="bi bi-search text-sm text-slate-400"></i>
            <input type="text" x-model.debounce.200ms="q" placeholder="Search note, type, amount…"
                   class="h-11 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
            <button type="button" x-show="q" x-cloak @click="q = ''" title="Clear search"
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-slate-400 transition hover:bg-slate-200 hover:text-slate-600">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    </div>

    {{-- Legend: explains the amount direction (display only; math is unchanged) --}}
    <p class="mb-2 text-xs text-slate-500">
        <span class="font-semibold text-emerald-700"><i class="bi bi-arrow-up-short"></i> Paid</span> = advance to agent (increases what they owe)
        <span class="mx-1 text-slate-300">·</span>
        <span class="font-semibold text-rose-600"><i class="bi bi-arrow-down-short"></i> Received</span> = repayment from agent (reduces what they owe)
    </p>

    {{-- Ledger --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Note</th>
                        <th class="px-4 py-3">By</th>
                        @if($isAdmin)<th class="px-4 py-3 text-right">Actions</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($txns as $t)
                        @php $isReversed = in_array($t->id, $reversedIds); @endphp
                        <tr class="hover:bg-slate-50 {{ $isReversed ? 'opacity-60' : '' }}"
                            x-show="q === '' || $el.dataset.s.includes(q.toLowerCase())"
                            data-s="{{ \Illuminate\Support\Str::lower(trim(($t->note ?? '').' '.($typeHint[$t->type] ?? $t->typeLabel()).' '.number_format((float) $t->amount, 2))) }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $t->txn_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $t->type === 'debit' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $typeHint[$t->type] ?? $t->typeLabel() }}</span>
                                @if($t->isReversal())<span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-[0.6rem] font-bold uppercase text-slate-500">Reversal</span>@endif
                                @if($isReversed)<span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-[0.6rem] font-bold uppercase text-slate-500">Reversed</span>@endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold {{ $t->type === 'debit' ? 'text-emerald-700' : 'text-rose-600' }}"><i class="bi {{ $t->type === 'debit' ? 'bi-arrow-up-short' : 'bi-arrow-down-short' }}"></i>৳{{ number_format((float) $t->amount, 2) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $t->note ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $t->recordedBy?->name ?: '—' }}</td>
                            @if($isAdmin)
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($t->type === 'credit' && ! $t->isReversal() && ! $isReversed)
                                            <a href="{{ route('erp.agent-khata.voucher', $t) }}" target="_blank" title="Print Credit Voucher"
                                               class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100"><i class="bi bi-receipt"></i> Voucher</a>
                                        @endif
                                        @if(! $t->isReversal() && ! $isReversed)
                                            <button type="button" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"
                                                    x-on:click="openReverse(@js(['id' => $t->id, 'type' => $t->typeLabel(), 'amount' => number_format((float) $t->amount, 2)]))"><i class="bi bi-arrow-counterclockwise"></i> Reverse</button>
                                        @elseif(! ($t->type === 'credit' && ! $t->isReversal() && ! $isReversed))
                                            <span class="text-xs text-slate-300">—</span>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAdmin ? 6 : 5 }}" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No transactions yet.</td></tr>
                    @endforelse
                    <tr x-show="q !== '' && ![...$root.querySelectorAll('tr[data-s]')].some(r => r.dataset.s.includes(q.toLowerCase()))" x-cloak>
                        <td colspan="{{ $isAdmin ? 6 : 5 }}" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-search mb-2 block text-2xl"></i>No transactions match “<span x-text="q"></span>”.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if($isAdmin)
        {{-- Reverse modal (admin-only) --}}
        <div x-show="reversing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" x-on:click="reversing = false"></div>
            <form method="POST" x-bind:action="reverseBase + '/' + form.id + '/reverse'" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" onsubmit="return confirm('Reverse this transaction? This cannot be undone.')">
                @csrf
                <h3 class="mb-1 text-base font-bold text-slate-900">Reverse Transaction</h3>
                <p class="mb-4 text-sm text-slate-500"><span x-text="form.type"></span> of ৳<span x-text="form.amount"></span> — a reversing entry of the opposite type will be booked.</p>
                <div><label class="{{ $lbl }}">Reason <span class="text-rose-500">*</span></label><input type="text" name="note" x-model="form.note" required maxlength="255" placeholder="Why is this being reversed?" class="{{ $inp }}"></div>
                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" x-on:click="reversing = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700"><i class="bi bi-arrow-counterclockwise"></i> Reverse</button>
                </div>
            </form>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function khataPage() {
        return {
            q: '',
            reversing: false,
            reverseBase: '{{ url('erp/agent-khata/transactions') }}',
            form: {},
            openReverse(row) {
                this.form = { id: row.id, type: row.type, amount: row.amount, note: '' };
                this.reversing = true;
            },
        };
    }
</script>
@endpush
@endsection
