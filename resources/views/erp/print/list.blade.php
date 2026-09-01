<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Shared ERP module list PDF (E7a). Plain CSS only (no Tailwind); page
           margins (10mm) are set by PdfGeneratorService — no @page margin rule.
           Driven entirely by $columns + $rows so all 7 modules reuse this one
           template. */
        body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 10px; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #64748b; font-size: 9px; }
        .meta { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { border: 0.5px solid #cbd5e1; padding: 4px 6px; }
        th { background: #f1f5f9; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.03em; color: #475569; }
        td { font-size: 9px; }
        .l { text-align: left; }
        .r { text-align: right; white-space: nowrap; }
        tfoot td { background: #f8fafc; font-weight: bold; }
        .empty { color: #94a3b8; text-align: center; padding: 12px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta muted">
        {{ $agency->name ?? 'Agency' }}<br>
        @isset($subtitle){{ $subtitle }}<br>@endisset
        Generated: {{ $generated->format('d M Y, h:i A') }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th class="{{ ($col['align'] ?? 'left') === 'right' ? 'r' : 'l' }}">{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $i => $col)
                        <td class="{{ ($col['align'] ?? 'left') === 'right' ? 'r' : 'l' }}">{{ $row[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($columns) }}">{{ $empty ?? 'No records to print.' }}</td></tr>
            @endforelse
        </tbody>
        @isset($totals)
            <tfoot>
                <tr>
                    @foreach($columns as $i => $col)
                        <td class="{{ ($col['align'] ?? 'left') === 'right' ? 'r' : 'l' }}">{{ $totals[$i] ?? '' }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endisset
    </table>
</body>
</html>
