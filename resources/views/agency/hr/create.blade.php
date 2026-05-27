@extends('layouts.agency')
@section('title', 'Add HR Profile')
@section('page-title', 'Add HR Profile')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('hr.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Add HR / Candidate Profile</h5>
</div>

<form method="POST" action="{{ route('hr.store') }}" id="hrForm">
    @csrf

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-0" id="hrTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personal" type="button">
                <i class="bi bi-person me-1"></i> Personal
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-passport" type="button">
                <i class="bi bi-passport me-1"></i> Passport
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-visa" type="button">
                <i class="bi bi-globe me-1"></i> Visa
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-clearance" type="button">
                <i class="bi bi-shield-check me-1"></i> Clearance
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-other" type="button">
                <i class="bi bi-file-text me-1"></i> Contract & Other
            </button>
        </li>
    </ul>

    <div class="card border-top-0 rounded-top-0">
        <div class="card-body">
            <div class="tab-content" id="hrTabContent">

                {{-- Tab 1: Personal --}}
                <div class="tab-pane fade show active" id="tab-personal">
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Full Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="full_name_en" class="form-control @error('full_name_en') is-invalid @enderror"
                                value="{{ old('full_name_en') }}" required>
                            @error('full_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name (Arabic)</label>
                            <input type="text" name="full_name_ar" class="form-control @error('full_name_ar') is-invalid @enderror"
                                value="{{ old('full_name_ar') }}" dir="rtl" placeholder="الاسم الكامل">
                            @error('full_name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nationality <span class="text-danger">*</span></label>
                            <input type="text" name="nationality" class="form-control @error('nationality') is-invalid @enderror"
                                value="{{ old('nationality') }}" required>
                            @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                                value="{{ old('date_of_birth') }}" required>
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                <option value="">Select...</option>
                                <option value="male" {{ old('gender')=='male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender')=='female' ? 'selected' : '' }}>Female</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Religion</label>
                            <input type="text" name="religion" class="form-control @error('religion') is-invalid @enderror"
                                value="{{ old('religion') }}">
                            @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                                <option value="">Select...</option>
                                <option value="single" {{ old('marital_status')=='single' ? 'selected' : '' }}>Single</option>
                                <option value="married" {{ old('marital_status')=='married' ? 'selected' : '' }}>Married</option>
                                <option value="divorced" {{ old('marital_status')=='divorced' ? 'selected' : '' }}>Divorced</option>
                                <option value="widowed" {{ old('marital_status')=='widowed' ? 'selected' : '' }}>Widowed</option>
                            </select>
                            @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" class="form-control @error('occupation') is-invalid @enderror"
                                value="{{ old('occupation') }}">
                            @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">File Number</label>
                            <input type="text" name="file_number" class="form-control @error('file_number') is-invalid @enderror"
                                value="{{ old('file_number') }}" placeholder="e.g. HR-2024-001">
                            @error('file_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Agent</label>
                            <select name="agent_id" class="form-select @error('agent_id') is-invalid @enderror">
                                <option value="">No Agent</option>
                                @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>
                                    {{ $agent->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status','active')=='active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status')=='inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="blacklisted" {{ old('status')=='blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Passport --}}
                <div class="tab-pane fade" id="tab-passport">
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Passport Number</label>
                            <input type="text" name="passport_number" class="form-control @error('passport_number') is-invalid @enderror"
                                value="{{ old('passport_number') }}">
                            @error('passport_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passport Type</label>
                            <select name="passport_type" class="form-select @error('passport_type') is-invalid @enderror">
                                <option value="regular" {{ old('passport_type','regular')=='regular' ? 'selected' : '' }}>Regular</option>
                                <option value="diplomatic" {{ old('passport_type')=='diplomatic' ? 'selected' : '' }}>Diplomatic</option>
                                <option value="service" {{ old('passport_type')=='service' ? 'selected' : '' }}>Service</option>
                            </select>
                            @error('passport_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Place</label>
                            <input type="text" name="passport_issue_place" class="form-control @error('passport_issue_place') is-invalid @enderror"
                                value="{{ old('passport_issue_place') }}">
                            @error('passport_issue_place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="passport_issue_date" class="form-control @error('passport_issue_date') is-invalid @enderror"
                                value="{{ old('passport_issue_date') }}">
                            @error('passport_issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="passport_expiry_date" class="form-control @error('passport_expiry_date') is-invalid @enderror"
                                value="{{ old('passport_expiry_date') }}">
                            @error('passport_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Tab 3: Visa --}}
                <div class="tab-pane fade" id="tab-visa">
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Visa Number</label>
                            <input type="text" name="visa_number" class="form-control @error('visa_number') is-invalid @enderror"
                                value="{{ old('visa_number') }}">
                            @error('visa_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Visa Type</label>
                            <input type="text" name="visa_type" class="form-control @error('visa_type') is-invalid @enderror"
                                value="{{ old('visa_type') }}" placeholder="e.g. Work, Family, Tourist">
                            @error('visa_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Place</label>
                            <input type="text" name="visa_issue_place" class="form-control @error('visa_issue_place') is-invalid @enderror"
                                value="{{ old('visa_issue_place') }}">
                            @error('visa_issue_place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="visa_issue_date" class="form-control @error('visa_issue_date') is-invalid @enderror"
                                value="{{ old('visa_issue_date') }}">
                            @error('visa_issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="visa_expiry_date" class="form-control @error('visa_expiry_date') is-invalid @enderror"
                                value="{{ old('visa_expiry_date') }}">
                            @error('visa_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sponsor Name</label>
                            <input type="text" name="sponsor_name" class="form-control @error('sponsor_name') is-invalid @enderror"
                                value="{{ old('sponsor_name') }}">
                            @error('sponsor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sponsor ID</label>
                            <input type="text" name="sponsor_id" class="form-control @error('sponsor_id') is-invalid @enderror"
                                value="{{ old('sponsor_id') }}">
                            @error('sponsor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Border Number</label>
                            <input type="text" name="border_number" class="form-control @error('border_number') is-invalid @enderror"
                                value="{{ old('border_number') }}">
                            @error('border_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Tab 4: Clearance --}}
                <div class="tab-pane fade" id="tab-clearance">
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Police Clearance Number</label>
                            <input type="text" name="police_clearance_number" class="form-control @error('police_clearance_number') is-invalid @enderror"
                                value="{{ old('police_clearance_number') }}">
                            @error('police_clearance_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clearance Issue Date</label>
                            <input type="date" name="clearance_issue_date" class="form-control @error('clearance_issue_date') is-invalid @enderror"
                                value="{{ old('clearance_issue_date') }}">
                            @error('clearance_issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clearance Expiry Date</label>
                            <input type="date" name="clearance_expiry_date" class="form-control @error('clearance_expiry_date') is-invalid @enderror"
                                value="{{ old('clearance_expiry_date') }}">
                            @error('clearance_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clearance Country</label>
                            <input type="text" name="clearance_country" class="form-control @error('clearance_country') is-invalid @enderror"
                                value="{{ old('clearance_country') }}">
                            @error('clearance_country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="medical_fit" value="1" id="medicalFit"
                                    {{ old('medical_fit') ? 'checked' : '' }}>
                                <label class="form-check-label" for="medicalFit">
                                    <strong>Medically Fit</strong>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Medical Date</label>
                            <input type="date" name="medical_date" class="form-control @error('medical_date') is-invalid @enderror"
                                value="{{ old('medical_date') }}">
                            @error('medical_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Medical Center</label>
                            <input type="text" name="medical_center" class="form-control @error('medical_center') is-invalid @enderror"
                                value="{{ old('medical_center') }}">
                            @error('medical_center')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Tab 5: Contract & Other --}}
                <div class="tab-pane fade" id="tab-other">
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Contract Period</label>
                            <input type="text" name="contract_period" class="form-control @error('contract_period') is-invalid @enderror"
                                value="{{ old('contract_period') }}" placeholder="e.g. 2 years">
                            @error('contract_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Salary (SAR)</label>
                            <input type="number" step="0.01" name="salary" class="form-control @error('salary') is-invalid @enderror"
                                value="{{ old('salary') }}" min="0">
                            @error('salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Work City</label>
                            <input type="text" name="work_city" class="form-control @error('work_city') is-invalid @enderror"
                                value="{{ old('work_city') }}">
                            @error('work_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employer Name</label>
                            <input type="text" name="employer_name" class="form-control @error('employer_name') is-invalid @enderror"
                                value="{{ old('employer_name') }}">
                            @error('employer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employer Phone</label>
                            <input type="text" name="employer_phone" class="form-control @error('employer_phone') is-invalid @enderror"
                                value="{{ old('employer_phone') }}">
                            @error('employer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Arrival Date</label>
                            <input type="date" name="arrival_date" class="form-control @error('arrival_date') is-invalid @enderror"
                                value="{{ old('arrival_date') }}">
                            @error('arrival_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Departure Date</label>
                            <input type="date" name="departure_date" class="form-control @error('departure_date') is-invalid @enderror"
                                value="{{ old('departure_date') }}">
                            @error('departure_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="3">{{ old('remarks') }}</textarea>
                            @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('hr.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Save HR Profile
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// Restore active tab after validation error
const activeTab = sessionStorage.getItem('hrActiveTab');
if (activeTab) {
    const tab = document.querySelector('[data-bs-target="' + activeTab + '"]');
    if (tab) new bootstrap.Tab(tab).show();
    sessionStorage.removeItem('hrActiveTab');
}
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tab) {
    tab.addEventListener('click', function() {
        sessionStorage.setItem('hrActiveTab', this.getAttribute('data-bs-target'));
    });
});
</script>
@endpush
