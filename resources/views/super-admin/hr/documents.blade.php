@extends('layouts.super-admin-app')
@section('title', 'Documents — ' . $hr->full_name_en)
@section('page-title', 'HR Documents')

@section('content')

<x-ui.page-header title="{{ $hr->full_name_en }} — Documents" subtitle="{{ $hr->agency?->name ?? '—' }} · Read-only preview" icon="bi-file-earmark-pdf">
    <x-slot:actions>
        <x-ui.button :href="route('super-admin.hr.show', $hr)" variant="secondary" class="cursor-pointer"><i class="bi bi-arrow-left"></i> Back</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@php
    $docs = [
        ['icon' => 'bi-file-earmark-person', 'title' => 'Application Form',        'preview' => 'hr.print.application',            'download' => 'hr.download.application'],
        ['icon' => 'bi-envelope',            'title' => 'Forwarding Letter',       'preview' => 'hr.print.forwarding-letter',      'download' => 'hr.download.forwarding-letter'],
        ['icon' => 'bi-file-earmark-text',   'title' => 'Employment Agreement',    'preview' => 'hr.print.employment-agreement',   'download' => 'hr.download.employment-agreement'],
        ['icon' => 'bi-card-checklist',      'title' => 'Checklist',               'preview' => 'hr.print.checklist',              'download' => 'hr.download.checklist'],
    ];
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    @foreach($docs as $doc)
        <x-ui.card>
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800"><i class="bi {{ $doc['icon'] }} text-brand-600"></i> {{ $doc['title'] }}</div>
            <div class="flex gap-2 p-4">
                <x-ui.button :href="route($doc['preview'], $hr)" variant="secondary" size="sm" class="cursor-pointer" target="_blank"><i class="bi bi-eye"></i> Preview</x-ui.button>
                <x-ui.button :href="route($doc['download'], $hr)" size="sm" class="cursor-pointer"><i class="bi bi-download"></i> Download PDF</x-ui.button>
            </div>
        </x-ui.card>
    @endforeach

    {{-- Complete File --}}
    <x-ui.card class="border-brand-200 md:col-span-2">
        <div class="flex flex-col items-start justify-between gap-3 p-4 sm:flex-row sm:items-center">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-file-earmark-zip text-brand-600"></i> Complete File (All Documents)</h2>
            <x-ui.button :href="route('hr.download.full-file', $hr)" size="sm" class="cursor-pointer"><i class="bi bi-download"></i> Download Full File PDF</x-ui.button>
        </div>
    </x-ui.card>
</div>

{{-- Document Generation Log --}}
@php
    $docLog = \App\Models\GeneratedDocument::where('hr_profile_id', $hr->id)
        ->with(['generatedBy', 'agency'])
        ->latest('created_at')
        ->limit(15)
        ->get();
@endphp
@if($docLog->isNotEmpty())
    <x-ui.card class="mt-5 overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
            <span class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-clock-history text-violet-500"></i> Document Activity Log</span>
            <span class="text-xs text-slate-400">Last 15 events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-2.5">Document</th>
                        <th class="px-5 py-2.5">Action</th>
                        <th class="px-5 py-2.5">Agency</th>
                        <th class="px-5 py-2.5">By</th>
                        <th class="px-5 py-2.5">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($docLog as $log)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-2.5 text-slate-700">{{ ucwords(str_replace('_', ' ', $log->document_type)) }}</td>
                            <td class="px-5 py-2.5">
                                @if($log->action === 'download')
                                    <x-ui.badge tone="brand">Download</x-ui.badge>
                                @else
                                    <x-ui.badge tone="slate">Preview</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-slate-600">{{ $log->agency?->name ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-slate-600">{{ $log->generatedBy?->name ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-slate-400">{{ $log->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endif
@endsection
