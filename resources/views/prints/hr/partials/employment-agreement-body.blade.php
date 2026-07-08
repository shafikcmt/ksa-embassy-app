{{--
  SHARED Employment Agreement body. Used by BOTH the single preview/PDF
  (prints.hr.employment-agreement) and the Complete File (prints.hr.full-file,
  page 3) so the layout is identical everywhere. Fully self-contained with
  inline styles + .ksa-letter scoped wrapper.
--}}
<style>
.ksa-letter, .ksa-letter table, .ksa-letter td, .ksa-letter th,
.ksa-letter div, .ksa-letter span, .ksa-letter p, .ksa-letter strong { font-family: ksaroboto, freesans, sans-serif; }
@if(empty($_pdf))
  @font-face { font-family: ksaroboto; font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Medium.ttf') format('truetype'); }
  @font-face { font-family: ksaroboto; font-weight: bold;   font-style: normal; src: url('/fonts/Roboto-Bold.ttf') format('truetype'); }
@endif
</style>
<div class="ksa-letter" style="font-size:13pt;line-height:1.6;color:#000;">

{{-- Top spacer: content starts 2in / 50.8mm from the physical page top
     (mPDF reserves a 10mm margin, so 10 + 40.8 = 50.8mm). Same value on
     pages 2–4 so single-page and Complete-File PDFs match. --}}
<div style="height:40.8mm;"></div>

{{-- Title --}}
<div style="text-align:center;margin-bottom:16pt;">
  <span style="font-size:14pt;font-weight:bold;text-decoration:underline;letter-spacing:1pt;">EMPLOYMENT AGREEMENT</span>
</div>

{{-- Party info — bottom border only --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:14pt;font-size:10pt;">
  <tbody>
    <tr>
      <td style="width:50%;border-bottom:1px solid #000;padding:5pt 4pt;">NAME OF COMPANY:</td>
      <td style="width:50%;border-bottom:1px solid #000;padding:5pt 4pt;font-weight:bold;">
        @if(!empty($sponsor_name_ar))<span class="ar" style="font-weight:bold;">{{ $sponsor_name_ar }}</span>@else{{ $sponsor_name ?: $agency_name }}@endif
      </td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">HEREBY APPOINTED:</td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;font-weight:bold;">{{ $full_name_en_upper }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">PASSPORT NO WITH ISSUE DATE:</td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;font-weight:bold;">{{ $passport_no ?: '—' }}@if($passport_issue_date) &nbsp; Date: {{ $passport_issue_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">PASSPORT HOLDER:</td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;font-weight:bold;">{{ $nationality }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;">PROFESSION:</td>
      <td style="border-bottom:1px solid #000;padding:5pt 4pt;font-weight:bold;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
    </tr>
  </tbody>
</table>

{{-- Terms heading --}}
<div style="text-align:center;margin-bottom:10pt;">
  <span style="font-size:12pt;font-weight:bold;text-decoration:underline;">UNDER THE FOLLOWING TERMS AND CONDITIONS :</span>
</div>

{{-- Terms table --}}
<table style="width:100%;margin-bottom:18pt;font-size:10pt;border-collapse:collapse;">
  <colgroup>
    <col style="width:8%">
    <col style="width:56%">
    <col style="width:36%">
  </colgroup>
  <tbody>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">1</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">MONTHLY SALARY:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">{{ $salary ?: '800/= SR' }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">2</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">FOOD AND ACCOMMODATION:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">200/= SR</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">3</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">AIR PASSAGE:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">BORNE BY THE EMPLOYER</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">4</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">DUTY HOUR:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">8 HOURS DAILY</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">5</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">HOLIDAY:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">6</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">LEAVE:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">7</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">OVERTIME &amp; OTHER BENEFIT:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">8</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">MEDICAL FACILITIES:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">FREE</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">9</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">PERIOD OF CONTRACT:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">{{ $contract_period ?: ($duration_stay_en ?: 'TWO/ONE YEARS') }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;text-align:center;">10</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;font-size:9pt;">REPATRIATION ARRANGEMENT INCLUDING RETURN OF DEAD BODY &amp; SERVICE BENEFIT TO THE LEGAL HEIR OF THE EMPLOYEE:</td>
      <td style="border-bottom:1px solid #000;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
  </tbody>
</table>

{{-- Signatures — kept together so the row is never split across pages --}}
<div class="ksa-signature" style="page-break-inside:avoid;break-inside:avoid;">
  <div style="height:45mm;"></div>
  <table style="width:100%;border-collapse:collapse;font-size:11pt;">
    <tr>
      <td style="width:50%;text-align:center;padding:0 6pt;vertical-align:bottom;">
        <div style="border-top:1px solid #000;padding-top:4pt;">
          <strong>SIGNATURE OF FIRST PARTY</strong>
        </div>
      </td>
      <td style="width:50%;text-align:center;padding:0 6pt;vertical-align:bottom;">
        <div style="border-top:1px solid #000;padding-top:4pt;">
          <strong>SIGNATURE OF SECOND PARTY</strong>
        </div>
      </td>
    </tr>
  </table>
</div>

</div>{{-- /.ksa-letter --}}
