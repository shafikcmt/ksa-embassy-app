@extends('layouts.erp-app')

@section('title', 'Edit Medical Entry')
@section('page-title', 'Medical')

@section('content')
<x-ui.page-header title="Edit Medical Entry" :subtitle="$entry->full_name . ' · ' . $entry->passport_no" icon="bi-heart-pulse">
    <x-slot:actions>
        <a href="{{ route('erp.medical.show', $entry) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> Back to entry</a>
    </x-slot:actions>
</x-ui.page-header>

@include('erp.medical._form', [
    'action'    => route('erp.medical.update', $entry),
    'method'    => 'PUT',
    'cancelUrl' => route('erp.medical.show', $entry),
])
@endsection
