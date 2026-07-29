@extends('layouts.agency-app')
@section('title', 'Account Suspended')
@section('page-title', 'Account Suspended')

@section('content')
<div class="mx-auto max-w-md py-6 text-center">
    <div class="mx-auto mb-5 grid h-18 w-18 place-items-center rounded-full bg-rose-50" style="height:72px;width:72px;">
        <i class="bi bi-pause-circle text-3xl text-rose-600"></i>
    </div>
    <h1 class="mb-2 text-xl font-bold tracking-tight text-slate-900">Agency Account Suspended</h1>
    <p class="mb-5 text-sm text-slate-500">
        Your agency account has been suspended by the system administrator.
        You cannot access any features until the suspension is lifted.
    </p>

    <x-ui.card class="border-l-4 border-l-rose-400 text-left">
        <div class="p-4 text-sm">
            <div class="mb-1 flex items-center gap-1.5 font-semibold text-slate-800"><i class="bi bi-info-circle text-rose-600"></i> What to do:</div>
            <ul class="ml-5 list-disc space-y-0.5 text-slate-500">
                <li>Contact the system administrator</li>
                <li>Check your email for any notice regarding this suspension</li>
                <li>Verify your subscription and agency license are up to date</li>
            </ul>
        </div>
    </x-ui.card>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <x-ui.button type="submit" variant="secondary" size="sm" class="cursor-pointer"><i class="bi bi-box-arrow-right"></i> Logout</x-ui.button>
    </form>
</div>
@endsection
