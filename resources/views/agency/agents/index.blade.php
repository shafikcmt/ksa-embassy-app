@extends('layouts.agency-app')
@section('title', 'Agents')
@section('page-title', 'Agent Management')

@section('content')
<div x-data="{ del: { open: false, name: '', action: '' } }">

    {{-- Header --}}
    <x-ui.page-header
        title="Agents"
        subtitle="Manage your agency's recruitment agents"
        icon="bi-people">
        <x-slot:actions>
            @can('create', \App\Models\Agent::class)
                @if($planLimit !== null && $totalAgents >= $planLimit && $planLimit < 999)
                    <x-ui.button variant="secondary" disabled title="Plan limit reached">
                        <i class="bi bi-slash-circle"></i> Limit Reached ({{ $totalAgents }}/{{ $planLimit }})
                    </x-ui.button>
                @else
                    <x-ui.button :href="route('agents.create')" class="cursor-pointer">
                        <i class="bi bi-plus-lg"></i> Add New Agent
                    </x-ui.button>
                @endif
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Stat cards --}}
    <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <x-ui.stat icon="bi-people-fill" tone="brand" label="Total Agents"
            :value="$totalAgents"
            :sub="$planLimit && $planLimit < 999 ? 'of '.$planLimit.' allowed' : null" />
        <x-ui.stat icon="bi-person-check" tone="green" label="Active" :value="$activeAgents" />
        <x-ui.stat icon="bi-person-dash" tone="slate" label="Inactive" :value="$totalAgents - $activeAgents" />
    </div>

    {{-- Filter bar --}}
    <x-ui.card class="mb-5">
        <form method="GET" action="{{ route('agents.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 transition-colors focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400">
                    <i class="bi bi-search text-sm text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, or email…"
                           class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                </div>
            </div>
            <select name="status" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-3">
                <option value="">All status</option>
                <option value="active"   @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2 lg:col-span-3">
                <x-ui.button type="submit" class="flex-1 cursor-pointer"><i class="bi bi-funnel"></i> Filter</x-ui.button>
                @if(request()->hasAny(['search','status']))
                    <x-ui.button :href="route('agents.index')" variant="secondary" size="icon" title="Clear filters" class="cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
                @endif
            </div>
        </form>
    </x-ui.card>

    {{-- ── Desktop table ─────────────────────────────────────── --}}
    <x-ui.card class="hidden overflow-hidden lg:block">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="w-[4%] px-3 py-3">#</th>
                        <th class="px-3 py-3">Name</th>
                        <th class="px-3 py-3">Phone</th>
                        <th class="px-3 py-3">Email</th>
                        <th class="px-3 py-3">Address</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Created</th>
                        <th class="px-3 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agents as $agent)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-3 py-3 text-slate-400">{{ $loop->iteration + ($agents->currentPage() - 1) * $agents->perPage() }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('agents.show', $agent) }}" class="block font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $agent->name }}</a>
                                @if($agent->createdBy)
                                    <div class="text-xs text-slate-400">by {{ $agent->createdBy->name }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $agent->phone }}</td>
                            <td class="px-3 py-3">
                                @if($agent->email)
                                    <a href="mailto:{{ $agent->email }}" class="text-brand-600 transition-colors hover:text-brand-700 hover:underline">{{ $agent->email }}</a>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span title="{{ $agent->address }}" class="block max-w-[160px] truncate text-slate-600">{{ $agent->address }}</span>
                            </td>
                            <td class="px-3 py-3"><x-ui.status-badge :status="$agent->status" /></td>
                            <td class="px-3 py-3 text-slate-400">{{ $agent->created_at->format('d M Y') }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                    <a href="{{ route('agents.show', $agent) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                                    @can('update', $agent)
                                        <a href="{{ route('agents.edit', $agent) }}" title="Edit" class="grid h-8 w-8 place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"><i class="bi bi-pencil"></i></a>
                                    @endcan
                                    @can('delete', $agent)
                                        <button type="button" title="Delete" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
                                            x-on:click="del.open = true; del.name = @js($agent->name); del.action = '{{ route('agents.destroy', $agent) }}'"><i class="bi bi-trash"></i></button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-0">
                            <x-ui.empty icon="bi-people" title="No agents found"
                                message="{{ request()->hasAny(['search','status']) ? 'No agents match your filters.' : 'No agents added yet.' }}"
                                :actionUrl="!request()->hasAny(['search','status']) && auth()->user()->can('create', \App\Models\Agent::class) ? route('agents.create') : null"
                                actionLabel="Add your first agent" />
                        </td></tr>
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

    {{-- ── Mobile cards ──────────────────────────────────────── --}}
    <div class="space-y-3 lg:hidden">
        @forelse($agents as $agent)
            <x-ui.card class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('agents.show', $agent) }}" class="block truncate font-semibold text-slate-800">{{ $agent->name }}</a>
                        @if($agent->createdBy)<div class="truncate text-xs text-slate-400">by {{ $agent->createdBy->name }}</div>@endif
                    </div>
                    <x-ui.status-badge :status="$agent->status" />
                </div>
                <dl class="mt-3 grid grid-cols-2 gap-y-2 text-xs">
                    <div><dt class="text-slate-400">Phone</dt><dd class="font-medium text-slate-700">{{ $agent->phone }}</dd></div>
                    <div><dt class="text-slate-400">Email</dt><dd class="truncate font-medium text-slate-700">{{ $agent->email ?: '—' }}</dd></div>
                    <div class="col-span-2"><dt class="text-slate-400">Address</dt><dd class="font-medium text-slate-700">{{ $agent->address }}</dd></div>
                    <div><dt class="text-slate-400">Created</dt><dd class="font-medium text-slate-700">{{ $agent->created_at->format('d M Y') }}</dd></div>
                </dl>
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    <x-ui.button :href="route('agents.show', $agent)" variant="secondary" size="sm" class="flex-1 cursor-pointer"><i class="bi bi-eye"></i> View</x-ui.button>
                    @can('update', $agent)
                        <x-ui.button :href="route('agents.edit', $agent)" variant="secondary" size="sm" class="flex-1 cursor-pointer"><i class="bi bi-pencil"></i> Edit</x-ui.button>
                    @endcan
                    @can('delete', $agent)
                        <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer"
                            x-on:click="del.open = true; del.name = @js($agent->name); del.action = '{{ route('agents.destroy', $agent) }}'" title="Delete"><i class="bi bi-trash text-rose-500"></i></x-ui.button>
                    @endcan
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <x-ui.empty icon="bi-people" title="No agents found"
                    message="{{ request()->hasAny(['search','status']) ? 'No agents match your filters.' : 'No agents added yet.' }}"
                    :actionUrl="!request()->hasAny(['search','status']) && auth()->user()->can('create', \App\Models\Agent::class) ? route('agents.create') : null"
                    actionLabel="Add your first agent" />
            </x-ui.card>
        @endforelse
        @if($agents->hasPages())
            <div class="pt-1">{{ $agents->withQueryString()->links() }}</div>
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
                    <h3 class="text-base font-semibold text-slate-900">Delete Agent</h3>
                    <p class="mt-1 text-sm text-slate-500">Are you sure you want to delete <strong x-text="del.name"></strong>? This action cannot be undone.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="del.open = false">Cancel</x-ui.button>
                <form :action="del.action" method="POST">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm" class="cursor-pointer"><i class="bi bi-trash"></i> Delete Agent</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
