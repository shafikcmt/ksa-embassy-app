@extends('layouts.agency-app')
@section('title', 'Add New HR')
@section('page-title', 'Add HR Profile')

@section('content')
<x-ui.page-header title="Add HR Profile" subtitle="Create a new candidate file" icon="bi-person-plus">
    <x-slot:actions>
        <x-ui.button :href="route('hr.index')" variant="secondary"><i class="bi bi-arrow-left"></i> All HR</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="mx-auto max-w-4xl">
    <form method="POST" action="{{ route('hr.store') }}" id="hrForm" novalidate>
        @csrf
        @include('agency.hr._form', ['hr' => null, 'mode' => 'create'])
    </form>
</div>
@endsection
