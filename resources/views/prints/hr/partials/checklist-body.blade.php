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
<style>
{{-- Latin uses Roboto; Arabic glyphs (title, Step column, footer — not all are
     .ar) fall through to XB Riyaz, the same Naskh mPDF renders in the PDF, so the
     browser preview matches the download and the reference. --}}
.ksa-checklist, .ksa-checklist table, .ksa-checklist td, .ksa-checklist th,
.ksa-checklist div, .ksa-checklist span, .ksa-checklist p, .ksa-checklist strong { font-family: ksaroboto, xbriyaz, freesans, sans-serif; }
.ksa-checklist .ar { font-family: xbriyaz, 'DejaVu Sans', sans-serif; }
@if(empty($_pdf))
  @font-face { font-family: ksaroboto; font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Medium.ttf') format('truetype'); }
  @font-face { font-family: ksaroboto; font-weight: bold;   font-style: normal; src: url('/fonts/Roboto-Bold.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz;   font-weight: normal; font-style: normal; src: url('/fonts/XBRiyaz.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz;   font-weight: bold;   font-style: normal; src: url('/fonts/XBRiyaz-Bold.ttf') format('truetype'); }
@endif
</style>
{{-- width:92% + margin:0 auto matches PAGE 1's .ksa-app column so all four
     Complete-File pages share the SAME left/right margin (≈17.6mm) in the PDF.
     The inner table is width:100% below so it fills this column (was 94%, which
     nested-inset it narrower than the other pages). --}}
<div class="ksa-checklist" style="width:92%;margin:0 auto;color:#000;">

{{-- Top spacer: page 4's reference (ksa-application-reference-0004.jpg) places
     the title ~35mm from the physical page top. mPDF reserves a 10mm margin, so
     22mm here puts the title at ~35mm to match. --}}
<div style="height:22mm;"></div>

{{-- Centred, underlined Arabic title --}}
<div style="text-align:center;margin:4pt 0 18pt;direction:rtl;">
  <span style="font-size:13pt;font-weight:bold;text-decoration:underline;">إرفاق الجدول التالي في كل معاملة</span>
</div>

{{-- RTL table: visual columns L→R are Notes | Port | Agency | Step --}}
<table style="width:100%;margin:0 auto;border-collapse:collapse;direction:rtl;font-size:10pt;">
  <colgroup>
    <col style="width:36%"><col style="width:30%"><col style="width:16%"><col style="width:18%">
  </colgroup>
  <thead>
    <tr>
      <th style="border:1px solid #202428;padding:8pt 6pt;text-align:center;font-weight:bold;background:#fff;">
        الاجراء<br>Step
      </th>
      <th style="border:1px solid #202428;padding:8pt 6pt;text-align:center;font-weight:bold;background:#fff;">
        المكتب<br>Agency
      </th>
      <th style="border:1px solid #202428;padding:8pt 6pt;text-align:center;font-weight:bold;background:#fff;">
        المنفذ<br>Port
      </th>
      <th style="border:1px solid #202428;padding:8pt 6pt;text-align:center;font-weight:bold;background:#fff;">
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
        ['رقم إنجاز / Application Number',            $application_no ?: '—',                                          false, true],
        ['رقم المستند / Visa No.',                    $visa_no ?: '—',                                                false, true],
        ['الاسم في الجواز / Passport Holder Name',    $full_name_en_upper,                                            false, true],
        ['رقم الجواز / Passport Number',              $passport_no ?: '—',                                            false, true],
        ['صلاحية الجواز / Passport Validity',         $passport_expiry_date_long ?: ($passport_expiry_date ?: '—'),   false, true],
        ['العمر / Age',                               trim(($date_of_birth && $date_of_birth !== '—' ? $date_of_birth."\n" : '').($age_detail ?: ($age !== '—' ? $age.' years' : ''))) ?: '—', false],
        ['الجنس / Sex',                               $gender ?: '—',                                                 false],
        ['مساند / Musaned',                           $musaned_no ?: 'N/A',                                           false],
        ['الوكالة / Alwakala',                        $wakala_no ?: '—',                                              false],
        ['فحص طبي / Medical Report',                  'FIT',                                                          false, true],
        ['ورقة الشرطة / Police Clearance',            $pc_display ?: '—',                                             false],
        ['الرخصة / License',                          $license_type ?: 'N/A',                                         false],
        ['المهنة / Profession',                       $profession_ar ?: ($profession_en ?: ($occupation ?: '—')),    (bool) ($profession_ar ?? '')],
        ['المؤهل وشهادة الخبرة / Experience Certificate', $qualification_en ?: 'N/A',                                 false],
        ['البصمة / Fingerprint',                      $fingerprint ?: '—',                                            false],
      ];
    @endphp
    @foreach($rows as $row)
    @php
      // $bold (4th tuple element, default false) bolds only the candidate name row.
      [$step, $value, $arabicValue, $bold] = array_pad($row, 4, false);
      // Step is stored "Arabic / English"; the reference renders the English
      // label bold and the Arabic label in regular weight, so split and bold
      // only the English half (order preserved by the RTL cell direction).
      [$stepAr, $stepEn] = array_pad(array_map('trim', explode('/', $step, 2)), 2, '');
    @endphp
    <tr>
      <td style="border:1px solid #202428;padding:7pt 6pt;text-align:right;direction:rtl;">{{ $stepAr }} / <strong dir="ltr" style="unicode-bidi:isolate;">{{ $stepEn }}</strong></td>
      <td style="border:1px solid #202428;padding:7pt 6pt;text-align:center;{{ $arabicValue ? 'direction:rtl;' : 'direction:ltr;' }}">{!! nl2br(e($value)) !!}</td>
      <td style="border:1px solid #202428;padding:7pt 6pt;"></td>
      <td style="border:1px solid #202428;padding:7pt 6pt;"></td>
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
