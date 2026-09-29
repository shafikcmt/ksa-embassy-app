<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    /* ERP Payment Voucher — money OUT (mPDF; preview = inline, download =
       attachment). Same visual system as prints/invoice.blade.php: accent bar →
       header (logo | agency | barcode) → underlined title → 3 meta boxes
       (Payee | Voucher | Status) → purpose → 4-column items grid + GRAND TOTAL
       → subtotal/tax/discount/TOTAL → in words → signatures → pinned footer.

       Fixed font scale: 8pt body, 7.5pt labels, 8pt items table (4 columns fit
       single-line headers at 8pt with room to spare; autosize="1" keeps mPDF
       from shrinking it), 9pt box headers. mPDF-safe CSS only (tables for all
       layout). A4 + 10mm margins from PdfGeneratorService::makeMpdf(); no @page. */
    body  { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 8pt; line-height: 1.3; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }

    .accent td { background-color: #2563eb; height: 3pt; line-height: 3pt; font-size: 1pt; }

    .head td { vertical-align: middle; border-bottom: 0.8px solid #cbd5e1; padding: 6pt 4pt; }
    .a-name { font-size: 11pt; font-weight: bold; color: #0f172a; }
    .a-rl   { font-size: 9pt; font-weight: bold; color: #334155; margin-top: 2pt; }
    .a-addr { font-size: 8pt; font-style: italic; color: #475569; margin-top: 2pt; }
    .bc-num { font-family: dejavusansmono, monospace; font-size: 7pt; color: #334155; letter-spacing: 0.6pt; margin-top: 1pt; }

    .title td { text-align: center; padding: 9pt 0 7pt; }
    .t-main { color: #0f172a; font-weight: bold; font-size: 14pt; letter-spacing: 1pt; text-decoration: underline; }

    .box { border: 0.5px solid #333333; }
    .box-h td { background-color: #e8e8e8; border-bottom: 0.5px solid #333333; font-size: 9pt; font-weight: bold; padding: 4pt 6pt; }
    .box td { padding: 2pt 6pt; vertical-align: top; }
    .lbl { color: #475569; font-size: 7.5pt; white-space: nowrap; }
    .val { color: #0f172a; font-size: 8pt; font-weight: bold; text-align: right; }
    .p-name { color: #0f172a; font-size: 9pt; font-weight: bold; }
    .p-line { color: #475569; font-size: 7.5pt; margin-top: 1.5pt; }

    .badge { font-size: 7.5pt; font-weight: bold; letter-spacing: 0.4pt; padding: 1.5pt 6pt; }
    .b-draft     { background-color: #e5e7eb; color: #1e293b; }
    .b-approved  { background-color: #bae6fd; color: #0c4a6e; }
    .b-paid      { background-color: #86efac; color: #14532d; }
    .b-cancelled { background-color: #fca5a5; color: #7f1d1d; }

    .purpose td { border: 0.5px solid #333333; padding: 5pt 6pt; font-size: 8pt; }
    .purpose .ph { background-color: #e8e8e8; font-weight: bold; width: 16%; white-space: nowrap; }

    .grid th { background-color: #e8e8e8; color: #1e293b; font-size: 8pt; font-weight: bold; text-align: center; white-space: nowrap; padding: 5pt 4pt; border: 0.5px solid #333333; vertical-align: middle; }
    .grid td { font-size: 8pt; padding: 4pt 4pt; border: 0.5px solid #333333; vertical-align: top; }
    .grid tr.alt td { background-color: #f9f9f9; }
    .grid tr.grand td { background-color: #e8e8e8; font-weight: bold; padding: 5pt 4pt; }
    .c { text-align: center; }
    .l { text-align: left; }
    .amt { font-family: dejavusanscondensed, sans-serif; font-weight: bold; text-align: right; white-space: nowrap; }
    .sub { color: #64748b; font-size: 7.5pt; margin-top: 1pt; }

    .totals td { font-size: 8pt; padding: 3pt 8pt; }
    .totals .tk { color: #475569; text-align: right; }
    .totals .tv { font-family: dejavusansmono, monospace; font-weight: bold; text-align: right; white-space: nowrap; }
    .totals tr.grand td { background-color: #1e293b; color: #ffffff; font-size: 9pt; font-weight: bold; padding: 5pt 8pt; border: 0.5px solid #1e293b; }

    .stamp { font-weight: bold; font-size: 12pt; letter-spacing: 3pt; padding: 4pt 8pt; border: 1.5px solid; text-align: center; }
    .stamp-paid      { color: #047857; border-color: #047857; }
    .stamp-cancelled { color: #be123c; border-color: #be123c; }

    .words td { background-color: #eff6ff; border: 0.5px solid #dbeafe; padding: 6pt 8pt; font-size: 8pt; }
    .w-label { color: #334155; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; }
    .w-value { color: #0f172a; font-weight: bold; font-style: italic; }

    .sign { margin-top: 48pt; }
    .sign .line { border-top: 0.5px solid #64748b; padding-top: 4pt; font-size: 9pt; font-weight: bold; color: #334155; }

    .foot td { border-top: 0.5px solid #e2e8f0; padding-top: 3pt; color: #999999; font-size: 7pt; }
</style>
</head>
<body>
@php
    $n2  = fn ($v) => number_format((float) ($v ?? 0), 2);
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $hasTax      = (float) $voucher->tax_amount > 0;
    $hasDiscount = (float) $voucher->discount_amount > 0;
    $sumQty      = $voucher->items->sum('quantity');

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

{{-- Meta boxes: Payee | Voucher | Status --}}
<table style="margin-bottom:8pt;">
    <tr>
        <td style="width:36%; padding-right:6pt; vertical-align:top;">
            <table class="box">
                <tr class="box-h"><td>Payee Details:</td></tr>
                <tr><td style="padding-top:5pt; padding-bottom:6pt;">
                    <div class="p-name">{{ $voucher->payee_name }}</div>
                    <div class="p-line">{{ $voucher->payeeTypeLabel() }}</div>
                    @if($voucher->payee_phone)<div class="p-line">Phone: {{ $voucher->payee_phone }}</div>@endif
                    @if($voucher->payee_account)<div class="p-line">Account: {{ $voucher->payee_account }}</div>@endif
                    @if($voucher->payee_address)<div class="p-line">{{ $voucher->payee_address }}</div>@endif
                </td></tr>
            </table>
        </td>
        <td style="width:34%; padding-right:6pt; vertical-align:top;">
            <table class="box">
                <tr class="box-h"><td colspan="2">Voucher Details:</td></tr>
                <tr><td class="lbl" style="padding-top:5pt;">Voucher No</td><td class="val" style="padding-top:5pt; white-space:nowrap;">{{ $voucher->voucher_number }}</td></tr>
                <tr><td class="lbl">Voucher Date</td><td class="val">{{ $voucher->voucher_date->format('d-M-Y') }}</td></tr>
                <tr><td class="lbl">Method</td><td class="val">{{ $voucher->paymentMethodLabel() }}</td></tr>
                @if($voucher->cheque_number)<tr><td class="lbl">Cheque No</td><td class="val">{{ $voucher->cheque_number }}</td></tr>@endif
                @if($voucher->bank_name)<tr><td class="lbl">Bank</td><td class="val">{{ $voucher->bank_name }}</td></tr>@endif
                @if($voucher->reference_number)<tr><td class="lbl">Reference</td><td class="val">{{ $voucher->reference_number }}</td></tr>@endif
                <tr><td colspan="2" style="padding:0 0 4pt;"></td></tr>
            </table>
        </td>
        <td style="width:30%; vertical-align:top;">
            <table class="box">
                <tr class="box-h"><td colspan="2">Status:</td></tr>
                <tr><td colspan="2" style="padding-top:5pt; padding-bottom:3pt;"><span class="badge b-{{ $voucher->status }}">{{ strtoupper($voucher->statusLabel()) }}</span></td></tr>
                <tr><td class="lbl">Date</td><td class="val">{{ $statusDate?->format('d-M-Y') ?? '—' }}</td></tr>
                <tr><td class="lbl">Approved By</td><td class="val">{{ $voucher->approvedBy->name ?? '—' }}</td></tr>
                @if($voucher->status === 'paid')<tr><td class="lbl">Paid By</td><td class="val">{{ $voucher->paidBy->name ?? '—' }}</td></tr>@endif
                <tr><td colspan="2" style="padding:0 0 4pt;"></td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- Purpose --}}
<table class="purpose" style="margin-bottom:8pt;">
    <tr><td class="ph">Purpose:</td><td>{!! nl2br(e($voucher->description)) !!}</td></tr>
</table>

{{-- Items: SL | Description | Qty | Amount --}}
<table class="grid" autosize="1">
    <thead>
        <tr>
            <th style="width:7%;">SL</th>
            <th style="width:61%;" class="l">Description</th>
            <th style="width:10%;">Qty</th>
            <th style="width:22%;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($voucher->items as $i => $item)
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c">{{ $i + 1 }}</td>
                <td class="l">
                    {{ $item->description }}
                    <div class="sub">{{ $item->quantity }} × {{ $n2($item->unit_price) }}@if($item->remarks) · {{ $item->remarks }}@endif</div>
                </td>
                <td class="c">{{ $item->quantity }}</td>
                <td class="amt">{{ $n2($item->amount) }}</td>
            </tr>
        @endforeach
        <tr class="grand">
            <td></td>
            <td class="l">GRAND TOTAL</td>
            <td class="c">{{ $sumQty }}</td>
            <td class="amt">{{ $n2($voucher->subtotal) }}</td>
        </tr>
    </tbody>
</table>

{{-- Totals (right) + PAID/CANCELLED stamp (left) --}}
<table style="margin-top:8pt;">
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
                <tr><td class="tk">Subtotal</td><td class="tv">BDT {{ $n2($voucher->subtotal) }}</td></tr>
                @if($hasTax)
                    <tr><td class="tk">Tax{{ $voucher->tax_type === 'percent' ? ' (' . $pct($voucher->tax_value) . '%)' : ' (fixed)' }}</td><td class="tv">+ BDT {{ $n2($voucher->tax_amount) }}</td></tr>
                @endif
                @if($hasDiscount)
                    <tr><td class="tk">Discount{{ $voucher->discount_type === 'percent' ? ' (' . $pct($voucher->discount_value) . '%)' : ' (fixed)' }}</td><td class="tv">− BDT {{ $n2($voucher->discount_amount) }}</td></tr>
                @endif
                <tr class="grand"><td style="text-align:right;">TOTAL</td><td style="text-align:right; white-space:nowrap; font-family:dejavusansmono, monospace;">BDT {{ $n2($voucher->total_amount) }}</td></tr>
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
        <td class="line" style="width:40%; text-align:left;">
            Prepared By
            <div style="font-size:7.5pt; font-weight:normal; color:#64748b; margin-top:1pt;">{{ $voucher->createdBy->name ?? '' }}</div>
        </td>
        <td style="width:20%;"></td>
        <td class="line" style="width:40%; text-align:right;">
            Approved By
            <div style="font-size:7.5pt; font-weight:normal; color:#64748b; margin-top:1pt;">{{ $voucher->approvedBy->name ?? '' }}</div>
        </td>
    </tr>
</table>

</body>
</html>
