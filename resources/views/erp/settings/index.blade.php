@extends('layouts.erp-app')

@section('title', 'ERP Settings')
@section('page-title', 'ERP Settings')

@section('content')
    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('erp.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Opening balance --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                    <i class="bi bi-wallet2 text-teal-600"></i> Opening Balance
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Historical starting balance carried into ERP totals. Use a negative value for a starting deficit.
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="opening_balance" class="mb-1 block text-sm font-semibold text-slate-700">Amount (৳)</label>
                        <input type="number" step="0.01" id="opening_balance" name="opening_balance"
                               value="{{ old('opening_balance', number_format((float) $settings->opening_balance, 2, '.', '')) }}"
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="opening_balance_note" class="mb-1 block text-sm font-semibold text-slate-700">Note <span class="font-normal text-slate-400">(optional)</span></label>
                        <input type="text" id="opening_balance_note" name="opening_balance_note" maxlength="255"
                               value="{{ old('opening_balance_note', $settings->opening_balance_note) }}"
                               placeholder="e.g. Balance carried from previous year"
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>
            </section>

            {{-- Profit / Loss privacy --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="flex items-center gap-2 text-base font-bold text-slate-900">
                    <i class="bi bi-shield-lock text-emerald-600"></i> Profit / Loss Privacy
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Protect the Profit/Loss page with an owner-only security code. Leave the code blank to keep the
                    current one. The code is stored securely (hashed) and never shown again.
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label for="pl_security_code" class="mb-1 block text-sm font-semibold text-slate-700">
                            Security Code
                            @if($settings->hasSecurityCode())
                                <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[0.65rem] font-semibold text-emerald-700"><i class="bi bi-check-circle-fill"></i> Set</span>
                            @else
                                <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-500">Not set</span>
                            @endif
                        </label>
                        <input type="password" id="pl_security_code" name="pl_security_code" autocomplete="new-password"
                               minlength="4" maxlength="100"
                               placeholder="{{ $settings->hasSecurityCode() ? 'Enter a new code to change it' : 'Set a code (min 4 characters)' }}"
                               class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    @if($settings->hasSecurityCode())
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="clear_security_code" value="1"
                                   class="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            <span>Remove the security code (make Profit/Loss unprotected)</span>
                        </label>
                    @endif

                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="pl_visible_to_all" value="1" @checked(old('pl_visible_to_all', $settings->pl_visible_to_all))
                               class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>
                            <span class="font-semibold">Visible to all staff</span>
                            <span class="block text-xs text-slate-500">When off, income / P&amp;L / balance stay owner-only on the dashboard.</span>
                        </span>
                    </label>
                </div>
            </section>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('erp.dashboard') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</a>
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                    <i class="bi bi-check-lg"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
@endsection
