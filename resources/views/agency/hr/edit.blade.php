@extends('layouts.agency-app')
@section('title', 'Edit HR')
@section('page-title', 'Edit HR Profile')

@section('content')
<x-ui.page-header title="Edit Profile" subtitle="{{ $hr->full_name_en }}" icon="bi-pencil-square">
    <x-slot:actions>
        <x-ui.button :href="route('hr.show', $hr)" variant="secondary"><i class="bi bi-arrow-left"></i> Cancel</x-ui.button>
        <x-ui.button :href="route('hr.index')" variant="secondary"><i class="bi bi-people"></i> All HR</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('hr.update', $hr) }}" id="hrForm" novalidate>
    @csrf @method('PUT')
    @include('agency.hr._form', ['hr' => $hr, 'mode' => 'edit'])
</form>
@endsection
