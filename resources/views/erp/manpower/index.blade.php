@extends('layouts.erp-app')

@section('title', 'Manpower Complete')
@section('page-title', 'Manpower Complete')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
@endphp

<div x-data="manpowerPage()">

    {{-- Counter: manpower passports not yet delivered --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <div class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5">
            <i class="bi bi-truck text-amber-500"></i>
            <span class="text-sm font-semibold text-amber-700">Delivery বাকি</span>
            <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">{{ $deliveryBaki }}</span>
        </div>
        <span class="text-xs text-slate-400">Passports completed but not yet delivered. Total manpower so far: {{ $totalManpower }}.</span>
    </div>

    {{-- Print (full list PDF, E7a) --}}
    <div class="mb-4 flex justify-end">
        <a href="{{ route('erp.manpower.print') }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
    </div>

    {{-- Add entry --}}
    <form method="POST" action="{{ route('erp.manpower.store') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-plus-circle text-emerald-600"></i> Add Manpower Entry</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><label class="{{ $lbl }}">Date <span class="text-rose-500">*</span></label><input type="date" name="completed_date" value="{{ old('completed_date', now()->format('Y-m-d')) }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Customer Name <span class="text-rose-500">*</span></label><input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Passport Number <span class="text-rose-500">*</span></label><input type="text" name="passport_no" value="{{ old('passport_no') }}" required class="{{ $inp }}"></div>
            <div>
                <label class="{{ $lbl }}">Agent</label>
                <select name="agent_id" class="{{ $inp }}">
                    <option value="">—</option>
                    @foreach($agents as $agent)<option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)>{{ $agent->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-plus-lg"></i> Add Entry</button>
        </div>
    </form>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Total Serial</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Customer Name</th>
                        <th class="px-4 py-3">Passport Number</th>
                        <th class="px-4 py-3">Agent Name</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-slate-700">{{ $e->t_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->completed_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $e->customer_name }}</td>
                            <td class="px-4 py-3">{{ $e->passport_no }}</td>
                            <td class="px-4 py-3">{{ $e->agent?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-emerald-600"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'completed_date' => $e->completed_date->format('Y-m-d'),
                                                'customer_name' => $e->customer_name,
                                                'passport_no' => $e->passport_no,
                                                'agent_id' => $e->agent_id,
                                            ]))"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" action="{{ route('erp.manpower.destroy', $e) }}" onsubmit="return confirm('Delete this manpower entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No manpower entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit modal --}}
    <div x-show="editing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="editing = false"></div>
        <form method="POST" x-bind:action="updateBase + '/' + form.id" class="relative w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl">
            @csrf @method('PUT')
            <h3 class="mb-4 text-base font-bold text-slate-900">Edit Manpower Entry</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="{{ $lbl }}">Date <span class="text-rose-500">*</span></label><input type="date" name="completed_date" x-model="form.completed_date" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Customer Name <span class="text-rose-500">*</span></label><input type="text" name="customer_name" x-model="form.customer_name" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Passport Number <span class="text-rose-500">*</span></label><input type="text" name="passport_no" x-model="form.passport_no" required class="{{ $inp }}"></div>
                <div>
                    <label class="{{ $lbl }}">Agent</label>
                    <select name="agent_id" x-model="form.agent_id" class="{{ $inp }}">
                        <option value="">—</option>
                        @foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
                    </select>
                </div>
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
    function manpowerPage() {
        return {
            editing: false,
            updateBase: '{{ url('erp/manpower') }}',
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
