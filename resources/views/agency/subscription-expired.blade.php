@extends('layouts.agency')
@section('title', 'Subscription Expired')
@section('page-title', 'Subscription Renewal')

@section('content')
<div class="row justify-content-center mt-2">
    <div class="col-lg-6 col-md-8">

        {{-- Status Banner --}}
        <div class="text-center mb-4">
            <div style="width:72px;height:72px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="bi bi-slash-circle text-danger" style="font-size:2rem;"></i>
            </div>
            <h4 class="fw-bold mb-1">Subscription Expired</h4>
            <p class="text-muted mb-0" style="font-size:.875rem;">
                You can still view existing data. Creating new records and generating PDFs requires an active subscription.
            </p>
        </div>

        {{-- Last subscription info --}}
        @if($lastSubscription)
        <div class="card mb-3" style="border-left:4px solid #fca5a5;">
            <div class="card-body py-3">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;">Last Plan</div>
                        <div class="fw-bold">{{ $lastSubscription->plan->name ?? '—' }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;">Expired On</div>
                        <div class="fw-bold text-danger">{{ $lastSubscription->end_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;">Plan Price</div>
                        <div class="fw-bold">
                            @if($lastSubscription->plan?->price)
                                {{ $lastSubscription->plan->priceLabel('/period') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if(session('success'))
        <div class="alert alert-success py-2 mb-3" style="border-radius:8px;">
            <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
        </div>
        @endif

        {{-- Renewal form --}}
        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-send me-1"></i> Request Renewal
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Fill in the form below and our team will contact you to process your renewal.
                </p>
                <form method="POST" action="{{ route('subscription.renew-request') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Message <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="message" class="form-control form-control-sm" rows="3"
                            placeholder="Mention preferred plan, number of candidates, or any special requirements..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-1"></i> Send Renewal Request
                    </button>
                </form>
            </div>
        </div>

        {{-- Navigation links --}}
        <div class="mt-3 text-center" style="font-size:.82rem;">
            <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none me-3">
                <i class="bi bi-house me-1"></i>Dashboard
            </a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link p-0 text-muted" style="font-size:.82rem;">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
