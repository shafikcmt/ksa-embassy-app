<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* mPDF template — plain CSS only (no Tailwind). Page margins (10mm) are
           set by PdfGeneratorService; do NOT add an @page margin:0 rule. */
        body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 14px 0 4px; color: #334155; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; }
        .muted { color: #64748b; font-size: 9px; }
        .meta { margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 3px; }
        td { border: 0.5px solid #e2e8f0; padding: 4px 6px; text-align: left; font-size: 9px; }
        .k { color: #475569; }
        .v { text-align: right; font-weight: bold; }
        .due { color: #be123c; }
        .paid { color: #047857; }
        .profit { color: {{ ($bal['profit'] ?? 0) < 0 ? '#be123c' : '#047857' }}; }
    </style>
</head>
<body>
    @php $m = fn ($v) => '৳ ' . number_format((float) $v, 2); @endphp

    <h1>Monthly Summary</h1>
    <div class="meta muted">
        {{ $agency->name ?? 'Agency' }}<br>
        Month: {{ $month->format('F Y') }}<br>
        Generated: {{ $generated->format('d M Y, h:i A') }}
    </div>

    <h2>Operations</h2>
    <table>
        <tr><td class="k">MOFA</td><td class="v">{{ number_format($ops['mofa']) }}</td>
            <td class="k">Double MOFA</td><td class="v">{{ number_format($ops['doubleMofa']) }}</td></tr>
        <tr><td class="k">Stamping</td><td class="v">{{ number_format($ops['stamping']) }}</td>
            <td class="k">Manpower</td><td class="v">{{ number_format($ops['manpower']) }}</td></tr>
        <tr><td class="k">Delivery</td><td class="v">{{ number_format($ops['delivery']) }}</td>
            <td class="k">Pending Delivery</td><td class="v">{{ number_format($ops['pendingDelivery']) }}</td></tr>
    </table>

    <h2>Money</h2>
    <table>
        <tr><td class="k">Income (collected)</td><td class="v paid">{{ $m($ops['income']) }}</td>
            <td class="k">Expense</td><td class="v due">{{ $m($ops['expense']) }}</td></tr>
        <tr><td class="k">Due (record-dated in month)</td><td class="v due">{{ $m($ops['due']) }}</td>
            <td class="k">Profit / Loss</td><td class="v profit">{{ $m($bal['profit']) }}</td></tr>
    </table>

    <h2>Balance (owner-only)</h2>
    <table>
        <tr><td class="k">Starting Balance</td><td class="v">{{ $m($bal['starting']) }}</td>
            <td class="k">Ending Balance</td><td class="v">{{ $m($bal['ending']) }}</td></tr>
    </table>
</body>
</html>
