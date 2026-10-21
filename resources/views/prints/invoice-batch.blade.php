<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
@include('prints.partials.invoice-styles')

    /* ── Compact sheet: two invoices per A4 page ─────────────────────────────
       Same design AND same font sizes as the single invoice — only the empty
       space (padding / margins) is tightened so a normal invoice fits in half
       of the printable height. */
    .head td { padding: 0 4pt 3pt; }
    .a-rl, .a-addr, .a-contact { margin-top: 1pt; }
    .title td { padding: 3pt 0 3pt; }
    .meta td.mh { padding: 2.5pt 6pt; }
    .meta td.mb { padding: 3pt 6pt 4pt; }
    .kv td { padding: 1pt 0; }
    .grid th { padding: 3.5pt 2.5pt; }
    .grid td { padding: 3pt 2.5pt; }
    .grid tr.grand td { padding: 3.5pt 3pt; }
    .totals td { padding: 2pt 8pt; }
    .totals tr.grand td { padding: 3.5pt 8pt; }
    .words td { padding: 3pt 8pt; }
    .sign { margin-top: 20pt; }
    .cut td { border-top: 0.5px dashed #94a3b8; font-size: 1pt; line-height: 1pt; padding: 0; }
</style>
</head>
<body>
<htmlpagefooter name="invoiceBatchFooter">
    <table class="foot"><tr>
        <td style="width:60%; text-align:left;">Generated {{ now()->format('d-M-Y h:i A') }} | @if(count($sheets) === 1){{ $sheets[0]['invoice']->invoice_number }}@else{{ count($sheets) }} {{ \Illuminate\Support\Str::plural('invoice', count($sheets)) }}@endif</td>
        <td style="width:40%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr></table>
</htmlpagefooter>
<sethtmlpagefooter name="invoiceBatchFooter" value="on" />
@if(count($sheets) === 1 && $sheets[0]['invoice']->status === 'draft')
    <watermarktext content="DRAFT" alpha="0.06" />
@endif

@php
    // Agency logo — base64, gated by the agency's print_logo toggle; loaded once for the whole batch.
    $logoSrc = null;
    if ($agency->print_logo && $agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo)) {
        $ext = strtolower(pathinfo($agency->logo, PATHINFO_EXTENSION)) ?: 'png';
        $logoSrc = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,'
            . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($agency->logo));
    }

    // Layout: a "half" invoice shares the page with the next one (cut line between them);
    // a long invoice (many passengers / notes) gets its own page(s) and flows normally.
    $slot = 0;
    $needBreak = false;

    // A lone half-size invoice can sit on the lower half of a sheet that already carries one (?position=bottom).
    $lone = count($sheets) === 1 && $sheets[0]['half'];
    $position = $lone && ($position ?? 'top') === 'bottom' ? 'bottom' : 'top';
    $cut = '<table class="cut" style="margin:0;"><tr><td>&nbsp;</td></tr></table>';
    if ($lone && $position === 'bottom') {
        echo view('components.print.slot', ['pos' => 'top', 'height' => 138, 'slot' => new \Illuminate\Support\HtmlString('&nbsp;')])->render() . $cut;
        $slot = 1;
    }
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
    @if($half)
        {{-- Half-page box: top slot hugs the top edge, bottom slot the bottom edge; the cut line sits at the centre. --}}
        <x-print.slot :pos="$slot === 1 ? 'bottom' : 'top'">
            @include('prints.partials.invoice-body', [
                'invoice'       => $sheet['invoice'],
                'barcodeSrc'    => $sheet['barcodeSrc'],
                'amountInWords' => $sheet['amountInWords'],
                'dueInWords'    => $sheet['dueInWords'],
                'agency'        => $agency,
                'logoSrc'       => $logoSrc,
                'compact'       => true,
            ])
        </x-print.slot>
        @if($slot === 0){!! $cut !!}@endif
    @else
        @include('prints.partials.invoice-body', [
            'invoice'       => $sheet['invoice'],
            'barcodeSrc'    => $sheet['barcodeSrc'],
            'amountInWords' => $sheet['amountInWords'],
            'dueInWords'    => $sheet['dueInWords'],
            'agency'        => $agency,
            'logoSrc'       => $logoSrc,
            'compact'       => true,
        ])
    @endif

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
