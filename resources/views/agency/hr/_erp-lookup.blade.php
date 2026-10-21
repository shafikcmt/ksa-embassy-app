{{--
    Automatic ERP fill for the HR form. There is no search box: typing the form's OWN Passport / Visa / MOFA
    field is enough. After a short pause the ERP is searched (agency-scoped, JSON) and, when exactly one person
    matches, the EMPTY fields are filled (never overwritten) — everything stays editable. A one-line status sits
    under the typed field. The picker below appears ONLY when several people match, to choose one.
--}}
<fieldset id="hrErpLookup" class="hr-sec" aria-labelledby="hrErpTitle" hidden>
    <legend id="hrErpTitle" class="hr-legend">More than one person found</legend>
    <p id="hrErpMessage" class="hr-note" role="status" aria-live="polite">Choose one passport to fill the form.</p>
    <div id="hrErpResults" class="mt-2 space-y-2 text-sm text-slate-700"></div>
</fieldset>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('hrErpLookup');
    const form = document.getElementById('hrForm');
    const message = document.getElementById('hrErpMessage');
    const results = document.getElementById('hrErpResults');
    const currentHr = @json($hr?->id);
    const labels = {passport_number:'Passport Number', full_name_en:'Name', father_name:'Father', mother_name:'Mother',
        date_of_birth:'Date of Birth', mofa_new:'New MOFA', mofa_old:'Old MOFA', passport_issue_date:'Passport Issue Date',
        passport_expiry_date:'Passport Expiry Date', visa_number:'Visa Number', sponsor_id:'Sponsor ID'};
    // [form field, ERP search key, minimum characters]
    const AUTO = [['passport_number', 'passport', 5], ['visa_number', 'visa', 6], ['mofa_new', 'mofa', 6]];
    let selected = null, sequence = 0, pending = null, applying = false, timer = null, last = '', statusEl = null;
    const normalize = value => String(value || '').trim().toUpperCase();

    // One-line status under the field the user typed in.
    function say(field, text, tone) {
        if (!statusEl) {
            statusEl = document.createElement('p');
            statusEl.setAttribute('role', 'status');
            statusEl.setAttribute('aria-live', 'polite');
        }
        const host = field.closest('.hr-field, div') || field.parentNode;
        if (statusEl.parentNode !== host) host.appendChild(statusEl);
        statusEl.hidden = !text;
        statusEl.textContent = text || '';
        statusEl.className = 'mt-1 text-xs ' + ({ok: 'text-emerald-700', warn: 'text-amber-700'}[tone] || 'text-slate-500');
    }
    function reset() {
        sequence++;
        if (pending) pending.abort();
        selected = null;
        results.replaceChildren();
        root.hidden = true;
    }
    function showCandidate(candidate, container) {
        candidate.records.forEach(record => {
            const line = document.createElement('p');
            line.textContent = [record.module, record.name, record.mofa_number && 'MOFA: ' + record.mofa_number,
                record.visa_number && 'Visa: ' + record.visa_number, record.visa_serial && 'Serial: ' + record.visa_serial].filter(Boolean).join(' · ');
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
    function applySelected(field) {
        if (!selected) return;
        const passport = form.elements.namedItem('passport_number');
        if (passport && normalize(passport.value) && normalize(passport.value) !== selected.passport) {
            say(field, 'The form passport differs from the person found — change or clear it to fill from the ERP.', 'warn');
            return;
        }
        applying = true; setTimeout(() => { applying = false; }, 0); // the fill itself must not trigger another search
        const filled = [], skipped = [];
        Object.entries(selected.fields).forEach(([name, data]) => {
            if (!Object.prototype.hasOwnProperty.call(labels, name)) return;
            const input = form.elements.namedItem(name);
            if (!input || input.type === 'hidden' || input.disabled || input.readOnly) return;
            if (String(input.value).trim()) { skipped.push(labels[name]); return; }
            input.value = data.value;
            // Input clears validation; change would recalculate and overwrite passport expiry.
            input.dispatchEvent(new Event('input', {bubbles: true}));
            filled.push(labels[name]);
        });
        root.hidden = true;
        const sources = [...new Set(Object.values(selected.fields).map(f => f.source))];
        say(field, filled.length
            ? 'Auto-filled ' + filled.length + ' field' + (filled.length === 1 ? '' : 's') + ' from ERP' + (sources.length ? ' (' + sources.join(', ') + ')' : '') + ' — you can edit them.'
            : 'Found in ERP — nothing new to fill.', filled.length ? 'ok' : 'muted');
    }
    async function lookup(field, key, value) {
        reset();
        const requestId = sequence;
        pending = new AbortController();
        say(field, 'Searching ERP…', 'muted');
        try {
            const response = await fetch(@json(route('hr.erp-lookup')) + '?' + new URLSearchParams({[key]: value}).toString(), {
                headers: {'Accept': 'application/json'}, signal: pending.signal
            });
            if (!response.ok || response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) throw new Error('lookup failed');
            const data = await response.json();
            if (requestId !== sequence) return;
            const candidates = data.candidates;
            if (!candidates.length) { say(field, 'No ERP record found — enter the details manually.', 'muted'); return; }
            if (candidates.length === 1) {
                selected = candidates[0];
                // Another HR record for this passport? show the warning line in the picker box only.
                if (selected.hr_profiles.some(profile => String(profile.id) !== String(currentHr))) {
                    const container = document.createElement('div');
                    showCandidate({records: [], hr_profiles: selected.hr_profiles}, container);
                    results.append(container);
                    message.textContent = 'Check before saving:';
                    root.querySelector('legend').textContent = 'Existing HR record';
                    root.hidden = false;
                }
                applySelected(field);
                return;
            }
            // Several people: choose one (the only time anything is shown outside the form).
            root.querySelector('legend').textContent = 'More than one person found';
            message.textContent = 'Choose one passport to fill the form.';
            candidates.forEach(candidate => {
                const container = document.createElement('div');
                container.style.cssText = 'border:1px solid #e2e8f0;border-radius:8px;padding:12px;overflow-wrap:anywhere';
                const heading = document.createElement('label');
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'hr_erp_candidate';
                radio.setAttribute('form', 'hrErpNoSubmit'); // detach from #hrForm so it is never submitted
                radio.value = candidate.passport;
                radio.addEventListener('change', () => { selected = candidate; applySelected(field); });
                heading.append(radio, ' Passport: ' + candidate.passport);
                container.append(heading);
                showCandidate(candidate, container);
                results.append(container);
            });
            root.hidden = false;
            say(field, 'More than one person found — choose one above.', 'warn');
        } catch (error) {
            if (requestId === sequence && error.name !== 'AbortError') say(field, 'ERP search unavailable — fill the details manually.', 'warn');
        }
    }

    AUTO.forEach(([fieldName, key, min]) => {
        const field = form.elements.namedItem(fieldName);
        if (!field || field.type === 'hidden') return;
        field.addEventListener('input', () => {
            if (applying) return;
            clearTimeout(timer);
            const value = normalize(field.value);
            if (value.length < min) { reset(); say(field, ''); last = ''; return; }
            timer = setTimeout(() => {
                if (last === key + ':' + value) return;
                last = key + ':' + value;
                lookup(field, key, value);
            }, 600);
        });
    });
})();
</script>
@endpush
