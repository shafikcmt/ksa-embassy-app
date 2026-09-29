@extends('layouts.erp-app')

@section('title', 'Edit ' . $voucher->voucher_number)
@section('page-title', 'Edit Payment Voucher')

@section('content')
<x-ui.page-header :title="'Edit ' . $voucher->voucher_number" subtitle="Drafts only — approval locks the voucher" icon="bi-pencil-square">
    <x-slot:actions>
        <a href="{{ route('erp.payment-vouchers.show', $voucher) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> Back to voucher</a>
    </x-slot:actions>
</x-ui.page-header>

@include('erp.payment-vouchers._form', ['action' => route('erp.payment-vouchers.update', $voucher), 'method' => 'PUT'])
@endsection
