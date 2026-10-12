@extends('layouts.agency-app')
@section('title', 'License')
@section('page-title', 'License')

@section('content')
@php
    $hasPayment = ! empty($payment); // payment rows exist
    $plural = fn (int $n) => $n . ' ' . \Illuminate\Support\Str::plural('day', $n);
    $expiry = $agency->license_expiry_date;

    // Status pill (top right): tone → classes.
    $pill = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'red'   => 'bg-rose-50 text-rose-700 ring-rose-200',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ][$statusTone] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
    $pillIcon = ['green' => 'bi-shield-check', 'amber' => 'bi-exclamation-triangle', 'red' => 'bi-x-octagon'][$statusTone] ?? 'bi-shield';

    // One detail row: muted label on the left, bold value on the right.
    $row = 'flex items-start justify-between gap-6 py-3.5';
    $lbl = 'shrink-0 text-sm text-slate-500';
    $val = 'text-right text-sm font-semibold text-slate-900';
    $mono = 'font-mono tracking-wide';
@endphp

<div class="mx-auto max-w-6xl">

    {{-- Header + status --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">License Information</h1>
            <p class="mt-0.5 text-sm text-slate-500">লাইসেন্স তথ্য ও নবায়ন</p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold ring-1 ring-inset {{ $pill }}">
            <i class="bi {{ $pillIcon }}" aria-hidden="true"></i>
            {{ $statusLabel }}
            @if($daysRemaining !== null && $daysRemaining >= 0)
                <span class="font-medium opacity-80">({{ $plural($daysRemaining) }} left)</span>
            @elseif($daysRemaining !== null)
                <span class="font-medium opacity-80">({{ $plural(abs($daysRemaining)) }} ago)</span>
            @endif
        </span>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 lg:items-start">

        {{-- License details --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="lic-details">
            <h2 id="lic-details" class="border-b border-slate-100 px-6 py-4 text-base font-bold text-slate-900">License Details</h2>
            <dl class="divide-y divide-slate-100 px-6 py-1">
                <div class="{{ $row }}"><dt class="{{ $lbl }}">License Holder Name</dt><dd class="{{ $val }}">{{ $agency->owner_name ?: '—' }}</dd></div>
                <div class="{{ $row }}"><dt class="{{ $lbl }}">Company</dt><dd class="{{ $val }}">{{ $agency->name }}</dd></div>
                <div class="{{ $row }}"><dt class="{{ $lbl }}">Address</dt><dd class="{{ $val }} max-w-[22rem] font-medium">{{ $agency->address ?: '—' }}</dd></div>
                <div class="{{ $row }}"><dt class="{{ $lbl }}">RL Number</dt><dd class="{{ $val }} {{ $mono }}">{{ $agency->rl_number ?: '—' }}</dd></div>
                <div class="{{ $row }}"><dt class="{{ $lbl }}">License Code</dt><dd class="{{ $val }} {{ $mono }}">{{ $agency->license_number ?: '—' }}</dd></div>
                <div class="{{ $row }}"><dt class="{{ $lbl }}">Last Renewal Date</dt><dd class="{{ $val }}">{{ $agency->license_renewed_at?->format('d M Y') ?? '—' }}</dd></div>
                <div class="{{ $row }}">
                    <dt class="{{ $lbl }}">License Expiry Date</dt>
                    <dd class="{{ $val }} {{ $expiry?->isPast() ? '!text-rose-600' : '' }}">{{ $expiry?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="{{ $row }}">
                    <dt class="{{ $lbl }}">Next Renewal Due Date</dt>
                    <dd class="{{ $val }}">{{ $expiry?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div class="{{ $row }}">
                    <dt class="{{ $lbl }}">Days Remaining</dt>
                    <dd class="{{ $val }}">
                        @if($daysRemaining === null)
                            —
                        @elseif($daysRemaining < 0)
                            <span class="text-rose-600">Expired</span>
                        @else
                            {{ $plural($daysRemaining) }}
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        {{-- Payment information — Super Admin configured; each row appears only when it is set --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="lic-pay">
            <h2 id="lic-pay" class="flex items-center gap-2 border-b border-slate-100 px-6 py-4 text-base font-bold text-slate-900">
                <i class="bi bi-credit-card text-slate-500" aria-hidden="true"></i> Payment Information
            </h2>
            <div class="space-y-3 px-6 py-5">
                @unless($hasPayment)
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                        <i class="bi bi-wallet2 text-3xl text-slate-400" aria-hidden="true"></i>
                        <p class="mt-2 text-sm font-semibold text-slate-700">Payment details have not been added yet</p>
                        <p class="mt-0.5 text-sm text-slate-500">পেমেন্টের তথ্য এখনো যোগ করা হয়নি। নবায়নের জন্য আপনার সিস্টেম অ্যাডমিনের সাথে যোগাযোগ করুন।</p>
                    </div>
                @endunless
                @isset($payment['amount'])
                    <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                        <span class="text-sm font-semibold text-emerald-800">Renewal Amount</span>
                        <span class="text-2xl font-extrabold tracking-tight text-emerald-700">৳ {{ $payment['amount'] }}</span>
                    </div>
                @endisset

                @foreach(['bkash' => ['bKash', 'bi-phone', 'text-pink-600'], 'nagad' => ['Nagad', 'bi-phone', 'text-orange-600']] as $key => [$name, $icon, $tone])
                    @isset($payment[$key])
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-5 py-3.5">
                            <span class="flex items-center gap-2.5 text-sm font-semibold text-slate-700"><i class="bi {{ $icon }} {{ $tone }}" aria-hidden="true"></i> {{ $name }}</span>
                            <span class="font-mono text-sm font-semibold tracking-wide text-slate-900">{{ $payment[$key] }}</span>
                        </div>
                    @endisset
                @endforeach

                @isset($payment['bank'])
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
                        <div class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-600"><i class="bi bi-bank" aria-hidden="true"></i> Bank Account Details</div>
                        <div class="whitespace-pre-line text-sm font-medium leading-relaxed text-slate-800">{{ $payment['bank'] }}</div>
                    </div>
                @endisset

                @isset($payment['instructions'])
                    <div class="pt-2">
                        <div class="mb-1 text-sm font-bold text-slate-900">Renewal Instructions</div>
                        <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $payment['instructions'] }}</p>
                    </div>
                @endisset
            </div>
        </section>
    </div>

    <p class="mt-5 flex items-start gap-1.5 text-sm text-slate-500">
        <i class="bi bi-info-circle mt-0.5" aria-hidden="true"></i>
        @if($hasPayment)
            Pay the renewal amount to any channel above, then contact your system administrator to confirm renewal.
        @else
            To renew your license or update these details, please contact your system administrator.
        @endif
    </p>
</div>
@endsection
