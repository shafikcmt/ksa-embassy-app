@extends('layouts.super-admin')
@section('title', 'Assign Subscription')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-1"></i> Assign Subscription</h5>
    <a href="{{ route('super-admin.subscriptions.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('super-admin.subscriptions.store') }}">
@csrf
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-2">Subscription Details</div>
            <div class="card-body">
                @include('super-admin.subscriptions._form')
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-1"></i> Assign Subscription
                </button>
            </div>
        </div>
    </div>
</div>
</form>
@endsection
