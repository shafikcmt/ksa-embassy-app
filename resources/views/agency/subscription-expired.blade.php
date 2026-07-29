@extends('layouts.agency-app')
@section('title', 'Subscription Expired')
@section('page-title', 'Subscription Renewal')

@section('content')
<div class="mx-auto max-w-2xl py-2">

    {{-- Status Banner --}}
    <div class="mb-5 text-center">
        <div class="mx-auto mb-3 grid place-items-center rounded-full bg-rose-50" style="height:72px;width:72px;">
            <i class="bi bi-slash-circle text-3xl text-rose-600"></i>
        </div>
        <h1 class="mb-1 text-xl font-bold tracking-tight text-slate-900">Subscription Expired</h1>
        <p class="text-sm text-slate-500">
            You can still view existing data. Creating new records and generating PDFs requires an active subscription.
        </p>
    </div>

    {{-- Last subscription info --}}
    @if($lastSubscription)
        <x-ui.card class="mb-4 border-l-4 border-l-rose-400">
            <div class="grid grid-cols-3 gap-2 p-4 text-center">
                <div>
                    <div class="text-[0.7rem] uppercase tracking-wide text-slate-400">Last Plan</div>
                    <div class="font-bold text-slate-800">{{ $lastSubscription->plan->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-[0.7rem] uppercase tracking-wide text-slate-400">Expired On</div>
                    <div class="font-bold text-rose-600">{{ $lastSubscription->end_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-[0.7rem] uppercase tracking-wide text-slate-400">Plan Price</div>
                    <div class="font-bold text-slate-800">
                        @if($lastSubscription->plan?->price)
                            {{ $lastSubscription->plan->priceLabel('/period') }}
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Renewal form --}}
    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
            <i class="bi bi-send text-brand-600"></i> Request Renewal
        </div>
        <div class="p-5">
            <p class="mb-3 text-sm text-slate-500">
                Fill in the form below and our team will contact you to process your renewal.
            </p>
            <form method="POST" action="{{ route('subscription.renew-request') }}">
                @csrf
                <x-ui.field label="Message" name="message" hint="Optional — mention preferred plan, number of candidates, or any special requirements." class="mb-4">
                    <textarea name="message" rows="3"
                        placeholder="Mention preferred plan, number of candidates, or any special requirements…"
                        class="w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400"></textarea>
                </x-ui.field>
                <x-ui.button type="submit" class="w-full cursor-pointer"><i class="bi bi-send"></i> Send Renewal Request</x-ui.button>
            </form>
        </div>
    </x-ui.card>

    {{-- Navigation links --}}
    <div class="mt-4 flex items-center justify-center gap-4 text-sm">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-1 text-slate-500 transition-colors hover:text-slate-700">
            <i class="bi bi-house"></i> Dashboard
        </a>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="flex cursor-pointer items-center gap-1 text-slate-500 transition-colors hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>

</div>
@endsection
