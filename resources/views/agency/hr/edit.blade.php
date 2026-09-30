@extends('layouts.agency-app')
@section('title', 'Edit HR')
@section('page-title', 'Edit HR Profile')

@section('content')
<div class="hr-page mx-auto max-w-7xl">
    {{-- Slim header — consistent with the HR index / dashboard aesthetic. --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                <i class="bi bi-pencil-square text-xl"></i>
            </span>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900">Edit Profile</h1>
                <p class="truncate text-xs text-slate-500">{{ $hr->full_name_en }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button :href="route('hr.show', $hr)" variant="secondary"><i class="bi bi-arrow-left"></i> Cancel</x-ui.button>
            <x-ui.button :href="route('hr.index')" variant="secondary"><i class="bi bi-people"></i> All HR</x-ui.button>
        </div>
    </div>

    <form method="POST" action="{{ route('hr.update', $hr) }}" id="hrForm" novalidate>
        @csrf @method('PUT')
        @include('agency.hr._form', ['hr' => $hr, 'mode' => 'edit'])
    </form>
</div>
@endsection
