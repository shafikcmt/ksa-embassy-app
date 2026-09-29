{{--
    Shared Add / Edit Medical Entry form.

    Expects: $entry (App\Models\Medical), $statuses, $action (form URL),
             $method ('POST'|'PUT'), $cancelUrl.

    Alpine (medicalForm): live Age from D.O.B (current year − birth year, same
    formula as Medical::ageFromDob — the server recomputes on save), passport →
    HR Profile / previous-entry suggestion popup, and real-time validation that
    mirrors MedicalEntryRequest (the server stays the authority). Initial data
    reaches Alpine via <script type="application/json">, not an attribute.
--}}
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $err = 'mt-1 text-xs font-medium text-rose-600';
    $req = '<span class="text-rose-500">*</span>';

    $val = fn (string $field, $fallback = null) => old($field, $fallback ?? $entry->{$field});
    $date = fn (string $field) => old($field, $entry->{$field}?->format('Y-m-d'));

    $initial = [
        'full_name'           => $val('full_name'),
        'father_name'         => $val('father_name'),
        'passport_no'         => $val('passport_no'),
        'date_of_birth'       => $date('date_of_birth'),
        'medical_center_name' => $val('medical_center_name'),
        'country'             => $val('country'),
        'medical_code'        => $val('medical_code'),
        'mobile_no'           => $val('mobile_no'),
        'medical_issue_date'  => $date('medical_issue_date'),
        'medical_expire_date' => $date('medical_expire_date'),
        'medical_status'      => $val('medical_status') ?: 'pending',
        'reference'           => $val('reference'),
        'remarks'             => $val('remarks'),
    ];
    $initial = array_map(fn ($v) => $v ?? '', $initial);

    $formJson = json_encode(
        ['initial' => $initial, 'lookupUrl' => route('erp.medical.lookup'), 'today' => today()->format('Y-m-d')],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
@endphp
<script type="application/json" id="medical-form-data">{!! $formJson !!}</script>

<form method="POST" action="{{ $action }}" novalidate x-data="medicalForm()" x-on:submit="onSubmit($event)"
      class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="font-semibold"><i class="bi bi-exclamation-circle"></i> Please fix the highlighted fields.</div>
        </div>
    @endif

    {{-- Row 1 — identity --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="{{ $lbl }}" for="m_full_name">Full Name {!! $req !!}</label>
            <input id="m_full_name" type="text" name="full_name" maxlength="255" x-model="f.full_name" x-on:blur="touch('full_name')"
                   class="{{ $inp }}" x-bind:class="bad('full_name') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('full_name')" x-text="errs.full_name" x-cloak></p>
            @error('full_name')<p class="{{ $err }}" x-show="!touched.full_name">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_father_name">Father's Name {!! $req !!}</label>
            <input id="m_father_name" type="text" name="father_name" maxlength="255" x-model="f.father_name" x-on:blur="touch('father_name')"
                   class="{{ $inp }}" x-bind:class="bad('father_name') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('father_name')" x-text="errs.father_name" x-cloak></p>
            @error('father_name')<p class="{{ $err }}" x-show="!touched.father_name">{{ $message }}</p>@enderror
        </div>
        <div class="relative">
            <label class="{{ $lbl }}" for="m_passport_no">Passport No {!! $req !!}</label>
            <div class="relative">
                <input id="m_passport_no" type="text" name="passport_no" maxlength="100" autocomplete="off"
                       x-model="f.passport_no" x-on:input="queueLookup()" x-on:blur="touch('passport_no'); lookup()"
                       placeholder="Type to search HR profiles"
                       class="{{ $inp }} pr-9 uppercase" x-bind:class="bad('passport_no') && 'border-rose-400'">
                <i class="bi pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                   x-bind:class="looking ? 'bi-arrow-repeat animate-spin' : 'bi-search'"></i>
            </div>
            <p class="{{ $err }}" x-show="bad('passport_no')" x-text="errs.passport_no" x-cloak></p>
            @error('passport_no')<p class="{{ $err }}" x-show="!touched.passport_no">{{ $message }}</p>@enderror

            {{-- Suggestion popup --}}
            <div x-show="suggestion" x-cloak x-transition.opacity x-on:click.outside="suggestion = null"
                 class="absolute left-0 right-0 z-30 mt-2 rounded-xl border border-emerald-200 bg-white p-4 shadow-lg">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        <i class="bi bi-person-check"></i> <span x-text="'Found in ' + (suggestion?.source ?? '')"></span>
                    </span>
                    <button type="button" x-on:click="suggestion = null" class="text-slate-400 hover:text-slate-600" title="Dismiss"><i class="bi bi-x-lg text-xs"></i></button>
                </div>
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Name</dt><dd class="text-right font-medium text-slate-800" x-text="suggestion?.full_name || '—'"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Father</dt><dd class="text-right font-medium text-slate-800" x-text="suggestion?.father_name || '—'"></dd></div>
                    <div class="flex justify-between gap-3" x-show="suggestion?.date_of_birth"><dt class="text-slate-500">D.O.B</dt><dd class="text-right font-medium text-slate-800" x-text="suggestion?.date_of_birth"></dd></div>
                    <div class="flex justify-between gap-3" x-show="suggestion?.mobile_no"><dt class="text-slate-500">Mobile</dt><dd class="text-right font-medium text-slate-800" x-text="suggestion?.mobile_no"></dd></div>
                </dl>
                <div class="mt-3 flex justify-end gap-2">
                    <button type="button" x-on:click="suggestion = null" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700">Ignore</button>
                    <button type="button" x-on:click="applySuggestion()" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"><i class="bi bi-magic"></i> Use these details</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2 — D.O.B / Age / Medical Center --}}
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="{{ $lbl }}" for="m_dob">Date of Birth {!! $req !!}</label>
            <input id="m_dob" type="date" name="date_of_birth" x-model="f.date_of_birth" x-on:change="touch('date_of_birth')" x-on:blur="touch('date_of_birth')"
                   max="{{ today()->subDay()->format('Y-m-d') }}"
                   class="{{ $inp }}" x-bind:class="bad('date_of_birth') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('date_of_birth')" x-text="errs.date_of_birth" x-cloak></p>
            @error('date_of_birth')<p class="{{ $err }}" x-show="!touched.date_of_birth">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_age">Age <span class="font-normal text-slate-400">(auto)</span></label>
            <input id="m_age" type="number" readonly tabindex="-1" x-bind:value="calculateAge()" placeholder="—"
                   class="{{ $inp }} cursor-not-allowed bg-slate-50 font-semibold text-slate-700">
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_center">Medical Center Name {!! $req !!}</label>
            <input id="m_center" type="text" name="medical_center_name" maxlength="255" x-model="f.medical_center_name" x-on:blur="touch('medical_center_name')"
                   class="{{ $inp }}" x-bind:class="bad('medical_center_name') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('medical_center_name')" x-text="errs.medical_center_name" x-cloak></p>
            @error('medical_center_name')<p class="{{ $err }}" x-show="!touched.medical_center_name">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Row 3 — Country / Code / Mobile --}}
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="{{ $lbl }}" for="m_country">Country {!! $req !!}</label>
            <input id="m_country" type="text" name="country" maxlength="100" x-model="f.country" x-on:blur="touch('country')"
                   class="{{ $inp }}" x-bind:class="bad('country') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('country')" x-text="errs.country" x-cloak></p>
            @error('country')<p class="{{ $err }}" x-show="!touched.country">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_code">Code No</label>
            <input id="m_code" type="text" name="medical_code" maxlength="100" x-model="f.medical_code" class="{{ $inp }}">
            @error('medical_code')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_mobile">Mobile No</label>
            <input id="m_mobile" type="tel" name="mobile_no" maxlength="30" x-model="f.mobile_no" x-on:blur="touch('mobile_no')" placeholder="01XXXXXXXXX"
                   class="{{ $inp }}" x-bind:class="bad('mobile_no') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('mobile_no')" x-text="errs.mobile_no" x-cloak></p>
            @error('mobile_no')<p class="{{ $err }}" x-show="!touched.mobile_no">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Row 4 — Issue / Expiry / Status --}}
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="{{ $lbl }}" for="m_issue">Medical Issue Date (M. Issu. D.) {!! $req !!}</label>
            <input id="m_issue" type="date" name="medical_issue_date" x-model="f.medical_issue_date" x-on:change="touch('medical_issue_date')" x-on:blur="touch('medical_issue_date')"
                   class="{{ $inp }}" x-bind:class="bad('medical_issue_date') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('medical_issue_date')" x-text="errs.medical_issue_date" x-cloak></p>
            @error('medical_issue_date')<p class="{{ $err }}" x-show="!touched.medical_issue_date">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_expire">Medical Expiry Date (M. E. D.) {!! $req !!}</label>
            <input id="m_expire" type="date" name="medical_expire_date" x-model="f.medical_expire_date" x-on:change="touch('medical_expire_date')" x-on:blur="touch('medical_expire_date')"
                   x-bind:min="f.medical_issue_date || null"
                   class="{{ $inp }}" x-bind:class="bad('medical_expire_date') && 'border-rose-400'">
            <p class="{{ $err }}" x-show="bad('medical_expire_date')" x-text="errs.medical_expire_date" x-cloak></p>
            @error('medical_expire_date')<p class="{{ $err }}" x-show="!touched.medical_expire_date">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_status">Medical Status (Me.St.) {!! $req !!}</label>
            <select id="m_status" name="medical_status" x-model="f.medical_status" class="{{ $inp }}">
                @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
            @error('medical_status')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Row 5 — Reference / Remarks --}}
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label class="{{ $lbl }}" for="m_reference">Reference</label>
            <input id="m_reference" type="text" name="reference" maxlength="255" x-model="f.reference" placeholder="Doctor / reference name" class="{{ $inp }}">
            @error('reference')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="{{ $lbl }}" for="m_remarks">Remarks</label>
            <textarea id="m_remarks" name="remarks" rows="3" maxlength="1000" x-model="f.remarks" class="{{ $inp }}"></textarea>
            @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ $cancelUrl }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"><i class="bi bi-x-lg"></i> Cancel</a>
        <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"><i class="bi bi-check-lg"></i> Save</button>
    </div>

    {{-- Duplicate-passport confirm — auto-opens when the controller flashes
         'duplicate_warning'. Lives INSIDE the form so "Yes, Add Anyway"
         resubmits it with confirm_duplicate=1. --}}
    @if(session('duplicate_warning'))
        <div x-data="{ open: true }">
            <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display:none">
                <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/50" x-on:click="open = false"></div>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-600"><i class="bi bi-exclamation-triangle text-lg"></i></span>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-slate-900">Possible Duplicate Entry</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ session('duplicate_warning') }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 space-y-1.5 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Full Name</dt><dd class="font-medium text-slate-700">{{ old('full_name') ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Passport Number</dt><dd class="font-medium text-slate-700">{{ old('passport_no') ?: '—' }}</dd></div>
                    </dl>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" x-on:click="open = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel / Edit Entry</button>
                        <button type="submit" name="confirm_duplicate" value="1" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"><i class="bi bi-check-lg"></i> Yes, Add Anyway</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</form>

@push('scripts')
<script>
    function medicalForm() {
        const cfg = JSON.parse(document.getElementById('medical-form-data').textContent);
        const REQUIRED = {
            full_name: 'Full name is required.',
            father_name: "Father's name is required.",
            passport_no: 'Passport number is required.',
            date_of_birth: 'Date of birth is required.',
            medical_center_name: 'Medical center name is required.',
            country: 'Country is required.',
            medical_issue_date: 'Medical issue date is required.',
            medical_expire_date: 'Medical expiry date is required.',
        };

        return {
            f: cfg.initial,
            touched: {},
            errs: {},
            submitted: false,
            suggestion: null,
            looking: false,
            lastLookup: (cfg.initial.passport_no || '').trim().toUpperCase(),
            timer: null,

            init() {
                this.$watch('f', () => this.validate(), { deep: true });
                this.validate();
            },

            /** Reference-sheet age: current year − birth year (server recomputes on save). */
            calculateAge() {
                const y = parseInt((this.f.date_of_birth || '').slice(0, 4), 10);
                return Number.isFinite(y) ? Math.max(0, new Date().getFullYear() - y) : '';
            },

            validate() {
                const e = {};
                for (const [k, msg] of Object.entries(REQUIRED)) {
                    if (String(this.f[k] ?? '').trim() === '') e[k] = msg;
                }
                if (!e.date_of_birth && this.f.date_of_birth >= cfg.today) e.date_of_birth = 'Date of birth must be before today.';
                if (!e.medical_expire_date && !e.medical_issue_date && this.f.medical_expire_date <= this.f.medical_issue_date) {
                    e.medical_expire_date = 'Medical expiry date must be after the issue date.';
                }
                if (this.f.mobile_no && !/^[0-9+\-\s()]+$/.test(this.f.mobile_no)) {
                    e.mobile_no = 'Mobile number may contain only digits, spaces, +, - and brackets.';
                }
                this.errs = e;
            },

            touch(field) { this.touched[field] = true; },
            bad(field) { return (this.touched[field] || this.submitted) && !!this.errs[field]; },

            onSubmit(event) {
                this.submitted = true;
                this.validate();
                const first = Object.keys(this.errs)[0];
                if (first) {
                    event.preventDefault();
                    this.$el.querySelector('[name="' + first + '"]')?.focus();
                }
            },

            queueLookup() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.lookup(), 500);
            },

            lookup() {
                clearTimeout(this.timer);
                const passport = (this.f.passport_no || '').trim().toUpperCase();
                if (passport.length < 5 || passport === this.lastLookup) return;
                this.lastLookup = passport;
                this.looking = true;

                fetch(cfg.lookupUrl + '?passport_no=' + encodeURIComponent(passport), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => r.ok ? r.json() : { found: false })
                    .then(data => {
                        // Ignore stale answers if the user kept typing.
                        if (passport !== (this.f.passport_no || '').trim().toUpperCase()) return;
                        this.suggestion = data && data.found ? data : null;
                    })
                    .catch(() => { this.suggestion = null; })
                    .finally(() => { this.looking = false; });
            },

            applySuggestion() {
                const s = this.suggestion;
                if (!s) return;
                if (s.full_name) this.f.full_name = s.full_name;
                if (s.father_name) this.f.father_name = s.father_name;
                if (s.date_of_birth && !this.f.date_of_birth) this.f.date_of_birth = s.date_of_birth;
                if (s.mobile_no && !this.f.mobile_no) this.f.mobile_no = s.mobile_no;
                this.suggestion = null;
            },
        };
    }
</script>
@endpush
