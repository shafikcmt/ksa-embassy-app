@extends('layouts.agency')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@push('styles')
<style>
    .metric { border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; padding: 1rem 1.1rem;
        display: block; transition: transform .15s, box-shadow .15s; height: 100%; position: relative; overflow: hidden; }
    .metric:hover { transform: translateY(-2px); box-shadow: 0 10px 24px -14px rgba(15,23,42,.35); }
    .metric .m-ic { width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center; color: #fff; font-size: 1.1rem; }
    .metric .m-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 600; }
    .metric .m-value { font-size: 1.7rem; font-weight: 800; line-height: 1.1; color: #0f172a; position: relative; z-index: 1; }
    .metric .m-sub { font-size: .72rem; position: relative; z-index: 1; }
    .metric .m-wm { position: absolute; right: .35rem; bottom: -.35rem; font-size: 3rem; line-height: 1; opacity: .06; color: #0f172a; pointer-events: none; }
    .metric .m-go { color: #cbd5e1; font-size: .85rem; transition: transform .15s, color .15s; }
    .metric:hover .m-go { color: var(--brand-blue, #2563eb); transform: translateX(2px); }
    .m-blue{background:linear-gradient(135deg,#3b82f6,#2563eb)} .m-green{background:linear-gradient(135deg,#34d399,#059669)}
    .m-amber{background:linear-gradient(135deg,#fbbf24,#d97706)} .m-violet{background:linear-gradient(135deg,#a78bfa,#7c3aed)}
    .m-rose{background:linear-gradient(135deg,#fb7185,#e11d48)} .m-teal{background:linear-gradient(135deg,#2dd4bf,#0d9488)}
    .qa { display: flex; align-items: center; gap: .7rem; padding: .85rem; border-radius: 10px; text-decoration: none;
        border: 1px solid #e2e8f0; background: #fff; transition: .15s; height: 100%; }
    .qa:hover { border-color: #cbd5e1; background: #f8fafc; transform: translateY(-1px); }
    .qa .qa-ic { width: 38px; height: 38px; border-radius: 9px; display: grid; place-items: center; color: #fff; flex-shrink: 0; font-size: 1.05rem; }
    .qa .qa-title { font-weight: 600; font-size: .82rem; color: #0f172a; }
    .qa .qa-sub { font-size: .7rem; color: #64748b; }
    .wq-item { display: flex; align-items: center; gap: .75rem; padding: .7rem .25rem; border-bottom: 1px solid #f1f5f9; }
    .wq-item:last-child { border-bottom: 0; }
    .wq-ic { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; flex-shrink: 0; font-size: .95rem; }
    .header-card { border-radius: 14px; background: linear-gradient(120deg,#0f172a,#1e3a5f); color: #fff; padding: 1.1rem 1.35rem; }
    .header-card .meta-chip { font-size: .72rem; background: rgba(255,255,255,.12); padding: .2rem .55rem; border-radius: 6px; color: #e2e8f0; }
</style>
@endpush

@section('content')

{{-- ── Header ─────────────────────────────────────────────── --}}
<div class="header-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h5 class="mb-1 fw-bold"><i class="bi bi-buildings me-1"></i> {{ $agency?->name }}</h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="meta-chip"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, d F Y') }}</span>
                @if($agency?->rl_number)<span class="meta-chip">RL: {{ $agency->rl_number }}</span>@endif
                @if($agency?->license_number)<span class="meta-chip">License: {{ $agency->license_number }}</span>@endif
                @if($subscription)
                    <span class="badge badge-status-{{ $subscription->status }}">{{ ucfirst($subscription->status) }} · {{ $subscription->plan->name ?? 'Plan' }}</span>
                @else
                    <span class="badge badge-status-expired">No subscription</span>
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('create', \App\Models\HrProfile::class)
            <a href="{{ route('hr.create') }}" class="btn btn-sm btn-light fw-semibold"><i class="bi bi-plus-lg me-1"></i> Add HR</a>
            @endcan
            @can('create', \App\Models\EmbassyList::class)
            <a href="{{ route('embassy-lists.create') }}" class="btn btn-sm btn-outline-light"><i class="bi bi-list-ol me-1"></i> Embassy List</a>
            @endcan
            <a href="{{ route('hr.index') }}" class="btn btn-sm btn-outline-light"><i class="bi bi-file-earmark-pdf me-1"></i> Generate Documents</a>
        </div>
    </div>
</div>

{{-- ── Alerts ─────────────────────────────────────────────── --}}
<x-alert-panel :alerts="$alerts" />

{{-- Super-admin notices --}}
@if($agency?->notices?->count())
<div class="mb-3">
    @foreach($agency->notices as $notice)
    <div class="alert alert-{{ in_array($notice->type, ['danger','warning','info','success']) ? $notice->type : 'info' }} alert-dismissible fade show py-2" style="font-size:.82rem;border-radius:10px;">
        <strong>{{ $notice->title }}:</strong> {{ $notice->body }}
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>
    @endforeach
</div>
@endif

{{-- ── Summary metric cards ──────────────────────────────── --}}
<div class="row g-3 mb-3">
    @php
        $metrics = [
            ['route' => route('hr.index'), 'ic' => 'bi-person-vcard', 'cls' => 'm-blue', 'label' => 'HR / Candidates', 'value' => $stats['total_hr'], 'sub' => $stats['active_hr'].' active', 'subcls' => 'text-success'],
            ['route' => route('agents.index'), 'ic' => 'bi-people', 'cls' => 'm-green', 'label' => 'Agents', 'value' => $stats['total_agents'], 'sub' => $stats['active_agents'].' active', 'subcls' => 'text-success'],
            ['route' => route('embassy-lists.index'), 'ic' => 'bi-list-ol', 'cls' => 'm-amber', 'label' => 'Embassy Lists', 'value' => $stats['embassy_lists_month'], 'sub' => 'this month', 'subcls' => 'text-muted'],
            ['route' => route('hr.index'), 'ic' => 'bi-file-earmark-pdf', 'cls' => 'm-violet', 'label' => 'PDF Downloads', 'value' => $stats['pdf_downloads_month'], 'sub' => ($subscription && ($subscription->plan->max_pdf_monthly ?? 0) < 9999 ? 'of '.$subscription->plan->max_pdf_monthly.' / mo' : 'this month'), 'subcls' => 'text-muted'],
            ['route' => route('hr.index', ['filter' => 'passport_expiring']), 'ic' => 'bi-passport', 'cls' => 'm-rose', 'label' => 'Passport Expiring', 'value' => $stats['passports_expiring'], 'sub' => 'within 6 months', 'subcls' => $stats['passports_expiring'] > 0 ? 'text-danger' : 'text-muted'],
            ['route' => route('embassy-lists.index', ['status' => 'draft']), 'ic' => 'bi-hourglass-split', 'cls' => 'm-teal', 'label' => 'Pending Drafts', 'value' => $stats['hr_draft_embassy'], 'sub' => 'embassy lists', 'subcls' => $stats['hr_draft_embassy'] > 0 ? 'text-warning' : 'text-muted'],
        ];
    @endphp
    @foreach($metrics as $m)
    <div class="col-6 col-md-4 col-xl-2">
        <a href="{{ $m['route'] }}" class="metric text-decoration-none">
            <span class="m-wm"><i class="bi {{ $m['ic'] }}"></i></span>
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="m-ic {{ $m['cls'] }}"><i class="bi {{ $m['ic'] }}"></i></span>
                <i class="bi bi-arrow-right m-go"></i>
            </div>
            <div class="m-label">{{ $m['label'] }}</div>
            <div class="m-value">{{ $m['value'] }}</div>
            <div class="m-sub {{ $m['subcls'] }}">{{ $m['sub'] }}</div>
        </a>
    </div>
    @endforeach
</div>

{{-- ── Subscription + Quick Actions ──────────────────────── --}}
<div class="row g-3 mb-3">
    {{-- Subscription --}}
    <div class="col-lg-5">
        @if($subscription)
        @php $daysLeft = $subscription->daysRemaining(); @endphp
        <div class="card h-100 {{ $daysLeft <= 3 ? 'border-danger' : ($daysLeft <= 7 ? 'border-warning' : '') }}">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-credit-card me-1"></i> Subscription &amp; Usage</span>
                <span class="badge badge-status-{{ $subscription->status }}">{{ ucfirst($subscription->status) }}</span>
            </div>
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold fs-6">{{ $subscription->plan->name ?? '—' }}</div>
                        <small class="text-muted">Expires {{ optional($subscription->end_date)->format('d M Y') }}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold {{ $daysLeft <= 7 ? 'text-danger' : 'text-success' }}" style="font-size:1.1rem;">{{ $daysLeft }}</div>
                        <small class="text-muted">days left</small>
                    </div>
                </div>
                <x-usage-meter label="HR Profiles" :used="$stats['total_hr']" :limit="$subscription->plan->max_hr ?? 9999" color="primary" />
                <x-usage-meter label="Agents" :used="$stats['total_agents']" :limit="$subscription->plan->max_agents ?? 9999" color="success" />
                <x-usage-meter label="Embassy Lists (month)" :used="$stats['embassy_lists_month']" :limit="$subscription->plan->max_embassy_lists_monthly ?? 9999" color="warning" />
                <x-usage-meter label="PDF Downloads (month)" :used="$stats['pdf_downloads_month']" :limit="$subscription->plan->max_pdf_monthly ?? 9999" color="info" />
            </div>
        </div>
        @else
        <div class="card h-100 border-warning">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                <i class="bi bi-credit-card-x fs-1 text-warning opacity-50 mb-2"></i>
                <div class="fw-semibold">No Active Subscription</div>
                <p class="text-muted small mt-1 mb-3">You cannot create new records or generate PDFs until you subscribe.</p>
                <a href="{{ route('subscription.expired') }}" class="btn btn-warning btn-sm"><i class="bi bi-send me-1"></i> Request Renewal</a>
            </div>
        </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-lightning-charge me-1"></i> Quick Actions</div>
            <div class="card-body py-3">
                <div class="row g-2">
                    @can('create', \App\Models\Agent::class)
                    <div class="col-sm-6">
                        <a href="{{ route('agents.create') }}" class="qa">
                            <span class="qa-ic m-green"><i class="bi bi-person-plus"></i></span>
                            <div><div class="qa-title">Add Agent</div><div class="qa-sub">{{ $stats['total_agents'] }} total</div></div>
                        </a>
                    </div>
                    @endcan
                    @can('create', \App\Models\HrProfile::class)
                    <div class="col-sm-6">
                        <a href="{{ route('hr.create') }}" class="qa">
                            <span class="qa-ic m-blue"><i class="bi bi-person-vcard"></i></span>
                            <div><div class="qa-title">Add HR Profile</div><div class="qa-sub">{{ $stats['total_hr'] }} total</div></div>
                        </a>
                    </div>
                    @endcan
                    @can('create', \App\Models\EmbassyList::class)
                    <div class="col-sm-6">
                        <a href="{{ route('embassy-lists.create') }}" class="qa">
                            <span class="qa-ic m-amber"><i class="bi bi-list-ol"></i></span>
                            <div><div class="qa-title">Create Embassy List</div><div class="qa-sub">{{ $stats['embassy_lists_month'] }} this month</div></div>
                        </a>
                    </div>
                    @endcan
                    <div class="col-sm-6">
                        <a href="{{ route('hr.index') }}" class="qa">
                            <span class="qa-ic m-violet"><i class="bi bi-file-earmark-pdf"></i></span>
                            <div><div class="qa-title">Generate Documents</div><div class="qa-sub">{{ $stats['pdf_downloads_month'] }} PDFs this month</div></div>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="{{ route('hr.index') }}" class="qa">
                            <span class="qa-ic m-teal"><i class="bi bi-search"></i></span>
                            <div><div class="qa-title">View Candidates</div><div class="qa-sub">Browse &amp; filter HR files</div></div>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="{{ route('embassy-lists.index') }}" class="qa">
                            <span class="qa-ic m-rose"><i class="bi bi-collection"></i></span>
                            <div><div class="qa-title">Embassy Lists</div><div class="qa-sub">{{ $stats['total_embassy_lists'] }} total</div></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Work Queue ────────────────────────────────────────── --}}
@php
    $queue = [];
    if (($stats['hr_draft_embassy'] ?? 0) > 0)   $queue[] = ['ic'=>'bi-hourglass-split','bg'=>'#fef3c7','fg'=>'#92400e','title'=>'Draft embassy lists to finalize','count'=>$stats['hr_draft_embassy'],'url'=>route('embassy-lists.index',['status'=>'draft'])];
    if (($stats['hr_no_passport'] ?? 0) > 0)     $queue[] = ['ic'=>'bi-person-exclamation','bg'=>'#dbeafe','fg'=>'#1e40af','title'=>'Active HR profiles missing passport','count'=>$stats['hr_no_passport'],'url'=>route('hr.index')];
    if (($stats['passports_expiring'] ?? 0) > 0) $queue[] = ['ic'=>'bi-passport','bg'=>'#fee2e2','fg'=>'#991b1b','title'=>'Passports expiring within 6 months','count'=>$stats['passports_expiring'],'url'=>route('hr.index',['filter'=>'passport_expiring'])];
@endphp
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header py-2"><i class="bi bi-check2-square me-1"></i> Work Queue</div>
            <div class="card-body py-2">
                @forelse($queue as $q)
                <a href="{{ $q['url'] }}" class="wq-item text-decoration-none text-reset">
                    <span class="wq-ic" style="background:{{ $q['bg'] }};color:{{ $q['fg'] }};"><i class="bi {{ $q['ic'] }}"></i></span>
                    <div class="flex-grow-1"><div style="font-size:.82rem;font-weight:600;">{{ $q['title'] }}</div></div>
                    <span class="badge rounded-pill" style="background:{{ $q['bg'] }};color:{{ $q['fg'] }};">{{ $q['count'] }}</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-check-circle fs-3 text-success opacity-50 d-block mb-2"></i>
                    <div style="font-size:.85rem;">All caught up — no pending tasks.</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Embassy Lists --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span><i class="bi bi-list-ol me-1"></i> Recent Embassy Lists</span>
                <a href="{{ route('embassy-lists.index') }}" class="btn btn-sm btn-outline-primary py-0">View All</a>
            </div>
            @if($recentEmbassyLists->count())
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead><tr><th>List No</th><th>Date</th><th class="text-center">Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach($recentEmbassyLists as $list)
                        <tr>
                            <td class="font-monospace fw-semibold" style="font-size:.8rem;">{{ $list->list_no }}</td>
                            <td><small class="text-muted">{{ optional($list->list_date)->format('d M Y') }}</small></td>
                            <td class="text-center fw-bold">{{ $list->total_items }}</td>
                            <td><span class="badge badge-status-{{ $list->status }}">{{ ucfirst($list->status) }}</span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('embassy-lists.show', $list) }}" class="btn btn-sm btn-outline-secondary py-0" title="View"><i class="bi bi-eye"></i></a>
                                    @if($list->isFinalized() || $list->status === 'printed')
                                    <a href="{{ route('embassy-lists.download-pdf', $list) }}" class="btn btn-sm btn-outline-primary py-0" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
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

{{-- ── Recent HR ─────────────────────────────────────────── --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span><i class="bi bi-person-vcard me-1"></i> Recent HR Files</span>
        <a href="{{ route('hr.index') }}" class="btn btn-sm btn-outline-primary py-0">View All</a>
    </div>
    @if($recentHr->count())
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Name</th><th>Passport</th><th>Visa</th><th>Agent</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach($recentHr as $hr)
                <tr>
                    <td><div class="fw-semibold">{{ $hr->full_name_en }}</div><small class="text-muted">{{ $hr->nationality }}</small></td>
                    <td>
                        @if($hr->passport?->passport_number)
                            <code style="font-size:.75rem;">{{ $hr->passport->passport_number }}</code>
                            @if($hr->passport->expiry_date?->isPast())
                                <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Passport expired"></i>
                            @elseif($hr->passport->expiry_date && $hr->passport->expiry_date->isBefore(now()->addMonths(6)))
                                <i class="bi bi-exclamation-triangle text-warning ms-1" title="Expiring soon"></i>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><small>{{ $hr->visa?->visa_number ?? '—' }}</small></td>
                    <td><small class="text-muted">{{ $hr->agent?->name ?? '—' }}</small></td>
                    <td><span class="badge badge-status-{{ $hr->status }}">{{ ucfirst($hr->status) }}</span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('hr.show', $hr) }}" class="btn btn-sm btn-outline-secondary py-0" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('hr.documents', $hr) }}" class="btn btn-sm btn-outline-success py-0" title="Documents"><i class="bi bi-file-earmark-pdf"></i></a>
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

{{-- ── Recent Document Activity ──────────────────────────── --}}
@if($recentDocActivity->count())
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span><i class="bi bi-clock-history me-1"></i> Recent Document Activity</span>
        <small class="text-muted">Last {{ $recentDocActivity->count() }} events</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Candidate</th><th>Document</th><th>Action</th><th>By</th><th>When</th></tr></thead>
            <tbody>
                @foreach($recentDocActivity as $doc)
                <tr>
                    <td>
                        @if($doc->hrProfile)
                            <a href="{{ route('hr.show', $doc->hrProfile) }}" class="text-decoration-none fw-semibold" style="font-size:.8rem;">{{ $doc->hrProfile->full_name_en }}</a>
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
