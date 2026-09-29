@extends('layouts.erp-app')

@section('title', 'Edit ' . $invoice->invoice_number)
@section('page-title', 'Edit Invoice')

@section('content')
<x-ui.page-header :title="'Edit ' . $invoice->invoice_number" subtitle="Editable until it is marked paid or cancelled" icon="bi-pencil-square">
    <x-slot:actions>
        <a href="{{ route('erp.invoices.show', $invoice) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> Back to invoice</a>
    </x-slot:actions>
</x-ui.page-header>

@include('erp.invoices._form', ['action' => route('erp.invoices.update', $invoice), 'method' => 'PUT'])
@endsection
