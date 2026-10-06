<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    /* ERP Invoice — standard per-passenger format (mPDF; preview = inline,
       download = attachment). Layout: accent → header (logo | agency | barcode)
       → title → 3 meta boxes (Bill To | Invoice Details | Status) → 9-column
       items grid + GRAND TOTAL → subtotal/tax/discount/TOTAL (+ PAID/CANCELLED
       stamp) → total / amount paid / BALANCE DUE → in words → notes → signatures → pinned footer.

       Rules: fixed font scale — 8pt body text, 7.5pt labels AND the whole items
       table (headers + cells), 9pt box headers. Column widths were sized from
       mPDF's own string metrics so every header fits on ONE line at 7.5pt (at
       8pt the 9 headers need ~196mm > the 190mm usable width); amounts use the
       condensed DejaVu cut so 9,999,999.99 fits without wrapping;
       the items table carries autosize="1" so mPDF never shrinks it (shrinking
       would silently change font sizes). mPDF-safe CSS only — tables for all
       layout (no inline-block / display:table / flex). A4 + 10mm margins come
       from PdfGeneratorService::makeMpdf(); no @page rule. Amounts use the ISO
       code (e.g. "BDT"), not the ৳ glyph, so the default font always renders. */
    body  { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 8pt; line-height: 1.3; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }

    .accent td { background-color: #2563eb; height: 3pt; line-height: 3pt; font-size: 1pt; }

    /* Header */
    .head td { vertical-align: middle; border-bottom: 0.8px solid #cbd5e1; padding: 6pt 4pt; }
    .a-name { font-size: 11pt; font-weight: bold; color: #0f172a; }
    .a-rl   { font-size: 9pt; font-weight: bold; color: #334155; margin-top: 2pt; }
    .a-addr { font-size: 8pt; font-style: italic; color: #475569; margin-top: 2pt; }
    .a-contact { font-size: 7.5pt; color: #475569; margin-top: 1.5pt; }
    .bc-num { font-family: dejavusansmono, monospace; font-size: 7pt; color: #334155; letter-spacing: 0.6pt; margin-top: 1pt; }

    /* Title */
    .title td { text-align: center; padding: 9pt 0 7pt; }
    .t-main { color: #0f172a; font-weight: bold; font-size: 14pt; letter-spacing: 1pt; text-decoration: underline; }

    /* Meta boxes — one shared table so all three boxes get the same height */
    .meta td.mh { background-color: #e8e8e8; border: 0.5px solid #333333; font-size: 9pt; font-weight: bold; padding: 4pt 6pt; }
    .meta td.mb { border: 0.5px solid #333333; border-top: none; padding: 4pt 6pt 6pt; vertical-align: top; }
    .meta td.gap { border: none; }
    .kv td { padding: 1.5pt 0; vertical-align: top; }
    .lbl { color: #475569; font-size: 7.5pt; white-space: nowrap; }
    .val { color: #0f172a; font-size: 8pt; font-weight: bold; text-align: right; white-space: nowrap; }
    .bill-name { color: #0f172a; font-size: 9pt; font-weight: bold; }
    .bill-line { color: #475569; font-size: 7.5pt; margin-top: 1.5pt; }

    .badge { font-size: 7.5pt; font-weight: bold; letter-spacing: 0.4pt; padding: 1.5pt 6pt; }
    .b-draft     { background-color: #e5e7eb; color: #1e293b; }
    .b-pending   { background-color: #fcd34d; color: #1e293b; }
    .b-paid      { background-color: #86efac; color: #14532d; }
    .b-cancelled { background-color: #fca5a5; color: #7f1d1d; }
    .b-overdue   { background-color: #e11d48; color: #ffffff; }

    /* Items grid — single-line headers, fixed widths */
    .grid th { background-color: #e8e8e8; color: #1e293b; font-size: 7.5pt; font-weight: bold; text-align: center; white-space: nowrap; padding: 5pt 2.5pt; border: 0.5px solid #333333; vertical-align: middle; }
    .grid td { font-size: 7.5pt; padding: 4pt 2.5pt; border: 0.5px solid #333333; vertical-align: top; }
    .grid tr.alt td { background-color: #f9f9f9; }
    .c { text-align: center; }
    .l { text-align: left; }
    .amt { font-family: dejavusanscondensed, sans-serif; font-weight: bold; text-align: right; white-space: nowrap; }
    .nw  { white-space: nowrap; }
    .due-open { color: #92400e; }
    .grid tr.grand td { background-color: #e8e8e8; font-weight: bold; padding: 5pt 3pt; }

    /* Totals */
    .totals td { font-size: 8pt; padding: 3pt 8pt; }
    .totals .tk { color: #475569; text-align: right; }
    .totals .tv { font-family: dejavusansmono, monospace; font-weight: bold; text-align: right; white-space: nowrap; }
    .totals tr.total td { border-top: 0.8px solid #94a3b8; color: #0f172a; font-weight: bold; font-size: 8.5pt; }
    .totals tr.paid td { color: #047857; }
    .totals tr.grand td { background-color: #1e293b; color: #ffffff; font-size: 9pt; font-weight: bold; padding: 5pt 8pt; border: 0.5px solid #1e293b; }

    .stamp { font-weight: bold; font-size: 12pt; letter-spacing: 3pt; padding: 4pt 8pt; border: 1.5px solid; text-align: center; }
    .stamp-paid      { color: #047857; border-color: #047857; }
    .stamp-cancelled { color: #be123c; border-color: #be123c; }

    /* In words / notes */
    .words td { background-color: #eff6ff; border: 0.5px solid #dbeafe; padding: 4pt 8pt; font-size: 8pt; }
    .w-label { color: #334155; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; }
    .w-value { color: #0f172a; font-weight: bold; font-style: italic; }
    .notes-h { color: #475569; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3pt; margin-bottom: 2pt; }
    .notes   { color: #334155; font-size: 8pt; }

    /* Signatures */
    .sign { margin-top: 48pt; }
    .sign .line { border-top: 0.5px solid #64748b; padding-top: 4pt; font-size: 9pt; font-weight: bold; color: #334155; }

    .foot td { border-top: 0.5px solid #e2e8f0; padding-top: 3pt; color: #999999; font-size: 7pt; }
</style>
</head>
<body>
@php
    $n2  = fn ($v) => number_format((float) ($v ?? 0), 2);
    $cur = $invoice->currency;
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

    // Bill To: typed snapshot first, linked agent as fallback per field.
    $billName    = $invoice->bill_to_name ?: ($invoice->agent?->name ?: '—');
    $billPhone   = $invoice->bill_to_phone ?: $invoice->agent?->phone;
    $billEmail   = $invoice->bill_to_email ?: $invoice->agent?->email;
    $billAddress = $invoice->bill_to_address ?: $invoice->agent?->address;

    // Column sums for the GRAND TOTAL row.
    $sumProc = $invoice->items->sum(fn ($i) => (float) $i->processing_fee);
    $sumMofa = $invoice->items->sum(fn ($i) => (float) $i->mofa_fee);
    $sumTot  = $invoice->items->sum(fn ($i) => (float) $i->total_amount);
    $sumPaid = $invoice->items->sum(fn ($i) => (float) ($i->paid_amount ?? 0));
    $sumDue  = $invoice->items->sum(fn ($i) => (float) $i->due_amount);

    // Footer summary: what was billed, what is already paid, what is still owed.
    $amountPaid = $invoice->status === 'paid' ? (float) $invoice->total_amount : $sumPaid;
    $balanceDue = round((float) $invoice->total_amount - $amountPaid, 2);

    $hasTax      = (float) $invoice->tax_amount > 0;
    $hasDiscount = (float) $invoice->discount_amount > 0;

    // Agency logo — base64, gated by the agency's print_logo toggle.
    $logoSrc = null;
    if ($agency->print_logo && $agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo)) {
        $ext = strtolower(pathinfo($agency->logo, PATHINFO_EXTENSION)) ?: 'png';
        $logoSrc = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,'
            . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($agency->logo));
    }
@endphp

{{-- Footer pinned to the bottom of every page --}}
<htmlpagefooter name="invoiceFooter">
    <table class="foot"><tr>
        <td style="width:60%; text-align:left;">Generated {{ now()->format('d-M-Y h:i A') }} | {{ $invoice->invoice_number }}@if($invoice->status === 'paid' && $invoice->paid_at) | Paid: {{ $invoice->paid_at->format('d-M-Y') }}@endif</td>
        <td style="width:40%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr></table>
</htmlpagefooter>
<sethtmlpagefooter name="invoiceFooter" value="on" />
@if($invoice->status === 'draft')
    <watermarktext content="DRAFT" alpha="0.06" />
@endif

<table class="accent"><tr><td>&nbsp;</td></tr></table>

{{-- 1. Header: logo | agency info | barcode --}}
<table class="head">
    <tr>
        @if($logoSrc)
            <td style="width:15%;"><img src="{{ $logoSrc }}" style="max-width:20mm; max-height:14mm;"></td>
        @endif
        <td style="width:{{ $logoSrc ? 57 : 72 }}%;">
            <div class="a-name">{{ $agency->name }}</div>
            <div class="a-rl">Recruiting Licence No. : {{ $agency->rl_number ?: '—' }}</div>
            @if($agency->address)<div class="a-addr">{{ $agency->address }}</div>@endif
            @if($agency->phone || $agency->email)
                <div class="a-contact">{{ collect([$agency->phone ? 'Phone: ' . $agency->phone : null, $agency->email ? 'Email: ' . $agency->email : null])->filter()->implode('  |  ') }}</div>
            @endif
        </td>
        <td style="width:28%; text-align:right;">
            @if($barcodeSrc)
                <img src="{{ $barcodeSrc }}" style="width:40mm; height:11mm;">
                <div class="bc-num">{{ $invoice->invoice_number }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- 2. Title --}}
<table class="title"><tr><td><span class="t-main">INVOICE</span></td></tr></table>

{{-- 3. Meta boxes: Bill To | Invoice Details | Status (equal heights) --}}
<table class="meta" style="margin-bottom:10pt;">
    <tr>
        <td class="mh" style="width:35%;">Bill To:</td>
        <td class="gap" style="width:2%;"></td>
        <td class="mh" style="width:33%;">Invoice Details:</td>
        <td class="gap" style="width:2%;"></td>
        <td class="mh" style="width:28%;">Status:</td>
    </tr>
    <tr>
        <td class="mb">
            <div class="bill-name">{{ $billName }}</div>
            @if($billPhone)<div class="bill-line">Phone: {{ $billPhone }}</div>@endif
            @if($billEmail)<div class="bill-line">Email: {{ $billEmail }}</div>@endif
            @if($billAddress)<div class="bill-line">{{ $billAddress }}</div>@endif
        </td>
        <td class="gap"></td>
        <td class="mb">
            <table class="kv">
                <tr><td class="lbl">Invoice No</td><td class="val">{{ $invoice->invoice_number }}</td></tr>
                <tr><td class="lbl">Invoice Date</td><td class="val">{{ $invoice->invoice_date->format('d-M-Y') }}</td></tr>
                @if($invoice->due_date)
                    <tr><td class="lbl">Due Date</td><td class="val">{{ $invoice->due_date->format('d-M-Y') }}</td></tr>
                @endif
            </table>
        </td>
        <td class="gap"></td>
        <td class="mb">
            <table class="kv">
                <tr><td colspan="2" style="padding-bottom:3pt;"><span class="badge b-{{ $invoice->status }}">{{ strtoupper($invoice->statusLabel()) }}</span></td></tr>
                <tr><td class="lbl">Currency</td><td class="val">{{ $cur }}</td></tr>
                @if($invoice->status === 'paid')
                    <tr><td class="lbl">Paid On</td><td class="val">{{ $invoice->paid_at?->format('d-M-Y') }}</td></tr>
                    <tr><td class="lbl">Method</td><td class="val">{{ $invoice->paymentMethodLabel() }}</td></tr>
                @else
                    <tr><td class="lbl">Passengers</td><td class="val">{{ $invoice->items->count() }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- 4. Items — 9 fixed columns, single-line headers, never auto-shrunk --}}
<table class="grid" autosize="1">
    <thead>
        <tr>
            <th style="width:5%;">SL No.</th>
            <th style="width:19%;">Passenger Name</th>
            <th style="width:11%;">Passport No</th>
            <th style="width:13%;">Processing Fee</th>
            <th style="width:10%;">MOFA Fee</th>
            <th style="width:12%;">Total Amount</th>
            <th style="width:11%;">Paid Amount</th>
            <th style="width:11%;">Due Amount</th>
            <th style="width:8%;">Remarks</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $i => $item)
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c">{{ $i + 1 }}</td>
                <td class="l">{{ $item->displayName() ?? '—' }}</td>
                <td class="l nw">{{ $item->displayPassport() ?? '—' }}</td>
                <td class="amt">{{ $n2($item->processing_fee) }}</td>
                <td class="amt">{{ $n2($item->mofa_fee) }}</td>
                <td class="amt">{{ $n2($item->total_amount) }}</td>
                <td class="amt">{{ $n2($item->paid_amount) }}</td>
                <td class="amt {{ (float) $item->due_amount > 0 ? 'due-open' : '' }}">{{ $n2($item->due_amount) }}</td>
                <td class="{{ $item->remarks ? 'l' : 'c' }}">{{ $item->remarks ?: '—' }}</td>
            </tr>
        @endforeach
        <tr class="grand">
            <td colspan="3" class="l">GRAND TOTAL</td>
            <td class="amt">{{ $n2($sumProc) }}</td>
            <td class="amt">{{ $n2($sumMofa) }}</td>
            <td class="amt">{{ $n2($sumTot) }}</td>
            <td class="amt">{{ $n2($sumPaid) }}</td>
            <td class="amt">{{ $n2($sumDue) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

{{-- 5. Totals (right) + PAID/CANCELLED stamp (left) --}}
<table style="margin-top:8pt;">
    <tr>
        <td style="width:55%; vertical-align:middle; padding-right:8pt;">
            @if($invoice->status === 'paid')
                <table style="width:auto;"><tr><td class="stamp stamp-paid">PAID</td></tr></table>
                <div style="margin-top:3pt; color:#047857; font-size:7.5pt;">
                    {{ $invoice->paid_at?->format('d-M-Y') }} · {{ $invoice->paymentMethodLabel() }}@if($invoice->payment_reference) · Ref: {{ $invoice->payment_reference }}@endif
                </div>
            @elseif($invoice->status === 'cancelled')
                <table style="width:auto;"><tr><td class="stamp stamp-cancelled">CANCELLED</td></tr></table>
            @endif
        </td>
        <td style="width:45%; vertical-align:top;">
            <table class="totals">
                <tr><td class="tk">Subtotal</td><td class="tv">{{ $cur }} {{ $n2($invoice->subtotal) }}</td></tr>
                @if($hasTax)
                    <tr><td class="tk">Tax{{ $invoice->tax_type === 'percent' ? ' (' . $pct($invoice->tax_value) . '%)' : ' (fixed)' }}</td><td class="tv">+ {{ $cur }} {{ $n2($invoice->tax_amount) }}</td></tr>
                @endif
                @if($hasDiscount)
                    <tr><td class="tk">Discount{{ $invoice->discount_type === 'percent' ? ' (' . $pct($invoice->discount_value) . '%)' : ' (fixed)' }}</td><td class="tv">− {{ $cur }} {{ $n2($invoice->discount_amount) }}</td></tr>
                @endif
                <tr class="total"><td class="tk" style="color:#0f172a;">Total</td><td class="tv">{{ $cur }} {{ $n2($invoice->total_amount) }}</td></tr>
                <tr class="paid"><td class="tk" style="color:#047857;">Amount Paid</td><td class="tv">− {{ $cur }} {{ $n2($amountPaid) }}</td></tr>
                <tr class="grand"><td style="text-align:right;">BALANCE DUE</td><td style="text-align:right; white-space:nowrap; font-family:dejavusansmono, monospace;">{{ $cur }} {{ $n2($balanceDue) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- 6. In words --}}
<table class="words" style="margin-top:8pt;">
    <tr><td><span class="w-label">Total In Words:</span> &nbsp;<span class="w-value">{{ $amountInWords }}</span></td></tr>
    @if(! empty($dueInWords) && $balanceDue > 0 && $balanceDue != (float) $invoice->total_amount)
        <tr><td><span class="w-label">Balance Due In Words:</span> &nbsp;<span class="w-value">{{ $dueInWords }}</span></td></tr>
    @endif
</table>

{{-- 7. Notes --}}
@if($invoice->notes)
    <div style="margin-top:8pt;">
        <div class="notes-h">Notes / Terms</div>
        <div class="notes">{!! nl2br(e($invoice->notes)) !!}</div>
    </div>
@endif

{{-- 8. Signatures --}}
<table class="sign">
    <tr>
        <td class="line" style="width:40%; text-align:left;">Deposit Signature</td>
        <td style="width:20%;"></td>
        <td class="line" style="width:40%; text-align:right;">Accounts Signature</td>
    </tr>
</table>

</body>
</html>
