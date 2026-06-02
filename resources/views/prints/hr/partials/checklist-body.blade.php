{{--
  SHARED Attachment Checklist body. Used by BOTH the single preview/PDF
  (prints.hr.checklist) and the Complete File (prints.hr.full-file, page 4)
  so the layout is identical everywhere. Fully self-contained with inline styles.
--}}
<div class="ksa-checklist">

{{-- Arabic title --}}
<div style="text-align:center;margin-bottom:12pt;direction:rtl;">
  <span style="font-size:13pt;font-weight:bold;text-decoration:underline;font-family:DejaVu Sans,sans-serif;">إرفاق الجدول التالي في كل معاملة</span>
</div>

{{-- 4-column table: RTL order (right to left): الاجراء | المكتب | المنفذ | الملاحظات --}}
<table style="border-collapse:collapse;direction:rtl;width:100%;">
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
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">رقم إنجاز / Application Number</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $application_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">رقم المستند / Visa No.</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $visa_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">الاسم في الجواز / Passport Holder Name</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">{{ $full_name_en }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">رقم الجواز / Passport Number</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $passport_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">صلاحية الجواز / Passport Validity</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $passport_expiry_date ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">العمر / Age</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">
        @if($date_of_birth && $date_of_birth !== '—'){{ $date_of_birth }}<br>@endif{{ $age_detail ?: ($age !== '—' ? $age.' years' : '—') }}
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
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">مساند / Musaned</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $musaned_no ?: 'N/A' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">الوكالة / Alwakala</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $wakala_no ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">فحص طبي / Medical Report</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">{{ $medical_fit ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;font-size:8pt;">{{ $medical_center ?: '' }}</td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">ورقة الشرطة / Police Clearance</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">{{ $pc_display ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:ltr;font-size:8pt;">{{ $pc_country ?: '' }}</td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">الرخصة / License</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $license_type ?: 'N/A' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">المهنة / Profession</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;font-size:8pt;{{ ($profession_ar ?? '') ? 'direction:rtl;' : 'direction:ltr;' }}">{{ $profession_ar ?: ($profession_en ?: ($occupation ?: '—')) }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr style="background:#f9f9f9;">
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">المؤهل وشهادة الخبرة / Experience Certificate</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;font-size:8pt;">{{ $qualification_en ?: 'N/A' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
    <tr>
      <td style="border:1px solid #000;padding:3pt 5pt;direction:rtl;text-align:right;">البصمة / Fingerprint</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;direction:ltr;">{{ $fingerprint ?: '—' }}</td>
      <td style="border:1px solid #000;padding:3pt 5pt;text-align:center;">&#9744;</td>
      <td style="border:1px solid #000;padding:3pt 5pt;"></td>
    </tr>
  </tbody>
</table>

{{-- Agency info and signatures (Arabic RTL) --}}
<table style="margin-top:18pt;direction:rtl;font-size:10pt;width:100%;border-collapse:collapse;">
  <tr>
    <td style="width:50%;text-align:right;direction:rtl;padding:4pt 0;">
      إسم المكتب - <strong>{{ $agency_name }}</strong>
    </td>
    <td style="width:50%;text-align:right;direction:rtl;padding:4pt 0;">
      رقم الرخصة - <strong>{{ $agency_rl ?: $agency_license ?: '—' }}</strong>
    </td>
  </tr>
  <tr><td colspan="2" style="height:14pt;"></td></tr>
  <tr>
    <td style="text-align:right;direction:rtl;padding:4pt 0;">التوقيع -</td>
    <td style="text-align:right;direction:rtl;padding:4pt 0;">الختم -</td>
  </tr>
</table>

</div>{{-- /.ksa-checklist --}}
