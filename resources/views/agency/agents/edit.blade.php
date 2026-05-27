@extends('layouts.agency')
@section('title', 'Edit Agent')
@section('page-title', 'Edit Agent')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-pencil me-1 text-warning"></i> Edit Agent</h5>
        <small class="text-muted">{{ $agent->name }}</small>
    </div>
    <div class="d-flex gap-1">
        <a href="{{ route('agents.show', $agent) }}" class="btn btn-sm btn-outline-info">
            <i class="bi bi-eye me-1"></i> View
        </a>
        <a href="{{ route('agents.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<form method="POST" action="{{ route('agents.update', $agent) }}" novalidate>
@csrf @method('PUT')
<div class="row g-3">

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-person me-1"></i> Agent Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $agent->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $agent->phone) }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $agent->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="active"   @selected(old('status', $agent->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $agent->status) === 'inactive')>Inactive</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                        <textarea name="address" rows="2"
                            class="form-control @error('address') is-invalid @enderror"
                            required>{{ old('address', $agent->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" rows="2"
                            class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $agent->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-2">Actions</div>
            <div class="card-body d-flex flex-column gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
                <a href="{{ route('agents.show', $agent) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x me-1"></i> Cancel
                </a>
            </div>
        </div>

        <div class="card border-0 bg-light">
            <div class="card-body py-2 small text-muted">
                <div><strong>Created:</strong> {{ $agent->created_at->format('d M Y, H:i') }}</div>
                @if($agent->createdBy)
                    <div><strong>By:</strong> {{ $agent->createdBy->name }}</div>
                @endif
                @if($agent->updated_at != $agent->created_at)
                    <div class="mt-1"><strong>Last updated:</strong> {{ $agent->updated_at->format('d M Y, H:i') }}</div>
                    @if($agent->updatedBy)
                        <div><strong>By:</strong> {{ $agent->updatedBy->name }}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>

</div>
</form>
@endsection
