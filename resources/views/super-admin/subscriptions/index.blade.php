@extends('layouts.super-admin-app')
@section('title', 'Subscriptions')
@section('page-title', 'Subscriptions')

@section('content')

<x-ui.page-header title="Subscriptions" subtitle="Assign and manage agency subscriptions" icon="bi-credit-card">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.subscriptions.create')" class="cursor-pointer"><i class="bi bi-plus-lg"></i> Assign Subscription</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

{{-- Filters --}}
<x-ui.card class="mb-5">
    <form method="GET" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-12">
        <select name="agency_id" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-5">
            <option value="">All Agencies</option>
            @foreach($agencies as $ag)
                <option value="{{ $ag->id }}" @selected(request('agency_id') == $ag->id)>{{ $ag->name }}</option>
            @endforeach
        </select>
        <select name="status" class="h-10 rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 lg:col-span-4">
            <option value="">All Status</option>
            @foreach(['trial','active','expired','suspended'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <div class="flex gap-2 lg:col-span-3">
            <x-ui.button type="submit" class="flex-1 cursor-pointer"><i class="bi bi-funnel"></i> Filter</x-ui.button>
            @if(request()->hasAny(['status','agency_id']))
                <x-ui.button :href="route('super-admin.subscriptions.index')" variant="secondary" size="icon" title="Clear filters" class="cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i></x-ui.button>
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
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Period</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($subscriptions as $sub)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-400">{{ $loop->iteration + ($subscriptions->currentPage() - 1) * $subscriptions->perPage() }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $sub->agency->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $sub->plan->name }}</td>
                        <td class="px-4 py-3">
                            <div class="text-slate-600">{{ $sub->start_date->format('d M Y') }} → {{ $sub->end_date->format('d M Y') }}</div>
                            @if($sub->isActive())
                                <div class="text-xs text-emerald-600">{{ $sub->daysRemaining() }} days left</div>
                            @endif
                        </td>
                        <td class="px-4 py-3"><x-ui.status-badge :status="$sub->status" /></td>
                        <td class="px-4 py-3">
                            @php $pc = $sub->payment_status; @endphp
                            <x-ui.badge :tone="$pc === 'paid' ? 'green' : ($pc === 'pending' ? 'amber' : 'slate')">{{ ucfirst($pc) }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-700">${{ number_format($sub->amount, 2) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1 whitespace-nowrap">
                                @if($sub->payment_status === 'pending')
                                    <form method="POST" action="{{ route('super-admin.subscriptions.approve', $sub) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Approve Payment" class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-emerald-600 transition-colors hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"><i class="bi bi-check-circle"></i></button>
                                    </form>
                                @endif
                                <a href="{{ route('super-admin.subscriptions.edit', $sub) }}" title="Edit" class="grid h-8 w-8 place-items-center rounded-lg text-brand-600 transition-colors hover:bg-brand-50"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('super-admin.subscriptions.destroy', $sub) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete" onclick="return confirm('Delete this subscription?')"
                                        class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-0"><x-ui.empty icon="bi-credit-card" title="No subscriptions found" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $subscriptions->withQueryString()->links() }}</div>
    @endif
</x-ui.card>
@endsection
