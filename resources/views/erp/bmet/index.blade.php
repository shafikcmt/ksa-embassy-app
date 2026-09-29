@extends('layouts.erp-app')
@section('title', 'BMET Clearance')
@section('page-title', 'BMET Clearance')
@section('content')
@include('erp.bmet._styles')
@php($listQuery = request()->only('q','status','agent_id','from','to','sort'))
<div class="bmet" x-data="{allColumns:false}">
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-bold">BMET Clearance</h1><p class="mt-2 text-sm text-gray-500">Manage BMET clearance certificates and compliance</p></div>
        <div class="flex flex-wrap items-center gap-4"><span class="text-xs text-gray-500">Generated: {{ now()->format('d-M-Y') }}</span><button class="bm-btn bm-primary" @click="$dispatch('bmet-add')"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add BMET Entry</button></div>
    </header>
    @if(session('bmet_toast'))<div x-data="{show:true}" x-init="setTimeout(()=>show=false,3000)" x-show="show" role="status" class="mb-5 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ session('bmet_toast') }}</div>@endif
    <div class="bm-stats mb-6">
        @foreach(['total'=>['Total Entries','bi-files'],'cleared'=>['Cleared','bi-check-circle'],'pending'=>['Pending','bi-hourglass-split'],'expired'=>['Expired','bi-calendar-x']] as $key=>[$label,$icon])
        <a class="bm-card flex items-center justify-between gap-3" href="{{ route('erp.bmet.index',$key==='total'?[]:['status'=>$key]) }}"><div><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-3xl font-semibold text-blue-950">{{ number_format($stats[$key]) }}</p></div><i class="bi {{ $icon }} bm-{{ $key }} rounded-lg p-3 text-xl" aria-hidden="true"></i></a>
        @endforeach
    </div>
    <section class="bm-card mb-5">
        <form method="get" action="{{ route('erp.bmet.index') }}" class="flex flex-wrap items-end gap-3" x-data="{loading:false}" @submit="loading=true" :aria-busy="loading">
            <div class="min-w-[220px] flex-1"><label class="bm-label" for="bm-search">Search records</label><div class="relative"><input class="bm-input pr-9" id="bm-search" name="q" value="{{ request('q') }}" placeholder="Passenger, passport, EC or reference" @input.debounce.300ms="$el.form.requestSubmit()"><i x-show="loading" class="bi bi-arrow-repeat animate-spin absolute right-3 top-3" aria-hidden="true"></i></div></div>
            <div><label class="bm-label" for="bm-filter-status">Status</label><select class="bm-input" id="bm-filter-status" name="status"><option value="">All statuses ({{ $stats['total'] }})</option>@foreach(\App\Models\BmetEntry::STATUSES as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }} ({{ $stats[$key] }})</option>@endforeach</select></div>
            <div><label class="bm-label" for="bm-filter-agent">Agent</label><select class="bm-input" id="bm-filter-agent" name="agent_id"><option value="">All agents</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected((string)request('agent_id')===(string)$agent->id)>{{ $agent->name }}</option>@endforeach</select></div>
            @foreach(['from'=>'EC date from','to'=>'EC date to'] as $key=>$label)<div><label for="bm-filter-{{ $key }}" class="bm-label">{{ $label }}</label><input id="bm-filter-{{ $key }}" class="bm-input" type="date" name="{{ $key }}" value="{{ request($key) }}"></div>@endforeach
            <div><label class="bm-label" for="bm-sort">Sort by</label><select class="bm-input" id="bm-sort" name="sort">@foreach(\App\Http\Controllers\Erp\BmetController::SORTS as $key=>$label)<option value="{{ $key }}" @selected(request('sort','date_desc')===$key)>{{ $label }}</option>@endforeach</select></div>
            <button class="bm-btn" :disabled="loading" x-text="loading ? 'Searching…' : 'Apply filters'">Apply filters</button><a class="bm-btn" href="{{ route('erp.bmet.index') }}">Reset</a>
        </form>
    </section>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-gray-500">{{ $entries->total() }} clearance records</p><div class="flex flex-wrap items-center gap-2"><label class="mr-3 flex items-center gap-2 text-xs md:hidden"><input type="checkbox" x-model="allColumns"> Show all columns</label><a class="bm-btn" href="{{ route('erp.bmet.export',$listQuery) }}"><i class="bi bi-download" aria-hidden="true"></i> Export CSV</a><a class="bm-btn" href="{{ route('erp.bmet.print',$listQuery) }}" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Print PDF</a>@if(auth()->user()->isAgencyAdmin())<a class="bm-btn" href="{{ route('erp.manpower.import.form') }}">Import legacy CSV</a>@endif</div></div>
    @if($entries->isEmpty())
        <section class="bm-card py-12 text-center"><i class="bi bi-patch-check text-4xl text-blue-500" aria-hidden="true"></i><h2 class="mt-4 text-lg font-bold">{{ array_filter($listQuery) ? 'No matching BMET entries' : 'No BMET entries yet' }}</h2><p class="my-3 text-sm text-gray-500">{{ array_filter($listQuery) ? 'Try another search or reset your filters.' : 'Add your first BMET clearance record.' }}</p><button class="bm-btn bm-primary" @click="$dispatch('bmet-add')">+ Add Entry</button></section>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white" tabindex="0" aria-label="BMET records, scroll horizontally">
            <table class="bm-table" :class="allColumns ? 'bm-all' : ''"><thead><tr>@foreach(\App\Http\Controllers\Erp\BmetController::COLUMNS as $column)<th scope="col" @class(['bm-secondary'=>!in_array($loop->index,[1,3,7])])>{{ $column }}</th>@endforeach<th scope="col">Actions</th></tr></thead><tbody>
            @foreach($entries as $entry)
            <tr><td class="bm-secondary">{{ $entries->firstItem()+$loop->index }}</td>
                @foreach(\App\Http\Controllers\Erp\BmetController::FIELDS as $field)
                <td @class(['bm-secondary'=>!in_array($field,['full_name','passport_number','ec_date'])])>{{ \App\Http\Controllers\Erp\BmetController::value($entry,$field) ?: '—' }}@if($field==='full_name')<div class="mt-2">@include('erp.bmet._badge',['entry'=>$entry])</div>@endif</td>
                @endforeach
                <td><div class="bm-actions"><a href="{{ route('erp.bmet.show',$entry) }}" aria-label="View {{ $entry->full_name }}"><i class="bi bi-eye"></i></a><button @click="$dispatch('bmet-edit',{id:{{ $entry->id }}})" aria-label="Edit {{ $entry->full_name }}"><i class="bi bi-pencil"></i></button><a href="{{ route('erp.bmet.print-pdf',$entry) }}" target="_blank" rel="noopener" aria-label="Print {{ $entry->full_name }}"><i class="bi bi-printer"></i></a><form method="post" action="{{ route('erp.bmet.destroy',$entry) }}" @submit="if(!confirm('Delete this BMET entry?')) $event.preventDefault()">@csrf @method('DELETE')<button class="text-red-700" aria-label="Delete {{ $entry->full_name }}"><i class="bi bi-trash"></i></button></form></div></td>
            </tr>
            @endforeach
            </tbody></table>
        </div><div class="mt-5">{{ $entries->links() }}</div>
    @endif
</div>
{{-- Outside the .bmet wrapper so its legacy focus/heading CSS doesn't reach the modal. --}}
@include('erp.bmet._modal')
@endsection
