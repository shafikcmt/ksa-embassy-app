@extends('layouts.erp-app')

@section('title', 'MOFA Entry')
@section('page-title', 'MOFA Entry')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
@endphp

<div x-data="mofaPage()">

    {{-- Counter --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <div class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5">
            <i class="bi bi-hourglass-split text-amber-600"></i>
            <span class="text-sm font-semibold text-amber-800">Stamping বাকি</span>
            <span class="grid h-6 min-w-6 place-items-center rounded-full bg-amber-600 px-2 text-xs font-bold text-white">{{ $stampingBaki }}</span>
        </div>
        <span class="text-xs text-slate-400">MOFA passports not yet stamped.</span>
    </div>

    {{-- Add entry --}}
    <form method="POST" action="{{ route('erp.mofa.store') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-plus-circle text-emerald-600"></i> Add MOFA Entry</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="{{ $lbl }}">MOFA Date <span class="text-rose-500">*</span></label>
                <input type="date" name="mofa_date" value="{{ old('mofa_date', now()->format('Y-m-d')) }}" required class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">MOFA Number</label>
                <input type="text" name="mofa_number" value="{{ old('mofa_number') }}" class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Visa Serial</label>
                <input type="text" name="visa_serial" value="{{ old('visa_serial') }}" class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" required class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Passport No <span class="text-rose-500">*</span></label>
                <input type="text" name="passport_no" value="{{ old('passport_no') }}" required class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Reference Name</label>
                <input type="text" name="reference_name" value="{{ old('reference_name') }}" class="{{ $inp }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Payment Method</label>
                <select name="payment_method" class="{{ $inp }}">
                    <option value="">—</option>
                    @foreach($paymentMethods as $val => $label)
                        <option value="{{ $val }}" @selected(old('payment_method') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">WhatsApp Number</label>
                <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}" class="{{ $inp }}">
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <label class="{{ $lbl }}">Payment Note</label>
                <input type="text" name="payment_note" value="{{ old('payment_note') }}" maxlength="1000" class="{{ $inp }}">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-plus-lg"></i> Add Entry
            </button>
        </div>
    </form>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Y#</th>
                        <th class="px-4 py-3">M#</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">MOFA #</th>
                        <th class="px-4 py-3">Visa Serial</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Passport</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Payment</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-slate-700">{{ $e->y_no }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $e->m_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->mofa_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $e->mofa_number ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $e->visa_serial ?: '—' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $e->full_name }}</td>
                            <td class="px-4 py-3">{{ $e->passport_no }}</td>
                            <td class="px-4 py-3">{{ $e->reference_name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($e->payment_method)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $e->paymentMethodLabel() }}</span>
                                @else — @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-emerald-600"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'mofa_date' => $e->mofa_date->format('Y-m-d'),
                                                'mofa_number' => $e->mofa_number,
                                                'visa_serial' => $e->visa_serial,
                                                'full_name' => $e->full_name,
                                                'passport_no' => $e->passport_no,
                                                'reference_name' => $e->reference_name,
                                                'payment_method' => $e->payment_method,
                                                'whatsapp_number' => $e->whatsapp_number,
                                                'payment_note' => $e->payment_note,
                                            ]))"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('erp.mofa.destroy', $e) }}" onsubmit="return confirm('Delete this MOFA entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-12 text-center text-slate-400">
                            <i class="bi bi-inbox mb-2 block text-2xl"></i>No MOFA entries yet. Add your first one above.
                        </td></tr>
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
            <h3 class="mb-4 text-base font-bold text-slate-900">Edit MOFA Entry</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="{{ $lbl }}">MOFA Date <span class="text-rose-500">*</span></label><input type="date" name="mofa_date" x-model="form.mofa_date" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">MOFA Number</label><input type="text" name="mofa_number" x-model="form.mofa_number" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Visa Serial</label><input type="text" name="visa_serial" x-model="form.visa_serial" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Full Name <span class="text-rose-500">*</span></label><input type="text" name="full_name" x-model="form.full_name" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Passport No <span class="text-rose-500">*</span></label><input type="text" name="passport_no" x-model="form.passport_no" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Reference Name</label><input type="text" name="reference_name" x-model="form.reference_name" class="{{ $inp }}"></div>
                <div>
                    <label class="{{ $lbl }}">Payment Method</label>
                    <select name="payment_method" x-model="form.payment_method" class="{{ $inp }}">
                        <option value="">—</option>
                        @foreach($paymentMethods as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div><label class="{{ $lbl }}">WhatsApp Number</label><input type="text" name="whatsapp_number" x-model="form.whatsapp_number" class="{{ $inp }}"></div>
                <div class="sm:col-span-2 lg:col-span-3"><label class="{{ $lbl }}">Payment Note</label><input type="text" name="payment_note" x-model="form.payment_note" maxlength="1000" class="{{ $inp }}"></div>
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
    function mofaPage() {
        return {
            editing: false,
            updateBase: '{{ url('erp/mofa') }}',
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
