@extends('layouts.agency')
@section('title', 'Edit HR Profile')
@section('page-title', 'Edit HR Profile')

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Edit: {{ $hr->full_name_en }}</h5>
</div>

<form method="POST" action="{{ route('hr.update', $hr) }}" id="hrForm">
    @csrf @method('PUT')

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
                                value="{{ old('full_name_en', $hr->full_name_en) }}" required>
                            @error('full_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name (Arabic)</label>
                            <input type="text" name="full_name_ar" class="form-control @error('full_name_ar') is-invalid @enderror"
                                value="{{ old('full_name_ar', $hr->full_name_ar) }}" dir="rtl" placeholder="الاسم الكامل">
                            @error('full_name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nationality <span class="text-danger">*</span></label>
                            <input type="text" name="nationality" class="form-control @error('nationality') is-invalid @enderror"
                                value="{{ old('nationality', $hr->nationality) }}" required>
                            @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                                value="{{ old('date_of_birth', $hr->date_of_birth?->format('Y-m-d')) }}" required>
                            @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                <option value="male" {{ old('gender', $hr->gender)=='male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $hr->gender)=='female' ? 'selected' : '' }}>Female</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Religion</label>
                            <input type="text" name="religion" class="form-control @error('religion') is-invalid @enderror"
                                value="{{ old('religion', $hr->religion) }}">
                            @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Marital Status</label>
                            <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                                <option value="">Select...</option>
                                @foreach(['single','married','divorced','widowed'] as $ms)
                                <option value="{{ $ms }}" {{ old('marital_status', $hr->marital_status)==$ms ? 'selected' : '' }}>
                                    {{ ucfirst($ms) }}
                                </option>
                                @endforeach
                            </select>
                            @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" class="form-control @error('occupation') is-invalid @enderror"
                                value="{{ old('occupation', $hr->occupation) }}">
                            @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $hr->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $hr->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">File Number</label>
                            <input type="text" name="file_number" class="form-control @error('file_number') is-invalid @enderror"
                                value="{{ old('file_number', $hr->file_number) }}">
                            @error('file_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Agent</label>
                            <select name="agent_id" class="form-select @error('agent_id') is-invalid @enderror">
                                <option value="">No Agent</option>
                                @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ old('agent_id', $hr->agent_id) == $agent->id ? 'selected' : '' }}>
                                    {{ $agent->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('agent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach(['active','inactive','blacklisted'] as $st)
                                <option value="{{ $st }}" {{ old('status', $hr->status)==$st ? 'selected' : '' }}>
                                    {{ ucfirst($st) }}
                                </option>
                                @endforeach
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $hr->notes) }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Tab 2: Passport --}}
                <div class="tab-pane fade" id="tab-passport">
                    @php $passport = $hr->passport; @endphp
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Passport Number</label>
                            <input type="text" name="passport_number" class="form-control"
                                value="{{ old('passport_number', $passport?->passport_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passport Type</label>
                            <select name="passport_type" class="form-select">
                                @foreach(['regular','diplomatic','service'] as $pt)
                                <option value="{{ $pt }}" {{ old('passport_type', $passport?->passport_type ?? 'regular')==$pt ? 'selected' : '' }}>
                                    {{ ucfirst($pt) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Place</label>
                            <input type="text" name="passport_issue_place" class="form-control"
                                value="{{ old('passport_issue_place', $passport?->issue_place) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="passport_issue_date" class="form-control"
                                value="{{ old('passport_issue_date', $passport?->issue_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="passport_expiry_date" class="form-control"
                                value="{{ old('passport_expiry_date', $passport?->expiry_date?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                {{-- Tab 3: Visa --}}
                <div class="tab-pane fade" id="tab-visa">
                    @php $visa = $hr->visa; @endphp
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Visa Number</label>
                            <input type="text" name="visa_number" class="form-control"
                                value="{{ old('visa_number', $visa?->visa_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Visa Type</label>
                            <input type="text" name="visa_type" class="form-control"
                                value="{{ old('visa_type', $visa?->visa_type) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Place</label>
                            <input type="text" name="visa_issue_place" class="form-control"
                                value="{{ old('visa_issue_place', $visa?->issue_place) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Issue Date</label>
                            <input type="date" name="visa_issue_date" class="form-control"
                                value="{{ old('visa_issue_date', $visa?->issue_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="visa_expiry_date" class="form-control"
                                value="{{ old('visa_expiry_date', $visa?->expiry_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sponsor Name</label>
                            <input type="text" name="sponsor_name" class="form-control"
                                value="{{ old('sponsor_name', $visa?->sponsor_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sponsor ID</label>
                            <input type="text" name="sponsor_id" class="form-control"
                                value="{{ old('sponsor_id', $visa?->sponsor_id) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Border Number</label>
                            <input type="text" name="border_number" class="form-control"
                                value="{{ old('border_number', $visa?->border_number) }}">
                        </div>
                    </div>
                </div>

                {{-- Tab 4: Clearance --}}
                <div class="tab-pane fade" id="tab-clearance">
                    @php $clearance = $hr->clearance; @endphp
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Police Clearance Number</label>
                            <input type="text" name="police_clearance_number" class="form-control"
                                value="{{ old('police_clearance_number', $clearance?->police_clearance_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clearance Issue Date</label>
                            <input type="date" name="clearance_issue_date" class="form-control"
                                value="{{ old('clearance_issue_date', $clearance?->clearance_issue_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clearance Expiry Date</label>
                            <input type="date" name="clearance_expiry_date" class="form-control"
                                value="{{ old('clearance_expiry_date', $clearance?->clearance_expiry_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clearance Country</label>
                            <input type="text" name="clearance_country" class="form-control"
                                value="{{ old('clearance_country', $clearance?->clearance_country) }}">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="medical_fit" value="1" id="medicalFit"
                                    {{ old('medical_fit', $clearance?->medical_fit) ? 'checked' : '' }}>
                                <label class="form-check-label" for="medicalFit">
                                    <strong>Medically Fit</strong>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Medical Date</label>
                            <input type="date" name="medical_date" class="form-control"
                                value="{{ old('medical_date', $clearance?->medical_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Medical Center</label>
                            <input type="text" name="medical_center" class="form-control"
                                value="{{ old('medical_center', $clearance?->medical_center) }}">
                        </div>
                    </div>
                </div>

                {{-- Tab 5: Contract & Other --}}
                <div class="tab-pane fade" id="tab-other">
                    @php $other = $hr->otherInfo; @endphp
                    <div class="row g-3 mt-1">
                        <div class="col-md-4">
                            <label class="form-label">Contract Period</label>
                            <input type="text" name="contract_period" class="form-control"
                                value="{{ old('contract_period', $other?->contract_period) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Salary (SAR)</label>
                            <input type="number" step="0.01" name="salary" class="form-control"
                                value="{{ old('salary', $other?->salary) }}" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Work City</label>
                            <input type="text" name="work_city" class="form-control"
                                value="{{ old('work_city', $other?->work_city) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employer Name</label>
                            <input type="text" name="employer_name" class="form-control"
                                value="{{ old('employer_name', $other?->employer_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employer Phone</label>
                            <input type="text" name="employer_phone" class="form-control"
                                value="{{ old('employer_phone', $other?->employer_phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Arrival Date</label>
                            <input type="date" name="arrival_date" class="form-control"
                                value="{{ old('arrival_date', $other?->arrival_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Departure Date</label>
                            <input type="date" name="departure_date" class="form-control"
                                value="{{ old('departure_date', $other?->departure_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $other?->remarks) }}</textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('hr.show', $hr) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Update HR Profile
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
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
