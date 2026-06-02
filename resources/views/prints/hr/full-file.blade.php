<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #000; margin: 0; padding: 0; line-height: 1.3; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 2pt 3pt; vertical-align: middle; font-size: 8pt; }
.ar { direction: rtl; text-align: right; font-family: DejaVu Sans, sans-serif; }
.b { font-weight: bold; }
.bdr td, .bdr th { border: 1px solid #000; }
.dtbl td, .dtbl th { font-size: 10pt; padding: 5pt 4pt; vertical-align: top; }

/* ── Scoped styles for the shared KSA Application form body ──────────────
   Identical ruleset to prints.hr.application so the form renders the SAME
   in the combined Complete File view. Scoped to .ksa-app so it does not
   affect the forwarding-letter / agreement / checklist pages. */
.ksa-app table { width: 100%; border-collapse: collapse; }
.ksa-app td, .ksa-app th { padding: 2pt 3pt; vertical-align: middle; font-size: 7.5pt; }
.ksa-app .bdr td, .ksa-app .bdr th { border: 1px solid #000; }
.ksa-app .ar { direction: rtl; text-align: right; font-family: 'DejaVu Sans', sans-serif; }
.ksa-app img { max-width: none; }
@media screen {
  body { background: #e5e7eb; }
  .a4-page { width: 210mm; min-height: 297mm; margin: 10mm auto; background: #fff; box-shadow: 0 0 12px rgba(0,0,0,.15); padding: 6mm; box-sizing: border-box; overflow: visible; }
  .a4-page-lg { width: 210mm; min-height: 297mm; margin: 10mm auto; background: #fff; box-shadow: 0 0 12px rgba(0,0,0,.15); padding: 14mm 16mm; box-sizing: border-box; overflow: visible; }
  /* KSA application page uses the SAME padding as the single preview (8mm 10mm) */
  .ksa-application-page { padding: 8mm 10mm; }
}
{{-- @page is emitted ONLY for the browser. mPDF's constructor already sets
     A4 + 10mm margins; feeding it an @page rule makes this mPDF version spray
     hundreds of blank pages, so it must be hidden from the PDF render. --}}
@if(empty($_pdf))
@page { size: A4; margin: 10mm; }
@endif
@media print {
  body { background: #fff; margin: 0; padding: 0; }
  .no-print { display: none !important; }
  .a4-page { width: 100%; margin: 0; padding: 6mm; box-shadow: none; box-sizing: border-box; page-break-after: always; }
  .a4-page-lg { width: 100%; margin: 0; padding: 14mm 16mm; box-shadow: none; box-sizing: border-box; page-break-after: always; }
  .a4-page:last-child, .a4-page-lg:last-child { page-break-after: auto; }
}
</style>
</head>
<body>

@if(empty($_pdf))
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:7pt 12pt;margin-bottom:6pt;font-size:8pt;">
  <strong>Complete File (All 4 Documents)</strong> — {{ $full_name_en }}
  &nbsp;&nbsp;
  <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:3pt 10pt;border-radius:3pt;cursor:pointer;">&#128424; Print</button>
  &nbsp;
  <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8592; Back</a>
  &nbsp;
  <a href="{{ route('hr.download.full-file', request()->route('hr')) }}" style="background:#16a34a;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8595; Download All PDF</a>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE 1: APPLICATION FORM --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if(empty($_pdf))<div class="a4-page ksa-application-page">@endif

{{-- Shared Saudi Embassy Application Form — identical to the single preview --}}
@include('prints.hr.partials.application-body')

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE BREAK --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if(!empty($_pdf))<pagebreak />@else</div><div class="a4-page-lg">@endif

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE 2: FORWARDING LETTER --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}

{{-- Shared Forwarding Letter — identical to the single preview --}}
@include('prints.hr.partials.forwarding-letter-body')

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE BREAK --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if(!empty($_pdf))<pagebreak />@else</div><div class="a4-page-lg">@endif

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE 3: EMPLOYMENT AGREEMENT --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}

{{-- Shared Employment Agreement — identical to the single preview --}}
@include('prints.hr.partials.employment-agreement-body')

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE BREAK --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if(!empty($_pdf))<pagebreak />@else</div><div class="a4-page">@endif

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- PAGE 4: ATTACHMENT CHECKLIST --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}

{{-- Shared Attachment Checklist — identical to the single preview --}}
@include('prints.hr.partials.checklist-body')

@if(empty($_pdf))</div>@endif
</body>
</html>
