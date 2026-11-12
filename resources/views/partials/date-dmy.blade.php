{{--
    DD-MM-YYYY display for every <input type="date"> — WITHOUT touching the data.

    How it stays safe (lesson from the reverted flatpickr attempt):
      • The real <input type="date"> is kept as the single source of truth. Its value is
        still ISO yyyy-mm-dd, it keeps its name / x-model / listeners, and it is what the
        form submits. Only its box is moved off-screen.
      • A plain text box (no name → never submitted) shows the same date as dd-mm-yyyy.
      • Real → text: the value setter is hooked, so ANY programmatic change (Alpine x-model,
        auto-fill, JS) refreshes the text box immediately — no stale dates.
      • Text → real: written ONLY when a complete, valid dd-mm-yyyy is typed (or cleared).
        A half-typed / invalid entry is never saved: on blur the box reverts to the real value.
      • The calendar icon opens the browser's own date picker on the real input.
    Opt out per field with data-native-date.
--}}
<style>
    .dmy-text { padding-right: 2.25rem !important; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: right .7rem center !important; background-size: 15px 15px !important; }
    .dmy-text.dmy-bad { border-color: #f43f5e !important; }
    input[type="date"].dmy-real { position: fixed !important; opacity: 0 !important; pointer-events: none !important;
        width: 1px !important; height: 1px !important; min-width: 0 !important; padding: 0 !important; border: 0 !important; top: 0; left: 0; }
</style>
<script>
(function () {
    if (window.__dmyDates) return;
    window.__dmyDates = true;

    var desc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    if (!desc || !desc.set) return;
    Object.defineProperty(HTMLInputElement.prototype, 'value', {
        configurable: true, enumerable: desc.enumerable,
        get: function () { return desc.get.call(this); },
        set: function (v) { desc.set.call(this, v); if (this.__dmySync) this.__dmySync(); }
    });

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function isoToDmy(iso) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || ''); return m ? m[3] + '-' + m[2] + '-' + m[1] : ''; }
    function valid(y, mo, d) {
        if (y < 1000 || mo < 1 || mo > 12 || d < 1 || d > 31) return false;
        var t = new Date(Date.UTC(y, mo - 1, d));
        return t.getUTCFullYear() === y && t.getUTCMonth() === mo - 1 && t.getUTCDate() === d;
    }
    // Strictly day-first (dd-mm-yyyy, any separator) or ISO yyyy-mm-dd (e.g. pasted) → ISO, else null.
    function toIso(text) {
        var s = String(text || '').trim().replace(/[٠-٩]/g, function (c) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(c); });
        var m = /^(\d{1,2})[\/\-. ](\d{1,2})[\/\-. ](\d{4})$/.exec(s), y, mo, d;
        if (m) { d = +m[1]; mo = +m[2]; y = +m[3]; }
        else if ((m = /^(\d{4})[\/\-. ](\d{1,2})[\/\-. ](\d{1,2})$/.exec(s))) { y = +m[1]; mo = +m[2]; d = +m[3]; }
        else if ((m = /^(\d{2})(\d{2})(\d{4})$/.exec(s))) { d = +m[1]; mo = +m[2]; y = +m[3]; }
        else return null;
        return valid(y, mo, d) ? y + '-' + pad(mo) + '-' + pad(d) : null;
    }
    function mask(text) {
        var g = String(text || '').replace(/\D/g, '').slice(0, 8), out = g.slice(0, 2);
        if (g.length > 2) out += '-' + g.slice(2, 4);
        if (g.length > 4) out += '-' + g.slice(4, 8);
        return out;
    }
    function fire(el, type) { el.dispatchEvent(new Event(type, { bubbles: true })); }

    function enhance(real) {
        if (real.__dmyText || real.hasAttribute('data-native-date') || real.type !== 'date') return;
        var text = document.createElement('input');
        text.type = 'text';
        text.inputMode = 'numeric';
        text.autocomplete = 'off';
        text.placeholder = 'dd-mm-yyyy';
        text.setAttribute('aria-label', (real.labels && real.labels[0] ? real.labels[0].textContent.trim() + ' ' : '') + '(dd-mm-yyyy)');
        real.__dmyText = text;

        function mirror() {
            text.className = (real.className || '').replace(/\bdmy-real\b/g, '').trim() + ' dmy-text';
            text.disabled = real.disabled;
            text.readOnly = real.readOnly;
            if (real.required) { text.required = true; real.required = false; }
        }
        var writing = false;
        real.__dmySync = function () { if (!writing && document.activeElement !== text) { text.value = isoToDmy(desc.get.call(real)); text.classList.remove('dmy-bad'); } };

        mirror();
        real.classList.add('dmy-real');
        real.tabIndex = -1;
        real.setAttribute('aria-hidden', 'true');
        text.value = isoToDmy(real.value);
        real.parentNode.insertBefore(text, real);
        new MutationObserver(mirror).observe(real, { attributes: true, attributeFilter: ['class', 'disabled', 'readonly', 'required'] });

        function commit(iso) {
            if (iso === desc.get.call(real)) return;
            writing = true; real.value = iso; writing = false;
            fire(real, 'input'); fire(real, 'change');
        }
        text.addEventListener('input', function (e) {
            if (e.inputType !== 'insertFromPaste') text.value = mask(text.value);
            var v = text.value.trim();
            if (v === '') { text.classList.remove('dmy-bad'); return; }   // cleared → saved on blur
            var iso = toIso(v);
            if (iso) { text.classList.remove('dmy-bad'); commit(iso); }
        });
        text.addEventListener('paste', function (e) {
            var iso = toIso((e.clipboardData || window.clipboardData).getData('text'));
            if (!iso) return;
            e.preventDefault();
            text.value = isoToDmy(iso); text.classList.remove('dmy-bad'); commit(iso);
        });
        text.addEventListener('blur', function () {
            var v = text.value.trim(), iso = v === '' ? '' : toIso(v);
            if (iso === null) {                       // incomplete / impossible date → never saved
                text.value = isoToDmy(desc.get.call(real));
                text.classList.add('dmy-bad');
                setTimeout(function () { text.classList.remove('dmy-bad'); }, 1600);
            } else { commit(iso); text.value = isoToDmy(iso); }
            real.dispatchEvent(new FocusEvent('blur'));
            fire(real, 'focusout');
        });
        text.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { var iso = toIso(text.value); if (iso) commit(iso); }
        });
        // Calendar icon (right edge) → the browser's native picker on the real input.
        text.addEventListener('click', function (e) {
            var r = text.getBoundingClientRect();
            if (text.disabled || text.readOnly || e.clientX < r.right - 36) return;
            real.style.setProperty('top', r.bottom + 'px', 'important');
            real.style.setProperty('left', r.left + 'px', 'important');
            try { real.showPicker(); } catch (_) {}
        });
        // Native picker / other scripts changing the real input → always reflect it.
        real.addEventListener('change', function () { if (!writing) { text.value = isoToDmy(desc.get.call(real)); text.classList.remove('dmy-bad'); } });
        // Focus requests on the real input (label click, scripts) land on the text box.
        real.addEventListener('focus', function () { text.focus(); });
    }

    function scan(root) {
        if (root.nodeType !== 1) return;
        if (root.matches && root.matches('input[type="date"]')) enhance(root);
        if (root.querySelectorAll) root.querySelectorAll('input[type="date"]').forEach(enhance);
    }
    scan(document.body);
    new MutationObserver(function (list) {
        list.forEach(function (m) { m.addedNodes.forEach(scan); });
    }).observe(document.body, { childList: true, subtree: true });
})();
</script>
