@extends('layouts.erp-app')

@section('title', 'Agent Khata')
@section('page-title', 'Agent Khata')

@section('content')
<div>
    <x-ui.page-header title="Agent Khata" subtitle="Agent ledgers & balances" icon="bi-journal-bookmark" />

    {{-- Summary --}}
    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Receivable (agents owe agency)</div>
            <div class="mt-1 text-xl font-bold text-emerald-700">৳{{ number_format($receivable, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-rose-600">Payable (agency owes agents)</div>
            <div class="mt-1 text-xl font-bold text-rose-700">৳{{ number_format($payable, 2) }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Net Position</div>
            <div class="mt-1 text-xl font-bold {{ $net >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">৳{{ number_format($net, 2) }}</div>
        </div>
    </div>

    {{-- Print (full balances PDF, E7a) --}}
    <div class="mb-4 flex justify-end">
        <a href="{{ route('erp.agent-khata.print') }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="bi bi-printer"></i> Print
        </a>
    </div>

    {{-- Agents + balances --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Agent</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3 text-right">Opening</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-right">Khata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agents as $agent)
                        @php $bal = (float) ($balances[$agent->id] ?? 0); @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $agent->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $agent->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-slate-500">৳{{ number_format((float) $agent->opening_balance, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold {{ $bal > 0 ? 'text-emerald-700' : ($bal < 0 ? 'text-rose-600' : 'text-slate-400') }}">৳{{ number_format($bal, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('erp.agent-khata.show', $agent) }}" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100"><i class="bi bi-journal-text"></i> Open Khata</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400"><i class="bi bi-people mb-2 block text-2xl"></i>No agents yet. Add agents from the main app.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="mt-3 text-xs text-slate-400"><i class="bi bi-info-circle"></i> Positive balance = the agent owes the agency; negative = the agency owes the agent. Balances are computed live from the opening balance + ledger.</p>
</div>
@endsection
