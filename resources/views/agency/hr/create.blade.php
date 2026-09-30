@extends('layouts.agency-app')
@section('title', 'Add New HR')
@section('page-title', 'Add HR Profile')

@section('content')
<div class="mx-auto max-w-6xl">
    {{-- Slim header — consistent with the HR index / dashboard aesthetic. --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                <i class="bi bi-person-plus text-xl"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900">Add HR Profile</h1>
                <p class="text-xs text-slate-500">Create a new candidate file</p>
            </div>
        </div>
        <x-ui.button :href="route('hr.index')" variant="secondary"><i class="bi bi-arrow-left"></i> All HR</x-ui.button>
    </div>

    <form method="POST" action="{{ route('hr.store') }}" id="hrForm" novalidate>
        @csrf
        @include('agency.hr._form', ['hr' => null, 'mode' => 'create'])
    </form>
</div>
@endsection
