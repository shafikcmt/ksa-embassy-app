<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Plan Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $plan->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Price</label>
        <input type="number" name="price" class="form-control" step="0.01" min="0"
            value="{{ old('price', $plan->price ?? 0) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Currency</label>
        @php $planCurrency = old('currency', $plan->currency ?? 'BDT'); @endphp
        <select name="currency" class="form-select">
            <option value="BDT" @selected($planCurrency === 'BDT')>BDT (৳)</option>
            <option value="USD" @selected($planCurrency === 'USD')>USD ($)</option>
            <option value="SAR" @selected($planCurrency === 'SAR')>SAR</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold">Duration (Days)</label>
        <input type="number" name="duration_days" class="form-control" min="1"
            value="{{ old('duration_days', $plan->duration_days ?? 30) }}">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Description</label>
        <input type="text" name="description" class="form-control"
            value="{{ old('description', $plan->description ?? '') }}">
    </div>

    <div class="col-12"><hr class="my-1"><small class="text-muted text-uppercase fw-bold">Limits</small></div>

    <div class="col-md-4">
        <label class="form-label">Max HR / Candidates</label>
        <input type="number" name="max_hr" class="form-control" min="1"
            value="{{ old('max_hr', $plan->max_hr ?? 50) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Max Users</label>
        <input type="number" name="max_users" class="form-control" min="1"
            value="{{ old('max_users', $plan->max_users ?? 3) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Max Agents</label>
        <input type="number" name="max_agents" class="form-control" min="0"
            value="{{ old('max_agents', $plan->max_agents ?? 10) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Embassy Lists / Month</label>
        <input type="number" name="max_embassy_lists_monthly" class="form-control" min="0"
            value="{{ old('max_embassy_lists_monthly', $plan->max_embassy_lists_monthly ?? 10) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">PDF Prints / Month</label>
        <input type="number" name="max_pdf_monthly" class="form-control" min="0"
            value="{{ old('max_pdf_monthly', $plan->max_pdf_monthly ?? 100) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Storage Limit (MB)</label>
        <input type="number" name="storage_limit_mb" class="form-control" min="1"
            value="{{ old('storage_limit_mb', $plan->storage_limit_mb ?? 512) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Active?</label>
        <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                @checked(old('is_active', $plan->is_active ?? true))>
            <label class="form-check-label">Visible to agencies</label>
        </div>
    </div>
</div>
