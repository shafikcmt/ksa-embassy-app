<fieldset id="hrErpLookup" class="hr-sec" aria-labelledby="hrErpTitle">
    <legend id="hrErpTitle" class="hr-legend">Find in ERP</legend>
    <p class="hr-note">Type the Passport, Visa or MOFA number here or in the form below — matching ERP details are fetched and filled in automatically (only empty fields; you can edit everything afterwards). When you enter more than one value, all must match the same person.</p>
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
        @foreach(['passport' => 'Passport No', 'visa' => 'Visa No / Serial', 'mofa' => 'MOFA No'] as $key => $label)
            <div style="flex:1 1 220px;min-width:0">
                <label for="hrErp-{{ $key }}" class="block text-xs font-medium text-slate-600">{{ $label }}</label>
                <input id="hrErp-{{ $key }}" data-erp-key="{{ $key }}" type="text" maxlength="100" autocomplete="off" class="{{ $inp }}">
            </div>
        @endforeach
    </div>
    <p id="hrErpMessage" class="mt-2 text-sm text-slate-600" role="status" aria-live="polite"></p>
    <div id="hrErpResults" class="mt-2 space-y-2 text-sm text-slate-700"></div>
</fieldset>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('hrErpLookup');
    const form = document.getElementById('hrForm');
    const inputs = Array.from(root.querySelectorAll('[data-erp-key]'));
    const message = document.getElementById('hrErpMessage');
    const results = document.getElementById('hrErpResults');
    const currentHr = @json($hr?->id);
    const labels = {passport_number:'Passport Number', full_name_en:'Name', father_name:'Father', mother_name:'Mother',
        date_of_birth:'Date of Birth', mofa_new:'New MOFA', mofa_old:'Old MOFA', passport_issue_date:'Passport Issue Date',
        passport_expiry_date:'Passport Expiry Date', visa_number:'Visa Number', sponsor_id:'Sponsor ID'};
    let selected = null, sequence = 0, pending = null, applying = false;
    const normalize = value => String(value || '').trim().toUpperCase();
    function reset() {
        sequence++;
        if (pending) pending.abort();
        selected = null;
        results.replaceChildren();
    }
    function showCandidate(candidate, container) {
        candidate.records.forEach(record => {
            const line = document.createElement('p');
            line.textContent = [record.module, record.name, record.mofa_number && 'MOFA: ' + record.mofa_number,
                record.visa_number && 'Visa: ' + record.visa_number, record.visa_serial && 'Serial: ' + record.visa_serial,
                record.updated_at && 'Updated: ' + record.updated_at].filter(Boolean).join(' · ');
            container.append(line);
        });
        candidate.hr_profiles.filter(profile => String(profile.id) !== String(currentHr)).forEach(profile => {
            const line = document.createElement('p');
            line.append('This passport already has another HR record: ');
            const link = document.createElement('a');
            link.href = profile.url;
            link.textContent = profile.name;
            link.style.textDecoration = 'underline';
            line.append(link);
            container.append(line);
        });
    }
    async function runSearch() {
        reset();
        const params = new URLSearchParams();
        inputs.forEach(input => { if (input.value.trim()) params.set(input.dataset.erpKey, input.value.trim()); });
        if (!params.size) {
            message.textContent = 'Enter a Passport No, Visa No / Serial, or MOFA No.';
            return;
        }
        const requestId = sequence;
        pending = new AbortController();
        message.textContent = 'Searching ERP…';
        try {
            const response = await fetch(@json(route('hr.erp-lookup')) + '?' + params.toString(), {
                headers: {'Accept':'application/json'}, signal: pending.signal
            });
            if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
                throw new Error('Lookup failed. Check your access or search values and try again.');
            }
            const data = await response.json();
            if (requestId !== sequence) return;
            const candidates = data.candidates;
            message.textContent = candidates.length ? (candidates.length === 1 ? 'One person found — filling the form…' : 'More than one person found. Choose one passport to fill the form.') : 'No ERP record found for these search values.';
            candidates.forEach(candidate => {
                const container = document.createElement('div');
                container.style.cssText = 'border:1px solid #e2e8f0;border-radius:8px;padding:12px;overflow-wrap:anywhere';
                const heading = document.createElement('label');
                if (candidates.length > 1) {
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'hr_erp_candidate';
                    radio.setAttribute('form', 'hrErpNoSubmit'); // detach from #hrForm so it is never submitted
                    radio.value = candidate.passport;
                    radio.addEventListener('change', () => { selected = candidate; applySelected(); });
                    heading.append(radio, ' ');
                }
                heading.append('Passport: ' + candidate.passport);
                container.append(heading);
                showCandidate(candidate, container);
                results.append(container);
            });
            if (candidates.length === 1) { selected = candidates[0]; applySelected(); } // exactly one person: fill straight away
        } catch (error) {
            if (requestId === sequence && error.name !== 'AbortError') message.textContent = 'Lookup failed. Please try again; you can still complete the form manually.';
        }
    }
    // The box above searches by itself: a short pause after typing (Enter searches at once).
    const MIN = {passport: 5, visa: 6, mofa: 6};
    let boxTimer = null;
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            reset(); message.textContent = '';
            clearTimeout(boxTimer);
            boxTimer = setTimeout(() => {
                if (inputs.some(box => box.value.trim().length >= MIN[box.dataset.erpKey])) runSearch();
            }, 500);
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); event.stopPropagation(); clearTimeout(boxTimer); runSearch(); }
        });
    });

    // ── Automatic mode ────────────────────────────────────────────────────────
    // Typing the form's OWN Passport / Visa / MOFA field is enough: after a short pause the ERP is
    // searched and, when exactly one person matches, the EMPTY fields are filled (never overwritten).
    // Several matches still wait for the user to pick one. The box above stays as a manual fallback.
    const AUTO = [['passport_number', 'passport', 5], ['visa_number', 'visa', 6], ['mofa_new', 'mofa', 6]];
    let autoTimer = null, autoLast = '';

    AUTO.forEach(([fieldName, key, min]) => {
        const field = form.elements.namedItem(fieldName);
        if (!field || field.type === 'hidden') return;
        field.addEventListener('input', () => {
            if (applying) return; // the lookup itself filled this field
            clearTimeout(autoTimer);
            autoTimer = setTimeout(async () => {
                const value = normalize(field.value);
                if (value.length < min || autoLast === key + ':' + value) return;
                autoLast = key + ':' + value;
                inputs.forEach(box => { box.value = box.dataset.erpKey === key ? value : ''; });
                await runSearch();
            }, 600);
        });
    });
    function applySelected() {
        if (!selected) return;
        applying = true; setTimeout(() => { applying = false; }, 0); // the fill itself must not trigger another search
        const passport = form.elements.namedItem('passport_number');
        if (passport && normalize(passport.value) && normalize(passport.value) !== selected.passport) {
            message.textContent = 'The form passport differs from the selected person. Change or clear it in the form to fill from this person.';
            return;
        }
        const filled = [], skipped = [];
        Object.entries(selected.fields).forEach(([name, field]) => {
            if (!Object.prototype.hasOwnProperty.call(labels, name)) return;
            const input = form.elements.namedItem(name);
            if (!input || input.type === 'hidden' || input.disabled || input.readOnly) return;
            if (String(input.value).trim()) { skipped.push(labels[name]); return; }
            input.value = field.value;
            // Input clears validation; change would recalculate and overwrite passport expiry.
            input.dispatchEvent(new Event('input', {bubbles:true}));
            filled.push(labels[name] + ' from ' + field.source);
        });
        message.textContent = (filled.length ? 'Filled: ' + filled.join('; ') + '.' : 'No empty matching fields to fill.')
            + (skipped.length ? ' Skipped (already filled): ' + skipped.join(', ') + '.' : '');
    }
})();
</script>
@endpush
