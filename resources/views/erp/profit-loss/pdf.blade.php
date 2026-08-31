<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* mPDF template — plain CSS only. Margins (10mm) set by PdfGeneratorService. */
        body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 11px; }
        h1 { font-size: 17px; margin: 0 0 2px; }
        .muted { color: #64748b; font-size: 9px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        td { padding: 6px 8px; border: 0.5px solid #cbd5e1; }
        .k { color: #334155; }
        .v { text-align: right; white-space: nowrap; }
        .sub .k { padding-left: 22px; color: #64748b; }
        .rev .v { color: #047857; font-weight: bold; }
        .cost .v { color: #be123c; }
        .totalcost .k, .totalcost .v { font-weight: bold; }
        .totalcost .v { color: #be123c; }
        .profit td { background: #f1f5f9; font-size: 13px; font-weight: bold; }
        .profit .v.pos { color: #047857; }
        .profit .v.neg { color: #be123c; }
        .context td { border: 0.5px dashed #cbd5e1; color: #64748b; }
    </style>
</head>
<body>
    @php
        $m = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $profit = (float) $summary['profit'];
        $pos = $profit >= 0;
        $periodLabel = $period === 'custom'
            ? (($summary['from'] ?: 'beginning') . ' to ' . ($summary['to'] ?: 'today'))
            : ($period === 'month' ? 'This month' : 'All-time');
    @endphp

    <h1>Profit / Loss <span class="muted">(cash basis)</span></h1>
    <div class="meta muted">
        {{ $agency->name ?? 'Agency' }}<br>
        Period: {{ $periodLabel }}<br>
        Generated: {{ $generated->format('d M Y, h:i A') }}
    </div>

    <table>
        <tr class="rev"><td class="k">Revenue (collected)</td><td class="v">{{ $m($summary['revenue']) }}</td></tr>
        <tr class="cost sub"><td class="k">Cost — Expenses</td><td class="v">− {{ $m($summary['expenseCost']) }}</td></tr>
        <tr class="cost sub"><td class="k">Cost — Agent payouts</td><td class="v">− {{ $m($summary['agentPayoutCost']) }}</td></tr>
        <tr class="totalcost"><td class="k">Total Cost</td><td class="v">− {{ $m($summary['totalCost']) }}</td></tr>
        <tr class="profit"><td class="k">Net {{ $pos ? 'Profit' : 'Loss' }}</td><td class="v {{ $pos ? 'pos' : 'neg' }}">{{ $m($profit) }}</td></tr>
    </table>

    <table class="context">
        <tr><td class="k">Opening Balance (starting cash — context only, NOT in profit)</td><td class="v">{{ $m($summary['openingBalance']) }}</td></tr>
    </table>

    <p class="muted" style="margin-top:10px;">
        Cash basis: revenue is money actually collected (Delivery + Double MOFA). Cost is expenses plus money paid out to
        agents. Agent repayments (credits) and unpaid dues are not counted; opening balance is excluded from the profit figure.
    </p>
</body>
</html>
