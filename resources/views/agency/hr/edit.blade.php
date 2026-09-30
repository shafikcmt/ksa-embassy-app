@extends('layouts.agency-app')
@section('title', 'Edit HR')
@section('page-title', 'Edit HR Profile')

@section('content')
<div class="mx-auto max-w-6xl">
    {{-- Header: back link + title (matches the HR form reference design) --}}
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('hr.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-brand-600"><i class="bi bi-arrow-left"></i> Back to All HR</a>
            <h1 class="mt-1 text-xl font-bold text-slate-900">Edit HR <span class="text-base font-semibold text-slate-500">· {{ $hr->full_name_en }}</span></h1>
        </div>
        <x-ui.button :href="route('hr.show', $hr)" variant="secondary"><i class="bi bi-x-lg"></i> Cancel</x-ui.button>
    </div>

    <form method="POST" action="{{ route('hr.update', $hr) }}" id="hrForm" novalidate>
        @csrf @method('PUT')
        @include('agency.hr._form', ['hr' => $hr, 'mode' => 'edit'])
    </form>
</div>
@endsection
