@extends('layouts.agency')
@section('title', 'Embassy Lists')
@section('page-title', 'Embassy Lists')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Embassy Lists</h5>
        <small class="text-muted">
            {{ $monthlyCount }} this month
            @if($monthlyLimit < 999) · limit {{ $monthlyLimit }} @endif
        </small>
    </div>
    @can('create', \App\Models\EmbassyList::class)
    <a href="{{ route('embassy-lists.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Create Embassy List
    </a>
    @endcan
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Total Lists</div>
            <div class="fs-3 fw-bold text-primary">{{ $totalCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Draft</div>
            <div class="fs-3 fw-bold text-warning">{{ $draftCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Finalized</div>
            <div class="fs-3 fw-bold text-success">{{ $finalizedCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">This Month</div>
            <div class="fs-3 fw-bold text-info">{{ $monthlyCount }}</div>
        </div>
    </div>
</div>

{{-- Search & Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('embassy-lists.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="List no, candidate, passport..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status')=='draft' ? 'selected' : '' }}>Draft</option>
                    <option value="finalized" {{ request('status')=='finalized' ? 'selected' : '' }}>Finalized</option>
                    <option value="printed" {{ request('status')=='printed' ? 'selected' : '' }}>Printed</option>
                    <option value="cancelled" {{ request('status')=='cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ request('date_from') }}" placeholder="From date">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="{{ request('date_to') }}" placeholder="To date">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="{{ route('embassy-lists.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>List No</th>
                    <th>Date</th>
                    <th>Title</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">New</th>
                    <th class="text-center">Re-stamp</th>
                    <th class="text-center">Cancel</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($embassyLists as $list)
                <tr>
                    <td class="text-muted">{{ $embassyLists->firstItem() + $loop->index }}</td>
                    <td>
                        <a href="{{ route('embassy-lists.show', $list) }}" class="fw-semibold text-decoration-none font-monospace">
                            {{ $list->list_no }}
                        </a>
                    </td>
                    <td class="text-muted">{{ $list->list_date->format('d M Y') }}</td>
                    <td>{{ $list->title ?? '—' }}</td>
                    <td class="text-center fw-bold">{{ $list->total_items }}</td>
                    <td class="text-center">
                        @if($list->total_new > 0)
                            <span class="badge bg-success bg-opacity-10 text-success">{{ $list->total_new }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($list->total_restamping > 0)
                            <span class="badge bg-primary bg-opacity-10 text-primary">{{ $list->total_restamping }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($list->total_cancellation > 0)
                            <span class="badge bg-danger bg-opacity-10 text-danger">{{ $list->total_cancellation }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $list->statusBadgeClass() }}">{{ ucfirst($list->status) }}</span>
                    </td>
                    <td class="text-muted" style="font-size:.8rem;">{{ $list->createdBy?->name ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('embassy-lists.show', $list) }}"
                               class="btn btn-sm btn-outline-secondary py-0" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($list->isDraft())
                                @can('update', $list)
                                <a href="{{ route('embassy-lists.edit', $list) }}"
                                   class="btn btn-sm btn-outline-primary py-0" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                            @endif
                            @if($list->isFinalized() || $list->status === 'printed')
                                <a href="{{ route('embassy-lists.print', $list) }}"
                                   class="btn btn-sm btn-outline-info py-0" title="Print" target="_blank">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <a href="{{ route('embassy-lists.download-pdf', $list) }}"
                                   class="btn btn-sm btn-outline-primary py-0" title="Download PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            @endif
                            @if($list->isDraft())
                                @can('finalize', $list)
                                <form method="POST" action="{{ route('embassy-lists.finalize', $list) }}" class="d-inline"
                                      onsubmit="return confirm('Finalize list {{ $list->list_no }}? This will mark all {{ $list->total_items }} candidates as listed.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success py-0" title="Finalize">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                                @endcan
                            @endif
                            @if(!$list->isCancelled())
                                @can('cancel', $list)
                                <button type="button" class="btn btn-sm btn-outline-danger py-0"
                                    data-bs-toggle="modal" data-bs-target="#cancelModal"
                                    data-list-id="{{ $list->id }}"
                                    data-list-no="{{ $list->list_no }}"
                                    data-list-finalized="{{ $list->isFinalized() ? '1' : '0' }}"
                                    title="Cancel">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted py-5">
                        <i class="bi bi-list-ol fs-1 d-block mb-2 opacity-25"></i>
                        No embassy lists found.
                        @can('create', \App\Models\EmbassyList::class)
                            <a href="{{ route('embassy-lists.create') }}">Create the first one.</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($embassyLists->hasPages())
    <div class="card-footer py-2">{{ $embassyLists->links() }}</div>
    @endif
</div>

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title text-danger"><i class="bi bi-x-circle me-1"></i> Cancel List</h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Cancel <strong id="cancelListNo"></strong>?</p>
                <small id="cancelWarning" class="text-danger d-none">
                    This list is finalized. Cancelling will reset candidate statuses back to active (if not in another list).
                </small>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Back</button>
                <form id="cancelForm" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">Cancel List</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('cancelModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('cancelListNo').textContent = btn.dataset.listNo;
    document.getElementById('cancelForm').action = '/embassy-lists/' + btn.dataset.listId + '/cancel';
    document.getElementById('cancelWarning').classList.toggle('d-none', btn.dataset.listFinalized !== '1');
});
</script>
@endpush
