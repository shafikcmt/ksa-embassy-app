@extends('layouts.agency-app')
@section('title', 'Edit Embassy List')
@section('page-title', 'Edit Embassy List')

@section('content')
<x-ui.page-header title="Edit {{ $embassyList->list_no }}" subtitle="Draft — changes aren't submitted until you finalize" icon="bi-pencil-square">
    <x-slot:actions>
        <x-ui.status-badge status="draft" />
        <x-ui.button :href="route('embassy-lists.show', $embassyList)" variant="secondary"><i class="bi bi-arrow-left"></i> Cancel</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="mb-4 flex items-start gap-2.5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
    <i class="bi bi-info-circle-fill mt-0.5 text-brand-500"></i>
    <span>You are editing a <strong>draft</strong> list. Changes are not submitted to the embassy until you finalize.</span>
</div>

<form method="POST" action="{{ route('embassy-lists.update', $embassyList) }}" id="embassyForm">
    @csrf @method('PUT')
    @include('agency.embassy-lists._form', ['mode' => 'edit'])
</form>
@endsection
