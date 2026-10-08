<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    /* Payment Voucher only: restrained blue/gray palette, shared metadata rows,
       single classification row and light amount summary. All layout uses
       mPDF-safe tables; A4 + 10mm margins come from PdfGeneratorService.
       Existing historical tax/discount amounts and status details stay visible. */
    body  { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 9pt; line-height: 1.4; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }

    .accent td { background-color: #2563eb; height: 3pt; line-height: 3pt; font-size: 1pt; }

    .head td { vertical-align: middle; border-bottom: 0.5pt solid #d5dee9; padding: 8pt 4pt; }
    .a-name { font-size: 12pt; font-weight: bold; color: #0f172a; }
    .a-rl   { font-size: 9pt; font-weight: bold; color: #334155; margin-top: 2pt; }
    .a-addr { font-size: 8pt; color: #64748b; margin-top: 2pt; }
    .bc-num { font-family: dejavusansmono, monospace; font-size: 7pt; color: #334155; letter-spacing: 0.6pt; margin-top: 1pt; }

    .title td { text-align: center; padding: 13pt 0 12pt; }
    .t-main { color: #1e40af; font-weight: bold; font-size: 13.5pt; letter-spacing: 0.8pt; }

    .meta .box-h { background-color: #f1f5f9; border: 0.5pt solid #d5dee9; color: #334155; font-size: 8.5pt; font-weight: bold; padding: 7pt 9pt; }
    .meta .box-body { border: 0.5pt solid #d5dee9; padding: 9pt; vertical-align: top; height: 78pt; }
    .meta .gap { width: 2%; }
    .fields td { padding: 0 0 5pt; vertical-align: top; }
    .lbl { color: #64748b; font-size: 7.5pt; padding-right: 5pt; white-space: nowrap; }
    .val { color: #0f172a; font-size: 8pt; font-weight: bold; text-align: right; }
    .p-name { color: #0f172a; font-size: 9pt; font-weight: bold; }
    .p-line { color: #475569; font-size: 8pt; margin-top: 4pt; }

    .badge { font-size: 7.5pt; font-weight: bold; letter-spacing: 0.4pt; padding: 1.5pt 6pt; }
    .b-draft     { background-color: #e5e7eb; color: #1e293b; }
    .b-approved  { background-color: #dbeafe; color: #1e40af; }
    .b-paid      { background-color: #dcfce7; color: #166534; }
    .b-cancelled { background-color: #ffe4e6; color: #9f1239; }

    .purpose td { border: 0.5pt solid #d5dee9; background-color: #f8fafc; padding: 9pt 11pt; font-size: 9pt; vertical-align: middle; }
    .purpose .ph { background-color: #f1f5f9; color: #475569; font-size: 8pt; font-weight: bold; width: 36%; }
    .purpose .head-value { color: #1e40af; font-size: 10pt; font-weight: bold; }
    .remarks td { padding: 7pt 10pt; border-bottom: 0.5pt solid #e2e8f0; font-size: 8pt; color: #475569; vertical-align: top; }
    .remarks .ph { width: 18%; color: #64748b; }

    .grid th { background-color: #e8e8e8; color: #1e293b; font-size: 8pt; font-weight: bold; text-align: center; white-space: nowrap; padding: 5pt 4pt; border: 0.5px solid #333333; vertical-align: middle; }
    .grid td { font-size: 8pt; padding: 4pt 4pt; border: 0.5px solid #333333; vertical-align: top; }
    .grid tr.alt td { background-color: #f9f9f9; }
    .grid tr.grand td { background-color: #e8e8e8; font-weight: bold; padding: 5pt 4pt; }
    .c { text-align: center; }
    .l { text-align: left; }
    .amt { font-family: dejavusanscondensed, sans-serif; font-weight: bold; text-align: right; white-space: nowrap; }
    .sub { color: #64748b; font-size: 7.5pt; margin-top: 1pt; }

    .totals { border: 0.5pt solid #cbd9ed; background-color: #eff6ff; }
    .totals td { font-size: 8pt; padding: 5pt 10pt; }
    .totals .tk { color: #475569; text-align: right; }
    .totals .tv { font-family: dejavusansmono, monospace; font-weight: bold; text-align: right; white-space: nowrap; }
    .totals tr.grand td { color: #1e40af; font-size: 15pt; font-weight: bold; padding: 4pt 12pt 12pt; text-align: right; }
    .totals .amount-label { color: #475569; font-size: 8pt; font-weight: bold; text-align: right; padding: 10pt 12pt 0; }

    .stamp { font-weight: bold; font-size: 12pt; letter-spacing: 3pt; padding: 4pt 8pt; border: 1.5px solid; text-align: center; }
    .stamp-paid      { color: #047857; border-color: #047857; }
    .stamp-cancelled { color: #be123c; border-color: #be123c; }

    .words td { background-color: #f1f5f9; border: 0.5pt solid #d5dee9; padding: 10pt 12pt; font-size: 9pt; }
    .w-label { color: #64748b; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
    .w-value { color: #0f172a; font-weight: bold; }

    .sign { margin-top: 34pt; }
    .sign .line { border-top: 0.5pt solid #94a3b8; padding-top: 6pt; font-size: 8pt; font-weight: bold; color: #475569; }
    .signature-name { padding-top: 3pt; text-align: center; font-size: 7.5pt; color: #64748b; }

    .foot td { border-top: 0.5px solid #e2e8f0; padding-top: 3pt; color: #999999; font-size: 7pt; }
</style>
</head>
<body>
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

    $logoSrc = null;
    if ($agency->print_logo && $agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo)) {
        $ext = strtolower(pathinfo($agency->logo, PATHINFO_EXTENSION)) ?: 'png';
        $logoSrc = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,'
            . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($agency->logo));
    }
    $barcodeSrc = app(\App\Services\BarcodeService::class)->make($voucher->voucher_number);
@endphp

<htmlpagefooter name="voucherFooter">
    <table class="foot"><tr>
        <td style="width:60%; text-align:left;">Generated {{ now()->format('d-M-Y h:i A') }} | {{ $voucher->voucher_number }}</td>
        <td style="width:40%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr></table>
</htmlpagefooter>
<sethtmlpagefooter name="voucherFooter" value="on" />

{{-- A modest upper offset balances a single voucher on A4 while leaving room for wrapped details. --}}
<div style="height:50mm;"></div>
<table class="accent"><tr><td>&nbsp;</td></tr></table>

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

</body>
</html>
