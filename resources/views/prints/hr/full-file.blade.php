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

<div style="text-align:center;margin-bottom:12pt;direction:rtl;">
  <span style="font-size:13pt;font-weight:bold;text-decoration:underline;font-family:DejaVu Sans,sans-serif;">إرفاق الجدول التالي في كل معاملة</span>
</div>

<table style="border-collapse:collapse;direction:rtl;">
  <colgroup>
    <col style="width:38%">
    <col style="width:22%">
    <col style="width:12%">
    <col style="width:28%">
  </colgroup>
  <thead>
    <tr style="background:#2c3e50;color:#fff;">
      <th style="border:1px solid #000;padding:4pt 5pt;text-align:right;direction:rtl;">الاجراء / Step</th>
      <th style="border:1px solid #000;padding:4pt 5pt;text-align:center;">المكتب / Agency</th>
      <th style="border:1px solid #000;padding:4pt 5pt;text-align:center;">المنفذ / Port</th>
      <th style="border:1px solid #000;padding:4pt 5pt;text-align:right;direction:rtl;">الملاحظات / Notes</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">رقم الملف / File Number</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $file_number ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">رقم التأشيرة / Visa No.</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $visa_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">اسم الكامل / Full Name</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:7.5pt;">{{ $full_name_en }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">رقم الجواز / Passport No.</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $passport_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">انتهاء الجواز / Passport Validity</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $passport_expiry_date ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">السن / Age</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:7.5pt;">
        {{ $age }} yrs @if($date_of_birth)({{ $date_of_birth }})@endif
      </td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">الجنس / Sex</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $gender ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">مصنع / Musaned No.</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $musaned_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">الوكالة / Al-Wakala No.</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $wakala_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">التقرير الطبي / Medical Report</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:7.5pt;">
        {{ $medical_fit ?: '—' }} @if($medical_date)({{ $medical_date }})@endif
      </td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;font-size:7.5pt;">{{ $medical_center ?: '' }}</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">شهادة السيرة / Police Clearance</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:7.5pt;">
        {{ $pc_number ?: '—' }} @if($pc_expiry_date)exp.{{ $pc_expiry_date }}@endif
      </td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:ltr;font-size:7.5pt;">{{ $pc_country ?: '' }}</td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">نوع الرخصة / License Type</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $license_type ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">المهنة / Profession</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:7.5pt;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">شهادة خبرة / Experience Cert.</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:7.5pt;">{{ $qualification_en ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">بصمة / Fingerprint</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $fingerprint ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
  </tbody>
</table>

<table style="margin-top:14pt;direction:rtl;font-size:9.5pt;border-collapse:collapse;">
  <tr>
    <td style="width:50%;text-align:right;direction:rtl;padding:3pt 0;">
      إسم المكتب - <strong>{{ $agency_name }}</strong>
    </td>
    <td style="width:50%;text-align:right;direction:rtl;padding:3pt 0;">
      رقم الرخصة - <strong>{{ $agency_rl ?: '—' }}</strong>
    </td>
  </tr>
  <tr>
    <td style="text-align:right;direction:rtl;padding:3pt 0;">
      التوقيع - &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </td>
    <td style="text-align:right;direction:rtl;padding:3pt 0;">
      الختم - &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </td>
  </tr>
</table>

@if(empty($_pdf))</div>@endif
</body>
</html>
