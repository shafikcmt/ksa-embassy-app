@extends('layouts.agency')
@section('title', 'Add New HR')
@section('page-title', 'Add New HR')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('hr.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0 fw-bold">Add New HR / Candidate</h5>
    </div>
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-dark"><i class="bi bi-people me-1"></i> All HR</a>
</div>

<form method="POST" action="{{ route('hr.store') }}" id="hrForm">
    @csrf
    @include('agency.hr._form', ['hr' => null])

    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="reset" class="btn btn-danger px-4"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
        <button type="submit" class="btn btn-dark px-4"><i class="bi bi-check-lg me-1"></i> Save</button>
    </div>
</form>
@endsection
