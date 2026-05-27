@extends('layouts.agency')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

{{-- Page header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-building me-1 text-primary"></i>
            {{ $agency?->name }}
        </h5>
        <small class="text-muted">
            {{ now()->format('l, d F Y') }}
            @if($agency?->rl_number) &nbsp;·&nbsp; RL: {{ $agency->rl_number }} @endif
            @if($agency?->license_number) &nbsp;·&nbsp; License: {{ $agency->license_number }} @endif
        </small>
    </div>
    <div class="d-flex gap-2">
        @can('create', \App\Models\HrProfile::class)
        <a href="{{ route('hr.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add HR
        </a>
        @endcan
        @can('create', \App\Models\EmbassyList::class)
        <a href="{{ route('embassy-lists.create') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg me-1"></i> Embassy List
        </a>
        @endcan
    </div>
</div>

{{-- Alerts panel --}}
<x-alert-panel :alerts="$alerts" />

{{-- Agency notices from super-admin --}}
@if($agency?->notices?->count())
<div class="mb-3">
    @foreach($agency->notices as $notice)
    <div class="alert alert-{{ in_array($notice->type, ['danger','warning','info','success']) ? $notice->type : 'info' }} alert-dismissible fade show py-2" style="font-size:.82rem;border-radius:8px;">
        <strong>{{ $notice->title }}:</strong> {{ $notice->body }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endforeach
</div>
@endif

{{-- ── ROW 1: Stat cards ──────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <a href="{{ route('hr.index') }}" class="text-decoration-none">
            <div class="card p-3 stat-card blue h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#64748b;letter-spacing:.04em;">HR / Candidates</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_hr'] }}</div>
                        <small class="text-success">{{ $stats['active_hr'] }} active</small>
                    </div>
                    <i class="bi bi-person-vcard fs-2 text-primary opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('agents.index') }}" class="text-decoration-none">
            <div class="card p-3 stat-card green h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#64748b;letter-spacing:.04em;">Agents</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['total_agents'] }}</div>
                        <small class="text-success">{{ $stats['active_agents'] }} active</small>
                    </div>
                    <i class="bi bi-people fs-2 text-success opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="{{ route('embassy-lists.index') }}" class="text-decoration-none">
            <div class="card p-3 stat-card orange h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:.68rem;text-transform:uppercase;color:#64748b;letter-spacing:.04em;">Embassy Lists</div>
                        <div class="fs-3 fw-bold text-dark">{{ $stats['embassy_lists_month'] }}</div>
                        <small class="text-muted">this month</small>
                    </div>
                    <i class="bi bi-list-ol fs-2 text-warning opacity-25"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3 stat-card purple h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:.68rem;text-transform:uppercase;color:#64748b;letter-spacing:.04em;">PDF Downloads</div>
                    <div class="fs-3 fw-bold text-dark">{{ $stats['pdf_downloads_month'] }}</div>
                    <small class="text-muted">
                        this month
                        @if($subscription && ($subscription->plan->max_pdf_monthly ?? 0) < 9999)
                            · / {{ $subscription->plan->max_pdf_monthly }}
                        @endif
                    </small>
                </div>
                <i class="bi bi-file-earmark-pdf fs-2 opacity-25" style="color:#8b5cf6;"></i>
            </div>
        </div>
    </div>
</div>

{{-- ── ROW 2: Subscription box + Quick Actions ──────── --}}
<div class="row g-3 mb-3">

    {{-- Subscription & Usage --}}
    <div class="col-md-5">
        @if($subscription)
        <div class="card h-100">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-credit-card me-1"></i> Subscription</span>
                <span class="badge badge-status-{{ $subscription->status }}">{{ ucfirst($subscription->status) }}</span>
            </div>
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-bold fs-6">{{ $subscription->plan->name ?? '—' }}</div>
                        <small class="text-muted">Expires {{ $subscription->end_date->format('d M Y') }}</small>
                    </div>
                    <span class="fw-bold {{ $subscription->daysRemaining() <= 7 ? 'text-danger' : 'text-success' }}" style="font-size:.85rem;">
                        {{ $subscription->daysRemaining() }}d left
                    </span>
                </div>

                {{-- Usage meters --}}
                <x-usage-meter
                    label="HR Profiles"
                    :used="$stats['total_hr']"
                    :limit="$subscription->plan->max_hr ?? 9999"
                    color="primary"
                />
                <x-usage-meter
                    label="Agents"
                    :used="$stats['total_agents']"
                    :limit="$subscription->plan->max_agents ?? 9999"
                    color="success"
                />
                <x-usage-meter
                    label="Embassy Lists (month)"
                    :used="$stats['embassy_lists_month']"
                    :limit="$subscription->plan->max_embassy_lists_monthly ?? 9999"
                    color="warning"
                />
                <x-usage-meter
                    label="PDF Downloads (month)"
                    :used="$stats['pdf_downloads_month']"
                    :limit="$subscription->plan->max_pdf_monthly ?? 9999"
                    color="info"
                />
            </div>
        </div>
        @else
        <div class="card h-100 border-warning">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                <i class="bi bi-credit-card-x fs-1 text-warning opacity-50 mb-2"></i>
                <div class="fw-semibold">No Active Subscription</div>
                <p class="text-muted small mt-1 mb-3">You cannot create new records or generate PDFs until you subscribe.</p>
                <a href="{{ route('subscription.expired') }}" class="btn btn-warning btn-sm">
                    <i class="bi bi-send me-1"></i> Request Renewal
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header py-2">
                <i class="bi bi-lightning-charge me-1"></i> Quick Actions
            </div>
            <div class="card-body py-3">
                <div class="row g-2">
                    @can('create', \App\Models\Agent::class)
                    <div class="col-6">
                        <a href="{{ route('agents.create') }}"
                           class="d-flex align-items-center gap-2 p-3 rounded text-decoration-none"
                           style="background:#f0f9ff;border:1px solid #bae6fd;color:#0369a1;transition:background .15s;"
                           onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='#f0f9ff'">
                            <i class="bi bi-person-plus fs-5"></i>
                            <div>
                                <div class="fw-semibold" style="font-size:.82rem;">Add Agent</div>
                                <div style="font-size:.72rem;opacity:.75;">{{ $stats['total_agents'] }} total</div>
                            </div>
                        </a>
                    </div>
                    @endcan
                    @can('create', \App\Models\HrProfile::class)
                    <div class="col-6">
                        <a href="{{ route('hr.create') }}"
                           class="d-flex align-items-center gap-2 p-3 rounded text-decoration-none"
                           style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;transition:background .15s;"
                           onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                            <i class="bi bi-person-vcard fs-5"></i>
                            <div>
                                <div class="fw-semibold" style="font-size:.82rem;">Add HR Profile</div>
                                <div style="font-size:.72rem;opacity:.75;">{{ $stats['total_hr'] }} total</div>
                            </div>
                        </a>
                    </div>
                    @endcan
                    @can('create', \App\Models\EmbassyList::class)
                    <div class="col-6">
                        <a href="{{ route('embassy-lists.create') }}"
                           class="d-flex align-items-center gap-2 p-3 rounded text-decoration-none"
                           style="background:#fffbeb;border:1px solid #fde68a;color:#78350f;transition:background .15s;"
                           onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='#fffbeb'">
                            <i class="bi bi-list-ol fs-5"></i>
                            <div>
                                <div class="fw-semibold" style="font-size:.82rem;">Create Embassy List</div>
                                <div style="font-size:.72rem;opacity:.75;">{{ $stats['embassy_lists_month'] }} this month</div>
                            </div>
                        </a>
                    </div>
                    @endcan
                    <div class="col-6">
                        <a href="{{ route('hr.index') }}"
                           class="d-flex align-items-center gap-2 p-3 rounded text-decoration-none"
                           style="background:#faf5ff;border:1px solid #ddd6fe;color:#5b21b6;transition:background .15s;"
                           onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                            <i class="bi bi-file-earmark-pdf fs-5"></i>
                            <div>
                                <div class="fw-semibold" style="font-size:.82rem;">Generate Documents</div>
                                <div style="font-size:.72rem;opacity:.75;">{{ $stats['pdf_downloads_month'] }} PDFs this month</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── ROW 3: Recent HR + Recent Embassy Lists ──────── --}}
<div class="row g-3 mb-3">

    {{-- Recent HR --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span><i class="bi bi-person-vcard me-1"></i> Recent HR Files</span>
                <a href="{{ route('hr.index') }}" class="btn btn-sm btn-outline-primary py-0">View All</a>
            </div>
            @if($recentHr->count())
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Passport</th>
                            <th>Visa</th>
                            <th>Agent</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentHr as $hr)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $hr->full_name_en }}</div>
                                <small class="text-muted">{{ $hr->nationality }}</small>
                            </td>
                            <td>
                                @if($hr->passport?->passport_number)
                                    <code style="font-size:.75rem;">{{ $hr->passport->passport_number }}</code>
                                    @if($hr->passport->expiry_date?->isPast())
                                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Passport expired"></i>
                                    @elseif($hr->passport->expiry_date && $hr->passport->expiry_date->diffInMonths(now()) >= 0 && $hr->passport->expiry_date->isBefore(now()->addMonths(6)))
                                        <i class="bi bi-exclamation-triangle text-warning ms-1" title="Expiring soon"></i>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $hr->visa?->visa_number ?? '—' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $hr->agent?->name ?? '—' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-status-{{ $hr->status }}">{{ ucfirst($hr->status) }}</span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary py-0" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('hr.documents', $hr) }}" class="btn btn-sm btn-outline-success py-0" title="Documents">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <x-empty-state icon="bi-person-vcard" title="No HR profiles yet"
                actionUrl="{{ route('hr.create') }}" actionLabel="Add First Profile" size="sm" />
            @endif
        </div>
    </div>

    {{-- Recent Embassy Lists --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span><i class="bi bi-list-ol me-1"></i> Embassy Lists</span>
                <a href="{{ route('embassy-lists.index') }}" class="btn btn-sm btn-outline-primary py-0">View All</a>
            </div>
            @if($recentEmbassyLists->count())
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>List No</th>
                            <th class="text-center">Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentEmbassyLists as $list)
                        <tr>
                            <td>
                                <div class="font-monospace fw-semibold" style="font-size:.8rem;">{{ $list->list_no }}</div>
                                <small class="text-muted">{{ $list->list_date->format('d M Y') }}</small>
                            </td>
                            <td class="text-center fw-bold">{{ $list->total_items }}</td>
                            <td>
                                <span class="badge badge-status-{{ $list->status }}">{{ ucfirst($list->status) }}</span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('embassy-lists.show', $list) }}"
                                       class="btn btn-sm btn-outline-secondary py-0" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($list->isFinalized() || $list->status === 'printed')
                                    <a href="{{ route('embassy-lists.download-pdf', $list) }}"
                                       class="btn btn-sm btn-outline-primary py-0" title="PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <x-empty-state icon="bi-list-ol" title="No embassy lists yet" size="sm"
                actionUrl="{{ route('embassy-lists.create') }}" actionLabel="Create First List" />
            @endif
        </div>
    </div>
</div>

{{-- ── ROW 4: Recent Document Activity ──────────────── --}}
@if($recentDocActivity->count())
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span><i class="bi bi-clock-history me-1"></i> Recent Document Activity</span>
        <small class="text-muted">Last 8 events</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Document</th>
                    <th>Action</th>
                    <th>By</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentDocActivity as $doc)
                <tr>
                    <td>
                        @if($doc->hrProfile)
                            <a href="{{ route('hr.show', $doc->hrProfile) }}" class="text-decoration-none fw-semibold" style="font-size:.8rem;">
                                {{ $doc->hrProfile->full_name_en }}
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="font-size:.78rem;">{{ ucwords(str_replace('_', ' ', $doc->document_type)) }}</td>
                    <td>
                        @if($doc->action === 'download')
                            <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:.68rem;">Download</span>
                        @else
                            <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:.68rem;">Preview</span>
                        @endif
                    </td>
                    <td style="font-size:.78rem;">{{ $doc->generatedBy?->name ?? '—' }}</td>
                    <td class="text-muted" style="font-size:.78rem;">{{ $doc->created_at->format('d M, H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
