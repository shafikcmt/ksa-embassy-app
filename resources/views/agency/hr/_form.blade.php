{{--
    Shared HR / Candidate form — used by both create and edit.
    Single-page layout (no wizard): five bordered sections on one page.
    Expects: $agents (Collection), optionally $hr (HrProfile|null), and $mode ('create'|'edit').
    All input name="" attributes mirror the controller exactly — this is a UI re-skin only.

    Field visibility: each optional field is gated by $on('<key>') which reads the
    agency's HR Form Field controls (see App\Support\HrFieldControls). Required fields
    are always shown. When an optional field is Inactive on EDIT, its existing value is
    preserved via a hidden input so saving never wipes stored data.
--}}
@php
    $hr        = $hr ?? null;
    $mode      = $mode ?? ($hr ? 'edit' : 'create');
    $passport  = $hr?->passport;
    $visa      = $hr?->visa;
    $clearance = $hr?->clearance;
    $other     = $hr?->otherInfo;

    $v   = fn($field, $default = null) => old($field, $hr?->{$field} ?? $default);
    $rel = fn($model, $field, $default = null) => old($field, $model?->{$field} ?? $default);
    $dt  = fn($model, $field) => old($field, optional($model?->{$field})->format('Y-m-d'));

    // Field Active/Inactive controls (agency override → global default → on).
    $fieldStatus = \App\Support\HrFieldControls::resolved(auth()->user()->agency_id);
    $on = fn($key) => $fieldStatus[$key] ?? true;
    $isEdit = $mode === 'edit';

    // Compact, premium input styles (slightly shorter + lighter than before).
    $inp = 'h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-brand-400 focus:ring-brand-400';
    $ta  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm transition focus:border-brand-400 focus:ring-brand-400';
    $seg = 'flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 py-2 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-hover:border-brand-300';
    $arBtn = 'ar-gen h-10 shrink-0 rounded-lg border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50';

    // Common nationalities for the searchable <datalist> (free text still allowed).
    $nationalityOptions = ['BANGLADESH','INDIA','PAKISTAN','NEPAL','SRI LANKA','PHILIPPINES','INDONESIA','MYANMAR','KENYA','UGANDA','ETHIOPIA','NIGERIA'];

    $gender = $v('gender'); $marital = $v('marital_status'); $religion = $v('religion');
@endphp

<div>
    @if($errors->any())
        <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <i class="bi bi-exclamation-circle-fill mt-0.5 text-rose-500"></i>
            <span>Please review the highlighted fields below and try again.</span>
        </div>
    @endif

    {{-- ── Preserved fields ──────────────────────────────────────────────
         These are not shown on the simplified form but are still consumed by
         the controller / document generation. On edit we keep existing values
         so saving the form never wipes data the documents rely on. --}}
    <input type="hidden" name="status" value="{{ $v('status', 'active') }}">
    @if($isEdit)
        <input type="hidden" name="full_name_ar"  value="{{ old('full_name_ar', $hr?->full_name_ar) }}">
        <input type="hidden" name="file_number"   value="{{ $v('file_number') }}">
        <input type="hidden" name="phone"         value="{{ $v('phone') }}">
        <input type="hidden" name="email"         value="{{ $v('email') }}">
        <input type="hidden" name="occupation"    value="{{ $v('occupation') }}">
        <input type="hidden" name="notes"         value="{{ $v('notes') }}">

        <input type="hidden" name="passport_type" value="{{ $rel($passport, 'passport_type', 'regular') }}">

        <input type="hidden" name="visa_type"        value="{{ $rel($visa, 'visa_type') }}">
        <input type="hidden" name="visa_expiry_date" value="{{ $dt($visa, 'expiry_date') }}">
        <input type="hidden" name="border_number"    value="{{ $rel($visa, 'border_number') }}">

        <input type="hidden" name="clearance_issue_date"  value="{{ $dt($clearance, 'clearance_issue_date') }}">
        <input type="hidden" name="clearance_expiry_date" value="{{ $dt($clearance, 'clearance_expiry_date') }}">
        <input type="hidden" name="clearance_country"     value="{{ $rel($clearance, 'clearance_country') }}">
        <input type="hidden" name="medical_date"          value="{{ $dt($clearance, 'medical_date') }}">
        <input type="hidden" name="medical_center"        value="{{ $rel($clearance, 'medical_center') }}">
        @if(old('medical_fit', $clearance?->medical_fit))<input type="hidden" name="medical_fit" value="1">@endif

        <input type="hidden" name="contract_period"     value="{{ $rel($other, 'contract_period') }}">
        <input type="hidden" name="salary"              value="{{ $rel($other, 'salary') }}">
        <input type="hidden" name="work_city"           value="{{ $rel($other, 'work_city') }}">
        <input type="hidden" name="employer_name"       value="{{ $rel($other, 'employer_name') }}">
        <input type="hidden" name="employer_phone"      value="{{ $rel($other, 'employer_phone') }}">
        <input type="hidden" name="remarks"             value="{{ $rel($other, 'remarks') }}">
        <input type="hidden" name="business_address_en" value="{{ $rel($other, 'business_address_en') }}">
        <input type="hidden" name="business_address_ar" value="{{ $rel($other, 'business_address_ar') }}">
        <input type="hidden" name="kingdom_address_en"  value="{{ $rel($other, 'kingdom_address_en') }}">
        <input type="hidden" name="kingdom_address_ar"  value="{{ $rel($other, 'kingdom_address_ar') }}">
    @endif

    {{-- Searchable nationality suggestions (free text still allowed) --}}
    <datalist id="nationalityList">
        @foreach($nationalityOptions as $optNat)<option value="{{ $optNat }}"></option>@endforeach
    </datalist>

    <div class="space-y-5">

        {{-- ════════ 1 · PERSONAL INFO ════════ --}}
        <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <legend class="flex items-center gap-2 px-2 text-sm font-bold text-brand-700">
                <i class="bi bi-person-vcard"></i> Personal Info
            </legend>
            <div class="grid grid-cols-1 gap-x-4 gap-y-3.5 sm:grid-cols-2">
                <x-ui.field label="Name" name="full_name_en" :required="true" hint="As printed in the passport" class="sm:col-span-2">
                    <input type="text" id="full_name_en" name="full_name_en" required value="{{ $v('full_name_en') }}" placeholder="e.g. MOHAMMED RAHMAN" autocomplete="off" class="{{ $inp }} js-trim uppercase placeholder:normal-case @error('full_name_en') !border-rose-400 @enderror">
                </x-ui.field>

                @if($on('father_name'))
                    <x-ui.field label="Father" name="father_name">
                        <input type="text" name="father_name" value="{{ $v('father_name') }}" placeholder="Optional" class="{{ $inp }} js-trim">
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="father_name" value="{{ $v('father_name') }}">
                @endif

                @if($on('mother_name'))
                    <x-ui.field label="Mother" name="mother_name">
                        <input type="text" name="mother_name" value="{{ $v('mother_name') }}" placeholder="Optional" class="{{ $inp }} js-trim">
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="mother_name" value="{{ $v('mother_name') }}">
                @endif

                <x-ui.field label="Date of Birth" name="date_of_birth" :required="true">
                    <input type="date" id="date_of_birth" name="date_of_birth" required max="{{ now()->subYears(15)->format('Y-m-d') }}" value="{{ old('date_of_birth', optional($hr?->date_of_birth)->format('Y-m-d')) }}" class="{{ $inp }} @error('date_of_birth') !border-rose-400 @enderror">
                </x-ui.field>
                <x-ui.field label="Place of Birth" name="place_of_birth" :required="true">
                    <input type="text" name="place_of_birth" required value="{{ $v('place_of_birth') }}" placeholder="e.g. DHAKA" class="{{ $inp }} js-trim @error('place_of_birth') !border-rose-400 @enderror">
                </x-ui.field>

                <x-ui.field label="MOFA Application ID" name="mofa_new" :required="true" :hint="$on('mofa_old') ? 'New Mofa is required · Old Mofa optional' : 'New Mofa is required'" class="sm:col-span-2">
                    <div class="grid {{ $on('mofa_old') ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
                        <input type="text" name="mofa_new" required placeholder="New Mofa" value="{{ $v('mofa_new') }}" class="{{ $inp }} @error('mofa_new') !border-rose-400 @enderror">
                        @if($on('mofa_old'))
                            <input type="text" name="mofa_old" placeholder="Old Mofa (optional)" value="{{ $v('mofa_old') }}" class="{{ $inp }}">
                        @elseif($isEdit)
                            <input type="hidden" name="mofa_old" value="{{ $v('mofa_old') }}">
                        @endif
                    </div>
                </x-ui.field>

                {{-- Nationality pair — aligned 2-up with a modern "Same as present" sync toggle --}}
                @if($on('previous_nationality'))
                    @php
                        $presentNat  = $v('nationality', 'BANGLADESH');
                        $previousNat = $v('previous_nationality', 'BANGLADESH');
                        $natSynced   = $previousNat !== '' && $previousNat === $presentNat;
                    @endphp
                    <div class="sm:col-span-2">
                        <div class="grid grid-cols-1 gap-x-4 gap-y-3.5 sm:grid-cols-2">
                            {{-- Present Nationality (required) --}}
                            <div>
                                <div class="mb-1 flex h-6 items-center">
                                    <label for="nationality" class="text-xs font-semibold text-slate-600">Present Nationality<span class="ml-0.5 text-rose-500">*</span></label>
                                </div>
                                <input type="text" id="nationality" name="nationality" list="nationalityList" required value="{{ $presentNat }}" autocomplete="off" class="{{ $inp }} uppercase @error('nationality') !border-rose-400 @enderror">
                                @error('nationality')
                                    <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @else
                                    <p class="mt-1 text-xs text-slate-400">Type to search or enter manually</p>
                                @enderror
                            </div>
                            {{-- Previous Nationality (optional) with sync toggle --}}
                            <div>
                                <div class="mb-1 flex h-6 items-center justify-between gap-2">
                                    <label for="previous_nationality" class="text-xs font-semibold text-slate-600">Previous Nationality</label>
                                    <label class="inline-flex cursor-pointer select-none items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[0.7rem] font-semibold text-slate-500 transition hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:text-brand-700">
                                        <input type="checkbox" id="sameAsPresent" class="peer sr-only" {{ $natSynced ? 'checked' : '' }}>
                                        <i class="bi bi-link-45deg text-sm peer-checked:hidden"></i>
                                        <i class="bi bi-check-circle-fill hidden text-sm peer-checked:inline"></i>
                                        Same as present
                                    </label>
                                </div>
                                <input type="text" id="previous_nationality" name="previous_nationality" list="nationalityList" value="{{ $previousNat }}" autocomplete="off" class="{{ $inp }} uppercase">
                                <p class="mt-1 text-xs text-slate-400">Auto-fills from present when synced</p>
                            </div>
                        </div>
                    </div>
                @else
                    @if($isEdit)<input type="hidden" name="previous_nationality" value="{{ $v('previous_nationality') }}">@endif
                    <x-ui.field label="Present Nationality" name="nationality" :required="true" hint="Type to search or enter manually">
                        <input type="text" id="nationality" name="nationality" list="nationalityList" required value="{{ $v('nationality', 'BANGLADESH') }}" autocomplete="off" class="{{ $inp }} uppercase @error('nationality') !border-rose-400 @enderror">
                    </x-ui.field>
                @endif
                <x-ui.field label="Sex" name="gender" :required="true">
                    <div class="flex gap-2">
                        @foreach(['male' => ['Male','bi-gender-male'], 'female' => ['Female','bi-gender-female']] as $val => $meta)
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="gender" value="{{ $val }}" class="peer sr-only" {{ $gender === $val ? 'checked' : '' }} {{ $loop->first ? 'required' : '' }}>
                                <span class="{{ $seg }}"><i class="bi {{ $meta[1] }}"></i>{{ $meta[0] }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.field>
                <x-ui.field label="Marital Status" name="marital_status" :required="true">
                    <div class="flex gap-2">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="marital_status" value="married" class="peer sr-only" {{ $marital === 'married' ? 'checked' : '' }} required>
                            <span class="{{ $seg }}"><i class="bi bi-heart"></i>Married</span>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="marital_status" value="single" class="peer sr-only" {{ in_array($marital, ['single','divorced','widowed'], true) ? 'checked' : '' }}>
                            <span class="{{ $seg }}"><i class="bi bi-person"></i>Unmarried</span>
                        </label>
                    </div>
                </x-ui.field>

                @if($on('sect'))
                    <x-ui.field label="Sect" name="sect" hint="Optional">
                        <input type="text" name="sect" value="{{ $v('sect') }}" placeholder="e.g. Sunni" class="{{ $inp }} js-trim">
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="sect" value="{{ $v('sect') }}">
                @endif

                <x-ui.field label="Religion" name="religion" :required="true">
                    <div class="flex gap-2">
                        @foreach(['Muslim' => ['Muslim','bi-moon-stars'], 'Non-muslim' => ['Non-muslim','bi-person']] as $val => $meta)
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="religion" value="{{ $val }}" class="peer sr-only" {{ $religion === $val ? 'checked' : '' }} {{ $loop->first ? 'required' : '' }}>
                                <span class="{{ $seg }}"><i class="bi {{ $meta[1] }}"></i>{{ $meta[0] }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.field>

                @if($on('home_address'))
                    <x-ui.field label="Home Address &amp; Phone" name="home_address" class="sm:col-span-2">
                        <textarea name="home_address" rows="2" placeholder="Village / city, district — and a contact phone number" class="{{ $ta }}">{{ $v('home_address') }}</textarea>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="home_address" value="{{ $v('home_address') }}">
                @endif
            </div>
        </fieldset>

        {{-- ════════ 2 · PASSPORT INFO ════════ --}}
        <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <legend class="flex items-center gap-2 px-2 text-sm font-bold text-brand-700">
                <i class="bi bi-passport"></i> Passport Info
            </legend>
            <div class="grid grid-cols-1 gap-x-4 gap-y-3.5 sm:grid-cols-2">
                @if($on('passport_issue_place'))
                    <x-ui.field label="Passport Issue Place" name="passport_issue_place">
                        <input type="text" name="passport_issue_place" value="{{ $rel($passport, 'issue_place', 'DHAKA') }}" class="{{ $inp }}">
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="passport_issue_place" value="{{ $rel($passport, 'issue_place') }}">
                @endif

                <x-ui.field label="Passport No" name="passport_number" :required="true">
                    <input type="text" name="passport_number" required value="{{ $rel($passport, 'passport_number') }}" class="{{ $inp }} @error('passport_number') !border-rose-400 @enderror">
                </x-ui.field>
                <x-ui.field label="Passport Issue Date" name="passport_issue_date" :required="true">
                    <input type="date" id="passport_issue_date" name="passport_issue_date" required value="{{ $dt($passport, 'issue_date') }}" class="{{ $inp }} @error('passport_issue_date') !border-rose-400 @enderror">
                </x-ui.field>

                @if($on('passport_validity_years'))
                    <x-ui.field label="Passport Validity" name="passport_validity_years">
                        @php $validity = (int) $rel($passport, 'validity_years', 5); @endphp
                        <div class="flex gap-2">
                            @foreach([5 => '5 Years', 10 => '10 Years'] as $val => $lbl)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="passport_validity_years" value="{{ $val }}" class="peer sr-only" {{ $validity === $val ? 'checked' : '' }}>
                                    <span class="block rounded-lg border border-slate-300 py-2 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white">{{ $lbl }}</span>
                                </label>
                            @endforeach
                        </div>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="passport_validity_years" value="{{ $rel($passport, 'validity_years') }}">
                @endif

                <x-ui.field label="Passport Validity Date" name="passport_expiry_date" :required="true" hint="Auto-filled from issue date + validity; you can edit it." class="sm:col-span-2 sm:max-w-[50%]">
                    <input type="date" id="passport_expiry_date" name="passport_expiry_date" required value="{{ $dt($passport, 'expiry_date') }}" class="{{ $inp }} @error('passport_expiry_date') !border-rose-400 @enderror">
                </x-ui.field>
            </div>
        </fieldset>

        {{-- ════════ 3 · VISA INFO ════════ --}}
        <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <legend class="flex items-center gap-2 px-2 text-sm font-bold text-brand-700">
                <i class="bi bi-globe2"></i> Visa Info
            </legend>
            <div class="grid grid-cols-1 gap-x-4 gap-y-3.5 sm:grid-cols-2">
                <x-ui.field label="Visa No" name="visa_number" :required="true">
                    <input type="text" name="visa_number" required value="{{ $rel($visa, 'visa_number') }}" class="{{ $inp }} @error('visa_number') !border-rose-400 @enderror">
                </x-ui.field>
                <x-ui.field label="Visa Date" name="visa_issue_date" :required="true">
                    <input type="date" name="visa_issue_date" required value="{{ $dt($visa, 'issue_date') }}" class="{{ $inp }} @error('visa_issue_date') !border-rose-400 @enderror">
                </x-ui.field>
                <x-ui.field label="Sponsor Name" name="sponsor_name" :required="true" hint="Arabic auto-fills · editable">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="sponsor_name" required placeholder="English" value="{{ $rel($visa, 'sponsor_name') }}" data-ar-source="sponsor_name_ar" data-ar-dict="generic" class="{{ $inp }} @error('sponsor_name') !border-rose-400 @enderror">
                        <div class="flex gap-1">
                            <input type="text" id="sponsor_name_ar" name="sponsor_name_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($visa, 'sponsor_name_ar') }}" class="{{ $inp }} ar-target ar-input">
                            <button type="button" class="{{ $arBtn }}" data-target="sponsor_name_ar" data-dict="generic" tabindex="-1" title="Generate Arabic">ع</button>
                        </div>
                    </div>
                </x-ui.field>
                <x-ui.field label="Sponsor ID" name="sponsor_id" :required="true">
                    <input type="text" name="sponsor_id" required value="{{ $rel($visa, 'sponsor_id') }}" class="{{ $inp }} @error('sponsor_id') !border-rose-400 @enderror">
                </x-ui.field>

                @if($on('visa_issue_place'))
                    <x-ui.field label="Place of Issue" name="visa_issue_place" hint="English / Arabic">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="visa_issue_place" placeholder="English" value="{{ $rel($visa, 'issue_place') }}" data-ar-source="visa_issue_place_ar" data-ar-dict="city" class="{{ $inp }}">
                            <div class="flex gap-1">
                                <input type="text" id="visa_issue_place_ar" name="visa_issue_place_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($visa, 'issue_place_ar') }}" class="{{ $inp }} ar-target ar-input">
                                <button type="button" class="{{ $arBtn }}" data-target="visa_issue_place_ar" data-dict="city" tabindex="-1" title="Generate Arabic">ع</button>
                            </div>
                        </div>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="visa_issue_place" value="{{ $rel($visa, 'issue_place') }}">
                    <input type="hidden" name="visa_issue_place_ar" value="{{ $rel($visa, 'issue_place_ar') }}">
                @endif

                @if($on('qualification'))
                    <x-ui.field label="Qualification" name="qualification_en" hint="English / Arabic">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="qualification_en" placeholder="English" value="{{ $rel($visa, 'qualification_en') }}" data-ar-source="qualification_ar" data-ar-dict="qualification" class="{{ $inp }}">
                            <div class="flex gap-1">
                                <input type="text" id="qualification_ar" name="qualification_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($visa, 'qualification_ar') }}" class="{{ $inp }} ar-target ar-input">
                                <button type="button" class="{{ $arBtn }}" data-target="qualification_ar" data-dict="qualification" tabindex="-1" title="Generate Arabic">ع</button>
                            </div>
                        </div>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="qualification_en" value="{{ $rel($visa, 'qualification_en') }}">
                    <input type="hidden" name="qualification_ar" value="{{ $rel($visa, 'qualification_ar') }}">
                @endif

                <x-ui.field label="Profession" name="profession_en" :required="true" hint="English / Arabic">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="profession_en" required placeholder="English" value="{{ $rel($visa, 'profession_en') }}" data-ar-source="profession_ar" data-ar-dict="profession" class="{{ $inp }} @error('profession_en') !border-rose-400 @enderror">
                        <div class="flex gap-1">
                            <input type="text" id="profession_ar" name="profession_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($visa, 'profession_ar') }}" class="{{ $inp }} ar-target ar-input">
                            <button type="button" class="{{ $arBtn }}" data-target="profession_ar" data-dict="profession" tabindex="-1" title="Generate Arabic">ع</button>
                        </div>
                    </div>
                </x-ui.field>

                @if($on('travel_purpose'))
                    <x-ui.field label="Travel Purpose" name="travel_purpose">
                        @php $tp = $rel($visa, 'travel_purpose', 'Work'); $tpOpts = ['Work','Family Visit','Business','Hajj','Umrah','Visit','Other']; @endphp
                        <select name="travel_purpose" class="{{ $inp }}">
                            @foreach($tpOpts as $opt)<option value="{{ $opt }}" {{ $tp === $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                            @if($tp && !in_array($tp, $tpOpts, true))<option value="{{ $tp }}" selected>{{ $tp }}</option>@endif
                        </select>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="travel_purpose" value="{{ $rel($visa, 'travel_purpose') }}">
                @endif

                @if($on('musaned_no'))
                    <x-ui.field label="Musaned No" name="musaned_no">
                        <input type="text" name="musaned_no" value="{{ $rel($visa, 'musaned_no') }}" class="{{ $inp }}">
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="musaned_no" value="{{ $rel($visa, 'musaned_no') }}">
                @endif

                <x-ui.field label="Wakala No" name="wakala_no" :required="true">
                    <input type="text" name="wakala_no" required value="{{ $rel($visa, 'wakala_no') }}" class="{{ $inp }} @error('wakala_no') !border-rose-400 @enderror">
                </x-ui.field>
            </div>
        </fieldset>

        {{-- ════════ 4 · POLICE CLEARANCE & DRIVING LICENSE INFO ════════ --}}
        <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <legend class="flex items-center gap-2 px-2 text-sm font-bold text-brand-700">
                <i class="bi bi-shield-check"></i> Police Clearance &amp; Driving License Info
            </legend>
            <div class="grid grid-cols-1 gap-x-4 gap-y-3.5 sm:grid-cols-2">
                @if($on('pc_qr_code'))
                    <x-ui.field label="P.C QRCode" name="pc_qr_code" class="sm:col-span-2">
                        <input type="text" name="pc_qr_code" value="{{ $rel($clearance, 'pc_qr_code') }}" class="{{ $inp }}">
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="pc_qr_code" value="{{ $rel($clearance, 'pc_qr_code') }}">
                @endif

                <x-ui.field label="P.C Reference No." name="police_clearance_number" :required="true">
                    <input type="text" name="police_clearance_number" required value="{{ $rel($clearance, 'police_clearance_number') }}" class="{{ $inp }} @error('police_clearance_number') !border-rose-400 @enderror">
                </x-ui.field>

                @if($on('license_type'))
                    <x-ui.field label="License Type" name="license_type">
                        @php $lt = $rel($clearance, 'license_type'); $ltOpts = ['Light Vehicle','Heavy Vehicle','Motorcycle','Bus','Equipment','None']; @endphp
                        <select name="license_type" class="{{ $inp }}">
                            <option value="">Select a type</option>
                            @foreach($ltOpts as $opt)<option value="{{ $opt }}" {{ $lt === $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                            @if($lt && !in_array($lt, $ltOpts, true))<option value="{{ $lt }}" selected>{{ $lt }}</option>@endif
                        </select>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="license_type" value="{{ $rel($clearance, 'license_type') }}">
                @endif
            </div>
        </fieldset>

        {{-- ════════ 5 · OTHERS INFO ════════ --}}
        @php
            $showDuration  = $on('duration_stay');
            $showFinger    = $on('fingerprint');
            $showArrival   = $on('arrival_date');
            $showDeparture = $on('departure_date');
            $showAgent     = $on('agent_id');
            $anyOther      = $showDuration || $showFinger || $showArrival || $showDeparture || $showAgent;
        @endphp
        @if($anyOther)
        <fieldset class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <legend class="flex items-center gap-2 px-2 text-sm font-bold text-brand-700">
                <i class="bi bi-info-circle"></i> Others Info
            </legend>
            <div class="grid grid-cols-1 gap-x-4 gap-y-3.5 sm:grid-cols-2">
                @if($showDuration)
                    <x-ui.field label="Duration of Stay" name="duration_stay_en" hint="English / Arabic">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="duration_stay_en" placeholder="English" value="{{ $rel($other, 'duration_stay_en', '02 Years') }}" data-ar-source="duration_stay_ar" data-ar-dict="duration" class="{{ $inp }}">
                            <div class="flex gap-1">
                                <input type="text" id="duration_stay_ar" name="duration_stay_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'duration_stay_ar') }}" class="{{ $inp }} ar-target ar-input">
                                <button type="button" class="{{ $arBtn }}" data-target="duration_stay_ar" data-dict="duration" tabindex="-1" title="Generate Arabic">ع</button>
                            </div>
                        </div>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="duration_stay_en" value="{{ $rel($other, 'duration_stay_en') }}">
                    <input type="hidden" name="duration_stay_ar" value="{{ $rel($other, 'duration_stay_ar') }}">
                @endif

                @if($showFinger)
                    <x-ui.field label="Fingerprint" name="fingerprint">
                        <input type="text" name="fingerprint" value="{{ $rel($clearance, 'fingerprint', 'Yes') }}" class="{{ $inp }}">
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="fingerprint" value="{{ $rel($clearance, 'fingerprint') }}">
                @endif

                @if($showArrival)
                    <x-ui.field label="Date of Arrival" name="arrival_date" hint="Arabic auto-fills · editable">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="arrival_date" value="{{ $dt($other, 'arrival_date') }}" data-ar-source="arrival_date_ar" data-ar-dict="date" class="{{ $inp }}">
                            <div class="flex gap-1">
                                <input type="text" id="arrival_date_ar" name="arrival_date_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'arrival_date_ar') }}" class="{{ $inp }} ar-target ar-input">
                                <button type="button" class="{{ $arBtn }}" data-target="arrival_date_ar" data-dict="date" tabindex="-1" title="Generate Arabic">ع</button>
                            </div>
                        </div>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="arrival_date" value="{{ $dt($other, 'arrival_date') }}">
                    <input type="hidden" name="arrival_date_ar" value="{{ $rel($other, 'arrival_date_ar') }}">
                @endif

                @if($showDeparture)
                    <x-ui.field label="Date of Departure" name="departure_date" hint="Arabic auto-fills · editable">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="departure_date" value="{{ $dt($other, 'departure_date') }}" data-ar-source="departure_date_ar" data-ar-dict="date" class="{{ $inp }}">
                            <div class="flex gap-1">
                                <input type="text" id="departure_date_ar" name="departure_date_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'departure_date_ar') }}" class="{{ $inp }} ar-target ar-input">
                                <button type="button" class="{{ $arBtn }}" data-target="departure_date_ar" data-dict="date" tabindex="-1" title="Generate Arabic">ع</button>
                            </div>
                        </div>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="departure_date" value="{{ $dt($other, 'departure_date') }}">
                    <input type="hidden" name="departure_date_ar" value="{{ $rel($other, 'departure_date_ar') }}">
                @endif

                @if($showAgent)
                    <x-ui.field label="Agent" name="agent_id" class="sm:col-span-2">
                        <select name="agent_id" class="{{ $inp }} sm:max-w-[50%]">
                            <option value="">Select an agent</option>
                            @foreach($agents as $agent)<option value="{{ $agent->id }}" {{ (string) $v('agent_id') === (string) $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>@endforeach
                        </select>
                    </x-ui.field>
                @elseif($isEdit)
                    <input type="hidden" name="agent_id" value="{{ $v('agent_id') }}">
                @endif
            </div>
        </fieldset>
        @elseif($isEdit)
            {{-- Whole section hidden — preserve all its values so nothing is wiped. --}}
            <input type="hidden" name="duration_stay_en" value="{{ $rel($other, 'duration_stay_en') }}">
            <input type="hidden" name="duration_stay_ar" value="{{ $rel($other, 'duration_stay_ar') }}">
            <input type="hidden" name="fingerprint" value="{{ $rel($clearance, 'fingerprint') }}">
            <input type="hidden" name="arrival_date" value="{{ $dt($other, 'arrival_date') }}">
            <input type="hidden" name="arrival_date_ar" value="{{ $rel($other, 'arrival_date_ar') }}">
            <input type="hidden" name="departure_date" value="{{ $dt($other, 'departure_date') }}">
            <input type="hidden" name="departure_date_ar" value="{{ $rel($other, 'departure_date_ar') }}">
            <input type="hidden" name="agent_id" value="{{ $v('agent_id') }}">
        @endif

        {{-- ── Action bar (Reset / Save) ─────────────────────────── --}}
        <div class="flex items-center justify-end gap-3 pb-2">
            <button type="reset" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
            <button type="submit" name="after_save" value="view" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-6 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                <i class="bi bi-check-lg"></i> {{ $mode === 'create' ? 'Save' : 'Save Changes' }}
            </button>
        </div>
    </div>
</div>

@push('styles')
{{-- Standard, premium Arabic typography for every RTL field on the HR form --}}
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;600;700&family=Noto+Naskh+Arabic:wght@500;600;700&display=swap" rel="stylesheet">
<style>
    .ar-input {
        font-family: "Noto Naskh Arabic", "Cairo", "Tajawal", "Segoe UI", Tahoma, Arial, sans-serif;
        font-weight: 700;
        direction: rtl;
        text-align: right;
        font-size: 0.95rem;
    }
    .ar-input::placeholder { font-family: inherit; font-weight: 400; text-align: right; opacity: .55; }
</style>
@endpush

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
        nationality: { 'bangladesh':'بنغلاديش','india':'الهند','pakistan':'باكستان','philippines':'الفلبين','nepal':'نيبال','sri lanka':'سريلانكا' },
        religion:  { 'muslim':'مسلم','non-muslim':'غير مسلم','non muslim':'غير مسلم' },
        sex:       { 'male':'ذكر','female':'أنثى' },
        marital:   { 'married':'متزوج','unmarried':'غير متزوج','single':'غير متزوج' },
        travel:    { 'work':'الشغل','visit':'زيارة','umrah':'العمرة','residence':'إقامة','hajj':'الحج','diplomacy':'الدبلوماسية','transit':'عبور' },
        fingerprint:{ 'yes':'نعم','no':'لا' },
        duration:  { '02 years':'سنتان','2 years':'سنتان','two years':'سنتان','1 year':'سنة واحدة','one year':'سنة واحدة','01 year':'سنة واحدة','3 years':'ثلاث سنوات','03 years':'ثلاث سنوات','6 months':'ستة أشهر','06 months':'ستة أشهر' },
        profession:{ 'domestic worker':'عامل منزلي','housemaid':'عاملة منزلية','house maid':'عاملة منزلية','house driver':'سائق خاص','private driver':'سائق خاص','family driver':'سائق عائلة','driver':'سائق','heavy driver':'سائق ثقيل','heavy vehicle driver':'سائق ثقيل','light driver':'سائق خفيف','cleaner':'عامل نظافة','labour':'عامل','labourer':'عامل','labor':'عامل','worker':'عامل','load and unload worker':'عامل تحميل وتنزيل','security guard':'حارس أمن','guard':'حارس','watchman':'حارس','electrician':'كهربائي','plumber':'سباك','welder':'لحام','mason':'بنّاء','carpenter':'نجار','painter':'دهان','cook':'طباخ','chef':'طباخ','tailor':'خياط','farmer':'مزارع','gardener':'بستاني','shepherd':'راعي غنم','salesman':'بائع','accountant':'محاسب','nurse':'ممرض','technician':'فني','mechanic':'ميكانيكي','helper':'مساعد','waiter':'نادل','barber':'حلاق' },
        qualification:{ 'secondary':'ثانوي','higher secondary':'ثانوية عليا','primary':'ابتدائي','graduate':'خريج','bachelor':'بكالوريوس','diploma':'دبلوم','master':'ماجستير','none':'لا يوجد','illiterate':'أمي','read and write':'يقرأ ويكتب','can read and write':'يقرأ ويكتب' },
        city:      { 'riyadh':'الرياض','jeddah':'جدة','jiddah':'جدة','dammam':'الدمام','makkah':'مكة المكرمة','mecca':'مكة المكرمة','madinah':'المدينة المنورة','medina':'المدينة المنورة','taif':'الطائف','tabuk':'تبوك','abha':'أبها','khobar':'الخبر','al khobar':'الخبر','jubail':'الجبيل','yanbu':'ينبع','hail':'حائل','najran':'نجران','buraidah':'بريدة','qassim':'القصيم','qatif':'القطيف','hofuf':'الهفوف','khamis mushait':'خميس مشيط','dhaka':'دكا' },
        generic:   {},
        address:   {}
    };
    // Dicts whose unmatched values fall back to phonetic transliteration.
    var FREE = { generic: true, address: true };

    // Latin→Arabic phonetic transliteration (best-effort, fully editable result).
    var TR_MULTI = [['sh','ش'],['ch','تش'],['th','ث'],['kh','خ'],['gh','غ'],['ph','ف'],['ck','ك'],['oo','و'],['ou','و'],['ee','ي'],['aa','ا'],['ll','ل']];
    var TR_ONE = {a:'ا',b:'ب',c:'ك',d:'د',e:'ي',f:'ف',g:'ج',h:'ه',i:'ي',j:'ج',k:'ك',l:'ل',m:'م',n:'ن',o:'و',p:'ب',q:'ق',r:'ر',s:'س',t:'ت',u:'و',v:'ف',w:'و',x:'كس',y:'ي',z:'ز'};
    function translitWord(w) {
        var out = '', i = 0, lw = w.toLowerCase();
        while (i < lw.length) {
            var ch = lw[i], two = lw.substr(i, 2), hit = null;
            for (var k = 0; k < TR_MULTI.length; k++) { if (TR_MULTI[k][0] === two) { hit = TR_MULTI[k][1]; break; } }
            if (hit !== null) { out += hit; i += 2; continue; }
            if (/[0-9]/.test(ch)) { out += ch; i++; continue; }
            out += (TR_ONE[ch] || (ch === '-' || ch === '/' ? ch : '')); i++;
        }
        return out;
    }
    function translit(value) {
        var s = (value || '').trim();
        if (!s) return '';
        if (/[؀-ۿ]/.test(s)) return s;           // already Arabic → leave it
        return s.split(/\s+/).map(translitWord).filter(Boolean).join(' ');
    }

    // Gregorian yyyy-mm-dd → Arabic-Indic dd/mm/yyyy (matches embassy form style).
    var AR_DIGITS = '٠١٢٣٤٥٦٧٨٩';
    function arDigits(s) { return s.replace(/[0-9]/g, function (d) { return AR_DIGITS[+d]; }); }
    function toArabicDate(v) {
        var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec((v || '').trim());
        return m ? arDigits(m[3] + '/' + m[2] + '/' + m[1]) : '';
    }

    function translate(dict, value, allowTranslit) {
        var v = (value || '').trim();
        if (!v) return '';
        if (dict === 'date') return toArabicDate(v);
        var hit = DICT[dict] && DICT[dict][v.toLowerCase()];
        if (hit) return hit;
        if (allowTranslit && FREE[dict]) return translit(v);
        return '';
    }

    // Track manual edits so we never overwrite a value the user typed/saved.
    document.querySelectorAll('.ar-target').forEach(function (el) {
        if (el.value.trim() !== '') el.dataset.touched = '1';
        el.addEventListener('input', function () { el.dataset.touched = '1'; });
    });

    function fill(src, opts) {
        var target = document.getElementById(src.dataset.arSource);
        if (!target) return;
        var force = opts && opts.force;
        if (!force && target.dataset.touched === '1' && target.value.trim() !== '') return;
        var ar = translate(src.dataset.arDict || 'generic', src.value, force || (opts && opts.translit));
        if (ar) { target.value = ar; delete target.dataset.touched; }
    }

    document.querySelectorAll('[data-ar-source]').forEach(function (src) {
        // Live: dictionary + date map (no per-keystroke transliteration churn).
        src.addEventListener('input', function () { fill(src, { translit: false }); });
        // On blur / change: also allow phonetic transliteration for free-text fields.
        src.addEventListener('change', function () { fill(src, { translit: true }); });
    });

    // Manual regenerate button (ع) — always (re)generates from the English value.
    document.querySelectorAll('.ar-gen').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var src = document.querySelector('[data-ar-source="' + btn.dataset.target + '"]');
            if (src) fill(src, { force: true });
        });
    });
})();
</script>
<script>
(function () {
    // Trim + collapse inner whitespace on blur (no case-changing — safe for all names).
    document.querySelectorAll('.js-trim').forEach(function (el) {
        el.addEventListener('blur', function () {
            el.value = el.value.replace(/\s+/g, ' ').trim();
        });
    });

    // "Same as present" sync toggle → keep Previous Nationality in step with Present.
    var natSync    = document.getElementById('sameAsPresent');
    var natPresent = document.getElementById('nationality');
    var natPrev    = document.getElementById('previous_nationality');
    if (natSync && natPresent && natPrev) {
        var lockCls = ['bg-slate-100', 'text-slate-500', 'cursor-not-allowed'];
        function applyNatSync() {
            if (natSync.checked) {
                natPrev.value = natPresent.value;
                natPrev.readOnly = true;
                lockCls.forEach(function (c) { natPrev.classList.add(c); });
            } else {
                natPrev.readOnly = false;
                lockCls.forEach(function (c) { natPrev.classList.remove(c); });
            }
        }
        natSync.addEventListener('change', applyNatSync);
        natPresent.addEventListener('input', function () { if (natSync.checked) natPrev.value = natPresent.value; });
        applyNatSync();
    }
})();
</script>
@endpush
