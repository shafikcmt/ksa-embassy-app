@extends('layouts.erp-app')

@section('title', 'Delivery')
@section('page-title', 'Delivery')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $statusChip = [
        'pending'   => 'bg-slate-100 text-slate-600',
        'ready'     => 'bg-amber-100 text-amber-700',
        'delivered' => 'bg-emerald-100 text-emerald-700',
    ];
    $canReceive = auth()->user()->isAgencyAdmin() || auth()->user()->can('erp_receive_payment');
@endphp

<div x-data="deliveryPage()">

    <x-ui.page-header title="Delivery" subtitle="Passport delivery & payment collection" icon="bi-truck" />

<div data-ajax-region="stats" data-ajax-group="erp-delivery">
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
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Due</div>
            <div class="mt-1 text-xl font-bold text-rose-700">৳{{ number_format($totalDue, 2) }}</div>
        </div>
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
    {{-- E7a Print · E7e Export/Import --}}
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <button type="button" x-on:click="addOpen = !addOpen; if (addOpen) $nextTick(() => document.getElementById('erpAddForm')?.querySelector('input:not([type=hidden]), select')?.focus())"
                class="mr-auto inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
            <i class="bi" x-bind:class="addOpen ? 'bi-dash-lg' : 'bi-plus-lg'"></i> <span x-text="addOpen ? 'Close form' : 'Add Delivery'">Add Delivery</span>
        </button>
        <a data-ajax-region="export" data-ajax-group="erp-delivery" href="{{ route('erp.delivery.export', array_filter(['agent' => $agentFilter])) }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-filetype-csv text-emerald-600"></i> Export CSV
        </a>
        <a data-ajax-region="print" data-ajax-group="erp-delivery" href="{{ route('erp.delivery.print', array_filter(['agent' => $agentFilter])) }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
        @if(auth()->user()->isAgencyAdmin())
            <a href="{{ route('erp.delivery.import.form') }}"
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
    <form method="POST" action="{{ route('erp.delivery.store') }}" class="mb-6" id="erpAddForm" x-show="addOpen" x-cloak x-data="{ busy: false }" x-on:submit="busy = true">
        @csrf
        <x-erp.section icon="bi-plus-circle" title="Add Delivery">
            <x-erp.field label="Full Name" for="dl_full_name" required :name="$addErr ? 'full_name' : null">
                <input id="dl_full_name" type="text" name="full_name" value="{{ old('full_name') }}" required class="{{ $fi }} {{ $bd('full_name') }}">
            </x-erp.field>
            <x-erp.field label="Passport Number" for="deliveryPassport" required :name="$addErr ? 'passport_no' : null">
                <input type="text" id="deliveryPassport" name="passport_no" value="{{ old('passport_no') }}" required class="{{ $fi }} {{ $bd('passport_no') }}">
            </x-erp.field>
            <x-erp.field label="Date" for="dl_delivery_date" required :name="$addErr ? 'delivery_date' : null">
                <input id="dl_delivery_date" type="date" name="delivery_date" value="{{ old('delivery_date', now()->format('Y-m-d')) }}" required class="{{ $fi }} {{ $bd('delivery_date') }}">
            </x-erp.field>
            <x-erp.field label="Total Amount (৳)" for="dl_total_amount" required :name="$addErr ? 'total_amount' : null">
                <input id="dl_total_amount" type="number" step="0.01" min="0" name="total_amount" value="{{ old('total_amount', '0.00') }}" required class="{{ $fi }} {{ $bd('total_amount') }}">
            </x-erp.field>
            <x-erp.field label="Status" for="dl_status" required :name="$addErr ? 'status' : null">
                <select id="dl_status" name="status" class="{{ $fi }} {{ $bd('status') }}">
                    @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected(old('status', 'pending') === $key)>{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
            <x-erp.field label="Payment Method" for="dl_payment_method" :name="$addErr ? 'payment_method' : null">
                <select id="dl_payment_method" name="payment_method" class="{{ $fi }} {{ $bd('payment_method') }}">
                    <option value="">—</option>
                    @foreach($paymentMethods as $key => $label)<option value="{{ $key }}" @selected(old('payment_method') === $key)>{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
            <x-erp.field label="Reference" for="dl_reference" :name="$addErr ? 'reference' : null">
                <x-erp.select-search id="dl_reference" name="reference" :value="old('reference')" :agents="$agentOptions ?? []" />
            </x-erp.field>
            <div class="col-span-full flex justify-end">
                <button type="submit" x-bind:disabled="busy"
                        class="inline-flex min-w-[6.5rem] items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak aria-hidden="true"></i>
                    <span x-text="busy ? 'Saving…' : 'Save'">Save</span>
                </button>
            </div>
        </x-erp.section>

        {{-- Duplicate-passport confirm modal — auto-opens when the controller flashes
             'duplicate_warning'. Lives INSIDE the form so "Yes, Add Anyway" submits it
             with confirm_duplicate=1 (same mechanism as before, now in a modal). Reuses
             the app's standard modal shell (staff delete dialog / Receive Payment modal). --}}
        @if(session('duplicate_warning'))
            <div x-data="{ open: true }">
                <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
                    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/50" x-on:click="open = false"></div>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-slate-900">Possible Duplicate Entry</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ session('duplicate_warning') }}</p>
                            </div>
                        </div>

                        {{-- Read-only recap of what was entered --}}
                        <dl class="mt-4 space-y-1.5 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-400">Full Name</dt><dd class="font-medium text-slate-700">{{ old('full_name') ?: '—' }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-400">Passport Number</dt><dd class="font-medium text-slate-700">{{ old('passport_no') ?: '—' }}</dd></div>
                        </dl>

                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" x-on:click="open = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel / Edit Entry</button>
                            <button type="submit" name="confirm_duplicate" value="1" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-check-lg"></i> Yes, Add Anyway</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </form>
    </div>

    {{-- Agent filter (server-side: list, totals, print and CSV) + live search (client-side, loaded rows only) --}}
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
    <form data-ajax-filter data-ajax-group="erp-delivery" method="GET" action="{{ route('erp.delivery') }}" class="flex items-center gap-2">
        <label for="agentFilter" class="sr-only">Agent</label>
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-emerald-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
            <i class="bi bi-person-badge text-sm text-slate-400"></i>
            <select id="agentFilter" name="agent" class="h-11 min-w-[11rem] border-0 bg-transparent py-0 pl-0 pr-8 text-sm focus:ring-0">
                <option value="">All agents</option>
                @foreach($agentOptions as $opt)<option value="{{ $opt['name'] }}" @selected($agentFilter === $opt['name'])>{{ $opt['name'] }}</option>@endforeach
            </select>
        </div>
        @if($agentFilter !== '')
            <a data-ajax-link data-ajax-group="erp-delivery" href="{{ route('erp.delivery') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700" title="Clear agent filter"><i class="bi bi-x-circle"></i> Clear</a>
        @endif
    <noscript><button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Apply filters</button></noscript>
        </form>
    <div class="w-full max-w-md">
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-emerald-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
            <i class="bi bi-search text-sm text-slate-400"></i>
            <input type="text" x-model.debounce.200ms="q" placeholder="Search name, passport…"
                   class="h-11 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
            <button type="button" x-show="q" x-cloak @click="q = ''" title="Clear search"
                    class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-slate-400 transition hover:bg-slate-200 hover:text-slate-600">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    </div>
    </div>

<div data-ajax-region="results" data-ajax-group="erp-delivery">
    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Full Name</th>
                        <th class="px-4 py-3">Passport</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Due</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deliveries as $d)
                        @php
                            $reversedIds = $d->receipts->where('type', 'reversal')->pluck('reverses_id')->filter()->all();
                            $due = (float) $d->total_amount - (float) $d->paid_amount;
                            $receiptRows = $d->receipts->sortByDesc('received_at')->map(fn ($r) => [
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
                            data-s="{{ \Illuminate\Support\Str::lower(trim(($d->full_name ?? '').' '.($d->passport_no ?? ''))) }}">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $d->delivery_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $d->full_name }}</td>
                            <td class="px-4 py-3">{{ $d->passport_no }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">৳{{ number_format((float) $d->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-emerald-700">৳{{ number_format((float) $d->paid_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap {{ $due > 0 ? 'font-semibold text-rose-600' : 'text-slate-400' }}">৳{{ number_format($due, 2) }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusChip[$d->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $d->statusLabel() }}</span></td>
                            <td class="px-4 py-3">{{ $d->paymentMethodLabel() ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @php $pill = 'inline-flex items-center gap-1 whitespace-nowrap rounded-lg px-2 py-1 text-xs font-semibold ring-1 ring-inset transition'; @endphp
                                {{-- Buttons wrap inside a fixed width so every action stays visible (no sideways scroll). --}}
                                <div class="ml-auto flex flex-wrap items-center justify-end gap-1" style="min-width: 10rem; max-width: 13.5rem">
                                    @if($canReceive && $due > 0)
                                    <button type="button" class="{{ $pill }} bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100"
                                            x-on:click="openPay(@js(['id' => $d->id, 'name' => $d->full_name, 'due' => number_format($due, 2, '.', '')]))"><i class="bi bi-cash-coin"></i> Receive</button>
                                    @endif
                                    <button type="button" class="{{ $pill }} bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100"
                                            x-on:click="openReceipts(@js($d->full_name), @js($receiptRows))"><i class="bi bi-clock-history"></i> History</button>
                                    <button type="button" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"
                                            x-on:click="openEdit(@js([
                                                'id' => $d->id,
                                                'delivery_date' => $d->delivery_date->format('Y-m-d'),
                                                'full_name' => $d->full_name,
                                                'passport_no' => $d->passport_no,
                                                'payment_method' => $d->payment_method,
                                                'reference' => $d->reference,
                                                'total_amount' => number_format((float) $d->total_amount, 2, '.', ''),
                                                'status' => $d->status,
                                            ]))"><i class="bi bi-pencil"></i> Edit</button>
                                    <form method="POST" action="{{ route('erp.delivery.destroy', $d) }}" onsubmit="return confirm('Delete this delivery?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No deliveries yet.</td></tr>
                    @endforelse
                    <tr x-show="q !== '' && ![...$root.querySelectorAll('tr[data-s]')].some(r => r.dataset.s.includes(q.toLowerCase()))" x-cloak>
                        <td colspan="9" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-search mb-2 block text-2xl"></i>No records match “<span x-text="q"></span>”.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
    {{-- Receive Payment modal --}}
    <div x-show="paying" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="paying = false"></div>
        <form method="POST" x-bind:action="payBase + '/' + payForm.id + '/payment'" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            @csrf
            <h3 class="mb-1 text-base font-bold text-slate-900">Receive Payment</h3>
            <p class="mb-4 text-sm text-slate-500"><span x-text="payForm.name"></span> — due <span class="font-semibold text-rose-600">৳<span x-text="payForm.due"></span></span></p>
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

                        {{-- Reverse control: only for a live (non-reversed) payment --}}
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

    {{-- Edit modal (classic POST → PUT erp.delivery.update) --}}
    @php $eb = \App\Support\ErpForm::INPUT . ' ' . \App\Support\ErpForm::BORDER_OK; @endphp
    <x-erp.modal show="editing" close="editing = false" icon="bi-truck" title-id="delivery-edit-title"
                 title="'Edit Delivery'" edit="true" action="updateBase + '/' + form.id" method="PUT">
        <x-erp.section icon="bi-person-vcard" title="Candidate Information">
            <x-erp.field label="Full Name" for="dle_full_name" required>
                <input id="dle_full_name" type="text" name="full_name" x-model="form.full_name" required class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Passport Number" for="dle_passport_no" required>
                <input id="dle_passport_no" type="text" name="passport_no" x-model="form.passport_no" required class="{{ $eb }}">
            </x-erp.field>
        </x-erp.section>
        <x-erp.section icon="bi-truck" title="Delivery Details">
            <x-erp.field label="Date" for="dle_delivery_date" required>
                <input id="dle_delivery_date" type="date" name="delivery_date" x-model="form.delivery_date" required class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Total Amount (৳)" for="dle_total_amount" required>
                <input id="dle_total_amount" type="number" step="0.01" min="0" name="total_amount" x-model="form.total_amount" required class="{{ $eb }}">
            </x-erp.field>
            <x-erp.field label="Status" for="dle_status" required>
                <select id="dle_status" name="status" x-model="form.status" class="{{ $eb }}">
                    @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
            <x-erp.field label="Payment Method" for="dle_payment_method">
                <select id="dle_payment_method" name="payment_method" x-model="form.payment_method" class="{{ $eb }}">
                    <option value="">—</option>
                    @foreach($paymentMethods as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
        </x-erp.section>
        <x-erp.section icon="bi-journal-text" title="Additional Info" cols="2">
            <x-erp.field label="Reference" for="dle_reference">
                <x-erp.select-search id="dle_reference" name="reference" x-model="form.reference" :agents="$agentOptions ?? []" />
            </x-erp.field>
        </x-erp.section>
    </x-erp.modal>
</div>

@include('erp.partials._passport-autofill', [
    'passportId' => 'deliveryPassport',
    'map' => ['full_name' => 'full_name', 'reference' => 'reference'],
])

@push('scripts')
<script>
    function deliveryPage() {
        return {
            q: '',
            editing: false, paying: false, viewing: false,
            updateBase: '{{ url('erp/delivery') }}',
            payBase: '{{ url('erp/delivery') }}',
            reverseBase: '{{ url('erp/delivery/receipt') }}',
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
