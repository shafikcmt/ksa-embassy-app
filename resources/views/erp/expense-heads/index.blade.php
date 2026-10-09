@extends('layouts.erp-app')
@section('title', 'Expense Heads')
@section('page-title', 'Expense Heads')
@section('content')
@php $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500'; @endphp
<x-ui.page-header title="Expense Heads" subtitle="Manage reasons of costing for Expenses and Payment Vouchers" icon="bi-list-check">
    <x-slot:actions><x-ui.button :href="route('erp.settings')" variant="secondary">ERP Settings</x-ui.button></x-slot:actions>
</x-ui.page-header>
<p class="mb-5 text-sm text-slate-600">Deactivate heads to keep them out of new entries. Existing records retain their head. Agent payouts belong in Agent Khata.</p>
@if($errors->any())
    <div role="alert" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif
<x-ui.card class="mb-5 p-4">
    <h2 class="mb-3 text-sm font-semibold text-slate-900">Add expense head</h2>
    <form method="POST" action="{{ route('erp.expense-heads.store') }}" class="grid items-end gap-3 sm:grid-cols-4">
        @csrf
        <label class="sm:col-span-2"><span class="mb-1 block text-sm text-slate-600">Name</span><input name="name" value="{{ old('name') }}" maxlength="120" required class="{{ $input }}"></label>
        <label><span class="mb-1 block text-sm text-slate-600">Order</span><input type="number" name="sort_order" value="{{ old('sort_order', ($heads->max('sort_order') ?? 0) + 10) }}" min="0" max="1000000" required class="{{ $input }}"></label>
        <input type="hidden" name="is_active" value="1">
        <x-ui.button type="submit">Add head</x-ui.button>
    </form>
</x-ui.card>
<x-ui.card class="overflow-hidden">
    <div class="border-b border-slate-100 px-4 py-3"><h2 class="font-semibold text-slate-900">Agency expense heads</h2><p class="mt-1 text-xs text-slate-500">Lower order numbers appear first. Codes remain stable for CSV imports when names change.</p></div>
    <div class="divide-y divide-slate-100">
    @foreach($heads as $head)
        <div class="p-4">
            <form method="POST" action="{{ route('erp.expense-heads.update', $head) }}" class="grid items-end gap-3 sm:grid-cols-12">
                @csrf @method('PUT')
                <label class="sm:col-span-5"><span class="mb-1 block text-xs text-slate-500">Name</span><input name="name" value="{{ $head->name }}" maxlength="120" required @readonly($head->is_system) class="{{ $input }}"></label>
                <label class="sm:col-span-2"><span class="mb-1 block text-xs text-slate-500">Order</span><input type="number" name="sort_order" value="{{ $head->sort_order }}" min="0" max="1000000" required class="{{ $input }}"></label>
                <label class="sm:col-span-3"><span class="mb-1 block text-xs text-slate-500">Availability</span>
                    <select name="is_active" class="{{ $input }}">
                        @unless($head->is_system)<option value="1" @selected($head->is_active)>Active</option>@endunless
                        <option value="0" @selected(! $head->is_active)>Inactive</option>
                    </select>
                </label>
                <x-ui.button type="submit" variant="secondary" class="sm:col-span-2">Save</x-ui.button>
            </form>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                <span>{{ $head->code }} · {{ $head->expenses_count }} expenses · {{ $head->payment_vouchers_count }} vouchers @if($head->is_system) · Protected archive @endif</span>
                @if(! $head->is_system && ! $head->expenses_count && ! $head->payment_vouchers_count)
                    <form method="POST" action="{{ route('erp.expense-heads.destroy', $head) }}" onsubmit="return confirm('Delete this unused expense head?')">
                        @csrf @method('DELETE')<button type="submit" class="rounded px-2 py-1 text-rose-700 hover:bg-rose-50 focus-visible:ring-2 focus-visible:ring-rose-500">Delete unused head</button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
    </div>
</x-ui.card>
@endsection
