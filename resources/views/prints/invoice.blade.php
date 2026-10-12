<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
@include('prints.partials.invoice-styles')
</style>
</head>
<body>
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

@include('prints.partials.invoice-body', ['compact' => false])

</body>
</html>
