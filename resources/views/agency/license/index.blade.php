@extends('layouts.agency-app')
@section('title', 'License')
@section('page-title', 'License')

@section('content')
<div class="mx-auto max-w-3xl">

    <x-ui.page-header
        title="License Information"
        subtitle="Your agency license details and status"
        icon="bi-patch-check" />

    {{-- Status banner --}}
    <x-ui.card class="mb-5">
        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-lg text-brand-600">
                    <i class="bi bi-patch-check-fill"></i>
                </span>
                <div>
                    <div class="text-sm font-bold text-slate-900">{{ $agency->name }}</div>
                    <div class="text-xs text-slate-500">RL Number: {{ $agency->rl_number ?: '—' }}</div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge>
                @if($daysRemaining !== null && $daysRemaining >= 0)
                    <span class="text-xs text-slate-500">({{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', $daysRemaining) }} left)</span>
                @elseif($daysRemaining !== null && $daysRemaining < 0)
                    <span class="text-xs font-semibold text-rose-600">(expired {{ abs($daysRemaining) }} {{ \Illuminate\Support\Str::plural('day', abs($daysRemaining)) }} ago)</span>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{-- License Details --}}
    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
            <i class="bi bi-card-list text-brand-600"></i> License Details
        </div>
        <dl class="divide-y divide-slate-100 px-5 py-2 text-sm">
            <x-ui.dl-row label="License Holder Name">{{ $agency->owner_name ?: '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="Company">{{ $agency->name }}</x-ui.dl-row>
            <x-ui.dl-row label="Address">{{ $agency->address ?: '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="RL Number">{{ $agency->rl_number ?: '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="System License No.">{{ $agency->license_number ?: '—' }}</x-ui.dl-row>
            <x-ui.dl-row label="License Expiry Date">
                <span class="{{ $agency->license_expiry_date?->isPast() ? 'font-bold text-rose-600' : '' }}">
                    {{ $agency->license_expiry_date?->format('d M Y') ?? '—' }}
                </span>
            </x-ui.dl-row>
            <x-ui.dl-row label="Days Remaining">
                @if($daysRemaining === null)
                    —
                @elseif($daysRemaining < 0)
                    <span class="font-semibold text-rose-600">Expired</span>
                @else
                    {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', $daysRemaining) }}
                @endif
            </x-ui.dl-row>
        </dl>
    </x-ui.card>

    <p class="mt-4 text-xs text-slate-400">
        <i class="bi bi-info-circle"></i>
        To renew your license or update these details, please contact your system administrator.
    </p>

</div>
@endsection
