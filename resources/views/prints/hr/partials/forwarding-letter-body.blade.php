{{--
  SHARED Forwarding Letter body. Used by BOTH the single preview/PDF
  (prints.hr.forwarding-letter) and the Complete File (prints.hr.full-file,
  page 2) so the layout is identical everywhere. Fully self-contained with
  inline styles + .ksa-letter scoped wrapper — does not depend on host body
  font-size or any .dtbl class.
--}}
{{-- ── Scoped typography — match reference page 2 (ksa-application-reference-0002.jpg)
     The reference letter's Latin text is dense/semibold, not thin. Plain FreeSans
     (400) renders too light and mPDF has no FreeSans medium face (font-weight:500
     silently rounds to 400), so the body uses ksaroboto (Roboto-Medium — a real
     500-weight face, same family pages 3–4 use) for density, with freesans as the
     fallback. Scoped to .ksa-letter so pages 1/3/4 are unaffected. --}}
<style>
@if(empty($_pdf))
  /* BROWSER-ONLY @font-face: embed the exact TTFs mPDF renders with, so the
     on-screen preview and Ctrl+P print match the downloaded PDF glyph-for-glyph.
     mPDF ships these fonts internally, so emit them only when NOT rendering to
     PDF. Files live in public/fonts (copied from vendor/mpdf/mpdf/ttfonts). */
  @font-face { font-family: freesans;      font-weight: normal; font-style: normal; src: url('/fonts/FreeSans.ttf') format('truetype'); }
  @font-face { font-family: freesans;      font-weight: bold;   font-style: normal; src: url('/fonts/FreeSansBold.ttf') format('truetype'); }
  @font-face { font-family: ksaroboto;     font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Medium.ttf') format('truetype'); }
  @font-face { font-family: ksaroboto;     font-weight: bold;   font-style: normal; src: url('/fonts/Roboto-Bold.ttf') format('truetype'); }
  @font-face { font-family: 'DejaVu Sans'; font-weight: normal; font-style: normal; src: url('/fonts/DejaVuSans.ttf') format('truetype'); }
  @font-face { font-family: 'DejaVu Sans'; font-weight: bold;   font-style: normal; src: url('/fonts/DejaVuSans-Bold.ttf') format('truetype'); }
  /* Arabic Naskh: match the PDF (mPDF renders Arabic in XB Riyaz). */
  @font-face { font-family: xbriyaz; font-weight: normal; font-style: normal; src: url('/fonts/XBRiyaz.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz; font-weight: bold;   font-style: normal; src: url('/fonts/XBRiyaz-Bold.ttf') format('truetype'); }
@endif
  /* Declare the body font DIRECTLY on every element type — mPDF table cells do
     NOT inherit font-family from an ancestor (they silently fall back to the host
     body font), so the container alone is not enough. */
  .ksa-letter,
  .ksa-letter table, .ksa-letter td, .ksa-letter th,
  .ksa-letter div, .ksa-letter span, .ksa-letter p, .ksa-letter strong { font-family: ksaroboto, freesans, sans-serif; }
  /* Arabic keeps DejaVu Sans (full Arabic coverage in browser + mPDF);
     mPDF's autoLangToFont substitutes the Arabic font for RTL text anyway. */
  .ksa-letter .ar { font-family: xbriyaz, 'DejaVu Sans', sans-serif; }
</style>
{{-- Geometry matched to docs/references/CHECK.pdf page 2: text column runs from
     41.5pt to 7pt inside the 10mm print margin (x 69.9 → 560.5pt), Roboto Medium
     11.2pt body on a 16.6pt line pitch, #212529 text. --}}
<div class="ksa-letter" style="margin:0 7pt 0 41.5pt;font-size:11.2pt;line-height:1.48;font-weight:normal;color:#212529;">

{{-- Top spacer: 62.9mm puts "To," at the reference's y≈208pt (≈73.5mm from the
     physical page top, with the 10mm print margin). The SAME height is used with
     or without a logo — previously the no-logo branch was only 30mm, which
     started the letter ~90pt too high. The band is the reference's
     pre-printed-letterhead / optional agency-logo space. --}}
@if(!empty($agency_show_logo) && !empty($agency_logo))
  <div style="height:62.9mm;text-align:center;">
    <img src="{{ empty($_pdf) ? asset('storage/'.$agency_logo) : public_path('storage/'.$agency_logo) }}"
         style="max-height:34mm;max-width:80mm;" alt="">
  </div>
@else
  <div style="height:62.9mm;"></div>
@endif

{{-- To address --}}
<p style="margin:0;">To,</p>
<p style="margin:0;">The Chief Of Consular Section,</p>
<p style="margin:0;">The Royal Embassy Kingdom Of Saudi Arabia,</p>
<p style="margin:0 0 27.5pt 0;">Gulshan, Dhaka, Bangladesh.</p>

{{-- Reference prints "Excellency," in the same Medium weight as the body. --}}
<p style="margin:0;">Excellency,</p>

<p style="margin:0 0 26.6pt 0;">
With Due Respect we are Submitting One Passport for work Visa with all Necessary Documents and Particulars mentioned as below, knowing all instruction and regulation of the consulate section.
</p>

{{-- Details table — bottom rules only (#dee2e6), 23.3pt rows; labels Medium
     9pt, values Bold 9pt ("Date:" captions inside values stay Medium). --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:23.6pt;font-size:9pt;line-height:1.47;table-layout:fixed;">
  <colgroup><col style="width:50.9%"><col style="width:49.1%"></colgroup>
  <tbody>
    <tr>
      <td style="width:50.9%;border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-size:9pt;">NAME OF COMPANY:</td>
      <td style="width:49.1%;border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-weight:bold;font-size:9pt;">
        @if(!empty($sponsor_name_ar))<span class="ar" style="font-weight:bold;">{{ $sponsor_name_ar }}</span>@else{{ $sponsor_name ?: $agency_name }}@endif
      </td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-size:9pt;">VISA NUMBER &amp; DATE:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-weight:bold;font-size:9pt;">{{ $visa_no ?: '—' }}@if($visa_date) &nbsp;&nbsp; <span style="font-weight:normal;">Date:</span> {{ $visa_date_hijri }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-size:9pt;">FULL NAME OF THE EMPLOYEE:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-weight:bold;font-size:9pt;">{{ $full_name_en_upper }}</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-size:9pt;">PASSPORT NO. WITH ISSUE DATE:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-weight:bold;font-size:9pt;">{{ $passport_no ?: '—' }}@if($passport_issue_date) &nbsp;&nbsp; <span style="font-weight:normal;">Date:</span> {{ $passport_issue_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-size:9pt;">PROFESSION:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-weight:bold;font-size:9pt;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
    </tr>
    <tr>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-size:9pt;">RELIGION:</td>
      <td style="border-bottom:0.7pt solid #dee2e6;padding:5pt 4.5pt;font-weight:bold;font-size:9pt;">{{ $religion ?: '—' }}</td>
    </tr>
  </tbody>
</table>

<p style="margin:0 0 17.2pt 0;">
I do hereby confirm and declare that the region stated in the Visa form and forwarding letter is fully correct. I also undertake with my own responsibility to cancel the Visa and to stop functioning with my office, If the statement is found incorrect.
</p>

<p style="margin:0;">
We therefore, Request your Excellency to kindly issue work Visa out of - 01 - Visas and oblige thereby.
</p>

{{-- Signature — kept together so it is never split across pages. Reference:
     a 111.8pt rule with "Your Faithfully" (Medium 9pt) centred directly under
     it, ~57pt below the last paragraph. --}}
<div class="ksa-signature" style="margin-top:53.6pt;page-break-inside:avoid;break-inside:avoid;">
  <div style="border-top:0.7pt solid #212529;width:111.8pt;padding-top:0;font-size:9pt;line-height:1.2;font-weight:normal;text-align:center;">
    Your Faithfully
  </div>
</div>

</div>{{-- /.ksa-letter --}}
