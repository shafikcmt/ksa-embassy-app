{{--
  SHARED Saudi Embassy Application Form body (PAGE 1).
  Used by BOTH the single preview/PDF (prints.hr.application) and the
  Complete File preview/PDF (prints.hr.full-file) so the layout is identical
  in browser preview and downloaded PDF. Self-contained: depends only on the
  .ksa-app scoped CSS (.bdr / .ar / .lbl / .val / .inner) included by each host
  blade. Layout intentionally mirrors docs/images/ksa-application-reference-0001.jpg
  — do not redesign; only the dynamic values change per candidate.
--}}
@php
  $tp = strtolower($travel_purpose ?: 'work');
  $fullNameDisplay = $full_name_en . ($father_name ? ' S/O. ' . strtoupper($father_name) : '');
  // Purpose-of-Travel options — each box shows Arabic (top) + English (bottom),
  // exactly one row of boxes as in the reference (no duplicate EN/AR sections).
  $purposeOpts = [
    ['en' => 'Work',       'ar' => 'الشغل',       'val' => 'work'],
    ['en' => 'Transit',    'ar' => 'عبور',        'val' => 'transit'],
    ['en' => 'Visit',      'ar' => 'زيارة',       'val' => 'visit'],
    ['en' => 'Umrah',      'ar' => 'العمرة',      'val' => 'umrah'],
    ['en' => 'Residence',  'ar' => 'إقامة',       'val' => 'residence'],
    ['en' => 'Hajj',       'ar' => 'الحج',        'val' => 'hajj'],
    ['en' => 'Diplomacy',  'ar' => 'الدبلوماسية', 'val' => 'diplomacy'],
  ];
@endphp

{{-- ── Page-1 scoped typography/layout — bold compact embassy-form style ─────
     Defined here (inside the shared partial) so single preview, single PDF,
     complete-file preview and complete-file PDF all render identically. Every
     selector is under .ksa-app, so pages 2–4 are unaffected. Placed after the
     host <head> CSS, so these rules win on equal specificity. --}}
<style>
  .ksa-app { line-height: 1.12; color: #000; }
  .ksa-app table { width: 100%; border-collapse: collapse; }
  .ksa-app .bdr td, .ksa-app .bdr th { border: 1px solid #000; padding: 2.4pt 4pt; font-size: 7.6pt; font-weight: bold; vertical-align: middle; }
  .ksa-app .lbl { font-weight: bold; text-align: left; white-space: nowrap; }
  .ksa-app .val { font-weight: bold; text-align: center; }
  .ksa-app .ar  { direction: rtl; text-align: right; font-weight: bold; font-size: 7.3pt; white-space: nowrap; }
  .ksa-app .inner td { border: 0 !important; padding: 0; font-weight: bold; }
  /* Purpose-of-Travel option boxes — bordered table cells, AR over EN */
  .ksa-app .pt td.box { border: 1px solid #000 !important; text-align: center; padding: 1pt 2pt; line-height: 1.05; }
  .ksa-app .pt td.box .pa { font-size: 6.5pt; font-weight: normal; }
  .ksa-app .pt td.box .pe { font-size: 7pt; font-weight: bold; }
  .ksa-app .pt td.sel { background: #595959; }
  .ksa-app .pt td.sel .pa, .ksa-app .pt td.sel .pe { color: #fff; }
</style>
<div class="ksa-app">

{{-- ── HEADER: photo (left) · barcode (center) · embassy (right) ───────────── --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:8pt;">
  <tr>
    <td style="width:30%;vertical-align:top;padding:0;">
      {{-- Passport-size photo box (~35mm × 41mm), thin black border, top-left --}}
      <table style="width:100pt;border-collapse:collapse;"><tr>
        <td style="width:100pt;height:117pt;border:1px solid #000;text-align:center;vertical-align:middle;font-size:8pt;color:#555;padding:2pt;">
          Photo
        </td>
      </tr></table>
    </td>
    <td style="width:40%;text-align:center;vertical-align:top;padding:6pt 4pt 4pt 4pt;">
      {{-- Nested table: row-1 fixed height reliably pushes "New Application" down
           (mPDF ignores margin/padding between sibling divs inside a cell). --}}
      <table style="width:100%;border-collapse:collapse;">
        <tr><td style="height:74pt;text-align:center;vertical-align:top;border:0;padding:0;">
          @if(!empty($topBarcodeSrc))
            <img src="{{ $topBarcodeSrc }}" style="width:52mm;height:12mm;display:block;margin:0 auto;">
          @elseif(!empty($topBarcodeText))
            <div style="width:52mm;height:12mm;border:1px dashed #aaa;margin:0 auto;text-align:center;line-height:12mm;font-size:7pt;">{{ $topBarcodeText }}</div>
          @endif
          <div style="text-align:center;font-weight:bold;font-size:9pt;margin-top:2pt;letter-spacing:0.5pt;">{{ $topBarcodeText ?? '' }}</div>
        </td></tr>
        <tr><td style="text-align:center;vertical-align:top;border:0;padding:0;">
          <div style="font-size:11pt;font-weight:bold;text-align:center;">New Application</div>
        </td></tr>
      </table>
    </td>
    <td style="width:30%;text-align:right;vertical-align:top;padding:8pt 0 2pt 4pt;">
      @if(!empty($application_no))
      <div style="font-size:22pt;font-weight:bold;letter-spacing:0.5pt;">{{ $application_no }}</div>
      @endif
      <div style="font-size:10pt;font-weight:bold;margin-top:13pt;white-space:nowrap;">EMBASSY OF SAUDI ARABIA</div>
      <div style="font-size:9.5pt;font-weight:bold;margin-top:3pt;white-space:nowrap;">CONSULAR SECTION</div>
    </td>
  </tr>
</table>

{{-- ── MAIN IDENTITY TABLE (6-col grid: label | value | arabic ×2) ─────────── --}}
<table class="bdr">
  <colgroup>
    <col style="width:15%"><col style="width:20%"><col style="width:15%">
    <col style="width:15%"><col style="width:20%"><col style="width:15%">
  </colgroup>
  <tbody>
    {{-- Full Name --}}
    <tr>
      <td class="lbl">Full Name:</td>
      <td colspan="4" class="val">{{ $fullNameDisplay }}</td>
      <td class="ar">اسم الكامل :</td>
    </tr>
    {{-- Mother's Name --}}
    <tr>
      <td class="lbl">Mother's Name:</td>
      <td colspan="4" class="val">{{ $mother_name ?: '' }}</td>
      <td class="ar">اسم الأم :</td>
    </tr>
    {{-- Date of Birth | Place of Birth --}}
    <tr>
      <td class="lbl">Date of Birth:</td>
      <td class="val">{{ $date_of_birth }}</td>
      <td class="ar">تاريخ الولادة :</td>
      <td class="lbl">Place of Birth:</td>
      <td class="val">{{ $place_of_birth ?: '' }}</td>
      <td class="ar">محل الولادة :</td>
    </tr>
    {{-- Previous | Present Nationality --}}
    <tr>
      <td class="lbl">Previous Nationality:</td>
      <td class="val">{{ $previous_nationality ?: '' }}</td>
      <td class="ar">الجنسية السابقة :</td>
      <td class="lbl">Present Nationality:</td>
      <td class="val">{{ $nationality }}</td>
      <td class="ar">الجنسية الحالية :</td>
    </tr>
    {{-- Sex | Marital Status --}}
    <tr>
      <td class="lbl">Sex:</td>
      <td class="val">{{ $gender }}</td>
      <td class="ar">الجنس :</td>
      <td class="lbl">Marital Status:</td>
      <td class="val">{{ $marital_status ?: '' }}</td>
      <td class="ar">الحالة الاجتماعية :</td>
    </tr>
    {{-- Sect | Religion --}}
    <tr>
      <td class="lbl">Sect:</td>
      <td class="val">{{ $sect ?: '' }}</td>
      <td class="ar">المذهب :</td>
      <td class="lbl">Religion:</td>
      <td class="val">{{ $religion ?: '' }}</td>
      <td class="ar">الديانة :</td>
    </tr>
    {{-- Profession block — arabic labels + arabic profession value --}}
    <tr>
      <td>&nbsp;</td>
      <td class="ar">مصدره :</td>
      <td class="ar">المؤهل العلمي :</td>
      <td>&nbsp;</td>
      <td class="ar"><strong>{{ $profession_ar ?: '' }}</strong></td>
      <td class="ar">المهنة :</td>
    </tr>
    {{-- Profession block — english labels + english profession value --}}
    <tr>
      <td class="lbl">Place of Issue:</td>
      <td class="lbl" style="font-weight:normal;"><strong>Qualification:</strong> {{ $qualification_en ?: '' }}</td>
      <td>&nbsp;</td>
      <td class="lbl">Profession:</td>
      <td colspan="2" class="val">{{ $profession_en ?: ($occupation ?: '') }}</td>
    </tr>
    {{-- Home address --}}
    <tr>
      <td class="lbl">Home address &amp; phone No.:</td>
      <td colspan="3" class="val">{{ $home_address ?: ($phone ?: '') }}</td>
      <td colspan="2" class="ar">عنوان المنزل ورقم التلفون :</td>
    </tr>
    {{-- Business address --}}
    <tr>
      <td class="lbl">Business address &amp; phone No.:</td>
      <td colspan="3" class="val">
        @if($business_address_en){{-- stored value already includes RL; don't append it again --}}
          {{ $business_address_en }}@if($agency_email)<br>{{ $agency_email }}@endif
        @else
          {{ $agency_name }}@if($agency_rl) &nbsp; RL: {{ $agency_rl }}@endif @if($agency_email)<br>{{ $agency_email }}@endif
        @endif
      </td>
      <td colspan="2" class="ar">عنوان الشركة (المؤسسة) ورقم التلفون :</td>
    </tr>
    {{-- Full agency address (single centered line) --}}
    @if($agency_address)
    <tr>
      <td colspan="6" class="val" style="font-size:7.5pt;">{{ $agency_address }}</td>
    </tr>
    @endif
    {{-- Purpose of Travel — ONE row: label · compact boxes (AR+EN) · arabic label.
         Selected purpose (default Work) is grey-filled like the reference. --}}
    <tr>
      <td class="lbl" style="white-space:nowrap;">Purpose of Travel:</td>
      <td colspan="4" style="padding:2pt 4pt;vertical-align:middle;">
        <table class="pt" style="width:100%;border-collapse:collapse;"><tr>
          @foreach($purposeOpts as $opt)
          <td class="box{{ $tp === $opt['val'] ? ' sel' : '' }}" style="width:{{ number_format(100/count($purposeOpts),2) }}%;">
            <span class="pa">{{ $opt['ar'] }}</span><br><span class="pe">{{ $opt['en'] }}</span>
          </td>
          @endforeach
        </tr></table>
      </td>
      <td class="ar" style="white-space:nowrap;">الغاية من السفر :</td>
    </tr>
  </tbody>
</table>

{{-- ── PASSPORT / VISA SECTION ─────────────────────────────────────────────── --}}
<table class="bdr" style="margin-top:0;">
  <colgroup>
    <col style="width:22%"><col style="width:16%"><col style="width:22%">
    <col style="width:22%"><col style="width:18%">
  </colgroup>
  <tbody>
    {{-- Passport field headers: english + arabic label in one cell --}}
    <tr>
      <td><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Place of issue:</td><td class="ar">محل الإصدار :</td></tr></table></td>
      <td><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Date of issue:</td><td class="ar">تاريخ الإصدار :</td></tr></table></td>
      <td><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Date of expiry:</td><td class="ar">تاريخ انتهاء الصلاحية :</td></tr></table></td>
      <td colspan="2"><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Passport No.:</td><td class="ar">رقم الجواز :</td></tr></table></td>
    </tr>
    {{-- Passport field values (centered) --}}
    <tr>
      <td class="val">{{ $passport_issue_place ?: '' }}</td>
      <td class="val">{{ $passport_issue_date ?: '' }}</td>
      <td class="val">{{ $passport_expiry_date ?: '' }}</td>
      <td colspan="2" class="val">{{ $passport_no ?: '' }}</td>
    </tr>
    {{-- Duration / Arrival / Departure — arabic labels --}}
    <tr>
      <td colspan="2" class="ar">مدة الإقامة بالمملكة :</td>
      <td class="ar">تاريخ الوصول :</td>
      <td colspan="2" class="ar">تاريخ المغادرة :</td>
    </tr>
    {{-- Duration / Arrival / Departure — english labels + values --}}
    <tr>
      <td class="lbl" style="font-size:7pt;">Duration of stay in the kingdom:</td>
      <td class="val">{{ $duration_stay_en ?: '' }}@if(!empty($duration_stay_ar)) <span class="ar">({{ $duration_stay_ar }})</span>@endif</td>
      <td class="lbl" style="font-weight:normal;"><strong>Date of arrival:</strong> {{ $arrival_date ?: ($arrival_date_ar ?: '') }}</td>
      <td colspan="2" class="lbl" style="font-weight:normal;"><strong>Date of departure:</strong> {{ $departure_date ?: ($departure_date_ar ?: '') }}</td>
    </tr>
    {{-- Mode of payment — arabic labels --}}
    <tr>
      <td class="ar">تاريخ :</td>
      <td class="ar">إيصال رقم ( ) :</td>
      <td class="ar">تاريخ :</td>
      <td class="ar">بشيك رقم :</td>
      <td class="ar">طريقة الدفع :</td>
    </tr>
    {{-- Mode of payment — english (Mode of payment on the LEFT) --}}
    <tr>
      <td class="lbl">Mode of payment:</td>
      <td style="font-size:7pt;"><strong>{{ $payment_mode ?: 'Free' }}</strong> &nbsp; Cash &nbsp; Cheque</td>
      <td style="font-size:7.5pt;">Date: ________</td>
      <td style="font-size:7.5pt;">No.: ________</td>
      <td style="font-size:7.5pt;">Date: ________</td>
    </tr>
    {{-- Mahram name | Relationship --}}
    <tr>
      <td colspan="2" class="ar">صلته :</td>
      <td colspan="3" class="ar">اسم المحرم :</td>
    </tr>
    <tr>
      <td class="lbl">Relationship:</td>
      <td colspan="4" class="val">{{ $relationship ?: 'EMPLOYER AND EMPLOYEE' }}</td>
    </tr>
    {{-- Destination | Carrier --}}
    <tr>
      <td class="lbl">Destination:</td>
      <td class="val">{{ $destination_city ?: ($work_city ?: '') }}</td>
      <td class="ar">جهة الوصول :</td>
      <td class="lbl"><strong>Carrier's:</strong> {{ $carrier ?: '' }}</td>
      <td class="ar">اسم الشركة الناقلة :</td>
    </tr>
  </tbody>
</table>

{{-- ── DEPENDENTS ──────────────────────────────────────────────────────────── --}}
<table class="bdr" style="margin-top:0;font-size:7.5pt;">
  <colgroup>
    <col style="width:20%"><col style="width:30%"><col style="width:15%"><col style="width:35%">
  </colgroup>
  <tbody>
    <tr>
      <td class="lbl" colspan="2" style="font-size:7.5pt;">Dependents traveling in the same passport</td>
      <td colspan="2" class="ar">إصاحبات تخص أفراد العائلة المنتقلين في نفس جواز السفر</td>
    </tr>
    <tr>
      <td class="val"><span class="ar">نوع الصلة</span><br>Relationship</td>
      <td class="val"><span class="ar">تاريخ الميلاد</span><br>Date of Birth</td>
      <td class="val"><span class="ar">الجنس</span><br>Sex</td>
      <td class="val"><span class="ar">الاسم الكامل</span><br>Full Name</td>
    </tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr>
      <td>&nbsp;</td>
      <td class="val">CITY: {{ $work_city ?: '' }}, K.S.A</td>
      <td>&nbsp;</td><td>&nbsp;</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td class="val">TEL: {{ $agency_phone ?: '' }}</td>
      <td>&nbsp;</td><td>&nbsp;</td>
    </tr>
  </tbody>
</table>

{{-- ── NAME AND ADDRESS IN KINGDOM ─────────────────────────────────────────── --}}
<table class="bdr" style="margin-top:0;font-size:7.5pt;">
  <tbody>
    <tr>
      <td style="width:50%;">Name and address of company or individual in the kingdom :</td>
      <td style="width:50%;" class="ar">اسم وعنوان الشركة أو اسم الشخص وعنوانه بالمملكة :</td>
    </tr>
    <tr>
      <td class="val">{{ $kingdom_address_en ?: '' }}</td>
      <td class="ar"><strong>{{ $kingdom_address_ar ?: '' }}</strong></td>
    </tr>
  </tbody>
</table>

{{-- ── DECLARATION (english left | arabic right) ───────────────────────────── --}}
<table class="bdr" style="margin-top:0;font-size:7.5pt;">
  <tbody>
    <tr>
      <td style="width:55%;text-align:center;">I the undersigned hereby that all the information I have provided are correct. I will abide by laws of the kingdom during the period of my residence in it.</td>
      <td style="width:45%;" class="ar">أنا الموقع أدناه أقر بأن كل المعلومات التي زودتها صحيحة وسأكون ملتزماً بقوانين المملكة العربية السعودية خلال فترة وجودي بها.</td>
    </tr>
  </tbody>
</table>

{{-- ── SIGNATURE (single horizontal row) ───────────────────────────────────── --}}
<table class="bdr" style="margin-top:0;font-size:7.5pt;">
  <colgroup>
    <col style="width:14%"><col style="width:8%"><col style="width:18%">
    <col style="width:8%"><col style="width:44%"><col style="width:8%">
  </colgroup>
  <tbody>
    <tr>
      <td class="lbl">Date: ________</td>
      <td class="ar">التاريخ :</td>
      <td class="lbl">Signature: ________</td>
      <td class="ar">التوقيع :</td>
      <td class="lbl" style="font-weight:normal;"><strong>Name:</strong> {{ $full_name_en }}</td>
      <td class="ar">الاسم :</td>
    </tr>
  </tbody>
</table>

{{-- ── FOR OFFICIAL USE ONLY ───────────────────────────────────────────────── --}}
<table class="bdr" style="margin-top:2pt;font-size:7.5pt;">
  <colgroup>
    <col style="width:12%"><col style="width:16%"><col style="width:12%">
    <col style="width:14%"><col style="width:16%"><col style="width:30%">
  </colgroup>
  <tbody>
    <tr>
      <td colspan="3" style="font-weight:bold;font-size:8pt;text-decoration:underline;">For official use only</td>
      <td colspan="3" class="ar" style="font-weight:bold;font-size:8pt;">للاستعمال الرسمي فقط</td>
    </tr>
    <tr>
      <td class="lbl">Date:</td>
      <td class="val">{{ $visa_date_hijri ?: '' }}</td>
      <td class="ar">التاريخ :</td>
      <td class="lbl">Visa No:</td>
      <td class="val">{{ $visa_no ?: '' }}</td>
      <td class="ar">رقم الأمر المعتمد عليه في إعطاء التأشيرة :</td>
    </tr>
    <tr>
      <td class="lbl">Visit/Work for:</td>
      <td colspan="4" class="val">@if(!empty($sponsor_name_ar))<span class="ar">{{ $sponsor_name_ar }}</span>@else{{ $sponsor_name ?: '' }}@endif</td>
      <td class="ar">لزيارة :</td>
    </tr>
    <tr>
      <td class="lbl">Date: ________</td>
      <td>&nbsp;</td>
      <td class="ar">التاريخ :</td>
      <td class="lbl">Authorization:</td>
      <td class="val">{{ $wakala_no ?: ($musaned_no ?: '') }}</td>
      <td class="ar">أشير برقم :</td>
    </tr>
    <tr>
      <td class="lbl">Fee Collected:</td>
      <td class="ar">المبلغ المحصل :</td>
      <td class="lbl">Type:</td>
      <td class="ar">نوعها :</td>
      <td class="lbl">Duration:</td>
      <td class="ar">مدتها :</td>
    </tr>
  </tbody>
</table>

{{-- ── HEAD OF CONSULAR / BOTTOM BARCODE / CHECKED BY (below the box) ───────── --}}
<table style="margin-top:6pt;width:100%;border-collapse:collapse;">
  <tr>
    <td style="width:33%;vertical-align:bottom;font-size:7.5pt;">
      _______________<br>
      <span class="ar">رئيس القسم القنصلي :</span><br>
      Head of consular section
    </td>
    <td style="width:34%;text-align:center;vertical-align:bottom;">
      @if(!empty($bottomBarcodeSrc))
        <img src="{{ $bottomBarcodeSrc }}" style="width:52mm;height:9mm;display:block;margin:0 auto;">
      @endif
      <div style="text-align:center;font-size:8pt;font-weight:bold;letter-spacing:0.5pt;margin-top:2pt;">{{ $bottomBarcodeText ?? '' }}</div>
    </td>
    <td style="width:33%;text-align:right;vertical-align:bottom;font-size:7.5pt;">
      _______________<br>
      <span class="ar">مدقق البيانات رقم صاحب العمل</span><br>
      Checked by
    </td>
  </tr>
</table>

</div>{{-- /.ksa-app --}}
