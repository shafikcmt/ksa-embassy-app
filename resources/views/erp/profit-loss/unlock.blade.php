@extends('layouts.erp-app')

@section('title', 'Profit / Loss — Locked')
@section('page-title', 'Profit / Loss')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
        <div class="mb-5 flex flex-col items-center text-center">
            <span class="mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 text-2xl text-white shadow-lg">
                <i class="bi bi-shield-lock"></i>
            </span>
            <h2 class="text-lg font-bold text-slate-900">Owner-only screen</h2>
            <p class="mt-1 text-sm text-slate-500">
                Profit / Loss is protected. Enter your security code to unlock it for the next 15 minutes.
            </p>
        </div>

        <form method="POST" action="{{ route('erp.profit-loss.unlock.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Security Code</label>
                <input type="password" name="code" autofocus autocomplete="off" required
                       class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                       placeholder="••••••••">
            </div>
            <button type="submit"
                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-gradient-to-r from-slate-800 to-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                <i class="bi bi-unlock"></i> Unlock Profit / Loss
            </button>
        </form>

        <p class="mt-4 text-center text-[0.7rem] text-slate-400">
            <i class="bi bi-info-circle"></i>
            The code is set by the agency admin in ERP Settings. Repeated wrong attempts are rate-limited.
        </p>
    </div>
</div>
@endsection
