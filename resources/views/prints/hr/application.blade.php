<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #000; margin: 0; padding: 0; line-height: 1.3; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 2pt 3pt; vertical-align: middle; font-size: 7.5pt; }
.ar { direction: rtl; text-align: right; font-family: DejaVu Sans, sans-serif; }
.b { font-weight: bold; }
.bdr td, .bdr th { border: 1px solid #000; }

/* ── Scoped styles for the shared KSA Application form body ──────────────
   Guarantees the partial renders identically in single and combined views,
   independent of any host/parent CSS. */
.ksa-app table { width: 100%; border-collapse: collapse; }
.ksa-app td, .ksa-app th { padding: 2pt 3pt; vertical-align: middle; font-size: 7.5pt; }
.ksa-app .bdr td, .ksa-app .bdr th { border: 1px solid #000; }
.ksa-app .ar { direction: rtl; text-align: right; font-family: 'DejaVu Sans', sans-serif; }
.ksa-app img { max-width: none; }

{{-- @page is emitted ONLY for the browser. mPDF's constructor already sets
     A4 + 10mm margins; feeding it an @page rule makes this mPDF version spray
     dozens of blank pages, so it must be hidden from the PDF render. --}}
@if(empty($_pdf))
@page { size: A4; margin: 10mm; }
@endif

@media screen {
  body { background: #e5e7eb; }
  .a4-page {
    width: 210mm;
    min-height: 297mm;
    margin: 10mm auto;
    background: #fff;
    box-shadow: 0 0 12px rgba(0,0,0,.15);
    padding: 8mm 10mm;
    box-sizing: border-box;
    overflow: hidden;
  }
}

@media print {
  body { background: #fff; margin: 0; padding: 0; }
  .no-print { display: none !important; }
  .a4-page {
    width: 100%;
    margin: 0;
    padding: 8mm 10mm;
    box-shadow: none;
    box-sizing: border-box;
    page-break-after: always;
  }
  .a4-page:last-child { page-break-after: auto; }
}
</style>
</head>
<body>

@if(empty($_pdf))
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:6pt 12pt;margin-bottom:4pt;font-size:8pt;">
  <strong>Application Form</strong> — {{ $full_name_en }}
  &nbsp;&nbsp;
  <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:3pt 9pt;border-radius:3pt;cursor:pointer;">&#128424; Print</button>
  &nbsp;
  <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;padding:3pt 9pt;border-radius:3pt;text-decoration:none;">&#8592; Back</a>
  &nbsp;
  <a href="{{ route('hr.download.application', request()->route('hr')) }}" style="background:#16a34a;color:#fff;padding:3pt 9pt;border-radius:3pt;text-decoration:none;">&#8595; PDF</a>
</div>
@endif

@if(empty($_pdf))<div class="a4-page">@endif

@include('prints.hr.partials.application-body')

@if(empty($_pdf))</div>@endif
</body>
</html>
