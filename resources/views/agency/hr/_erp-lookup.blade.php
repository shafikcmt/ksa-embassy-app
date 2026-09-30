<section id="hrErpLookup" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4" aria-labelledby="hrErpTitle">
    {{-- Header matches the other HR form sections (icon + uppercase title). --}}
    <div class="mb-3 flex items-center gap-3 border-b border-slate-100 pb-2">
        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600"><i class="bi bi-search text-base"></i></span>
        <div>
            <h2 id="hrErpTitle" class="text-xs font-bold uppercase tracking-wider text-slate-700">Find in ERP</h2>
            <p class="mt-0.5 text-xs text-slate-400">Enter at least one value. When you enter more, every value must match the same person.</p>
        </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
        @foreach(['passport' => 'Passport No', 'visa' => 'Visa No / Serial', 'mofa' => 'MOFA No'] as $key => $label)
            <div style="flex:1 1 220px;min-width:0">
                <label for="hrErp-{{ $key }}" class="block text-xs font-medium text-slate-600">{{ $label }}</label>
                <input id="hrErp-{{ $key }}" data-erp-key="{{ $key }}" type="text" maxlength="100" autocomplete="off" class="{{ $inp }}">
            </div>
        @endforeach
        <button id="hrErpSearch" type="button" class="h-9 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">Search ERP</button>
    </div>
    <p id="hrErpMessage" class="mt-2 text-sm text-slate-600" role="status" aria-live="polite"></p>
    <div id="hrErpResults" class="mt-2 space-y-2 text-sm text-slate-700"></div>
    <button id="hrErpApply" type="button" hidden class="mt-2 h-9 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">Apply to form</button>
</section>

@push('scripts')
<script>
(function () {
    const root = document.getElementById('hrErpLookup');
    const form = document.getElementById('hrForm');
    const inputs = Array.from(root.querySelectorAll('[data-erp-key]'));
    const search = document.getElementById('hrErpSearch');
    const apply = document.getElementById('hrErpApply');
    const message = document.getElementById('hrErpMessage');
    const results = document.getElementById('hrErpResults');
    const currentHr = @json($hr?->id);
    const labels = {passport_number:'Passport Number', full_name_en:'Name', father_name:'Father', mother_name:'Mother',
        date_of_birth:'Date of Birth', mofa_new:'New MOFA', mofa_old:'Old MOFA', passport_issue_date:'Passport Issue Date',
        passport_expiry_date:'Passport Expiry Date', visa_number:'Visa Number', sponsor_id:'Sponsor ID'};
    let selected = null, sequence = 0, pending = null;
    const normalize = value => String(value || '').trim().toUpperCase();
    function reset() {
        sequence++;
        if (pending) pending.abort();
        selected = null;
        apply.hidden = true;
        results.replaceChildren();
        search.disabled = false;
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
        search.disabled = true;
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
            message.textContent = candidates.length ? (candidates.length === 1 ? 'One person found. Review before applying.' : 'More than one person found. Choose one passport.') : 'No ERP record found for these search values.';
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
                    radio.addEventListener('change', () => { selected = candidate; apply.hidden = false; });
                    heading.append(radio, ' ');
                }
                heading.append('Passport: ' + candidate.passport);
                container.append(heading);
                showCandidate(candidate, container);
                results.append(container);
            });
            if (candidates.length === 1) { selected = candidates[0]; apply.hidden = false; }
        } catch (error) {
            if (requestId === sequence && error.name !== 'AbortError') message.textContent = 'Lookup failed. Please try again; you can still complete the form manually.';
        } finally {
            if (requestId === sequence) search.disabled = false;
        }
    }
    inputs.forEach(input => {
        input.addEventListener('input', () => { reset(); message.textContent = ''; });
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); event.stopPropagation(); runSearch(); }
        });
    });
    search.addEventListener('click', runSearch);
    apply.addEventListener('click', () => {
        if (!selected) return;
        const passport = form.elements.namedItem('passport_number');
        if (passport && normalize(passport.value) && normalize(passport.value) !== selected.passport) {
            message.textContent = 'The form passport differs from the selected person. Review and change or clear it manually before applying.';
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
    });
})();
</script>
@endpush
