{{--
  SHARED Attachment Checklist body (reference page 4). Used by BOTH the single
  preview/PDF (prints.hr.checklist) and the Complete File (prints.hr.full-file,
  page 4) so the layout is identical everywhere. Fully self-contained.

  Government print style — match the reference exactly:
  • plain black-bordered table, WHITE header background, no dark fills
  • NO checkbox/square icons
  • Notes + Port columns stay empty; the value goes in the Agency column
  • Step column is bilingual (Arabic / English), right-aligned
  • unboxed, right-aligned footer (office name / licence / signature / stamp)
--}}
<div class="ksa-checklist" style="font-family:'DejaVu Sans',sans-serif;color:#000;">

{{-- Centred, underlined Arabic title --}}
<div style="text-align:center;margin:4pt 0 18pt;direction:rtl;">
  <span style="font-size:13pt;font-weight:bold;text-decoration:underline;">إرفاق الجدول التالي في كل معاملة</span>
</div>

{{-- RTL table: visual columns L→R are Notes | Port | Agency | Step --}}
<table style="width:94%;margin:0 auto;border-collapse:collapse;direction:rtl;font-size:9.5pt;">
  <colgroup>
    <col style="width:36%"><col style="width:30%"><col style="width:16%"><col style="width:18%">
  </colgroup>
  <thead>
    <tr>
      <th style="border:1px solid #000;padding:5pt 6pt;text-align:center;font-weight:bold;background:#fff;">
        الاجراء<br>Step
      </th>
      <th style="border:1px solid #000;padding:5pt 6pt;text-align:center;font-weight:bold;background:#fff;">
        المكتب<br>Agency
      </th>
      <th style="border:1px solid #000;padding:5pt 6pt;text-align:center;font-weight:bold;background:#fff;">
        المنفذ<br>Port
      </th>
      <th style="border:1px solid #000;padding:5pt 6pt;text-align:center;font-weight:bold;background:#fff;">
        الملاحظات<br>Notes
      </th>
    </tr>
  </thead>
  <tbody>
    @php
      // Step label (Arabic / English) => Agency value. Arabic-first in an RTL
      // cell renders English on the left and Arabic on the right, as in the
      // reference. $arabicValue marks rows whose value is Arabic (Profession).
      $rows = [
        ['رقم إنجاز / Application Number',            $application_no ?: '—',                                          false],
        ['رقم المستند / Visa No.',                    $visa_no ?: '—',                                                false],
        ['الاسم في الجواز / Passport Holder Name',    $full_name_en,                                                  false],
        ['رقم الجواز / Passport Number',              $passport_no ?: '—',                                            false],
        ['صلاحية الجواز / Passport Validity',         $passport_expiry_date ?: '—',                                   false],
        ['العمر / Age',                               trim(($date_of_birth && $date_of_birth !== '—' ? $date_of_birth."\n" : '').($age_detail ?: ($age !== '—' ? $age.' years' : ''))) ?: '—', false],
        ['الجنس / Sex',                               $gender ?: '—',                                                 false],
        ['مساند / Musaned',                           $musaned_no ?: 'N/A',                                           false],
        ['الوكالة / Alwakala',                        $wakala_no ?: '—',                                              false],
        ['فحص طبي / Medical Report',                  $medical_fit ?: '—',                                            false],
        ['ورقة الشرطة / Police Clearance',            $pc_display ?: '—',                                             false],
        ['الرخصة / License',                          $license_type ?: 'N/A',                                         false],
        ['المهنة / Profession',                       $profession_ar ?: ($profession_en ?: ($occupation ?: '—')),    (bool) ($profession_ar ?? '')],
        ['المؤهل وشهادة الخبرة / Experience Certificate', $qualification_en ?: 'N/A',                                 false],
        ['البصمة / Fingerprint',                      $fingerprint ?: '—',                                            false],
      ];
    @endphp
    @foreach($rows as [$step, $value, $arabicValue])
    <tr>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:right;direction:rtl;">{{ $step }}</td>
      <td style="border:1px solid #000;padding:4pt 6pt;text-align:center;{{ $arabicValue ? 'direction:rtl;' : 'direction:ltr;' }}">{!! nl2br(e($value)) !!}</td>
      <td style="border:1px solid #000;padding:4pt 6pt;"></td>
      <td style="border:1px solid #000;padding:4pt 6pt;"></td>
    </tr>
    @endforeach
  </tbody>
</table>

{{-- Footer — right-aligned, NOT boxed (office name / licence / signature / stamp) --}}
<div style="margin-top:26pt;direction:rtl;text-align:right;font-size:11pt;">
  {{-- dir="ltr" isolates the Latin name/number so RTL bidi doesn't flip the "( )" --}}
  <div style="margin-bottom:6pt;">إسم المكتب - <strong dir="ltr" style="unicode-bidi:isolate;">{{ $agency_name }}</strong></div>
  <div>رقم الرخصة - <strong dir="ltr" style="unicode-bidi:isolate;">{{ $agency_rl ?: $agency_license ?: '—' }}</strong></div>
  <div style="margin-top:34pt;">التوقيع -</div>
  <div style="margin-top:34pt;">الختم -</div>
</div>

</div>{{-- /.ksa-checklist --}}
