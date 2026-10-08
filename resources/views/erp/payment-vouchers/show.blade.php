@extends('layouts.erp-app')

@section('title', $voucher->voucher_number)
@section('page-title', 'Payment Voucher')

@section('content')
@php
    $inp = 'w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $lbl = 'mb-1 block text-xs font-semibold text-slate-600';
    $btn = 'inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition';

    $events = [
        'voucher_created'   => ['Created (draft)', 'bi-plus-circle',  'bg-brand-600'],
        'voucher_updated'   => ['Edited',          'bi-pencil',       'bg-amber-500'],
        'voucher_approved'  => ['Approved',        'bi-patch-check',  'bg-sky-600'],
        'voucher_paid'      => ['Paid out',        'bi-check-circle', 'bg-emerald-600'],
        'voucher_cancelled' => ['Cancelled',       'bi-x-circle',     'bg-rose-600'],
        'voucher_deleted'   => ['Deleted',         'bi-trash',        'bg-rose-700'],
        'voucher_restored'  => ['Restored',        'bi-arrow-counterclockwise', 'bg-slate-500'],
        'voucher_expense_created' => ['Booked in Expenses', 'bi-journal-check', 'bg-emerald-500'],
    ];
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $payErrors = $errors->hasAny(['payment_method', 'payment_date', 'cheque_number', 'bank_name', 'reference_number']);

    // Workflow stepper: draft → approved → paid
    $flow = ['draft' => 0, 'approved' => 1, 'paid' => 2];
    $at = $flow[$voucher->status] ?? -1;
@endphp

<div x-data="{ paying: {{ $payErrors ? 'true' : 'false' }}, method: @js(old('payment_method', $voucher->payment_method)) }">

<x-ui.page-header :title="$voucher->voucher_number" :subtitle="'Pay to ' . $voucher->payee_name" icon="bi-wallet2">
    <x-slot:actions>
        <div class="flex flex-wrap items-center justify-end gap-2">
            @can('update', $voucher)
                <a href="{{ route('erp.payment-vouchers.edit', $voucher) }}" class="{{ $btn }} border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100"><i class="bi bi-pencil"></i> Edit</a>
            @endcan
            <a href="{{ route('erp.payment-vouchers.preview-pdf', $voucher) }}" target="_blank" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-printer"></i> Print</a>
            <a href="{{ route('erp.payment-vouchers.download-pdf', $voucher) }}" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"><i class="bi bi-download"></i> PDF</a>
            @can('approve', $voucher)
                <form method="POST" action="{{ route('erp.payment-vouchers.approve', $voucher) }}" onsubmit="return confirm('Approve {{ $voucher->voucher_number }}? It can no longer be edited.')">
                    @csrf @method('PATCH')
                    <button type="submit" class="{{ $btn }} bg-sky-600 text-white shadow-sm hover:bg-sky-700"><i class="bi bi-patch-check"></i> Approve</button>
                </form>
            @endcan
            @can('pay', $voucher)
                <button type="button" x-on:click="paying = true" class="{{ $btn }} bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-sm hover:shadow-md"><i class="bi bi-cash-coin"></i> Mark as paid</button>
            @endcan
        </div>
    </x-slot:actions>
</x-ui.page-header>

@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        @foreach($errors->all() as $message)<div><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>@endforeach
    </div>
@endif

{{-- Workflow --}}
@if($voucher->status === 'cancelled')
    <div class="mb-5 flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><i class="bi bi-x-circle-fill"></i> Cancelled {{ $voucher->cancelled_at?->format('d M Y, g:i A') }} — this voucher is locked and will not be paid.</div>
@else
    <ol class="mb-5 grid grid-cols-3 gap-2">
        @foreach([['Draft', 'Prepared by ' . ($voucher->createdBy->name ?? '—')], ['Approved', $voucher->approvedBy ? 'by ' . $voucher->approvedBy->name : 'Awaiting admin'], ['Paid', $voucher->paidBy ? 'by ' . $voucher->paidBy->name : 'Not yet paid']] as $i => [$label, $sub])
            <li class="flex items-center gap-2 rounded-xl border px-3 py-2.5 {{ $i < $at ? 'border-emerald-200 bg-white' : ($i === $at ? 'border-brand-300 bg-brand-50' : 'border-slate-200 bg-white') }}">
                <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-xs font-bold {{ $i <= $at ? ($i === 2 || $i < $at ? 'bg-emerald-500 text-white' : 'bg-brand-600 text-white') : 'bg-slate-100 text-slate-400' }}">
                    <i class="bi {{ $i < $at || ($i === 2 && $at === 2) ? 'bi-check-lg' : 'bi-' . ($i + 1) . '-circle' }}"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-800">{{ $label }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $sub }}</span>
                </span>
            </li>
        @endforeach
    </ol>
@endif

@if($voucher->status === 'paid' && $voucher->expense)
    <div class="mb-5 flex flex-wrap items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <i class="bi bi-journal-check text-lg text-emerald-600"></i>
        <div class="min-w-0 flex-1">
            <div class="font-semibold">Booked in Expenses automatically</div>
            <div class="text-emerald-800">{{ $voucher->money($voucher->expense->amount) }} on {{ $voucher->expense->expense_date->format('d M Y') }} · counted in Reports and Profit / Loss.</div>
        </div>
        <a href="{{ route('erp.expenses') }}" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">View expenses <i class="bi bi-arrow-right"></i></a>
    </div>
@endif

<div class="grid gap-5 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 bg-gradient-to-br from-[#1a1f2e] via-slate-800 to-brand-900 px-6 py-5 text-white sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-[0.65rem] font-semibold uppercase tracking-widest text-white/60">Payment voucher</div>
                    <div class="mt-1 font-mono text-lg font-bold">{{ $voucher->voucher_number }}</div>
                    <div class="mt-2">@include('erp.payment-vouchers._status', ['voucher' => $voucher])</div>
                </div>
                <div class="sm:text-right">
                    <div class="text-[0.65rem] font-semibold uppercase tracking-widest text-white/60">Total to pay</div>
                    <div class="mt-1 text-3xl font-bold">{{ $voucher->money($voucher->total_amount) }}</div>
                </div>
            </div>

            <div class="grid gap-5 border-b border-slate-100 px-6 py-5 sm:grid-cols-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Paid To / Recipient</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $voucher->payee_name }}</div>
                    @if($voucher->payee_phone)<div class="text-sm text-slate-600"><i class="bi bi-telephone text-slate-400"></i> {{ $voucher->payee_phone }}</div>@endif
                    @if($voucher->payee_account)<div class="text-sm text-slate-600"><i class="bi bi-bank text-slate-400"></i> {{ $voucher->payee_account }}</div>@endif
                    @if($voucher->payee_address)<div class="text-sm text-slate-600">{{ $voucher->payee_address }}</div>@endif
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Voucher date</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $voucher->voucher_date->format('d M Y') }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Payment method</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $voucher->paymentMethodLabel() }}</div>
                    @if($voucher->cheque_number)<div class="text-sm text-slate-600">Cheque: {{ $voucher->cheque_number }}</div>@endif
                    @if($voucher->bank_name)<div class="text-sm text-slate-600">{{ $voucher->bank_name }}</div>@endif
                    @if($voucher->reference_number)<div class="text-sm text-slate-600">Ref: {{ $voucher->reference_number }}</div>@endif
                </div>
            </div>

            <div class="border-b border-slate-100 px-6 py-4 text-sm text-slate-700">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Expense Head / Reason of Costing</div>
                <p class="mt-1 whitespace-pre-line">{{ $voucher->expenseHeadLabel() }}</p>
                @if($voucher->description !== $voucher->expenseHeadLabel())<p class="mt-2 text-slate-500">{{ $voucher->description }}</p>@endif
            </div>

            @if(! $voucher->expense_head_id || $voucher->expenseHead?->is_system || $voucher->items->count() > 1 || (float) $voucher->tax_amount || (float) $voucher->discount_amount)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">#</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Unit price</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($voucher->items as $i => $item)
                            <tr>
                                <td class="px-6 py-3 text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $item->description }}</div>
                                    @if($item->remarks)<div class="text-xs text-slate-500">{{ $item->remarks }}</div>@endif
                                </td>
                                <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="whitespace-nowrap px-6 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="flex flex-col gap-5 border-t border-slate-100 px-6 py-5 sm:flex-row sm:justify-between">
                <div class="max-w-sm text-sm text-slate-600">
                    @if($voucher->notes)
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Internal notes</div>
                        <p class="mt-1 whitespace-pre-line">{{ $voucher->notes }}</p>
                    @endif
                </div>
                <dl class="w-full space-y-1.5 text-sm sm:w-72">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-semibold">{{ $voucher->money($voucher->subtotal) }}</dd></div>
                    @if((float) $voucher->tax_amount > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Tax{{ $voucher->tax_type === 'percent' ? ' (' . $pct($voucher->tax_value) . '%)' : '' }}</dt><dd class="font-semibold">+ {{ $voucher->money($voucher->tax_amount) }}</dd></div>
                    @endif
                    @if((float) $voucher->discount_amount > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Discount{{ $voucher->discount_type === 'percent' ? ' (' . $pct($voucher->discount_value) . '%)' : '' }}</dt><dd class="font-semibold text-emerald-700">− {{ $voucher->money($voucher->discount_amount) }}</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt class="font-bold text-slate-900">Total</dt><dd class="font-bold text-slate-900">{{ $voucher->money($voucher->total_amount) }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <aside class="space-y-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-diagram-3 text-brand-600"></i> Workflow</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Prepared by</dt><dd class="text-right font-medium">{{ $voucher->createdBy->name ?? '—' }}<div class="text-xs text-slate-400">{{ $voucher->created_at?->format('d M Y') }}</div></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Approved by</dt><dd class="text-right font-medium">{{ $voucher->approvedBy->name ?? '—' }}@if($voucher->approved_at)<div class="text-xs text-slate-400">{{ $voucher->approved_at->format('d M Y') }}</div>@endif</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Paid by</dt><dd class="text-right font-medium">{{ $voucher->paidBy->name ?? '—' }}@if($voucher->payment_date)<div class="text-xs text-slate-400">{{ $voucher->payment_date->format('d M Y') }}</div>@endif</dd></div>
            </dl>

            @if(! auth()->user()->isAgencyAdmin() && in_array($voucher->status, ['draft', 'approved'], true))
                <p class="mt-3 text-xs text-slate-400">An agency admin approves and pays vouchers.</p>
            @endif

            @if(auth()->user()->can('cancel', $voucher) || auth()->user()->can('delete', $voucher))
                <div class="mt-4 flex gap-2 border-t border-slate-100 pt-4">
                    @can('cancel', $voucher)
                        <form method="POST" action="{{ route('erp.payment-vouchers.cancel', $voucher) }}" class="flex-1" onsubmit="return confirm('Cancel {{ $voucher->voucher_number }}? It will be locked.')">
                            @csrf @method('PATCH')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"><i class="bi bi-x-circle"></i> Cancel voucher</button>
                        </form>
                    @endcan
                    @can('delete', $voucher)
                        <form method="POST" action="{{ route('erp.payment-vouchers.destroy', $voucher) }}" class="flex-1" onsubmit="return confirm('Delete draft {{ $voucher->voucher_number }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 hover:bg-rose-100"><i class="bi bi-trash"></i> Delete draft</button>
                        </form>
                    @endcan
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-bold text-slate-900"><i class="bi bi-clock-history text-brand-600"></i> Timeline</h3>
            @if($timeline->isEmpty())
                <p class="text-sm text-slate-400">Created {{ $voucher->created_at->format('d M Y, g:i A') }}.</p>
            @else
                <ol class="relative space-y-4 border-l border-slate-200 pl-5">
                    @foreach($timeline as $log)
                        @php [$label, $icon, $dot] = $events[$log->action] ?? [ucfirst(str_replace('_', ' ', $log->action)), 'bi-dot', 'bg-slate-400']; @endphp
                        <li class="relative">
                            <span class="absolute -left-[1.95rem] grid h-5 w-5 place-items-center rounded-full {{ $dot }} text-[0.6rem] text-white ring-4 ring-white"><i class="bi {{ $icon }}"></i></span>
                            <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
                            <div class="text-xs text-slate-500">{{ $log->created_at?->format('d M Y, g:i A') }} · {{ $log->user?->name ?? 'System' }}</div>
                            @if($log->action === 'voucher_paid' && ! empty($log->new_values['payment_method']))
                                <div class="mt-0.5 text-xs text-slate-500">{{ \App\Models\PaymentVoucher::PAYMENT_METHODS[$log->new_values['payment_method']] ?? $log->new_values['payment_method'] }} · {{ $voucher->money($log->new_values['total_amount'] ?? 0) }}</div>
                            @elseif($log->action === 'voucher_updated' && isset($log->old_values['total_amount'], $log->new_values['total_amount']) && $log->old_values['total_amount'] !== $log->new_values['total_amount'])
                                <div class="mt-0.5 text-xs text-slate-500">Total {{ $voucher->money($log->old_values['total_amount']) }} → {{ $voucher->money($log->new_values['total_amount']) }}</div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </aside>
</div>

{{-- Mark-as-paid modal --}}
@can('pay', $voucher)
    <div x-show="paying" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-on:keydown.escape.window="paying = false">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="paying = false"></div>
        <form method="POST" action="{{ route('erp.payment-vouchers.mark-paid', $voucher) }}" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            @csrf @method('PATCH')
            <h3 class="flex items-center gap-2 text-base font-bold text-slate-900"><i class="bi bi-cash-coin text-emerald-600"></i> Mark as paid</h3>
            <p class="mt-1 text-sm text-slate-500">Records payment of <strong class="text-slate-800">{{ $voucher->money($voucher->total_amount) }}</strong> to <strong class="text-slate-800">{{ $voucher->payee_name }}</strong> and books it in Expenses. The voucher will be locked.</p>
            <div class="mt-4 space-y-3">
                <div>
                    <label class="{{ $lbl }}">Payment method <span class="text-rose-500">*</span></label>
                    <select name="payment_method" x-model="method" class="{{ $inp }}">
                        @foreach($paymentMethods as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Payment date <span class="text-rose-500">*</span></label>
                    <input type="date" name="payment_date" max="{{ today()->format('Y-m-d') }}" value="{{ old('payment_date', today()->format('Y-m-d')) }}" class="{{ $inp }}">
                </div>
                <div x-show="method === 'cheque'">
                    <label class="{{ $lbl }}">Cheque number <span class="text-rose-500">*</span></label>
                    <input type="text" name="cheque_number" maxlength="50" value="{{ old('cheque_number', $voucher->cheque_number) }}" x-bind:disabled="method !== 'cheque'" class="{{ $inp }}">
                </div>
                <div x-show="method === 'cheque' || method === 'bank_transfer'">
                    <label class="{{ $lbl }}">Bank name</label>
                    <input type="text" name="bank_name" maxlength="100" value="{{ old('bank_name', $voucher->bank_name) }}" x-bind:disabled="!(method === 'cheque' || method === 'bank_transfer')" class="{{ $inp }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Reference no. (TrxID, slip…)</label>
                    <input type="text" name="reference_number" maxlength="100" value="{{ old('reference_number', $voucher->reference_number) }}" class="{{ $inp }}">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" x-on:click="paying = false" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"><i class="bi bi-lock"></i> Confirm &amp; lock</button>
            </div>
        </form>
    </div>
@endcan

</div>
@endsection
