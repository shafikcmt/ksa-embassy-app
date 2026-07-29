@extends('layouts.super-admin-app')
@section('title', $agent->name)
@section('page-title', 'Agent Detail')

@section('content')
<div x-data="{ del: { open: false } }">

    <x-ui.page-header :title="$agent->name" subtitle="Agent Detail" icon="bi-person-badge">
        <x-slot:actions>
            <x-ui.button type="button" variant="secondary" class="cursor-pointer" x-on:click="del.open = true"><i class="bi bi-trash text-rose-500"></i> Delete</x-ui.button>
            <x-ui.button :href="route('super-admin.agents.index')" variant="secondary" class="cursor-pointer"><i class="bi bi-arrow-left"></i> Back</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

        {{-- Agent Details --}}
        <div class="lg:col-span-5">
            <x-ui.card>
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <span class="text-sm font-bold text-slate-800">Agent Details</span>
                    <x-ui.status-badge :status="$agent->status" />
                </div>
                <div class="divide-y divide-slate-100 px-5 py-2 text-sm">
                    <x-ui.dl-row label="Agency">
                        <a href="{{ route('super-admin.agencies.show', $agent->agency_id) }}" class="text-brand-600 transition-colors hover:text-brand-700">{{ $agent->agency->name }}</a>
                    </x-ui.dl-row>
                    <x-ui.dl-row label="Phone">{{ $agent->phone }}</x-ui.dl-row>
                    <x-ui.dl-row label="Email">{{ $agent->email ?? '—' }}</x-ui.dl-row>
                    <x-ui.dl-row label="Address">{{ $agent->address }}</x-ui.dl-row>
                    @if($agent->notes)
                        <x-ui.dl-row label="Notes">{{ $agent->notes }}</x-ui.dl-row>
                    @endif
                    <x-ui.dl-row label="Created">
                        {{ $agent->created_at->format('d M Y') }}
                        @if($agent->createdBy)<span class="text-slate-400">by {{ $agent->createdBy->name }}</span>@endif
                    </x-ui.dl-row>
                    @if($agent->updatedBy && $agent->updated_at != $agent->created_at)
                        <x-ui.dl-row label="Updated">{{ $agent->updated_at->format('d M Y') }} by {{ $agent->updatedBy->name }}</x-ui.dl-row>
                    @endif
                </div>
            </x-ui.card>
        </div>

        {{-- Assigned HR / Candidates --}}
        <div class="lg:col-span-7">
            <x-ui.card>
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                    <i class="bi bi-person-vcard text-violet-500"></i> Assigned HR / Candidates
                </div>
                <x-ui.empty icon="bi-person-vcard" title="HR module — Phase 3" />
            </x-ui.card>
        </div>
    </div>

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
                    <p class="mt-1 text-sm text-slate-500">Delete <strong>{{ $agent->name }}</strong> from <strong>{{ $agent->agency->name }}</strong>? This cannot be undone.</p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="del.open = false">Cancel</x-ui.button>
                <form method="POST" action="{{ route('super-admin.agents.destroy', $agent) }}">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm" class="cursor-pointer"><i class="bi bi-trash"></i> Delete</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
