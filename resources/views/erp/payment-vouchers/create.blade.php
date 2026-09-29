@extends('layouts.erp-app')

@section('title', 'New Payment Voucher')
@section('page-title', 'New Payment Voucher')

@section('content')
<x-ui.page-header title="New Payment Voucher" subtitle="Payee → payment details → items" icon="bi-wallet2">
    <x-slot:actions>
        <a href="{{ route('erp.payment-vouchers.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="bi bi-arrow-left"></i> All vouchers</a>
    </x-slot:actions>
</x-ui.page-header>

@include('erp.payment-vouchers._form', ['action' => route('erp.payment-vouchers.store'), 'method' => 'POST'])
@endsection
