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
{{-- Uniform type scale (page 4, matched to docs/references/CHECK.pdf): 9.7pt
     Medium in every cell, one weight for labels + values, 1.5 line pitch. The
     size is set INLINE on each th/td because the wrapper views' global
     "td, th { font-size: 9pt }" beats a size inherited from the table.
     autosize="1" stops mPDF shrinking the table. XB Riyaz runs wider than the
     reference's Naskh, so in-table Arabic is set at 8.6pt and Step cells
     never wrap. --}}
.ksa-checklist .ck-table { font-size: 9.7pt; }
.ksa-checklist .ck-ar { font-family: xbriyaz, 'DejaVu Sans', sans-serif; }
.ksa-checklist .ck-table td .ck-ar, .ksa-checklist .ck-table th .ck-ar { font-size: 8.6pt; }
@if(empty($_pdf))
  @font-face { font-family: ksaroboto; font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Medium.ttf') format('truetype'); }
  @font-face { font-family: ksaroboto; font-weight: bold;   font-style: normal; src: url('/fonts/Roboto-Bold.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz;   font-weight: normal; font-style: normal; src: url('/fonts/XBRiyaz.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz;   font-weight: bold;   font-style: normal; src: url('/fonts/XBRiyaz-Bold.ttf') format('truetype'); }
@endif
</style>
{{-- Reference (CHECK.pdf page 4): the table and footer run 7.6pt inside the
     10mm print margin (x ≈35.5 → 560pt); text #212529. --}}
<div class="ksa-checklist" style="margin:0 7.1pt;color:#212529;">

{{-- Top spacer: places the title at the reference's y≈98.5pt (≈34.7mm from the
     physical page top, with the 10mm print margin). --}}
<div style="height:24.8mm;"></div>

{{-- Centred, underlined Arabic title --}}
<div style="text-align:center;margin:0 0 25pt;direction:rtl;line-height:1.2;">
  <span style="font-size:16.7pt;font-weight:bold;text-decoration:underline;">إرفاق الجدول التالي في كل معاملة</span>
</div>

{{-- RTL table: visual columns L→R are Notes | Port | Agency | Step. Widths sit on
     each <th> (mPDF ignored the old RTL <colgroup>). Reference split: Step 35.1% ·
     Agency 30% · Port 18.1% · Notes 16.8%. Cells are top-aligned so the 2-line
     Age row reads like the reference. --}}
<table class="ck-table" autosize="1" style="width:100%;margin:0 auto;border-collapse:collapse;direction:rtl;font-size:9.7pt;line-height:1.5;">
  <thead>
    <tr>
      <th style="font-size:9.7pt;width:35.1%;border:0.7pt solid #212529;padding:4.9pt 4pt;text-align:center;font-weight:normal;background:#fff;">
        <span class="ck-ar">الاجراء</span><br>Step
      </th>
      <th style="font-size:9.7pt;width:30%;border:0.7pt solid #212529;padding:4.9pt 4pt;text-align:center;font-weight:normal;background:#fff;">
        <span class="ck-ar">المكتب</span><br>Agency
      </th>
      <th style="font-size:9.7pt;width:18.1%;border:0.7pt solid #212529;padding:4.9pt 4pt;text-align:center;font-weight:normal;background:#fff;">
        <span class="ck-ar">المنفذ</span><br>Port
      </th>
      <th style="font-size:9.7pt;width:16.8%;border:0.7pt solid #212529;padding:4.9pt 4pt;text-align:center;font-weight:normal;background:#fff;">
        <span class="ck-ar">الملاحظات</span><br>Notes
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
      // 4th tuple element is legacy (unused): every value uses ONE weight.
      [$step, $value, $arabicValue] = array_pad($row, 3, false);
      // Step is stored "Arabic / English"; both halves use the same weight and
      // optical size (order preserved by the RTL cell direction).
      [$stepAr, $stepEn] = array_pad(array_map('trim', explode('/', $step, 2)), 2, '');
    @endphp
    <tr>
      <td style="font-size:9.7pt;border:0.7pt solid #212529;padding:4.4pt 4pt;vertical-align:top;text-align:right;direction:rtl;white-space:nowrap;"><span class="ck-ar">{{ $stepAr }}</span> / <span dir="ltr" style="unicode-bidi:isolate;">{{ $stepEn }}</span></td>
      <td style="font-size:9.7pt;border:0.7pt solid #212529;padding:4.4pt 4pt;vertical-align:top;text-align:center;{{ $arabicValue ? 'direction:rtl;' : 'direction:ltr;' }}">@if($arabicValue)<span class="ck-ar">{{ $value }}</span>@else{!! nl2br(e($value)) !!}@endif</td>
      <td style="font-size:9.7pt;border:0.7pt solid #212529;padding:4.4pt 4pt;vertical-align:top;"></td>
      <td style="font-size:9.7pt;border:0.7pt solid #212529;padding:4.4pt 4pt;vertical-align:top;"></td>
    </tr>
    @endforeach
  </tbody>
</table>

{{-- Footer — right-aligned, NOT boxed (office name / licence / signature / stamp).
     Reference: Bold 12.4pt on an 18.6pt pitch, starting ~34pt below the table;
     signature and stamp captions ~56pt and ~75pt further down. --}}
<div style="margin-top:35.9pt;direction:rtl;text-align:right;font-size:12.4pt;font-weight:bold;line-height:1.5;">
  {{-- dir="ltr" isolates the Latin name/number so RTL bidi doesn't flip the "( )" --}}
  <div><span class="ck-ar" style="font-size:12.4pt;font-weight:bold;">إسم المكتب -</span> <strong dir="ltr" style="unicode-bidi:isolate;font-size:12.4pt;">{{ $agency_name }}</strong></div>
  <div><span class="ck-ar" style="font-size:12.4pt;font-weight:bold;">رقم الرخصة -</span> <strong dir="ltr" style="unicode-bidi:isolate;font-size:12.4pt;">{{ $agency_rl ?: $agency_license ?: '—' }}</strong></div>
  <div class="ck-ar" style="margin-top:37.3pt;font-size:12.4pt;font-weight:bold;">التوقيع -</div>
  <div class="ck-ar" style="margin-top:55.9pt;font-size:12.4pt;font-weight:bold;">الختم -</div>
</div>

</div>{{-- /.ksa-checklist --}}
