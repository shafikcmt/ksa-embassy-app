<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
@include('prints.partials.payment-voucher-styles')

    /* ── Compact sheet: two vouchers per A4 page ─────────────────────────────
       Same design AND same font sizes as the single voucher — only the empty
       space (padding / margins) is tightened so a normal voucher fits in half
       of the printable height. */
    .head td { padding: 0 4pt 4pt; }
    .title td { padding: 4pt 0 4pt; }
    .meta .box-h { padding: 4pt 8pt; }
    .meta .box-body { padding: 5pt 8pt; height: 50pt; }
    .fields td { padding: 0 0 3pt; }
    .p-line { margin-top: 2pt; }
    .purpose td { padding: 5pt 10pt; }
    .remarks td { padding: 4pt 10pt; }
    .totals td { padding: 3pt 10pt; }
    .totals .amount-label { padding: 5pt 12pt 0; }
    .totals tr.grand td { padding: 2pt 12pt 6pt; }
    .words td { padding: 6pt 12pt; }
    .sign { margin-top: 18pt; }
    .cut td { border-top: 0.5px dashed #94a3b8; font-size: 1pt; line-height: 1pt; padding: 0; }
</style>
</head>
<body>
<htmlpagefooter name="voucherBatchFooter">
    <table class="foot"><tr>
        <td style="width:60%; text-align:left;">Generated {{ now()->format('d-M-Y h:i A') }} | {{ count($sheets) }} {{ \Illuminate\Support\Str::plural('voucher', count($sheets)) }}</td>
        <td style="width:40%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr></table>
</htmlpagefooter>
<sethtmlpagefooter name="voucherBatchFooter" value="on" />

@php
    // Agency logo — base64, gated by the agency's print_logo toggle; loaded once for the whole batch.
    $logoSrc = null;
    if ($agency->print_logo && $agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo)) {
        $ext = strtolower(pathinfo($agency->logo, PATHINFO_EXTENSION)) ?: 'png';
        $logoSrc = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,'
            . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($agency->logo));
    }

    // A "half" voucher shares the page with the next one (cut line between them);
    // a heavy one (long remarks, tax/discount rows …) gets a page of its own.
    $slot = 0;
    $needBreak = false;
@endphp
@foreach($sheets as $sheet)
    @php
        $half = $sheet['half'];
        if ($needBreak || (! $half && $slot > 0)) {
            echo '<pagebreak />';
            $slot = 0;
            $needBreak = false;
        }
    @endphp
    @if($half && $slot === 1)
        <table class="cut" style="margin:6pt 0 6pt;"><tr><td>&nbsp;</td></tr></table>
    @endif

    @include('prints.partials.payment-voucher-body', [
        'voucher'       => $sheet['voucher'],
        'amountInWords' => $sheet['amountInWords'],
        'agency'        => $agency,
        'logoSrc'       => $logoSrc,
        'compact'       => true,
    ])

    @php
        if ($half) {
            $slot++;
            if ($slot === 2) { $slot = 0; $needBreak = true; }
        } else {
            $needBreak = true;
        }
    @endphp
@endforeach

</body>
</html>
