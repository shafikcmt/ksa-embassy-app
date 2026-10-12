/**
 * Live (AJAX) list filters — no "Apply" click, no full page reload.
 *
 * Markup:
 *   <form method="GET" data-ajax-filter> … </form>         filters (text = debounced, select/date/checkbox = instant)
 *   <div id="some-id" data-ajax-region> … </div>           parts of the page that change (count, links, table, pagination)
 *
 * On change the form's GET URL is fetched in the background, every [data-ajax-region]
 * is swapped with the element of the same id from the response, and the address bar
 * is updated (so Print / Export / refresh / sharing keep the filters). Pagination
 * links inside a region load the same way. Any failure falls back to a normal page load.
 */
const DEBOUNCE_MS = 350;
const FORM = 'form[data-ajax-filter]';
let controller = null;

function buildUrl(form, submitter = null) {
    const url = new URL(form.getAttribute('action') || window.location.pathname, window.location.origin);
    const params = new URLSearchParams();
    const add = (key, value) => {
        if (typeof value !== 'string' || value.trim() === '') return;
        // Array fields (name[]) keep every value; otherwise the last one wins, like PHP.
        key.endsWith('[]') ? params.append(key, value) : params.set(key, value);
    };

    // A page may split its filters over several forms (e.g. filter bar + search/per-page toolbar)
    // that copy each other's values into hidden inputs. Visible controls are the source of truth;
    // a hidden copy is used only when no visible control of that name exists.
    const forms = [...document.querySelectorAll(FORM)].filter((f) => f.getAttribute('action') === form.getAttribute('action'));
    const visible = new Set();
    for (const f of forms) {
        for (const el of f.elements) {
            if (el.name && el.type !== 'hidden' && el.type !== 'submit' && el.type !== 'button') visible.add(el.name);
        }
    }
    for (const f of forms) {
        for (const [key, value] of new FormData(f, f === form ? submitter : null)) {
            const el = f.elements.namedItem(key);
            const hidden = el instanceof HTMLInputElement && el.type === 'hidden';
            if (hidden && visible.has(key) && !(f === form && submitter?.name === key)) continue;
            add(key, value);
        }
    }
    url.search = params.toString();
    return url;
}

function regions() {
    return [...document.querySelectorAll('[data-ajax-region][id]')];
}

async function load(url, { push = true } = {}) {
    const targets = regions();
    if (!targets.length) {
        window.location.href = url;
        return;
    }

    controller?.abort();
    controller = new AbortController();
    targets.forEach((el) => { el.setAttribute('aria-busy', 'true'); el.classList.add('ajax-loading'); });

    try {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            credentials: 'same-origin',
            signal: controller.signal,
        });
        // Validation errors / auth redirects etc. → let the browser handle it normally.
        if (!res.ok || res.redirected) throw new Error('fallback');

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        for (const el of targets) {
            const fresh = doc.getElementById(el.id);
            if (!fresh) throw new Error('fallback');
            el.replaceWith(document.importNode(fresh, true));
        }
        if (push) window.history.replaceState({ ajaxFilter: true }, '', url);
    } catch (e) {
        if (e.name === 'AbortError') return; // a newer request replaced this one
        window.location.href = url;
    }
}

const isText = (t) => t instanceof HTMLInputElement && ['text', 'search'].includes(t.type);

// Old inline auto-submit handlers (onchange="this.form.submit()") would reload the page.
function stripInlineSubmit(root = document) {
    root.querySelectorAll(`${FORM} [onchange]`).forEach((el) => el.removeAttribute('onchange'));
}

function init() {
    if (!document.querySelector(FORM)) return;
    stripInlineSubmit();

    let timer = null;
    const run = (form, submitter = null) => {
        clearTimeout(timer);
        load(buildUrl(form, submitter)).then(() => stripInlineSubmit());
    };

    // Delegated, so forms swapped in by a reload (e.g. a per-page selector inside a region) keep working.
    document.addEventListener('input', (e) => {
        const form = e.target.closest?.(FORM);
        if (!form || !isText(e.target)) return;
        clearTimeout(timer);
        timer = setTimeout(() => run(form), DEBOUNCE_MS);
    });
    document.addEventListener('change', (e) => {
        const form = e.target.closest?.(FORM);
        if (!form || isText(e.target)) return; // text inputs are debounced above
        run(form);
    });
    document.addEventListener('submit', (e) => {
        const form = e.target.closest?.(FORM);
        if (!form) return;
        e.preventDefault();
        run(form, e.submitter);
    });

    // Pagination links inside a region load without a reload.
    document.addEventListener('click', (e) => {
        const a = e.target.closest('[data-ajax-region] nav[role="navigation"] a[href]');
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        load(new URL(a.href, window.location.origin)).then(() => {
            stripInlineSubmit();
            document.querySelector('[data-ajax-region]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
