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
        <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3.5">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-50 text-xl text-brand-600">
                    <i class="bi bi-patch-check-fill"></i>
                </span>
                <div>
                    <div class="text-base font-bold text-slate-900">{{ $agency->name }}</div>
                    <div class="text-xs text-slate-500">RL Number: {{ $agency->rl_number ?: '—' }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge>
                @if($daysRemaining !== null && $daysRemaining >= 0)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', $daysRemaining) }} left</span>
                @elseif($daysRemaining !== null && $daysRemaining < 0)
                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600">expired {{ abs($daysRemaining) }} {{ \Illuminate\Support\Str::plural('day', abs($daysRemaining)) }} ago</span>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{-- License Details --}}
    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
            <i class="bi bi-card-list text-brand-600"></i> License Details
        </div>
        @php $dt = 'text-[0.7rem] font-semibold uppercase tracking-wider text-slate-400'; @endphp
        <dl class="grid grid-cols-1 gap-x-8 gap-y-5 px-5 py-5 sm:grid-cols-2">
            <div>
                <dt class="{{ $dt }}">License Holder Name</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $agency->owner_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $dt }}">Company</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $agency->name }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="{{ $dt }}">Address</dt>
                <dd class="mt-1 text-sm font-medium text-slate-700">{{ $agency->address ?: '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $dt }}">RL Number</dt>
                <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $agency->rl_number ?: '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $dt }}">System License No.</dt>
                <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $agency->license_number ?: '—' }}</dd>
            </div>
            <div>
                <dt class="{{ $dt }}">License Expiry Date</dt>
                <dd class="mt-1 text-sm font-semibold {{ $agency->license_expiry_date?->isPast() ? 'text-rose-600' : 'text-slate-800' }}">
                    {{ $agency->license_expiry_date?->format('d M Y') ?? '—' }}
                </dd>
            </div>
            <div>
                <dt class="{{ $dt }}">Days Remaining</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-800">
                    @if($daysRemaining === null)
                        —
                    @elseif($daysRemaining < 0)
                        <span class="text-rose-600">Expired</span>
                    @else
                        {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', $daysRemaining) }}
                    @endif
                </dd>
            </div>
        </dl>
    </x-ui.card>

    <p class="mt-4 text-xs text-slate-400">
        <i class="bi bi-info-circle"></i>
        To renew your license or update these details, please contact your system administrator.
    </p>

</div>
@endsection
