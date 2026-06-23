@extends('layouts.agency-app')
@section('title', 'HR / Candidates')
@section('page-title', 'HR / Candidates')

@php
    $active      = $statusCounts['active'] ?? 0;
    $inactive    = $statusCounts['inactive'] ?? 0;
    $blacklisted = $statusCounts['blacklisted'] ?? 0;

    // Shared helper: passport expiry state for a profile
    $passState = function ($hr) {
        $exp = $hr->passport?->expiry_date;
        if (! $exp) return null;
        if ($exp->isPast()) return ['red', 'Expired', $exp];
        if ($exp->lt(now()->addMonths(6))) return ['amber', 'Expiring', $exp];
        return ['green', $exp->format('d M Y'), $exp];
    };
@endphp

@section('content')
<div x-data="{ del: { open: false, name: '', action: '' } }">

    {{-- Header --}}
    <x-ui.page-header
        title="HR / Candidates"
        subtitle="{{ $totalHr }} total profile{{ $totalHr === 1 ? '' : 's' }}{{ $planLimit > 0 && $planLimit < 9999 ? ' · plan limit '.$planLimit : '' }}"
        icon="bi-person-vcard">
        <x-slot:actions>
            @can('create', \App\Models\HrProfile::class)
                <x-ui.button :href="route('hr.create')">
                    <i class="bi bi-plus-lg"></i> Add HR Profile
                </x-ui.button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Stat cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-ui.stat icon="bi-people" tone="brand"  label="Total"       :value="$totalHr" />
        <x-ui.stat icon="bi-person-check" tone="green" label="Active" :value="$active" />
        <x-ui.stat icon="bi-person-dash" tone="slate" label="Inactive" :value="$inactive" />
        <x-ui.stat icon="bi-person-x" :tone="$blacklisted > 0 ? 'red' : 'slate'" label="Blacklisted" :value="$blacklisted" />
    </div>

    {{-- Filter bar --}}
    <x-ui.card class="mb-5">
        <form method="GET" action="{{ route('hr.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400">
                    <i class="bi bi-search text-sm text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, file #, passport, phone…"
                           class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                </div>
            </div>
            <select name="status" class="h-10 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
                <option value="">All status</option>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'blacklisted' => 'Blacklisted'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            <select name="agent_id" class="h-10 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
                <option value="">All agents</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" @selected(request('agent_id') == $agent->id)>{{ $agent->name }}</option>
                @endforeach
            </select>
            <select name="nationality" class="h-10 rounded-lg border-slate-300 text-sm focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
                <option value="">All nationalities</option>
                @foreach($nationalities as $nat)
                    <option value="{{ $nat }}" @selected(request('nationality') === $nat)>{{ $nat }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 lg:col-span-2">
                <x-ui.button type="submit" class="flex-1"><i class="bi bi-funnel"></i> Filter</x-ui.button>
                <x-ui.button :href="route('hr.index')" variant="secondary" size="icon" title="Clear filters"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- ── Desktop table ─────────────────────────────────────── --}}
    <x-ui.card class="hidden overflow-hidden lg:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Candidate</th>
                        <th class="px-4 py-3">Nationality</th>
                        <th class="px-4 py-3">File #</th>
                        <th class="px-4 py-3">Agent</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Passport</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($hrProfiles as $hr)
                        @php $ps = $passState($hr); @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">
                                        {{ strtoupper(mb_substr($hr->full_name_en, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <a href="{{ route('hr.show', $hr) }}" class="block truncate font-semibold text-slate-800 hover:text-brand-600">{{ $hr->full_name_en }}</a>
                                        @if($hr->full_name_ar)
                                            <div class="truncate text-xs text-slate-400" dir="rtl">{{ $hr->full_name_ar }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $hr->nationality ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($hr->file_number)
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">{{ $hr->file_number }}</span>
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $hr->agent?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $hr->phone ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if($ps)
                                    <x-ui.badge :tone="$ps[0]">
                                        @if($ps[0] !== 'green')<i class="bi bi-exclamation-triangle"></i>@endif
                                        {{ $ps[1] }}
                                    </x-ui.badge>
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                            <td class="px-4 py-3"><x-ui.status-badge :status="$hr->status" /></td>
                            <td class="px-4 py-3 text-slate-400">{{ $hr->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('hr.show', $hr) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('hr.documents', $hr) }}" title="Documents" class="grid h-8 w-8 place-items-center rounded-lg text-emerald-600 transition hover:bg-emerald-50"><i class="bi bi-file-earmark-pdf"></i></a>
                                    @can('update', $hr)
                                        <a href="{{ route('hr.edit', $hr) }}" title="Edit" class="grid h-8 w-8 place-items-center rounded-lg text-brand-600 transition hover:bg-brand-50"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('delete', $hr)
                                        <button type="button" title="Delete" class="grid h-8 w-8 place-items-center rounded-lg text-rose-500 transition hover:bg-rose-50"
                                            x-on:click="del.open = true; del.name = @js($hr->full_name_en); del.action = '{{ route('hr.destroy', $hr) }}'"><i class="bi bi-trash"></i></button>
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
            @php $ps = $passState($hr); @endphp
            <x-ui.card class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">
                            {{ strtoupper(mb_substr($hr->full_name_en, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <a href="{{ route('hr.show', $hr) }}" class="block truncate font-semibold text-slate-800">{{ $hr->full_name_en }}</a>
                            @if($hr->full_name_ar)<div class="truncate text-xs text-slate-400" dir="rtl">{{ $hr->full_name_ar }}</div>@endif
                        </div>
                    </div>
                    <x-ui.status-badge :status="$hr->status" />
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-y-2 text-xs">
                    <div><dt class="text-slate-400">Nationality</dt><dd class="font-medium text-slate-700">{{ $hr->nationality ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">File #</dt><dd class="font-mono text-slate-700">{{ $hr->file_number ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Agent</dt><dd class="font-medium text-slate-700">{{ $hr->agent?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-400">Phone</dt><dd class="font-medium text-slate-700">{{ $hr->phone ?: '—' }}</dd></div>
                    <div class="col-span-2"><dt class="text-slate-400">Passport</dt><dd>@if($ps)<x-ui.badge :tone="$ps[0]">{{ $ps[1] }}</x-ui.badge>@else <span class="text-slate-400">—</span> @endif</dd></div>
                </dl>
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    <x-ui.button :href="route('hr.show', $hr)" variant="secondary" size="sm" class="flex-1"><i class="bi bi-eye"></i> View</x-ui.button>
                    <x-ui.button :href="route('hr.documents', $hr)" variant="secondary" size="sm" class="flex-1"><i class="bi bi-file-earmark-pdf"></i> Docs</x-ui.button>
                    @can('update', $hr)
                        <x-ui.button :href="route('hr.edit', $hr)" variant="secondary" size="sm" class="flex-1"><i class="bi bi-pencil"></i> Edit</x-ui.button>
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
</div>
@endsection
