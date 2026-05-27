@extends('layouts.super-admin')
@section('title', 'Edit Agency')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil me-1"></i> Edit Agency: {{ $agency->name }}</h5>
    <div>
        <a href="{{ route('super-admin.agencies.show', $agency) }}" class="btn btn-sm btn-outline-info me-1">
            <i class="bi bi-eye"></i> View
        </a>
        <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="{{ route('super-admin.agencies.update', $agency) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-building me-1"></i> Agency Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Agency Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $agency->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">License Number</label>
                        <input type="text" name="license_number" class="form-control"
                            value="{{ old('license_number', $agency->license_number) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">RL Number</label>
                        <input type="text" name="rl_number" class="form-control"
                            value="{{ old('rl_number', $agency->rl_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control"
                            value="{{ old('phone', $agency->phone) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control"
                            value="{{ old('email', $agency->email) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">License Expiry</label>
                        <input type="date" name="license_expiry_date" class="form-control"
                            value="{{ old('license_expiry_date', $agency->license_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $agency->address) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $agency->status) === 'active')>Active</option>
                            <option value="suspended" @selected(old('status', $agency->status) === 'suspended')>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        @if($agency->logo)
                            <small class="text-muted">Current: {{ basename($agency->logo) }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
                <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection
