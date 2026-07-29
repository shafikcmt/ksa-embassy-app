@extends('layouts.agency-app')
@section('title', $agent->name)
@section('page-title', 'Agent Detail')

@section('content')
<div x-data="{ del: { open: false } }">

    <x-ui.page-header :title="$agent->name" subtitle="Agent Profile" icon="bi-person-badge">
        <x-slot:actions>
            @can('update', $agent)
                <x-ui.button :href="route('agents.edit', $agent)" class="cursor-pointer"><i class="bi bi-pencil"></i> Edit</x-ui.button>
            @endcan
            @can('delete', $agent)
                <x-ui.button type="button" variant="secondary" class="cursor-pointer" x-on:click="del.open = true"><i class="bi bi-trash text-rose-500"></i> Delete</x-ui.button>
            @endcan
            <x-ui.button :href="route('agents.index')" variant="secondary" class="cursor-pointer"><i class="bi bi-arrow-left"></i> Back</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

        {{-- Agent Details --}}
        <div class="lg:col-span-5">
            <x-ui.card>
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-info-circle text-brand-600"></i> Agent Details</span>
                    <x-ui.status-badge :status="$agent->status" />
                </div>
                <div class="divide-y divide-slate-100 px-5 py-2 text-sm">
                    <x-ui.dl-row label="Name"><span class="font-semibold text-slate-800">{{ $agent->name }}</span></x-ui.dl-row>
                    <x-ui.dl-row label="Phone">
                        <a href="tel:{{ $agent->phone }}" class="text-brand-600 transition-colors hover:text-brand-700"><i class="bi bi-telephone"></i> {{ $agent->phone }}</a>
                    </x-ui.dl-row>
                    <x-ui.dl-row label="Email">
                        @if($agent->email)
                            <a href="mailto:{{ $agent->email }}" class="text-brand-600 transition-colors hover:text-brand-700 hover:underline">{{ $agent->email }}</a>
                        @else <span class="text-slate-300">—</span> @endif
                    </x-ui.dl-row>
                    <x-ui.dl-row label="Address">{{ $agent->address }}</x-ui.dl-row>
                    @if($agent->notes)
                        <x-ui.dl-row label="Notes">{{ $agent->notes }}</x-ui.dl-row>
                    @endif
                    <x-ui.dl-row label="Added On">{{ $agent->created_at->format('d M Y') }}</x-ui.dl-row>
                    @if($agent->createdBy)
                        <x-ui.dl-row label="Added By">{{ $agent->createdBy->name }}</x-ui.dl-row>
                    @endif
                    @if($agent->updatedBy && $agent->updated_at != $agent->created_at)
                        <x-ui.dl-row label="Last Updated">{{ $agent->updated_at->format('d M Y') }} by {{ $agent->updatedBy->name }}</x-ui.dl-row>
                    @endif
                </div>
            </x-ui.card>
        </div>

        {{-- HR / Candidates + Activity --}}
        <div class="space-y-5 lg:col-span-7">
            <x-ui.card class="overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <span class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <i class="bi bi-person-vcard text-violet-500"></i> Assigned HR / Candidates
                        @if($agent->hrProfiles->count())
                            <x-ui.badge tone="brand">{{ $agent->hrProfiles->count() }}</x-ui.badge>
                        @endif
                    </span>
                    <a href="{{ route('hr.index', ['agent_id' => $agent->id]) }}" class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">View all</a>
                </div>
                @if($agent->hrProfiles->count())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="px-5 py-2.5">Name</th>
                                    <th class="px-5 py-2.5">Nationality</th>
                                    <th class="px-5 py-2.5">Status</th>
                                    <th class="px-5 py-2.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($agent->hrProfiles->take(5) as $hr)
                                    <tr class="transition-colors hover:bg-slate-50">
                                        <td class="px-5 py-2.5">
                                            <a href="{{ route('hr.show', $hr) }}" class="font-semibold text-slate-800 transition-colors hover:text-brand-600">{{ $hr->full_name_en }}</a>
                                        </td>
                                        <td class="px-5 py-2.5 text-slate-600">{{ $hr->nationality }}</td>
                                        <td class="px-5 py-2.5"><x-ui.status-badge :status="$hr->status" /></td>
                                        <td class="px-5 py-2.5">
                                            <div class="flex justify-end">
                                                <a href="{{ route('hr.show', $hr) }}" title="View" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"><i class="bi bi-eye"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty icon="bi-person-vcard" title="No HR profiles assigned" message="This agent has no assigned candidates yet." />
                @endif
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                    <i class="bi bi-clock-history text-slate-500"></i> Recent Activity
                </div>
                <div class="px-5 py-8 text-center text-sm text-slate-400">
                    <i class="bi bi-clipboard-data mb-1 block text-2xl opacity-40"></i>
                    Activity log will show create/update/assign events here.
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Delete dialog --}}
    @can('delete', $agent)
        <div x-show="del.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
            <div @click="del.open = false" x-show="del.open" x-transition.opacity class="absolute inset-0 bg-slate-900/50"></div>
            <div x-show="del.open"
                 x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Delete Agent</h3>
                        <p class="mt-1 text-sm text-slate-500">Are you sure you want to delete <strong>{{ $agent->name }}</strong>? This action cannot be undone.</p>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <x-ui.button type="button" variant="secondary" size="sm" class="cursor-pointer" x-on:click="del.open = false">Cancel</x-ui.button>
                    <form method="POST" action="{{ route('agents.destroy', $agent) }}">
                        @csrf @method('DELETE')
                        <x-ui.button type="submit" variant="danger" size="sm" class="cursor-pointer"><i class="bi bi-trash"></i> Delete Agent</x-ui.button>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection
