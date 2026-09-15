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
{{-- width:92% + margin:0 auto matches PAGE 1's .ksa-app column so all four
     Complete-File pages share the SAME left/right margin (≈17.6mm) in the PDF.
     (Removed the old `padding:0 2.5mm`, which made this page inset differently
     from pages 2 and 4.) --}}
<div class="ksa-letter" style="width:92%;margin:0 auto;font-size:13pt;line-height:1.6;color:#000;">

{{-- Top spacer: page 3's reference (ksa-application-reference-0003.jpg) has NO
     letterhead gap — the title sits ~35.7mm from the physical page top. mPDF
     reserves a 10mm margin, so 24mm here puts the title at ~36mm to match.
     (Page 2 keeps its larger letterhead gap; the reference pages differ.) --}}
<div style="height:24mm;"></div>

{{-- Title — normal word/letter spacing to match reference (no extra tracking). --}}
<div style="text-align:center;margin-bottom:16pt;">
  <span style="font-size:14pt;font-weight:bold;text-decoration:underline;">EMPLOYMENT AGREEMENT</span>
</div>

{{-- Party info — bottom border only --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:14pt;font-size:10pt;">
  <tbody>
    <tr>
      <td style="width:50%;border-bottom:1px solid #e0e0e0;padding:5pt 4pt;">NAME OF COMPANY:</td>
      <td style="width:50%;border-bottom:1px solid #e0e0e0;padding:5pt 4pt;font-weight:bold;font-size:10.5pt;">
        @if(!empty($sponsor_name_ar))<span class="ar" style="font-weight:bold;">{{ $sponsor_name_ar }}</span>@else{{ $sponsor_name ?: $agency_name }}@endif
      </td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;">HEREBY APPOINTED:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;font-weight:bold;font-size:10.5pt;">{{ $full_name_en_upper }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;">PASSPORT NO WITH ISSUE DATE:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;font-weight:bold;font-size:10.5pt;">{{ $passport_no ?: '—' }}@if($passport_issue_date) &nbsp; Date: {{ $passport_issue_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;">PASSPORT HOLDER:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;font-weight:bold;font-size:10.5pt;">{{ $nationality }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;">PROFESSION:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:5pt 4pt;font-weight:bold;font-size:10.5pt;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
    </tr>
  </tbody>
</table>

{{-- Terms heading --}}
<div style="text-align:center;margin-bottom:10pt;">
  <span style="font-size:12pt;font-weight:bold;text-decoration:underline;">UNDER THE FOLLOWING TERMS AND CONDITIONS:</span>
</div>

{{-- Terms table --}}
{{-- table-layout:fixed pins the columns to the colgroup so (1) the item-10 label
     wraps inside a fixed 52% column — breaking after "…DEAD BODY &" with
     "SERVICE BENEFIT…" on line 2, matching ksa-application-reference-0003.jpg —
     and (2) the value column starts at ~60% for EVERY row like the reference.
     Without fixed layout, item-10's long text stretched column 2 (auto layout),
     pushing all values rightward to ~70% and letting "SERVICE" stay on line 1. --}}
<table style="width:100%;margin-bottom:18pt;font-size:10pt;border-collapse:collapse;table-layout:fixed;">
  <colgroup>
    <col style="width:8%">
    <col style="width:55%">
    <col style="width:37%">
  </colgroup>
  <tbody>
    {{-- Zero-height sizing row: mPDF ignores <colgroup>/table-layout:fixed widths
         and content-sizes columns instead (item-10's long text otherwise stretched
         column 2 and pushed the values rightward). This invisible row pins all three
         columns to the 8/55/37 grid — the same technique application-body uses. --}}
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:8%;"></td>
      <td style="border:0;padding:0;width:55%;"></td>
      <td style="border:0;padding:0;width:37%;"></td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">1</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">MONTHLY SALARY:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">{{ $salary ?: '800/= SR' }}</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">2</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">FOOD AND ACCOMMODATION:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">200/= SR</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">3</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">AIR PASSAGE:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">BORNE BY THE EMPLOYER</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">4</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">DUTY HOUR:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">8 HOURS DAILY</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">5</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">HOLIDAY:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">6</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">LEAVE:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">7</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">OVERTIME &amp; OTHER BENEFIT:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">8</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">MEDICAL FACILITIES:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">FREE</td>
    </tr>
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;">9</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">PERIOD OF CONTRACT:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;">{{ $contract_period ?: ($duration_stay_en ?: 'TWO/ONE YEARS') }}</td>
    </tr>
    {{-- vertical-align:top on this row only: it is the sole 2-line row, and the
         reference top-aligns its value ("AS PER SAUDI LABOUR LAWS") to line 1 of the
         label. Rows 1-9 are single-line, so their (default) alignment is visually
         identical either way — no change needed there. --}}
    <tr>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;text-align:center;vertical-align:top;">10</td>
      {{-- No font-size override: inherit the table's 10pt (same size as rows 1-9,
           matching the reference). At 10pt in the fixed 55% column the line breaks
           after "…DEAD BODY &" with "SERVICE BENEFIT…" on line 2, exactly like
           docs/images/ksa-application-reference-0003.jpg. --}}
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;vertical-align:top;">REPATRIATION ARRANGEMENT INCLUDING RETURN OF DEAD BODY &amp; SERVICE BENEFIT TO THE LEGAL HEIR OF THE EMPLOYEE:</td>
      <td style="border-bottom:1px solid #e0e0e0;padding:4pt 6pt;vertical-align:top;">AS PER SAUDI LABOUR LAWS</td>
    </tr>
  </tbody>
</table>

{{-- Pre-signature spacer pushes the signature row to the page bottom (≈268mm),
     matching the reference. Sized to absorb the smaller 24mm top spacer so the
     signature stays bottom-anchored while the title sits near the top.
     KEPT OUTSIDE the break-inside:avoid wrapper below: bundling this tall spacer
     INSIDE the avoid block made the browser-print engine jump the whole 90mm+
     block (spacer included) onto its own page — a stray 5th page with only the
     signature line, pushing the checklist to page 5. With the spacer outside,
     print keeps the spacer butted against the page bottom and only the short
     signature row stays "together". mPDF is single-page here, so its output is
     unchanged. --}}
<div style="height:80mm;"></div>
{{-- Signatures — kept together so the row is never split across pages --}}
<div class="ksa-signature" style="page-break-inside:avoid;break-inside:avoid;">
  {{-- Reference (page 3): First Party rule/label anchored to the LEFT column edge,
       Second Party anchored to the RIGHT. inline-block makes the top rule exactly
       the label width (not the full cell), matching the reference. Regular weight
       (ksaroboto Medium) — reference labels are not heavy-bold. --}}
  <table style="width:100%;border-collapse:collapse;font-size:11pt;">
    <tr>
      <td style="width:50%;text-align:left;padding:0;vertical-align:bottom;">
        <span style="display:inline-block;border-top:1px solid #000;padding-top:4pt;">SIGNATURE OF FIRST PARTY</span>
      </td>
      <td style="width:50%;text-align:right;padding:0;vertical-align:bottom;">
        <span style="display:inline-block;border-top:1px solid #000;padding-top:4pt;">SIGNATURE OF SECOND PARTY</span>
      </td>
    </tr>
  </table>
</div>

</div>{{-- /.ksa-letter --}}
