@extends('layouts.erp-app')
@section('title', 'MOFA Summary')
@section('page-title', 'MOFA')
@section('content')
@include('erp.mofa._styles')
<div class="mofa" x-data>
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-bold">MOFA Summary</h1><p class="mt-2 text-sm text-slate-500">Manage MOFA visa processing and compliance</p></div>
        <div class="flex flex-wrap items-center gap-4"><span class="text-xs text-slate-500">Generated: {{ now()->format('d-M-Y') }}</span><button class="mf-btn mf-primary" @click="$dispatch('mofa-add')"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add MOFA Entry</button></div>
    </header>
    <div class="mf-grid mb-6">
        @foreach(['total'=>['Total Entries','bi-files','processing'],'active'=>['Active','bi-check-circle','active'],'expiring'=>['Expiring Soon','bi-hourglass-split','expiring'],'expired'=>['Expired','bi-calendar-x','expired']] as $key=>$card)
        <a href="{{ route('erp.mofa', $key==='total'?[]:['status'=>$key]) }}" class="mf-card flex items-center justify-between gap-3"><div><p class="text-sm text-slate-500">{{ $card[0] }}</p><p class="mt-2 text-3xl font-semibold text-blue-950">{{ number_format($stats[$key]) }}</p></div><i class="bi {{ $card[1] }} mf-{{ $card[2] }} rounded-lg p-3 text-xl" aria-hidden="true"></i></a>
        @endforeach
    </div>
    <section class="mf-card mb-5">
        <form method="get" class="flex flex-wrap items-end gap-3" x-data="{loading:false}" @submit="loading=true" :aria-busy="loading">
            <div class="min-w-[220px] flex-1"><label class="mf-label" for="mf-search">Search entries</label><input class="mf-input" id="mf-search" name="q" value="{{ request('q') }}" placeholder="Name, passport, visa or MOFA number" @input.debounce.300ms="$el.form.requestSubmit()"></div>
            <div><label class="mf-label" for="mf-status">Status</label><select id="mf-status" name="status" class="mf-input"><option value="">All statuses</option>@foreach(\App\Models\MofaEntry::STATUSES as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></div>
            @foreach(['from'=>'From date','to'=>'To date'] as $key=>$label)<div><label for="mf-{{ $key }}" class="mf-label">{{ $label }}</label><input id="mf-{{ $key }}" class="mf-input" type="date" name="{{ $key }}" value="{{ request($key) }}"></div>@endforeach
            <button class="mf-btn" :disabled="loading"><span x-text="loading ? 'Searching…' : 'Apply filters'">Apply filters</span></button><a class="mf-btn" href="{{ route('erp.mofa') }}">Reset</a>
        </form>
        @if($errors->any())<p class="mf-error" role="alert">{{ $errors->first() }}</p>@endif
    </section>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-slate-500">{{ $entries->total() }} records @if($entries->total() > 0) <span class="mx-2">/</span> Scroll to see all passenger details @endif</p><div class="flex flex-wrap gap-2">
        <a class="mf-btn" href="{{ route('erp.mofa.export',request()->only('q','status','from','to')) }}"><i class="bi bi-download" aria-hidden="true"></i> Export CSV</a>
        <a class="mf-btn" target="_blank" rel="noopener" href="{{ route('erp.mofa.print',request()->only('q','status','from','to')) }}">Print</a>
        @if(auth()->user()->isAgencyAdmin())<a class="mf-btn" href="{{ route('erp.mofa.import.form') }}">Import CSV</a>@endif
    </div></div>
    @if($entries->isEmpty())
    <section class="rounded-lg border border-slate-200 bg-white px-4 py-8 text-center sm:px-6" aria-labelledby="mf-empty-title">
        <i class="bi bi-passport text-4xl text-blue-500" aria-hidden="true"></i>
        <h2 id="mf-empty-title" class="mt-3 text-lg font-bold">{{ request()->hasAny(['q','status','from','to']) ? 'No matching MOFA entries' : 'No MOFA entries yet' }}</h2>
        <p class="mx-auto mt-2 mb-4 max-w-md text-sm text-slate-500">Add your first MOFA record or adjust your filters.</p>
        <button class="mf-btn mf-primary" @click="$dispatch('mofa-add')">+ Add MOFA Entry</button>
    </section>
    @else
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white" tabindex="0" aria-label="MOFA entries, scroll horizontally">
        <table class="mf-table"><thead><tr>@foreach(\App\Http\Controllers\Erp\MofaController::COLUMNS as $column)<th scope="col">{{ $column }}</th>@endforeach<th scope="col">Actions</th></tr></thead><tbody>
        @foreach($entries as $entry)
        <tr><td>{{ $entries->firstItem()+$loop->index }}</td>
            @foreach(\App\Http\Controllers\Erp\MofaController::FIELDS as $field)
            <td @class(['text-right'=>in_array($field,['age','left_day'])])>{{ \App\Http\Controllers\Erp\MofaController::value($entry,$field) ?? '—' }}
            @if($field==='full_name')<br><span class="mf-badge mf-{{ $entry->status }}"><i class="bi bi-circle-fill" aria-hidden="true"></i> {{ $entry->statusLabel() }}</span>@endif</td>
            @endforeach
            <td><div class="mf-actions"><a href="{{ route('erp.mofa.show',$entry) }}" aria-label="View {{ $entry->full_name }}"><i class="bi bi-eye"></i></a><button @click="$dispatch('mofa-edit',{id:{{ $entry->id }}})" aria-label="Edit {{ $entry->full_name }}"><i class="bi bi-pencil"></i></button><a target="_blank" rel="noopener" href="{{ route('erp.mofa.print-pdf',$entry) }}" aria-label="Print {{ $entry->full_name }}"><i class="bi bi-printer"></i></a><form method="post" action="{{ route('erp.mofa.destroy',$entry) }}" @submit="if (!confirm('Delete this MOFA entry?')) $event.preventDefault()">@csrf @method('DELETE')<button aria-label="Delete {{ $entry->full_name }}" class="text-red-700"><i class="bi bi-trash"></i></button></form></div></td>
        </tr>
        @endforeach
        </tbody></table>
    </div><div class="mt-5">{{ $entries->links() }}</div>
    @endif
</div>
{{-- Outside the .mofa wrapper so its legacy focus/heading CSS doesn't reach the modal. --}}
@include('erp.mofa._modal')
@endsection
