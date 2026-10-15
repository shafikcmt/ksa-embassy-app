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
        if (value === null || value === undefined || String(value).trim() === '') return;
        const el = form.querySelector('[name="' + name + '"]');
        if (!el) return;
        if (String(el.value).trim() !== '') return; // never overwrite user input
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function runLookup() {
        clearTimeout(timer);
        const passportNo = input.value.trim().toUpperCase();
        if (!PASSPORT.test(passportNo) || passportNo === lastLookup) return;
        lastLookup = passportNo;

        fetch(endpoint + '/' + encodeURIComponent(passportNo), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            // A newer passport was typed while this request was in flight → ignore it.
            if (!data || !data.found || input.value.trim().toUpperCase() !== passportNo) return;
            Object.keys(map).forEach(function (key) {
                const merged = data.merged && data.merged[ALIAS[key] || key];
                fillIfEmpty(map[key], merged ? merged.value : null);
            });
        })
        .catch(() => { /* silent — auto-fill is best-effort, form still works */ });
    }

    // Look up while typing (debounced) so the user never has to tab out first.
    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(runLookup, 450); });
    input.addEventListener('blur', runLookup);
    input.addEventListener('change', runLookup);
})();
</script>
@endpush
