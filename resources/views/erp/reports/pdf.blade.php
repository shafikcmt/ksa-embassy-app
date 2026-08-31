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
        th, td { border: 0.5px solid #cbd5e1; padding: 4px 6px; text-align: left; }
        th { background: #f1f5f9; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.03em; color: #475569; }
        td { font-size: 9px; }
        .num { text-align: right; white-space: nowrap; }
        .due { color: #be123c; font-weight: bold; }
        .paid { color: #047857; }
        .summary td { border: 0.5px solid #e2e8f0; }
        .summary .k { color: #475569; }
        .summary .v { text-align: right; font-weight: bold; }
        .empty { color: #94a3b8; text-align: center; padding: 10px; }
    </style>
</head>
<body>
    @php
        $m = fn ($v) => '৳ ' . number_format((float) $v, 2);
        $range = ($filters['from'] ?: 'beginning') . '  to  ' . ($filters['to'] ?: 'today');
    @endphp

    <h1>ERP Report</h1>
    <div class="meta muted">
        {{ $agency->name ?? 'Agency' }}<br>
        Date range: {{ $range }}<br>
        Generated: {{ $generated->format('d M Y, h:i A') }}
    </div>

    <h2>Summary</h2>
    <table class="summary">
        <tr><td class="k">Collected (in range)</td><td class="v">{{ $m($collectedInRange) }}</td>
            <td class="k">Collected (all-time)</td><td class="v">{{ $m($summary['collected']) }}</td></tr>
        <tr><td class="k">Total Billed</td><td class="v">{{ $m($summary['totalBilled']) }}</td>
            <td class="k">Outstanding Due</td><td class="v">{{ $m($summary['outstandingDue']) }}</td></tr>
        <tr><td class="k">Expenses (in range)</td><td class="v">{{ $m($expenses['rangeTotal']) }}</td>
            <td class="k">Expenses (all-time)</td><td class="v">{{ $m($expenses['allTime']) }}</td></tr>
        <tr><td class="k">Agent Receivable</td><td class="v">{{ $m($summary['agentReceivable']) }}</td>
            <td class="k">Agent Payable</td><td class="v">{{ $m($summary['agentPayable']) }}</td></tr>
    </table>

    <h2>Outstanding Dues ({{ $dues['count'] }} item{{ $dues['count'] === 1 ? '' : 's' }} · {{ $m($dues['combinedDue']) }})</h2>
    <table>
        <thead>
            <tr>
                <th>Type</th><th>Date</th><th>Full Name</th><th>Passport</th>
                <th class="num">Billed</th><th class="num">Paid</th><th class="num">Due</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dues['rows'] as $r)
                <tr>
                    <td>{{ $r['source_label'] }}</td>
                    <td>{{ $r['date'] }}</td>
                    <td>{{ $r['full_name'] }}</td>
                    <td>{{ $r['passport_no'] }}</td>
                    <td class="num">{{ $m($r['billed']) }}</td>
                    <td class="num paid">{{ $m($r['paid']) }}</td>
                    <td class="num due">{{ $m($r['due']) }}</td>
                    <td>{{ $r['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">No outstanding dues for this filter.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Expenses in range ({{ $expenses['rows']->count() }} item{{ $expenses['rows']->count() === 1 ? '' : 's' }} · {{ $m($expenses['rangeTotal']) }})</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Category</th><th>Paid Via</th><th class="num">Amount</th><th>Note</th></tr>
        </thead>
        <tbody>
            @forelse($expenses['rows'] as $e)
                <tr>
                    <td>{{ $e->expense_date->format('d M Y') }}</td>
                    <td>{{ $e->categoryLabel() }}</td>
                    <td>{{ $e->paidViaLabel() ?? '—' }}</td>
                    <td class="num due">{{ $m($e->amount) }}</td>
                    <td>{{ $e->note ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">No expenses for this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
