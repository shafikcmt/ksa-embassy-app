{{--
    Cross-module ERP auto-fill for a module modal's Passport field.

    Include it INSIDE the modal's Alpine component, right under the passport
    input. It watches the parent's passport value; once a full passport is typed
    (5–20 letters/digits, debounced) it calls erp.autofill, which searches
    Medical → MOFA → Stamping → BMET (HR profile only as fallback), then:
      • fills the parent's EMPTY form fields listed in $map (never overwrites),
        but only while the user is typing in the passport field — loading an
        Edit shows the tags without silently changing the saved record;
      • shows "Found in: Medical ✓ | MOFA ✓ | …" tags; clicking a tag expands
        the data that module holds, with a link to open that record.
    It emits `erp-autofill-result` (full payload) and `erp-autofilled`
    ({ keys }) so a modal can react (e.g. lock filled fields).

    Params:
      $module      medical | mofa | stamping | bmet   (this modal's own module)
      $passportKey parent form key holding the passport (passport_no / passport_number)
      $inputId     DOM id of the passport input (focus = "user is typing")
      $idKey       parent property with the id being edited (editId / id)
      $map         [canonical field => parent form key] to fill when empty
--}}
@php
    $erpCfg = [
        'module'      => $module,
        'passportKey' => $passportKey,
        'inputId'     => $inputId,
        'idKey'       => $idKey,
        'map'         => $map,
        'url'         => url('erp/autofill'),
        'labels'      => \App\Services\ErpPassportDataService::LABELS,
        'fieldLabels' => collect(array_keys(\App\Services\ErpPassportDataService::FIELD_PRIORITY))
            ->mapWithKeys(fn ($k) => [$k => \App\Services\ErpPassportDataService::fieldLabel($k)]),
    ];
@endphp
<div x-data="erpAutoFill(@js($erpCfg))" class="mt-1.5 text-xs" id="{{ $inputId }}-erp">
    <p x-show="erp.loading" class="flex items-center gap-1.5 text-gray-500" role="status">
        <i class="bi bi-arrow-repeat animate-spin text-blue-500" aria-hidden="true"></i> Searching ERP records…
    </p>
    <p x-show="erp.error" x-cloak class="text-amber-700"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> ERP search unavailable — enter details manually.</p>

    <div x-show="!erp.loading && erp.checked" x-cloak aria-live="polite">
        <div class="flex flex-wrap items-center gap-1">
            <span class="font-semibold text-gray-600">Found in:</span>
            <template x-for="(tag, i) in erpTags()" :key="tag.key">
                <span class="inline-flex items-center gap-1">
                    <span x-show="i > 0" class="text-gray-300" aria-hidden="true">|</span>
                    <button type="button" x-on:click="erpToggle(tag.key)" x-bind:disabled="!tag.found"
                            x-bind:aria-expanded="erp.open === tag.key ? 'true' : 'false'"
                            x-bind:aria-label="tag.found ? 'Linked to ' + tag.label + ' — show details' : 'Not found in ' + tag.label"
                            x-bind:title="tag.found ? 'Linked to ' + tag.label + ' — click to see its data' : 'No ' + tag.label + ' record for this passport'"
                            class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            x-bind:class="tag.found ? tag.tone + ' ring-1 ring-inset hover:brightness-95' : 'cursor-default text-gray-400'">
                        <span x-text="tag.label"></span>
                        <i class="bi" x-bind:class="tag.found ? 'bi-check-lg' : 'bi-dash'" aria-hidden="true"></i>
                    </button>
                </span>
            </template>
        </div>

        <p x-show="!erpFound()" class="mt-1 text-gray-500">No existing records for this passport — enter details manually.</p>
        {{-- Short summary; the per-field breakdown is in the tooltip / screen-reader text. --}}
        <p x-show="erp.filled.length" class="mt-1 text-emerald-700" x-bind:title="erpFilledDetail()">
            <i class="bi bi-magic" aria-hidden="true"></i>
            <span x-text="erpFilledSummary()"></span>
            <span class="sr-only" x-text="': ' + erpFilledDetail()"></span>
        </p>

        {{-- Expanded source details --}}
        <template x-if="erp.open && erp.sources[erp.open]">
            <div class="mt-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <span class="font-bold text-gray-800" x-text="'From ' + erp.sources[erp.open].label"></span>
                    <a x-bind:href="erp.sources[erp.open].url" target="_blank" rel="noopener" class="font-semibold text-blue-600 hover:underline">Open record <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
                </div>
                <dl class="grid grid-cols-[repeat(auto-fill,minmax(9rem,1fr))] gap-x-3 gap-y-1.5">
                    <template x-for="row in erp.sources[erp.open].display" :key="row[0]">
                        <div class="min-w-0"><dt class="text-gray-500" x-text="row[0]"></dt><dd class="truncate font-medium text-gray-800" x-text="row[1]" x-bind:title="row[1]"></dd></div>
                    </template>
                </dl>
            </div>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
    /*
     * Shared ERP auto-fill sub-component. Reads/writes the PARENT modal's state
     * through $data (Alpine's merged scope): f, touched, server and the edit id.
     */
    function erpAutoFill(cfg) {
        const TONES = {
            medical: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            mofa: 'bg-blue-50 text-blue-700 ring-blue-200',
            stamping: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            bmet: 'bg-blue-50 text-blue-700 ring-blue-200',
            hr: 'bg-purple-50 text-purple-700 ring-purple-200',
        };
        const PASSPORT = /^[A-Z0-9]{5,20}$/;

        return {
            erp: { loading: false, checked: false, error: false, sources: {}, open: null, filled: [], last: '', timer: null, version: 0, fillNext: false },

            init() {
                this.$watch('f.' + cfg.passportKey, value => this.erpQueue(value));
            },

            erpQueue(value) {
                const p = String(value ?? '').trim().toUpperCase();
                // Fill only when the change comes from the user typing in the passport field.
                const typing = document.activeElement && document.activeElement.id === cfg.inputId;
                clearTimeout(this.erp.timer);

                if (!PASSPORT.test(p)) {
                    this.erp.version++;
                    Object.assign(this.erp, { loading: false, checked: false, error: false, sources: {}, open: null, filled: [], last: '' });
                    return;
                }
                if (p === this.erp.last && !typing) return;
                this.erp.fillNext = this.erp.fillNext || typing;
                this.erp.timer = setTimeout(() => this.erpFetch(p), 400);
            },

            async erpFetch(p) {
                const version = ++this.erp.version;
                const scope = this.$data;
                const editId = scope[cfg.idKey];
                const qs = editId ? '?exclude=' + encodeURIComponent(cfg.module + ':' + editId) : '';
                const fill = this.erp.fillNext;
                this.erp.fillNext = false;
                Object.assign(this.erp, { loading: true, error: false, open: null });

                try {
                    const r = await fetch(cfg.url + '/' + encodeURIComponent(p) + qs, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!r.ok) throw new Error('lookup failed');
                    const data = await r.json();
                    if (version !== this.erp.version) return; // a newer passport was typed meanwhile

                    this.erp.sources = data.sources || {};
                    this.erp.last = p;
                    this.erp.checked = true;
                    this.erp.filled = fill ? this.erpFill(data.merged || {}) : [];
                    this.$dispatch('erp-autofill-result', data);
                    if (this.erp.filled.length) this.$dispatch('erp-autofilled', { keys: this.erp.filled.map(x => x.key) });
                } catch (e) {
                    if (version === this.erp.version) Object.assign(this.erp, { error: true, checked: false });
                } finally {
                    if (version === this.erp.version) this.erp.loading = false;
                }
            },

            /** Copy merged values into the parent's EMPTY fields only. */
            erpFill(merged) {
                const scope = this.$data;
                const filled = [];
                for (const [canonical, key] of Object.entries(cfg.map)) {
                    const m = merged[canonical];
                    if (!m || m.value === null || m.value === '') continue;
                    if (String(scope.f[key] ?? '').trim() !== '') continue;
                    scope.f[key] = m.value;
                    if (scope.touched) scope.touched[key] = true;
                    if (scope.server && scope.server[key]) delete scope.server[key];
                    filled.push({ key, label: cfg.fieldLabels[canonical] || key, source: cfg.labels[m.source] || m.source });
                }
                return filled;
            },

            erpTags() {
                const keys = ['medical', 'mofa', 'stamping', 'bmet'];
                if (this.erp.sources.hr) keys.push('hr');
                return keys.map(key => ({ key, label: cfg.labels[key], found: !!this.erp.sources[key], tone: TONES[key] }));
            },
            erpFound() { return Object.keys(this.erp.sources).length > 0; },
            erpFilledSummary() {
                const n = this.erp.filled.length;
                const from = [...new Set(this.erp.filled.map(x => x.source))].join(', ');
                return 'Auto-filled ' + n + ' field' + (n === 1 ? '' : 's') + ' from ' + from;
            },
            erpFilledDetail() { return this.erp.filled.map(x => x.label + ' (' + x.source + ')').join(', '); },
            erpToggle(key) { if (this.erp.sources[key]) this.erp.open = this.erp.open === key ? null : key; },
        };
    }
</script>
@endpush
@endonce
