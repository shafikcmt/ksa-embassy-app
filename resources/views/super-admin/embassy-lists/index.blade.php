@extends('layouts.super-admin')
@section('title', 'Embassy Lists')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-bold">Embassy Lists</h5>
        <small class="text-muted">All lists across agencies</small>
    </div>
</div>

{{-- Search & Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('super-admin.embassy-lists.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="List no, agency, candidate, passport..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="agency_id" class="form-select form-select-sm">
                    <option value="">All Agencies</option>
                    @foreach($agencies as $agency)
                    <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                        {{ $agency->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="draft"       {{ request('status')=='draft'       ? 'selected' : '' }}>Draft</option>
                    <option value="finalized"   {{ request('status')=='finalized'   ? 'selected' : '' }}>Finalized</option>
                    <option value="printed"     {{ request('status')=='printed'     ? 'selected' : '' }}>Printed</option>
                    <option value="cancelled"   {{ request('status')=='cancelled'   ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('super-admin.embassy-lists.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>List No</th>
                    <th>Agency</th>
                    <th>Date</th>
                    <th>Title</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">New</th>
                    <th class="text-center">Re-stamp</th>
                    <th class="text-center">Cancel</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($embassyLists as $list)
                <tr>
                    <td class="text-muted">{{ $embassyLists->firstItem() + $loop->index }}</td>
                    <td>
                        <a href="{{ route('super-admin.embassy-lists.show', $list) }}"
                           class="fw-semibold text-decoration-none font-monospace">
                            {{ $list->list_no }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('super-admin.agencies.show', $list->agency_id) }}"
                           class="text-decoration-none text-muted">
                            {{ $list->agency?->name ?? '—' }}
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
                        @php
                            $sc = match($list->status) {
                                'draft'      => 'bg-warning text-dark',
                                'finalized'  => 'bg-success',
                                'printed'    => 'bg-info',
                                'cancelled'  => 'bg-secondary',
                                default      => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $sc }}">{{ ucfirst($list->status) }}</span>
                    </td>
                    <td class="text-muted" style="font-size:.8rem;">
                        {{ $list->created_at->format('d M Y') }}
                    </td>
                    <td>
                        <a href="{{ route('super-admin.embassy-lists.show', $list) }}"
                           class="btn btn-sm btn-outline-secondary py-0">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="text-center text-muted py-5">
                        <i class="bi bi-list-ol fs-1 d-block mb-2 opacity-25"></i>
                        No embassy lists found.
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
@endsection
