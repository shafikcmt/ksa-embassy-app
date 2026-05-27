@extends('layouts.agency')
@section('title', 'Account Suspended')
@section('page-title', 'Account Suspended')

@section('content')
<div class="row justify-content-center mt-4">
    <div class="col-lg-5 col-md-7 text-center">
        <div style="width:72px;height:72px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
            <i class="bi bi-pause-circle text-danger" style="font-size:2rem;"></i>
        </div>
        <h4 class="fw-bold mb-2">Agency Account Suspended</h4>
        <p class="text-muted mb-4" style="font-size:.875rem;">
            Your agency account has been suspended by the system administrator.
            You cannot access any features until the suspension is lifted.
        </p>

        <div class="card text-start mb-3" style="border-left:4px solid #fca5a5;">
            <div class="card-body py-3" style="font-size:.85rem;">
                <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1 text-danger"></i>What to do:</div>
                <ul class="mb-0 ps-3 text-muted">
                    <li>Contact the system administrator</li>
                    <li>Check your email for any notice regarding this suspension</li>
                    <li>Verify your subscription and agency license are up to date</li>
                </ul>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </button>
        </form>
    </div>
</div>
@endsection
