@extends('layouts.agency-app')
@section('title', 'Add New HR')
@section('page-title', 'Add HR Profile')

@section('content')
<div class="mx-auto max-w-6xl">
    {{-- Header: back link + title (matches the HR form reference design) --}}
    <div class="mb-4">
        <a href="{{ route('hr.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-brand-600"><i class="bi bi-arrow-left"></i> Back to All HR</a>
        <h1 class="mt-1 text-xl font-bold text-slate-900">Add New HR</h1>
    </div>

    <form method="POST" action="{{ route('hr.store') }}" id="hrForm" novalidate>
        @csrf
        @include('agency.hr._form', ['hr' => null, 'mode' => 'create'])
    </form>
</div>
@endsection
