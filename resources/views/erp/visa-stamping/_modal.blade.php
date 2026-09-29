{{--
    Add / Edit Visa Stamping modal (Alpine `stampingModal`).

    Opened by window events:  stamping-add          → empty form
                              stamping-edit {id}    → loads JSON from erp.visa-stamping.show
    Saves over fetch (JSON): 422 → inline errors, 200 → the server flashes the
    toast + row highlight and the list reloads. Passport → HR-profile dropdown
    (name / father / mother / DOB) and → latest MOFA entry (MOFA no/date, visa,
    ID, issue/expiry). The auto-filled card is read-only until "Edit manually".
    Validation mirrors VisaStampingRequest; the server stays the authority.

    Expects: $statuses, $agents.
--}}
@php
    $cfgJson = json_encode([
        'base'      => url('erp/visa-stamping'),
        'storeUrl'  => route('erp.visa-stamping.store'),
        'agents'    => $agents->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
        'statuses'  => $statuses,
        'today'     => today()->format('Y-m-d'),
        'warnDays'  => \App\Models\VisaStamping::LEFT_DAY_WARNING,
        'openAdd'   => request()->boolean('add'),
        'openEdit'  => (int) request()->query('edit', 0) ?: null,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

    $inp = \App\Support\ErpForm::INPUT;
    // Auto-filled inputs: locked (gray) until "Edit manually"; no bg here so the binding decides.
    $ro  = 'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20';

    // Field border/aria state keyed by field name (error lines come from x-erp.field).
    $state = fn (string $k) => 'x-bind:class="fieldError(\'' . $k . '\') ? \'' . \App\Support\ErpForm::BORDER_ERROR . '\' : \'' . \App\Support\ErpForm::BORDER_OK . '\'"'
        . ' x-bind:aria-invalid="fieldError(\'' . $k . '\') ? \'true\' : \'false\'" aria-describedby="vs-err-' . $k . '"';
    // Auto-filled (read-only until "Edit manually") input state.
    $auto = fn (string $k) => 'x-bind:readonly="!manual" x-bind:tabindex="manual ? 0 : -1"'
        . ' x-bind:class="[manual ? \'bg-white text-slate-800\' : \'cursor-not-allowed bg-slate-100 text-slate-500\', fieldError(\'' . $k . '\') ? \'' . \App\Support\ErpForm::BORDER_ERROR . '\' : \'' . \App\Support\ErpForm::BORDER_OK . '\']"'
        . ' x-bind:aria-invalid="fieldError(\'' . $k . '\') ? \'true\' : \'false\'" aria-describedby="vs-err-' . $k . '"';
@endphp
<script type="application/json" id="stamping-modal-config">{!! $cfgJson !!}</script>

<div x-data="stampingModal()"
     x-on:stamping-add.window="openAdd()"
     x-on:stamping-edit.window="openEdit($event.detail.id)"
     x-on:erp-autofill-result="onErpResult($event.detail)"
     x-on:keydown.escape.window="onEscape()">

    <x-erp.modal kind="overlay" show="open" icon="bi-postage" title-id="vs-modal-title"
                 title="mode === 'edit' ? 'Edit Visa Stamping Entry' : 'Add Visa Stamping Entry'"
                 submit="submit()" close="close()" busy="submitting" edit="mode === 'edit'" loading="loadingEntry"
                 x-on:keydown.tab="trapFocus($event)">

        {{-- Section 1 — Candidate --}}
        <x-erp.section id="vs-sec-main" icon="bi-person-badge" title="Candidate Information">
            <x-erp.field label="Full Name" for="vs_full_name" required error="fieldError('full_name')" error-id="vs-err-full_name">
                <input id="vs_full_name" type="text" maxlength="100" autocomplete="off" x-model="f.full_name" x-on:input="touch('full_name')" x-on:blur="touch('full_name')"
                       class="{{ $inp }}" {!! $state('full_name') !!}>
            </x-erp.field>

            {{-- Passport → cross-module ERP auto-fill (Medical → MOFA → Stamping → BMET, HR fallback) --}}
            <x-erp.field label="Passport Number" for="vs_passport" required error="fieldError('passport_number')" error-id="vs-err-passport_number">
                <div class="relative">
                    <input id="vs_passport" type="text" maxlength="20" autocomplete="off" spellcheck="false"
                           aria-describedby="vs-err-passport_number vs_passport-erp"
                           x-model="f.passport_number"
                           x-on:input="touch('passport_number')"
                           x-on:blur="touch('passport_number')"
                           class="{{ $inp }} pr-9 uppercase" {!! $state('passport_number') !!}>
                    <i class="bi bi-search pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                </div>
                @include('erp.partials._erp-autofill', [
                    'module'      => 'stamping',
                    'passportKey' => 'passport_number',
                    'inputId'     => 'vs_passport',
                    'idKey'       => 'editId',
                    'map'         => [
                        'full_name' => 'full_name', 'father_name' => 'father_name', 'mother_name' => 'mother_name',
                        'date_of_birth' => 'date_of_birth', 'mofa_number' => 'mofa_number', 'mofa_date' => 'mofa_date',
                        'visa_number' => 'visa_number', 'id_number' => 'id_number',
                        'issued_visa_number' => 'issued_visa_number', 'issued_date' => 'issued_date', 'expiry_date' => 'expiry_date',
                        'reference' => 'reference',
                    ],
                ])
            </x-erp.field>

            <x-erp.field label="Visa Number" for="vs_visa" error="fieldError('visa_number')" error-id="vs-err-visa_number">
                <input id="vs_visa" type="text" maxlength="100" x-model="f.visa_number" class="{{ $inp }}" {!! $state('visa_number') !!}>
            </x-erp.field>
            <x-erp.field label="ID Number" for="vs_id" error="fieldError('id_number')" error-id="vs-err-id_number">
                <input id="vs_id" type="text" maxlength="100" x-model="f.id_number" class="{{ $inp }}" {!! $state('id_number') !!}>
            </x-erp.field>
        </x-erp.section>

        {{-- Section 2 — Auto-filled details (read-only until "Edit manually") --}}
        <x-erp.section id="vs-sec-auto" icon="bi-magic" title="Auto-filled Details">
            <x-slot:actions>
                <span x-show="mofa.linked" x-cloak class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-200">
                    <i class="bi bi-link-45deg" aria-hidden="true"></i> <span x-text="'Linked to MOFA ' + (mofa.number || '')"></span>
                </span>
                <span x-show="mofa.checked && !mofa.linked" x-cloak class="text-xs text-slate-500">No MOFA entry for this passport</span>
                <button type="button" x-on:click="manual = !manual" x-bind:aria-pressed="manual ? 'true' : 'false'"
                        class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 ring-inset transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500"
                        x-bind:class="manual ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'">
                    <i class="bi" x-bind:class="manual ? 'bi-unlock' : 'bi-lock'" aria-hidden="true"></i>
                    <span x-text="manual ? 'Editing manually' : 'Edit manually'"></span>
                </button>
            </x-slot:actions>

            <x-erp.field label="Father's Name" for="vs_father" error="fieldError('father_name')" error-id="vs-err-father_name">
                <input id="vs_father" type="text" maxlength="100" x-model="f.father_name" class="{{ $ro }}" {!! $auto('father_name') !!}>
            </x-erp.field>
            <x-erp.field label="Mother's Name" for="vs_mother" error="fieldError('mother_name')" error-id="vs-err-mother_name">
                <input id="vs_mother" type="text" maxlength="100" x-model="f.mother_name" class="{{ $ro }}" {!! $auto('mother_name') !!}>
            </x-erp.field>
            <x-erp.field label="Date of Birth" for="vs_dob" error="fieldError('date_of_birth')" error-id="vs-err-date_of_birth">
                <input id="vs_dob" type="date" x-model="f.date_of_birth" x-on:change="touch('date_of_birth')" class="{{ $ro }}" {!! $auto('date_of_birth') !!}>
            </x-erp.field>
            <x-erp.field label="Age" for="vs_age" auto>
                <input id="vs_age" type="text" readonly tabindex="-1" x-bind:value="age()" placeholder="—" class="{{ \App\Support\ErpForm::READONLY }}">
            </x-erp.field>
            <x-erp.field label="MOFA Number" for="vs_mofa_no" error="fieldError('mofa_number')" error-id="vs-err-mofa_number">
                <input id="vs_mofa_no" type="text" maxlength="100" x-model="f.mofa_number" class="{{ $ro }}" {!! $auto('mofa_number') !!}>
            </x-erp.field>
            <x-erp.field label="MOFA Date" for="vs_mofa_date" error="fieldError('mofa_date')" error-id="vs-err-mofa_date">
                <input id="vs_mofa_date" type="date" x-model="f.mofa_date" class="{{ $ro }}" {!! $auto('mofa_date') !!}>
            </x-erp.field>
            <x-erp.field label="Issued Visa Number" for="vs_issued_visa" error="fieldError('issued_visa_number')" error-id="vs-err-issued_visa_number">
                <input id="vs_issued_visa" type="text" maxlength="100" x-model="f.issued_visa_number" class="{{ $ro }}" {!! $auto('issued_visa_number') !!}>
            </x-erp.field>
            <x-erp.field label="Issued Date" for="vs_issued_date" error="fieldError('issued_date')" error-id="vs-err-issued_date">
                <input id="vs_issued_date" type="date" x-model="f.issued_date" x-on:change="touch('expiry_date')" class="{{ $ro }}" {!! $auto('issued_date') !!}>
            </x-erp.field>
            <x-erp.field label="Expiry Date" for="vs_expiry" error="fieldError('expiry_date')" error-id="vs-err-expiry_date">
                <input id="vs_expiry" type="date" x-model="f.expiry_date" x-bind:min="f.issued_date || null" x-on:change="touch('expiry_date')" class="{{ $ro }}" {!! $auto('expiry_date') !!}>
            </x-erp.field>
            {{-- Left Day: red input when under the warning threshold (no helper text). --}}
            <x-erp.field label="Left Day" for="vs_left" auto>
                <input id="vs_left" type="text" readonly tabindex="-1" x-bind:value="leftDay() === '' ? '' : leftDay() + ' days'" placeholder="—"
                       x-bind:class="leftLow() ? '{{ \App\Support\ErpForm::READONLY_ALERT }}' : '{{ \App\Support\ErpForm::READONLY }}'">
            </x-erp.field>
        </x-erp.section>

        {{-- Section 3 — Stamping date & status --}}
        <x-erp.section id="vs-sec-status" icon="bi-calendar2-check" title="Stamping Date & Status">
            <x-erp.field label="Stamping Date" for="vs_stamp_date" required error="fieldError('stamping_date')" error-id="vs-err-stamping_date">
                <input id="vs_stamp_date" type="date" x-model="f.stamping_date" x-bind:max="cfg.today" x-on:change="touch('stamping_date')" x-on:blur="touch('stamping_date')"
                       class="{{ $inp }}" {!! $state('stamping_date') !!}>
            </x-erp.field>
            <x-erp.field label="Status" for="vs_status" required error="fieldError('status')" error-id="vs-err-status">
                <select id="vs_status" x-model="f.status" class="{{ $inp }}" {!! $state('status') !!}>
                    @foreach($statuses as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </x-erp.field>
            <x-erp.field label="Agent" for="vs_agent" error="fieldError('agent_id')" error-id="vs-err-agent_id">
                <select id="vs_agent" x-model="f.agent_id" x-on:change="onAgent()" class="{{ $inp }}" {!! $state('agent_id') !!}>
                    <option value="">— None —</option>
                    @foreach($agents as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                </select>
            </x-erp.field>
        </x-erp.section>

        {{-- Section 4 — Additional --}}
        <x-erp.section id="vs-sec-extra" icon="bi-journal-text" title="Additional Info" cols="2">
            {{-- Filled from the Agent select (single source); read-only so old values are kept as-is. --}}
            <x-erp.field label="Reference" for="vs_reference" auto error="fieldError('reference')" error-id="vs-err-reference">
                <input id="vs_reference" type="text" readonly tabindex="-1" x-model="f.reference" placeholder="—" class="{{ \App\Support\ErpForm::READONLY }}">
            </x-erp.field>
            <x-erp.textarea label="Remarks" id="vs_remarks" maxlength="1000" x-model="f.remarks"
                            error="fieldError('remarks')" error-id="vs-err-remarks" />
        </x-erp.section>
    </x-erp.modal>
</div>

@push('scripts')
<script>
    function stampingModal() {
        const cfg = JSON.parse(document.getElementById('stamping-modal-config').textContent);
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const JSON_HEADERS = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        const TONES = {
            pending: 'bg-amber-100 text-amber-800', processing: 'bg-purple-100 text-purple-800',
            completed: 'bg-blue-100 text-blue-800', stamped: 'bg-emerald-100 text-emerald-800',
            expired: 'bg-red-100 text-red-800', rejected: 'bg-gray-100 text-gray-700',
        };
        const ICONS = {
            pending: 'bi-hourglass-split', processing: 'bi-arrow-repeat', completed: 'bi-check2-all',
            stamped: 'bi-patch-check-fill', expired: 'bi-calendar-x', rejected: 'bi-x-octagon',
        };
        const REQUIRED = {
            full_name: 'Full name is required',
            passport_number: 'Passport number is required',
            stamping_date: 'Stamping date is required',
            status: 'Status is required',
        };
        const blank = () => ({
            full_name: '', passport_number: '', visa_number: '', id_number: '',
            stamping_date: cfg.today, status: 'pending', agent_id: '', reference: '',
            father_name: '', mother_name: '', date_of_birth: '', mofa_number: '', mofa_date: '',
            issued_visa_number: '', issued_date: '', expiry_date: '', remarks: '',
        });
        const dayMs = 86400000;

        return {
            cfg,
            open: false, mode: 'add', editId: null, loadingEntry: false, submitting: false, manual: false,
            f: blank(), touched: {}, server: {}, serverSnapshot: {}, returnFocus: null,
            mofa: { checked: false, linked: false, number: '' },

            init() {
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
                this.f.agent_id = this.f.agent_id ? String(this.f.agent_id) : '';
                this.touched = {}; this.server = {}; this.manual = false;
                this.mofa = { checked: false, linked: !!this.f.mofa_number, number: this.f.mofa_number };
            },
            show() {
                this.returnFocus = document.activeElement;
                this.open = true;
                document.body.style.overflow = 'hidden';
            },
            openAdd() {
                this.mode = 'add'; this.editId = null; this.reset(); this.show();
                this.$nextTick(() => setTimeout(() => document.getElementById('vs_full_name')?.focus(), 60));
            },
            openEdit(id) {
                this.mode = 'edit'; this.editId = id; this.reset();
                this.loadingEntry = true; this.show();
                fetch(cfg.base + '/' + id, { headers: JSON_HEADERS })
                    .then(r => { if (!r.ok) throw r; return r.json(); })
                    .then(data => {
                        this.reset(data);
                        this.loadingEntry = false;
                        this.$nextTick(() => document.getElementById('vs_full_name')?.focus());
                    })
                    .catch(() => { this.close(true); this.toast('error', 'Could not load this entry.'); });
            },
            close(force = false) {
                if (this.submitting && !force) return;
                this.open = false; this.loadingEntry = false;
                document.body.style.overflow = '';
                const url = new URL(window.location.href);
                if (url.searchParams.has('add') || url.searchParams.has('edit')) {
                    url.searchParams.delete('add'); url.searchParams.delete('edit');
                    history.replaceState(null, '', url);
                }
                this.$nextTick(() => this.returnFocus?.focus?.());
            },
            onEscape() {
                if (!this.open) return;
                this.close();
            },
            trapFocus(e) {
                const nodes = [...this.$refs.panel.querySelectorAll('button:not([disabled]), input:not([tabindex="-1"]):not([disabled]), select, textarea')]
                    .filter(el => el.offsetParent !== null);
                if (!nodes.length) return;
                const first = nodes[0], last = nodes[nodes.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            },

            // ── computed ───────────────────────────────────────────────────
            age() {
                const y = parseInt((this.f.date_of_birth || '').slice(0, 4), 10);
                return Number.isFinite(y) ? Math.max(0, new Date().getFullYear() - y) : '';
            },
            leftDay() {
                if (!this.f.issued_date || !this.f.expiry_date) return '';
                const a = Date.parse(this.f.issued_date + 'T00:00:00Z'), b = Date.parse(this.f.expiry_date + 'T00:00:00Z');
                return Number.isFinite(a) && Number.isFinite(b) ? Math.round((b - a) / dayMs) : '';
            },
            leftLow() { const d = this.leftDay(); return d !== '' && d < cfg.warnDays; },
            tone() { return TONES[this.f.status] || 'bg-gray-100 text-gray-700'; },
            icon() { return ICONS[this.f.status] || 'bi-circle'; },

            // ── validation (mirrors VisaStampingRequest) ───────────────────
            clientErrors() {
                const f = this.f, e = {};
                for (const [k, msg] of Object.entries(REQUIRED)) if (String(f[k] ?? '').trim() === '') e[k] = msg;
                if (!e.full_name && f.full_name.trim().length > 100) e.full_name = 'Full name may not exceed 100 characters';
                if (!e.passport_number && !/^[A-Za-z0-9]{5,20}$/.test(f.passport_number.trim())) e.passport_number = 'Passport number is invalid (5–20 letters/digits, no spaces)';
                if (!e.stamping_date && f.stamping_date > cfg.today) e.stamping_date = 'Date cannot be in the future';
                if (f.date_of_birth && f.date_of_birth >= cfg.today) e.date_of_birth = 'Date of birth must be before today';
                if (f.issued_date && f.expiry_date && f.expiry_date <= f.issued_date) e.expiry_date = 'Expiry date must be after the issue date';
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
                return n + (n === 1 ? ' field needs' : ' fields need') + ' attention before saving';
            },
            touch(k) { this.touched[k] = true; },

            // ── agent → reference ──────────────────────────────────────────
            onAgent() {
                const a = cfg.agents.find(x => String(x.id) === String(this.f.agent_id));
                if (a) { this.f.reference = a.name; }
            },

            // ── ERP auto-fill result (from erp.partials._erp-autofill) ─────
            // Drives the "Linked to MOFA" chip on the auto-filled card.
            onErpResult(data) {
                const mofa = data?.sources?.mofa;
                this.mofa = { checked: true, linked: !!mofa, number: mofa?.fields?.mofa_number || '' };
            },

            // ── submit ─────────────────────────────────────────────────────
            submit() {
                Object.keys(REQUIRED).forEach(k => this.touch(k));
                if (!this.isValid() || this.submitting) {
                    const first = Object.keys(this.clientErrors())[0];
                    if (first) {
                        if (!['full_name', 'passport_number', 'stamping_date', 'status'].includes(first)) this.manual = true;
                        this.$nextTick(() => this.$refs.panel.querySelector('[x-model="f.' + first + '"]')?.focus());
                    }
                    return;
                }
                this.submitting = true; this.server = {};
                const editing = this.mode === 'edit';

                fetch(editing ? cfg.base + '/' + this.editId : cfg.storeUrl, {
                    method: editing ? 'PUT' : 'POST',
                    headers: Object.assign({ 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, JSON_HEADERS),
                    body: JSON.stringify(Object.assign({}, this.f, { agent_id: this.f.agent_id || null })),
                })
                    .then(async r => ({ status: r.status, data: await r.json().catch(() => ({})) }))
                    .then(({ status, data }) => {
                        if (status === 200 && data.ok) {
                            this.close(true);
                            const url = new URL(window.location.href);
                            url.searchParams.delete('add'); url.searchParams.delete('edit');
                            setTimeout(() => window.location.replace(url.toString()), 160);
                            return;
                        }
                        this.submitting = false;
                        if (status === 422 && data.errors) {
                            this.serverSnapshot = Object.assign({}, this.f);
                            this.server = data.errors;
                            // An error in a locked auto-filled field needs it unlocked to fix.
                            const autoKeys = ['father_name', 'mother_name', 'date_of_birth', 'mofa_number', 'mofa_date', 'issued_visa_number', 'issued_date', 'expiry_date'];
                            if (Object.keys(data.errors).some(k => autoKeys.includes(k))) this.manual = true;
                            this.toast('error', data.errors.passport_number?.[0] === 'Passport number already exists'
                                ? 'Passport number already exists' : 'Please fix the highlighted fields.');
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
                window.dispatchEvent(new CustomEvent('stamping-toast', { detail: { type, message } }));
            },
        };
    }
</script>
@endpush
