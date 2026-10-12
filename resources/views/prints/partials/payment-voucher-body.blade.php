{{--
    One payment voucher (header → signatures). Shared by prints/payment-voucher (single page) and
    prints/payment-voucher-batch (two per A4 sheet). Needs $voucher, $agency, $amountInWords;
    optional $compact (smaller barcode) and $logoSrc.
--}}
@php
    $n2  = fn ($v) => number_format((float) ($v ?? 0), 2);
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $hasTax      = (float) $voucher->tax_amount > 0;
    $hasDiscount = (float) $voucher->discount_amount > 0;

    // Status box date: when it reached its current state.
    $statusDate = match ($voucher->status) {
        'paid'      => $voucher->payment_date ?? $voucher->paid_at,
        'approved'  => $voucher->approved_at,
        'cancelled' => $voucher->cancelled_at,
        default     => $voucher->created_at,
    };

    // Agency logo (a batch passes it in once); barcode per voucher.
    $compact = $compact ?? false;
    $logoSrc = $logoSrc ?? null;
    if ($logoSrc === null && $agency->print_logo && $agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo)) {
        $ext = strtolower(pathinfo($agency->logo, PATHINFO_EXTENSION)) ?: 'png';
        $logoSrc = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,'
            . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($agency->logo));
    }
    $barcodeSrc = app(\App\Services\BarcodeService::class)->make($voucher->voucher_number);
@endphp

{{-- Header --}}
<table class="head">
    <tr>
        <td style="width:15%;">
            @if($logoSrc)<img src="{{ $logoSrc }}" style="max-width:20mm; max-height:14mm;">@endif
        </td>
        <td style="width:57%;">
            <div class="a-name">{{ $agency->name }}</div>
            <div class="a-rl">Recruiting Licence No. : {{ $agency->rl_number ?: '—' }}</div>
            @if($agency->address)<div class="a-addr">{{ $agency->address }}</div>@endif
        </td>
        <td style="width:28%; text-align:right;">
            @if($barcodeSrc)
                <img src="{{ $barcodeSrc }}" style="width:40mm; height:11mm;">
                <div class="bc-num">{{ $voucher->voucher_number }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="title"><tr><td><span class="t-main">PAYMENT VOUCHER</span></td></tr></table>

{{-- Shared heading/body rows keep all three cards equal in height, including wrapped content. --}}
<table class="meta" style="margin-bottom:10pt;">
    <tr>
        <td class="box-h" style="width:32%;">Paid To / Recipient</td>
        <td class="gap" rowspan="2"></td>
        <td class="box-h" style="width:32%;">Voucher Details</td>
        <td class="gap" rowspan="2"></td>
        <td class="box-h" style="width:32%;">Status</td>
    </tr>
    <tr>
        <td class="box-body">
            <div class="p-name">{{ $voucher->payee_name }}</div>
            <div class="p-line">{{ $voucher->payeeTypeLabel() }}</div>
            @if($voucher->payee_phone)<div class="p-line">Phone: {{ $voucher->payee_phone }}</div>@endif
            @if($voucher->payee_account)<div class="p-line">Account: {{ $voucher->payee_account }}</div>@endif
            @if($voucher->payee_address)<div class="p-line">{{ $voucher->payee_address }}</div>@endif
        </td>
        <td class="box-body">
            <table class="fields">
                <tr><td class="lbl">Voucher No</td><td class="val">{{ $voucher->voucher_number }}</td></tr>
                <tr><td class="lbl">Voucher Date</td><td class="val">{{ $voucher->voucher_date->format('d-M-Y') }}</td></tr>
                <tr><td class="lbl">Payment Method</td><td class="val">{{ $voucher->paymentMethodLabel() }}</td></tr>
                @if($voucher->cheque_number)<tr><td class="lbl">Cheque No</td><td class="val">{{ $voucher->cheque_number }}</td></tr>@endif
                @if($voucher->bank_name)<tr><td class="lbl">Bank</td><td class="val">{{ $voucher->bank_name }}</td></tr>@endif
                @if($voucher->reference_number)<tr><td class="lbl">Reference</td><td class="val">{{ $voucher->reference_number }}</td></tr>@endif
            </table>
        </td>
        <td class="box-body">
            <table class="fields">
                <tr><td colspan="2" style="padding-bottom:8pt;"><span class="badge b-{{ $voucher->status }}">{{ strtoupper($voucher->statusLabel()) }}</span></td></tr>
                <tr><td class="lbl">Date</td><td class="val">{{ $statusDate?->format('d-M-Y') ?? '—' }}</td></tr>
                <tr><td class="lbl">Approved By</td><td class="val">{{ $voucher->approvedBy->name ?? '—' }}</td></tr>
                @if($voucher->status === 'paid')<tr><td class="lbl">Paid By</td><td class="val">{{ $voucher->paidBy->name ?? '—' }}</td></tr>@endif
            </table>
        </td>
    </tr>
</table>

{{-- Expense classification and optional historical purpose. --}}
<table class="purpose">
    <tr><td class="ph">Expense Head / Reason of Costing</td><td class="head-value">{{ $voucher->expenseHeadLabel() }}</td></tr>
</table>
<table class="remarks">
    @if($voucher->description && $voucher->description !== $voucher->expenseHeadLabel())
        <tr><td class="ph">Remarks</td><td>{!! nl2br(e($voucher->description)) !!}</td></tr>
    @endif
    @if($voucher->notes)<tr><td class="ph">Remarks</td><td>{!! nl2br(e($voucher->notes)) !!}</td></tr>@endif
</table>

{{-- Totals (right) + PAID/CANCELLED stamp (left) --}}
<table style="margin-top:10pt;">
    <tr>
        <td style="width:55%; vertical-align:middle; padding-right:8pt;">
            @if($voucher->status === 'paid')
                <table style="width:auto;"><tr><td class="stamp stamp-paid">PAID</td></tr></table>
                <div style="margin-top:3pt; color:#047857; font-size:7.5pt;">{{ $voucher->payment_date?->format('d-M-Y') }} · {{ $voucher->paymentMethodLabel() }}@if($voucher->reference_number) · Ref: {{ $voucher->reference_number }}@endif</div>
            @elseif($voucher->status === 'cancelled')
                <table style="width:auto;"><tr><td class="stamp stamp-cancelled">CANCELLED</td></tr></table>
            @endif
        </td>
        <td style="width:45%; vertical-align:top;">
            <table class="totals">
                @if($hasTax || $hasDiscount)<tr><td class="tk">Subtotal</td><td class="tv">BDT {{ $n2($voucher->subtotal) }}</td></tr>@endif
                @if($hasTax)
                    <tr><td class="tk">Tax{{ $voucher->tax_type === 'percent' ? ' (' . $pct($voucher->tax_value) . '%)' : ' (fixed)' }}</td><td class="tv">+ BDT {{ $n2($voucher->tax_amount) }}</td></tr>
                @endif
                @if($hasDiscount)
                    <tr><td class="tk">Discount{{ $voucher->discount_type === 'percent' ? ' (' . $pct($voucher->discount_value) . '%)' : ' (fixed)' }}</td><td class="tv">− BDT {{ $n2($voucher->discount_amount) }}</td></tr>
                @endif
                <tr><td colspan="2" class="amount-label">Amount</td></tr>
                <tr class="grand"><td colspan="2" style="white-space:nowrap;">BDT {{ $n2($voucher->total_amount) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<table class="words" style="margin-top:8pt;">
    <tr><td><span class="w-label">In Words:</span> &nbsp;<span class="w-value">{{ $amountInWords }}</span></td></tr>
</table>

{{-- Signatures --}}
<table class="sign">
    <tr>
        <td class="line" style="width:30%; text-align:center;">Recipient</td>
        <td style="width:5%;"></td>
        <td class="line" style="width:30%; text-align:center;">Accountant</td>
        <td style="width:5%;"></td>
        <td class="line" style="width:30%; text-align:center;">Proprietor</td>
    </tr>
    <tr>
        <td class="signature-name">{{ $voucher->payee_name }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
