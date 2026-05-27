@extends('layouts.super-admin')
@section('title', 'Create Agency')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-1"></i> Create New Agency</h5>
    <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<form method="POST" action="{{ route('super-admin.agencies.store') }}" enctype="multipart/form-data">
@csrf
<div class="row g-3">

    {{-- Agency Info --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header py-2">
                <i class="bi bi-building me-1"></i> Agency Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Agency Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">License Number</label>
                        <input type="text" name="license_number" class="form-control @error('license_number') is-invalid @enderror"
                            value="{{ old('license_number') }}">
                        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">RL Number</label>
                        <input type="text" name="rl_number" class="form-control @error('rl_number') is-invalid @enderror"
                            value="{{ old('rl_number') }}">
                        @error('rl_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">License Expiry Date</label>
                        <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status','active') === 'active')>Active</option>
                            <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>

        {{-- Admin Account --}}
        <div class="card mb-3">
            <div class="card-header py-2">
                <i class="bi bi-person-badge me-1"></i> Agency Admin Account
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Admin Name <span class="text-danger">*</span></label>
                        <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror"
                            value="{{ old('admin_name') }}" required>
                        @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Admin Email <span class="text-danger">*</span></label>
                        <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror"
                            value="{{ old('admin_email') }}" required>
                        @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="admin_password"
                            class="form-control @error('admin_password') is-invalid @enderror"
                            required minlength="8">
                        @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Subscription --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-2">
                <i class="bi bi-credit-card me-1"></i> Initial Subscription
            </div>
            <div class="card-body">
                <label class="form-label fw-semibold">Assign Plan</label>
                <select name="plan_id" class="form-select mb-1">
                    <option value="">— No subscription yet —</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                            {{ $plan->name }} ({{ $plan->price > 0 ? '$'.$plan->price.'/mo' : 'Free' }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Will start a trial subscription with the selected plan.</small>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-1"></i> Create Agency
                </button>
                <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                    Cancel
                </a>
            </div>
        </div>
    </div>

</div>
</form>
@endsection
