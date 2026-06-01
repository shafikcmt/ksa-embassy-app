<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #000; margin: 0; padding: 0; line-height: 1.5; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 3pt 5pt; vertical-align: middle; font-size: 9pt; }
.ar { direction: rtl; text-align: right; font-family: DejaVu Sans, sans-serif; }
.ltr { direction: ltr; text-align: left; }
@media screen {
  body { background: #e5e7eb; }
  .a4-page { width: 210mm; min-height: 297mm; margin: 10mm auto; background: #fff; box-shadow: 0 0 12px rgba(0,0,0,.15); padding: 10mm 12mm; box-sizing: border-box; overflow: hidden; }
}
{{-- @page is emitted ONLY for the browser. mPDF's constructor already sets
     A4 + 10mm margins; feeding it an @page rule makes this mPDF version spray
     blank pages, so it must be hidden from the PDF render. --}}
@if(empty($_pdf))
@page { size: A4; margin: 0; }
@endif
@media print {
  body { background: #fff; margin: 0; padding: 0; }
  .no-print { display: none !important; }
  .a4-page { width: 100%; margin: 0; padding: 10mm 12mm; box-shadow: none; box-sizing: border-box; page-break-after: always; }
  .a4-page:last-child { page-break-after: auto; }
}
</style>
</head>
<body>

@if(empty($_pdf))
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:7pt 12pt;margin-bottom:6pt;font-size:8pt;">
  <strong>Attachment Checklist</strong> — {{ $full_name_en }}
  &nbsp;&nbsp;
  <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:3pt 10pt;border-radius:3pt;cursor:pointer;">&#128424; Print</button>
  &nbsp;
  <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8592; Back</a>
  &nbsp;
  <a href="{{ route('hr.download.checklist', request()->route('hr')) }}" style="background:#16a34a;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8595; PDF</a>
</div>
@endif

@if(empty($_pdf))<div class="a4-page">@endif

{{-- Arabic title --}}
<div style="text-align:center;margin-bottom:12pt;direction:rtl;">
  <span style="font-size:13pt;font-weight:bold;text-decoration:underline;font-family:DejaVu Sans,sans-serif;">إرفاق الجدول التالي في كل معاملة</span>
</div>

{{-- 4-column table: RTL order (right to left): الاجراء | المكتب | المنفذ | الملاحظات --}}
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
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">{{ $full_name_en }}</td>
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
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">
        {{ $age }} yrs
        @if($date_of_birth) ({{ $date_of_birth }}) @endif
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
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">
        {{ $medical_fit ?: '—' }}
        @if($medical_date) ({{ $medical_date }}) @endif
      </td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;font-size:8pt;">{{ $medical_center ?: '' }}</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">شهادة السيرة / Police Clearance</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">
        {{ $pc_number ?: '—' }}
        @if($pc_expiry_date) exp.{{ $pc_expiry_date }} @endif
      </td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:ltr;font-size:8pt;">{{ $pc_country ?: '' }}</td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">نوع الرخصة / License Type</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $license_type ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">المهنة / Profession</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">شهادة خبرة / Experience Cert.</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">{{ $qualification_en ?: '—' }}</td>
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

{{-- Agency info and signatures (Arabic RTL) --}}
<table style="margin-top:14pt;direction:rtl;font-size:9.5pt;">
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
