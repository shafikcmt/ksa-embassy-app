@extends('layouts.agency')
@section('title', 'Create Embassy List')
@section('page-title', 'Create Embassy List')

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
    <a href="{{ route('embassy-lists.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Create Embassy List</h5>
</div>

<form method="POST" action="{{ route('embassy-lists.store') }}" id="embassyForm">
    @csrf

    <div class="row g-3">
        {{-- Left: Main form --}}
        <div class="col-lg-8">
            {{-- Header fields --}}
            <div class="card mb-3">
                <div class="card-header py-2"><i class="bi bi-info-circle me-1"></i> List Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">List Date <span class="text-danger">*</span></label>
                            <input type="date" name="list_date" class="form-control @error('list_date') is-invalid @enderror"
                                value="{{ old('list_date', now()->format('Y-m-d')) }}" required>
                            @error('list_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Title <span class="text-muted">(optional)</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}" placeholder="e.g. First batch January 2024">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes <span class="text-muted">(optional)</span></label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- HR Selection --}}
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-1"></i> Select Candidates</span>
                    <span class="badge bg-primary" id="selectedCount">0 selected</span>
                </div>
                <div class="card-body pb-2">
                    {{-- Search/Filter bar --}}
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
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="clearFilters">
                                <i class="bi bi-x me-1"></i>Clear
                            </button>
                        </div>
                    </div>

                    @error('items')
                    <div class="alert alert-danger py-2 mb-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror

                    {{-- HR Table --}}
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
                                <tr class="hr-row" data-id="{{ $hr->id }}"
                                    data-name="{{ strtolower($hr->full_name_en) }}"
                                    data-nationality="{{ strtolower($hr->nationality) }}"
                                    data-passport="{{ strtolower($hr->passport?->passport_number ?? '') }}"
                                    data-agent-id="{{ $hr->agent_id ?? '' }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input hr-checkbox"
                                            data-hr-id="{{ $hr->id }}" style="cursor:pointer;">
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $hr->full_name_en }}</div>
                                        <small class="text-muted">{{ $hr->nationality }}
                                            @if($hr->status === 'listed')
                                                <span class="badge bg-warning text-dark in-list-badge ms-1">In a list</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td><code style="font-size:.8rem;">{{ $hr->passport?->passport_number ?? '—' }}</code></td>
                                    <td><small>{{ $hr->visa?->visa_number ?? '—' }}</small></td>
                                    <td><small class="text-muted">{{ $hr->agent?->name ?? '—' }}</small></td>
                                    <td>
                                        <select class="form-select form-select-sm category-select"
                                            name="items[{{ $hr->id }}][category]"
                                            style="min-width:130px;">
                                            <option value="new">New</option>
                                            <option value="restamping">Re-stamping</option>
                                            <option value="cancellation">Cancellation</option>
                                        </select>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No available HR profiles. <a href="{{ route('hr.create') }}">Add HR profiles</a> first.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Selected summary --}}
        <div class="col-lg-4">
            <div id="selectedPanel" class="card">
                <div class="card-header py-2"><i class="bi bi-clipboard-check me-1"></i> Selected Candidates</div>
                <div id="selectedList" class="list-group list-group-flush" style="max-height:400px;overflow-y:auto;">
                    <div class="list-group-item text-center text-muted py-4" id="emptyMsg">
                        <i class="bi bi-person-plus opacity-25 d-block fs-2 mb-1"></i>
                        Check candidates from the table to add them
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between mb-2" style="font-size:.8rem;">
                        <span class="text-success">New: <strong id="countNew">0</strong></span>
                        <span class="text-primary">Re-stamp: <strong id="countRestamp">0</strong></span>
                        <span class="text-danger">Cancel: <strong id="countCancel">0</strong></span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="saveBtn" disabled>
                        <i class="bi bi-floppy me-1"></i> Save as Draft
                    </button>
                    <a href="{{ route('embassy-lists.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden inputs container for form submission --}}
    <div id="hiddenInputs"></div>
</form>
@endsection

@push('scripts')
<script>
(function() {
    const selected = {}; // hr_id => {name, category}
    const checkboxes = document.querySelectorAll('.hr-checkbox');
    const selectedList = document.getElementById('selectedList');
    const emptyMsg = document.getElementById('emptyMsg');
    const hiddenInputs = document.getElementById('hiddenInputs');
    const saveBtn = document.getElementById('saveBtn');

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
        saveBtn.disabled = total === 0;
        emptyMsg.style.display = total === 0 ? 'block' : 'none';
    }

    function renderSelectedList() {
        // Remove old selected items
        selectedList.querySelectorAll('.selected-item').forEach(el => el.remove());
        Object.entries(selected).forEach(([hrId, item]) => {
            const div = document.createElement('div');
            div.className = 'list-group-item list-group-item-action py-2 selected-item';
            div.dataset.hrId = hrId;
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div style="font-size:.8rem;font-weight:600;">${item.name}</div>
                    <button type="button" class="btn-close btn-sm remove-btn" data-hr-id="${hrId}" style="font-size:.6rem;"></button>
                </div>
                <div style="font-size:.75rem;">
                    <span class="badge ${categoryBadge(item.category)} mt-1">${categoryLabel(item.category)}</span>
                </div>
            `;
            selectedList.insertBefore(div, emptyMsg);
        });
    }

    function categoryBadge(cat) {
        return {new: 'bg-success', restamping: 'bg-primary', cancellation: 'bg-danger'}[cat] || 'bg-secondary';
    }
    function categoryLabel(cat) {
        return {new: 'New', restamping: 'Re-stamping', cancellation: 'Cancellation'}[cat] || cat;
    }

    function rebuildHiddenInputs() {
        hiddenInputs.innerHTML = '';
        let i = 0;
        Object.entries(selected).forEach(([hrId, item]) => {
            hiddenInputs.innerHTML += `<input type="hidden" name="items[${i}][hr_profile_id]" value="${hrId}">`;
            hiddenInputs.innerHTML += `<input type="hidden" name="items[${i}][category]" value="${item.category}">`;
            i++;
        });
    }

    // Checkbox toggle
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const hrId = this.dataset.hrId;
            const row = this.closest('tr');
            if (this.checked) {
                const catSelect = row.querySelector('.category-select');
                row.classList.add('selected');
                catSelect.style.display = 'block';
                selected[hrId] = {
                    name: row.querySelector('.fw-semibold').textContent.trim(),
                    category: catSelect.value
                };
                catSelect.addEventListener('change', function() {
                    if (selected[hrId]) {
                        selected[hrId].category = this.value;
                        updateCounts();
                        renderSelectedList();
                        rebuildHiddenInputs();
                    }
                });
            } else {
                row.classList.remove('selected');
                row.querySelector('.category-select').style.display = 'none';
                delete selected[hrId];
            }
            updateCounts();
            renderSelectedList();
            rebuildHiddenInputs();
        });
        // Row click selects checkbox
        cb.closest('tr').addEventListener('click', function(e) {
            if (e.target.tagName === 'SELECT' || e.target.tagName === 'INPUT') return;
            cb.checked = !cb.checked;
            cb.dispatchEvent(new Event('change'));
        });
    });

    // Remove from selected panel
    selectedList.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-btn');
        if (!btn) return;
        const hrId = btn.dataset.hrId;
        const cb = document.querySelector(`.hr-checkbox[data-hr-id="${hrId}"]`);
        if (cb) {
            cb.checked = false;
            const row = cb.closest('tr');
            row.classList.remove('selected');
            row.querySelector('.category-select').style.display = 'none';
        }
        delete selected[hrId];
        updateCounts();
        renderSelectedList();
        rebuildHiddenInputs();
    });

    // Search filter
    function applyFilter() {
        const search = document.getElementById('hrSearch').value.toLowerCase();
        const agentId = document.getElementById('agentFilter').value;
        document.querySelectorAll('#hrTable tbody tr').forEach(row => {
            const matchSearch = !search ||
                row.dataset.name?.includes(search) ||
                row.dataset.nationality?.includes(search) ||
                row.dataset.passport?.includes(search);
            const matchAgent = !agentId || row.dataset.agentId === agentId;
            row.style.display = (matchSearch && matchAgent) ? '' : 'none';
        });
    }
    document.getElementById('hrSearch').addEventListener('input', applyFilter);
    document.getElementById('agentFilter').addEventListener('change', applyFilter);
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('hrSearch').value = '';
        document.getElementById('agentFilter').value = '';
        applyFilter();
    });

    updateCounts();
})();
</script>
@endpush
