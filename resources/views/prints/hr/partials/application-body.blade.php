{{--
  SHARED Saudi Embassy Application Form body.
  Used by BOTH the single preview/PDF (prints.hr.application) and the
  Complete File preview/PDF (prints.hr.full-file) so the layout is identical
  everywhere. Self-contained: only depends on the .ksa-app scoped CSS that
  each host blade includes in its <style> head. Expects the same $data the
  withBarcodes() helper provides (topBarcodeSrc/Text, bottomBarcodeSrc/Text,
  plus the standard PrintDataMapper fields).
--}}
<div class="ksa-app">

{{-- HEADER ROW --}}
{{-- Symmetric 30/40/30 columns so the barcode sits at the TRUE page center,
     photo left-aligned in left cell, embassy text right-aligned in right cell --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:4pt;">
  <tr>
    <td style="width:30%;vertical-align:top;padding:0;">
      <table style="width:75pt;border-collapse:collapse;"><tr>
        <td style="width:75pt;height:105pt;border:1px solid #000;text-align:center;vertical-align:middle;font-size:7.5pt;color:#555;padding:2pt;">
          Photo
        </td>
      </tr></table>
    </td>
    <td style="width:40%;text-align:center;vertical-align:top;padding:6pt 4pt 4pt 4pt;">
      @if(!empty($topBarcodeSrc))
        <img src="{{ $topBarcodeSrc }}" style="width:52mm;height:10mm;display:block;margin:0 auto;">
      @elseif(!empty($topBarcodeText))
        <div style="width:52mm;height:10mm;border:1px dashed #aaa;margin:0 auto;text-align:center;line-height:10mm;font-size:7pt;">{{ $topBarcodeText }}</div>
      @endif
      <div style="text-align:center;font-weight:bold;font-size:7.5pt;margin-top:2pt;letter-spacing:0.5pt;">{{ $topBarcodeText ?? '' }}</div>
      <div style="font-size:10pt;font-weight:bold;margin-top:16pt;text-align:center;">New Application</div>
    </td>
    <td style="width:30%;text-align:right;vertical-align:top;padding:4pt 0 2pt 4pt;">
      @if($file_number && $file_number !== '—')
      <div style="font-size:16pt;font-weight:bold;letter-spacing:0.5pt;">{{ $file_number }}</div>
      @endif
      <div style="font-size:9.5pt;font-weight:bold;margin-top:4pt;">EMBASSY OF SAUDI ARABIA</div>
      <div style="font-size:8.5pt;margin-top:2pt;">CONSULAR SECTION</div>
    </td>
  </tr>
</table>

<div style="border-top:1.5px solid #000;margin-bottom:2pt;"></div>

{{-- MAIN FORM TABLE (4-column structure throughout) --}}
{{-- Columns: 35% | 15% | 35% | 15% --}}
@php
  $tp = strtolower($travel_purpose ?: '');
  $fullNameDisplay = $full_name_en . ($father_name ? ' S/O. ' . strtoupper($father_name) : '');
@endphp
<table class="bdr">
  <colgroup>
    <col style="width:35%">
    <col style="width:15%">
    <col style="width:35%">
    <col style="width:15%">
  </colgroup>
  <tbody>
    {{-- Full Name --}}
    <tr>
      <td colspan="2"><strong>Full Name:</strong> {{ $fullNameDisplay }}</td>
      <td colspan="2" class="ar" style="font-size:7pt;">اسم الكامل : <strong>{{ $full_name_ar ?: '' }}</strong></td>
    </tr>
    {{-- Mother's Name --}}
    <tr>
      <td colspan="2"><strong>Mother's Name:</strong> {{ $mother_name ?: '' }}</td>
      <td colspan="2" class="ar" style="font-size:7pt;">اسم الأم :</td>
    </tr>
    {{-- DOB + Place of Birth --}}
    <tr>
      <td><strong>Date of Birth:</strong> {{ $date_of_birth }}</td>
      <td class="ar" style="font-size:7pt;">تاريخ الولادة :</td>
      <td><strong>Place of Birth:</strong> {{ $place_of_birth ?: '' }}</td>
      <td class="ar" style="font-size:7pt;">محل الولادة :</td>
    </tr>
    {{-- Prev + Present Nationality --}}
    <tr>
      <td><strong>Previous Nationality:</strong> {{ $previous_nationality ?: '' }}</td>
      <td class="ar" style="font-size:7pt;">الجنسية السابقة :</td>
      <td><strong>Present Nationality:</strong> {{ $nationality }}</td>
      <td class="ar" style="font-size:7pt;">الجنسية الحالية :</td>
    </tr>
    {{-- Sex + Marital Status --}}
    <tr>
      <td><strong>Sex:</strong> {{ $gender }}</td>
      <td class="ar" style="font-size:7pt;">الجنس :</td>
      <td><strong>Marital Status:</strong> {{ $marital_status ?: '' }}</td>
      <td class="ar" style="font-size:7pt;">الحالة الاجتماعية :</td>
    </tr>
    {{-- Sect + Religion --}}
    <tr>
      <td><strong>Sect:</strong> ___________</td>
      <td class="ar" style="font-size:7pt;">الطائفة :</td>
      <td><strong>Religion:</strong> {{ $religion ?: '' }}</td>
      <td class="ar" style="font-size:7pt;">الديانة :</td>
    </tr>
    {{-- Misc row --}}
    <tr>
      <td>&nbsp;</td>
      <td class="ar" style="font-size:7pt;">مصنعه :</td>
      <td>&nbsp;</td>
      <td class="ar" style="font-size:7pt;">المؤهل العلمي :</td>
    </tr>
    {{-- Place of Issue | Qualification | Profession --}}
    <tr>
      <td><strong>Place of Issue:</strong> {{ $passport_issue_place ?: '' }}</td>
      <td><strong>Qualification:</strong> {{ $qualification_en ?: '' }}</td>
      <td><strong>Profession:</strong> {{ $profession_en ?: ($occupation ?: '') }}</td>
      <td class="ar" style="font-size:7pt;">المهنة :</td>
    </tr>
    {{-- Home address --}}
    <tr>
      <td colspan="2"><strong>Home address &amp; phone No.:</strong> {{ $phone ?: '' }}</td>
      <td colspan="2" class="ar" style="font-size:7pt;">عنوان المنزل ورقم التلفون :</td>
    </tr>
    {{-- Business address --}}
    <tr>
      <td colspan="2">
        <strong>Business address &amp; phone No.:</strong>
        {{ $agency_name }}
        @if($agency_rl) &nbsp; RL: {{ $agency_rl }} @endif
        @if($agency_email) &nbsp; {{ $agency_email }} @endif
      </td>
      <td colspan="2" class="ar" style="font-size:7pt;">عنوان الشركة (ورقم التلفون) :</td>
    </tr>
    {{-- Full agency address --}}
    @if($agency_address)
    <tr>
      <td colspan="4" style="font-size:7.5pt;">{{ $agency_address }}</td>
    </tr>
    @endif
    {{-- Purpose of Travel – bordered option boxes matching reference --}}
    <tr>
      <td colspan="2" style="padding:2pt 3pt;font-size:7.5pt;vertical-align:middle;">
        <strong>Purpose of Travel:</strong>
        <table style="width:100%;border-collapse:collapse;margin-top:1pt;">
          <tr>
            @foreach(['Work'=>'work','Transit'=>'transit','Visit'=>'visit','Umrah'=>'umrah','Residence'=>'residence','Hajj'=>'hajj','Diplomacy'=>'diplomacy'] as $lbl => $val)
            <td style="border:1px solid #000;text-align:center;font-size:6.5pt;padding:2pt 3pt;{{ $tp===$val ? 'background:#1a1a1a;color:#fff;font-weight:bold;' : '' }}">{{ $lbl }}</td>
            @endforeach
          </tr>
        </table>
      </td>
      <td colspan="2" class="ar" style="padding:2pt 3pt;font-size:7.5pt;vertical-align:middle;">
        <strong>الغاية من السفر :</strong>
        <table style="width:100%;border-collapse:collapse;margin-top:1pt;">
          <tr>
            @foreach(['العمل'=>'work','عبور'=>'transit','زيارة'=>'visit','عمرة'=>'umrah','إقامة'=>'residence','حج'=>'hajj','دبلوماسي'=>'diplomacy'] as $lbl => $val)
            <td style="border:1px solid #000;text-align:center;font-size:6.5pt;padding:2pt 3pt;{{ $tp===$val ? 'background:#1a1a1a;color:#fff;font-weight:bold;' : '' }}">{{ $lbl }}</td>
            @endforeach
          </tr>
        </table>
      </td>
    </tr>
  </tbody>
</table>

{{-- PASSPORT / VISA SECTION --}}
<table class="bdr" style="margin-top:1pt;">
  <colgroup>
    <col style="width:22%">
    <col style="width:16%">
    <col style="width:22%">
    <col style="width:22%">
    <col style="width:18%">
  </colgroup>
  <tbody>
    {{-- Place of issue | Date of issue | Date of expiry | Passport No --}}
    <tr>
      <td style="font-size:7pt;" class="ar">محل الإصدار :</td>
      <td style="font-size:7pt;" class="ar">تاريخ الإصدار :</td>
      <td style="font-size:7pt;" class="ar">تاريخ انتهاء الصلاحية :</td>
      <td colspan="2" style="font-size:7pt;" class="ar">رقم الجواز :</td>
    </tr>
    <tr>
      <td><strong>Place of issue:</strong> {{ $passport_issue_place ?: '' }}</td>
      <td><strong>{{ $passport_issue_date ?: '' }}</strong></td>
      <td><strong>Date of expiry:</strong> {{ $passport_expiry_date ?: '' }}</td>
      <td colspan="2"><strong>Passport No.:</strong> {{ $passport_no ?: '' }}</td>
    </tr>
    {{-- Duration | Arrival | Departure --}}
    <tr>
      <td style="font-size:7pt;" class="ar">مدة الإقامة :</td>
      <td style="font-size:7pt;" class="ar">تاريخ الوصول :</td>
      <td style="font-size:7pt;" class="ar">تاريخ المغادرة :</td>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td><strong>Duration of stay in the kingdom:</strong> {{ $duration_stay_en ?: '' }}</td>
      <td><strong>Date of arrival:</strong> {{ $arrival_date ?: '' }}</td>
      <td><strong>Date of departure:</strong> {{ $departure_date ?: '' }}</td>
      <td colspan="2">&nbsp;</td>
    </tr>
    {{-- Mode of payment --}}
    <tr>
      <td style="font-size:7pt;" class="ar">تاريخ :</td>
      <td style="font-size:7pt;" class="ar">بنك / رقم :</td>
      <td style="font-size:7pt;" class="ar">تاريخ :</td>
      <td style="font-size:7pt;" class="ar">رقم / بنك :</td>
      <td style="font-size:7pt;" class="ar">طريقة الدفع :</td>
    </tr>
    <tr>
      <td style="font-size:7.5pt;">Date: ___________</td>
      <td style="font-size:7.5pt;">No.: ___________</td>
      <td style="font-size:7.5pt;">&#9744; Cash &nbsp; &#9744; Cheque</td>
      <td style="font-size:7.5pt;"><strong>{{ $payment_mode ?: 'Free' }}</strong></td>
      <td style="font-size:7.5pt;"><strong>Mode of payment:</strong></td>
    </tr>
    {{-- صلة --}}
    <tr>
      <td colspan="2" class="ar" style="font-size:7pt;">اسم المحرم :</td>
      <td colspan="3"><strong>Relationship:</strong> {{ $relationship ?: 'EMPLOYER AND EMPLOYEE' }}</td>
    </tr>
    {{-- Destination / Carrier --}}
    <tr>
      <td class="ar" style="font-size:7pt;">اسم الشركة الناقلة :</td>
      <td><strong>Carrier's:</strong> {{ $carrier ?: '' }}</td>
      <td class="ar" style="font-size:7pt;">جهة الوصول :</td>
      <td colspan="2"><strong>Destination:</strong> {{ $destination_city ?: ($work_city ?: '') }}</td>
    </tr>
  </tbody>
</table>

{{-- DEPENDENTS --}}
<table class="bdr" style="margin-top:1pt;font-size:7.5pt;">
  <tbody>
    <tr>
      <td colspan="4" style="font-size:7.5pt;">
        <strong>Dependents traveling in the same passport</strong> &nbsp;|&nbsp;
        <span class="ar" style="font-size:7pt;">إصاحبات تخص أفراد العائلة التنتقلين في نفس جواز السفر</span>
      </td>
    </tr>
    <tr style="background:#f0f0f0;">
      <td style="width:20%;text-align:center;"><span class="ar">نوع الصلة</span><br>Relationship</td>
      <td style="width:30%;text-align:center;"><span class="ar">تاريخ الميلاد</span><br>Date of Birth</td>
      <td style="width:15%;text-align:center;"><span class="ar">الجنس</span><br>Sex</td>
      <td style="width:35%;text-align:center;"><span class="ar">الاسم الكامل</span><br>Full Name</td>
    </tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr>
      <td colspan="2"><strong>CITY:</strong> {{ $work_city ?: '' }}, K.S.A</td>
      <td colspan="2"><strong>TEL:</strong> {{ $agency_phone ?: '' }}</td>
    </tr>
  </tbody>
</table>

{{-- NAME AND ADDRESS IN KINGDOM --}}
<table class="bdr" style="margin-top:1pt;font-size:7.5pt;">
  <tbody>
    <tr>
      <td style="width:50%;">Name and address of company or individual in the kingdom</td>
      <td style="width:50%;" class="ar" style="font-size:7pt;">اسم وعنوان الشركة أو اسم الشخص وعنوانه بالمملكة :</td>
    </tr>
  </tbody>
</table>

{{-- DECLARATION --}}
<table class="bdr" style="margin-top:1pt;font-size:7.5pt;">
  <tbody>
    <tr>
      <td>I the undersigned hereby that all the information I have provided are correct. I will abide by laws of the Kingdom of Saudi Arabia during the period of my residence in it.</td>
    </tr>
    <tr>
      <td class="ar" style="font-size:7pt;">أنا الموقع أدناه أقرر بأن كل المعلومات التي زودتها صحيحة وسأكون ملتزماً بقوانين المملكة العربية السعودية خلال فترة وجودي بها.</td>
    </tr>
  </tbody>
</table>

{{-- SIGNATURE ROW --}}
<table class="bdr" style="margin-top:1pt;font-size:7.5pt;">
  <tbody>
    <tr>
      <td style="width:33%;padding:2pt 4pt;">
        <strong>Date:</strong> ___________________<br>
        <strong>Signature:</strong> _______________<br>
        <strong>Name:</strong> {{ $full_name_en }}
      </td>
      <td style="width:33%;text-align:center;padding:2pt;font-size:7pt;">
        <strong>توقيع :</strong>
        <br><br>
        <strong>اسم :</strong> {{ $full_name_en }}
      </td>
      <td style="width:34%;padding:2pt 4pt;text-align:right;" class="ar">
        <span style="font-size:7pt;">التاريخ : ___________<br>التوقيع : ___________<br>الاسم : {{ $full_name_en }}</span>
      </td>
    </tr>
  </tbody>
</table>

{{-- FOR OFFICIAL USE ONLY --}}
<table class="bdr" style="margin-top:2pt;font-size:7.5pt;">
  <tbody>
    <tr style="background:#f0f0f0;">
      <td colspan="4" style="text-align:center;font-weight:bold;font-size:8pt;">
        For official use only &nbsp;|&nbsp; <span class="ar">للاستخدام الرسمي فقط</span>
      </td>
    </tr>
    <tr>
      <td style="width:25%;font-size:7pt;" class="ar">تاريخ :</td>
      <td style="width:25%;">Date: _______________</td>
      <td style="width:25%;font-size:7pt;" class="ar">رقم التأشيرة :</td>
      <td style="width:25%;"><strong>Visa No:</strong> {{ $visa_no ?: '' }}</td>
    </tr>
    <tr>
      <td style="font-size:7pt;" class="ar">زيارة / أعمال :</td>
      <td>Visit/Work for: _______________</td>
      <td style="font-size:7pt;" class="ar">رقم التفويض :</td>
      <td><strong>Authorization:</strong> {{ $musaned_no ?: ($wakala_no ?: '') }}</td>
    </tr>
    <tr>
      <td style="font-size:7pt;" class="ar">المبلغ المحصل :</td>
      <td>Fee Collected: _______________</td>
      <td style="font-size:7pt;" class="ar">نوعها :</td>
      <td>Type: _____ &nbsp; Duration: _____</td>
    </tr>
    <tr>
      <td colspan="2" style="font-size:7.5pt;">
        <span class="ar">رئيس القسم القنصلي :</span><br>
        Head of consular section
      </td>
      <td colspan="2" style="text-align:right;font-size:7.5pt;">
        <span class="ar">صاحب العمل رقم :</span><br>
        Checked by
      </td>
    </tr>
  </tbody>
</table>

{{-- BOTTOM BARCODE – symmetric 30/40/30 so barcode is at the TRUE page center --}}
<div style="border-top:1px solid #000;margin-top:3pt;"></div>
<table style="margin-top:0;width:100%;border-collapse:collapse;">
  <tr>
    <td style="width:30%;font-size:7pt;vertical-align:middle;padding:3pt 2pt;">
      <span class="ar" style="font-size:7pt;">مدقق صاحب العمل رقم صاحب العمل</span>
    </td>
    <td style="width:40%;text-align:center;vertical-align:middle;padding:3pt 2pt;">
      @if(!empty($bottomBarcodeSrc))
        <img src="{{ $bottomBarcodeSrc }}" style="width:52mm;height:9mm;display:block;margin:0 auto;">
      @endif
      <div style="text-align:center;font-size:7.5pt;font-weight:bold;letter-spacing:0.5pt;margin-top:2pt;">{{ $bottomBarcodeText ?? '' }}</div>
    </td>
    <td style="width:30%;padding:3pt 2pt;">&nbsp;</td>
  </tr>
</table>

</div>{{-- /.ksa-app --}}
