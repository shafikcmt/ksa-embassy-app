{{-- Payment voucher status pill. Expects $voucher. --}}
@php
    $chip = [
        'draft'     => 'bg-slate-100 text-slate-700 ring-slate-200',
        'approved'  => 'bg-sky-50 text-sky-700 ring-sky-200',
        'paid'      => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-200',
    ];
    $icon = ['draft' => 'bi-pencil-square', 'approved' => 'bi-patch-check', 'paid' => 'bi-check-circle-fill', 'cancelled' => 'bi-x-circle'];
@endphp
<span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $chip[$voucher->status] ?? $chip['draft'] }}">
    <i class="bi {{ $icon[$voucher->status] ?? 'bi-circle' }}"></i> {{ $voucher->statusLabel() }}
</span>
