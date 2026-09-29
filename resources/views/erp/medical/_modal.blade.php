{{--
    Add / Edit Medical Entry modal (Alpine `medicalModal`).

    Opened by window events:  medical-add            → empty form
                              medical-edit {id}      → loads JSON from erp.medical.show
    Saves over fetch (JSON). 422 → inline field errors, 409 → duplicate-passport
    confirm inside the footer, 200 → the server flashes the toast + row highlight
    and the list reloads (so table, stats and pagination stay exact).

    Validation mirrors MedicalEntryRequest; the server remains the authority.
    Config reaches Alpine via <script type="application/json"> (no @js in attrs).

    Expects: $statuses, $countries.
--}}
@php
    $modalJson = json_encode([
        'base'      => url('erp/medical'),
        'storeUrl'  => route('erp.medical.store'),
        'countries' => $countries,
        'statuses'  => $statuses,
        'today'     => today()->format('Y-m-d'),
        'openAdd'   => request()->boolean('add'),
        'openEdit'  => (int) request()->query('edit', 0) ?: null,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

    $inp = \App\Support\ErpForm::INPUT;
    $req = '<span class="text-red-500" aria-hidden="true">*</span>';
@endphp
<script type="application/json" id="medical-modal-config">{!! $modalJson !!}</script>

{{-- Field border/aria state keyed by field name (error lines come from x-erp.field). --}}
@php
    $state = fn (string $k) => 'x-bind:class="fieldError(\'' . $k . '\') ? \'' . \App\Support\ErpForm::BORDER_ERROR . '\' : \'' . \App\Support\ErpForm::BORDER_OK . '\'"'
        . ' x-bind:aria-invalid="fieldError(\'' . $k . '\') ? \'true\' : \'false\'" aria-describedby="err-' . $k . '"';
@endphp

<div x-data="medicalModal()"
     x-on:medical-add.window="openAdd()"
     x-on:medical-edit.window="openEdit($event.detail.id)"
     x-on:keydown.escape.window="onEscape()">

    <x-erp.modal kind="overlay" show="open" icon="bi-heart-pulse" title-id="medical-modal-title"
                 title="mode === 'edit' ? 'Edit Medical Entry' : 'Add Medical Entry'"
                 submit="submit()" close="close()" busy="submitting" edit="mode === 'edit'" loading="loadingEntry"
                 x-on:keydown.tab="trapFocus($event)">

        {{-- Section 1 — Personal --}}
        <x-erp.section id="sec-personal" icon="bi-person-vcard" title="Personal Information">
            <x-erp.field label="Full Name" for="md_full_name" required error="fieldError('full_name')" error-id="err-full_name">
                <input id="md_full_name" type="text" maxlength="255" autocomplete="off" x-model="f.full_name" x-on:input="touch('full_name')" x-on:blur="touch('full_name')"
                       class="{{ $inp }}" {!! $state('full_name') !!}>
            </x-erp.field>
            <x-erp.field label="Father's Name" for="md_father_name" required error="fieldError('father_name')" error-id="err-father_name">
                <input id="md_father_name" type="text" maxlength="255" autocomplete="off" x-model="f.father_name" x-on:input="touch('father_name')" x-on:blur="touch('father_name')"
                       class="{{ $inp }}" {!! $state('father_name') !!}>
            </x-erp.field>

            {{-- Passport → cross-module ERP auto-fill (Medical → MOFA → Stamping → BMET, HR fallback) --}}
            <x-erp.field label="Passport No" for="md_passport_no" required error="fieldError('passport_no')" error-id="err-passport_no">
                <div class="relative">
                    <input id="md_passport_no" type="text" maxlength="20" autocomplete="off" spellcheck="false"
                           aria-describedby="err-passport_no md_passport_no-erp"
                           x-model="f.passport_no"
                           x-on:input="touch('passport_no')"
                           x-on:blur="touch('passport_no')"
                           class="{{ $inp }} pr-9 uppercase" {!! $state('passport_no') !!}>
                    <i class="bi bi-search pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                </div>
                @include('erp.partials._erp-autofill', [
                    'module'      => 'medical',
                    'passportKey' => 'passport_no',
                    'inputId'     => 'md_passport_no',
                    'idKey'       => 'editId',
                    'map'         => ['full_name' => 'full_name', 'father_name' => 'father_name', 'date_of_birth' => 'date_of_birth', 'mobile_no' => 'mobile_no'],
                ])
            </x-erp.field>

            <x-erp.field label="Date of Birth" for="md_dob" required error="fieldError('date_of_birth')" error-id="err-date_of_birth">
                <input id="md_dob" type="date" x-model="f.date_of_birth" x-bind:max="yesterday" x-on:change="touch('date_of_birth')" x-on:blur="touch('date_of_birth')"
                       class="{{ $inp }}" {!! $state('date_of_birth') !!}>
            </x-erp.field>
            <x-erp.field label="Age" for="md_age" auto>
                <input id="md_age" type="text" readonly tabindex="-1" x-bind:value="age() === '' ? '' : age()" placeholder="—"
                       class="{{ \App\Support\ErpForm::READONLY }}">
            </x-erp.field>
        </x-erp.section>

        {{-- Section 2 — Medical center & location --}}
        <x-erp.section id="sec-location" icon="bi-hospital" title="Medical Center & Location">
            <x-erp.field label="Medical Center Name" for="md_center" required error="fieldError('medical_center_name')" error-id="err-medical_center_name">
                <input id="md_center" type="text" maxlength="255" x-model="f.medical_center_name" x-on:input="touch('medical_center_name')" x-on:blur="touch('medical_center_name')"
                       class="{{ $inp }}" {!! $state('medical_center_name') !!}>
            </x-erp.field>

            {{-- Country combobox (suggestions + free text) --}}
            <x-erp.field label="Country" for="md_country" required error="fieldError('country')" error-id="err-country"
                         class="relative" x-on:click.outside="country.open = false">
                <div class="relative">
                    <input id="md_country" type="text" maxlength="100" autocomplete="off"
                           role="combobox" aria-autocomplete="list" aria-controls="md-country-list" x-bind:aria-expanded="country.open ? 'true' : 'false'"
                           x-model="f.country"
                           x-on:focus="$el.select(); country.open = true; country.active = -1"
                           x-on:input="touch('country'); country.open = true; country.active = -1"
                           x-on:blur="touch('country')"
                           x-on:keydown.arrow-down.prevent="moveCountry(1)" x-on:keydown.arrow-up.prevent="moveCountry(-1)"
                           x-on:keydown.enter="if (country.open && country.active >= 0) { $event.preventDefault(); pickCountry(countryOptions()[country.active]) }"
                           class="{{ $inp }} pr-9" {!! $state('country') !!}>
                    <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                </div>
                <ul id="md-country-list" role="listbox" aria-label="Countries" x-show="country.open && countryOptions().length" x-cloak x-transition.opacity.duration.150ms
                    class="absolute left-0 right-0 z-20 mt-1 max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-xl">
                    <template x-for="(c, i) in countryOptions()" :key="c">
                        <li role="option" x-bind:aria-selected="f.country === c ? 'true' : 'false'"
                            x-on:mousedown.prevent="pickCountry(c)" x-on:mouseenter="country.active = i"
                            class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm text-slate-700"
                            x-bind:class="country.active === i ? 'bg-brand-50' : ''">
                            <span x-text="c"></span>
                            <i class="bi bi-check2 text-brand-600" x-show="f.country === c" aria-hidden="true"></i>
                        </li>
                    </template>
                </ul>
            </x-erp.field>

            <x-erp.field label="Code No" for="md_code" error="fieldError('medical_code')" error-id="err-medical_code">
                <input id="md_code" type="text" maxlength="100" x-model="f.medical_code" class="{{ $inp }}" {!! $state('medical_code') !!}>
            </x-erp.field>
            <x-erp.field label="Mobile No" for="md_mobile" error="fieldError('mobile_no')" error-id="err-mobile_no">
                <input id="md_mobile" type="tel" maxlength="30" inputmode="tel" x-model="f.mobile_no" x-on:input="touch('mobile_no')" x-on:blur="touch('mobile_no')" placeholder="01XXXXXXXXX"
                       class="{{ $inp }}" {!! $state('mobile_no') !!}>
            </x-erp.field>
        </x-erp.section>

        {{-- Section 3 — Dates & status --}}
        <x-erp.section id="sec-dates" icon="bi-calendar2-check" title="Dates & Status">
            <x-erp.field label="Medical Issue Date" for="md_issue" required error="fieldError('medical_issue_date')" error-id="err-medical_issue_date">
                <input id="md_issue" type="date" x-model="f.medical_issue_date" x-on:change="touch('medical_issue_date')" x-on:blur="touch('medical_issue_date')"
                       class="{{ $inp }}" {!! $state('medical_issue_date') !!}>
            </x-erp.field>
            <x-erp.field label="Medical Expiry Date" for="md_expire" required error="fieldError('medical_expire_date')" error-id="err-medical_expire_date">
                <input id="md_expire" type="date" x-model="f.medical_expire_date" x-bind:min="f.medical_issue_date || null" x-on:change="touch('medical_expire_date')" x-on:blur="touch('medical_expire_date')"
                       class="{{ $inp }}" {!! $state('medical_expire_date') !!}>
            </x-erp.field>
            <x-erp.field label="Medical Status" for="md_status" required error="fieldError('medical_status')" error-id="err-medical_status">
                <select id="md_status" x-model="f.medical_status" class="{{ $inp }}" {!! $state('medical_status') !!}>
                    @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
        </x-erp.section>

        {{-- Section 4 — Additional --}}
        <x-erp.section id="sec-extra" icon="bi-journal-text" title="Additional Info" cols="2">
            <x-erp.field label="Reference" for="md_reference" error="fieldError('reference')" error-id="err-reference">
                <x-erp.select-search id="md_reference" :agents="$agentOptions ?? []" x-model="f.reference" error="fieldError('reference')" />
            </x-erp.field>
            <x-erp.textarea label="Remarks" id="md_remarks" maxlength="1000" x-model="f.remarks"
                            error="fieldError('remarks')" error-id="err-remarks" />
        </x-erp.section>

        {{-- Duplicate passport confirm (above the footer buttons) --}}
        <x-slot:footer>
            <div x-show="duplicate" x-cloak x-transition class="mb-3 flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 sm:flex-row sm:items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-lg text-amber-500" aria-hidden="true"></i>
                <div class="flex-1"><strong>Possible duplicate.</strong> <span x-text="duplicate"></span></div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" x-on:click="duplicate = null" class="rounded-md px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">Review</button>
                    <button type="button" x-on:click="submit(true)" class="rounded-md bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Yes, add anyway</button>
                </div>
            </div>
        </x-slot:footer>
    </x-erp.modal>
</div>

@push('scripts')
<script>
    function medicalModal() {
        const cfg = JSON.parse(document.getElementById('medical-modal-config').textContent);
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const REQUIRED = {
            full_name: 'Full name is required',
            father_name: "Father's name is required",
            passport_no: 'Passport number is required',
            date_of_birth: 'Date of birth is required',
            medical_center_name: 'Medical center name is required',
            country: 'Country is required',
            medical_issue_date: 'Medical issue date is required',
            medical_expire_date: 'Medical expiry date is required',
        };
        const TONES = {
            pending: 'bg-amber-100 text-amber-800', process: 'bg-blue-100 text-blue-800',
            under_review: 'bg-purple-100 text-purple-800', fit: 'bg-emerald-100 text-emerald-800',
            unfit: 'bg-red-100 text-red-800', expired: 'bg-gray-100 text-gray-700',
        };
        const ICONS = {
            pending: 'bi-hourglass-split', process: 'bi-arrow-repeat', under_review: 'bi-search',
            fit: 'bi-check-circle-fill', unfit: 'bi-x-circle-fill', expired: 'bi-calendar-x',
        };
        const blank = () => ({
            full_name: '', father_name: '', passport_no: '', date_of_birth: '',
            medical_center_name: '', country: 'Saudi Arabia', medical_code: '', mobile_no: '',
            medical_issue_date: '', medical_expire_date: '', medical_status: 'pending',
            reference: '', remarks: '',
        });
        const yesterday = (() => { const d = new Date(cfg.today + 'T00:00:00'); d.setDate(d.getDate() - 1); return d.toISOString().slice(0, 10); })();

        return {
            cfg, yesterday,
            open: false, mode: 'add', editId: null, loadingEntry: false, submitting: false,
            f: blank(), touched: {}, server: {}, serverSnapshot: {}, duplicate: null, returnFocus: null,
            country: { open: false, active: -1 },

            init() {
                // Changing a field clears its server-side error.
                this.$watch('f', () => {
                    for (const k in this.server) if (this.f[k] !== this.serverSnapshot[k]) delete this.server[k];
                }, { deep: true });
                if (cfg.openAdd) this.$nextTick(() => this.openAdd());
                else if (cfg.openEdit) this.$nextTick(() => this.openEdit(cfg.openEdit));
            },

            // ── open / close ───────────────────────────────────────────────
            reset(data) {
                this.f = Object.assign(blank(), data || {});
                for (const k in this.f) if (this.f[k] === null) this.f[k] = '';
                this.touched = {}; this.server = {}; this.duplicate = null;
                this.country = { open: false, active: -1 };
            },
            show() {
                this.returnFocus = document.activeElement;
                this.open = true;
                document.body.style.overflow = 'hidden';
            },
            openAdd() {
                this.mode = 'add'; this.editId = null; this.reset();
                this.show();
                this.$nextTick(() => setTimeout(() => document.getElementById('md_full_name')?.focus(), 60));
            },
            openEdit(id) {
                this.mode = 'edit'; this.editId = id; this.reset();
                this.loadingEntry = true;
                this.show();
                fetch(cfg.base + '/' + id, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => { if (!r.ok) throw r; return r.json(); })
                    .then(data => {
                        this.reset(data);
                        this.loadingEntry = false;
                        this.$nextTick(() => document.getElementById('md_full_name')?.focus());
                    })
                    .catch(() => {
                        this.close(true);
                        this.toast('error', 'Could not load this medical entry.');
                    });
            },
            close(force = false) {
                if (this.submitting && !force) return;
                this.open = false;
                this.loadingEntry = false;
                document.body.style.overflow = '';
                this.clearDeepLink();
                this.$nextTick(() => this.returnFocus?.focus?.());
            },
            onEscape() {
                if (!this.open) return;
                if (this.country.open) { this.country.open = false; return; }
                this.close();
            },
            clearDeepLink() {
                const url = new URL(window.location.href);
                if (url.searchParams.has('add') || url.searchParams.has('edit')) {
                    url.searchParams.delete('add'); url.searchParams.delete('edit');
                    history.replaceState(null, '', url);
                }
            },
            trapFocus(e) {
                const nodes = [...this.$refs.panel.querySelectorAll('a[href], button:not([disabled]), input:not([readonly]):not([disabled]), select, textarea')]
                    .filter(el => el.offsetParent !== null);
                if (!nodes.length) return;
                const first = nodes[0], last = nodes[nodes.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            },

            // ── age / status ───────────────────────────────────────────────
            age() {
                const y = parseInt((this.f.date_of_birth || '').slice(0, 4), 10);
                return Number.isFinite(y) ? Math.max(0, new Date().getFullYear() - y) : '';
            },
            statusTone() { return TONES[this.f.medical_status] || 'bg-gray-100 text-gray-700'; },
            statusIcon() { return ICONS[this.f.medical_status] || 'bi-circle'; },

            // ── validation (mirrors MedicalEntryRequest) ───────────────────
            clientErrors() {
                const f = this.f, e = {};
                for (const [k, msg] of Object.entries(REQUIRED)) if (String(f[k] ?? '').trim() === '') e[k] = msg;
                if (!e.passport_no && !/^[A-Za-z0-9]{5,20}$/.test(f.passport_no.trim())) e.passport_no = 'Passport number is invalid (5–20 letters/digits, no spaces)';
                if (!e.date_of_birth && f.date_of_birth >= cfg.today) e.date_of_birth = 'Date of birth must be before today';
                if (!e.medical_expire_date && !e.medical_issue_date && f.medical_expire_date <= f.medical_issue_date) e.medical_expire_date = 'Expiry date must be after issue date';
                if (f.mobile_no && !/^[0-9+\-\s()]+$/.test(f.mobile_no)) e.mobile_no = 'Mobile number may contain only digits, spaces, +, - and brackets';
                return e;
            },
            fieldError(k) {
                const client = this.touched[k] ? this.clientErrors()[k] : null;
                return client || (this.server[k] ? this.server[k][0] : '');
            },
            isOk(k) { return !!this.touched[k] && !this.fieldError(k); },
            isValid() { return Object.keys(this.clientErrors()).length === 0; },
            missingText() {
                const n = Object.keys(this.clientErrors()).length;
                return n === 1 ? '1 field needs attention before saving' : n + ' fields need attention before saving';
            },
            touch(k) { this.touched[k] = true; },

            // ── Country combobox ───────────────────────────────────────────
            countryOptions() {
                const q = (this.f.country || '').trim().toLowerCase();
                if (!q || cfg.countries.some(c => c.toLowerCase() === q)) return cfg.countries;
                return cfg.countries.filter(c => c.toLowerCase().includes(q));
            },
            moveCountry(step) {
                const opts = this.countryOptions();
                if (!opts.length) return;
                this.country.open = true;
                this.country.active = (this.country.active + step + opts.length) % opts.length;
            },
            pickCountry(c) {
                if (!c) return;
                this.f.country = c; this.touch('country'); this.country.open = false;
            },

            // ── submit ─────────────────────────────────────────────────────
            submit(confirmDuplicate = false) {
                Object.keys(REQUIRED).forEach(k => this.touch(k));
                if (this.f.mobile_no) this.touch('mobile_no');
                if (!this.isValid() || this.submitting) {
                    const first = Object.keys(this.clientErrors())[0];
                    if (first) this.$refs.panel.querySelector('[x-model="f.' + first + '"]')?.focus();
                    return;
                }
                this.submitting = true; this.server = {};

                const editing = this.mode === 'edit';
                const payload = Object.assign({}, this.f, confirmDuplicate ? { confirm_duplicate: 1 } : {});

                fetch(editing ? cfg.base + '/' + this.editId : cfg.storeUrl, {
                    method: editing ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                })
                    .then(async r => ({ status: r.status, data: await r.json().catch(() => ({})) }))
                    .then(({ status, data }) => {
                        if (status === 200 && data.ok) {
                            this.duplicate = null;
                            this.close(true);
                            // Reload so the table, stats and pagination are exact; the
                            // server flashed the toast + row highlight for this load.
                            const url = new URL(window.location.href);
                            url.searchParams.delete('add'); url.searchParams.delete('edit');
                            setTimeout(() => window.location.replace(url.toString()), 160);
                            return;
                        }
                        this.submitting = false;
                        if (status === 409 && data.duplicate) { this.duplicate = data.message; return; }
                        if (status === 422 && data.errors) {
                            this.serverSnapshot = Object.assign({}, this.f);
                            this.server = data.errors;
                            this.toast('error', 'Please fix the highlighted fields.');
                            return;
                        }
                        if (status === 419) { this.toast('error', 'Your session expired — reload the page and try again.'); return; }
                        this.toast('error', data.message || 'Error saving entry');
                    })
                    .catch(() => {
                        this.submitting = false;
                        this.toast('error', 'Error saving entry — check your connection and try again.');
                    });
            },

            toast(type, message) {
                window.dispatchEvent(new CustomEvent('medical-toast', { detail: { type, message } }));
            },
        };
    }
</script>
@endpush
