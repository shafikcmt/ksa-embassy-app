/** Explicitly opted-in GET lists. HTML responses remain the source of truth. */
const formSelector = 'form[data-ajax-filter][data-ajax-group]';
const states = new Map();
const members = (selector, group, root = document) => [...root.querySelectorAll(selector)]
    .filter(el => el.dataset.ajaxGroup === group);
const forms = (group, root) => members(formSelector, group, root);
const regions = (group, root) => members('[data-ajax-region]', group, root);
const stateFor = group => {
    if (!states.has(group)) states.set(group, { version: 0, controller: null, timer: null });
    return states.get(group);
};

// Hidden carry-forward fields are fallbacks. Current controls and the submitter win.
export function filterUrl(group, source, submitter) {
    const url = new URL(source.action || location.href, location.href);
    url.search = location.search;
    const fields = forms(group).flatMap(form => [...form.elements]).filter(field =>
        field.name && !field.disabled && !['submit', 'button', 'reset', 'file'].includes(field.type));
    const values = new Map();
    for (const field of fields.filter(field => field.type === 'hidden')) values.set(field.name, field.value);
    // The triggering form wins if two editable controls share a name.
    const editable = fields.filter(field => field.type !== 'hidden');
    editable.sort((a, b) => Number(a.form === source) - Number(b.form === source));
    for (const field of editable) {
        values.set(field.name, ['checkbox', 'radio'].includes(field.type) && !field.checked ? '' : field.value);
    }
    if (submitter?.name) values.set(submitter.name, submitter.value);
    for (const [name, value] of values) {
        url.searchParams.delete(name);
        if (value !== '') url.searchParams.set(name, value);
    }
    url.searchParams.delete('page');
    // URLSearchParams.set also collapses duplicates carried in the current URL.
    for (const name of new Set(url.searchParams.keys())) url.searchParams.set(name, url.searchParams.get(name));
    return url;
}

function busy(group, value) {
    for (const el of [...forms(group), ...regions(group)]) {
        if (value) el.setAttribute('aria-busy', 'true');
        else el.removeAttribute('aria-busy');
    }
}

function invalidate(group) {
    const state = stateFor(group);
    clearTimeout(state.timer);
    state.controller?.abort();
    state.version++;
    busy(group, false);
    return state;
}

function replaceHtml(group, parsed) {
    const oldRegions = regions(group);
    const newRegions = regions(group, parsed);
    // Validate the whole response before mutating anything (including redirects).
    if (!oldRegions.length || oldRegions.length !== newRegions.length) throw Error('Missing list regions');
    const replacements = oldRegions.map(old => {
        const next = newRegions.find(el => el.dataset.ajaxRegion === old.dataset.ajaxRegion);
        if (!next) throw Error('Missing list region');
        return [old, next];
    });
    const oldForms = forms(group).filter(form => !oldRegions.some(region => region.contains(form)));
    const nextForms = forms(group, parsed).filter(form => !newRegions.some(region => region.contains(form)));
    if (oldForms.length !== nextForms.length) throw Error('Missing filter forms');
    oldForms.forEach((form, index) => replacements.push([form, nextForms[index]]));
    const active = document.activeElement;
    const activeForm = active?.closest(formSelector);
    const focus = activeForm?.dataset.ajaxGroup === group ? {
        index: forms(group).indexOf(activeForm), name: active.name,
        start: active.selectionStart, end: active.selectionEnd,
    } : null;
    const mutate = () => {
        for (const [old, next] of replacements) {
            const fresh = document.importNode(next, true);
            window.Alpine?.destroyTree(old);
            old.replaceWith(fresh);
            window.Alpine?.initTree(fresh);
        }
    };
    // Suppress Alpine's observer while manually destroying/initializing only new trees.
    if (window.Alpine) window.Alpine.mutateDom(mutate);
    else mutate();
    if (focus) {
        const field = [...(forms(group)[focus.index]?.elements || [])].find(el => el.name === focus.name && el.type !== 'hidden');
        field?.focus({ preventScroll: true });
        if (focus.start !== null && field?.setSelectionRange) field.setSelectionRange(focus.start, focus.end);
    }
}

async function navigate(group, url, historyMode = 'push') {
    const state = invalidate(group);
    const version = state.version;
    const controller = new AbortController();
    state.controller = controller;
    busy(group, true);
    try {
        const response = await fetch(url, {
            signal: controller.signal, credentials: 'same-origin',
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok || response.redirected || new URL(response.url || url, location.href).pathname !== url.pathname) throw Error('List request failed');
        const html = await response.text();
        if (state.version !== version) return;
        replaceHtml(group, new DOMParser().parseFromString(html, 'text/html'));
        if (historyMode !== 'none' && url.href !== location.href) {
            history[historyMode === 'replace' ? 'replaceState' : 'pushState']({ ...history.state, ajaxFilterGroup: group }, '', url);
        }
        document.dispatchEvent(new CustomEvent('ajax-filter:updated', { detail: { group, url: url.href } }));
    } catch (error) {
        if (state.version === version && error.name !== 'AbortError') location.assign(url.href);
    } finally {
        if (state.version === version) {
            state.controller = null;
            busy(group, false);
        }
    }
}

document.addEventListener('submit', event => {
    const form = event.target.closest(formSelector);
    if (!form || form.method.toLowerCase() !== 'get' || event.defaultPrevented) return;
    event.preventDefault();
    navigate(form.dataset.ajaxGroup, filterUrl(form.dataset.ajaxGroup, form, event.submitter));
});

function textInput(event) {
    const field = event.target;
    const form = field.closest(formSelector);
    if (!form || !field.name || !field.matches('input:not([type]), input[type="text"], input[type="search"], input[type="number"]')) return;
    const group = form.dataset.ajaxGroup;
    const state = invalidate(group); // Immediately prevent in-flight HTML from erasing new typing.
    if (event.isComposing || event.type === 'compositionstart') return;
    state.timer = setTimeout(() => navigate(group, filterUrl(group, form)), 300);
}
document.addEventListener('input', textInput);
document.addEventListener('compositionstart', textInput);
document.addEventListener('compositionend', textInput);

document.addEventListener('change', event => {
    const field = event.target;
    const form = field.closest(formSelector);
    if (!form || !field.name || !field.matches('select, input[type="date"], input[type="checkbox"], input[type="radio"]')) return;
    navigate(form.dataset.ajaxGroup, filterUrl(form.dataset.ajaxGroup, form));
});

document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download')) return;
    const region = link.closest('[data-ajax-region][data-ajax-group]');
    const explicit = link.hasAttribute('data-ajax-link');
    const url = new URL(link.href, location.href);
    if (!explicit && !(region && link.closest('nav') && url.searchParams.has('page'))) return;
    const group = link.dataset.ajaxGroup || link.closest('[data-ajax-group]')?.dataset.ajaxGroup;
    if (!group || url.origin !== location.origin || url.pathname !== location.pathname) return;
    event.preventDefault();
    // Merge pending edits into pagination; reset/status URLs are authoritative.
    if (!explicit && forms(group).length) {
        const merged = filterUrl(group, forms(group)[0]);
        merged.searchParams.set('page', url.searchParams.get('page'));
        navigate(group, merged);
        return;
    }
    navigate(group, url);
});

window.addEventListener('popstate', () => {
    const groups = new Set([...document.querySelectorAll('[data-ajax-region][data-ajax-group]')].map(el => el.dataset.ajaxGroup));
    for (const group of groups) navigate(group, new URL(location.href), 'none');
});

document.addEventListener('DOMContentLoaded', () => {
    const groups = [...new Set([...document.querySelectorAll('[data-ajax-region][data-ajax-group]')].map(el => el.dataset.ajaxGroup))];
    if (groups.length) history.replaceState({ ...history.state, ajaxFilterGroups: groups }, '', location.href);
});
