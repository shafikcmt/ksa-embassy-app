@extends('layouts.super-admin-app')
@section('title', 'All Agents')
@section('page-title', 'All Agents')

@section('content')
<div x-data="{ del: { open: false, name: '', action: '' } }">

    <x-ui.page-header title="All Agents" subtitle="Read-only view across all agencies" icon="bi-people" />

    {{-- Filters --}}
    <x-ui.card class="mb-5">
        <form method="GET" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 transition-colors focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400">
                    <i class="bi bi-search text-sm text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, phone, email…"
                           class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                </div>
            </div>
            <select name="agency_id" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-3">
                <option value="">All Agencies</option>
                @foreach($agencies as $ag)
                    <option value="{{ $ag->id }}" @selected(request('agency_id') == $ag->id)>{{ $ag->name }}</option>
                @endforeach
            </select>
            <select name="status" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-2">
                <option value="">All Status</option>
                <option value="active"   @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2 lg:col-span-2">
                <x-ui.button type="submit" class="flex-1 cursor-pointer"><i class="bi bi-funnel"></i> Filter</x-ui.button>
                @if(request()->hasAny(['search','agency_id','status']))
                    <x-ui.button :href="route('super-admin.agents.index')" variant="secondary" size="icon" title="Clear filters" class="cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
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
                        <th class="px-4 py-3">Agent Name</th>
                        <th class="px-4 py-3">Agency</th>
                        <th class="px-4 py-3">Phone</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agents as $agent)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-400">{{ $loop->iteration + ($agents->currentPage() - 1) * $agents->perPage() }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $agent->name }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('super-admin.agencies.show', $agent->agency_id) }}" class="text-brand-600 transition-colors hover:text-brand-700">{{ $agent->agency->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $agent->phone }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $agent->email ?? '—' }}</td>
                            <td class="px-4 py-3"><x-ui.status-badge :status="$agent->status" /></td>
                            <td class="px-4 py-3 text-slate-400">{{ $agent->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                    <a href="{{ route('super-admin.agents.show', $agent) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                                    <button type="button" title="Delete" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                                        x-on:click="del.open = true; del.name = @js($agent->name); del.action = '{{ route('super-admin.agents.destroy', $agent) }}'"><i class="bi bi-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-0"><x-ui.empty icon="bi-people" title="No agents found" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agents->hasPages())
            <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3">
                <span class="text-xs text-slate-500">Showing {{ $agents->firstItem() }}–{{ $agents->lastItem() }} of {{ $agents->total() }} agents</span>
                {{ $agents->withQueryString()->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- Delete dialog --}}
    <div x-show="del.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
        <div @click="del.open = false" x-show="del.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
        <div x-show="del.open"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Delete Agent</h3>
                    <p class="mt-1 text-sm text-slate-500">Delete <strong x-text="del.name"></strong>? This action cannot be undone.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="del.open = false">Cancel</x-ui.button>
                <form :action="del.action" method="POST">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm" class="cursor-pointer"><i class="bi bi-trash"></i> Delete</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
