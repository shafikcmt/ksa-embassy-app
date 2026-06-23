{{--
    Shared embassy-list builder — used by create + edit.
    Expects: $agents, $availableHr, optionally $embassyList + $selectedItems (hr_id => category),
             $listedHrIds (collection of hr ids already in another active list), $mode.
    Submission contract (must match EmbassyListController): items[i][hr_profile_id] + items[i][category],
    built as hidden inputs by JS. The visible category <select> is intentionally name-less.
--}}
@php
    $embassyList   = $embassyList ?? null;
    $mode          = $mode ?? ($embassyList ? 'edit' : 'create');
    $selectedItems = $selectedItems ?? collect();
    $listedHrIds   = ($listedHrIds ?? collect());
    $listedHrIds   = $listedHrIds instanceof \Illuminate\Support\Collection ? $listedHrIds : collect($listedHrIds);
    $saveLabel     = $mode === 'edit' ? 'Update Draft' : 'Save as Draft';
    $cancelUrl     = $mode === 'edit' ? route('embassy-lists.show', $embassyList) : route('embassy-lists.index');
    $preSelected   = $selectedItems instanceof \Illuminate\Support\Collection ? $selectedItems->toArray() : (array) $selectedItems;
    $nationalities = $availableHr->pluck('nationality')->filter()->unique()->sort()->values();
    $statusLabels  = ['active' => 'Active', 'inactive' => 'Inactive', 'listed' => 'Listed'];
    $categories    = ['new' => 'New', 'restamping' => 'Re-stamping', 'cancellation' => 'Cancellation'];
    $inp = 'h-10 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400';
@endphp

{{-- ── Workflow hint strip ─────────────────────────────────────── --}}
<div class="mb-5 hidden items-center gap-1 overflow-x-auto rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-medium text-slate-400 shadow-soft md:flex">
    @foreach(['Enter details', 'Find & filter', 'Select candidates', 'Assign category', 'Review & save'] as $i => $step)
        <span class="flex items-center gap-1.5 whitespace-nowrap {{ $i === 0 ? 'text-brand-600' : '' }}">
            <span class="grid h-5 w-5 place-items-center rounded-full {{ $i === 0 ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-500' }} text-[0.65rem] font-bold">{{ $i + 1 }}</span>
            {{ $step }}
        </span>
        @if(! $loop->last)<i class="bi bi-chevron-right mx-1 text-[0.6rem] text-slate-300"></i>@endif
    @endforeach
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    {{-- ── Left: details + picker ─────────────────────────────── --}}
    <div class="space-y-5 lg:col-span-2">

        {{-- List details --}}
        <x-ui.card class="p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-800"><span class="grid h-6 w-6 place-items-center rounded-lg bg-brand-50 text-brand-600"><i class="bi bi-info-circle text-xs"></i></span> List Details</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.field label="List Date" name="list_date" :required="true">
                    <input type="date" name="list_date" required value="{{ old('list_date', $embassyList?->list_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $inp }} field-watch @error('list_date') !border-rose-400 @enderror">
                </x-ui.field>
                <x-ui.field label="Title" name="title" hint="Optional — helps you find this list later" class="sm:col-span-2">
                    <input type="text" name="title" value="{{ old('title', $embassyList?->title) }}" placeholder="e.g. First batch January 2026" class="{{ $inp }} field-watch">
                </x-ui.field>
                <x-ui.field label="Notes" name="notes" hint="Optional" class="sm:col-span-3">
                    <textarea name="notes" rows="2" placeholder="Any internal note about this batch…" class="field-watch w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400">{{ old('notes', $embassyList?->notes) }}</textarea>
                </x-ui.field>
            </div>
        </x-ui.card>

        {{-- Quick add by passport --}}
        <x-ui.card class="p-5">
            <h2 class="mb-1 flex items-center gap-2 text-sm font-bold text-slate-800"><span class="grid h-6 w-6 place-items-center rounded-lg bg-violet-50 text-violet-600"><i class="bi bi-upc-scan text-xs"></i></span> Quick Add by Passport</h2>
            <p class="mb-3 text-xs text-slate-400">Scan or type a passport number and press Enter to find the candidate instantly.</p>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <i class="bi bi-upc absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="passportLookupInput" maxlength="50" placeholder="Enter passport number…" class="h-10 w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400">
                </div>
                <button type="button" id="passportLookupBtn" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"><i class="bi bi-search"></i> Find</button>
            </div>
            <div id="passportLookupResult" class="mt-3 hidden text-sm"></div>
        </x-ui.card>

        {{-- Candidate selection --}}
        <x-ui.card class="overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><span class="grid h-6 w-6 place-items-center rounded-lg bg-emerald-50 text-emerald-600"><i class="bi bi-people text-xs"></i></span> Select Candidates</h2>
                <span id="selectedCount" class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">0 selected</span>
            </div>

            {{-- Filters --}}
            <div class="border-b border-slate-100 bg-slate-50/60 p-4">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-12">
                    <div class="relative sm:col-span-12 lg:col-span-4">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="text" id="hrSearch" placeholder="Search name, passport, visa, nationality…" class="h-10 w-full rounded-lg border-slate-300 pl-9 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <select id="agentFilter" class="{{ $inp }} sm:col-span-4 lg:col-span-2">
                        <option value="">All Agents</option>
                        @foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
                    </select>
                    <select id="nationalityFilter" class="{{ $inp }} sm:col-span-4 lg:col-span-2">
                        <option value="">All Nationalities</option>
                        @foreach($nationalities as $nat)<option value="{{ strtolower($nat) }}">{{ $nat }}</option>@endforeach
                    </select>
                    <select id="statusFilter" class="{{ $inp }} sm:col-span-4 lg:col-span-2">
                        <option value="">All Statuses</option>
                        @foreach($statusLabels as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                    </select>
                    <select id="sortSelect" class="{{ $inp }} sm:col-span-4 lg:col-span-2">
                        <option value="name-asc">Name A–Z</option>
                        <option value="name-desc">Name Z–A</option>
                        <option value="nationality">By nationality</option>
                    </select>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <button type="button" id="selectAllFiltered" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-brand-200 bg-brand-50 px-3 text-xs font-semibold text-brand-700 transition hover:bg-brand-100"><i class="bi bi-check2-all"></i> Select all filtered</button>
                    <button type="button" id="clearSelection" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"><i class="bi bi-x-circle"></i> Clear selection</button>
                    <button type="button" id="clearFilters" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:bg-slate-50"><i class="bi bi-funnel"></i> Reset filters</button>
                    <span id="visibleCount" class="ml-auto text-xs text-slate-400"></span>
                </div>
            </div>

            {{-- Bulk action toolbar (shown when ≥1 selected) --}}
            <div id="bulkBar" class="hidden items-center gap-2 border-b border-brand-100 bg-brand-50/70 px-4 py-2.5">
                <span class="text-xs font-semibold text-brand-700"><span id="bulkCount">0</span> selected</span>
                <span class="hidden text-xs text-slate-400 sm:inline">· set category for all selected:</span>
                <select id="bulkCategory" class="h-8 rounded-lg border-slate-300 text-xs focus:border-brand-400 focus:ring-brand-400">
                    @foreach($categories as $val => $label)<option value="{{ $val }}">{{ $label }}</option>@endforeach
                </select>
                <button type="button" id="bulkApply" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-brand-600 px-3 text-xs font-semibold text-white transition hover:bg-brand-700"><i class="bi bi-lightning-charge"></i> Apply</button>
                <button type="button" id="bulkRemove" class="ml-auto inline-flex h-8 items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"><i class="bi bi-trash3"></i> Remove selected</button>
            </div>

            <div class="p-4">
                @error('items')
                    <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"><i class="bi bi-exclamation-circle mr-1"></i>{{ $message }}</div>
                @enderror

                <div class="max-h-[520px] overflow-auto rounded-xl border border-slate-200">
                    <table class="w-full text-sm" id="hrTable">
                        <thead class="sticky top-0 z-10 bg-slate-50 shadow-[0_1px_0_0_theme(colors.slate.200)]">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="w-10 px-3 py-2.5">
                                    <input type="checkbox" id="selectAllVisible" title="Select all visible" class="h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                </th>
                                <th class="px-3 py-2.5">Candidate</th>
                                <th class="px-3 py-2.5">Passport #</th>
                                <th class="px-3 py-2.5">Visa #</th>
                                <th class="px-3 py-2.5">Agent</th>
                                <th class="px-3 py-2.5">Category</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($availableHr as $hr)
                                @php
                                    $preCategory = $preSelected[$hr->id] ?? null;
                                    $hasPassport = (bool) ($hr->passport?->passport_number);
                                    $inOtherList = $listedHrIds->contains($hr->id);
                                @endphp
                                <tr class="hr-row cursor-pointer align-top transition hover:bg-slate-50 {{ $preCategory ? 'bg-brand-50' : '' }}"
                                    data-id="{{ $hr->id }}"
                                    data-name="{{ strtolower($hr->full_name_en) }}"
                                    data-nationality="{{ strtolower($hr->nationality) }}"
                                    data-passport="{{ strtolower($hr->passport?->passport_number ?? '') }}"
                                    data-visa="{{ strtolower($hr->visa?->visa_number ?? '') }}"
                                    data-status="{{ $hr->status }}"
                                    data-agent-id="{{ $hr->agent_id ?? '' }}">
                                    <td class="px-3 py-3">
                                        <input type="checkbox" class="hr-checkbox h-4 w-4 cursor-pointer rounded border-slate-300 text-brand-600 focus:ring-brand-500" data-hr-id="{{ $hr->id }}" {{ $preCategory ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-semibold text-slate-800" data-cand-name>{{ $hr->full_name_en }}</div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-slate-400">
                                            <span>{{ $hr->nationality }}</span>
                                            @if($inOtherList)
                                                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[0.6rem] font-semibold text-amber-700"><i class="bi bi-list-check"></i> In a list</span>
                                            @endif
                                            @if(! $hasPassport)
                                                <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[0.6rem] font-semibold text-rose-700"><i class="bi bi-exclamation-triangle"></i> No passport</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-3"><span class="font-mono text-xs text-slate-600">{{ $hr->passport?->passport_number ?? '—' }}</span></td>
                                    <td class="px-3 py-3 text-slate-500">{{ $hr->visa?->visa_number ?? '—' }}</td>
                                    <td class="px-3 py-3 text-slate-500">{{ $hr->agent?->name ?? '—' }}</td>
                                    <td class="px-3 py-3">
                                        <select class="category-select h-8 rounded-lg border-slate-300 text-xs focus:border-brand-400 focus:ring-brand-400 {{ $preCategory ? '' : 'hidden' }}">
                                            @foreach($categories as $val => $label)
                                                <option value="{{ $val }}" {{ $preCategory === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-10 text-center text-sm text-slate-400"><i class="bi bi-people mb-1 block text-2xl opacity-40"></i>No available HR profiles. <a href="{{ route('hr.create') }}" class="font-semibold text-brand-600">Add HR profiles</a> first.</td></tr>
                            @endforelse
                            <tr id="noResultsRow" class="hidden"><td colspan="6" class="px-3 py-10 text-center text-sm text-slate-400"><i class="bi bi-search mb-1 block text-2xl opacity-40"></i>No candidates match your filters.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- ── Right: selected summary (sticky) ───────────────────── --}}
    <div class="lg:col-span-1">
        <x-ui.card class="overflow-hidden lg:sticky lg:top-20">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><span class="grid h-6 w-6 place-items-center rounded-lg bg-brand-50 text-brand-600"><i class="bi bi-clipboard-check text-xs"></i></span> Selected</h2>
                <button type="button" id="clearAllBtn" class="hidden text-xs font-semibold text-slate-400 transition hover:text-rose-600">Clear all</button>
            </div>

            {{-- Summary chips --}}
            <div class="grid grid-cols-4 gap-px bg-slate-100 text-center">
                <div class="bg-white py-2.5">
                    <div class="text-lg font-extrabold leading-none text-emerald-600" id="countNew">0</div>
                    <div class="mt-0.5 text-[0.62rem] font-semibold uppercase tracking-wide text-slate-400">New</div>
                </div>
                <div class="bg-white py-2.5">
                    <div class="text-lg font-extrabold leading-none text-brand-600" id="countRestamp">0</div>
                    <div class="mt-0.5 text-[0.62rem] font-semibold uppercase tracking-wide text-slate-400">Re-stamp</div>
                </div>
                <div class="bg-white py-2.5">
                    <div class="text-lg font-extrabold leading-none text-rose-600" id="countCancel">0</div>
                    <div class="mt-0.5 text-[0.62rem] font-semibold uppercase tracking-wide text-slate-400">Cancel</div>
                </div>
                <div class="bg-white py-2.5">
                    <div class="text-lg font-extrabold leading-none text-slate-800" id="countTotal">0</div>
                    <div class="mt-0.5 text-[0.62rem] font-semibold uppercase tracking-wide text-slate-400">Total</div>
                </div>
            </div>

            <div id="selectedList" class="max-h-[360px] overflow-y-auto border-t border-slate-100">
                <div id="emptyMsg" class="px-5 py-10 text-center text-sm text-slate-400">
                    <i class="bi bi-person-plus mb-1 block text-2xl opacity-40"></i>
                    Check candidates to add them here
                </div>
            </div>

            <div class="border-t border-slate-100 p-4">
                <button type="submit" id="saveBtn" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 text-sm font-semibold text-white shadow-sm shadow-brand-600/30 transition hover:shadow-md disabled:pointer-events-none disabled:opacity-50">
                    <i class="bi bi-floppy"></i> <span id="saveBtnLabel">{{ $saveLabel }}</span>
                </button>
                <a href="{{ $cancelUrl }}" id="cancelLink" class="mt-2 inline-flex h-10 w-full items-center justify-center rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</a>
                <p class="mt-2 text-center text-[0.68rem] text-slate-400">Saved as a <strong>draft</strong> — finalize later to submit to the embassy.</p>
            </div>
        </x-ui.card>
    </div>
</div>

{{-- Mobile sticky action bar --}}
<div id="mobileBar" class="fixed inset-x-0 bottom-0 z-30 hidden border-t border-slate-200 bg-white/95 p-3 shadow-[0_-4px_16px_-6px_rgba(15,23,42,.15)] backdrop-blur lg:hidden">
    <div class="flex items-center gap-3">
        <div class="text-sm"><span class="font-bold text-slate-900" id="mobileCount">0</span> <span class="text-slate-400">selected</span></div>
        <button type="submit" id="mobileSaveBtn" class="ml-auto inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 text-sm font-semibold text-white shadow-sm disabled:opacity-50">
            <i class="bi bi-floppy"></i> {{ $saveLabel }}
        </button>
    </div>
</div>
<div class="h-16 lg:hidden"></div>

<div id="hiddenInputs"></div>

{{-- Toast container --}}
<div id="toastWrap" class="fixed right-4 top-20 z-[60] flex w-72 flex-col gap-2"></div>

@push('scripts')
<script>
(function () {
    const selected     = {};
    const preSelected  = @json($preSelected);
    const selectedList = document.getElementById('selectedList');
    const emptyMsg     = document.getElementById('emptyMsg');
    const hiddenInputs = document.getElementById('hiddenInputs');
    const saveBtn      = document.getElementById('saveBtn');
    const mobileSave   = document.getElementById('mobileSaveBtn');
    const mobileBar    = document.getElementById('mobileBar');
    const bulkBar      = document.getElementById('bulkBar');
    const form         = document.getElementById('embassyForm');
    let   dirty        = false;

    const catLabel = c => ({ new: 'New', restamping: 'Re-stamping', cancellation: 'Cancellation' }[c] || c);
    const catBadge = c => ({ new: 'bg-emerald-50 text-emerald-700', restamping: 'bg-brand-50 text-brand-700', cancellation: 'bg-rose-50 text-rose-700' }[c] || 'bg-slate-100 text-slate-600');

    // ── Toast ────────────────────────────────────────────────────
    const toastWrap = document.getElementById('toastWrap');
    function toast(msg, type = 'success') {
        const tones = {
            success: ['border-emerald-200 bg-emerald-50 text-emerald-800', 'bi-check-circle-fill text-emerald-500'],
            error:   ['border-rose-200 bg-rose-50 text-rose-800', 'bi-x-circle-fill text-rose-500'],
            info:    ['border-brand-200 bg-brand-50 text-brand-800', 'bi-info-circle-fill text-brand-500'],
        }[type] || ['border-slate-200 bg-white text-slate-800', 'bi-info-circle'];
        const el = document.createElement('div');
        el.className = 'flex items-start gap-2 rounded-xl border px-3 py-2.5 text-sm shadow-card transition-all duration-300 ' + tones[0];
        el.style.opacity = '0'; el.style.transform = 'translateX(1rem)';
        el.innerHTML = '<i class="bi ' + tones[1] + ' mt-0.5"></i><span class="flex-1">' + msg + '</span>';
        toastWrap.appendChild(el);
        requestAnimationFrame(() => { el.style.opacity = '1'; el.style.transform = 'translateX(0)'; });
        setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(1rem)'; setTimeout(() => el.remove(), 300); }, 2600);
    }

    // ── Core state sync ──────────────────────────────────────────
    function updateCounts() {
        const counts = { new: 0, restamping: 0, cancellation: 0 };
        let total = 0;
        Object.values(selected).forEach(i => { counts[i.category] = (counts[i.category] || 0) + 1; total++; });
        document.getElementById('selectedCount').textContent = total + ' selected';
        document.getElementById('countNew').textContent = counts.new;
        document.getElementById('countRestamp').textContent = counts.restamping;
        document.getElementById('countCancel').textContent = counts.cancellation;
        document.getElementById('countTotal').textContent = total;
        document.getElementById('bulkCount').textContent = total;
        document.getElementById('mobileCount').textContent = total;
        if (saveBtn)    saveBtn.disabled = total === 0;
        if (mobileSave) mobileSave.disabled = total === 0;
        emptyMsg.style.display = total === 0 ? 'block' : 'none';
        document.getElementById('clearAllBtn').classList.toggle('hidden', total === 0);
        bulkBar.classList.toggle('hidden', total === 0);
        bulkBar.classList.toggle('flex', total > 0);
        mobileBar.classList.toggle('hidden', total === 0);
        syncSelectAllState();
    }

    function renderSelectedList() {
        selectedList.querySelectorAll('.selected-item').forEach(el => el.remove());
        Object.entries(selected).forEach(([hrId, item]) => {
            const div = document.createElement('div');
            div.className = 'selected-item flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5 last:border-0';
            div.innerHTML =
                '<div class="min-w-0">' +
                    '<div class="truncate text-sm font-semibold text-slate-700">' + item.name + '</div>' +
                    '<span class="mt-1 inline-block rounded-full px-2 py-0.5 text-[0.65rem] font-semibold ' + catBadge(item.category) + '">' + catLabel(item.category) + '</span>' +
                '</div>' +
                '<button type="button" class="remove-btn grid h-7 w-7 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-500" title="Remove" data-hr-id="' + hrId + '"><i class="bi bi-x-lg text-xs"></i></button>';
            selectedList.insertBefore(div, emptyMsg);
        });
    }

    function rebuildHiddenInputs() {
        hiddenInputs.innerHTML = '';
        let i = 0;
        Object.entries(selected).forEach(([hrId, item]) => {
            hiddenInputs.innerHTML +=
                '<input type="hidden" name="items[' + i + '][hr_profile_id]" value="' + hrId + '">' +
                '<input type="hidden" name="items[' + i + '][category]" value="' + item.category + '">';
            i++;
        });
    }

    function sync() { updateCounts(); renderSelectedList(); rebuildHiddenInputs(); }

    function markDirty() { dirty = true; }

    // ── Row helpers ──────────────────────────────────────────────
    function rowFor(hrId)  { return document.querySelector('.hr-row[data-id="' + hrId + '"]'); }
    function cbFor(hrId)   { return document.querySelector('.hr-checkbox[data-hr-id="' + hrId + '"]'); }

    function selectRow(hrId, category) {
        const row = rowFor(hrId);
        if (!row) return;
        const cb = row.querySelector('.hr-checkbox');
        const catSelect = row.querySelector('.category-select');
        const name = row.querySelector('[data-cand-name]').textContent.trim();
        cb.checked = true;
        row.classList.add('bg-brand-50');
        catSelect.classList.remove('hidden');
        if (category) catSelect.value = category;
        selected[hrId] = { name, category: catSelect.value };
        markDirty();
    }

    function deselectRow(hrId) {
        const row = rowFor(hrId);
        if (row) {
            row.querySelector('.hr-checkbox').checked = false;
            row.classList.remove('bg-brand-50');
            row.querySelector('.category-select').classList.add('hidden');
        }
        delete selected[hrId];
        markDirty();
    }

    // wire up every row
    document.querySelectorAll('.hr-row').forEach(row => {
        const hrId      = row.dataset.id;
        const cb        = row.querySelector('.hr-checkbox');
        const catSelect = row.querySelector('.category-select');
        const name      = row.querySelector('[data-cand-name]').textContent.trim();

        if (preSelected[hrId]) selected[hrId] = { name, category: preSelected[hrId] };

        catSelect.addEventListener('change', function () {
            if (selected[hrId]) { selected[hrId].category = this.value; markDirty(); sync(); }
        });

        cb.addEventListener('change', function () {
            if (this.checked) selectRow(hrId, catSelect.value);
            else deselectRow(hrId);
            sync();
        });

        row.addEventListener('click', function (e) {
            if (e.target.closest('select') || e.target.closest('input')) return;
            cb.checked = !cb.checked;
            cb.dispatchEvent(new Event('change'));
        });
    });

    // remove from selected panel
    selectedList.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-btn');
        if (!btn) return;
        deselectRow(btn.dataset.hrId);
        sync();
    });

    // ── Filtering / sorting ──────────────────────────────────────
    const tbody       = document.querySelector('#hrTable tbody');
    const searchEl    = document.getElementById('hrSearch');
    const agentEl     = document.getElementById('agentFilter');
    const natEl       = document.getElementById('nationalityFilter');
    const statusEl    = document.getElementById('statusFilter');
    const sortEl      = document.getElementById('sortSelect');
    const noResults   = document.getElementById('noResultsRow');
    const visibleCntEl= document.getElementById('visibleCount');

    function rowMatches(row) {
        const search = searchEl.value.toLowerCase().trim();
        const agentId = agentEl.value, nat = natEl.value, status = statusEl.value;
        const matchSearch = !search ||
            (row.dataset.name || '').includes(search) ||
            (row.dataset.nationality || '').includes(search) ||
            (row.dataset.passport || '').includes(search) ||
            (row.dataset.visa || '').includes(search);
        const matchAgent  = !agentId || row.dataset.agentId === agentId;
        const matchNat    = !nat || row.dataset.nationality === nat;
        const matchStatus = !status || row.dataset.status === status;
        return matchSearch && matchAgent && matchNat && matchStatus;
    }

    function applyFilter() {
        let visible = 0;
        document.querySelectorAll('.hr-row').forEach(row => {
            const show = rowMatches(row);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        noResults.classList.toggle('hidden', visible > 0 || !document.querySelector('.hr-row'));
        visibleCntEl.textContent = visible ? (visible + ' shown') : '';
        syncSelectAllState();
    }

    function applySort() {
        const mode = sortEl.value;
        const rows = Array.from(document.querySelectorAll('.hr-row'));
        rows.sort((a, b) => {
            if (mode === 'name-desc')   return (b.dataset.name || '').localeCompare(a.dataset.name || '');
            if (mode === 'nationality') return (a.dataset.nationality || '').localeCompare(b.dataset.nationality || '') || (a.dataset.name || '').localeCompare(b.dataset.name || '');
            return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        });
        rows.forEach(r => tbody.insertBefore(r, noResults));
    }

    [searchEl, agentEl, natEl, statusEl].forEach(el => el && el.addEventListener('input', applyFilter));
    [agentEl, natEl, statusEl].forEach(el => el && el.addEventListener('change', applyFilter));
    sortEl.addEventListener('change', () => { applySort(); applyFilter(); });

    document.getElementById('clearFilters').addEventListener('click', function () {
        searchEl.value = ''; agentEl.value = ''; natEl.value = ''; statusEl.value = '';
        applyFilter();
        toast('Filters reset', 'info');
    });

    // ── Select-all / bulk ────────────────────────────────────────
    const selectAllVisible = document.getElementById('selectAllVisible');

    function visibleRows() {
        return Array.from(document.querySelectorAll('.hr-row')).filter(r => r.style.display !== 'none');
    }

    function syncSelectAllState() {
        const rows = visibleRows();
        const checkedCount = rows.filter(r => r.querySelector('.hr-checkbox').checked).length;
        selectAllVisible.checked = rows.length > 0 && checkedCount === rows.length;
        selectAllVisible.indeterminate = checkedCount > 0 && checkedCount < rows.length;
    }

    function selectAll(rows) {
        let added = 0;
        rows.forEach(row => {
            const cb = row.querySelector('.hr-checkbox');
            if (!cb.checked) { selectRow(row.dataset.id, row.querySelector('.category-select').value); added++; }
        });
        sync();
        return added;
    }

    selectAllVisible.addEventListener('change', function () {
        const rows = visibleRows();
        if (this.checked) {
            const n = selectAll(rows);
            if (n) toast(n + ' candidate(s) added', 'success');
        } else {
            rows.forEach(row => deselectRow(row.dataset.id));
            sync();
        }
    });

    document.getElementById('selectAllFiltered').addEventListener('click', function () {
        const n = selectAll(visibleRows());
        toast(n ? (n + ' candidate(s) added') : 'All filtered candidates already selected', n ? 'success' : 'info');
    });

    document.getElementById('clearSelection').addEventListener('click', clearAll);
    document.getElementById('clearAllBtn').addEventListener('click', clearAll);

    function clearAll() {
        const n = Object.keys(selected).length;
        if (!n) return;
        Object.keys(selected).slice().forEach(deselectRow);
        sync();
        toast(n + ' candidate(s) removed', 'info');
    }

    // bulk category apply
    document.getElementById('bulkApply').addEventListener('click', function () {
        const cat = document.getElementById('bulkCategory').value;
        const ids = Object.keys(selected);
        if (!ids.length) return;
        ids.forEach(hrId => {
            selected[hrId].category = cat;
            const row = rowFor(hrId);
            if (row) row.querySelector('.category-select').value = cat;
        });
        markDirty(); sync();
        toast(ids.length + ' set to ' + catLabel(cat), 'success');
    });

    document.getElementById('bulkRemove').addEventListener('click', clearAll);

    // ── Passport lookup ──────────────────────────────────────────
    const lookupInput  = document.getElementById('passportLookupInput');
    const lookupBtn    = document.getElementById('passportLookupBtn');
    const lookupResult = document.getElementById('passportLookupResult');

    function note(cls, html) { lookupResult.className = 'mt-3 rounded-lg border px-3 py-2 text-sm ' + cls; lookupResult.innerHTML = html; }

    function runPassportLookup() {
        const passportNo = lookupInput.value.trim();
        if (!passportNo) return;
        lookupResult.classList.remove('hidden');
        lookupBtn.disabled = true;
        note('border-slate-200 bg-slate-50 text-slate-500', '<i class="bi bi-arrow-repeat mr-1 animate-spin"></i>Searching…');

        fetch('{{ route("hr.lookup-by-passport") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ passport_no: passportNo })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.found) { note('border-amber-200 bg-amber-50 text-amber-700', '<i class="bi bi-exclamation-circle mr-1"></i>' + data.message); return; }
            if (selected[data.id]) { note('border-brand-200 bg-brand-50 text-brand-700', '<i class="bi bi-info-circle mr-1"></i><strong>' + data.full_name_en + '</strong> is already selected.'); return; }
            const onPage = !!rowFor(data.id);
            lookupResult.className = 'mt-3';
            lookupResult.innerHTML =
                '<div class="rounded-xl border border-slate-200 bg-white p-3 text-sm shadow-soft">' +
                    '<div class="flex flex-wrap items-start justify-between gap-3">' +
                        '<div class="min-w-0">' +
                            '<strong>' + data.full_name_en + '</strong>' + (data.full_name_ar ? ' <span class="text-slate-400">' + data.full_name_ar + '</span>' : '') +
                            ' <span class="text-slate-400">' + (data.nationality || '') + '</span>' +
                            '<div class="text-xs text-slate-400">Passport: ' + (data.passport_no || '—') + ' · Visa: ' + (data.visa_no || '—') + ' · Agent: ' + (data.agent_name || '—') + '</div>' +
                            (onPage ? '' : '<div class="mt-1 text-[0.7rem] font-semibold text-amber-600"><i class="bi bi-exclamation-triangle"></i> Not in the list below — will be added directly.</div>') +
                        '</div>' +
                        '<div class="flex shrink-0 items-center gap-2">' +
                            '<select id="lookupCategory" class="h-9 rounded-lg border-slate-300 text-xs focus:border-brand-400 focus:ring-brand-400">' +
                                '<option value="new">New</option><option value="restamping">Re-stamping</option><option value="cancellation">Cancellation</option>' +
                            '</select>' +
                            '<button type="button" id="addFoundBtn" class="inline-flex h-9 items-center gap-1 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700" data-hr-id="' + data.id + '" data-name="' + data.full_name_en.replace(/"/g, '&quot;') + '"><i class="bi bi-plus-lg"></i> Add</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            document.getElementById('addFoundBtn').addEventListener('click', function () {
                const hrId = this.dataset.hrId, name = this.dataset.name, cat = document.getElementById('lookupCategory').value;
                if (rowFor(hrId)) {
                    selectRow(hrId, cat);
                } else {
                    selected[hrId] = { name, category: cat };  // off-page candidate
                    markDirty();
                }
                sync();
                note('border-emerald-200 bg-emerald-50 text-emerald-700', '<i class="bi bi-check-circle mr-1"></i><strong>' + name + '</strong> added as ' + catLabel(cat) + '.');
                toast(name + ' added', 'success');
                lookupInput.value = '';
                lookupInput.focus();
            });
        })
        .catch(() => note('border-rose-200 bg-rose-50 text-rose-700', '<i class="bi bi-x-circle mr-1"></i>Lookup failed. Please try again.'))
        .finally(() => { lookupBtn.disabled = false; });
    }

    lookupBtn.addEventListener('click', runPassportLookup);
    lookupInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); runPassportLookup(); } });

    // ── Unsaved-changes guard ────────────────────────────────────
    document.querySelectorAll('.field-watch').forEach(el => el.addEventListener('input', markDirty));

    form.addEventListener('submit', function () {
        dirty = false;                         // allow navigation away after save
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Saving…';
        if (mobileSave) mobileSave.disabled = true;
    });

    document.getElementById('cancelLink').addEventListener('click', function (e) {
        if (dirty && !confirm('Discard your changes? Selected candidates will be lost.')) e.preventDefault();
    });

    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

    // ── Init ─────────────────────────────────────────────────────
    applySort();
    applyFilter();
    sync();
    dirty = false;   // pre-selected (edit) state isn't "dirty"
})();
</script>
@endpush
