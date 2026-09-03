<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ auth()->user()->agency->name ?? 'Agency' }}</title>

    {{-- Icon font only (framework-agnostic), used across the app --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        @media (prefers-reduced-motion: reduce){
            *,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}
        }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased">

    {{-- Persistent top app-header (shared with the ERP layout) --}}
    <x-app-header />

    {{-- Page content --}}
    <main class="mx-auto max-w-[1600px] p-4 sm:p-6">
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" class="mb-4 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <i class="bi bi-check-circle-fill mt-0.5 text-emerald-500"></i>
                <span class="flex-1">{{ session('success') }}</span>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <i class="bi bi-exclamation-circle-fill mt-0.5 text-rose-500"></i>
                <span class="flex-1">{{ session('error') }}</span>
                <button @click="show = false" class="text-rose-500 hover:text-rose-700"><i class="bi bi-x-lg"></i></button>
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <i class="bi bi-exclamation-circle-fill mr-1.5 text-rose-500"></i>{{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>

@stack('scripts')
</body>
</html>
