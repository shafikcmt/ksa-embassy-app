@extends('layouts.agency')
@section('title', 'Edit HR')
@section('page-title', 'Edit HR')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0 fw-bold">Edit: {{ $hr->full_name_en }}</h5>
    </div>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-dark"><i class="bi bi-people me-1"></i> All HR</a>
</div>

<form method="POST" action="{{ route('hr.update', $hr) }}" id="hrForm">
    @csrf @method('PUT')
    @include('agency.hr._form', ['hr' => $hr])

    <div class="d-flex justify-content-between gap-2 mb-4">
        <a href="{{ route('hr.show', $hr) }}" class="btn btn-outline-secondary px-4">Cancel</a>
        <div class="d-flex gap-2">
            <button type="reset" class="btn btn-danger px-4"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
            <button type="submit" class="btn btn-dark px-4"><i class="bi bi-check-lg me-1"></i> Save</button>
        </div>
    </div>
</form>
@endsection
