{{--
    ERP cross-module passport auto-fill — shared Add-form helper.

    On blur/change of an Add form's Passport Number input, this looks the passport
    up across all 6 ERP modules (agency-scoped) via GET erp.passport-lookup and
    fills ONLY the local inputs that are currently EMPTY. It never overwrites what
    the user already typed, and only identity fields (full_name / visa_serial /
    id_number / reference) are ever touched — never money, status or dates.

    Params (passed via @include):
      - $passportId : DOM id on this form's passport input (also carries the
                      "Auto-fills from existing records" placeholder).
      - $map        : [ json_key => local_input_name ] for THIS form only. Pass
                      only keys whose input exists here, e.g. Manpower passes
                      ['full_name' => 'customer_name']; MOFA maps
                      ['reference' => 'reference_name'].

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
    const endpoint = @js(route('erp.passport-lookup'));
    let lastLookup = '';

    function fillIfEmpty(name, value) {
        if (value === null || value === undefined || String(value).trim() === '') return;
        const el = form.querySelector('[name="' + name + '"]');
        if (!el) return;
        if (String(el.value).trim() !== '') return; // never overwrite user input
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function runLookup() {
        const passportNo = input.value.trim();
        if (!passportNo || passportNo === lastLookup) return;
        lastLookup = passportNo;

        fetch(endpoint + '?passport_no=' + encodeURIComponent(passportNo), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.found) return;
            Object.keys(map).forEach(function (jsonKey) {
                fillIfEmpty(map[jsonKey], data[jsonKey]);
            });
        })
        .catch(() => { /* silent — auto-fill is best-effort, form still works */ });
    }

    input.addEventListener('blur', runLookup);
    input.addEventListener('change', runLookup);
})();
</script>
@endpush
