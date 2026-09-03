@extends('layouts.agency-app')
@section('title', 'HR / Candidates')
@section('page-title', 'HR / Candidates')

@php
    $active      = $statusCounts['active'] ?? 0;
    $inactive    = $statusCounts['inactive'] ?? 0;
    $blacklisted = $statusCounts['blacklisted'] ?? 0;

    // Data payload for the quick-preview slide-over — built from rows already
    // loaded in this list (no extra query / no backend endpoint).
    $previewData = fn($hr) => [
        'name'        => $hr->full_name_en,
        'nameAr'      => $hr->full_name_ar ?? '',
        'initial'     => strtoupper(mb_substr($hr->full_name_en, 0, 1)),
        'status'      => $hr->status,
        'mofa'        => $hr->mofa_new ?: ($hr->mofa_old ?: ''),
        'passport'    => $hr->passport?->passport_number ?: '',
        'agent'       => $hr->agent?->name ?? '',
        'visa'        => $hr->visa?->visa_number ?: '',
        'sponsorId'   => $hr->visa?->sponsor_id ?: '',
        'sponsorName' => $hr->visa?->sponsor_name ?: '',
        'showUrl'     => route('hr.show', $hr),
        'docsUrl'     => route('hr.documents', $hr),
        'editUrl'     => route('hr.edit', $hr),
        'canEdit'     => auth()->user()->can('update', $hr),
    ];

    // Status badge styling, shared by the desktop table + mobile cards.
    // [pill classes, dot colour, label]
    $statusBadge = [
        'active'      => ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500', 'Active'],
        'inactive'    => ['bg-slate-100 text-slate-500 ring-slate-200',      'bg-slate-400',   'Inactive'],
        'blacklisted' => ['bg-rose-50 text-rose-700 ring-rose-200',          'bg-rose-500',    'Blacklisted'],
    ];
@endphp

@section('content')
<div x-data="{
        del: { open: false, name: '', action: '' },
        preview: {
            open: false, name: '', nameAr: '', initial: '', status: '',
            mofa: '', passport: '', agent: '', visa: '', sponsorId: '', sponsorName: '',
            showUrl: '', docsUrl: '', editUrl: '', canEdit: false
        },
        openPreview(data) { this.preview = { ...this.preview, ...data, open: true }; }
     }">

    {{-- Slim header — title + count on the left, primary action on the right.
         Replaces the old gradient banner to give the table more room and match
         the new dashboard/header aesthetic. --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                <i class="bi bi-person-vcard text-xl"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900">HR / Candidates</h1>
                <p class="text-xs text-slate-500">{{ $totalHr }} total profile{{ $totalHr === 1 ? '' : 's' }}{{ $planLimit > 0 && $planLimit < 9999 ? ' · plan limit '.$planLimit : '' }}</p>
            </div>
        </div>
        @can('create', \App\Models\HrProfile::class)
            <a href="{{ route('hr.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:shadow-md">
                <i class="bi bi-plus-lg"></i> Add HR Profile
            </a>
        @endcan
    </div>

    {{-- Stat cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-ui.stat class="js-fade-card" accentLeft icon="bi-people" tone="brand" label="Total" :value="$totalHr" />
        <x-ui.stat class="js-fade-card" accentLeft icon="bi-person-check" tone="green" label="Active" :value="$active" />
        <x-ui.stat class="js-fade-card" accentLeft icon="bi-person-dash" tone="slate" label="Inactive" :value="$inactive" />
        <x-ui.stat class="js-fade-card" accentLeft icon="bi-person-x" :tone="$blacklisted > 0 ? 'red' : 'slate'" label="Blacklisted" :value="$blacklisted" />
    </div>

    {{-- Filter bar --}}
    <x-ui.card class="mb-5">
        <form method="GET" action="{{ route('hr.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-4" x-data="{ q: @js(request('search') ?? '') }">
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 transition focus-within:border-brand-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-100">
                    <i class="bi bi-search text-sm text-slate-400"></i>
                    <input type="text" name="search" x-model="q" placeholder="Name, file #, passport, phone…"
                           class="h-11 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                    <button type="button" x-show="q" x-cloak @click="q = ''" title="Clear search"
                            class="grid h-6 w-6 shrink-0 place-items-center rounded-md text-slate-400 transition hover:bg-slate-200 hover:text-slate-600">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </div>
            </div>
            @php $selCls = 'h-11 rounded-xl border-slate-200 bg-slate-50 text-sm transition focus:border-brand-400 focus:bg-white focus:ring-brand-400'; @endphp
            <select name="status" class="{{ $selCls }} lg:col-span-2">
                <option value="">All status</option>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'blacklisted' => 'Blacklisted'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            <select name="agent_id" class="{{ $selCls }} lg:col-span-2">
                <option value="">All agents</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" @selected(request('agent_id') == $agent->id)>{{ $agent->name }}</option>
                @endforeach
            </select>
            <select name="nationality" class="{{ $selCls }} lg:col-span-2">
                <option value="">All nationalities</option>
                @foreach($nationalities as $nat)
                    <option value="{{ $nat }}" @selected(request('nationality') === $nat)>{{ $nat }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 lg:col-span-2">
                <x-ui.button type="submit" variant="gradient" class="h-11 flex-1"><i class="bi bi-funnel-fill"></i> Filter</x-ui.button>
                <x-ui.button :href="route('hr.index')" variant="secondary" size="icon" title="Clear all filters"
                             class="h-11 w-11 shrink-0 hover:border-brand-200 hover:text-brand-600"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- ── Desktop table ─────────────────────────────────────── --}}
    <x-ui.card class="hidden overflow-hidden lg:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="sticky top-0 z-10 border-b border-slate-200 bg-slate-100/95 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 backdrop-blur">
                        <th class="w-[4%]  px-3 py-3">#</th>
                        <th class="w-[18%] px-3 py-3">Name</th>
                        <th class="w-[9%]  px-3 py-3">MOFA ID</th>
                        <th class="w-[11%] px-3 py-3">Passport No</th>
                        <th class="w-[11%] px-3 py-3">Agent</th>
                        <th class="w-[10%] px-3 py-3">Visa No</th>
                        <th class="w-[13%] px-3 py-3">Sponsor</th>
                        <th class="w-[8%]  px-3 py-3">Status</th>
                        <th class="w-[16%] px-3 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($hrProfiles as $hr)
                        <tr class="odd:bg-white even:bg-slate-50/60 transition-colors hover:bg-brand-50/50">
                            <td class="px-3 py-3 text-slate-400">{{ $hrProfiles->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-indigo-600 text-xs font-bold text-white shadow-sm">
                                        {{ strtoupper(mb_substr($hr->full_name_en, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('hr.show', $hr) }}" @click.prevent="openPreview(@js($previewData($hr)))" class="block break-words text-left font-semibold text-slate-800 hover:text-brand-600">{{ $hr->full_name_en }}</a>
                                        @if($hr->full_name_ar)
                                            <div class="break-words text-xs text-slate-400" dir="rtl">{{ $hr->full_name_ar }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                @if($hr->mofa_new ?: $hr->mofa_old)
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600 ring-1 ring-inset ring-slate-200">{{ $hr->mofa_new ?: $hr->mofa_old }}</span>
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                            <td class="px-3 py-3 font-mono text-xs text-slate-600">{{ $hr->passport?->passport_number ?: '—' }}</td>
                            <td class="px-3 py-3 break-words text-slate-600">{{ $hr->agent?->name ?? '—' }}</td>
                            <td class="px-3 py-3 font-mono text-xs text-slate-600">{{ $hr->visa?->visa_number ?: '—' }}</td>
                            <td class="px-3 py-3">
                                @if($hr->visa?->sponsor_name || $hr->visa?->sponsor_id)
                                    <div class="break-words font-medium text-slate-700">{{ $hr->visa?->sponsor_name ?: '—' }}</div>
                                    @if($hr->visa?->sponsor_id)
                                        <div class="font-mono text-xs text-slate-400">{{ $hr->visa->sponsor_id }}</div>
                                    @endif
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                            <td class="px-3 py-3">
                                @php [$sCls, $sDot, $sLbl] = $statusBadge[$hr->status] ?? $statusBadge['inactive']; @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $sCls }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $sDot }}"></span>{{ $sLbl }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @php $pill = 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition'; @endphp
                                <div class="flex flex-nowrap justify-end gap-1.5">
                                    <a href="{{ route('hr.documents', $hr) }}" class="{{ $pill }} bg-blue-50 text-blue-700 ring-blue-200 hover:bg-blue-100"><i class="bi bi-printer"></i> Print</a>
                                    @can('update', $hr)
                                        <a href="{{ route('hr.edit', $hr) }}" class="{{ $pill }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"><i class="bi bi-pencil"></i> Edit</a>
                                    @endcan
                                    @can('delete', $hr)
                                        <button type="button" class="{{ $pill }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"
                                            x-on:click="del.open = true; del.name = @js($hr->full_name_en); del.action = '{{ route('hr.destroy', $hr) }}'"><i class="bi bi-trash"></i> Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="p-0">
                            <x-ui.empty icon="bi-person-vcard" title="No HR profiles found"
                                message="Try adjusting your filters, or add your first candidate profile."
                                :actionUrl="auth()->user()->can('create', \App\Models\HrProfile::class) ? route('hr.create') : null"
                                actionLabel="Add HR Profile" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($hrProfiles->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $hrProfiles->withQueryString()->links() }}</div>
        @endif
    </x-ui.card>

    {{-- ── Mobile cards ──────────────────────────────────────── --}}
    <div class="space-y-3 lg:hidden">
        @forelse($hrProfiles as $hr)
            <x-ui.card class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-500 to-indigo-600 text-sm font-bold text-white shadow-sm">
                            {{ strtoupper(mb_substr($hr->full_name_en, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <a href="{{ route('hr.show', $hr) }}" @click.prevent="openPreview(@js($previewData($hr)))" class="block truncate text-left font-semibold text-slate-800">{{ $hr->full_name_en }}</a>
                            @if($hr->full_name_ar)<div class="truncate text-xs text-slate-400" dir="rtl">{{ $hr->full_name_ar }}</div>@endif
                        </div>
                    </div>
                    @php [$sCls, $sDot, $sLbl] = $statusBadge[$hr->status] ?? $statusBadge['inactive']; @endphp
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $sCls }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $sDot }}"></span>{{ $sLbl }}
                    </span>
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-y-2 text-xs">
                    <div><dt class="text-slate-400">MOFA ID</dt><dd class="font-mono text-slate-700">{{ $hr->mofa_new ?: ($hr->mofa_old ?: '—') }}</dd></div>
                    <div><dt class="text-slate-400">Passport No</dt><dd class="font-mono text-slate-700">{{ $hr->passport?->passport_number ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Agent</dt><dd class="font-medium text-slate-700">{{ $hr->agent?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-400">Visa No</dt><dd class="font-mono text-slate-700">{{ $hr->visa?->visa_number ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Sponsor ID</dt><dd class="font-mono text-slate-700">{{ $hr->visa?->sponsor_id ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Sponsor Name</dt><dd class="font-medium text-slate-700">{{ $hr->visa?->sponsor_name ?: '—' }}</dd></div>
                </dl>
                @php $mBtn = 'flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold ring-1 ring-inset transition'; @endphp
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    <a href="{{ route('hr.documents', $hr) }}" class="{{ $mBtn }} bg-blue-50 text-blue-700 ring-blue-200 hover:bg-blue-100"><i class="bi bi-printer"></i> Print</a>
                    @can('update', $hr)
                        <a href="{{ route('hr.edit', $hr) }}" class="{{ $mBtn }} bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100"><i class="bi bi-pencil"></i> Edit</a>
                    @endcan
                    @can('delete', $hr)
                        <button type="button" class="{{ $mBtn }} bg-rose-50 text-rose-700 ring-rose-200 hover:bg-rose-100"
                            x-on:click="del.open = true; del.name = @js($hr->full_name_en); del.action = '{{ route('hr.destroy', $hr) }}'"><i class="bi bi-trash"></i> Delete</button>
                    @endcan
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty icon="bi-person-vcard" title="No HR profiles found"
                    message="Try adjusting your filters, or add your first candidate profile."
                    :actionUrl="auth()->user()->can('create', \App\Models\HrProfile::class) ? route('hr.create') : null"
                    actionLabel="Add HR Profile" />
            </x-ui.card>
        @endforelse
        @if($hrProfiles->hasPages())
            <div class="pt-1">{{ $hrProfiles->withQueryString()->links() }}</div>
        @endif
    </div>

    {{-- ── Delete dialog ─────────────────────────────────────── --}}
    <div x-show="del.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="del.open = false" x-show="del.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="del.open"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Delete HR Profile</h3>
                    <p class="mt-1 text-sm text-slate-500">Delete <strong x-text="del.name"></strong>? This permanently removes all associated passport, visa, clearance and other data.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" x-on:click="del.open = false">Cancel</x-ui.button>
                <form :action="del.action" method="POST">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm"><i class="bi bi-trash"></i> Delete</x-ui.button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Quick-view slide-over ─────────────────────────────────
         Triage a candidate without leaving the list. Data comes from the row
         already loaded above; the full profile / docs / edit links open the
         real pages. Same overlay + transition pattern as the dialogs above. --}}
    <div x-show="preview.open" x-cloak class="fixed inset-0 z-[70]" style="display:none" @keydown.escape.window="preview.open = false">
        <div @click="preview.open = false" x-show="preview.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="preview.open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
             class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-xl">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-50 text-base font-bold text-brand-700" x-text="preview.initial"></span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="truncate text-base font-bold text-slate-900" x-text="preview.name"></h3>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[0.68rem] font-semibold capitalize"
                                  :class="{
                                      'bg-emerald-50 text-emerald-600': preview.status === 'active',
                                      'bg-slate-100 text-slate-500': preview.status === 'inactive',
                                      'bg-rose-50 text-rose-600': preview.status === 'blacklisted'
                                  }" x-text="preview.status"></span>
                        </div>
                        <p class="truncate text-sm text-slate-400" dir="rtl" x-show="preview.nameAr" x-text="preview.nameAr"></p>
                    </div>
                </div>
                <button type="button" @click="preview.open = false" title="Close" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
            </div>

            {{-- Body: key fields from the row --}}
            <div class="flex-1 overflow-y-auto px-5 py-4">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-slate-400">MOFA ID</dt><dd class="text-right font-mono text-xs text-slate-700" x-text="preview.mofa || '—'"></dd></div>
                    <div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-slate-400">Passport No</dt><dd class="text-right font-mono text-xs text-slate-700" x-text="preview.passport || '—'"></dd></div>
                    <div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-slate-400">Agent</dt><dd class="text-right font-medium text-slate-700" x-text="preview.agent || '—'"></dd></div>
                    <div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-slate-400">Visa No</dt><dd class="text-right font-mono text-xs text-slate-700" x-text="preview.visa || '—'"></dd></div>
                    <div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-slate-400">Sponsor ID</dt><dd class="text-right font-mono text-xs text-slate-700" x-text="preview.sponsorId || '—'"></dd></div>
                    <div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-slate-400">Sponsor Name</dt><dd class="text-right font-medium text-slate-700" x-text="preview.sponsorName || '—'"></dd></div>
                </dl>
            </div>

            {{-- Footer actions --}}
            <div class="border-t border-slate-100 px-5 py-4">
                <a :href="preview.showUrl" class="mb-2 flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-brand-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:shadow-md"><i class="bi bi-person-vcard"></i> Open full profile</a>
                <div class="flex gap-2">
                    <a :href="preview.docsUrl" class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:text-brand-700"><i class="bi bi-file-earmark-pdf"></i> Documents</a>
                    <a x-show="preview.canEdit" :href="preview.editUrl" class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-brand-200 hover:text-brand-700"><i class="bi bi-pencil"></i> Edit</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Subtle fade + slide-in for the stat cards (Motion One, already bundled).
    // Guarded so a missing bundle never throws — cards stay visible regardless.
    document.addEventListener('DOMContentLoaded', () => {
        if (window.fadeInCards) window.fadeInCards('.js-fade-card');
    });
</script>
@endpush
@endsection
