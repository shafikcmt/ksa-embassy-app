{{--
    Shared HR / Candidate form — used by both create and edit.
    Expects: $agents (Collection), and optionally $hr (HrProfile|null for create).
    Layout mirrors the embassy form reference: 5 stacked sections + one optional section.
--}}
@php
    $hr        = $hr ?? null;
    $passport  = $hr?->passport;
    $visa      = $hr?->visa;
    $clearance = $hr?->clearance;
    $other     = $hr?->otherInfo;

    // value helper with sensible default (existing value wins on edit, default fills blanks)
    $v   = fn($field, $default = null) => old($field, $hr?->{$field} ?? $default);
    $rel = fn($model, $field, $default = null) => old($field, $model?->{$field} ?? $default);
    $dt  = fn($model, $field) => old($field, optional($model?->{$field})->format('Y-m-d'));
@endphp

<style>
    .hr-section { margin-bottom: 1rem; }
    .hr-section > .card-header { background:#fff; border-bottom:1px solid #e2e8f0; font-weight:700;
        color:#1e3a5f; font-size:.9rem; display:flex; align-items:center; gap:.5rem; }
    .hr-section .req { color:#dc2626; }
    .hr-section .form-label { font-size:.8rem; font-weight:600; color:#334155; margin-bottom:.25rem; }
    .hr-section .form-control, .hr-section .form-select { font-size:.85rem; }
    .hr-section .field-row { margin-bottom:.85rem; }
    .hr-radio-group { display:flex; gap:1.25rem; align-items:center; min-height:38px; }
    @media (max-width: 575px){ .hr-radio-group{ gap:.85rem; } }
</style>

@if($errors->any())
<div class="alert alert-danger py-2" style="font-size:.82rem;border-radius:10px;">
    <i class="bi bi-exclamation-circle-fill me-1"></i>Please fix the highlighted fields below.
</div>
@endif

{{-- ════════════ SECTION 1: PERSONAL INFO ════════════ --}}
<div class="card hr-section">
    <div class="card-header"><i class="bi bi-person-vcard"></i> Personal Info</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 field-row">
                <label class="form-label">Name <span class="req">*</span></label>
                <input type="text" name="full_name_en" required
                    class="form-control @error('full_name_en') is-invalid @enderror" value="{{ $v('full_name_en') }}">
                @error('full_name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Father</label>
                <input type="text" name="father_name" class="form-control" value="{{ $v('father_name') }}">
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Mother</label>
                <input type="text" name="mother_name" class="form-control" value="{{ $v('mother_name') }}">
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Date of Birth <span class="req">*</span></label>
                <input type="date" name="date_of_birth" required
                    class="form-control @error('date_of_birth') is-invalid @enderror"
                    value="{{ old('date_of_birth', optional($hr?->date_of_birth)->format('Y-m-d')) }}">
                @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">MOFA Application ID <span class="req">*</span></label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="text" name="mofa_new" placeholder="New Mofa"
                            class="form-control @error('mofa_new') is-invalid @enderror" value="{{ $v('mofa_new') }}">
                        @error('mofa_new')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <input type="text" name="mofa_old" placeholder="Old Mofa" class="form-control" value="{{ $v('mofa_old') }}">
                    </div>
                </div>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Place of Birth <span class="req">*</span></label>
                <input type="text" name="place_of_birth" required
                    class="form-control @error('place_of_birth') is-invalid @enderror" value="{{ $v('place_of_birth') }}">
                @error('place_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Previous Nationality</label>
                <input type="text" name="previous_nationality" class="form-control" value="{{ $v('previous_nationality', 'BANGLADESH') }}">
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Present Nationality <span class="req">*</span></label>
                <input type="text" name="nationality" required
                    class="form-control @error('nationality') is-invalid @enderror" value="{{ $v('nationality', 'BANGLADESH') }}">
                @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @php $gender = $v('gender'); $marital = $v('marital_status'); @endphp
            <div class="col-md-6 field-row">
                <label class="form-label">Sex <span class="req">*</span></label>
                <div class="hr-radio-group @error('gender') is-invalid @enderror">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="gender" id="gender_m" value="male" {{ $gender==='male'?'checked':'' }} required>
                        <label class="form-check-label" for="gender_m">Male</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="gender" id="gender_f" value="female" {{ $gender==='female'?'checked':'' }}>
                        <label class="form-check-label" for="gender_f">Female</label>
                    </div>
                </div>
                @error('gender')<div class="text-danger" style="font-size:.78rem;">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Marital Status <span class="req">*</span></label>
                <div class="hr-radio-group">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="marital_status" id="ms_married" value="married" {{ $marital==='married'?'checked':'' }} required>
                        <label class="form-check-label" for="ms_married">Married</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="marital_status" id="ms_single" value="single" {{ in_array($marital,['single','divorced','widowed'],true)?'checked':'' }}>
                        <label class="form-check-label" for="ms_single">Unmarried</label>
                    </div>
                </div>
                @error('marital_status')<div class="text-danger" style="font-size:.78rem;">{{ $message }}</div>@enderror
            </div>

            @php $religion = $v('religion'); @endphp
            <div class="col-md-6 field-row">
                <label class="form-label">Sect</label>
                <input type="text" name="sect" class="form-control" value="{{ $v('sect') }}">
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Religion <span class="req">*</span></label>
                <div class="hr-radio-group">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="religion" id="rel_m" value="Muslim" {{ $religion==='Muslim'?'checked':'' }} required>
                        <label class="form-check-label" for="rel_m">Muslim</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="religion" id="rel_n" value="Non-muslim" {{ $religion==='Non-muslim'?'checked':'' }}>
                        <label class="form-check-label" for="rel_n">Non-muslim</label>
                    </div>
                </div>
                @error('religion')<div class="text-danger" style="font-size:.78rem;">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Home Address &amp; Phone</label>
                <textarea name="home_address" rows="1" class="form-control">{{ $v('home_address') }}</textarea>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Full Name (Arabic) <small class="text-muted">— used on Arabic documents</small></label>
                <input type="text" name="full_name_ar" dir="rtl" placeholder="الاسم الكامل" class="form-control" value="{{ $v('full_name_ar') }}">
            </div>
        </div>
    </div>
</div>

{{-- ════════════ SECTION 2: PASSPORT INFO ════════════ --}}
<div class="card hr-section">
    <div class="card-header"><i class="bi bi-passport"></i> Passport Info</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 field-row">
                <label class="form-label">Passport Issue Place</label>
                <input type="text" name="passport_issue_place" class="form-control" value="{{ $rel($passport, 'issue_place', 'DHAKA') }}">
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Passport No <span class="req">*</span></label>
                <input type="text" name="passport_number" required
                    class="form-control @error('passport_number') is-invalid @enderror" value="{{ $rel($passport, 'passport_number') }}">
                @error('passport_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Passport Issue Date <span class="req">*</span></label>
                <input type="date" name="passport_issue_date" id="passport_issue_date" required
                    class="form-control @error('passport_issue_date') is-invalid @enderror" value="{{ $dt($passport, 'issue_date') }}">
                @error('passport_issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 field-row">
                @php $validity = (int) $rel($passport, 'validity_years', 5); @endphp
                <label class="form-label d-block">Passport Validity Type</label>
                <div class="hr-radio-group mb-2">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="passport_validity_years" id="pv5" value="5" {{ $validity===5?'checked':'' }}>
                        <label class="form-check-label" for="pv5">5 Years</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="passport_validity_years" id="pv10" value="10" {{ $validity===10?'checked':'' }}>
                        <label class="form-check-label" for="pv10">10 Years</label>
                    </div>
                </div>
                <label class="form-label">Passport Validity Date <span class="req">*</span></label>
                <input type="date" name="passport_expiry_date" id="passport_expiry_date" required
                    class="form-control @error('passport_expiry_date') is-invalid @enderror" value="{{ $dt($passport, 'expiry_date') }}">
                @error('passport_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Auto-filled from issue date + validity; you can edit it manually.</small>
            </div>
        </div>
    </div>
</div>

{{-- ════════════ SECTION 3: VISA INFO ════════════ --}}
<div class="card hr-section">
    <div class="card-header"><i class="bi bi-globe2"></i> Visa Info</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 field-row">
                <label class="form-label">Visa No <span class="req">*</span></label>
                <input type="text" name="visa_number" required
                    class="form-control @error('visa_number') is-invalid @enderror" value="{{ $rel($visa, 'visa_number') }}">
                @error('visa_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Visa Date <span class="req">*</span></label>
                <input type="date" name="visa_issue_date" required
                    class="form-control @error('visa_issue_date') is-invalid @enderror" value="{{ $dt($visa, 'issue_date') }}">
                @error('visa_issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Sponsor Name <span class="req">*</span></label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="text" name="sponsor_name" placeholder="English" required
                            class="form-control @error('sponsor_name') is-invalid @enderror"
                            value="{{ $rel($visa, 'sponsor_name') }}" data-ar-source="sponsor_name_ar" data-ar-dict="generic">
                        @error('sponsor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <input type="text" id="sponsor_name_ar" name="sponsor_name_ar" dir="rtl" placeholder="Arabic"
                            class="form-control ar-target" value="{{ $rel($visa, 'sponsor_name_ar') }}">
                    </div>
                </div>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Sponsor ID <span class="req">*</span></label>
                <input type="text" name="sponsor_id" required
                    class="form-control @error('sponsor_id') is-invalid @enderror" value="{{ $rel($visa, 'sponsor_id') }}">
                @error('sponsor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Place of Issue</label>
                <div class="row g-2">
                    <div class="col-6"><input type="text" name="visa_issue_place" placeholder="English" class="form-control" value="{{ $rel($visa, 'issue_place') }}" data-ar-source="visa_issue_place_ar" data-ar-dict="city"></div>
                    <div class="col-6">
                        <div class="input-group input-group-sm">
                            <input type="text" id="visa_issue_place_ar" name="visa_issue_place_ar" dir="rtl" placeholder="Arabic" class="form-control ar-target" value="{{ $rel($visa, 'issue_place_ar') }}">
                            <button type="button" class="btn btn-outline-secondary ar-gen" data-target="visa_issue_place_ar" data-dict="city" tabindex="-1" title="Generate Arabic">ع</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Qualification</label>
                <div class="row g-2">
                    <div class="col-6"><input type="text" name="qualification_en" placeholder="English" class="form-control" value="{{ $rel($visa, 'qualification_en') }}" data-ar-source="qualification_ar" data-ar-dict="qualification"></div>
                    <div class="col-6">
                        <div class="input-group input-group-sm">
                            <input type="text" id="qualification_ar" name="qualification_ar" dir="rtl" placeholder="Arabic" class="form-control ar-target" value="{{ $rel($visa, 'qualification_ar') }}">
                            <button type="button" class="btn btn-outline-secondary ar-gen" data-target="qualification_ar" data-dict="qualification" tabindex="-1" title="Generate Arabic">ع</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Profession <span class="req">*</span></label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="text" name="profession_en" placeholder="English" required
                            class="form-control @error('profession_en') is-invalid @enderror" value="{{ $rel($visa, 'profession_en') }}"
                            data-ar-source="profession_ar" data-ar-dict="profession">
                        @error('profession_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <div class="input-group input-group-sm">
                            <input type="text" id="profession_ar" name="profession_ar" dir="rtl" placeholder="Arabic" class="form-control ar-target" value="{{ $rel($visa, 'profession_ar') }}">
                            <button type="button" class="btn btn-outline-secondary ar-gen" data-target="profession_ar" data-dict="profession" tabindex="-1" title="Generate Arabic">ع</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Travel Purpose</label>
                @php $tp = $rel($visa, 'travel_purpose', 'Work'); $tpOpts = ['Work','Family Visit','Business','Hajj','Umrah','Visit','Other']; @endphp
                <select name="travel_purpose" class="form-select">
                    @foreach($tpOpts as $opt)
                        <option value="{{ $opt }}" {{ $tp===$opt?'selected':'' }}>{{ $opt }}</option>
                    @endforeach
                    @if($tp && !in_array($tp,$tpOpts,true))<option value="{{ $tp }}" selected>{{ $tp }}</option>@endif
                </select>
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Musaned No</label>
                <input type="text" name="musaned_no" class="form-control" value="{{ $rel($visa, 'musaned_no') }}">
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Wakala No <span class="req">*</span></label>
                <input type="text" name="wakala_no" required
                    class="form-control @error('wakala_no') is-invalid @enderror" value="{{ $rel($visa, 'wakala_no') }}">
                @error('wakala_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- ════════════ SECTION 4: POLICE CLEARANCE & DRIVING LICENSE ════════════ --}}
<div class="card hr-section">
    <div class="card-header"><i class="bi bi-shield-check"></i> Police Clearance &amp; Driving License Info</div>
    <div class="card-body">
        <div class="row">
            <div class="col-12 field-row">
                <label class="form-label">P.C QR Code</label>
                <input type="text" name="pc_qr_code" class="form-control" value="{{ $rel($clearance, 'pc_qr_code') }}">
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">P.C Reference No. <span class="req">*</span></label>
                <input type="text" name="police_clearance_number" required
                    class="form-control @error('police_clearance_number') is-invalid @enderror" value="{{ $rel($clearance, 'police_clearance_number') }}">
                @error('police_clearance_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">License Type</label>
                @php $lt = $rel($clearance, 'license_type'); $ltOpts = ['Light Vehicle','Heavy Vehicle','Motorcycle','Bus','Equipment','None']; @endphp
                <select name="license_type" class="form-select">
                    <option value="">Select a type</option>
                    @foreach($ltOpts as $opt)
                        <option value="{{ $opt }}" {{ $lt===$opt?'selected':'' }}>{{ $opt }}</option>
                    @endforeach
                    @if($lt && !in_array($lt,$ltOpts,true))<option value="{{ $lt }}" selected>{{ $lt }}</option>@endif
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ════════════ SECTION 5: OTHERS INFO (single instance) ════════════ --}}
<div class="card hr-section">
    <div class="card-header"><i class="bi bi-info-circle"></i> Others Info</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 field-row">
                <label class="form-label">Duration of Stay</label>
                <div class="row g-2">
                    <div class="col-6"><input type="text" name="duration_stay_en" placeholder="English" class="form-control" value="{{ $rel($other, 'duration_stay_en', '02 Years') }}" data-ar-source="duration_stay_ar" data-ar-dict="duration"></div>
                    <div class="col-6">
                        <div class="input-group input-group-sm">
                            <input type="text" id="duration_stay_ar" name="duration_stay_ar" dir="rtl" placeholder="Arabic" class="form-control ar-target" value="{{ $rel($other, 'duration_stay_ar') }}">
                            <button type="button" class="btn btn-outline-secondary ar-gen" data-target="duration_stay_ar" data-dict="duration" tabindex="-1" title="Generate Arabic">ع</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Date of Arrival</label>
                <div class="row g-2">
                    <div class="col-6"><input type="date" name="arrival_date" class="form-control" value="{{ $dt($other, 'arrival_date') }}"></div>
                    <div class="col-6"><input type="text" name="arrival_date_ar" dir="rtl" placeholder="Arabic" class="form-control" value="{{ $rel($other, 'arrival_date_ar') }}"></div>
                </div>
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Date of Departure</label>
                <div class="row g-2">
                    <div class="col-6"><input type="date" name="departure_date" class="form-control" value="{{ $dt($other, 'departure_date') }}"></div>
                    <div class="col-6"><input type="text" name="departure_date_ar" dir="rtl" placeholder="Arabic" class="form-control" value="{{ $rel($other, 'departure_date_ar') }}"></div>
                </div>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Fingerprint</label>
                <input type="text" name="fingerprint" class="form-control" value="{{ $rel($clearance, 'fingerprint', 'Yes') }}">
            </div>

            <div class="col-md-6 field-row">
                <label class="form-label">Agent</label>
                <select name="agent_id" class="form-select">
                    <option value="">Select a type</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ (string) $v('agent_id') === (string) $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 field-row">
                <label class="form-label">Status</label>
                @php $st = $v('status', 'active'); @endphp
                <select name="status" class="form-select">
                    @foreach(['active'=>'Active','inactive'=>'Inactive','blacklisted'=>'Blacklisted','listed'=>'Listed'] as $key=>$lbl)
                    <option value="{{ $key }}" {{ $st===$key?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ════════════ OPTIONAL: EMPLOYMENT / MEDICAL (feeds Employment Agreement & Checklist) ════════════ --}}
<div class="card hr-section">
    <div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#optionalSection" style="cursor:pointer;">
        <i class="bi bi-briefcase"></i> Employment / Medical
        <small class="text-muted ms-1" style="font-weight:500;">(optional — used on Employment Agreement &amp; Checklist)</small>
        <i class="bi bi-chevron-down ms-auto"></i>
    </div>
    <div class="collapse {{ $errors->hasAny(['salary','medical_date','clearance_issue_date','clearance_expiry_date']) ? 'show' : '' }}" id="optionalSection">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 field-row">
                    <label class="form-label">Contract Period</label>
                    <input type="text" name="contract_period" class="form-control" value="{{ $rel($other, 'contract_period') }}" placeholder="e.g. 2 years">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Salary (SAR)</label>
                    <input type="number" step="0.01" min="0" name="salary" class="form-control" value="{{ $rel($other, 'salary') }}">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Work City</label>
                    <input type="text" name="work_city" class="form-control" value="{{ $rel($other, 'work_city') }}">
                </div>
                <div class="col-md-6 field-row">
                    <label class="form-label">Employer Name</label>
                    <input type="text" name="employer_name" class="form-control" value="{{ $rel($other, 'employer_name') }}">
                </div>
                <div class="col-md-6 field-row">
                    <label class="form-label">Employer Phone</label>
                    <input type="text" name="employer_phone" class="form-control" value="{{ $rel($other, 'employer_phone') }}">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Medical Date</label>
                    <input type="date" name="medical_date" class="form-control" value="{{ $dt($clearance, 'medical_date') }}">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Medical Center</label>
                    <input type="text" name="medical_center" class="form-control" value="{{ $rel($clearance, 'medical_center') }}">
                </div>
                <div class="col-md-4 field-row d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="medical_fit" value="1" id="medicalFit"
                            {{ old('medical_fit', $clearance?->medical_fit) ? 'checked' : '' }}>
                        <label class="form-check-label" for="medicalFit"><strong>Medically Fit</strong></label>
                    </div>
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Clearance Issue Date</label>
                    <input type="date" name="clearance_issue_date" class="form-control" value="{{ $dt($clearance, 'clearance_issue_date') }}">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Clearance Expiry Date</label>
                    <input type="date" name="clearance_expiry_date" class="form-control" value="{{ $dt($clearance, 'clearance_expiry_date') }}">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Clearance Country</label>
                    <input type="text" name="clearance_country" class="form-control" value="{{ $rel($clearance, 'clearance_country') }}">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">File Number</label>
                    <input type="text" name="file_number" class="form-control" value="{{ $v('file_number') }}" placeholder="auto / optional">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ $v('phone') }}">
                </div>
                <div class="col-md-4 field-row">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ $v('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 field-row">
                    <label class="form-label">Business Address &amp; Phone <small class="text-muted">(Application Form)</small></label>
                    <div class="row g-2">
                        <div class="col-6"><input type="text" name="business_address_en" placeholder="English" class="form-control" value="{{ $rel($other, 'business_address_en') }}"></div>
                        <div class="col-6"><input type="text" name="business_address_ar" dir="rtl" placeholder="Arabic" class="form-control" value="{{ $rel($other, 'business_address_ar') }}"></div>
                    </div>
                </div>
                <div class="col-md-6 field-row">
                    <label class="form-label">Name/Address in Kingdom <small class="text-muted">(Application Form)</small></label>
                    <div class="row g-2">
                        <div class="col-6"><input type="text" name="kingdom_address_en" placeholder="English" class="form-control" value="{{ $rel($other, 'kingdom_address_en') }}"></div>
                        <div class="col-6"><input type="text" name="kingdom_address_ar" dir="rtl" placeholder="Arabic" class="form-control" value="{{ $rel($other, 'kingdom_address_ar') }}"></div>
                    </div>
                </div>
                <div class="col-12 field-row">
                    <label class="form-label">Notes / Remarks</label>
                    <textarea name="remarks" rows="2" class="form-control">{{ $rel($other, 'remarks') }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // Auto-calculate passport validity date from issue date + selected validity (editable)
    var issue   = document.getElementById('passport_issue_date');
    var expiry  = document.getElementById('passport_expiry_date');
    var manual  = false;
    if (expiry) expiry.addEventListener('input', function () { manual = true; });

    function years() {
        var r = document.querySelector('input[name="passport_validity_years"]:checked');
        return r ? parseInt(r.value, 10) : null;
    }
    function recalc(force) {
        if (!issue || !expiry || !issue.value) return;
        if (!force && manual && expiry.value) return;
        var y = years();
        if (!y) return;
        var d = new Date(issue.value);
        if (isNaN(d.getTime())) return;
        d.setFullYear(d.getFullYear() + y);
        d.setDate(d.getDate() - 1);
        expiry.value = d.toISOString().slice(0, 10);
    }
    if (issue) issue.addEventListener('change', function () { recalc(true); });
    document.querySelectorAll('input[name="passport_validity_years"]').forEach(function (el) {
        el.addEventListener('change', function () { recalc(true); });
    });
})();
</script>
<script>
(function () {
    // ── Local English→Arabic dictionary (safe, offline, no external API) ──
    var DICT = {
        nationality: {
            'bangladesh':'بنغلاديش','india':'الهند','pakistan':'باكستان','philippines':'الفلبين',
            'nepal':'نيبال','sri lanka':'سريلانكا'
        },
        religion:  { 'muslim':'مسلم','non-muslim':'غير مسلم','non muslim':'غير مسلم' },
        sex:       { 'male':'ذكر','female':'أنثى' },
        marital:   { 'married':'متزوج','unmarried':'غير متزوج','single':'غير متزوج' },
        travel:    { 'work':'الشغل','visit':'زيارة','umrah':'العمرة','residence':'إقامة','hajj':'الحج','diplomacy':'الدبلوماسية','transit':'عبور' },
        fingerprint:{ 'yes':'نعم','no':'لا' },
        duration:  { '02 years':'سنتان','2 years':'سنتان','two years':'سنتان','1 year':'سنة واحدة','one year':'سنة واحدة','01 year':'سنة واحدة' },
        profession:{
            'domestic worker':'عامل منزلي','house driver':'سائق خاص','private driver':'سائق خاص',
            'cleaner':'عامل نظافة','labour':'عامل','labourer':'عامل','labor':'عامل','worker':'عامل',
            'load and unload worker':'عامل تحميل وتنزيل','driver':'سائق'
        },
        qualification:{ 'secondary':'ثانوي','primary':'ابتدائي','graduate':'خريج','none':'لا يوجد','illiterate':'أمي' },
        city:      { 'riyadh':'الرياض','jeddah':'جدة','jeddah ':'جدة','dammam':'الدمام','makkah':'مكة المكرمة','mecca':'مكة المكرمة','madinah':'المدينة المنورة','dhaka':'دكا' },
        generic:   {} // no auto translation; user types Arabic manually
    };

    function lookup(dict, value) {
        var key = (value || '').trim().toLowerCase();
        if (!key) return '';
        return (DICT[dict] && DICT[dict][key]) ? DICT[dict][key] : '';
    }

    // Mark Arabic field as manually edited so auto-fill won't overwrite it.
    document.querySelectorAll('.ar-target').forEach(function (el) {
        if (el.value.trim() !== '') el.dataset.touched = '1';
        el.addEventListener('input', function () { el.dataset.touched = '1'; });
    });

    // Auto-fill on English source change (only if Arabic is empty / untouched).
    document.querySelectorAll('[data-ar-source]').forEach(function (src) {
        src.addEventListener('input', function () {
            var target = document.getElementById(src.dataset.arSource);
            if (!target) return;
            if (target.dataset.touched === '1' && target.value.trim() !== '') return;
            var ar = lookup(src.dataset.arDict || 'generic', src.value);
            if (ar) { target.value = ar; delete target.dataset.touched; }
        });
    });

    // "ع" Regenerate button — forces lookup from the matching English source.
    document.querySelectorAll('.ar-gen').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.target);
            var src = document.querySelector('[data-ar-source="' + btn.dataset.target + '"]');
            if (!target || !src) return;
            var ar = lookup(btn.dataset.dict || 'generic', src.value);
            if (ar) { target.value = ar; delete target.dataset.touched; }
        });
    });
})();
</script>
@endpush
