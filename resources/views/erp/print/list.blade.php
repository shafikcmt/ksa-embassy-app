<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        /* Shared ERP module list PDF (E7a). Plain CSS only (no Tailwind); page
           margins (10mm) are set by PdfGeneratorService — no @page margin rule
           for the PDF render. Driven entirely by $columns + $rows so all 7
           modules reuse this one template.

           E7-print-consistency: this template is now ALSO the on-screen preview
           (Print button -> browser dialog). The screen/print/page rules + the
           toolbar only render when the _pdf flag is unset; mPDF ignores media
           queries and never sees the toolbar (it sits inside an empty(_pdf)
           guard), so the actual PDF output is unchanged. */
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

        {{-- @page is emitted ONLY for the browser. mPDF's constructor already sets
             A4 + 10mm margins; feeding it an @page rule makes this mPDF version
             spray blank pages, so it must be hidden from the PDF render. --}}
        @if(empty($_pdf))
        @page { size: A4; margin: 10mm; }

        @media screen {
            body { background: #e5e7eb; }
            .a4-page {
                width: 210mm;
                min-height: 297mm;
                margin: 10mm auto;
                background: #fff;
                box-shadow: 0 0 12px rgba(0,0,0,.15);
                padding: 10mm;
                box-sizing: border-box;
            }
        }

        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            /* Match mPDF geometry: @page already supplies the 10mm margin, so the
               wrapper adds no extra padding — browser print and PDF stay identical. */
            .a4-page { width: 100%; margin: 0; padding: 0; box-shadow: none; box-sizing: border-box; }
        }
        @endif
    </style>
</head>
<body>

@if(empty($_pdf))
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:7pt 12pt;margin-bottom:6pt;font-size:8pt;font-family:sans-serif;">
    <strong>{{ $title }}</strong>
    &nbsp;&nbsp;
    <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:3pt 10pt;border-radius:3pt;cursor:pointer;">&#128424; Print</button>
</div>
@endif

@if(empty($_pdf))<div class="a4-page">@endif

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

@if(empty($_pdf))</div>@endif
</body>
</html>
