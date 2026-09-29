@extends('layouts.erp-app')

@section('title', 'Add Medical Entry')
@section('page-title', 'Medical')

@section('content')
<x-ui.page-header title="Add Medical Entry" subtitle="Candidate → medical center → dates & status" icon="bi-heart-pulse">
    <x-slot:actions>
        <a href="{{ route('erp.medical') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> All entries</a>
    </x-slot:actions>
</x-ui.page-header>

@include('erp.medical._form', [
    'action'    => route('erp.medical.store'),
    'method'    => 'POST',
    'cancelUrl' => route('erp.medical'),
])
@endsection
