@extends('layouts.agency')
@section('title', 'Edit Embassy List')
@section('page-title', 'Edit Embassy List')

@push('styles')
<style>
    .hr-row { cursor: pointer; }
    .hr-row:hover { background: #f0f7ff; }
    .hr-row.selected { background: #e0f0ff; }
    .category-select { display: none; }
    .selected .category-select { display: block; }
    #selectedPanel { position: sticky; top: 70px; }
    .in-list-badge { font-size: .65rem; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('embassy-lists.show', $embassyList) }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Edit: {{ $embassyList->list_no }}</h5>
    <span class="badge bg-warning text-dark">Draft</span>
</div>

<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-info-circle me-1"></i>
    You are editing a <strong>draft</strong> list. Changes are not submitted to the embassy until you finalize.
</div>

<form method="POST" action="{{ route('embassy-lists.update', $embassyList) }}" id="embassyForm">
    @csrf @method('PUT')

    @php
        $preSelected = $selectedItems->toArray(); // hr_profile_id => category
    @endphp

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Header fields --}}
            <div class="card mb-3">
                <div class="card-header py-2"><i class="bi bi-info-circle me-1"></i> List Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">List Date <span class="text-danger">*</span></label>
                            <input type="date" name="list_date" class="form-control @error('list_date') is-invalid @enderror"
                                value="{{ old('list_date', $embassyList->list_date->format('Y-m-d')) }}" required>
                            @error('list_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Title <span class="text-muted">(optional)</span></label>
                            <input type="text" name="title" class="form-control"
                                value="{{ old('title', $embassyList->title) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes <span class="text-muted">(optional)</span></label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $embassyList->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Add by Passport Number --}}
            <div class="card mb-3">
                <div class="card-header py-2">
                    <i class="bi bi-upc-scan me-1"></i> Quick Add by Passport Number
                </div>
                <div class="card-body py-2">
                    <div class="row g-2 align-items-start">
                        <div class="col-md-4">
                            <input type="text" id="passportLookupInput" class="form-control form-control-sm"
                                placeholder="Enter passport number..." maxlength="50">
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="passportLookupBtn">
                                <i class="bi bi-search me-1"></i> Find
                            </button>
                        </div>
                        <div class="col-12 mt-1" id="passportLookupResult" style="display:none;"></div>
                    </div>
                </div>
            </div>

            {{-- HR Selection --}}
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-1"></i> Candidates</span>
                    <span class="badge bg-primary" id="selectedCount">0 selected</span>
                </div>
                <div class="card-body pb-2">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" id="hrSearch" class="form-control form-control-sm"
                                placeholder="Search name, passport, nationality...">
                        </div>
                        <div class="col-md-4">
                            <select id="agentFilter" class="form-select form-select-sm">
                                <option value="">All Agents</option>
                                @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="clearFilters">Clear</button>
                        </div>
                    </div>

                    @error('items')
                    <div class="alert alert-danger py-2 mb-2">{{ $message }}</div>
                    @enderror

                    <div class="table-responsive" style="max-height:480px;overflow-y:auto;">
                        <table class="table table-hover table-sm mb-0" id="hrTable">
                            <thead style="position:sticky;top:0;z-index:1;">
                                <tr>
                                    <th width="32"></th>
                                    <th>Candidate</th>
                                    <th>Passport #</th>
                                    <th>Visa #</th>
                                    <th>Agent</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($availableHr as $hr)
                                @php $preCategory = $preSelected[$hr->id] ?? null; @endphp
                                <tr class="hr-row {{ $preCategory ? 'selected' : '' }}"
                                    data-id="{{ $hr->id }}"
                                    data-name="{{ strtolower($hr->full_name_en) }}"
                                    data-nationality="{{ strtolower($hr->nationality) }}"
                                    data-passport="{{ strtolower($hr->passport?->passport_number ?? '') }}"
                                    data-agent-id="{{ $hr->agent_id ?? '' }}"
                                    data-preselected="{{ $preCategory ? '1' : '0' }}"
                                    data-precategory="{{ $preCategory ?? '' }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input hr-checkbox"
                                            data-hr-id="{{ $hr->id }}"
                                            {{ $preCategory ? 'checked' : '' }}
                                            style="cursor:pointer;">
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $hr->full_name_en }}</div>
                                        <small class="text-muted">{{ $hr->nationality }}
                                            @if($hr->status === 'listed' && !$preCategory)
                                                <span class="badge bg-warning text-dark in-list-badge ms-1">In a list</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td><code style="font-size:.8rem;">{{ $hr->passport?->passport_number ?? '—' }}</code></td>
                                    <td><small>{{ $hr->visa?->visa_number ?? '—' }}</small></td>
                                    <td><small class="text-muted">{{ $hr->agent?->name ?? '—' }}</small></td>
                                    <td>
                                        <select class="form-select form-select-sm category-select"
                                            style="min-width:130px;{{ $preCategory ? '' : 'display:none;' }}">
                                            @foreach(['new' => 'New', 'restamping' => 'Re-stamping', 'cancellation' => 'Cancellation'] as $val => $label)
                                            <option value="{{ $val }}" {{ $preCategory === $val ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No available HR profiles.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: selected summary --}}
        <div class="col-lg-4">
            <div id="selectedPanel" class="card">
                <div class="card-header py-2"><i class="bi bi-clipboard-check me-1"></i> Selected Candidates</div>
                <div id="selectedList" class="list-group list-group-flush" style="max-height:400px;overflow-y:auto;">
                    <div class="list-group-item text-center text-muted py-4" id="emptyMsg" style="display:none;">
                        <i class="bi bi-person-plus opacity-25 d-block fs-2 mb-1"></i>
                        No candidates selected
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between mb-2" style="font-size:.8rem;">
                        <span class="text-success">New: <strong id="countNew">0</strong></span>
                        <span class="text-primary">Re-stamp: <strong id="countRestamp">0</strong></span>
                        <span class="text-danger">Cancel: <strong id="countCancel">0</strong></span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="saveBtn">
                        <i class="bi bi-floppy me-1"></i> Update Draft
                    </button>
                    <a href="{{ route('embassy-lists.show', $embassyList) }}" class="btn btn-outline-secondary w-100 mt-2">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div id="hiddenInputs"></div>
</form>
@endsection

@push('scripts')
<script>
(function() {
    const selected = {};
    const preSelected = @json($selectedItems);

    function categoryBadge(cat) {
        return {new: 'bg-success', restamping: 'bg-primary', cancellation: 'bg-danger'}[cat] || 'bg-secondary';
    }
    function categoryLabel(cat) {
        return {new: 'New', restamping: 'Re-stamping', cancellation: 'Cancellation'}[cat] || cat;
    }

    function updateCounts() {
        let counts = {new: 0, restamping: 0, cancellation: 0};
        let total = 0;
        Object.values(selected).forEach(item => {
            counts[item.category] = (counts[item.category] || 0) + 1;
            total++;
        });
        document.getElementById('selectedCount').textContent = total + ' selected';
        document.getElementById('countNew').textContent = counts.new || 0;
        document.getElementById('countRestamp').textContent = counts.restamping || 0;
        document.getElementById('countCancel').textContent = counts.cancellation || 0;
        document.getElementById('emptyMsg').style.display = total === 0 ? 'block' : 'none';
    }

    function renderSelectedList() {
        document.querySelectorAll('.selected-item').forEach(el => el.remove());
        Object.entries(selected).forEach(([hrId, item]) => {
            const div = document.createElement('div');
            div.className = 'list-group-item py-2 selected-item';
            div.innerHTML = `<div class="d-flex justify-content-between align-items-center">
                <div style="font-size:.8rem;font-weight:600;">${item.name}</div>
                <button type="button" class="btn-close btn-sm remove-btn" data-hr-id="${hrId}" style="font-size:.6rem;"></button>
            </div>
            <span class="badge ${categoryBadge(item.category)} mt-1" style="font-size:.65rem;">${categoryLabel(item.category)}</span>`;
            document.getElementById('selectedList').insertBefore(div, document.getElementById('emptyMsg'));
        });
    }

    function rebuildHiddenInputs() {
        const container = document.getElementById('hiddenInputs');
        container.innerHTML = '';
        let i = 0;
        Object.entries(selected).forEach(([hrId, item]) => {
            container.innerHTML += `<input type="hidden" name="items[${i}][hr_profile_id]" value="${hrId}">`;
            container.innerHTML += `<input type="hidden" name="items[${i}][category]" value="${item.category}">`;
            i++;
        });
    }

    // Initialize pre-selected
    document.querySelectorAll('.hr-checkbox').forEach(cb => {
        const hrId = cb.dataset.hrId;
        const row = cb.closest('tr');
        const catSelect = row.querySelector('.category-select');

        if (preSelected[hrId]) {
            selected[hrId] = {
                name: row.querySelector('.fw-semibold').textContent.trim(),
                category: preSelected[hrId]
            };
            catSelect.addEventListener('change', function() {
                if (selected[hrId]) { selected[hrId].category = this.value; updateCounts(); renderSelectedList(); rebuildHiddenInputs(); }
            });
        }

        cb.addEventListener('change', function() {
            if (this.checked) {
                row.classList.add('selected');
                catSelect.style.display = 'block';
                selected[hrId] = {name: row.querySelector('.fw-semibold').textContent.trim(), category: catSelect.value};
                catSelect.addEventListener('change', function() {
                    if (selected[hrId]) { selected[hrId].category = this.value; updateCounts(); renderSelectedList(); rebuildHiddenInputs(); }
                });
            } else {
                row.classList.remove('selected');
                catSelect.style.display = 'none';
                delete selected[hrId];
            }
            updateCounts(); renderSelectedList(); rebuildHiddenInputs();
        });

        row.addEventListener('click', function(e) {
            if (e.target.tagName === 'SELECT' || e.target.tagName === 'INPUT') return;
            cb.checked = !cb.checked;
            cb.dispatchEvent(new Event('change'));
        });
    });

    document.getElementById('selectedList').addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-btn');
        if (!btn) return;
        const hrId = btn.dataset.hrId;
        const cb = document.querySelector(`.hr-checkbox[data-hr-id="${hrId}"]`);
        if (cb) { cb.checked = false; cb.closest('tr').classList.remove('selected'); cb.closest('tr').querySelector('.category-select').style.display = 'none'; }
        delete selected[hrId];
        updateCounts(); renderSelectedList(); rebuildHiddenInputs();
    });

    document.getElementById('hrSearch').addEventListener('input', applyFilter);
    document.getElementById('agentFilter').addEventListener('change', applyFilter);
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('hrSearch').value = '';
        document.getElementById('agentFilter').value = '';
        applyFilter();
    });

    function applyFilter() {
        const search = document.getElementById('hrSearch').value.toLowerCase();
        const agentId = document.getElementById('agentFilter').value;
        document.querySelectorAll('#hrTable tbody tr').forEach(row => {
            const matchSearch = !search || row.dataset.name?.includes(search) || row.dataset.nationality?.includes(search) || row.dataset.passport?.includes(search);
            const matchAgent = !agentId || row.dataset.agentId === agentId;
            row.style.display = (matchSearch && matchAgent) ? '' : 'none';
        });
    }

    renderSelectedList();
    updateCounts();
    rebuildHiddenInputs();

    // ── Passport lookup ──────────────────────────────────────────────────
    const lookupInput  = document.getElementById('passportLookupInput');
    const lookupBtn    = document.getElementById('passportLookupBtn');
    const lookupResult = document.getElementById('passportLookupResult');

    function runPassportLookup() {
        const passportNo = lookupInput.value.trim();
        if (!passportNo) return;
        lookupResult.style.display = 'block';
        lookupResult.innerHTML = '<span class="text-muted" style="font-size:.85rem;"><i class="bi bi-hourglass me-1"></i>Searching...</span>';

        fetch('{{ route("hr.lookup-by-passport") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
            body: JSON.stringify({passport_no: passportNo})
        })
        .then(r => r.json())
        .then(data => {
            if (!data.found) {
                lookupResult.innerHTML = `<div class="alert alert-warning py-2 mb-0" style="font-size:.85rem;"><i class="bi bi-exclamation-circle me-1"></i>${data.message}</div>`;
                return;
            }
            if (selected[data.id]) {
                lookupResult.innerHTML = `<div class="alert alert-info py-2 mb-0" style="font-size:.85rem;"><i class="bi bi-info-circle me-1"></i><strong>${data.full_name_en}</strong> is already in the list.</div>`;
                return;
            }
            lookupResult.innerHTML = `
                <div class="border rounded p-2" style="font-size:.85rem;">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <strong>${data.full_name_en}</strong>${data.full_name_ar ? ' <span class="text-muted">'+data.full_name_ar+'</span>' : ''}
                            <span class="text-muted ms-2">${data.nationality}</span>
                            <br><small class="text-muted">Passport: ${data.passport_no||'—'} &nbsp; Visa: ${data.visa_no||'—'} &nbsp; Agent: ${data.agent_name||'—'}</small>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-shrink-0">
                            <select id="lookupCategory" class="form-select form-select-sm" style="width:140px;">
                                <option value="new">New</option>
                                <option value="restamping">Re-stamping</option>
                                <option value="cancellation">Cancellation</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-success" id="addFoundBtn"
                                data-hr-id="${data.id}" data-name="${data.full_name_en}">
                                <i class="bi bi-plus me-1"></i>Add
                            </button>
                        </div>
                    </div>
                </div>`;

            document.getElementById('addFoundBtn').addEventListener('click', function() {
                const hrId    = this.dataset.hrId;
                const name    = this.dataset.name;
                const cat     = document.getElementById('lookupCategory').value;
                selected[hrId] = {name, category: cat};
                const cb = document.querySelector(`.hr-checkbox[data-hr-id="${hrId}"]`);
                if (cb && !cb.checked) {
                    cb.checked = true;
                    const row = cb.closest('tr');
                    row.classList.add('selected');
                    const catSel = row.querySelector('.category-select');
                    catSel.style.display = 'block';
                    catSel.value = cat;
                    catSel.addEventListener('change', function() {
                        if (selected[hrId]) { selected[hrId].category = this.value; updateCounts(); renderSelectedList(); rebuildHiddenInputs(); }
                    });
                }
                updateCounts(); renderSelectedList(); rebuildHiddenInputs();
                lookupResult.innerHTML = `<div class="alert alert-success py-2 mb-0" style="font-size:.85rem;"><i class="bi bi-check-circle me-1"></i><strong>${name}</strong> added as ${categoryLabel(cat)}.</div>`;
                lookupInput.value = '';
            });
        })
        .catch(() => {
            lookupResult.innerHTML = '<div class="alert alert-danger py-2 mb-0" style="font-size:.85rem;"><i class="bi bi-x-circle me-1"></i>Lookup failed. Please try again.</div>';
        });
    }

    lookupBtn.addEventListener('click', runPassportLookup);
    lookupInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); runPassportLookup(); } });
})();
</script>
@endpush
