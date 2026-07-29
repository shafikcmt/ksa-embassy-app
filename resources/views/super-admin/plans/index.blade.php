@extends('layouts.super-admin-app')
@section('title', 'Plans')
@section('page-title', 'Plans')

@section('content')

<x-ui.page-header title="Subscription Plans" subtitle="Define the plans agencies can subscribe to" icon="bi-grid-3x3-gap">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.plans.create')" class="cursor-pointer"><i class="bi bi-plus-lg"></i> New Plan</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @forelse($plans as $plan)
        <x-ui.card class="flex flex-col {{ !$plan->is_active ? 'opacity-75' : '' }}">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <span class="font-bold text-slate-800">{{ $plan->name }}</span>
                <x-ui.status-badge :status="$plan->is_active ? 'active' : 'inactive'" />
            </div>
            <div class="flex-1 p-4">
                <div class="mb-1 text-2xl font-extrabold text-brand-600">
                    {{ $plan->priceLabel('') }}
                    <span class="text-sm font-normal text-slate-400">/ {{ $plan->duration_days }}d</span>
                </div>
                @if($plan->description)
                    <p class="mb-3 text-sm text-slate-500">{{ $plan->description }}</p>
                @endif
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between py-1.5"><dt class="text-slate-400">Max HR</dt><dd class="font-semibold text-slate-700">{{ $plan->max_hr == 9999 ? 'Unlimited' : number_format($plan->max_hr) }}</dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-slate-400">Max Users</dt><dd class="font-semibold text-slate-700">{{ $plan->max_users }}</dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-slate-400">Max Agents</dt><dd class="font-semibold text-slate-700">{{ $plan->max_agents == 999 ? 'Unlimited' : $plan->max_agents }}</dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-slate-400">Embassy Lists/mo</dt><dd class="font-semibold text-slate-700">{{ $plan->max_embassy_lists_monthly == 999 ? 'Unlimited' : $plan->max_embassy_lists_monthly }}</dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-slate-400">PDF/mo</dt><dd class="font-semibold text-slate-700">{{ $plan->max_pdf_monthly == 9999 ? 'Unlimited' : $plan->max_pdf_monthly }}</dd></div>
                    <div class="flex justify-between py-1.5"><dt class="text-slate-400">Storage</dt><dd class="font-semibold text-slate-700">{{ $plan->storage_limit_mb >= 1024 ? round($plan->storage_limit_mb/1024).'GB' : $plan->storage_limit_mb.'MB' }}</dd></div>
                </dl>
            </div>
            <div class="flex items-center gap-2 border-t border-slate-100 px-4 py-3">
                <x-ui.button :href="route('super-admin.plans.edit', $plan)" variant="secondary" size="sm" class="flex-1 cursor-pointer"><i class="bi bi-pencil"></i> Edit</x-ui.button>
                <form method="POST" action="{{ route('super-admin.plans.destroy', $plan) }}">
                    @csrf @method('DELETE')
                    <button type="submit" title="Delete" onclick="return confirm('Delete this plan? Agencies with active subscriptions on this plan will be affected.')"
                        class="grid h-8 w-8 cursor-pointer place-items-center rounded-lg text-rose-500 transition-colors hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"><i class="bi bi-trash"></i></button>
                </form>
                <span class="ml-auto text-xs text-slate-400">{{ $plan->subscriptions_count }} subs</span>
            </div>
        </x-ui.card>
    @empty
        <div class="sm:col-span-2 xl:col-span-4">
            <x-ui.card>
                <x-ui.empty icon="bi-grid-3x3-gap" title="No plans created yet"
                    :actionUrl="route('super-admin.plans.create')" actionLabel="Create the first plan" />
            </x-ui.card>
        </div>
    @endforelse
</div>
@endsection
