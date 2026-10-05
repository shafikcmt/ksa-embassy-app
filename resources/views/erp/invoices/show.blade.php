@extends('layouts.erp-app')

@section('title', $invoice->invoice_number)
@section('page-title', 'Invoice')

@section('content')
@php
    $inp  = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl  = 'mb-1 block text-xs font-semibold text-slate-600';
    $btn  = 'inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition';

    $events = [
        'invoice_created'   => ['Created',        'bi-plus-circle',   'bg-brand-600'],
        'invoice_updated'   => ['Edited',         'bi-pencil',        'bg-amber-500'],
        'invoice_paid'      => ['Marked as paid', 'bi-check-circle',  'bg-emerald-600'],
        'invoice_cancelled' => ['Cancelled',      'bi-x-circle',      'bg-rose-600'],
        'invoice_deleted'   => ['Deleted',        'bi-trash',         'bg-rose-700'],
    ];
    $adjLabel = fn ($type, $value) => $type === 'percent' ? ' (' . rtrim(rtrim((string) $value, '0'), '.') . '%)' : '';
    $canDelete = auth()->user()->can('delete', $invoice);
@endphp

<div x-data="{ paying: {{ $errors->has('payment_method') || $errors->has('paid_at') || $errors->has('payment_reference') ? 'true' : 'false' }} }">

<x-ui.page-header :title="$invoice->invoice_number" :subtitle="'Bill to ' . $invoice->billToLabel()" icon="bi-receipt">
    <x-slot:actions>
        <div class="flex flex-wrap items-center justify-end gap-2">
            @if($invoice->isEditable())
                <a href="{{ route('erp.invoices.edit', $invoice) }}" class="{{ $btn }} border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100"><i class="bi bi-pencil"></i> Edit</a>
            @endif
            <a href="{{ route('erp.invoices.preview-pdf', $invoice) }}" target="_blank" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-printer"></i> Print</a>
            <a href="{{ route('erp.invoices.download-pdf', $invoice) }}" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-download"></i> PDF</a>
            @if($canPay && $invoice->canBePaid())
                <button type="button" x-on:click="paying = true" class="{{ $btn }} bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-sm hover:shadow-md"><i class="bi bi-cash-coin"></i> Mark as paid</button>
            @endif
        </div>
    </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        @foreach($errors->all() as $message)<div><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@endforeach
    </div>
@endif

@if($invoice->isLocked())
    <div class="mb-4 flex items-center gap-2 rounded-xl border px-4 py-3 text-sm {{ $invoice->status === 'paid' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' }}">
        <i class="bi bi-lock-fill"></i>
        @if($invoice->status === 'paid')
            Paid on <strong>{{ $invoice->paid_at?->format('d M Y') }}</strong> via <strong>{{ $invoice->paymentMethodLabel() }}</strong>. This invoice is locked.
        @else
            Cancelled {{ $invoice->cancelled_at?->format('d M Y') }}. This invoice is locked.
        @endif
    </div>
@endif

<div class="grid gap-5 lg:grid-cols-3">
    {{-- Invoice document --}}
    <div class="lg:col-span-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 bg-gradient-to-br from-[#1a1f2e] via-slate-800 to-brand-900 px-6 py-5 text-white sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-[0.65rem] font-semibold uppercase tracking-widest text-white/60">Invoice</div>
                    <div class="mt-1 font-mono text-lg font-bold">{{ $invoice->invoice_number }}</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">@include('erp.invoices._status', ['invoice' => $invoice])</div>
                </div>
                <div class="sm:text-right">
                    <div class="text-[0.65rem] font-semibold uppercase tracking-widest text-white/60">Total</div>
                    <div class="mt-1 text-3xl font-bold">{{ $invoice->money($invoice->total_amount) }}</div>
                    <div class="text-xs text-white/60">{{ $invoice->currency }}</div>
                </div>
            </div>

            <div class="grid gap-5 border-b border-slate-100 px-6 py-5 sm:grid-cols-2">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Bill to</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $invoice->billToLabel() }}</div>
                    @if($invoice->bill_to_phone)<div class="text-sm text-slate-600"><i class="bi bi-telephone text-slate-400"></i> {{ $invoice->bill_to_phone }}</div>@endif
                    @if($invoice->bill_to_address)<div class="text-sm text-slate-600">{{ $invoice->bill_to_address }}</div>@endif
                    @if($invoice->agent)<div class="mt-1 text-xs text-slate-400">Agent: {{ $invoice->agent->name }}</div>@endif
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Invoice date</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $invoice->invoice_date->format('d M Y') }}</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Passenger</th>
                            <th class="px-4 py-3 text-right">Processing Fee</th>
                            <th class="px-4 py-3 text-right">MOFA Fee</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Paid</th>
                            <th class="px-4 py-3 text-right">Due</th>
                            <th class="px-4 py-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoice->items as $i => $item)
                            <tr>
                                <td class="px-4 py-3 text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    @if($item->hrProfile)
                                        <a href="{{ route('hr.show', $item->hrProfile) }}" class="font-medium text-brand-700 hover:underline">
                                            {{ $item->hrProfile->full_name_en }}
                                        </a>
                                        @if($item->hrProfile->passport?->passport_number)
                                            <div class="font-mono text-xs text-slate-500">{{ $item->hrProfile->passport->passport_number }}</div>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ $invoice->money($item->processing_fee) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ $invoice->money($item->mofa_fee) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-slate-900">{{ $invoice->money($item->total_amount) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ $invoice->money($item->paid_amount ?? 0) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right {{ (float)$item->due_amount > 0 ? 'font-semibold text-amber-700' : 'text-slate-500' }}">{{ $invoice->money($item->due_amount) }}</td>
                                <td class="px-4 py-3 text-slate-500 text-xs">{{ $item->remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-5 border-t border-slate-100 px-6 py-5 sm:flex-row sm:justify-between">
                <div class="max-w-sm text-sm text-slate-600">
                    @if($invoice->notes)
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Notes</div>
                        <p class="mt-1 whitespace-pre-line">{{ $invoice->notes }}</p>
                    @endif
                </div>
                <dl class="w-full space-y-1.5 text-sm sm:w-72">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-semibold">{{ $invoice->money($invoice->subtotal) }}</dd></div>
                    @if($invoice->tax_type !== 'none')
                        <div class="flex justify-between"><dt class="text-slate-500">Tax{{ $adjLabel($invoice->tax_type, $invoice->tax_value) }}</dt><dd class="font-semibold">+ {{ $invoice->money($invoice->tax_amount) }}</dd></div>
                    @endif
                    @if($invoice->discount_type !== 'none')
                        <div class="flex justify-between"><dt class="text-slate-500">Discount{{ $adjLabel($invoice->discount_type, $invoice->discount_value) }}</dt><dd class="font-semibold text-emerald-700">− {{ $invoice->money($invoice->discount_amount) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt class="font-bold text-slate-900">Total</dt><dd class="font-bold text-slate-900">{{ $invoice->money($invoice->total_amount) }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    {{-- Side panel --}}
    <aside class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-wallet2 text-brand-600"></i> Payment</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd>@include('erp.invoices._status', ['invoice' => $invoice])</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Method</dt><dd class="font-medium">{{ $invoice->paymentMethodLabel() ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Paid on</dt><dd class="font-medium">{{ $invoice->paid_at?->format('d M Y') ?? '—' }}</dd></div>
                @if($invoice->payment_reference)<div class="flex justify-between gap-3"><dt class="text-slate-500">Reference</dt><dd class="truncate font-medium">{{ $invoice->payment_reference }}</dd></div>@endif
                @if($invoice->paidBy)<div class="flex justify-between"><dt class="text-slate-500">Received by</dt><dd class="font-medium">{{ $invoice->paidBy->name }}</dd></div>@endif
            </dl>
            @if($canPay && $invoice->canBePaid())
                <button type="button" x-on:click="paying = true" class="mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"><i class="bi bi-cash-coin"></i> Mark as paid</button>
            @elseif($invoice->isEditable() && ! $canPay)
                <p class="mt-3 text-xs text-slate-400">Only an admin (or staff with the Receive Payment permission) can mark invoices as paid.</p>
            @endif

            @php $canCancel = $isAdmin && $invoice->isEditable(); @endphp
            @if($canCancel || $canDelete)
                <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
                    @if($canCancel)
                        <form method="POST" action="{{ route('erp.invoices.cancel', $invoice) }}" class="flex-1" onsubmit="return confirm('Cancel {{ $invoice->invoice_number }}? It will be locked.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"><i class="bi bi-x-circle"></i> Cancel invoice</button>
                        </form>
                    @endif
                    @if($canDelete)
                        <button type="button"
                                data-action="{{ route('erp.invoices.destroy', $invoice) }}"
                                data-number="{{ $invoice->invoice_number }}"
                                data-kind="{{ $invoice->status === 'draft' ? 'draft' : ($invoice->status === 'pending' ? 'pending' : 'locked') }}"
                                x-on:click="$dispatch('invoice-delete', { action: $el.dataset.action, number: $el.dataset.number, kind: $el.dataset.kind })"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete invoice</button>
                    @endif
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-clock-history text-brand-600"></i> Timeline</h3>
            @if($timeline->isEmpty())
                <p class="text-sm text-slate-400">Created {{ $invoice->created_at->format('d M Y, g:i A') }}{{ $invoice->createdBy ? ' by ' . $invoice->createdBy->name : '' }}.</p>
            @else
                <ol class="relative space-y-4 border-l border-slate-200 pl-5">
                    @foreach($timeline as $log)
                        @php [$label, $icon, $dot] = $events[$log->action] ?? [ucfirst(str_replace('_', ' ', $log->action)), 'bi-dot', 'bg-slate-400']; @endphp
                        <li class="relative">
                            <span class="absolute -left-[1.95rem] grid h-5 w-5 place-items-center rounded-full {{ $dot }} text-[0.6rem] text-white ring-4 ring-white"><i class="bi {{ $icon }}"></i></span>
                            <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
                            <div class="text-xs text-slate-500">{{ $log->created_at?->format('d M Y, g:i A') }} · {{ $log->user?->name ?? 'System' }}</div>
                            @if($log->action === 'invoice_paid' && ! empty($log->new_values['payment_method']))
                                <div class="mt-0.5 text-xs text-slate-500">{{ \App\Models\Invoice::PAYMENT_METHODS[$log->new_values['payment_method']] ?? $log->new_values['payment_method'] }} · {{ $invoice->money($log->new_values['total_amount'] ?? 0) }}</div>
                            @elseif($log->action === 'invoice_updated' && isset($log->old_values['total_amount'], $log->new_values['total_amount']) && $log->old_values['total_amount'] !== $log->new_values['total_amount'])
                                <div class="mt-0.5 text-xs text-slate-500">Total {{ $invoice->money($log->old_values['total_amount']) }} → {{ $invoice->money($log->new_values['total_amount']) }}</div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </aside>
</div>

{{-- Mark-as-paid modal --}}
@if($canPay && $invoice->canBePaid())
    <div x-show="paying" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-on:keydown.escape.window="paying = false">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="paying = false"></div>
        <form method="POST" action="{{ route('erp.invoices.mark-paid', $invoice) }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            @csrf @method('PATCH')
            <h3 class="flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-cash-coin text-emerald-600"></i> Mark as paid</h3>
            <p class="mt-1 text-sm text-slate-500">Records full payment of <strong class="text-slate-800">{{ $invoice->money($invoice->total_amount) }}</strong>. The invoice will be locked — no further edits.</p>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="{{ $lbl }}">Payment method <span class="text-rose-500">*</span></label>
                    <select name="payment_method" required class="{{ $inp }}">
                        @foreach($paymentMethods as $key => $label)<option value="{{ $key }}" @selected(old('payment_method') === $key)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Payment date <span class="text-rose-500">*</span></label>
                    <input type="date" name="paid_at" required max="{{ today()->format('Y-m-d') }}" value="{{ old('paid_at', today()->format('Y-m-d')) }}" class="{{ $inp }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Reference (cheque no., transaction ID…)</label>
                    <input type="text" name="payment_reference" maxlength="255" value="{{ old('payment_reference') }}" class="{{ $inp }}">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="paying = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"><i class="bi bi-lock"></i> Confirm &amp; lock</button>
            </div>
        </form>
    </div>
@endif

@if($canDelete)
    @include('erp.invoices._delete_modal')
@endif

</div>
@endsection
