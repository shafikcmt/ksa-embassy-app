{{--
    ERP cross-module passport auto-fill — shared Add-form helper.

    As soon as an Add form's Passport Number looks complete (5–20 letters/digits, debounced;
    also on blur/change) this asks GET erp.autofill/{passport} — the same source the Medical /
    MOFA / Stamping / BMET modals use: Medical, MOFA, Stamping, BMET, Delivery, Double MOFA and
    the HR profile (lowest priority), agency-scoped — and fills ONLY the local inputs that are
    currently EMPTY. It never overwrites what the user already typed, and only identity fields
    are ever touched — never money, status or dates.

    Params (passed via @include):
      - $passportId : DOM id on this form's passport input.
      - $map        : [ field => local_input_name ] for THIS form only. Fields are the
                      canonical names of ErpPassportDataService (full_name, father_name,
                      mother_name, date_of_birth, mobile_no, visa_number, id_number,
                      mofa_number, reference …); the legacy key `visa_serial` is accepted
                      as an alias of visa_number. Pass only keys whose input exists here,
                      e.g. Manpower passes ['full_name' => 'customer_name'].

    Scope is the passport input's own <form> (via closest('form')), so the
    separate Edit modal (which reuses name="passport_no") is never affected.

    Vanilla fetch (no Axios), matching the existing embassy-lists convention.
--}}
@push('scripts')
<script>
(function () {
    const input = document.getElementById(@js($passportId));
    if (!input) return;

    const form = input.closest('form');
    if (!form) return;

    const map = @js($map);
    const endpoint = @js(parse_url(url('erp/autofill'), PHP_URL_PATH));
    const ALIAS = { visa_serial: 'visa_number' };
    const PASSPORT = /^[A-Z0-9]{5,20}$/;
    let lastLookup = '';
    let timer = null;

    function fillIfEmpty(name, value) {
        if (value === null || value === undefined || String(value).trim() === '') return false;
        const el = form.querySelector('[name="' + name + '"]');
        if (!el) return false;
        if (String(el.value).trim() !== '') return false; // never overwrite user input
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }

    // One-line status under the passport input: searching / filled / not found / unavailable.
    const status = document.createElement('p');
    status.className = 'mt-1.5 text-xs';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.hidden = true;
    (input.closest('div') || input.parentNode).appendChild(status);
    function say(text, tone) {
        status.hidden = !text;
        status.textContent = text || '';
        status.className = 'mt-1.5 text-xs ' + ({ ok: 'text-emerald-700', warn: 'text-amber-700', muted: 'text-gray-500' }[tone] || 'text-gray-500');
    }
    const SOURCE = { medical: 'Medical', mofa: 'MOFA', stamping: 'Stamping', bmet: 'BMET', delivery: 'Delivery', double_mofa: 'Double MOFA', hr: 'HR profile' };

    function runLookup() {
        clearTimeout(timer);
        const passportNo = input.value.trim().toUpperCase();
        if (!PASSPORT.test(passportNo)) { lastLookup = ''; say(''); return; }
        if (passportNo === lastLookup) return;
        lastLookup = passportNo;
        say('Searching ERP records…', 'muted');

        fetch(endpoint + '/' + encodeURIComponent(passportNo), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => { if (!r.ok) throw new Error('lookup failed'); return r.json(); })
        .then(data => {
            // A newer passport was typed while this request was in flight → ignore it.
            if (input.value.trim().toUpperCase() !== passportNo) return;
            if (!data || !data.found) { say('No existing records for this passport — enter details manually.', 'muted'); return; }
            const used = new Set();
            Object.keys(map).forEach(function (key) {
                const merged = data.merged && data.merged[ALIAS[key] || key];
                if (fillIfEmpty(map[key], merged ? merged.value : null) && merged) used.add(SOURCE[merged.source] || merged.source);
            });
            say(used.size ? 'Auto-filled from ' + Array.from(used).join(', ') : 'Found in ' + Object.keys(data.sources || {}).map(k => SOURCE[k] || k).join(', ') + ' — nothing new to fill.', used.size ? 'ok' : 'muted');
        })
        .catch(() => { lastLookup = ''; say('ERP search unavailable — enter details manually.', 'warn'); }); // form still works
    }

    // Look up while typing (debounced) so the user never has to tab out first.
    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(runLookup, 450); });
    input.addEventListener('blur', runLookup);
    input.addEventListener('change', runLookup);
})();
</script>
@endpush
