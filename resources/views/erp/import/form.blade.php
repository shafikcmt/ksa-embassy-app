@extends('layouts.erp-app')

{{--
    Shared ERP CSV import view (E7c+). Parameterized — used by Stamping, Manpower
    and later modules (MOFA keeps its own E7b view, intentionally not retrofitted).
    Expects:
      $title, $heading                             strings
      $back, $formRoute, $templateRoute,
      $previewRoute, $commitRoute                  route NAMES
      $columnsHint                                 string (columns line, * = required)
      $legend                                      string[] (extra help lines, plain text)
      $previewCols                                 array of ['label'=>, 'key'=>]  (attrs to show)
      $result                                      import result array, or null (upload form)
--}}

@section('title', $title)
@section('page-title', $title)

@section('content')
<div class="mx-auto max-w-5xl">

    <a href="{{ route($back) }}"
       class="mb-4 inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-700">
        <i class="bi bi-arrow-left"></i> Back
    </a>

    @isset($result)
        {{-- ── Preview (dry run — nothing written yet) ──────────────────── --}}
        @php $ok = $result['ok']; @endphp

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
                            @foreach($previewCols as $col)
                                <th class="px-3 py-2.5">{{ $col['label'] }}</th>
                            @endforeach
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
                                @foreach($previewCols as $col)
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-700">{{ $row['attrs'][$col['key']] ?: '—' }}</td>
                                @endforeach
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
            <a href="{{ route($formRoute) }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="bi bi-arrow-counterclockwise"></i> Upload a different file
            </a>
            @if($ok)
                <form method="POST" action="{{ route($commitRoute) }}">
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
            <h2 class="mb-1 flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-upload text-emerald-600"></i> {{ $heading }}</h2>
            <p class="mb-5 text-sm text-slate-500">
                Upload a CSV file. You'll see a <strong>preview</strong> of every row (valid / invalid) before anything is saved.
                Import is <strong>all-or-nothing</strong> — if any row is invalid, nothing is written.
            </p>

            <form method="POST" action="{{ route($previewRoute) }}" enctype="multipart/form-data" class="space-y-4">
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
                    <a href="{{ route($templateRoute) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i class="bi bi-download"></i> Download template
                    </a>
                </div>
            </form>

            <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50 p-4 text-xs text-slate-600">
                <div class="mb-1 font-semibold text-slate-700">Columns (required marked *)</div>
                <code class="text-[0.7rem]">{{ $columnsHint }}</code>
                <div class="mt-2"><span class="font-semibold text-slate-700">Dates:</span> use YYYY-MM-DD.</div>
                @foreach($legend as $line)
                    <div class="mt-1">{{ $line }}</div>
                @endforeach
            </div>
        </div>
    @endisset

</div>
@endsection
