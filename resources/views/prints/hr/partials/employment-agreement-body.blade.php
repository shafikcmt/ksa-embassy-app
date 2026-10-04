{{--
  SHARED Employment Agreement body. Used by BOTH the single preview/PDF
  (prints.hr.employment-agreement) and the Complete File (prints.hr.full-file,
  page 3) so the layout is identical everywhere. Fully self-contained with
  inline styles + .ksa-letter scoped wrapper.
--}}
<style>
.ksa-letter, .ksa-letter table, .ksa-letter td, .ksa-letter th,
.ksa-letter div, .ksa-letter span, .ksa-letter p, .ksa-letter strong { font-family: ksaroboto, freesans, sans-serif; }
/* Arabic (e.g. NAME OF COMPANY value) uses XB Riyaz — the same Naskh mPDF
   renders in the PDF — so browser preview matches the download. */
.ksa-letter .ar { font-family: xbriyaz, 'DejaVu Sans', sans-serif; }
@if(empty($_pdf))
  @font-face { font-family: ksaroboto; font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Medium.ttf') format('truetype'); }
  @font-face { font-family: ksaroboto; font-weight: bold;   font-style: normal; src: url('/fonts/Roboto-Bold.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz;   font-weight: normal; font-style: normal; src: url('/fonts/XBRiyaz.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz;   font-weight: bold;   font-style: normal; src: url('/fonts/XBRiyaz-Bold.ttf') format('truetype'); }
@endif
</style>
{{-- Geometry matched to docs/references/CHECK.pdf page 3: content runs 7pt
     inside the 10mm print margin (x 35.4 → 560.5pt); 9pt Roboto, #212529. --}}
<div class="ksa-letter" style="margin:0 7pt;font-size:9pt;line-height:1.47;color:#212529;">

{{-- Top spacer: 24.4mm puts the title's top at the reference's y≈97.5pt
     (≈34.4mm from the physical page top, with the 10mm print margin). --}}
<div style="height:24.4mm;"></div>

{{-- Title — Roboto Bold 16.7pt, underlined. --}}
<div style="text-align:center;line-height:1.2;margin:0;">
  <span style="font-size:16.7pt;font-weight:bold;text-decoration:underline;">EMPLOYMENT AGREEMENT</span>
</div>

{{-- Party info — bottom rules only (#dee2e6), 23.3pt rows; labels Medium,
     values Bold ("Date:" caption stays Medium). Value column starts at 51.2%. --}}
<table style="width:100%;border-collapse:collapse;margin:26.1pt 0 28.4pt 0;font-size:9pt;table-layout:fixed;">
  <colgroup><col style="width:51.2%"><col style="width:48.8%"></colgroup>
  <tbody>
    <tr>
      <td style="width:51.2%;border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">NAME OF COMPANY:</td>
      <td style="width:48.8%;border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;font-weight:bold;">
        @if(!empty($sponsor_name_ar))<span class="ar" style="font-weight:bold;">{{ $sponsor_name_ar }}</span>@else{{ $sponsor_name ?: $agency_name }}@endif
      </td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">HEREBY APPOINTED:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;font-weight:bold;">{{ $full_name_en_upper }}</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">PASSPORT NO WITH ISSUE DATE:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;font-weight:bold;">{{ $passport_no ?: '—' }}@if($passport_issue_date) &nbsp;&nbsp; <span style="font-weight:normal;">Date:</span> {{ $passport_issue_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">PASSPORT HOLDER:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;font-weight:bold;">{{ $nationality }}</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">PROFESSION:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;font-weight:bold;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
    </tr>
  </tbody>
</table>

{{-- Terms heading — Roboto Bold 14.9pt, underlined. --}}
<div style="text-align:center;line-height:1.2;margin:0 0 3pt 0;">
  <span style="font-size:14.9pt;font-weight:bold;text-decoration:underline;">UNDER THE FOLLOWING TERMS AND CONDITIONS:</span>
</div>

{{-- Terms table — reference columns 5% (number, left-aligned) / 60% / 35%.
     table-layout:fixed pins the columns so item 10 wraps after "…DEAD BODY &"
     with "SERVICE BENEFIT…" on line 2, as in the reference. --}}
<table style="width:100%;font-size:9pt;border-collapse:collapse;table-layout:fixed;">
  <colgroup>
    <col style="width:5%">
    <col style="width:60%">
    <col style="width:35%">
  </colgroup>
  <tbody>
    {{-- Zero-height sizing row: mPDF ignores <colgroup>/table-layout:fixed widths
         and content-sizes columns instead; this invisible row pins the grid. --}}
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:5%;"></td>
      <td style="border:0;padding:0;width:60%;"></td>
      <td style="border:0;padding:0;width:35%;"></td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">1</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">MONTHLY SALARY:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">{{ $salary ?: '800/= SR' }}</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">2</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">FOOD AND ACCOMMODATION:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">200/= SR</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">3</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">AIR PASSAGE:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">BORNE BY THE EMPLOYER</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">4</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">DUTY HOUR:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">8 HOURS DAILY</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">5</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">HOLIDAY:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">6</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">LEAVE:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">7</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">OVERTIME &amp; OTHER BENEFIT:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">8</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">MEDICAL FACILITIES:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">FREE</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">9</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">PERIOD OF CONTRACT:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;">{{ $contract_period ?: ($duration_stay_en ?: 'TWO/ONE YEARS') }}</td>
    </tr>
    {{-- The sole 2-line row: the reference top-aligns the number and value to
         line 1 of the label. --}}
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;vertical-align:top;">10</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;vertical-align:top;">REPATRIATION ARRANGEMENT INCLUDING RETURN OF DEAD BODY &amp; SERVICE BENEFIT TO THE LEGAL HEIR OF THE EMPLOYEE:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:4.65pt 4.5pt;font-size:9pt;vertical-align:top;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
  </tbody>
</table>

{{-- Pre-signature spacer: places the signature rules at the reference's
     y≈761pt. KEPT OUTSIDE the break-inside:avoid wrapper below: bundling a tall
     spacer INSIDE the avoid block made the browser-print engine push the whole
     block onto its own (5th) page. --}}
<div style="height:206pt;"></div>
{{-- Signatures — First Party rule/label at the LEFT edge, Second Party at the
     RIGHT; inline-block makes each rule exactly the label width. Medium 9pt. --}}
<div class="ksa-signature" style="page-break-inside:avoid;break-inside:avoid;">
  <table style="width:100%;border-collapse:collapse;font-size:9pt;">
    <tr>
      <td style="width:50%;text-align:left;padding:0;vertical-align:bottom;font-size:9pt;line-height:1.2;">
        <span style="display:inline-block;border-top:0.7pt solid #212529;padding-top:0;">SIGNATURE OF FIRST PARTY</span>
      </td>
      <td style="width:50%;text-align:right;padding:0;vertical-align:bottom;font-size:9pt;line-height:1.2;">
        <span style="display:inline-block;border-top:0.7pt solid #212529;padding-top:0;">SIGNATURE OF SECOND PARTY</span>
      </td>
    </tr>
  </table>
</div>

</div>{{-- /.ksa-letter --}}
