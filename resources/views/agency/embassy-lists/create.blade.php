@extends('layouts.agency-app')
@section('title', 'Create Embassy List')
@section('page-title', 'Create Embassy List')

@section('content')
<x-ui.page-header title="Create Embassy List" subtitle="Pick candidates and assign a category to each" icon="bi-list-ol">
    <x-slot:actions>
        <x-ui.button :href="route('embassy-lists.index')" variant="secondary"><i class="bi bi-arrow-left"></i> All Lists</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('embassy-lists.store') }}" id="embassyForm">
    @csrf
    @include('agency.embassy-lists._form', ['mode' => 'create'])
</form>
@endsection
