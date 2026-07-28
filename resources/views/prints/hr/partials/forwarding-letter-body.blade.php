{{--
  SHARED Forwarding Letter body. Used by BOTH the single preview/PDF
  (prints.hr.forwarding-letter) and the Complete File (prints.hr.full-file,
  page 2) so the layout is identical everywhere. Fully self-contained with
  inline styles + .ksa-letter scoped wrapper — does not depend on host body
  font-size or any .dtbl class.
--}}
{{-- ── Scoped typography — match reference page 2 (ksa-application-reference-0002.jpg)
     The reference letter's Latin text is an Arial/Helvetica style, the same
     family page 1 uses (FreeSans, an Arial clone) — NOT the wider/rounder
     DejaVu Sans the host body defaults to. Scoped to .ksa-letter so pages 1/3/4
     are unaffected. --}}
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
  /* Declare freesans DIRECTLY on every element type — mPDF table cells do NOT
     inherit font-family from an ancestor (they silently fall back to the host
     body font), so the container alone is not enough. */
  .ksa-letter,
  .ksa-letter table, .ksa-letter td, .ksa-letter th,
  .ksa-letter div, .ksa-letter span, .ksa-letter p, .ksa-letter strong { font-family: freesans, sans-serif; }
  /* Arabic keeps DejaVu Sans (full Arabic coverage in browser + mPDF);
     mPDF's autoLangToFont substitutes the Arabic font for RTL text anyway. */
  .ksa-letter .ar { font-family: xbriyaz, 'DejaVu Sans', sans-serif; }
</style>
{{-- width:92% + margin:0 auto matches PAGE 1's .ksa-app column so all four
     Complete-File pages share the SAME left/right margin (≈17.6mm) in the PDF. --}}
<div class="ksa-letter" style="width:92%;margin:0 auto;font-size:13pt;line-height:1.6;font-weight:normal;">

{{-- Top spacer: 62mm — measured to match reference page 2, where "To," sits
     ~74mm from the physical page top (mPDF's ~12mm top offset + 62mm = ~74mm).
     This blank band is the reference's pre-printed-letterhead / optional
     agency-logo space. --}}
@if(!empty($agency_show_logo) && !empty($agency_logo))
  <div style="height:62mm;text-align:center;">
    <img src="{{ empty($_pdf) ? asset('storage/'.$agency_logo) : public_path('storage/'.$agency_logo) }}"
         style="max-height:34mm;max-width:80mm;" alt="">
  </div>
@else
  <div style="height:30mm;"></div>
@endif

{{-- To address --}}
<p style="margin:0;font-size:13pt;">To,</p>
<p style="margin:0;font-size:13pt;">The Chief Of Consular Section,</p>
<p style="margin:0;font-size:13pt;">The Royal Embassy Kingdom Of Saudi Arabia,</p>
<p style="margin:0 0 18pt 0;font-size:13pt;">Gulshan, Dhaka, Bangladesh.</p>

<p style="margin:0;font-size:13pt;"><strong>Excellency,</strong></p>

<p style="margin:0 0 14pt 0;font-size:13pt;">
With Due Respect we are Submitting One Passport for work Visa with all Necessary Documents and Particulars mentioned as below, knowing all instruction and regulation of the consulate section.
</p>

{{-- Details table — bottom border only --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:14pt;font-size:10pt;">
  <tbody>
    <tr>
      <td style="width:50%;border-bottom:0.5pt solid #000;padding:5pt 4pt;"><strong>NAME OF COMPANY:</strong></td>
      <td style="width:50%;border-bottom:0.5pt solid #000;padding:5pt 4pt;font-weight:bold;">
        @if(!empty($sponsor_name_ar))<span class="ar" style="font-weight:bold;">{{ $sponsor_name_ar }}</span>@else{{ $sponsor_name ?: $agency_name }}@endif
      </td>
    </tr>
    <tr>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;"><strong>VISA NUMBER &amp; DATE:</strong></td>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;font-weight:bold;">{{ $visa_no ?: '—' }}@if($visa_date) &nbsp; Date: {{ $visa_date_hijri }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;"><strong>FULL NAME OF THE EMPLOYEE:</strong></td>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;font-weight:bold;">{{ $full_name_en_upper }}</td>
    </tr>
    <tr>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;"><strong>PASSPORT NO. WITH ISSUE DATE:</strong></td>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;font-weight:bold;">{{ $passport_no ?: '—' }}@if($passport_issue_date) &nbsp; Date: {{ $passport_issue_date }}@endif</td>
    </tr>
    <tr>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;"><strong>PROFESSION:</strong></td>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;font-weight:bold;">{{ $profession_en ?: ($occupation ?: '—') }}</td>
    </tr>
    <tr>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;"><strong>RELIGION:</strong></td>
      <td style="border-bottom:0.5pt solid #000;padding:5pt 4pt;font-weight:bold;">{{ $religion ?: '—' }}</td>
    </tr>
  </tbody>
</table>

<p style="margin:0 0 12pt 0;font-size:13pt;">
I do hereby confirm and declare that the region stated in the Visa form and forwarding letter is fully correct. I also undertake with my own responsibility to cancel the Visa and to stop functioning with my office, If the statement is found incorrect.
</p>

<p style="margin:0 0 24pt 0;font-size:13pt;">
We therefore, Request your Excellency to kindly issue work Visa out of - 01 - Visas and oblige thereby.
</p>

{{-- Signature — kept together so it is never split across pages. Reference shows
     "Your Faithfully" in REGULAR weight (~11pt) under a short rule, not bold. --}}
<div class="ksa-signature" style="margin-top:90pt;page-break-inside:avoid;break-inside:avoid;">
  <div style="border-top:1px solid #000;width:80pt;padding-top:4pt;font-size:10pt;font-weight:normal;padding-left:14pt;">
    Your Faithfully
  </div>
</div>

</div>{{-- /.ksa-letter --}}
