<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create account — {{ config('app.name', 'VisaDeskPro') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-900 antialiased">
@php
    $field = 'block w-full';
    $label = 'mb-1.5 block text-sm font-semibold text-slate-700';
    $err   = 'mt-1.5 text-sm font-medium text-rose-600';
@endphp
<div class="grid min-h-screen lg:grid-cols-[1fr_1.1fr]">

    {{-- Brand panel --}}
    <aside class="relative hidden overflow-hidden bg-gradient-to-br from-[#06233f] via-[#0e3a66] to-[#0b6b66] p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <a href="/" class="flex items-center gap-3 text-xl font-extrabold tracking-tight">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15 ring-1 ring-white/25">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            </span>
            VisaDesk<span class="text-emerald-300">Pro</span>
        </a>

        <div class="relative z-10 max-w-md">
            <h2 class="text-4xl font-extrabold leading-tight tracking-tight">Complete Embassy File Solution for your agency</h2>
            <p class="mt-4 text-base leading-relaxed text-white/85">Manage HR records, MOFA, medical, stamping, delivery, invoices and print-ready KSA documents from one secure dashboard.</p>
            <ul class="mt-8 space-y-3 text-base font-medium">
                @foreach(['Official KSA application formats', 'Type once — auto-fill everywhere', 'Every agency sees only its own data'] as $point)
                    <li class="flex items-center gap-3">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-emerald-400/90 text-[#06233f]"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        {{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative z-10 text-sm text-white/70">&copy; {{ date('Y') }} VisaDeskPro. All rights reserved.</div>

        {{-- Mosque silhouette --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 opacity-30">@include('landing._hero-scenes', ['scene' => 'haram'])</div>
        <style>aside svg{width:100%;height:100%}</style>
    </aside>

    {{-- Form --}}
    <main class="flex items-center justify-center bg-slate-50 px-5 py-10 sm:px-8">
        <div class="w-full max-w-xl">
            <a href="/" class="mb-6 flex items-center justify-center gap-2 text-2xl font-extrabold tracking-tight lg:hidden">VisaDesk<span class="text-sky-500">Pro</span></a>

            <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Create your account</h1>
                <p class="mt-1 text-base text-slate-600">Agency, HR &amp; Visa Document Management System</p>

                <form method="POST" action="{{ route('register') }}" class="mt-7">
                    @csrf
                    <div class="grid gap-x-5 gap-y-5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="{{ $label }}">Name <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Enter your name" class="{{ $field }}">
                            @error('name')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="{{ $label }}">Email <span class="text-rose-500">*</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@example.com" class="{{ $field }}">
                            @error('email')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password" class="{{ $label }}">Password <span class="text-rose-500">*</span></label>
                            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Create password" class="{{ $field }}">
                            @error('password')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="{{ $label }}">Confirm Password <span class="text-rose-500">*</span></label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Confirm password" class="{{ $field }}">
                            @error('password_confirmation')<p class="{{ $err }}">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <button type="submit" class="mt-7 inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand-600 text-base font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                        Sign Up
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-600">Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-brand-700 underline-offset-2 hover:underline">Sign In here</a>
                </p>
            </div>
        </div>
    </main>
</div>
</body>
</html>
