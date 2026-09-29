{{--
    Shared layout for the ERP summary PDFs (Medical, MOFA, Visa Stamping, BMET).

    A summary view sets these, then @extends this layout and fills
    @section('table') with its <table class="grid">:
      $module       medical | mofa | stamping | bmet   (App\Support\ErpPrintTheme::DOCS)
      $orientation  landscape (default) | portrait
      $sectionNote  optional text appended to the subtitle (e.g. MOFA portrait part)
    Data from the controller: $agency, $entries, $generated, and optionally
    $_downloadUrl / $_backUrl (screen preview toolbar).

    One HTML, three outputs: browser preview, browser print and the mPDF file
    (?download / PdfGeneratorService). mPDF gets page size + 12 mm margins from
    ErpPrintTheme::mpdfOptions(); the @page rule is emitted for the browser only
    (an @page rule breaks mPDF pagination in this project).
--}}
@use('App\Support\ErpPrintTheme')
@php
    $doc         = ErpPrintTheme::doc($module);
    $orientation = ($orientation ?? 'landscape') === 'portrait' ? 'portrait' : 'landscape';
    $scale       = $orientation === 'portrait' ? 0.9 : 1;   // portrait: ~10% smaller type
    $bodyPt      = round($doc['body'] * $scale, 1);
    $headPt      = round($doc['head'] * $scale, 1);
    $count       = $entries->count();
    $stamp       = $generated->format('d-M-Y h:i A');
    $rl          = $agency->rl_number ?: '—';
    [$badgeBg, $badgeFg] = $doc['tone'];

    // Logo: mPDF ignores width/height on <div>s inside table cells, so the round
    // badge is an <img> with explicit size + border-radius. Agencies without a
    // print logo get an SVG circle with their initials (renders identically in
    // mPDF and the browser).
    $logoSrc = null;
    if ($agency->print_logo && $agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo)) {
        $ext     = strtolower(pathinfo($agency->logo, PATHINFO_EXTENSION)) ?: 'png';
        $logoSrc = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,'
            . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($agency->logo));
    }
    // Real logo inside a blue ring, clipped to a circle (SVG so mPDF draws it round).
    $logoRing = $logoSrc ? 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="120" height="120" viewBox="0 0 120 120">'
        . '<defs><clipPath id="c"><circle cx="60" cy="60" r="53"/></clipPath></defs>'
        . '<circle cx="60" cy="60" r="56" fill="#ffffff" stroke="' . ErpPrintTheme::PRIMARY . '" stroke-width="6"/>'
        . '<image x="10" y="10" width="100" height="100" preserveAspectRatio="xMidYMid meet" clip-path="url(#c)" xlink:href="' . $logoSrc . '" href="' . $logoSrc . '"/>'
        . '</svg>'
    ) : null;
    $initialsSvg = 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">'
        . '<circle cx="60" cy="60" r="56" fill="#eff6ff" stroke="' . ErpPrintTheme::PRIMARY . '" stroke-width="6"/>'
        . '<text x="60" y="74" font-family="DejaVu Sans, Arial, sans-serif" font-size="38" font-weight="bold" text-anchor="middle" fill="' . ErpPrintTheme::PRIMARY_DARK . '">'
        . e(ErpPrintTheme::initials($agency->name)) . '</text></svg>'
    );
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $doc['title'] }} — {{ $agency->name }}</title>
<style>
    body { font-family: dejavusans, Arial, sans-serif; color: #1f2937; font-size: {{ $bodyPt }}pt; line-height: 1.4; }

    /* ── Header band: logo | agency | document badge + date ── */
    .hdr { width: 100%; border-collapse: collapse; background: #f9fafb; }
    .hdr td { vertical-align: middle; padding: 10pt 12pt; }
    .logo-img { width: 16mm; height: 16mm; border: 2px solid {{ ErpPrintTheme::PRIMARY }}; border-radius: 8mm; background: #ffffff; padding: 1.5mm; }
    .logo-svg { width: 16mm; height: 16mm; }
    .agency-name { font-size: {{ $orientation === 'portrait' ? 13 : 16 }}pt; font-weight: bold; color: #0f172a; line-height: 1.15; }
    .agency-rl { font-size: {{ round(11 * $scale, 1) }}pt; color: #6b7280; margin-top: 3pt; }
    .agency-addr { font-size: 9pt; color: #6b7280; font-style: italic; margin-top: 2pt; }
    .doc-badge-wrap { border-collapse: collapse; margin-left: auto; }
    .doc-badge { background: {{ $badgeBg }}; color: {{ $badgeFg }}; font-size: 11pt; font-weight: bold; padding: 5pt 10pt; border-radius: 4pt; text-align: center; white-space: nowrap; }
    .doc-date { font-size: 9pt; color: #6b7280; margin-top: 5pt; text-align: right; }
    .divider { border-top: 1px solid #e5e7eb; margin: 9pt 0; height: 0; }

    /* ── Title ── */
    .title { text-align: center; font-size: 22pt; font-weight: bold; color: {{ ErpPrintTheme::PRIMARY_DARK }}; line-height: 1.1; }
    .subtitle { text-align: center; font-size: 10pt; color: #6b7280; margin-top: 3pt; margin-bottom: 10pt; }

    /* ── Data table ── */
    table.grid { width: 100%; border-collapse: collapse; }
    .grid th { background-color: {{ ErpPrintTheme::PRIMARY }}; background: linear-gradient(to bottom, {{ ErpPrintTheme::PRIMARY }}, {{ ErpPrintTheme::PRIMARY_DARK }});
               color: #ffffff; font-size: {{ $headPt }}pt; font-weight: bold; letter-spacing: 0.15pt; text-align: center; padding: 6pt 3pt; line-height: 1.2; }
    .grid th.l { text-align: left; }
    .grid td { padding: 4pt 3pt; border-bottom: 0.5px solid #e5e7eb; vertical-align: middle; color: #1f2937; line-height: 1.25; }
    .grid tr.alt td { background: #f9fafb; }
    .grid td.c { text-align: center; }
    .grid td.num { text-align: right; font-family: dejavusansmono, monospace; }
    .grid td.nw { white-space: nowrap; }
    .grid td.low { color: #b91c1c; font-weight: bold; }
    .grid td.muted { color: #9ca3af; }
    .badge { font-size: {{ max(6.5, $bodyPt - 1) }}pt; font-weight: bold; padding: 1.5pt 5pt; border-radius: 4pt; white-space: nowrap; }
    .grid tfoot td { background: #e0e7ff; border-top: 1px solid {{ ErpPrintTheme::PRIMARY }}; border-bottom: 0; font-weight: bold; color: {{ ErpPrintTheme::PRIMARY_DARK }}; padding: 6pt 8pt; }
    .grid td.empty { background: #f3f4f6; color: #6b7280; font-size: 10pt; font-style: italic; text-align: center; padding: 16pt; }

    /* ── Screen-only footer (mPDF uses <htmlpagefooter>) ── */
    .foot { width: 100%; border-collapse: collapse; margin-top: 14pt; border-top: 2px solid {{ ErpPrintTheme::PRIMARY }}; background: linear-gradient(to bottom, #f0f9ff, #ffffff); font-size: 8pt; color: #6b7280; }
    .foot td { padding: 6pt 10pt; }

    @if(empty($_pdf))
    @page { size: A4 {{ $orientation }}; margin: 12mm; }
    @media screen {
        body { background: #e5e7eb; margin: 0; }
        .sheet { width: {{ $orientation === 'portrait' ? '210mm' : '297mm' }}; min-height: {{ $orientation === 'portrait' ? '297mm' : '210mm' }};
                 margin: 10mm auto; background: #fff; box-shadow: 0 1px 14px rgba(15,23,42,.18); padding: 12mm; box-sizing: border-box; }
        .toolbar { position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: 12px; background: #1e293b; color: #fff; padding: 8px 16px; font: 13px/1.4 Arial, sans-serif; }
        .toolbar a, .toolbar button { color: #fff; background: rgba(255,255,255,.12); border: 0; border-radius: 6px; padding: 6px 12px; font: inherit; text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: {{ ErpPrintTheme::PRIMARY }}; }
        .toolbar .spacer { flex: 1; }
    }
    @media print {
        body { background: #fff; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .toolbar { display: none !important; }
        .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        .grid thead { display: table-header-group; }
        .grid tr { page-break-inside: avoid; }
    }
    @endif
</style>
</head>
<body>

@if(empty($_pdf))
    <div class="toolbar">
        <strong>{{ $doc['title'] }}</strong>
        <span style="opacity:.7">{{ $count }} {{ \Illuminate\Support\Str::plural('record', $count) }}</span>
        <span class="spacer"></span>
        @isset($_backUrl)<a href="{{ $_backUrl }}">&larr; Back</a>@endisset
        @isset($_downloadUrl)<a href="{{ $_downloadUrl }}">Download PDF</a>@endisset
        <button type="button" class="primary" onclick="window.print()">Print</button>
    </div>
    @isset($_downloadUrl)
        {{-- Medical / Visa Stamping previews open the print dialog automatically (existing behaviour). --}}
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
    @endisset
@else
    <htmlpagefooter name="erpSummaryFooter">
        <table width="100%" style="border-collapse:collapse; border-top:2px solid {{ ErpPrintTheme::PRIMARY }}; background:linear-gradient(to bottom, #f0f9ff, #ffffff); font-size:8pt; color:#6b7280;">
            <tr>
                <td width="33%" style="padding:5pt 8pt;">Page {PAGENO} of {nbpg}</td>
                <td width="34%" style="padding:5pt 8pt; text-align:center; border-left:0.5px solid #d1d5db; border-right:0.5px solid #d1d5db;">Recruiting Licence: {{ $rl }}</td>
                <td width="33%" style="padding:5pt 8pt; text-align:right;">Generated: {{ $stamp }}</td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="erpSummaryFooter" value="on" />
@endif

@if(empty($_pdf))<div class="sheet">@endif

    {{-- Header --}}
    <table class="hdr">
        <tr>
            <td style="width:20%;">
                @if($logoRing)
                    <img src="{{ $logoRing }}" alt="{{ $agency->name }} logo" class="logo-svg" width="60" height="60">
                @else
                    <img src="{{ $initialsSvg }}" alt="{{ $agency->name }}" class="logo-svg" width="60" height="60">
                @endif
            </td>
            <td style="width:60%; text-align:center;">
                <div class="agency-name">{{ $agency->name }}</div>
                <div class="agency-rl">Recruiting Licence No.: {{ $rl }}</div>
                @if($agency->address)<div class="agency-addr">{{ $agency->address }}</div>@endif
            </td>
            <td style="width:20%; text-align:right;">
                {{-- Nested table: mPDF only pads/colours a block reliably as a table cell. mPDF
                     right-aligns it via align="right"; browsers would FLOAT that, so they get
                     margin-left:auto (in .doc-badge-wrap) instead. --}}
                <table class="doc-badge-wrap" @if(! empty($_pdf)) align="right" @endif><tr><td class="doc-badge">{{ $doc['badge'] }}</td></tr></table>
                {{-- Portrait's narrow cell can't hold date + time on one line, and mPDF ignores
                     nowrap on inline spans, so break explicitly between them. --}}
                <div class="doc-date">Date: {{ $generated->format('d-M-Y') }}@if($orientation === 'portrait')<br>@else @endif{{ $generated->format('h:i A') }}</div>
            </td>
        </tr>
    </table>
    <div class="divider"></div>

    {{-- Title --}}
    <div class="title">{{ $doc['title'] }}</div>
    <div class="subtitle">
        Generated: {{ $stamp }} &nbsp;|&nbsp; Total records: {{ number_format($count) }}@isset($sectionNote) &nbsp;|&nbsp; {{ $sectionNote }}@endisset
    </div>

    @yield('table')

    @if(empty($_pdf))
        <table class="foot">
            <tr>
                {{-- Page numbers exist only in the PDF (<htmlpagefooter>); the browser can't know them. --}}
                <td width="33%">Total records: {{ number_format($count) }}</td>
                <td width="34%" style="text-align:center;">Recruiting Licence: {{ $rl }}</td>
                <td width="33%" style="text-align:right;">Generated: {{ $stamp }}</td>
            </tr>
        </table>
    @endif

@if(empty($_pdf))</div>@endif
</body>
</html>
