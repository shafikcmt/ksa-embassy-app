<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
    /* Credit Voucher (Payment Received) — mPDF render only (opened inline via the
       voucher routes). Clean, modern single-payment invoice/voucher: selective
       borders (grid only on the data table, thin dividers elsewhere), generous
       whitespace, clear hierarchy. mPDF-safe CSS only: table-based layout, solid
       colors, solid borders — no gradients/shadows/flex/grid. A4 + 10mm margins
       come from PdfGeneratorService::makeMpdf(); no @page rule is emitted. Brand
       accent = Tailwind brand indigo (#2563eb / #1d4ed8 / #1e40af / #eff6ff). */
    body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 9.5pt; margin: 0; padding: 0; }
    table { border-collapse: collapse; width: 100%; }

    /* Brand accent bar across the very top. */
    .accent td { background-color: #2563eb; height: 4pt; line-height: 4pt; font-size: 2pt; }

    /* Document title. */
    .title td { text-align: center; color: #1d4ed8; font-weight: bold; font-size: 14pt; letter-spacing: 0.8pt; padding: 12pt 0 8pt; }

    /* Voucher meta — clean label-over-value pairs, no heavy box; one soft divider. */
    .meta { border-bottom: 0.8px solid #e2e8f0; margin-bottom: 12pt; }
    .meta td { padding: 2pt 4pt 10pt; vertical-align: top; }
    .m-label { color: #64748b; font-weight: bold; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.5pt; margin-bottom: 3pt; }
    .m-value { color: #0f172a; font-weight: bold; font-size: 10.5pt; }

    /* Single-row data table — the one place a bordered grid belongs. */
    .grid th { background-color: #eff6ff; color: #1d4ed8; font-weight: bold; text-align: center; font-size: 7pt; letter-spacing: 0pt; text-transform: uppercase; padding: 6pt 3pt; border: 0.5px solid #cbd5e1; white-space: nowrap; }
    .grid td { padding: 8pt 6pt; font-size: 9pt; color: #1e293b; border: 0.5px solid #e2e8f0; }
    .l { text-align: left; }
    .c { text-align: center; }
    .r { text-align: right; white-space: nowrap; }   /* amounts: never wrap mid-value */
    .nw { white-space: nowrap; }                      /* passport ids: never wrap mid-value */
    .b { font-weight: bold; }

    /* Grand total — summary band (brand-50 bg, brand-800 text). */
    .grand td { background-color: #eff6ff; color: #1e40af; font-weight: bold; border: 0.5px solid #cbd5e1; }

    /* Amount in words — a single highlighted brand strip. */
    .words { margin-top: 12pt; }
    .words td { background-color: #eff6ff; border: 0.5px solid #dbeafe; padding: 8pt 10pt; }
    .w-label { color: #64748b; font-weight: bold; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.4pt; }
    .w-value { color: #1d4ed8; font-weight: bold; font-style: italic; font-size: 11pt; }

    /* Signatures — clean top-border lines, generous breathing room above. */
    .sign { margin-top: 52pt; }
    .sign .line { border-top: 0.7px solid #94a3b8; padding-top: 5pt; font-weight: bold; color: #334155; font-size: 10pt; }
</style>
</head>
<body>

{{-- ── Brand accent bar ── --}}
<table class="accent"><tr><td>&nbsp;</td></tr></table>

{{-- ── Header band (shared partial: logo + centered agency branding) ── --}}
@include('prints.partials.agency-header', ['agency' => $agency])

{{-- ── Title ── --}}
<table class="title"><tr><td>CREDIT VOUCHER (Payment Received)</td></tr></table>

{{-- ── Voucher meta: Reference Person / Voucher No / Date ── --}}
<table class="meta">
    <tr>
        <td style="width:42%;">
            <div class="m-label">Reference Person</div>
            <div class="m-value">{{ $referenceName ?: '—' }}</div>
        </td>
        <td style="width:33%;">
            <div class="m-label">Voucher No</div>
            <div class="m-value">{{ $voucherNo }}</div>
        </td>
        <td style="width:25%; text-align:right;">
            <div class="m-label">Date</div>
            <div class="m-value">{{ $date }}</div>
        </td>
    </tr>
</table>

{{-- ── Payment line item(s) + total. One receipt = one row (no filler rows). ── --}}
@php $money = fn ($v) => $v === null || $v === '' ? '' : number_format((float) $v, 2); @endphp
<table class="grid">
    <thead>
        <tr>
            <th style="width:5%;">SL No.</th>
            <th style="width:15%;">Passenger Name</th>
            <th style="width:13%;">Passport No</th>
            <th style="width:13%;">Processing Fee</th>
            <th style="width:10%;">Mofa Fee</th>
            <th style="width:12%;">Total Amount</th>
            <th style="width:12%;">Paid Amount</th>
            <th style="width:11%;">Due Amount</th>
            <th style="width:9%;">Remarks</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $i => $row)
            <tr>
                <td class="c">{{ $row['sl'] ?? ($i + 1) }}</td>
                <td class="l">{{ $row['passenger'] ?? '' }}</td>
                <td class="l nw">{{ $row['passport'] ?? '' }}</td>
                <td class="r">{{ $money($row['processing_fee'] ?? null) }}</td>
                <td class="r">{{ $money($row['mofa_fee'] ?? null) }}</td>
                <td class="r b">{{ $money($row['total'] ?? null) }}</td>
                <td class="r b">{{ $money($row['paid'] ?? null) }}</td>
                <td class="r">{{ $money($row['due'] ?? null) }}</td>
                <td class="l">{{ $row['remarks'] ?? '' }}</td>
            </tr>
        @endforeach
        <tr class="grand">
            <td colspan="3" class="r">GRAND TOTAL</td>
            <td class="r">{{ $money($grandTotals['processing_fee'] ?? null) }}</td>
            <td class="r">{{ $money($grandTotals['mofa_fee'] ?? null) }}</td>
            <td class="r">{{ $money($grandTotals['total'] ?? null) }}</td>
            <td class="r">{{ $money($grandTotals['paid'] ?? null) }}</td>
            <td></td><td></td>
        </tr>
    </tbody>
</table>

{{-- ── Amount in words (highlighted strip) ── --}}
<table class="words">
    <tr>
        <td><span class="w-label">In Words:</span> &nbsp;<span class="w-value">{{ $amountInWords }}</span></td>
    </tr>
</table>

{{-- ── Signatures ── --}}
<table class="sign">
    <tr>
        <td class="line" style="width:42%;">Deposit Signature</td>
        <td style="width:16%;"></td>
        <td class="line" style="width:42%; text-align:right;">Accounts Signature</td>
    </tr>
</table>

</body>
</html>
