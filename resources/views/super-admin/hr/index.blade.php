@extends('layouts.super-admin-app')
@section('title', 'HR Profiles')
@section('page-title', 'HR Profiles')

@section('content')

<x-ui.page-header title="HR / Candidates" subtitle="All profiles across agencies" icon="bi-person-vcard" />

{{-- Search & Filter --}}
<x-ui.card class="mb-5">
    <form method="GET" action="{{ route('super-admin.hr.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 transition-colors focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400">
                <i class="bi bi-search text-sm text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, file #, nationality…"
                       class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
            </div>
        </div>
        <select name="agency_id" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-3">
            <option value="">All Agencies</option>
            @foreach($agencies as $agency)
                <option value="{{ $agency->id }}" @selected(request('agency_id') == $agency->id)>{{ $agency->name }}</option>
            @endforeach
        </select>
        <select name="status" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
            <option value="">All Status</option>
            <option value="active"      @selected(request('status') == 'active')>Active</option>
            <option value="inactive"    @selected(request('status') == 'inactive')>Inactive</option>
            <option value="blacklisted" @selected(request('status') == 'blacklisted')>Blacklisted</option>
        </select>
        <div class="flex gap-2 lg:col-span-2">
            <x-ui.button type="submit" class="flex-1 cursor-pointer"><i class="bi bi-funnel"></i> Filter</x-ui.button>
            @if(request()->hasAny(['search','agency_id','status']))
                <x-ui.button :href="route('super-admin.hr.index')" variant="secondary" size="icon" title="Clear filters" class="cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
            @endif
        </div>
    </form>
</x-ui.card>

<x-ui.card class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Full Name</th>
                    <th class="px-4 py-3">Agency</th>
                    <th class="px-4 py-3">Nationality</th>
                    <th class="px-4 py-3">File #</th>
                    <th class="px-4 py-3">Agent</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($hrProfiles as $hr)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-400">{{ $hrProfiles->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('super-admin.hr.show', $hr) }}" class="font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $hr->full_name_en }}</a>
                            @if($hr->full_name_ar)
                                <div class="text-xs text-slate-400" dir="rtl">{{ $hr->full_name_ar }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('super-admin.agencies.show', $hr->agency_id) }}" class="text-slate-500 transition-colors hover:text-brand-600">{{ $hr->agency?->name ?? '—' }}</a>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $hr->nationality }}</td>
                        <td class="px-4 py-3">
                            @if($hr->file_number)
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-600">{{ $hr->file_number }}</span>
                            @else <span class="text-slate-300">—</span> @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $hr->agent?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><x-ui.status-badge :status="$hr->status" /></td>
                        <td class="px-4 py-3 text-slate-400">{{ $hr->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <a href="{{ route('super-admin.hr.show', $hr) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-0"><x-ui.empty icon="bi-person-vcard" title="No HR profiles found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($hrProfiles->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $hrProfiles->withQueryString()->links() }}</div>
    @endif
</x-ui.card>
@endsection
