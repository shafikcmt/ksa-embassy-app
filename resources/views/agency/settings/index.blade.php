@extends('layouts.agency-app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@php
    $inputCls = 'h-10 w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400';
    $roInputCls = 'h-10 w-full rounded-lg border-slate-200 bg-slate-100 text-sm text-slate-500';
    $areaCls = 'w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400';

    // Active-tab detection — same URL (?tab=…) logic as before.
    $activeTab = request('tab', 'profile');
    $tabBase = 'inline-flex items-center gap-1.5 whitespace-nowrap border-b-2 px-1 pb-2.5 pt-1 text-sm font-semibold transition-colors cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-1';
    $tabOn = 'border-brand-600 text-brand-700';
    $tabOff = 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700';
@endphp

@section('content')
<div class="mx-auto max-w-4xl">

    <x-ui.page-header title="Settings" subtitle="Manage your agency profile, print, notifications and form fields" icon="bi-gear" />

    {{-- Tabs (URL-based ?tab=…) --}}
    <div class="mb-5 flex gap-6 overflow-x-auto border-b border-slate-200">
        <a href="{{ route('settings.index') }}?tab=profile"
           @class([$tabBase, $tabOn => (!request()->has('tab') || request('tab') === 'profile'), $tabOff => !(!request()->has('tab') || request('tab') === 'profile')])>
            <i class="bi bi-building"></i> Agency Profile
        </a>
        <a href="{{ route('settings.index') }}?tab=print"
           @class([$tabBase, $tabOn => request('tab') === 'print', $tabOff => request('tab') !== 'print'])>
            <i class="bi bi-printer"></i> Print Settings
        </a>
        <a href="{{ route('settings.index') }}?tab=notifications"
           @class([$tabBase, $tabOn => request('tab') === 'notifications', $tabOff => request('tab') !== 'notifications'])>
            <i class="bi bi-bell"></i> Notifications
        </a>
        @if($canManageFields ?? false)
            <a href="{{ route('settings.index') }}?tab=hr_fields"
               @class([$tabBase, $tabOn => request('tab') === 'hr_fields', $tabOff => request('tab') !== 'hr_fields'])>
                <i class="bi bi-ui-checks"></i> HR Form Fields
            </a>
        @endif
    </div>

    {{-- Profile Tab --}}
    @if(!request()->has('tab') || request('tab') === 'profile')
    <div id="profile">
        <x-ui.card>
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                <i class="bi bi-building text-brand-600"></i> Agency Profile
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('settings.update') }}" id="profileForm" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <input type="hidden" name="tab" value="profile">

                    {{-- Name (contact person / owner) --}}
                    <x-ui.field label="Name" name="owner_name" required class="mb-4">
                        <input type="text" name="owner_name" value="{{ old('owner_name', $agency->owner_name) }}"
                            placeholder="Contact person / owner name"
                            class="{{ $inputCls }} @error('owner_name') border-rose-400 @enderror">
                    </x-ui.field>

                    {{-- Company Type + Referral Code --}}
                    @php $companyType = old('company_type', $agency->company_type); @endphp
                    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field label="Company Type" name="company_type" hint="Type of agency.">
                            <select name="company_type" class="{{ $inputCls }} @error('company_type') border-rose-400 @enderror">
                                <option value="">— Select —</option>
                                <option value="recruiting" {{ $companyType === 'recruiting' ? 'selected' : '' }}>Recruiting Agency</option>
                                <option value="consultancy" {{ $companyType === 'consultancy' ? 'selected' : '' }}>Consultancy Agency</option>
                            </select>
                        </x-ui.field>
                        <x-ui.field label="Referral Code" name="referral_code" hint="Optional referral code.">
                            <input type="text" name="referral_code" value="{{ old('referral_code', $agency->referral_code) }}"
                                placeholder="e.g. REF-1234"
                                class="{{ $inputCls }} @error('referral_code') border-rose-400 @enderror">
                        </x-ui.field>
                    </div>

                    {{-- Company + RL No — read-only (from license / registration data) --}}
                    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.field label="Company" hint="Registered company name (from license data)." class="sm:col-span-2">
                            <input type="text" value="{{ $agency->name }}" disabled readonly class="{{ $roInputCls }}">
                        </x-ui.field>
                        <x-ui.field label="RL No" hint="Recruiting license number.">
                            <input type="text" value="{{ $agency->rl_number ?: '—' }}" disabled readonly class="{{ $roInputCls }}">
                        </x-ui.field>
                    </div>

                    {{-- License info (read-only, from subscription/license system) --}}
                    <div class="mb-4 flex flex-wrap gap-8 rounded-lg bg-slate-100 px-4 py-3">
                        <div>
                            <div class="text-[0.68rem] uppercase tracking-wide text-slate-400">License No.</div>
                            <div class="text-sm text-slate-700">{{ $agency->license_number ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-[0.68rem] uppercase tracking-wide text-slate-400">License Expiry</div>
                            <div class="text-sm text-slate-700">{{ $agency->license_expiry_date?->format('d M Y') ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <x-ui.field label="Address" name="address" class="mb-4">
                        <textarea name="address" rows="2" placeholder="Full agency address"
                            class="{{ $areaCls }} @error('address') border-rose-400 @enderror">{{ old('address', $agency->address) }}</textarea>
                    </x-ui.field>

                    {{-- Official Email + Phone --}}
                    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field label="Official Email" name="official_email" hint="Shown on documents & notifications.">
                            <input type="email" name="official_email" value="{{ old('official_email', $agency->email) }}"
                                placeholder="agency@example.com"
                                class="{{ $inputCls }} @error('official_email') border-rose-400 @enderror">
                        </x-ui.field>
                        <x-ui.field label="Phone" name="phone">
                            <input type="text" name="phone" value="{{ old('phone', $agency->phone) }}" placeholder="+880…"
                                class="{{ $inputCls }} @error('phone') border-rose-400 @enderror">
                        </x-ui.field>
                    </div>

                    {{-- Agency Logo (self-upload; stored on the public disk) --}}
                    <x-ui.field label="Agency Logo" name="logo" hint="PNG/JPG, up to 2 MB. Shown on printed / PDF documents when Print Logo is enabled." class="mb-4">
                        <div class="flex items-center gap-4">
                            @if($agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo))
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($agency->logo) }}" alt="Agency logo"
                                     class="h-14 w-auto max-w-[120px] rounded-lg border border-slate-200 bg-white object-contain p-1">
                            @else
                                <span class="grid h-14 w-14 place-items-center rounded-lg border border-dashed border-slate-300 text-slate-300"><i class="bi bi-image"></i></span>
                            @endif
                            <input type="file" name="logo" accept="image/*"
                                class="block w-full text-sm text-slate-600 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100 @error('logo') border-rose-400 @enderror">
                        </div>
                    </x-ui.field>

                    {{-- Print Logo --}}
                    <x-ui.field label="Print Logo" required hint="Show the agency logo on printed / PDF documents." class="mb-4">
                        @php $printLogo = (int) old('print_logo', (int) $agency->print_logo); @endphp
                        <div class="flex gap-6 pt-1">
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input class="cursor-pointer border-slate-300 text-brand-600 focus:ring-brand-500" type="radio" name="print_logo" id="printLogoYes" value="1" {{ $printLogo === 1 ? 'checked' : '' }}>
                                <span class="text-sm text-slate-700">Yes</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input class="cursor-pointer border-slate-300 text-brand-600 focus:ring-brand-500" type="radio" name="print_logo" id="printLogoNo" value="0" {{ $printLogo === 0 ? 'checked' : '' }}>
                                <span class="text-sm text-slate-700">No</span>
                            </label>
                        </div>
                    </x-ui.field>

                    <hr class="my-5 border-slate-100">
                    <div class="mb-3 text-[0.68rem] font-semibold uppercase tracking-[0.04em] text-slate-400">Login &amp; Security</div>

                    {{-- Login Email + Current Password --}}
                    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.field label="Email" name="login_email" required hint="Used to sign in to your account.">
                            <input type="email" name="login_email" value="{{ old('login_email', $user->email) }}"
                                class="{{ $inputCls }} @error('login_email') border-rose-400 @enderror">
                        </x-ui.field>
                        <x-ui.field label="Current Password" name="current_password" hint="Only needed when changing the login email.">
                            <input type="password" name="current_password" autocomplete="current-password"
                                placeholder="Required to change login email"
                                class="{{ $inputCls }} @error('current_password') border-rose-400 @enderror">
                        </x-ui.field>
                    </div>

                    <div class="flex gap-2">
                        <x-ui.button type="submit" class="cursor-pointer"><i class="bi bi-floppy"></i> Update</x-ui.button>
                        <x-ui.button type="reset" variant="secondary" class="cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i> Reset</x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.card>
    </div>
    @endif

    {{-- Print Settings Tab --}}
    @if(request('tab') === 'print')
    <div id="print">
        <x-ui.card>
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                <i class="bi bi-printer text-brand-600"></i> Print Settings
            </div>
            <div class="p-5">
                <p class="mb-4 text-sm text-slate-500">
                    These texts appear in the header and footer of all printed / exported PDF documents.
                </p>
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="tab" value="print">

                    <x-ui.field label="Document Header Text" name="print_header" hint="Displayed at the top of printed documents." class="mb-4">
                        <textarea name="print_header" rows="3" class="{{ $areaCls }}"
                            placeholder="e.g. Kingdom of Saudi Arabia — Ministry of Human Resources…">{{ old('print_header', $printHeader) }}</textarea>
                    </x-ui.field>

                    <x-ui.field label="Document Footer Text" name="print_footer" hint="Displayed at the bottom of printed documents." class="mb-5">
                        <textarea name="print_footer" rows="3" class="{{ $areaCls }}"
                            placeholder="e.g. This document is issued by Al-Noor Recruitment Agency…">{{ old('print_footer', $printFooter) }}</textarea>
                    </x-ui.field>

                    <x-ui.button type="submit" class="cursor-pointer"><i class="bi bi-floppy"></i> Save Print Settings</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
    @endif

    {{-- Notifications Tab --}}
    @if(request('tab') === 'notifications')
    <div id="notifications">
        <x-ui.card>
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                <i class="bi bi-bell text-brand-600"></i> Notification Settings
            </div>
            <div class="p-5">
                <p class="mb-4 text-sm text-slate-500">
                    Control which email notifications you receive from the system.
                    Emails are sent to: <strong class="text-slate-700">{{ $agency->email ?? 'not set' }}</strong>
                </p>
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="tab" value="notifications">

                    <div class="mb-4">
                        <label class="inline-flex cursor-pointer items-start gap-3">
                            <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center">
                                <input type="checkbox" name="notify_subscription_expiry" id="notifySub" value="1" {{ $notifySubscription === '1' ? 'checked' : '' }} class="peer sr-only">
                                <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 peer-focus-visible:ring-offset-1"></span>
                                <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                            </span>
                            <span class="text-sm">
                                <span class="block font-semibold text-slate-800">Subscription Expiry Reminders</span>
                                <span class="block text-slate-500">Receive email when subscription expires or is about to expire.</span>
                            </span>
                        </label>
                    </div>

                    <div class="mb-5">
                        <label class="inline-flex cursor-pointer items-start gap-3">
                            <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center">
                                <input type="checkbox" name="notify_passport_expiry" id="notifyPassport" value="1" {{ $notifyPassport === '1' ? 'checked' : '' }} class="peer sr-only">
                                <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 peer-focus-visible:ring-offset-1"></span>
                                <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                            </span>
                            <span class="text-sm">
                                <span class="block font-semibold text-slate-800">Passport Expiry Alerts</span>
                                <span class="block text-slate-500">Receive email when HR candidate passports are expiring within 30 days.</span>
                            </span>
                        </label>
                    </div>

                    <x-ui.button type="submit" class="cursor-pointer"><i class="bi bi-floppy"></i> Save Notification Settings</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
    @endif

    {{-- HR Form Fields Tab --}}
    @if(request('tab') === 'hr_fields' && ($canManageFields ?? false))
    <div id="hr-fields">
        <x-ui.card>
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                <span class="flex items-center gap-2 text-sm font-bold text-slate-800"><i class="bi bi-ui-checks text-brand-600"></i> HR Form Field Controls</span>
                <x-ui.badge tone="slate">Active / Inactive</x-ui.badge>
            </div>
            <div class="p-5">
                <p class="mb-4 text-sm text-slate-500">
                    Turn fields <strong class="text-slate-700">On</strong> to show them on the Add / Edit HR form, or <strong class="text-slate-700">Off</strong> to hide them.
                    Required fields are always shown and can't be turned off. Changes apply to your agency's HR form only.
                </p>

                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="tab" value="hr_fields">

                    @foreach($hrFieldGroups as $section => $fields)
                        <div class="mb-5">
                            <div class="mb-2 text-[0.68rem] font-semibold uppercase tracking-[0.04em] text-slate-400">{{ $section }}</div>
                            <div class="overflow-hidden rounded-lg border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <th class="w-[55%] px-4 py-2.5">Field Name</th>
                                            <th class="w-[25%] px-4 py-2.5">Type</th>
                                            <th class="w-[20%] px-4 py-2.5 text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($fields as $field)
                                            <tr>
                                                <td class="px-4 py-2.5 font-semibold text-slate-800">{{ $field['label'] }}</td>
                                                <td class="px-4 py-2.5">
                                                    @if($field['required'])
                                                        <x-ui.badge tone="slate">Required</x-ui.badge>
                                                    @else
                                                        <span class="text-xs text-slate-400">Optional</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-right">
                                                    @if($field['required'])
                                                        <x-ui.badge tone="green"><i class="bi bi-lock-fill"></i> Always on</x-ui.badge>
                                                    @else
                                                        <label class="relative inline-flex h-6 w-11 cursor-pointer items-center">
                                                            <input type="checkbox" name="fields[]" value="{{ $field['key'] }}" id="hrf_{{ $field['key'] }}"
                                                                   {{ ($hrFieldStatuses[$field['key']] ?? true) ? 'checked' : '' }} class="peer sr-only">
                                                            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 peer-focus-visible:ring-offset-1"></span>
                                                            <span class="absolute left-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                                                        </label>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    <x-ui.button type="submit" class="mt-2 cursor-pointer"><i class="bi bi-floppy"></i> Save Field Settings</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
    @endif

</div>
@endsection
