<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
@include('prints.partials.payment-voucher-styles')
</style>
</head>
<body>
<htmlpagefooter name="voucherFooter">
    <table class="foot"><tr>
        <td style="width:60%; text-align:left;">Generated {{ now()->format('d-M-Y h:i A') }} | {{ $voucher->voucher_number }}</td>
        <td style="width:40%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr></table>
</htmlpagefooter>
<sethtmlpagefooter name="voucherFooter" value="on" />

@include('prints.partials.payment-voucher-body', ['compact' => false])

</body>
</html>
