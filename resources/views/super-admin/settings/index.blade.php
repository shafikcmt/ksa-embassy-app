@extends('layouts.super-admin')
@section('title', 'Global Settings')
@section('page-title', 'Global Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-gear me-1"></i> Global System Settings
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('super-admin.settings.update') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">System Name <span class="text-danger">*</span></label>
                        <input type="text" name="system_name" class="form-control form-control-sm @error('system_name') is-invalid @enderror"
                            value="{{ old('system_name', $systemName) }}">
                        <div class="form-text">Displayed in page titles and email notifications.</div>
                        @error('system_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Support Email</label>
                        <input type="email" name="support_email" class="form-control form-control-sm @error('support_email') is-invalid @enderror"
                            value="{{ old('support_email', $supportEmail) }}"
                            placeholder="support@example.com">
                        <div class="form-text">Shown to agencies on expired/suspended pages.</div>
                        @error('support_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Default Plan for New Agencies</label>
                        <select name="default_plan_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ (string)$defaultPlanId === (string)$plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — SAR {{ number_format($plan->price, 0) }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Pre-selected when creating a new subscription for an agency.</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                id="maintenanceMode" value="1"
                                {{ $maintenanceMode === '1' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="maintenanceMode">
                                <span class="fw-semibold text-danger">Maintenance Mode</span><br>
                                <span class="text-muted">When enabled, agency users see a maintenance notice. Super admin access is unaffected.</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-floppy me-1"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        {{-- Global default HR form field controls --}}
        <div class="card mt-4">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <span><i class="bi bi-ui-checks me-1"></i> HR Form Field Defaults</span>
                <span class="badge bg-light text-secondary border">Active / Inactive</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    These are the <strong>global defaults</strong> for the Add / Edit HR form. Each agency can override
                    them from their own Settings. Required fields are always shown and can't be turned off.
                </p>

                <form method="POST" action="{{ route('super-admin.settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="section" value="hr_fields">

                    @foreach($hrFieldGroups as $section => $fields)
                        <div class="mb-3">
                            <div class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.04em;">{{ $section }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted" style="font-size:.72rem;">
                                            <th style="width:55%;">Field Name</th>
                                            <th style="width:25%;">Type</th>
                                            <th style="width:20%;" class="text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($fields as $field)
                                            <tr>
                                                <td class="fw-semibold text-dark" style="font-size:.85rem;">{{ $field['label'] }}</td>
                                                <td>
                                                    @if($field['required'])
                                                        <span class="badge bg-secondary-subtle text-secondary border">Required</span>
                                                    @else
                                                        <span class="text-muted small">Optional</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($field['required'])
                                                        <span class="badge bg-success-subtle text-success border"><i class="bi bi-lock-fill me-1"></i>Always on</span>
                                                    @else
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="fields[]" value="{{ $field['key'] }}"
                                                                   id="gf_{{ $field['key'] }}"
                                                                   {{ ($hrFieldStatuses[$field['key']] ?? true) ? 'checked' : '' }}>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary btn-sm mt-2">
                        <i class="bi bi-floppy me-1"></i> Save Field Defaults
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
