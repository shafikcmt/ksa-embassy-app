{{-- Invoice status pill. Expects $invoice. Overdue = pending past its due date. --}}
@php
    $chip = [
        'draft'     => 'bg-slate-100 text-slate-700 ring-slate-200',
        'pending'   => 'bg-amber-50 text-amber-700 ring-amber-200',
        'paid'      => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $icon = ['draft' => 'bi-pencil-square', 'pending' => 'bi-hourglass-split', 'paid' => 'bi-check-circle-fill', 'cancelled' => 'bi-x-circle'];
@endphp
<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $chip[$invoice->status] ?? $chip['draft'] }}">
    <i class="bi {{ $icon[$invoice->status] ?? 'bi-circle' }}"></i> {{ $invoice->statusLabel() }}
</span>
@if($invoice->isOverdue())
    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-rose-600 px-2.5 py-0.5 text-xs font-semibold text-white">
        <i class="bi bi-alarm"></i> Overdue
    </span>
@endif
