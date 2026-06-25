@extends('layouts.agency')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">

        <ul class="nav nav-tabs mb-4" id="settingsTabs">
            <li class="nav-item">
                <a class="nav-link {{ !request()->has('tab') || request('tab') === 'profile' ? 'active' : '' }}"
                   href="{{ route('settings.index') }}?tab=profile">
                    <i class="bi bi-building me-1"></i> Agency Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('tab') === 'print' ? 'active' : '' }}"
                   href="{{ route('settings.index') }}?tab=print">
                    <i class="bi bi-printer me-1"></i> Print Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('tab') === 'notifications' ? 'active' : '' }}"
                   href="{{ route('settings.index') }}?tab=notifications">
                    <i class="bi bi-bell me-1"></i> Notifications
                </a>
            </li>
            @if($canManageFields ?? false)
            <li class="nav-item">
                <a class="nav-link {{ request('tab') === 'hr_fields' ? 'active' : '' }}"
                   href="{{ route('settings.index') }}?tab=hr_fields">
                    <i class="bi bi-ui-checks me-1"></i> HR Form Fields
                </a>
            </li>
            @endif
        </ul>

        {{-- Profile Tab --}}
        @if(!request()->has('tab') || request('tab') === 'profile')
        <div id="profile">
            <div class="card">
                <div class="card-header py-2">
                    <i class="bi bi-building me-1"></i> Agency Profile
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="tab" value="profile">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Agency Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                value="{{ old('name', $agency->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                                    value="{{ old('email', $agency->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Phone</label>
                                <input type="text" name="phone" class="form-control form-control-sm"
                                    value="{{ old('phone', $agency->phone) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Address</label>
                            <textarea name="address" class="form-control form-control-sm" rows="3">{{ old('address', $agency->address) }}</textarea>
                        </div>

                        <div class="mb-3 p-3" style="background:#f8fafc;border-radius:6px;">
                            <div class="text-muted small fw-semibold mb-2">Read-only Information</div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;">License No.</div>
                                    <div style="font-size:.82rem;">{{ $agency->license_number ?? '—' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;">RL Number</div>
                                    <div style="font-size:.82rem;">{{ $agency->rl_number ?? '—' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;">License Expiry</div>
                                    <div style="font-size:.82rem;">{{ $agency->license_expiry_date?->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-floppy me-1"></i> Save Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Print Settings Tab --}}
        @if(request('tab') === 'print')
        <div id="print">
            <div class="card">
                <div class="card-header py-2">
                    <i class="bi bi-printer me-1"></i> Print Settings
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        These texts appear in the header and footer of all printed / exported PDF documents.
                    </p>
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="tab" value="print">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Document Header Text</label>
                            <textarea name="print_header" class="form-control form-control-sm" rows="3"
                                placeholder="e.g. Kingdom of Saudi Arabia — Ministry of Human Resources...">{{ old('print_header', $printHeader) }}</textarea>
                            <div class="form-text">Displayed at the top of printed documents.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Document Footer Text</label>
                            <textarea name="print_footer" class="form-control form-control-sm" rows="3"
                                placeholder="e.g. This document is issued by Al-Noor Recruitment Agency...">{{ old('print_footer', $printFooter) }}</textarea>
                            <div class="form-text">Displayed at the bottom of printed documents.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-floppy me-1"></i> Save Print Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Notifications Tab --}}
        @if(request('tab') === 'notifications')
        <div id="notifications">
            <div class="card">
                <div class="card-header py-2">
                    <i class="bi bi-bell me-1"></i> Notification Settings
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Control which email notifications you receive from the system.
                        Emails are sent to: <strong>{{ $agency->email ?? 'not set' }}</strong>
                    </p>
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="tab" value="notifications">

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notify_subscription_expiry"
                                    id="notifySub" value="1"
                                    {{ $notifySubscription === '1' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="notifySub">
                                    <span class="fw-semibold">Subscription Expiry Reminders</span><br>
                                    <span class="text-muted">Receive email when subscription expires or is about to expire.</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notify_passport_expiry"
                                    id="notifyPassport" value="1"
                                    {{ $notifyPassport === '1' ? 'checked' : '' }}>
                                <label class="form-check-label small" for="notifyPassport">
                                    <span class="fw-semibold">Passport Expiry Alerts</span><br>
                                    <span class="text-muted">Receive email when HR candidate passports are expiring within 30 days.</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-floppy me-1"></i> Save Notification Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- HR Form Fields Tab --}}
        @if(request('tab') === 'hr_fields' && ($canManageFields ?? false))
        <div id="hr-fields">
            <div class="card">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-ui-checks me-1"></i> HR Form Field Controls</span>
                    <span class="badge bg-light text-secondary border">Active / Inactive</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Turn fields <strong>On</strong> to show them on the Add / Edit HR form, or <strong>Off</strong> to hide them.
                        Required fields are always shown and can't be turned off. Changes apply to your agency's HR form only.
                    </p>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="tab" value="hr_fields">

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
                                                                       id="hrf_{{ $field['key'] }}"
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
                            <i class="bi bi-floppy me-1"></i> Save Field Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
