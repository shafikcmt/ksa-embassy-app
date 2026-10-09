@php
    $input = 'w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500';
    $label = 'mb-1 block text-sm font-medium text-slate-600';
@endphp
<form method="POST" action="{{ $action }}" class="max-w-4xl space-y-5" x-data="{ method: @js(old('payment_method', $voucher->payment_method ?: 'cash')), busy: false }" @submit="busy = true">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    @if($errors->any())
        <div role="alert" class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <x-ui.card class="p-5">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-2"><h2 class="font-semibold text-slate-900">Voucher details</h2><span class="text-sm text-slate-500">{{ $nextNumber }}</span></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <label><span class="{{ $label }}">Voucher Date</span><input type="date" name="voucher_date" value="{{ old('voucher_date', $voucher->voucher_date?->format('Y-m-d')) }}" required class="{{ $input }}"></label>
            <label><span class="{{ $label }}">Expense Head / Reason of Costing</span>
                <select name="expense_head_id" required class="{{ $input }}">
                    <option value="">Choose an expense head</option>
                    @foreach($expenseHeads as $head)
                        <option value="{{ $head->id }}" @selected((string) old('expense_head_id', $voucher->expense_head_id) === (string) $head->id)>{{ $head->name }}{{ $head->is_active ? '' : ' (inactive — current head)' }}</option>
                    @endforeach
                </select>
            </label>
            <label><span class="{{ $label }}">Paid To / Recipient</span><input name="payee_name" value="{{ old('payee_name', $voucher->payee_name) }}" maxlength="255" required class="{{ $input }}"></label>
            <label><span class="{{ $label }}">Amount (BDT)</span><input name="amount" type="number" min="0.01" max="99999999999.99" step="0.01" value="{{ old('amount', $voucher->exists ? $voucher->total_amount : '') }}" required class="{{ $input }}"></label>
            <label><span class="{{ $label }}">Payment Method</span><select name="payment_method" x-model="method" class="{{ $input }}">@foreach($paymentMethods as $key => $value)<option value="{{ $key }}" @selected(old('payment_method', $voucher->payment_method ?: 'cash') === $key)>{{ $value }}</option>@endforeach</select></label>
            <label><span class="{{ $label }}">Reference</span><input name="reference_number" value="{{ old('reference_number', $voucher->reference_number) }}" maxlength="100" class="{{ $input }}"></label>
            <label x-show="method === 'cheque'"><span class="{{ $label }}">Cheque Number</span><input name="cheque_number" value="{{ old('cheque_number', $voucher->cheque_number) }}" maxlength="50" :required="method === 'cheque'" class="{{ $input }}"></label>
            <label x-show="method === 'cheque' || method === 'bank_transfer'"><span class="{{ $label }}">Bank Name</span><input name="bank_name" value="{{ old('bank_name', $voucher->bank_name) }}" maxlength="100" class="{{ $input }}"></label>
            <label class="sm:col-span-2"><span class="{{ $label }}">Remarks (optional)</span><textarea name="notes" rows="3" maxlength="2000" class="{{ $input }}">{{ old('notes', $voucher->notes) }}</textarea></label>
        </div>
        @if(auth()->user()->isAgencyAdmin())<a href="{{ route('erp.expense-heads.index') }}" class="mt-4 inline-block text-sm text-brand-700 hover:underline">Manage expense heads</a>@endif
    </x-ui.card>
    @if($voucher->exists && (! $voucher->expense_head_id || $voucher->expenseHead?->is_system || $voucher->items->count() > 1 || (float) $voucher->tax_amount || (float) $voucher->discount_amount))
        <p class="text-sm text-slate-600">This draft has historical line items or adjustments. Saving this form replaces them with the amount entered above. Paid and approved vouchers remain locked.</p>
    @endif
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Save as draft. An agency admin approves and marks it paid.</p>
        <x-ui.button type="submit" x-bind:disabled="busy"><span x-text="busy ? 'Saving…' : 'Save draft'">Save draft</span></x-ui.button>
    </div>
</form>
