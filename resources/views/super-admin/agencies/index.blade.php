@extends('layouts.super-admin-app')
@section('title', 'Agencies')
@section('page-title', 'Agencies')

@section('content')

<x-ui.page-header title="Agencies" subtitle="Manage all recruitment agencies" icon="bi-buildings">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.agencies.create')" class="cursor-pointer"><i class="bi bi-plus-lg"></i> New Agency</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

{{-- Filters --}}
<x-ui.card class="mb-5">
    <form method="GET" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-6">
            <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 transition-colors focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400">
                <i class="bi bi-search text-sm text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by agency, owner, RL no, email, or phone…"
                       class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
            </div>
        </div>
        <select name="status" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-3">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
        </select>
        <div class="flex gap-2 lg:col-span-3">
            <x-ui.button type="submit" class="flex-1 cursor-pointer"><i class="bi bi-funnel"></i> Filter</x-ui.button>
            @if(request()->hasAny(['search','status']))
                <x-ui.button :href="route('super-admin.agencies.index')" variant="secondary" size="icon" title="Clear filters" class="cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
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
                    <th class="px-4 py-3">Agency</th>
                    <th class="px-4 py-3">Owner</th>
                    <th class="px-4 py-3">RL No</th>
                    <th class="px-4 py-3">Official Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">License Expiry</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($agencies as $agency)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-400">{{ $loop->iteration + ($agencies->currentPage() - 1) * $agencies->perPage() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('super-admin.agencies.show', $agency) }}" class="font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $agency->name }}</a>
                            <div class="text-xs text-slate-400">{{ $agency->activeSubscription ? ($agency->activeSubscription->plan->name ?? '—') : 'No subscription' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $agency->owner_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $agency->rl_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $agency->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $agency->phone ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($agency->license_expiry_date)
                                <span class="{{ $agency->license_expiry_date->isPast() ? 'font-semibold text-rose-600' : 'text-slate-400' }}">{{ $agency->license_expiry_date->format('d M Y') }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-ui.status-badge :status="$agency->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                <a href="{{ route('super-admin.agencies.show', $agency) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('super-admin.agencies.edit', $agency) }}" title="Edit" class="grid h-8 w-8 place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('super-admin.agencies.toggle-status', $agency) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" title="{{ $agency->status === 'active' ? 'Suspend' : 'Activate' }}" onclick="return confirm('Change agency status?')"
                                        class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 {{ $agency->status === 'active' ? 'text-rose-500 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                                        <i class="bi bi-{{ $agency->status === 'active' ? 'pause-circle' : 'play-circle' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('super-admin.agencies.destroy', $agency) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete" onclick="return confirm('Delete this agency? This cannot be undone.')"
                                        class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-0"><x-ui.empty icon="bi-buildings" title="No agencies found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($agencies->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $agencies->withQueryString()->links() }}</div>
    @endif
</x-ui.card>
@endsection
