@extends('layouts.super-admin-app')
@section('title', $agency->name)
@section('page-title', 'Agency Detail')

@section('content')

<x-ui.page-header :title="$agency->name" subtitle="Agency Detail" icon="bi-building">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.agencies.edit', $agency)" class="cursor-pointer"><i class="bi bi-pencil"></i> Edit</x-ui.button>
        <x-ui.button :href="route('super-admin.agencies.index')" variant="secondary" class="cursor-pointer"><i class="bi bi-arrow-left"></i> Back</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

    {{-- Agency Details --}}
    <div class="lg:col-span-5">
        <x-ui.card>
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                <i class="bi bi-info-circle text-brand-600"></i> Details
            </div>
            <div class="divide-y divide-slate-100 px-5 py-2 text-sm">
                <x-ui.dl-row label="Status"><x-ui.status-badge :status="$agency->status" /></x-ui.dl-row>
                <x-ui.dl-row label="Owner / Contact">{{ $agency->owner_name ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="System License No.">{{ $agency->license_number ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="RL Number">{{ $agency->rl_number ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Phone">{{ $agency->phone ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Official Email">{{ $agency->email ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Address">{{ $agency->address ?? '—' }}</x-ui.dl-row>
                <x-ui.dl-row label="Print Logo">
                    <x-ui.badge :tone="$agency->print_logo ? 'green' : 'slate'">{{ $agency->print_logo ? 'Yes' : 'No' }}</x-ui.badge>
                </x-ui.dl-row>
                <x-ui.dl-row label="License Expiry">
                    <span class="{{ $agency->license_expiry_date?->isPast() ? 'font-bold text-rose-600' : '' }}">{{ $agency->license_expiry_date?->format('d M Y') ?? '—' }}</span>
                </x-ui.dl-row>
                <x-ui.dl-row label="Created">{{ $agency->created_at->format('d M Y') }}</x-ui.dl-row>
            </div>
        </x-ui.card>
    </div>

    {{-- Subscription History + Users --}}
    <div class="space-y-5 lg:col-span-7">

        {{-- Subscription History --}}
        <x-ui.card class="overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-credit-card text-brand-600"></i> Subscription History</span>
                <a href="{{ route('super-admin.subscriptions.create') }}?agency_id={{ $agency->id }}" class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">+ Add</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2.5">Plan</th>
                            <th class="px-5 py-2.5">Period</th>
                            <th class="px-5 py-2.5">Status</th>
                            <th class="px-5 py-2.5">Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($agency->subscriptions as $sub)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-5 py-2.5 text-slate-700">{{ $sub->plan->name }}</td>
                                <td class="px-5 py-2.5 text-slate-500">{{ $sub->start_date->format('d M Y') }} → {{ $sub->end_date->format('d M Y') }}</td>
                                <td class="px-5 py-2.5"><x-ui.status-badge :status="$sub->status" /></td>
                                <td class="px-5 py-2.5">
                                    @if($sub->payment_status === 'paid')
                                        <x-ui.badge tone="green">Paid</x-ui.badge>
                                    @elseif($sub->payment_status === 'pending')
                                        <span class="inline-flex items-center gap-2">
                                            <x-ui.badge tone="amber">Pending</x-ui.badge>
                                            <form method="POST" action="{{ route('super-admin.subscriptions.approve', $sub) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <x-ui.button type="submit" variant="success" size="sm" class="cursor-pointer">Approve</x-ui.button>
                                            </form>
                                        </span>
                                    @else
                                        <x-ui.badge tone="slate">{{ ucfirst($sub->payment_status) }}</x-ui.badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-4 text-center text-slate-400">No subscriptions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Users --}}
        <x-ui.card class="overflow-hidden">
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                <i class="bi bi-people text-violet-500"></i> Users
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-2.5">Name</th>
                            <th class="px-5 py-2.5">Email</th>
                            <th class="px-5 py-2.5">Role</th>
                            <th class="px-5 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($agency->users as $user)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-5 py-2.5 text-slate-700">{{ $user->name }}</td>
                                <td class="px-5 py-2.5 text-slate-500">{{ $user->email }}</td>
                                <td class="px-5 py-2.5 text-slate-500">{{ $user->roles->pluck('name')->join(', ') }}</td>
                                <td class="px-5 py-2.5">
                                    <x-ui.badge :tone="$user->is_active ? 'green' : 'red'">{{ $user->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-4 text-center text-slate-400">No users.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
