{{--
    App-wide date display: every <input type="date"> is shown as DD-MM-YYYY
    (Date-Month-Year) regardless of browser locale. The real input keeps its
    ISO yyyy-mm-dd value, so forms, validation, filters and the DB are unchanged.
--}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script>
(function () {
    function initDates(root) {
        if (!window.flatpickr) return;
        (root || document).querySelectorAll('input[type="date"]:not([data-dmy])').forEach(function (el) {
            var wasRequired = el.required;
            el.dataset.dmy = '1';
            var cls = el.className;
            window.flatpickr(el, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd-m-Y',
                allowInput: true,
                disableMobile: true,
                altInputClass: cls,
                onReady: function (sel, str, fp) {
                    // Keep the real field a date input (other scripts/filters match on it) but invisible.
                    el.type = 'date';
                    el.required = false;
                    el.style.display = 'none';
                    fp.altInput.placeholder = 'dd-mm-yyyy';
                    fp.altInput.required = wasRequired;
                    if (el.disabled) fp.altInput.disabled = true;
                }
            });
        });
    }
    window.initDmyDates = initDates;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { initDates(); });
    else initDates();
})();
</script>
