@extends('layouts.erp-app')

@section('title', $entry->full_name . ' — Medical')
@section('page-title', 'Medical')

@section('content')
@php
    $btn = 'inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition';
    $d = fn ($date) => $date?->format('d M Y') ?? '—';
    $expired = $entry->medical_expire_date?->isPast();
    $fields = [
        'Candidate' => [
            ['Full Name', $entry->full_name],
            ["Father's Name", $entry->father_name],
            ['Passport No', $entry->passport_no],
            ['Date of Birth', $d($entry->date_of_birth)],
            ['Age', $entry->currentAge() ?? '—'],
            ['Mobile No', $entry->mobile_no ?: '—'],
        ],
        'Medical' => [
            ['Medical Center', $entry->medical_center_name ?: '—'],
            ['Country', $entry->country ?: '—'],
            ['Code No', $entry->medical_code ?: '—'],
            ['Issue Date (M. Issu. D.)', $d($entry->medical_issue_date)],
            ['Expiry Date (M. E. D.)', $d($entry->medical_expire_date)],
            ['Reference', $entry->reference ?: '—'],
        ],
    ];
@endphp

<x-ui.page-header :title="$entry->full_name" :subtitle="'Passport ' . $entry->passport_no" icon="bi-heart-pulse">
    <x-slot:actions>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('erp.medical') }}" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-arrow-left"></i> All entries</a>
            <a href="{{ route('erp.medical.edit', $entry) }}" class="{{ $btn }} border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100"><i class="bi bi-pencil"></i> Edit</a>
            <a href="{{ route('erp.medical.print-pdf', $entry) }}" target="_blank" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-printer"></i> Print</a>
            <a href="{{ route('erp.medical.print-pdf', [$entry, 'download' => 1]) }}" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-download"></i> PDF</a>
        </div>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4">
            <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $entry->statusTone() }}">{{ $entry->statusLabel() }}</span>
            @if($entry->medical_expire_date)
                <span class="text-sm {{ $expired ? 'font-semibold text-rose-600' : 'text-slate-500' }}">
                    <i class="bi {{ $expired ? 'bi-exclamation-triangle' : 'bi-calendar-check' }}"></i>
                    {{ $expired ? 'Medical expired ' . $entry->medical_expire_date->diffForHumans() : 'Valid until ' . $d($entry->medical_expire_date) }}
                </span>
            @endif
        </div>

        @foreach($fields as $section => $rows)
            <div class="rounded-2xl border border-slate-200 bg-white">
                <h3 class="border-b border-slate-100 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $section }}</h3>
                <dl class="grid gap-x-6 gap-y-4 px-5 py-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($rows as [$label, $value])
                        <div>
                            <dt class="text-xs text-slate-400">{{ $label }}</dt>
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endforeach

        @if($entry->remarks)
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Remarks</div>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $entry->remarks }}</p>
            </div>
        @endif
    </div>

    <aside class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-info-circle text-emerald-600"></i> Record</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">HR Profile</dt>
                    <dd class="text-right font-medium">
                        @if($entry->hrProfile)
                            <a href="{{ route('hr.show', $entry->hrProfile) }}" class="text-emerald-700 hover:underline">{{ $entry->hrProfile->file_number ?: $entry->hrProfile->full_name_en }}</a>
                        @else
                            <span class="text-slate-400">Not linked</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Added by</dt><dd class="text-right font-medium">{{ $entry->createdBy->name ?? '—' }}<div class="text-xs text-slate-400">{{ $entry->created_at?->format('d M Y, g:i A') }}</div></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Last updated</dt><dd class="text-right font-medium">{{ $entry->updatedBy->name ?? '—' }}<div class="text-xs text-slate-400">{{ $entry->updated_at?->format('d M Y, g:i A') }}</div></dd></div>
            </dl>

            <form method="POST" action="{{ route('erp.medical.destroy', $entry) }}" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm('Delete this medical entry? An admin can restore it later.')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete entry</button>
            </form>
        </div>
    </aside>
</div>
@endsection
