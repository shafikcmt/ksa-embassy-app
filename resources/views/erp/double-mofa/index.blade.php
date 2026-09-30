@extends('layouts.erp-app')

@section('title', 'Double MOFA')
@section('page-title', 'Double MOFA')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $statusChip = [
        'unpaid'  => 'bg-rose-100 text-rose-700',
        'partial' => 'bg-amber-100 text-amber-700',
        'paid'    => 'bg-emerald-100 text-emerald-700',
    ];
    $canReceive = auth()->user()->isAgencyAdmin() || auth()->user()->can('erp_receive_payment');
@endphp

<div x-data="doubleMofaPage()">

    <x-ui.page-header title="Double MOFA" subtitle="Double MOFA billing & payments" icon="bi-files" />

    {{-- Summary --}}
    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Billed</div>
            <div class="mt-1 text-xl font-bold text-slate-900">৳{{ number_format($totalBilled, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Collected</div>
            <div class="mt-1 text-xl font-bold text-emerald-700">৳{{ number_format($totalCollected, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Unpaid</div>
            <div class="mt-1 text-xl font-bold text-rose-700">৳{{ number_format($totalDue, 2) }}</div>
        </div>
    </div>

    {{-- E7a Print · E7e Export/Import --}}
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('erp.double-mofa.export') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-filetype-csv text-emerald-600"></i> Export CSV
        </a>
        <a href="{{ route('erp.double-mofa.print') }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
        @if(auth()->user()->isAgencyAdmin())
            <a href="{{ route('erp.double-mofa.import.form') }}"
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
    <form method="POST" action="{{ route('erp.double-mofa.store') }}" class="mb-6"
          x-data="doubleMofaPassportCheck(@js(session('passport_matches', [])), @js(old('passport_no', '')))"
          x-on:submit="onSubmit($event)">
        @csrf
        <input type="hidden" name="confirm_duplicate" value="0" x-ref="confirm">
        <x-erp.section icon="bi-plus-circle" title="Add Double MOFA">
            <x-erp.field label="Full Name" for="dm_full_name" required :name="$addErr ? 'full_name' : null">
                <input id="dm_full_name" type="text" name="full_name" value="{{ old('full_name') }}" required class="{{ $fi }} {{ $bd('full_name') }}">
            </x-erp.field>
            <x-erp.field label="Passport Number" for="doubleMofaPassport" required :name="$addErr ? 'passport_no' : null">
                <input type="text" id="doubleMofaPassport" name="passport_no" value="{{ old('passport_no') }}" required class="{{ $fi }} {{ $bd('passport_no') }}" x-ref="passport" x-on:blur="onBlur()">
            </x-erp.field>
            <x-erp.field label="Old MOFA Number" for="dm_old_mofa_number" :name="$addErr ? 'old_mofa_number' : null">
                <input id="dm_old_mofa_number" type="text" name="old_mofa_number" value="{{ old('old_mofa_number') }}" class="{{ $fi }} {{ $bd('old_mofa_number') }}">
            </x-erp.field>
            <x-erp.field label="Date" for="dm_mofa_date" required :name="$addErr ? 'mofa_date' : null">
                <input id="dm_mofa_date" type="date" name="mofa_date" value="{{ old('mofa_date', now()->format('Y-m-d')) }}" required class="{{ $fi }} {{ $bd('mofa_date') }}">
            </x-erp.field>
            <x-erp.field label="Billing Amount (৳)" for="dm_billing_amount" required :name="$addErr ? 'billing_amount' : null">
                <input id="dm_billing_amount" type="number" step="0.01" min="0" name="billing_amount" value="{{ old('billing_amount', number_format($defaultRate, 2, '.', '')) }}" required class="{{ $fi }} {{ $bd('billing_amount') }}">
            </x-erp.field>
            <x-erp.field label="Reference" for="dm_reference" :name="$addErr ? 'reference' : null">
                <x-erp.select-search id="dm_reference" name="reference" :value="old('reference')" :agents="$agentOptions ?? []" />
            </x-erp.field>
            <div class="col-span-full flex justify-end">
                <button type="submit" x-bind:disabled="busy || checking" x-bind:class="checking && 'opacity-60'"
                        class="inline-flex min-w-[6.5rem] items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak aria-hidden="true"></i>
                    <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
                </button>
            </div>
        </x-erp.section>

        {{-- Passport-already-exists confirm modal. Opens (a) on passport blur / Save via the
             read-only erp.passport-records AJAX check, or (b) server-side when store()
             flashes 'passport_matches' (the backend guard, works without JS fetch).
             Lists EVERY match across Medical / MOFA / Double MOFA / Stamping / BMET /
             Delivery. "Continue Anyway" submits with confirm_duplicate=1. Same modal
             shell as the other ERP duplicate dialogs. --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none"
             x-on:keydown.escape.window="open && cancel()">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/50" x-on:click="cancel()"></div>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative flex w-full max-w-lg flex-col rounded-2xl bg-white p-6 shadow-xl" style="max-height: 90vh">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-slate-900">Passport Already Exists</h3>
                        <p class="mt-1 text-sm text-slate-500">This passport number already exists in the ERP.</p>
                        <p class="mt-1 text-sm text-slate-700">Passport Number: <span class="font-semibold" x-text="matchedPassport"></span></p>
                    </div>
                </div>

                <div class="mt-4 flex-1 overflow-y-auto rounded-xl border border-slate-100 bg-slate-50" style="min-height: 0">
                    <div class="px-4 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Existing records (<span x-text="matches.length"></span>)</div>
                    <ul class="divide-y divide-slate-100 text-sm">
                        <template x-for="(m, i) in matches" :key="i">
                            <li class="px-4 py-2.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-800" x-text="m.module"></span>
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700" x-show="m.status" x-text="m.status"></span>
                                </div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    <span x-text="m.name || '—'"></span>
                                    <template x-if="m.reference"><span> · <span x-text="m.reference"></span></span></template>
                                    <template x-if="m.date"><span> · <span x-text="m.date"></span></span></template>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" x-on:click="cancel()" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel / Edit Passport</button>
                    <button type="button" x-on:click="continueAnyway()" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 hover:shadow-md"><i class="bi bi-check-lg"></i> Continue Anyway</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Live search (client-side; filters only the already-loaded, agency-scoped rows) --}}
    <div class="mb-4 max-w-md">
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-emerald-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
            <i class="bi bi-search text-sm text-slate-400"></i>
            <input type="text" x-model.debounce.200ms="q" placeholder="Search name, passport, old MOFA, reference…"
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
                        <th class="px-4 py-3">Full Name</th>
                        <th class="px-4 py-3">Passport</th>
                        <th class="px-4 py-3">Old MOFA #</th>
                        <th class="px-4 py-3 text-right">Billing</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Unpaid</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $e)
                        @php
                            $reversedIds = $e->receipts->where('type', 'reversal')->pluck('reverses_id')->filter()->all();
                            $unpaid = (float) $e->billing_amount - (float) $e->paid_amount;
                            $receiptRows = $e->receipts->sortByDesc('received_at')->map(fn ($r) => [
                                'id'          => $r->id,
                                'type'        => $r->type,
                                'amount'      => number_format((float) $r->amount, 2),
                                'note'        => $r->note,
                                'by'          => $r->receivedBy?->name ?? '—',
                                'at'          => optional($r->received_at)->format('d M Y g:i A') ?? '—',
                                'reversed'    => $r->type === 'payment' && in_array($r->id, $reversedIds),
                                'is_reversal' => $r->type === 'reversal',
                            ])->values();
                        @endphp
                        <tr class="hover:bg-slate-50" x-show="q === '' || $el.dataset.s.includes(q.toLowerCase())"
                            data-s="{{ \Illuminate\Support\Str::lower(trim(($e->full_name ?? '').' '.($e->passport_no ?? '').' '.($e->old_mofa_number ?? '').' '.($e->reference ?? ''))) }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->mofa_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $e->full_name }}</td>
                            <td class="px-4 py-3">{{ $e->passport_no }}</td>
                            <td class="px-4 py-3">{{ $e->old_mofa_number ?: '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">৳{{ number_format((float) $e->billing_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-emerald-700">৳{{ number_format((float) $e->paid_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap {{ $unpaid > 0 ? 'font-semibold text-rose-600' : 'text-slate-400' }}">৳{{ number_format($unpaid, 2) }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusChip[$e->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $e->statusLabel() }}</span></td>
                            <td class="px-4 py-3">
                                @php $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition'; @endphp
                                <div class="flex flex-nowrap items-center justify-end gap-1.5">
                                    @if($canReceive && $unpaid > 0)
                                    <button type="button" class="{{ $pill }} bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100"
                                            x-on:click="openPay(@js(['id' => $e->id, 'name' => $e->full_name, 'due' => number_format($unpaid, 2, '.', '')]))"><i class="bi bi-cash-coin"></i> Receive</button>
                                    @endif
                                    <button type="button" class="{{ $pill }} bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100"
                                            x-on:click="openReceipts(@js($e->full_name), @js($receiptRows))"><i class="bi bi-clock-history"></i> History</button>
                                    <button type="button" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'mofa_date' => $e->mofa_date->format('Y-m-d'),
                                                'full_name' => $e->full_name,
                                                'passport_no' => $e->passport_no,
                                                'old_mofa_number' => $e->old_mofa_number,
                                                'reference' => $e->reference,
                                                'billing_amount' => number_format((float) $e->billing_amount, 2, '.', ''),
                                            ]))"><i class="bi bi-pencil"></i> Edit</button>
                                    <form method="POST" action="{{ route('erp.double-mofa.destroy', $e) }}" onsubmit="return confirm('Delete this Double MOFA entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No Double MOFA entries yet.</td></tr>
                    @endforelse
                    <tr x-show="q !== '' && ![...$root.querySelectorAll('tr[data-s]')].some(r => r.dataset.s.includes(q.toLowerCase()))" x-cloak>
                        <td colspan="9" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-search mb-2 block text-2xl"></i>No records match “<span x-text="q"></span>”.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Receive Payment modal --}}
    <div x-show="paying" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="paying = false"></div>
        <form method="POST" x-bind:action="payBase + '/' + payForm.id + '/payment'" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            @csrf
            <h3 class="mb-1 text-base font-bold text-slate-900">Receive Payment</h3>
            <p class="mb-4 text-sm text-slate-500"><span x-text="payForm.name"></span> — unpaid <span class="font-semibold text-rose-600">৳<span x-text="payForm.due"></span></span></p>
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

    {{-- Payment history modal --}}
    <div x-show="viewing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="viewing = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h3 class="mb-4 text-base font-bold text-slate-900">Payment History — <span x-text="receiptName"></span></h3>
            <div class="max-h-96 space-y-2 overflow-y-auto">
                <template x-if="receipts.length === 0"><p class="py-6 text-center text-sm text-slate-400">No payments recorded yet.</p></template>
                <template x-for="r in receipts" :key="r.id">
                    <div class="rounded-xl border border-slate-200 p-3" :class="r.is_reversal ? 'bg-rose-50/50' : (r.reversed ? 'bg-slate-50 opacity-70' : 'bg-white')">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold" :class="r.is_reversal ? 'text-rose-600' : 'text-emerald-700'">
                                    <span x-text="r.is_reversal ? '−৳' : '৳'"></span><span x-text="r.amount"></span>
                                </span>
                                <span x-show="r.is_reversal" class="rounded-full bg-rose-100 px-2 py-0.5 text-[0.6rem] font-bold uppercase text-rose-600">Reversal</span>
                                <span x-show="r.reversed" class="rounded-full bg-slate-200 px-2 py-0.5 text-[0.6rem] font-bold uppercase text-slate-500">Reversed</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400" x-text="r.at"></span>
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">By <span x-text="r.by"></span><template x-if="r.note"><span> · <span x-text="r.note"></span></span></template></div>

                        <template x-if="!r.is_reversal && !r.reversed">
                            <form method="POST" x-bind:action="reverseBase + '/' + r.id + '/reverse'" class="mt-2 flex gap-2" onsubmit="return confirm('Reverse this payment? This cannot be undone.')">
                                @csrf
                                <input type="text" name="note" required maxlength="255" placeholder="Reason (required)" class="flex-1 rounded-lg border-slate-300 text-xs shadow-sm focus:border-rose-500 focus:ring-rose-500">
                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700"><i class="bi bi-arrow-counterclockwise"></i> Reverse</button>
                            </form>
                        </template>
                    </div>
                </template>
            </div>
            <div class="mt-5 flex justify-end">
                <button type="button" x-on:click="viewing = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Close</button>
            </div>
        </div>
    </div>

    {{-- Edit modal (classic POST → PUT erp.double-mofa.update) --}}
    @php $eb = \App\Support\ErpForm::INPUT . ' ' . \App\Support\ErpForm::BORDER_OK; @endphp
    <x-erp.modal show="editing" close="editing = false" icon="bi-files" title-id="double-mofa-edit-title"
                 title="'Edit Double MOFA'" edit="true" action="updateBase + '/' + form.id" method="PUT">
        <x-erp.section icon="bi-person-vcard" title="Candidate Information">
            <x-erp.field label="Full Name" for="dme_full_name" required>
                <input id="dme_full_name" type="text" name="full_name" x-model="form.full_name" required class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Passport Number" for="dme_passport_no" required>
                <input id="dme_passport_no" type="text" name="passport_no" x-model="form.passport_no" required class="{{ $eb }}">
            </x-erp.field>
        </x-erp.section>
        <x-erp.section icon="bi-files" title="MOFA & Billing">
            <x-erp.field label="Old MOFA Number" for="dme_old_mofa_number">
                <input id="dme_old_mofa_number" type="text" name="old_mofa_number" x-model="form.old_mofa_number" class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Date" for="dme_mofa_date" required>
                <input id="dme_mofa_date" type="date" name="mofa_date" x-model="form.mofa_date" required class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Billing Amount (৳)" for="dme_billing_amount" required>
                <input id="dme_billing_amount" type="number" step="0.01" min="0" name="billing_amount" x-model="form.billing_amount" required class="{{ $eb }}">
            </x-erp.field>
        </x-erp.section>
        <x-erp.section icon="bi-journal-text" title="Additional Info" cols="2">
            <x-erp.field label="Reference" for="dme_reference">
                <x-erp.select-search id="dme_reference" name="reference" x-model="form.reference" :agents="$agentOptions ?? []" />
            </x-erp.field>
        </x-erp.section>
    </x-erp.modal>
</div>

@include('erp.partials._passport-autofill', [
    'passportId' => 'doubleMofaPassport',
    'map' => ['full_name' => 'full_name', 'reference' => 'reference'],
])

@push('scripts')
<script>
    /**
     * Double MOFA Add form: cross-module "passport already exists" warning.
     * Never blocks — the user can always Continue Anyway. If the AJAX check fails
     * the form just submits and DoubleMofaController::store() re-checks server-side.
     */
    function doubleMofaPassportCheck(initialMatches, initialPassport) {
        const norm = v => String(v || '').trim().toUpperCase();
        const hasInitial = Array.isArray(initialMatches) && initialMatches.length > 0;
        return {
            endpoint: @js(route('erp.passport-records')),
            matches: hasInitial ? initialMatches : [],
            matchedPassport: hasInitial ? norm(initialPassport) : '',
            open: hasInitial,
            submitAfter: hasInitial,   // Continue from a Save-triggered modal → submit
            confirmedPassport: '',
            checking: false,
            busy: false,
            cache: {},                 // normalised passport → Promise<matches|null>

            lookup(p) {
                if (!this.cache[p]) {
                    this.cache[p] = fetch(this.endpoint + '?passport_no=' + encodeURIComponent(p), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                    .then(r => r.ok ? r.json() : null)
                    .then(d => (d && Array.isArray(d.matches)) ? d.matches : null)
                    .catch(() => null)
                    .then(m => { if (m === null) delete this.cache[p]; return m; });
                }
                return this.cache[p];
            },
            show(p, matches, submitAfter) {
                this.matchedPassport = p;
                this.matches = matches;
                this.submitAfter = submitAfter;
                this.open = true;
            },
            async onBlur() {
                const p = norm(this.$refs.passport.value);
                // Blur warns once per passport (so Cancel → focus → click-away doesn't
                // re-open it); Save always re-checks until the user confirms.
                if (!p || p === this.confirmedPassport || p === this.matchedPassport || this.open) return;
                const m = await this.lookup(p);
                // Ignore stale results if the field changed meanwhile.
                if (m && m.length && norm(this.$refs.passport.value) === p && !this.open) this.show(p, m, false);
            },
            async onSubmit(e) {
                const p = norm(this.$refs.passport.value);
                if (p && p === this.confirmedPassport) {
                    this.$refs.confirm.value = '1';
                    this.busy = true;
                    return; // already confirmed for this exact passport → native submit
                }
                this.$refs.confirm.value = '0';
                e.preventDefault();
                if (this.checking) return;
                this.checking = true;
                const m = p ? await this.lookup(p) : null;
                this.checking = false;
                if (m && m.length) { this.show(p, m, true); return; }
                this.busy = true;
                this.$root.submit(); // no match (or check unavailable → server re-checks)
            },
            cancel() {
                this.open = false;
                this.submitAfter = false;
                this.confirmedPassport = '';
                this.$nextTick(() => { this.$refs.passport.focus(); this.$refs.passport.select(); });
            },
            continueAnyway() {
                this.confirmedPassport = this.matchedPassport;
                this.open = false;
                if (this.submitAfter && norm(this.$refs.passport.value) === this.confirmedPassport) {
                    this.$refs.confirm.value = '1';
                    this.busy = true;
                    this.$root.submit();
                }
            },
        };
    }

    function doubleMofaPage() {
        return {
            q: '',
            editing: false, paying: false, viewing: false,
            updateBase: '{{ url('erp/double-mofa') }}',
            payBase: '{{ url('erp/double-mofa') }}',
            reverseBase: '{{ url('erp/double-mofa/receipt') }}',
            form: {}, payForm: {}, receipts: [], receiptName: '',
            openEdit(row) {
                this.form = Object.assign({}, row);
                for (const k in this.form) if (this.form[k] === null) this.form[k] = '';
                this.editing = true;
            },
            openPay(row) {
                this.payForm = { id: row.id, name: row.name, due: row.due, amount: '', note: '' };
                this.paying = true;
            },
            openReceipts(name, rows) {
                this.receiptName = name;
                this.receipts = rows;
                this.viewing = true;
            },
        };
    }
</script>
@endpush
@endsection
