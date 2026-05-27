@extends('layouts.agency')
@section('title', 'Add New Agent')
@section('page-title', 'Add New Agent')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-person-plus me-1 text-primary"></i> Add New Agent</h5>
        <small class="text-muted">Fill in the agent's details below</small>
    </div>
    <a href="{{ route('agents.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Agents
    </a>
</div>

<form method="POST" action="{{ route('agents.store') }}" id="agentForm" novalidate>
@csrf
<div class="row g-3">

    {{-- Main form --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-person me-1"></i> Agent Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}"
                            placeholder="e.g. Mohammed Al-Rashid"
                            required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Phone <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}"
                                placeholder="+966501234567"
                                required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                placeholder="agent@example.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">Must be unique within your agency.</small>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active"   @selected(old('status','active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">
                            Address <span class="text-danger">*</span>
                        </label>
                        <textarea name="address" rows="2"
                            class="form-control @error('address') is-invalid @enderror"
                            placeholder="Full address including city and district"
                            required>{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold">Notes <small class="text-muted fw-normal">(optional)</small></label>
                        <textarea name="notes" rows="2"
                            class="form-control @error('notes') is-invalid @enderror"
                            placeholder="Any additional notes about this agent...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions sidebar --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-2">Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Agent
                </button>
                <button type="reset" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                </button>
                <a href="{{ route('agents.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i> Cancel
                </a>
            </div>
        </div>

        <div class="card border-start border-4 border-info">
            <div class="card-body py-2 small text-muted">
                <i class="bi bi-info-circle me-1 text-info"></i>
                <strong>Required fields</strong> are marked with <span class="text-danger">*</span>.
                Email is optional but must be unique per agency if provided.
            </div>
        </div>
    </div>

</div>
</form>
@endsection
