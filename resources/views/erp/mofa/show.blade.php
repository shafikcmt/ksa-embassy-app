@extends('layouts.erp-app')
@section('title', 'MOFA Entry')
@section('content')
@include('erp.mofa._styles')
<div class="mofa"><a class="mf-btn mb-5" href="{{ route('erp.mofa') }}">Back to MOFA Summary</a><div class="mf-card"><header class="mb-6 flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-bold">{{ $entry->full_name }}</h1><span class="mf-badge mf-{{ $entry->status }}">{{ $entry->statusLabel() }}</span></div><div class="flex flex-wrap gap-2"><a class="mf-btn mf-primary" href="{{ route('erp.mofa.edit',$entry) }}">Edit entry</a><a class="mf-btn" target="_blank" rel="noopener" href="{{ route('erp.mofa.print-pdf',$entry) }}">Print</a></div></header><dl class="mf-form-grid">@foreach(\App\Http\Controllers\Erp\MofaController::FIELDS as $index=>$field)<div><dt class="mf-label text-slate-500">{{ \App\Http\Controllers\Erp\MofaController::COLUMNS[$index+1] }}</dt><dd class="break-words">{{ \App\Http\Controllers\Erp\MofaController::value($entry,$field) ?? '—' }}</dd></div>@endforeach<div><dt class="mf-label">MOFA Expiry Date</dt><dd>{{ $entry->mofa_expiry_date?->format('d-M-Y') ?? '—' }}</dd></div></dl></div></div>
@endsection
