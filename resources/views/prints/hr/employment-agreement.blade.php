<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 13pt; color: #000; margin: 0; padding: 0; line-height: 1.6; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 3pt 5pt; vertical-align: top; }
.ar { direction: rtl; text-align: right; }
.ksa-signature { page-break-inside: avoid; break-inside: avoid; }

{{-- @page is emitted ONLY for the browser. mPDF's constructor already sets
     A4 + 10mm margins; feeding it an @page rule makes this mPDF version spray
     dozens/hundreds of blank pages, so it must be hidden from the PDF render. --}}
@if(empty($_pdf))
@page { size: A4; margin: 12mm; }
@endif

@media screen {
  body { background: #e5e7eb; }
  .a4-page {
    width: 210mm;
    min-height: 297mm;
    margin: 10mm auto;
    background: #fff;
    box-shadow: 0 0 12px rgba(0,0,0,.15);
    padding: 12mm;
    box-sizing: border-box;
    overflow: visible;
  }
}

@media print {
  html, body { background: #fff !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; }
  .no-print { display: none !important; }
  /* @page provides the 12mm safe margin; the wrapper adds none so the
     signature row never lands in the printer's unprintable bottom zone. */
  .a4-page {
    width: 100%;
    margin: 0;
    padding: 0;
    box-shadow: none;
    box-sizing: border-box;
    overflow: visible !important;
    page-break-after: auto;
  }
}
</style>
</head>
<body>

@if(empty($_pdf))
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:7pt 12pt;margin-bottom:6pt;font-size:8pt;">
  <strong>Employment Agreement</strong> — {{ $full_name_en }}
  &nbsp;&nbsp;
  <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:3pt 10pt;border-radius:3pt;cursor:pointer;">&#128424; Print</button>
  &nbsp;
  <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8592; Back</a>
  &nbsp;
  <a href="{{ route('hr.download.employment-agreement', request()->route('hr')) }}" style="background:#16a34a;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8595; PDF</a>
</div>
@endif

@if(empty($_pdf))<div class="a4-page">@endif

@include('prints.hr.partials.employment-agreement-body')

@if(empty($_pdf))</div>@endif
</body>
</html>
