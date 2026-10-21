{{--
    ERP auto-fill for the HR form. The three boxes on top (Passport / Visa / MOFA) take a number; after a short
    pause the ERP is searched (agency-scoped, JSON) and the form below is filled directly — EMPTY fields only,
    everything stays editable. No buttons, and nothing is listed under the boxes. The form's own Passport / Visa /
    MOFA fields trigger the same lookup. The picker (#hrErpPick) appears ONLY if one number matches several people.
--}}
<fieldset id="hrErpLookup" class="hr-sec" aria-labelledby="hrErpTitle">
    <legend id="hrErpTitle" class="hr-legend">Find in ERP</legend>
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
        @foreach(['passport' => 'Passport No', 'visa' => 'Visa No / Serial', 'mofa' => 'MOFA No'] as $key => $label)
            <div style="flex:1 1 220px;min-width:0">
                <label for="hrErp-{{ $key }}" class="block text-xs font-medium text-slate-600">{{ $label }}</label>
                <input id="hrErp-{{ $key }}" data-erp-key="{{ $key }}" type="text" maxlength="100" autocomplete="off" class="{{ $inp }}">
            </div>
        @endforeach
    </div>
    <div id="hrErpPick" hidden class="mt-3 space-y-2 text-sm text-slate-700" role="group" aria-label="More than one person found — choose one"></div>
</fieldset>

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
    async function lookup(params) {
        reset();
        const requestId = sequence;
        pending = new AbortController();
        try {
            const response = await fetch(@json(route('hr.erp-lookup')) + '?' + params.toString(), {headers: {'Accept': 'application/json'}, signal: pending.signal});
            if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) return;
            const candidates = (await response.json()).candidates;
            if (requestId !== sequence || !candidates.length) return;
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
                return value.length >= MIN[key] ? new URLSearchParams({[key]: value}) : null;
            });
        });
    });
})();
</script>
@endpush
