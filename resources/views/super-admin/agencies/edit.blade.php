@extends('layouts.super-admin')
@section('title', 'Edit Agency')

@section('content')
@php
    $sub = $agency->activeSubscription;
    $printLogo = (int) old('print_logo', (int) $agency->print_logo);
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-1"></i> Edit Agency Profile</h5>
    <div>
        <a href="{{ route('super-admin.agencies.show', $agency) }}" class="btn btn-sm btn-outline-info me-1">
            <i class="bi bi-eye"></i> View
        </a>
        <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="{{ route('super-admin.agencies.update', $agency) }}" enctype="multipart/form-data" id="agencyEditForm">
@csrf @method('PUT')
<div class="row g-3">
    <div class="col-lg-8">

        {{-- A. Basic Information --}}
        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-info-circle me-1"></i> Basic Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Owner / Contact Name</label>
                        <input type="text" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror"
                            value="{{ old('owner_name', $agency->owner_name) }}" placeholder="Contact person / owner">
                        @error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $agency->status) === 'active')>Active</option>
                            <option value="suspended" @selected(old('status', $agency->status) === 'suspended')>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Company / Agency Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $agency->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">RL No</label>
                        <input type="text" name="rl_number" class="form-control @error('rl_number') is-invalid @enderror"
                            value="{{ old('rl_number', $agency->rl_number) }}">
                        @error('rl_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- B. Contact Information --}}
        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-telephone me-1"></i> Contact Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Official Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $agency->email) }}" placeholder="agency@example.com">
                        <div class="form-text">Shown on documents &amp; notifications.</div>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $agency->phone) }}">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                            placeholder="Full agency address">{{ old('address', $agency->address) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- C. Print Settings --}}
        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-printer me-1"></i> Print Settings</div>
            <div class="card-body">
                <div class="row g-3 align-items-start">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold d-block">Print Logo <span class="text-danger">*</span></label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="print_logo" id="seLogoYes"
                                    value="1" {{ $printLogo === 1 ? 'checked' : '' }}>
                                <label class="form-check-label" for="seLogoYes">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="print_logo" id="seLogoNo"
                                    value="0" {{ $printLogo === 0 ? 'checked' : '' }}>
                                <label class="form-check-label" for="seLogoNo">No</label>
                            </div>
                        </div>
                        <div class="form-text">Show the agency logo on printed / PDF documents.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Logo</label>
                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                        @if($agency->logo)
                            <small class="text-muted">Current: {{ basename($agency->logo) }}</small>
                        @else
                            <small class="text-muted">No logo uploaded.</small>
                        @endif
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- D. Account Information --}}
        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-person-lock me-1"></i> Account Information (Agency Login)</div>
            <div class="card-body">
                @if($adminUser)
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Login Email</label>
                        <input type="email" name="login_email" class="form-control @error('login_email') is-invalid @enderror"
                            value="{{ old('login_email', $adminUser->email) }}">
                        <div class="form-text">Used by the agency admin to sign in.</div>
                        @error('login_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" autocomplete="new-password"
                            class="form-control @error('new_password') is-invalid @enderror" placeholder="Leave blank to keep">
                        @error('new_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" name="new_password_confirmation" autocomplete="new-password"
                            class="form-control" placeholder="Repeat password">
                    </div>
                </div>
                <div class="form-text mt-2">
                    <i class="bi bi-shield-lock me-1"></i> As Super Admin you can reset the agency login directly — the agency's current password is not required.
                </div>
                @else
                <p class="text-muted small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i> No login account is linked to this agency yet.
                </p>
                @endif
            </div>
        </div>

        {{-- E. License Information --}}
        <div class="card mb-3">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <span><i class="bi bi-patch-check me-1"></i> License Information</span>
                <span class="badge bg-light text-secondary border">License / Subscription</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">License No</label>
                        <input type="text" name="license_number" class="form-control @error('license_number') is-invalid @enderror"
                            value="{{ old('license_number', $agency->license_number) }}">
                        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">License Expiry</label>
                        <input type="date" name="license_expiry_date" class="form-control"
                            value="{{ old('license_expiry_date', $agency->license_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Subscription Status</label>
                        <input type="text" class="form-control" style="background:#eef1f5;" disabled readonly
                            value="{{ $sub ? ($sub->plan->name ?? '—') . ' · ' . ucfirst($sub->status) : 'No active subscription' }}">
                        <div class="form-text">Managed from the Subscriptions module.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action sidebar --}}
    <div class="col-lg-4">
        <div class="card mb-3" style="position:sticky;top:1rem;">
            <div class="card-header py-2"><i class="bi bi-save me-1"></i> Save Changes</div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted small">Current status</span>
                    <span class="badge badge-status-{{ $agency->status }}">{{ ucfirst($agency->status) }}</span>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg me-1"></i> Update Profile
                </button>
                <button type="reset" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
                <a href="{{ route('super-admin.agencies.index') }}" class="btn btn-link w-100 mt-1 text-decoration-none">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection
