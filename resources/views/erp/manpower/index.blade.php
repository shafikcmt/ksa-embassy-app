@extends('layouts.erp-app')

@section('title', 'Manpower Complete')
@section('page-title', 'Manpower Complete')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
@endphp

<div x-data="manpowerPage()">

    <x-ui.page-header title="Manpower Complete" subtitle="Completed manpower records" icon="bi-person-check" />

    {{-- Counter: manpower passports not yet delivered --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <div class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5">
            <i class="bi bi-truck text-amber-500"></i>
            <span class="text-sm font-semibold text-amber-700">Delivery বাকি</span>
            <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white">{{ $deliveryBaki }}</span>
        </div>
        <span class="text-xs text-slate-400">Passports completed but not yet delivered. Total manpower so far: {{ $totalManpower }}.</span>
    </div>

    {{-- E7a Print · E7c Export/Import --}}
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('erp.manpower.export') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-filetype-csv text-emerald-600"></i> Export CSV
        </a>
        <a href="{{ route('erp.manpower.print') }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
        @if(auth()->user()->isAgencyAdmin())
            <a href="{{ route('erp.manpower.import.form') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-upload"></i> Import CSV
            </a>
        @endif
    </div>

    {{-- Add entry --}}
    <form method="POST" action="{{ route('erp.manpower.store') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-plus-circle text-emerald-600"></i> Add Manpower Entry</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><label class="{{ $lbl }}">Passenger Name <span class="text-rose-500">*</span></label><input type="text" name="customer_name" value="{{ old('customer_name') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Passport Number <span class="text-rose-500">*</span></label><input type="text" name="passport_no" value="{{ old('passport_no') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">EC Number</label><input type="text" name="ec_number" value="{{ old('ec_number') }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">BMET Date <span class="text-rose-500">*</span></label><input type="date" name="completed_date" value="{{ old('completed_date', now()->format('Y-m-d')) }}" required class="{{ $inp }}"></div>
            <div>
                <label class="{{ $lbl }}">Agent</label>
                <select name="agent_id" class="{{ $inp }}">
                    <option value="">—</option>
                    @foreach($agents as $agent)<option value="{{ $agent->id }}" @selected(old('agent_id') == $agent->id)>{{ $agent->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-plus-lg"></i> Save</button>
        </div>

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
                            <div class="flex justify-between gap-3"><dt class="text-slate-400">Passenger Name</dt><dd class="font-medium text-slate-700">{{ old('customer_name') ?: '—' }}</dd></div>
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

    {{-- Live search (client-side; filters only the already-loaded, agency-scoped rows) --}}
    <div class="mb-4 max-w-md">
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-emerald-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-emerald-100">
            <i class="bi bi-search text-sm text-slate-400"></i>
            <input type="text" x-model.debounce.200ms="q" placeholder="Search passenger, passport, EC, agent…"
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
                        <th class="px-4 py-3">Total Serial</th>
                        <th class="px-4 py-3">BMET Date</th>
                        <th class="px-4 py-3">Passenger Name</th>
                        <th class="px-4 py-3">Passport Number</th>
                        <th class="px-4 py-3">EC Number</th>
                        <th class="px-4 py-3">Agent Name</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $e)
                        <tr class="hover:bg-slate-50" x-show="q === '' || $el.dataset.s.includes(q.toLowerCase())"
                            data-s="{{ \Illuminate\Support\Str::lower(trim(($e->customer_name ?? '').' '.($e->passport_no ?? '').' '.($e->ec_number ?? '').' '.($e->agent?->name ?? ''))) }}">
                            <td class="px-4 py-3 font-semibold text-slate-700">{{ $e->t_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $e->completed_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $e->customer_name }}</td>
                            <td class="px-4 py-3">{{ $e->passport_no }}</td>
                            <td class="px-4 py-3">{{ $e->ec_number ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $e->agent?->name ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @php $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition'; @endphp
                                <div class="flex flex-nowrap items-center justify-end gap-1.5">
                                    <button type="button" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'completed_date' => $e->completed_date->format('Y-m-d'),
                                                'customer_name' => $e->customer_name,
                                                'passport_no' => $e->passport_no,
                                                'ec_number' => $e->ec_number,
                                                'agent_id' => $e->agent_id,
                                            ]))"><i class="bi bi-pencil"></i> Edit</button>
                                    <form method="POST" action="{{ route('erp.manpower.destroy', $e) }}" onsubmit="return confirm('Delete this manpower entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No manpower entries yet.</td></tr>
                    @endforelse
                    <tr x-show="q !== '' && ![...$root.querySelectorAll('tr[data-s]')].some(r => r.dataset.s.includes(q.toLowerCase()))" x-cloak>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-search mb-2 block text-2xl"></i>No records match “<span x-text="q"></span>”.</td>
                    </tr>
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
                <div><label class="{{ $lbl }}">Passenger Name <span class="text-rose-500">*</span></label><input type="text" name="customer_name" x-model="form.customer_name" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Passport Number <span class="text-rose-500">*</span></label><input type="text" name="passport_no" x-model="form.passport_no" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">EC Number</label><input type="text" name="ec_number" x-model="form.ec_number" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">BMET Date <span class="text-rose-500">*</span></label><input type="date" name="completed_date" x-model="form.completed_date" required class="{{ $inp }}"></div>
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
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white"><i class="bi bi-check-lg"></i> Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function manpowerPage() {
        return {
            q: '',
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
