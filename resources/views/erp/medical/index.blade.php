@extends('layouts.erp-app')

@section('title', 'Medical')
@section('page-title', 'Medical')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $statusTone = [
        'pending'      => 'bg-slate-100 text-slate-600',
        'process'      => 'bg-blue-100 text-blue-700',
        'under_review' => 'bg-purple-100 text-purple-700',
        'fit'          => 'bg-emerald-100 text-emerald-700',
        'unfit'        => 'bg-rose-100 text-rose-700',
    ];
@endphp

<div x-data="medicalPage()">

    <x-ui.page-header title="Medical" subtitle="Track medical checks & status" icon="bi-heart-pulse" />

    {{-- E7a Print · E7c Export/Import --}}
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('erp.medical.export') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-filetype-csv text-emerald-600"></i> Export CSV
        </a>
        <a href="{{ route('erp.medical.print') }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
        @if(auth()->user()->isAgencyAdmin())
            <a href="{{ route('erp.medical.import.form') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-upload"></i> Import CSV
            </a>
        @endif
    </div>

    {{-- Add entry --}}
    <form method="POST" action="{{ route('erp.medical.store') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
        @csrf
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-plus-circle text-emerald-600"></i> Add Medical Entry</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><label class="{{ $lbl }}">Full Name <span class="text-rose-500">*</span></label><input type="text" name="full_name" value="{{ old('full_name') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Father Name <span class="text-rose-500">*</span></label><input type="text" name="father_name" value="{{ old('father_name') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Passport No <span class="text-rose-500">*</span></label><input type="text" name="passport_no" value="{{ old('passport_no') }}" required class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Medical Center Name</label><input type="text" name="medical_center_name" value="{{ old('medical_center_name') }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Medical Code</label><input type="text" name="medical_code" value="{{ old('medical_code') }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Issue Date</label><input type="date" name="medical_issue_date" value="{{ old('medical_issue_date') }}" class="{{ $inp }}"></div>
            <div><label class="{{ $lbl }}">Expire Date</label><input type="date" name="medical_expire_date" value="{{ old('medical_expire_date') }}" class="{{ $inp }}"></div>
            <div>
                <label class="{{ $lbl }}">Status <span class="text-rose-500">*</span></label>
                <select name="medical_status" required class="{{ $inp }}">
                    @foreach($statuses as $val => $label)<option value="{{ $val }}" @selected(old('medical_status', 'pending') === $val)>{{ $label }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-plus-lg"></i> Save</button>
        </div>
    </form>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Father</th>
                        <th class="px-4 py-3">Passport</th>
                        <th class="px-4 py-3">Medical Center</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Issue Date</th>
                        <th class="px-4 py-3">Expire Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $e->full_name }}</td>
                            <td class="px-4 py-3">{{ $e->father_name }}</td>
                            <td class="px-4 py-3">{{ $e->passport_no }}</td>
                            <td class="px-4 py-3">{{ $e->medical_center_name ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $e->medical_code ?: '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ optional($e->medical_issue_date)->format('d M Y') ?: '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ optional($e->medical_expire_date)->format('d M Y') ?: '—' }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusTone[$e->medical_status] ?? 'bg-slate-100 text-slate-600' }}">{{ $e->statusLabel() }}</span></td>
                            <td class="px-4 py-3">
                                @php $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition'; @endphp
                                <div class="flex flex-nowrap items-center justify-end gap-1.5">
                                    <button type="button" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"
                                            x-on:click="openEdit(@js([
                                                'id' => $e->id,
                                                'full_name' => $e->full_name,
                                                'father_name' => $e->father_name,
                                                'passport_no' => $e->passport_no,
                                                'medical_center_name' => $e->medical_center_name,
                                                'medical_code' => $e->medical_code,
                                                'medical_issue_date' => $e->medical_issue_date?->format('Y-m-d'),
                                                'medical_expire_date' => $e->medical_expire_date?->format('Y-m-d'),
                                                'medical_status' => $e->medical_status,
                                            ]))"><i class="bi bi-pencil"></i> Edit</button>
                                    <form method="POST" action="{{ route('erp.medical.destroy', $e) }}" onsubmit="return confirm('Delete this medical entry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-inbox mb-2 block text-2xl"></i>No medical entries yet.</td></tr>
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
            <h3 class="mb-4 text-base font-bold text-slate-900">Edit Medical Entry</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="{{ $lbl }}">Full Name <span class="text-rose-500">*</span></label><input type="text" name="full_name" x-model="form.full_name" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Father Name <span class="text-rose-500">*</span></label><input type="text" name="father_name" x-model="form.father_name" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Passport No <span class="text-rose-500">*</span></label><input type="text" name="passport_no" x-model="form.passport_no" required class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Medical Center Name</label><input type="text" name="medical_center_name" x-model="form.medical_center_name" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Medical Code</label><input type="text" name="medical_code" x-model="form.medical_code" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Issue Date</label><input type="date" name="medical_issue_date" x-model="form.medical_issue_date" class="{{ $inp }}"></div>
                <div><label class="{{ $lbl }}">Expire Date</label><input type="date" name="medical_expire_date" x-model="form.medical_expire_date" class="{{ $inp }}"></div>
                <div>
                    <label class="{{ $lbl }}">Status <span class="text-rose-500">*</span></label>
                    <select name="medical_status" x-model="form.medical_status" required class="{{ $inp }}">
                        @foreach($statuses as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
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
    function medicalPage() {
        return {
            editing: false,
            updateBase: '{{ url('erp/medical') }}',
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
