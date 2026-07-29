@extends('layouts.agency-app')
@section('title', 'Add New Agent')
@section('page-title', 'Add New Agent')

@php
    $inputCls = 'h-10 w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400';
@endphp

@section('content')
<x-ui.page-header title="Add New Agent" subtitle="Fill in the agent's details below" icon="bi-person-plus">
    <x-slot:actions>
        <x-ui.button :href="route('agents.index')" variant="secondary" class="cursor-pointer"><i class="bi bi-arrow-left"></i> Back to Agents</x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('agents.store') }}" id="agentForm" novalidate>
    @csrf
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- Main form --}}
        <div class="lg:col-span-2">
            <x-ui.card>
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">
                    <i class="bi bi-person text-brand-600"></i> Agent Information
                </div>
                <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                    {{-- Name --}}
                    <x-ui.field label="Full Name" name="name" required>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Mohammed Al-Rashid"
                            class="{{ $inputCls }} @error('name') border-rose-400 @enderror" required autofocus>
                    </x-ui.field>

                    {{-- Phone --}}
                    <x-ui.field label="Phone" name="phone" required>
                        <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 transition-colors focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400 @error('phone') border-rose-400 @enderror">
                            <i class="bi bi-telephone text-sm text-slate-400"></i>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+966501234567"
                                class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0" required>
                        </div>
                    </x-ui.field>

                    {{-- Email --}}
                    <x-ui.field label="Email" name="email" hint="Must be unique within your agency.">
                        <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 transition-colors focus-within:border-brand-400 focus-within:ring-1 focus-within:ring-brand-400 @error('email') border-rose-400 @enderror">
                            <i class="bi bi-envelope text-sm text-slate-400"></i>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="agent@example.com"
                                class="h-10 w-full border-0 bg-transparent p-0 text-sm focus:ring-0">
                        </div>
                    </x-ui.field>

                    {{-- Status --}}
                    <x-ui.field label="Status" name="status" required>
                        <select name="status" class="{{ $inputCls }} @error('status') border-rose-400 @enderror">
                            <option value="active"   @selected(old('status','active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                    </x-ui.field>

                    {{-- Address --}}
                    <x-ui.field label="Address" name="address" required class="sm:col-span-2">
                        <textarea name="address" rows="2" placeholder="Full address including city and district"
                            class="w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 @error('address') border-rose-400 @enderror" required>{{ old('address') }}</textarea>
                    </x-ui.field>

                    {{-- Notes --}}
                    <x-ui.field label="Notes" name="notes" hint="Optional — any additional notes about this agent." class="sm:col-span-2">
                        <textarea name="notes" rows="2" placeholder="Any additional notes about this agent…"
                            class="w-full rounded-lg border-slate-300 text-sm transition-colors focus:border-brand-400 focus:ring-brand-400 @error('notes') border-rose-400 @enderror">{{ old('notes') }}</textarea>
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        {{-- Actions sidebar --}}
        <div class="space-y-5">
            <x-ui.card>
                <div class="border-b border-slate-100 px-5 py-3 text-sm font-bold text-slate-800">Actions</div>
                <div class="flex flex-col gap-2 p-5">
                    <x-ui.button type="submit" class="w-full cursor-pointer"><i class="bi bi-check-lg"></i> Save Agent</x-ui.button>
                    <x-ui.button type="reset" variant="secondary" class="w-full cursor-pointer"><i class="bi bi-arrow-counterclockwise"></i> Reset Form</x-ui.button>
                    <x-ui.button :href="route('agents.index')" variant="secondary" class="w-full cursor-pointer"><i class="bi bi-x-lg"></i> Cancel</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card class="border-l-4 border-l-brand-500">
                <div class="flex items-start gap-2 p-4 text-xs text-slate-500">
                    <i class="bi bi-info-circle mt-0.5 text-brand-500"></i>
                    <span><strong class="text-slate-700">Required fields</strong> are marked with <span class="text-rose-500">*</span>. Email is optional but must be unique per agency if provided.</span>
                </div>
            </x-ui.card>
        </div>
    </div>
</form>
@endsection
