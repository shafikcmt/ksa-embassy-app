{{--
    Shared HR / Candidate wizard — used by both create and edit.
    Expects: $agents (Collection), optionally $hr (HrProfile|null), and $mode ('create'|'edit').
    All input name="" attributes mirror the controller exactly — this is a UI re-skin only.
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

    // Shared Tailwind input styles
    $inp = 'h-11 w-full rounded-xl border-slate-300 text-sm shadow-sm transition focus:border-brand-400 focus:ring-brand-400';
    $ta  = 'w-full rounded-xl border-slate-300 text-sm shadow-sm transition focus:border-brand-400 focus:ring-brand-400';

    // Common nationalities for the searchable <datalist> (free text still allowed).
    $nationalityOptions = ['BANGLADESH','INDIA','PAKISTAN','NEPAL','SRI LANKA','PHILIPPINES','INDONESIA','MYANMAR','KENYA','UGANDA','ETHIOPIA','NIGERIA'];

    // Which step holds the first server-side error → open there.
    $stepFields = [
        1 => ['full_name_en','father_name','mother_name','date_of_birth','mofa_new','mofa_old','place_of_birth','previous_nationality','nationality','gender','marital_status','sect','religion','home_address'],
        2 => ['passport_issue_place','passport_number','passport_issue_date','passport_validity_years','passport_expiry_date'],
        3 => ['visa_number','visa_issue_date','sponsor_name','sponsor_name_ar','sponsor_id','visa_issue_place','visa_issue_place_ar','qualification_en','qualification_ar','profession_en','profession_ar','travel_purpose','musaned_no','wakala_no'],
        4 => ['pc_qr_code','police_clearance_number','license_type'],
        5 => ['duration_stay_en','duration_stay_ar','arrival_date','arrival_date_ar','departure_date','departure_date_ar','fingerprint','agent_id','status'],
        6 => ['contract_period','salary','work_city','employer_name','employer_phone','medical_date','medical_center','medical_fit','clearance_issue_date','clearance_expiry_date','clearance_country','file_number','phone','email','business_address_en','business_address_ar','kingdom_address_en','kingdom_address_ar','remarks'],
    ];
    $initialStep = 1;
    foreach ($stepFields as $s => $fs) { if ($errors->hasAny($fs)) { $initialStep = $s; break; } }

    $steps = [
        1 => ['Personal', 'bi-person-vcard'],
        2 => ['Passport', 'bi-passport'],
        3 => ['Visa & Sponsor', 'bi-globe2'],
        4 => ['Clearance', 'bi-shield-check'],
        5 => ['Others', 'bi-info-circle'],
        6 => ['Employment', 'bi-briefcase'],
        7 => ['Review', 'bi-check2-circle'],
    ];
@endphp

<div data-step-fields
     x-data='{
        step: {{ $initialStep }},
        total: 7,
        next() { if (this.check()) { this.step++; this.top(); } },
        prev() { if (this.step > 1) { this.step--; this.top(); } },
        goto(n) { if (n <= this.step) { this.step = n; this.top(); } },
        check() {
            let panel = this.$root.querySelector("[data-step=\"" + this.step + "\"]");
            if (!panel) return true;
            for (const f of panel.querySelectorAll("input, select, textarea")) {
                if (!f.checkValidity()) { f.reportValidity(); return false; }
            }
            return true;
        },
        val(n) { let e = this.$root.querySelector("[name=\"" + n + "\"]"); return e && e.value ? e.value : "—"; },
        top() { this.$root.scrollIntoView({ behavior: "smooth", block: "start" }); }
     }'>

    @if($errors->any())
        <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <i class="bi bi-exclamation-circle-fill mt-0.5 text-rose-500"></i>
            <span>Please review the highlighted fields. The step with errors has been opened for you.</span>
        </div>
    @endif

    {{-- Arabic full name is no longer entered by users. On edit we preserve any
         existing value so old records aren't wiped; on create it stays null
         (PrintDataMapper already renders it as '' when absent). --}}
    @if($mode === 'edit')
        <input type="hidden" name="full_name_ar" value="{{ old('full_name_ar', $hr?->full_name_ar) }}">
    @endif

    {{-- Searchable nationality suggestions (free text still allowed) --}}
    <datalist id="nationalityList">
        @foreach($nationalityOptions as $optNat)<option value="{{ $optNat }}"></option>@endforeach
    </datalist>

    {{-- ── Progress stepper ─────────────────────────────────── --}}
    <div class="mb-5 overflow-x-auto">
        <ol class="flex min-w-max items-center gap-1">
            @foreach($steps as $n => [$label, $icon])
                <li class="flex items-center">
                    <button type="button" @click="goto({{ $n }})"
                            :class="step === {{ $n }} ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : (step > {{ $n }} ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-slate-200 bg-white text-slate-400')"
                            class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition">
                        <span class="grid h-5 w-5 place-items-center rounded-full text-[0.65rem]"
                              :class="step > {{ $n }} ? 'bg-emerald-500 text-white' : (step === {{ $n }} ? 'bg-white/20' : 'bg-slate-100')">
                            <template x-if="step > {{ $n }}"><i class="bi bi-check-lg"></i></template>
                            <span x-show="step <= {{ $n }}">{{ $n }}</span>
                        </span>
                        <span class="hidden sm:inline">{{ $label }}</span>
                    </button>
                    @if(!$loop->last)<span class="mx-0.5 h-px w-4 bg-slate-200 sm:w-6"></span>@endif
                </li>
            @endforeach
        </ol>
    </div>

    {{-- ════════ STEP 1 · PERSONAL ════════ --}}
    <x-ui.card data-step="1" x-show="step === 1" x-cloak class="p-5 sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-lg text-brand-600"><i class="bi bi-person-vcard"></i></span>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Personal Information</h2>
                    <p class="text-xs text-slate-400">Basic identity details — exactly as they appear in the passport.</p>
                </div>
            </div>
            @if(app()->environment('local'))
                <button type="button" id="quickFillBtn" class="hidden shrink-0 items-center gap-1.5 rounded-lg border border-dashed border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-50 sm:inline-flex">
                    <i class="bi bi-magic"></i> Quick fill (demo)
                </button>
            @endif
        </div>

        @php $gender = $v('gender'); $marital = $v('marital_status'); $religion = $v('religion'); @endphp

        {{-- Identity --}}
        <p class="mb-2 text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Name &amp; Parents</p>
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.field label="Full Name (English)" name="full_name_en" :required="true" hint="As printed in the passport" class="sm:col-span-2">
                <input type="text" id="full_name_en" name="full_name_en" required value="{{ $v('full_name_en') }}" placeholder="e.g. MOHAMMED RAHMAN" autocomplete="off" class="{{ $inp }} js-trim uppercase placeholder:normal-case @error('full_name_en') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="Father's Name" name="father_name">
                <input type="text" name="father_name" value="{{ $v('father_name') }}" placeholder="Optional" class="{{ $inp }} js-trim">
            </x-ui.field>
            <x-ui.field label="Mother's Name" name="mother_name">
                <input type="text" name="mother_name" value="{{ $v('mother_name') }}" placeholder="Optional" class="{{ $inp }} js-trim">
            </x-ui.field>
        </div>

        {{-- Birth & MOFA --}}
        <p class="mb-2 text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Birth &amp; MOFA</p>
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.field label="Date of Birth" name="date_of_birth" :required="true">
                <input type="date" id="date_of_birth" name="date_of_birth" required max="{{ now()->subYears(15)->format('Y-m-d') }}" value="{{ old('date_of_birth', optional($hr?->date_of_birth)->format('Y-m-d')) }}" class="{{ $inp }} @error('date_of_birth') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="Place of Birth" name="place_of_birth" :required="true">
                <input type="text" name="place_of_birth" required value="{{ $v('place_of_birth') }}" placeholder="e.g. DHAKA" class="{{ $inp }} js-trim @error('place_of_birth') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="MOFA Application ID" name="mofa_new" :required="true" hint="New MOFA is required · Old MOFA optional" class="sm:col-span-2">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="mofa_new" required placeholder="New MOFA" value="{{ $v('mofa_new') }}" class="{{ $inp }} @error('mofa_new') !border-rose-400 @enderror">
                    <input type="text" name="mofa_old" placeholder="Old MOFA (optional)" value="{{ $v('mofa_old') }}" class="{{ $inp }}">
                </div>
            </x-ui.field>
        </div>

        {{-- Nationality --}}
        <p class="mb-2 text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Nationality</p>
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.field label="Present Nationality" name="nationality" :required="true" hint="Type to search or enter manually">
                <input type="text" id="nationality" name="nationality" list="nationalityList" required value="{{ $v('nationality', 'BANGLADESH') }}" autocomplete="off" class="{{ $inp }} uppercase @error('nationality') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field name="previous_nationality">
                <div class="mb-1 flex items-center justify-between">
                    <label for="previous_nationality" class="block text-xs font-semibold text-slate-600">Previous Nationality</label>
                    <button type="button" id="copyNatBtn" class="text-[0.7rem] font-semibold text-brand-600 hover:text-brand-700"><i class="bi bi-arrow-left-short"></i>Same as present</button>
                </div>
                <input type="text" id="previous_nationality" name="previous_nationality" list="nationalityList" value="{{ $v('previous_nationality', 'BANGLADESH') }}" autocomplete="off" class="{{ $inp }} uppercase">
            </x-ui.field>
        </div>

        {{-- Personal details --}}
        <p class="mb-2 text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Personal Details</p>
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.field label="Sex" name="gender" :required="true">
                <div class="flex gap-2">
                    @foreach(['male' => ['Male','bi-gender-male'], 'female' => ['Female','bi-gender-female']] as $val => $meta)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="gender" value="{{ $val }}" class="peer sr-only" {{ $gender === $val ? 'checked' : '' }} {{ $loop->first ? 'required' : '' }}>
                            <span class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-hover:border-brand-300"><i class="bi {{ $meta[1] }}"></i>{{ $meta[0] }}</span>
                        </label>
                    @endforeach
                </div>
            </x-ui.field>
            <x-ui.field label="Marital Status" name="marital_status" :required="true">
                <div class="flex gap-2">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="marital_status" value="married" class="peer sr-only" {{ $marital === 'married' ? 'checked' : '' }} required>
                        <span class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-hover:border-brand-300"><i class="bi bi-heart"></i>Married</span>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="marital_status" value="single" class="peer sr-only" {{ in_array($marital, ['single','divorced','widowed'], true) ? 'checked' : '' }}>
                        <span class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-hover:border-brand-300"><i class="bi bi-person"></i>Unmarried</span>
                    </label>
                </div>
            </x-ui.field>
            <x-ui.field label="Religion" name="religion" :required="true">
                <div class="flex gap-2">
                    @foreach(['Muslim' => ['Muslim','bi-moon-stars'], 'Non-muslim' => ['Non-muslim','bi-person']] as $val => $meta)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="religion" value="{{ $val }}" class="peer sr-only" {{ $religion === $val ? 'checked' : '' }} {{ $loop->first ? 'required' : '' }}>
                            <span class="flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 py-2.5 text-center text-sm font-medium text-slate-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-hover:border-brand-300"><i class="bi {{ $meta[1] }}"></i>{{ $meta[0] }}</span>
                        </label>
                    @endforeach
                </div>
            </x-ui.field>
            <x-ui.field label="Sect" name="sect" hint="Optional">
                <input type="text" name="sect" value="{{ $v('sect') }}" placeholder="e.g. Sunni" class="{{ $inp }} js-trim">
            </x-ui.field>
        </div>

        {{-- Contact --}}
        <p class="mb-2 text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Contact</p>
        <div class="grid grid-cols-1 gap-4">
            <x-ui.field label="Home Address &amp; Phone" name="home_address">
                <textarea name="home_address" rows="2" placeholder="Village / city, district — and a contact phone number" class="{{ $ta }}">{{ $v('home_address') }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.card>

    {{-- ════════ STEP 2 · PASSPORT ════════ --}}
    <x-ui.card data-step="2" x-show="step === 2" x-cloak class="p-5 sm:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-passport text-brand-600"></i> Passport Information</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.field label="Passport No" name="passport_number" :required="true">
                <input type="text" name="passport_number" required value="{{ $rel($passport, 'passport_number') }}" class="{{ $inp }} @error('passport_number') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="Passport Issue Place" name="passport_issue_place">
                <input type="text" name="passport_issue_place" value="{{ $rel($passport, 'issue_place', 'DHAKA') }}" class="{{ $inp }}">
            </x-ui.field>
            <x-ui.field label="Passport Issue Date" name="passport_issue_date" :required="true">
                <input type="date" id="passport_issue_date" name="passport_issue_date" required value="{{ $dt($passport, 'issue_date') }}" class="{{ $inp }} @error('passport_issue_date') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="Passport Validity Type" name="passport_validity_years">
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
            <x-ui.field label="Passport Validity Date" name="passport_expiry_date" :required="true" hint="Auto-filled from issue date + validity; you can edit it." class="sm:col-span-2 sm:max-w-[50%]">
                <input type="date" id="passport_expiry_date" name="passport_expiry_date" required value="{{ $dt($passport, 'expiry_date') }}" class="{{ $inp }} @error('passport_expiry_date') !border-rose-400 @enderror">
            </x-ui.field>
        </div>
    </x-ui.card>

    {{-- ════════ STEP 3 · VISA & SPONSOR ════════ --}}
    <x-ui.card data-step="3" x-show="step === 3" x-cloak class="p-5 sm:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-globe2 text-brand-600"></i> Visa &amp; Sponsor</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="sponsor_name_ar" data-dict="generic" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Sponsor ID" name="sponsor_id" :required="true">
                <input type="text" name="sponsor_id" required value="{{ $rel($visa, 'sponsor_id') }}" class="{{ $inp }} @error('sponsor_id') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="Profession" name="profession_en" :required="true">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="profession_en" required placeholder="English" value="{{ $rel($visa, 'profession_en') }}" data-ar-source="profession_ar" data-ar-dict="profession" class="{{ $inp }} @error('profession_en') !border-rose-400 @enderror">
                    <div class="flex gap-1">
                        <input type="text" id="profession_ar" name="profession_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($visa, 'profession_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="profession_ar" data-dict="profession" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Qualification" name="qualification_en">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="qualification_en" placeholder="English" value="{{ $rel($visa, 'qualification_en') }}" data-ar-source="qualification_ar" data-ar-dict="qualification" class="{{ $inp }}">
                    <div class="flex gap-1">
                        <input type="text" id="qualification_ar" name="qualification_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($visa, 'qualification_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="qualification_ar" data-dict="qualification" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Place of Issue" name="visa_issue_place">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="visa_issue_place" placeholder="English" value="{{ $rel($visa, 'issue_place') }}" data-ar-source="visa_issue_place_ar" data-ar-dict="city" class="{{ $inp }}">
                    <div class="flex gap-1">
                        <input type="text" id="visa_issue_place_ar" name="visa_issue_place_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($visa, 'issue_place_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="visa_issue_place_ar" data-dict="city" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Travel Purpose" name="travel_purpose">
                @php $tp = $rel($visa, 'travel_purpose', 'Work'); $tpOpts = ['Work','Family Visit','Business','Hajj','Umrah','Visit','Other']; @endphp
                <select name="travel_purpose" class="{{ $inp }}">
                    @foreach($tpOpts as $opt)<option value="{{ $opt }}" {{ $tp === $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                    @if($tp && !in_array($tp, $tpOpts, true))<option value="{{ $tp }}" selected>{{ $tp }}</option>@endif
                </select>
            </x-ui.field>
            <x-ui.field label="Wakala No" name="wakala_no" :required="true">
                <input type="text" name="wakala_no" required value="{{ $rel($visa, 'wakala_no') }}" class="{{ $inp }} @error('wakala_no') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="Musaned No" name="musaned_no">
                <input type="text" name="musaned_no" value="{{ $rel($visa, 'musaned_no') }}" class="{{ $inp }}">
            </x-ui.field>
        </div>
    </x-ui.card>

    {{-- ════════ STEP 4 · CLEARANCE & LICENSE ════════ --}}
    <x-ui.card data-step="4" x-show="step === 4" x-cloak class="p-5 sm:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-shield-check text-brand-600"></i> Police Clearance &amp; Driving License</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.field label="P.C QR Code" name="pc_qr_code" class="sm:col-span-2">
                <input type="text" name="pc_qr_code" value="{{ $rel($clearance, 'pc_qr_code') }}" class="{{ $inp }}">
            </x-ui.field>
            <x-ui.field label="P.C Reference No." name="police_clearance_number" :required="true">
                <input type="text" name="police_clearance_number" required value="{{ $rel($clearance, 'police_clearance_number') }}" class="{{ $inp }} @error('police_clearance_number') !border-rose-400 @enderror">
            </x-ui.field>
            <x-ui.field label="License Type" name="license_type">
                @php $lt = $rel($clearance, 'license_type'); $ltOpts = ['Light Vehicle','Heavy Vehicle','Motorcycle','Bus','Equipment','None']; @endphp
                <select name="license_type" class="{{ $inp }}">
                    <option value="">Select a type</option>
                    @foreach($ltOpts as $opt)<option value="{{ $opt }}" {{ $lt === $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                    @if($lt && !in_array($lt, $ltOpts, true))<option value="{{ $lt }}" selected>{{ $lt }}</option>@endif
                </select>
            </x-ui.field>
        </div>
    </x-ui.card>

    {{-- ════════ STEP 5 · OTHERS & STATUS ════════ --}}
    <x-ui.card data-step="5" x-show="step === 5" x-cloak class="p-5 sm:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-info-circle text-brand-600"></i> Others &amp; Status</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.field label="Duration of Stay" name="duration_stay_en">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="duration_stay_en" placeholder="English" value="{{ $rel($other, 'duration_stay_en', '02 Years') }}" data-ar-source="duration_stay_ar" data-ar-dict="duration" class="{{ $inp }}">
                    <div class="flex gap-1">
                        <input type="text" id="duration_stay_ar" name="duration_stay_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'duration_stay_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="duration_stay_ar" data-dict="duration" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Fingerprint" name="fingerprint">
                <input type="text" name="fingerprint" value="{{ $rel($clearance, 'fingerprint', 'Yes') }}" class="{{ $inp }}">
            </x-ui.field>
            <x-ui.field label="Date of Arrival" name="arrival_date" hint="Arabic auto-fills · editable">
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="arrival_date" value="{{ $dt($other, 'arrival_date') }}" data-ar-source="arrival_date_ar" data-ar-dict="date" class="{{ $inp }}">
                    <div class="flex gap-1">
                        <input type="text" id="arrival_date_ar" name="arrival_date_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'arrival_date_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="arrival_date_ar" data-dict="date" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Date of Departure" name="departure_date" hint="Arabic auto-fills · editable">
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="departure_date" value="{{ $dt($other, 'departure_date') }}" data-ar-source="departure_date_ar" data-ar-dict="date" class="{{ $inp }}">
                    <div class="flex gap-1">
                        <input type="text" id="departure_date_ar" name="departure_date_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'departure_date_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="departure_date_ar" data-dict="date" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Agent" name="agent_id">
                <select name="agent_id" class="{{ $inp }}">
                    <option value="">Select an agent</option>
                    @foreach($agents as $agent)<option value="{{ $agent->id }}" {{ (string) $v('agent_id') === (string) $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>@endforeach
                </select>
            </x-ui.field>
            <x-ui.field label="Status" name="status">
                @php $st = $v('status', 'active'); @endphp
                <select name="status" class="{{ $inp }}">
                    @foreach(['active'=>'Active','inactive'=>'Inactive','blacklisted'=>'Blacklisted','listed'=>'Listed'] as $key => $lbl)
                        <option value="{{ $key }}" {{ $st === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>
    </x-ui.card>

    {{-- ════════ STEP 6 · EMPLOYMENT / MEDICAL (optional) ════════ --}}
    <x-ui.card data-step="6" x-show="step === 6" x-cloak class="p-5 sm:p-6">
        <h2 class="mb-1 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-briefcase text-brand-600"></i> Employment / Medical</h2>
        <p class="mb-4 text-xs text-slate-400">Optional — used on the Employment Agreement &amp; Checklist documents.</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.field label="Contract Period" name="contract_period"><input type="text" name="contract_period" placeholder="e.g. 2 years" value="{{ $rel($other, 'contract_period') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Salary (SAR)" name="salary"><input type="number" step="0.01" min="0" name="salary" value="{{ $rel($other, 'salary') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Work City" name="work_city"><input type="text" name="work_city" value="{{ $rel($other, 'work_city') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Employer Name" name="employer_name"><input type="text" name="employer_name" value="{{ $rel($other, 'employer_name') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Employer Phone" name="employer_phone"><input type="text" name="employer_phone" value="{{ $rel($other, 'employer_phone') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Medical Date" name="medical_date"><input type="date" name="medical_date" value="{{ $dt($clearance, 'medical_date') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Medical Center" name="medical_center"><input type="text" name="medical_center" value="{{ $rel($clearance, 'medical_center') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Clearance Issue Date" name="clearance_issue_date"><input type="date" name="clearance_issue_date" value="{{ $dt($clearance, 'clearance_issue_date') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Clearance Expiry Date" name="clearance_expiry_date"><input type="date" name="clearance_expiry_date" value="{{ $dt($clearance, 'clearance_expiry_date') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Clearance Country" name="clearance_country"><input type="text" name="clearance_country" value="{{ $rel($clearance, 'clearance_country') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="File Number" name="file_number" hint="Auto / optional"><input type="text" name="file_number" value="{{ $v('file_number') }}" class="{{ $inp }} @error('file_number') !border-rose-400 @enderror"></x-ui.field>
            <x-ui.field label="Phone" name="phone"><input type="text" name="phone" value="{{ $v('phone') }}" class="{{ $inp }}"></x-ui.field>
            <x-ui.field label="Email" name="email"><input type="email" name="email" value="{{ $v('email') }}" class="{{ $inp }} @error('email') !border-rose-400 @enderror"></x-ui.field>
            <label class="flex items-center gap-2 self-end pb-2 text-sm font-medium text-slate-700 sm:col-span-2">
                <input type="checkbox" name="medical_fit" value="1" {{ old('medical_fit', $clearance?->medical_fit) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Medically Fit
            </label>
            <x-ui.field label="Business Address & Phone" name="business_address_en" hint="Arabic auto-fills · editable" class="sm:col-span-3">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <input type="text" name="business_address_en" placeholder="English" value="{{ $rel($other, 'business_address_en') }}" data-ar-source="business_address_ar" data-ar-dict="address" class="{{ $inp }}">
                    <div class="flex gap-1">
                        <input type="text" id="business_address_ar" name="business_address_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'business_address_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="business_address_ar" data-dict="address" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Name/Address in Kingdom" name="kingdom_address_en" hint="Arabic auto-fills · editable" class="sm:col-span-3">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <input type="text" name="kingdom_address_en" placeholder="English" value="{{ $rel($other, 'kingdom_address_en') }}" data-ar-source="kingdom_address_ar" data-ar-dict="address" class="{{ $inp }}">
                    <div class="flex gap-1">
                        <input type="text" id="kingdom_address_ar" name="kingdom_address_ar" dir="rtl" lang="ar" placeholder="عربي" value="{{ $rel($other, 'kingdom_address_ar') }}" class="{{ $inp }} ar-target ar-input">
                        <button type="button" class="ar-gen h-11 shrink-0 rounded-xl border border-slate-300 px-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50" data-target="kingdom_address_ar" data-dict="address" tabindex="-1" title="Generate Arabic">ع</button>
                    </div>
                </div>
            </x-ui.field>
            <x-ui.field label="Notes / Remarks" name="remarks" class="sm:col-span-3">
                <textarea name="remarks" rows="2" class="{{ $ta }}">{{ $rel($other, 'remarks') }}</textarea>
            </x-ui.field>
        </div>
    </x-ui.card>

    {{-- ════════ STEP 7 · REVIEW & SAVE ════════ --}}
    <x-ui.card data-step="7" x-show="step === 7" x-cloak class="p-5 sm:p-6">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-check2-circle text-brand-600"></i> Review &amp; Save</h2>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                @foreach([
                    'Name' => 'full_name_en', 'Nationality' => 'nationality',
                    'Passport No' => 'passport_number', 'Visa No' => 'visa_number',
                    'Sponsor' => 'sponsor_name', 'Profession' => 'profession_en',
                ] as $lbl => $field)
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200/70 pb-2">
                        <dt class="text-xs font-medium text-slate-400">{{ $lbl }}</dt>
                        <dd class="truncate text-sm font-semibold text-slate-700" x-text="(step, val('{{ $field }}'))"></dd>
                    </div>
                @endforeach
            </dl>
        </div>
        <p class="mt-4 text-sm text-slate-500"><i class="bi bi-info-circle mr-1 text-slate-400"></i>Use <strong>Back</strong> to fix anything, then save using the bar below.</p>
    </x-ui.card>

    {{-- ── Sticky action bar (Back / Next / Save) ────────────── --}}
    <div class="sticky bottom-0 z-20 mt-5 -mx-4 border-t border-slate-200 bg-white/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
        <div class="flex items-center justify-between gap-3">
            <button type="button" @click="prev()" x-show="step > 1" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> <span class="hidden sm:inline">Back</span>
            </button>
            <span x-show="step === 1"></span>

            <div class="flex flex-1 items-center justify-end gap-2">
                <span class="mr-1 hidden text-xs text-slate-400 sm:inline">Step <span x-text="step"></span> of 7</span>

                <button type="button" @click="next()" x-show="step < 7" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                    Next <i class="bi bi-arrow-right"></i>
                </button>

                {{-- Save actions only exist in the DOM on the review step --}}
                <template x-if="step === 7">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        @if($mode === 'create')
                            <button type="submit" name="after_save" value="add_another" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <i class="bi bi-plus-circle"></i> <span class="hidden md:inline">Save &amp;</span> Add Another
                            </button>
                        @endif
                        <button type="submit" name="after_save" value="documents" class="inline-flex h-10 items-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-3 text-sm font-semibold text-brand-700 hover:bg-brand-100">
                            <i class="bi bi-file-earmark-pdf"></i> <span class="hidden md:inline">Save &amp;</span> Documents
                        </button>
                        <button type="submit" name="after_save" value="view" class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                            <i class="bi bi-check-lg"></i> {{ $mode === 'create' ? 'Save Profile' : 'Save Changes' }}
                        </button>
                    </div>
                </template>
            </div>
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

    // "Same as present" → copy present nationality into previous.
    var copyBtn = document.getElementById('copyNatBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var present = document.getElementById('nationality');
            var prev    = document.getElementById('previous_nationality');
            if (present && prev) { prev.value = present.value; prev.dispatchEvent(new Event('input')); }
        });
    }

    // Dev-only quick fill to speed up local testing.
    var qf = document.getElementById('quickFillBtn');
    if (qf) {
        var set = function (name, val) { var e = document.querySelector('[name="' + name + '"]'); if (e && !e.value) e.value = val; };
        var check = function (name, val) { var e = document.querySelector('[name="' + name + '"][value="' + val + '"]'); if (e && !document.querySelector('[name="' + name + '"]:checked')) e.checked = true; };
        qf.addEventListener('click', function () {
            set('full_name_en', 'MOHAMMED RAHMAN'); set('father_name', 'ABDUL RAHMAN'); set('mother_name', 'AMINA BEGUM');
            set('date_of_birth', '1995-06-15'); set('place_of_birth', 'DHAKA'); set('mofa_new', 'MOFA-' + Date.now().toString().slice(-6));
            set('nationality', 'BANGLADESH'); set('previous_nationality', 'BANGLADESH');
            check('gender', 'male'); check('marital_status', 'married'); check('religion', 'Muslim');
            set('passport_number', 'BP' + Date.now().toString().slice(-7)); set('passport_issue_date', '2022-01-01'); set('passport_expiry_date', '2032-01-01');
            set('visa_number', 'V-1001'); set('visa_issue_date', '2025-01-01'); set('sponsor_name', 'AL FUTTAIM CO'); set('sponsor_id', 'SP-7788');
            set('profession_en', 'DRIVER'); set('wakala_no', 'WK-3344'); set('police_clearance_number', 'PCC-9090');
        });
    }
})();
</script>
@endpush
