@extends('layouts.erp-app')

@section('title', 'New Invoice')
@section('page-title', 'New Invoice')

@section('content')
<x-ui.page-header title="New Invoice" subtitle="Details → line items → adjust & review" icon="bi-receipt">
    <x-slot:actions>
        <a href="{{ route('erp.invoices.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> All invoices</a>
    </x-slot:actions>
</x-ui.page-header>

@include('erp.invoices._form', ['action' => route('erp.invoices.store'), 'method' => 'POST'])
@endsection
