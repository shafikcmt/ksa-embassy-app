@extends('layouts.erp-app')

@section('title', 'Import MOFA')
@section('page-title', 'Import MOFA — CSV')

@section('content')
<div class="mx-auto max-w-5xl">

    <a href="{{ route('erp.mofa') }}"
       class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-700">
        <i class="bi bi-arrow-left"></i> Back to MOFA Entry
    </a>

    @isset($result)
        {{-- ── Preview (dry run — nothing written yet) ──────────────────── --}}
        @php
            $ok = $result['ok'];
            $badge = fn ($tone, $txt) => "<span class=\"rounded-full px-2 py-0.5 text-xs font-semibold $tone\">$txt</span>";
        @endphp

        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <div class="text-sm font-bold text-slate-900"><i class="bi bi-eye mr-1 text-slate-400"></i>Preview</div>
            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $result['total'] }} rows</span>
            <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">{{ $result['validCount'] }} valid</span>
            @if($result['errorCount'] > 0)
                <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700">{{ $result['errorCount'] }} invalid</span>
            @endif
            @if($result['noticeCount'] > 0)
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ $result['noticeCount'] }} notice{{ $result['noticeCount'] === 1 ? '' : 's' }}</span>
            @endif
        </div>

        @if(! $ok)
            <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <i class="bi bi-exclamation-octagon-fill mt-0.5 text-rose-500"></i>
                <span>This file has invalid rows. <strong>Nothing will be imported</strong> — all-or-nothing. Fix the rows below and re-upload the corrected file.</span>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2.5">Line</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">Date</th>
                            <th class="px-3 py-2.5">Name</th>
                            <th class="px-3 py-2.5">Passport</th>
                            <th class="px-3 py-2.5">Payment</th>
                            <th class="px-3 py-2.5">Messages</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($result['rows'] as $row)
                            <tr class="{{ $row['errors'] ? 'bg-rose-50/40' : '' }}">
                                <td class="px-3 py-2 text-slate-400">{{ $row['line'] }}</td>
                                <td class="px-3 py-2">
                                    @if($row['errors'])
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700"><i class="bi bi-x-circle"></i> Invalid</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700"><i class="bi bi-check-circle"></i> Valid</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-slate-700">{{ $row['attrs']['mofa_date'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-800">{{ $row['attrs']['full_name'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-700">{{ $row['attrs']['passport_no'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $row['attrs']['payment_method'] ?: '—' }}</td>
                                <td class="px-3 py-2">
                                    @foreach($row['errors'] as $err)
                                        <div class="text-xs text-rose-600"><i class="bi bi-dot"></i>{{ $err }}</div>
                                    @endforeach
                                    @foreach($row['notices'] as $note)
                                        <div class="text-xs text-amber-600"><i class="bi bi-info-circle"></i> {{ $note }}</div>
                                    @endforeach
                                    @if(! $row['errors'] && ! $row['notices'])
                                        <span class="text-xs text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
            <a href="{{ route('erp.mofa.import.form') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="bi bi-arrow-counterclockwise"></i> Upload a different file
            </a>
            @if($ok)
                <form method="POST" action="{{ route('erp.mofa.import') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                        <i class="bi bi-check2-circle"></i> Confirm import ({{ $result['total'] }})
                    </button>
                </form>
            @endif
        </div>
    @else
        {{-- ── Upload form ──────────────────────────────────────────────── --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="mb-1 flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-upload text-emerald-600"></i> Import MOFA entries from CSV</h2>
            <p class="mb-5 text-sm text-slate-500">
                Upload a CSV file. You'll see a <strong>preview</strong> of every row (valid / invalid) before anything is saved.
                Import is <strong>all-or-nothing</strong> — if any row is invalid, nothing is written.
            </p>

            <form method="POST" action="{{ route('erp.mofa.import.preview') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">CSV file (max 2 MB)</label>
                    <input type="file" name="file" accept=".csv,.txt" required
                           class="block w-full rounded-lg border border-slate-300 text-sm shadow-sm file:mr-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md">
                        <i class="bi bi-eye"></i> Preview
                    </button>
                    <a href="{{ route('erp.mofa.import.template') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i class="bi bi-download"></i> Download template
                    </a>
                </div>
            </form>

            <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-4 text-xs text-slate-600">
                <div class="mb-1 font-semibold text-slate-700">Columns (required marked *)</div>
                <code class="text-[0.7rem]">mofa_date*, mofa_number, visa_serial, full_name*, passport_no*, reference_name, payment_method, whatsapp_number, payment_note</code>
                <div class="mt-2"><span class="font-semibold text-slate-700">Dates:</span> use YYYY-MM-DD.</div>
                <div class="mt-1">
                    <span class="font-semibold text-slate-700">payment_method:</span>
                    accepts the key or its label —
                    @foreach($methods as $key => $label){{ $loop->first ? '' : ', ' }}<code>{{ $key }}</code> ({{ $label }})@endforeach.
                    Leave blank for none.
                </div>
                <div class="mt-1 text-slate-500">Tip: use <strong>Export CSV</strong> on the MOFA page to get a ready-to-edit file with the exact columns.</div>
            </div>
        </div>
    @endisset

</div>
@endsection
