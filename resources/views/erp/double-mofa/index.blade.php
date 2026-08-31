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
@endphp

<div x-data="doubleMofaPage()">

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

    {{-- Add entry --}}
    <form method="POST" action="{{ route('erp.double-mofa.store') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="mb-1 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-plus-circle text-emerald-600"></i> Add Double MOFA</h2>
        <p class="mb-4 text-xs text-slate-400">Billing defaults to the configured rate (৳{{ number_format($defaultRate, 2) }}) and is frozen on the record when saved.</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div><label class="{{ $lbl }}">Date <span class="text-rose-500">*</span></label><input type="date" name="mofa_date" value="{{ old('mofa_date', now()->format('Y-m-d')) }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Full Name <span class="text-rose-500">*</span></label><input type="text" name="full_name" value="{{ old('full_name') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Passport Number <span class="text-rose-500">*</span></label><input type="text" name="passport_no" value="{{ old('passport_no') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Visa Serial</label><input type="text" name="visa_serial" value="{{ old('visa_serial') }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Reference</label><input type="text" name="reference" value="{{ old('reference') }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Billing Amount (৳) <span class="text-rose-500">*</span></label><input type="number" step="0.01" min="0" name="billing_amount" value="{{ old('billing_amount', number_format($defaultRate, 2, '.', '')) }}" required class="{{ $inp }}"></div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-plus-lg"></i> Add Double MOFA</button>
        </div>
    </form>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Full Name</th>
                        <th class="px-4 py-3">Passport</th>
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
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->mofa_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $e->full_name }}</td>
                            <td class="px-4 py-3">{{ $e->passport_no }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">৳{{ number_format((float) $e->billing_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-emerald-700">৳{{ number_format((float) $e->paid_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap {{ $unpaid > 0 ? 'font-semibold text-rose-600' : 'text-slate-400' }}">৳{{ number_format($unpaid, 2) }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusChip[$e->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $e->statusLabel() }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" title="Receive payment" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-emerald-50 hover:text-emerald-600"
                                            x-on:click="openPay(@js(['id' => $e->id, 'name' => $e->full_name, 'due' => number_format($unpaid, 2, '.', '')]))"><i class="bi bi-cash-coin"></i></button>
                                    <button type="button" title="Payment history" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                                            x-on:click="openReceipts(@js($e->full_name), @js($receiptRows))"><i class="bi bi-clock-history"></i></button>
                                    <button type="button" title="Edit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-emerald-600"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'mofa_date' => $e->mofa_date->format('Y-m-d'),
                                                'full_name' => $e->full_name,
                                                'passport_no' => $e->passport_no,
                                                'visa_serial' => $e->visa_serial,
                                                'reference' => $e->reference,
                                                'billing_amount' => number_format((float) $e->billing_amount, 2, '.', ''),
                                            ]))"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('erp.double-mofa.destroy', $e) }}" onsubmit="return confirm('Delete this Double MOFA entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Delete" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No Double MOFA entries yet.</td></tr>
                    @endforelse
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
                            <span class="text-xs text-slate-400" x-text="r.at"></span>
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

    {{-- Edit modal --}}
    <div x-show="editing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="editing = false"></div>
        <form method="POST" x-bind:action="updateBase + '/' + form.id" class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
            @csrf @method('PUT')
            <h3 class="mb-4 text-base font-bold text-slate-900">Edit Double MOFA</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="{{ $lbl }}">Date <span class="text-rose-500">*</span></label><input type="date" name="mofa_date" x-model="form.mofa_date" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Full Name <span class="text-rose-500">*</span></label><input type="text" name="full_name" x-model="form.full_name" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Passport Number <span class="text-rose-500">*</span></label><input type="text" name="passport_no" x-model="form.passport_no" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Visa Serial</label><input type="text" name="visa_serial" x-model="form.visa_serial" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Reference</label><input type="text" name="reference" x-model="form.reference" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Billing Amount (৳) <span class="text-rose-500">*</span></label><input type="number" step="0.01" min="0" name="billing_amount" x-model="form.billing_amount" required class="{{ $inp }}"></div>
            </div>
            <p class="mt-3 text-xs text-slate-400"><i class="bi bi-info-circle"></i> Status and paid amount are derived from payments and cannot be edited here.</p>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" x-on:click="editing = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function doubleMofaPage() {
        return {
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
