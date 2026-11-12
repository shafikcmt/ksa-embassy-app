{{--
    ERP auto-fill for the HR form. The box on top takes a Passport number; after a short
    pause the ERP is searched (agency-scoped, JSON) and the form below is filled directly — EMPTY fields only,
    everything stays editable. No buttons, and nothing is listed under the boxes. The form's own Passport / Visa /
    MOFA fields trigger the same lookup. The picker (#hrErpPick) appears ONLY if one number matches several people.
--}}
<section id="hrErpLookup" class="hr-erp" aria-labelledby="hrErpTitle">
    <div class="hr-erp-head">
        <span class="hr-erp-icon"><i class="bi bi-search"></i></span>
        <div>
            <h3 id="hrErpTitle" class="hr-erp-title">Find in ERP</h3>
            <p class="hr-erp-sub">Type the passport number — matching ERP data fills the form automatically.</p>
        </div>
    </div>
    <div class="hr-erp-box">
        <i class="bi bi-passport hr-erp-box-icon"></i>
        <input id="hrErp-passport" data-erp-key="passport" type="text" maxlength="100" autocomplete="off" placeholder="Enter Passport No" aria-label="Passport No" class="hr-erp-input">
    </div>
    <div id="hrErpPick" hidden class="mt-3 space-y-2 text-sm text-slate-700" role="group" aria-label="More than one person found — choose one"></div>
</section>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('hrErpLookup');
    const form = document.getElementById('hrForm');
    const inputs = Array.from(root.querySelectorAll('[data-erp-key]'));
    const pick = document.getElementById('hrErpPick');
    const currentHr = @json($hr?->id);
    const labels = {passport_number:'Passport Number', full_name_en:'Name', father_name:'Father', mother_name:'Mother',
        date_of_birth:'Date of Birth', mofa_new:'New MOFA', mofa_old:'Old MOFA', passport_issue_date:'Passport Issue Date',
        passport_expiry_date:'Passport Expiry Date', visa_number:'Visa Number', sponsor_id:'Sponsor ID'};
    const MIN = {passport: 5, visa: 6, mofa: 6};
    // The form's own fields trigger the same lookup: [form field, ERP search key]
    const FORM_FIELDS = [['passport_number', 'passport'], ['visa_number', 'visa'], ['mofa_new', 'mofa']];
    let sequence = 0, pending = null, applying = false, timer = null, lastQuery = '';
    const normalize = value => String(value || '').trim().toUpperCase();

    function reset() {
        sequence++;
        if (pending) pending.abort();
        pick.replaceChildren();
        pick.hidden = true;
    }
    function fill(candidate) {
        const passport = form.elements.namedItem('passport_number');
        if (passport && normalize(passport.value) && normalize(passport.value) !== candidate.passport) return; // different person already typed
        applying = true; setTimeout(() => { applying = false; }, 0); // the fill itself must not trigger another search
        Object.entries(candidate.fields).forEach(([name, data]) => {
            if (!Object.prototype.hasOwnProperty.call(labels, name)) return;
            const input = form.elements.namedItem(name);
            if (!input || input.type === 'hidden' || input.disabled || input.readOnly || String(input.value).trim()) return; // empty fields only
            input.value = data.value;
            // Input clears validation; change would recalculate and overwrite passport expiry.
            input.dispatchEvent(new Event('input', {bubbles: true}));
        });
        pick.replaceChildren(); pick.hidden = true;
    }
    function warnings(candidate, container) {
        candidate.hr_profiles.filter(profile => String(profile.id) !== String(currentHr)).forEach(profile => {
            const line = document.createElement('p');
            line.append('This passport already has another HR record: ');
            const link = document.createElement('a');
            link.href = profile.url; link.textContent = profile.name; link.style.textDecoration = 'underline';
            line.append(link);
            container.append(line);
        });
    }
    // Same visa number → fill the Visa Info block (sponsor, visa date, place, profession, wakala…)
    // from the agency's latest HR record with that visa (sponsor ID can also come from ERP).
    // EMPTY fields only; English/Arabic pairs are set directly so neither side is re-translated.
    const PAIRS = {visa_issue_place: 'visa_issue_place_ar', profession_en: 'profession_ar', qualification_en: 'qualification_ar', sponsor_name: 'sponsor_name_ar'};
    function fillVisa(fields) {
        const done = [];
        Object.entries(fields).forEach(([name, data]) => {
            const input = form.elements.namedItem(name);
            if (!input || input.disabled || input.readOnly || String(input.value).trim()) return;
            if (input.type === 'hidden' && name !== 'sponsor_name') return; // field switched off for this agency
            if (input.tagName === 'SELECT' && ![...input.options].some(o => o.value === data.value)) {
                input.add(new Option(data.value, data.value));
            }
            input.value = data.value;
            input.dataset.touched = '1';
            input.classList.remove('!border-rose-400');
            done.push(name);
        });
        // A pair where only one side was known: let the form's own EN⇄AR helper fill the other side.
        Object.entries(PAIRS).forEach(([en, ar]) => {
            const enEl = form.elements.namedItem(en), arEl = form.elements.namedItem(ar);
            if (!enEl || !arEl) return;
            if (done.includes(en) && !String(arEl.value).trim()) enEl.dispatchEvent(new Event('change', {bubbles: true}));
            else if (done.includes(ar) && !String(enEl.value).trim()) arEl.dispatchEvent(new Event('change', {bubbles: true}));
        });
        const visaInput = form.elements.namedItem('visa_number');
        if (!visaInput) return;
        let note = document.getElementById('hrVisaFillNote');
        if (!note) {
            note = document.createElement('p');
            note.id = 'hrVisaFillNote';
            note.className = 'mt-1 text-xs font-medium text-emerald-600';
            visaInput.insertAdjacentElement('afterend', note);
        }
        const sources = [...new Set(done.map(name => fields[name].source))].join(', ');
        note.textContent = done.length ? 'Filled ' + done.length + ' visa field(s) from ' + sources + ' — please check.' : '';
    }

    async function lookup(params) {
        reset();
        const requestId = sequence;
        pending = new AbortController();
        try {
            const response = await fetch(@json(route('hr.erp-lookup')) + '?' + params.toString(), {headers: {'Accept': 'application/json'}, signal: pending.signal});
            if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) return;
            const payload = await response.json();
            if (requestId !== sequence) return;
            fillVisa(payload.visa || {});
            const candidates = payload.candidates || [];
            if (!candidates.length) return;
            if (candidates.length === 1) {
                fill(candidates[0]);
                // Another HR record already exists for this passport → keep the warning (the only thing shown).
                const box = document.createElement('div');
                warnings(candidates[0], box);
                if (box.children.length) { pick.append(box); pick.hidden = false; }
                return;
            }
            // One number, several people: the only case that needs a choice.
            candidates.forEach(candidate => {
                const box = document.createElement('div');
                box.style.cssText = 'border:1px solid #e2e8f0;border-radius:8px;padding:10px;overflow-wrap:anywhere';
                const label = document.createElement('label');
                const radio = document.createElement('input');
                radio.type = 'radio'; radio.name = 'hr_erp_candidate'; radio.value = candidate.passport;
                radio.setAttribute('form', 'hrErpNoSubmit'); // detach from #hrForm so it is never submitted
                radio.addEventListener('change', () => fill(candidate));
                label.append(radio, ' ' + candidate.passport + ' — ' + (candidate.records[0] ? candidate.records[0].name : ''));
                box.append(label);
                warnings(candidate, box);
                pick.append(box);
            });
            pick.hidden = false;
        } catch (error) { /* silent: the form can always be completed by hand */ }
    }
    function schedule(build) {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const params = build();
            if (!params || params.toString() === lastQuery) return;
            lastQuery = params.toString();
            lookup(params);
        }, 600);
    }

    // Top boxes: every filled box must match the same person.
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            reset();
            schedule(() => {
                if (!inputs.some(box => box.value.trim().length >= MIN[box.dataset.erpKey])) return null;
                const params = new URLSearchParams();
                inputs.forEach(box => { if (box.value.trim()) params.set(box.dataset.erpKey, box.value.trim()); });
                return params;
            });
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); event.stopPropagation(); }
        });
    });

    // The form's own Passport / Visa / MOFA fields.
    FORM_FIELDS.forEach(([fieldName, key]) => {
        const field = form.elements.namedItem(fieldName);
        if (!field || field.type === 'hidden') return;
        field.addEventListener('input', () => {
            if (applying) return;
            reset();
            schedule(() => {
                const value = normalize(field.value);
                if (value.length < MIN[key]) return null;
                const params = new URLSearchParams({[key]: value});
                if (currentHr) params.set('exclude', currentHr);
                return params;
            });
        });
    });
})();
</script>
@endpush
