@extends('layouts.agency-app')
@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-400 focus:ring-brand-400';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
@endphp

<div class="mx-auto max-w-xl">

    <x-ui.page-header title="Change Password"
        subtitle="Update the password for your account ({{ $user->email }})"
        icon="bi-key" />

    @if(session('status') === 'password-updated')
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            <i class="bi bi-check-circle-fill"></i> Your password has been updated.
        </div>
    @endif

    <x-ui.card>
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
            <i class="bi bi-shield-lock text-brand-600"></i> Update Password
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4 px-5 py-5">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="{{ $lbl }}">Current Password <span class="text-rose-500">*</span></label>
                <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                       class="{{ $inp }} @error('current_password', 'updatePassword') border-rose-400 @enderror">
                @error('current_password', 'updatePassword')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="{{ $lbl }}">New Password <span class="text-rose-500">*</span></label>
                <input type="password" id="password" name="password" required autocomplete="new-password"
                       class="{{ $inp }} @error('password', 'updatePassword') border-rose-400 @enderror">
                @error('password', 'updatePassword')<p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="{{ $lbl }}">Confirm New Password <span class="text-rose-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                       class="{{ $inp }}">
            </div>

            <div class="flex items-center justify-end gap-3 pt-1">
                <a href="{{ route('dashboard') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900">Cancel</a>
                <x-ui.button type="submit"><i class="bi bi-check-lg"></i> Update Password</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <p class="mt-4 text-xs text-slate-400">
        <i class="bi bi-info-circle"></i>
        For your security, you must enter your current password to set a new one.
    </p>

</div>
@endsection
