@extends('layouts.erp-app')

@section('title', $entry->full_name . ' — Visa Stamping')
@section('page-title', 'Visa Stamping')

@section('content')
@php
    $btn = 'inline-flex items-center gap-1.5 rounded-md px-3.5 py-2 text-sm font-semibold transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500';
    $d = fn ($date) => $date?->format('d-M-Y') ?? '—';
    $v = fn ($value) => filled($value) ? $value : '—';
    $sections = [
        ['Passenger', 'bi-person-vcard', [
            ['Passenger Name', $entry->full_name],
            ["Father's Name", $v($entry->father_name)],
            ["Mother's Name", $v($entry->mother_name)],
            ['PP No', $entry->passport_no],
            ['D.O.B', $d($entry->date_of_birth)],
            ['Age', $v($entry->age)],
        ]],
        ['Visa & MOFA', 'bi-file-earmark-text', [
            ['Visa No', $v($entry->visa_number)],
            ['Id No', $v($entry->id_number)],
            ['Mofa No', $v($entry->mofa_number)],
            ['Mofa Date', $d($entry->mofa_date)],
            ['Issu Visa No', $v($entry->issued_visa_number)],
            ['Issu Date', $d($entry->issued_date)],
            ['Exp. Date', $d($entry->expiry_date)],
        ]],
        ['Stamping', 'bi-postage', [
            ['Stamping Date', $d($entry->stamp_date)],
            ['Agent', $v($entry->agent?->name)],
            ['Reference', $v($entry->reference)],
        ]],
    ];
@endphp

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <a href="{{ route('erp.visa-stamping.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm font-semibold text-gray-500 hover:text-gray-800"><i class="bi bi-arrow-left" aria-hidden="true"></i> Visa Stamping</a>
        <h1 class="text-2xl font-bold tracking-tight text-blue-950">{{ $entry->full_name }}</h1>
        <p class="mt-1 font-mono text-sm text-gray-500">{{ $entry->passport_no }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('erp.visa-stamping.edit', $entry) }}" class="{{ $btn }} bg-blue-500 text-white shadow-sm hover:bg-blue-600 hover:shadow"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit</a>
        <a href="{{ route('erp.visa-stamping.print-pdf', $entry) }}" target="_blank" class="{{ $btn }} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"><i class="bi bi-printer" aria-hidden="true"></i> Print</a>
        <a href="{{ route('erp.visa-stamping.print-pdf', [$entry, 'download' => 1]) }}" class="{{ $btn }} border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"><i class="bi bi-download" aria-hidden="true"></i> PDF</a>
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-3">
    <div class="space-y-5 lg:col-span-2">
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {{ $entry->statusTone() }}">
                <i class="bi {{ $entry->statusIcon() }}" aria-hidden="true"></i>{{ $entry->statusLabel() }}
            </span>
            @if($entry->left_day !== null)
                <span class="inline-flex items-center gap-1 text-sm {{ $entry->leftDayIsLow() ? 'font-semibold text-red-600' : 'text-gray-600' }}">
                    <i class="bi {{ $entry->leftDayIsLow() ? 'bi-exclamation-triangle-fill' : 'bi-hourglass' }}" aria-hidden="true"></i>
                    Left Day: {{ $entry->left_day }}
                </span>
            @endif
            @if($entry->expiry_date?->isPast())
                <span class="text-sm font-semibold text-red-600"><i class="bi bi-calendar-x" aria-hidden="true"></i> Expired {{ $entry->expiry_date->diffForHumans() }}</span>
            @endif
        </div>

        @foreach($sections as [$title, $ico, $rows])
            <section class="rounded-xl border border-gray-200 bg-white shadow-sm" aria-labelledby="sec-{{ $loop->index }}">
                <h2 id="sec-{{ $loop->index }}" class="flex items-center gap-2 border-b border-gray-100 px-5 py-3 text-[13px] font-bold uppercase tracking-wide text-gray-500">
                    <i class="bi {{ $ico }} text-blue-500" aria-hidden="true"></i> {{ $title }}
                </h2>
                <dl class="grid gap-x-6 gap-y-4 px-5 py-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($rows as [$label, $value])
                        <div>
                            <dt class="text-xs text-gray-500">{{ $label }}</dt>
                            <dd class="mt-0.5 font-medium text-gray-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        @if($entry->remarks)
            <section class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm">
                <h2 class="text-[13px] font-bold uppercase tracking-wide text-gray-500">Remarks</h2>
                <p class="mt-1 whitespace-pre-line text-sm text-gray-800">{{ $entry->remarks }}</p>
            </section>
        @endif
    </div>

    <aside>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-900"><i class="bi bi-info-circle text-blue-500" aria-hidden="true"></i> Record</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-gray-500">HR Profile</dt>
                    <dd class="text-right font-medium">
                        @if($entry->hrProfile)
                            <a href="{{ route('hr.show', $entry->hrProfile) }}" class="text-blue-600 hover:underline">{{ $entry->hrProfile->file_number ?: $entry->hrProfile->full_name_en }}</a>
                        @else
                            <span class="text-gray-400">Not linked</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">Added by</dt><dd class="text-right font-medium">{{ $entry->createdBy->name ?? '—' }}<div class="text-xs text-gray-400">{{ $entry->created_at?->format('d-M-Y, g:i A') }}</div></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">Last updated</dt><dd class="text-right font-medium">{{ $entry->updatedBy->name ?? '—' }}<div class="text-xs text-gray-400">{{ $entry->updated_at?->format('d-M-Y, g:i A') }}</div></dd></div>
            </dl>
            <form method="POST" action="{{ route('erp.visa-stamping.destroy', $entry) }}" class="mt-4 border-t border-gray-100 pt-4" onsubmit="return confirm('Delete this visa stamping entry? An admin can restore it later.')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200 transition hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"><i class="bi bi-trash3" aria-hidden="true"></i> Delete entry</button>
            </form>
        </div>
    </aside>
</div>
@endsection
