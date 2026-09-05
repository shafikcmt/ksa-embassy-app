@extends('layouts.agency-app')
@section('title', 'Edit HR')
@section('page-title', 'Edit HR Profile')

@section('content')
<div class="mx-auto max-w-6xl">
    {{-- Slim header — consistent with the HR index / dashboard aesthetic. --}}
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
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
        {{-- Two-column: main form (left, wider) + Passport Auto-Fill sidebar (right,
             sticky). On mobile it collapses to one column with the sidebar on top
             (order-first) so the auto-fill affordance is seen first. --}}
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
            <div class="lg:col-start-1 lg:row-start-1">
                @include('agency.hr._form', ['hr' => $hr, 'mode' => 'edit'])
            </div>
            <aside class="order-first lg:order-none lg:col-start-2 lg:row-start-1 lg:sticky lg:top-24">
                @include('agency.hr._passport-autofill')
            </aside>
        </div>
    </form>
</div>
@endsection
