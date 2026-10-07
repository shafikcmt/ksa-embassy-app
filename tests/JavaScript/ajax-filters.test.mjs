import test from 'node:test';
import assert from 'node:assert/strict';

// Only form/query APIs are needed; these tests run with Node and no extra packages.
let listForms = [];
const listeners = new Map();
globalThis.document = { querySelectorAll: () => listForms, addEventListener(name, handler) { listeners.set(name, handler); } };
globalThis.window = { addEventListener() {} };
globalThis.location = new URL('https://example.test/hr?search=old&status=active&page=4');
const { filterUrl } = await import('../../resources/js/ajax-filters.js');
function form(fields, action = 'https://example.test/hr') {
    const result = { action, dataset: { ajaxGroup: 'list' } };
    result.elements = fields.map(([name, value, type = 'text', disabled = false]) => ({ name, value, type, disabled, form: result }));
    return result;
}

test('HR visible fields beat stale hidden carry-forward values in either form', () => {
    const filters = form([['status', 'inactive', 'select-one'], ['search', 'stale', 'hidden'], ['per_page', '10', 'hidden']]);
    const toolbar = form([['status', 'active', 'hidden'], ['search', 'current'], ['per_page', '25', 'select-one']]);
    listForms = [filters, toolbar];
    for (const source of listForms) {
        const url = filterUrl('list', source);
        assert.equal(url.searchParams.get('status'), 'inactive');
        assert.equal(url.searchParams.get('search'), 'current');
        assert.equal(url.searchParams.get('per_page'), '25');
        assert.equal(url.searchParams.has('page'), false);
        assert.equal(url.searchParams.getAll('status').length, 1);
    }
});

test('clearing a visible search cannot resurrect its hidden value', () => {
    const filters = form([['search', 'stale', 'hidden']]);
    const toolbar = form([['search', '']]);
    listForms = [filters, toolbar];
    assert.equal(filterUrl('list', filters).searchParams.has('search'), false);
});

test('Visa direction submitter wins exactly once and ordinary submissions preserve direction', () => {
    location.href = 'https://example.test/hr?dir=asc&dir=desc&sort=name';
    const filters = form([['dir', 'asc', 'hidden'], ['q', 'Alpha']]);
    listForms = [filters];
    const toggled = filterUrl('list', filters, { name: 'dir', value: 'desc' });
    assert.deepEqual(toggled.searchParams.getAll('dir'), ['desc']);
    assert.equal(toggled.searchParams.get('sort'), 'name');
    assert.equal(filterUrl('list', filters).searchParams.get('dir'), 'asc');
});

test('the current form wins duplicate editable controls, disabled fields are omitted, and other groups are isolated', () => {
    location.href = 'https://example.test/hr?unknown=1&unknown=2';
    const first = form([['q', 'first'], ['ignored', 'disabled', 'text', true]]);
    const second = form([['q', 'second']]);
    const unrelated = form([['q', 'unrelated']]);
    unrelated.dataset.ajaxGroup = 'other';
    listForms = [first, second, unrelated];
    assert.equal(filterUrl('list', first).searchParams.get('q'), 'first');
    const url = filterUrl('list', second);
    assert.equal(url.searchParams.get('q'), 'second');
    assert.equal(url.searchParams.has('ignored'), false);
    assert.equal(url.searchParams.getAll('unknown').length, 1);
});

test('IME composition aborts stale requests and fetches only the committed input', async () => {
    const filters = form([['q', 'Alpha']]);
    filters.setAttribute = () => {};
    filters.removeAttribute = () => {};
    listForms = [filters];
    const field = filters.elements[0];
    field.closest = () => filters;
    field.matches = () => true;
    const requests = [];
    const originalFetch = globalThis.fetch;
    globalThis.fetch = (url, options) => {
        requests.push({ url, signal: options.signal });
        return new Promise((resolve, reject) => options.signal.addEventListener('abort', () => {
            reject(Object.assign(new Error('Aborted'), { name: 'AbortError' }));
        }));
    };
    const pause = () => new Promise(resolve => setTimeout(resolve, 350));
    try {
        listeners.get('input')({ target: field, type: 'input' });
        await pause();
        assert.equal(requests.length, 1);
        listeners.get('compositionstart')({ target: field, type: 'compositionstart' });
        assert.equal(requests[0].signal.aborted, true);
        field.value = 'Beta';
        listeners.get('input')({ target: field, type: 'input', isComposing: true });
        await pause();
        assert.equal(requests.length, 1);
        listeners.get('compositionend')({ target: field, type: 'compositionend' });
        await pause();
        assert.equal(requests.length, 2);
        assert.equal(requests[1].url.searchParams.get('q'), 'Beta');
    } finally {
        listeners.get('compositionstart')({ target: field, type: 'compositionstart' });
        globalThis.fetch = originalFetch;
    }
});
