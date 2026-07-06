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
  // Uppercase helper — ONLY for fields the reference shows in UPPERCASE
  // (name, mother's name, place of birth, nationalities, passport no.).
  $U = fn($x) => ($x === null || $x === '') ? '' : mb_strtoupper((string) $x, 'UTF-8');
  // Title-case helper for fields the reference shows in mixed case
  // (Sex "Male", Marital Status "Unmarried", Religion "Muslim", Sect).
  // Normalises DB casing so e.g. "female" → "Female" regardless of storage.
  $T = fn($x) => ($x === null || $x === '') ? '' : mb_convert_case((string) $x, MB_CASE_TITLE, 'UTF-8');
  $tp = strtolower($travel_purpose ?: 'work');
  $fullNameDisplay = $U($full_name_en) . ($father_name ? ' S/O. ' . $U($father_name) : '');
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
@if(empty($_pdf))
  /* BROWSER-ONLY: embed the exact TTFs mPDF renders with, so the on-screen
     preview and Ctrl+P print match the downloaded PDF glyph-for-glyph. The
     surrounding blade if-guard emits this block only when NOT rendering to PDF
     — mPDF already ships these fonts internally, so it must not see url()-based
     font-face rules (it would try to re-load/embed them and can error).
     Files live in public/fonts (copied from vendor/mpdf/mpdf/ttfonts). */
  @font-face { font-family: freesans;      font-weight: normal; font-style: normal; src: url('/fonts/FreeSans.ttf') format('truetype'); }
  @font-face { font-family: freesans;      font-weight: bold;   font-style: normal; src: url('/fonts/FreeSansBold.ttf') format('truetype'); }
  @font-face { font-family: dejavusans;    font-weight: normal; font-style: normal; src: url('/fonts/DejaVuSans.ttf') format('truetype'); }
  @font-face { font-family: dejavusans;    font-weight: bold;   font-style: normal; src: url('/fonts/DejaVuSans-Bold.ttf') format('truetype'); }
  /* .ar / body reference the spaced name "DejaVu Sans" — alias it to the same files. */
  @font-face { font-family: 'DejaVu Sans'; font-weight: normal; font-style: normal; src: url('/fonts/DejaVuSans.ttf') format('truetype'); }
  @font-face { font-family: 'DejaVu Sans'; font-weight: bold;   font-style: normal; src: url('/fonts/DejaVuSans-Bold.ttf') format('truetype'); }
@endif
  /* FreeSans (an Arial/Helvetica clone) has a much heavier Bold than DejaVu
     Sans, matching the reference form's thick Latin text.
     CRITICAL mPDF quirk: table cells do NOT inherit font-family from an
     ancestor (<div class="ksa-app">) — they silently fall back to the host
     body font (DejaVu Sans), which is why earlier output looked too light.
     So freesans must be declared DIRECTLY on every element type (table/td/
     th/div/span), not just on the container. Arabic glyphs auto-substitute
     to mPDF's Arabic font via autoLangToFont regardless of this. */
  .ksa-app,
  .ksa-app table, .ksa-app td, .ksa-app th,
  .ksa-app div, .ksa-app span, .ksa-app p { font-family: freesans, sans-serif; }
  .ksa-app { line-height: 1.15; color: #000; font-weight: bold; }
  .ksa-app table { width: 100%; border-collapse: collapse; }
  /* Bordered grid: 8pt bold Latin + taller rows (more vertical padding) to
     match the reference form's spacing and heavier, clearer text. */
  .ksa-app .bdr td, .ksa-app .bdr th { border: 0.9pt solid #000; padding: 2.6pt 5pt; font-size: 8pt; font-weight: bold; vertical-align: middle; }
  .ksa-app .lbl { font-weight: bold; text-align: left; white-space: nowrap; }
  .ksa-app .val { font-weight: bold; text-align: center; }
  /* Full Name / Father / Mother values — the two headline rows. The
     reference uses a clean Helvetica/Arial-style Bold (crisp, even strokes,
     the same weight as the labels), NOT a heavy black weight. FreeSans is
     the Arial/Helvetica clone, so plain FreeSans Bold matches it. No
     text-shadow "stroke" here — that smeared the glyph edges and looked
     ugly; clean Bold reads sharper and closer to the reference. */
  /* Full Name / Mother's Name VALUES: sized 2px (=1.5pt) larger than the 8pt
     used by every other value cell — so 9.5pt — to headline them per the
     reference. Weight stays the `bold` keyword: in this mPDF, numeric weights
     (700/800/900) make DejaVu/FreeSans fall back to REGULAR (thin), so `bold`
     is the only correct heavy weight. line-height:1 keeps the taller text from
     inflating the row more than necessary. */
  .ksa-app .nmval { font-size: 9.5pt; font-weight: bold; line-height: 1; }
  /* Personal-Info table (Section 1): use FreeSans — the Arial/Helvetica clone
     that matches the reference form's narrow, compact Latin glyphs (labels AND
     values). This is the same family the rest of page 1 uses; keeping it here
     makes the label column read compact like the reference (an earlier DejaVu
     Sans experiment was wider/rounder and did not match the reference shape).
     WARNING: keep the `bold` keyword — numeric weights (700/800/900) make
     FreeSans fall back to REGULAR (thin) in this mPDF, so `bold` is the only
     correct heavy weight for .val/.lbl/.nmval. */
  .ksa-app .pi td, .ksa-app .pi th, .ksa-app .pi span { font-family: freesans, sans-serif; }
  /* Arabic: reference form's Arabic labels/content are NOT bold (lighter,
     regular weight) — clearly thinner than the heavy Latin bold. Force
     normal weight with !important because the grid rule (.bdr td{bold})
     has higher specificity than .ar and would otherwise win, leaving the
     Arabic heavy. Arabic VALUES that ARE bold in the reference are wrapped
     in <strong> and restored to bold below. */
  .ksa-app .ar  { direction: rtl; text-align: right; font-weight: normal !important; font-size: 7.8pt; white-space: nowrap; }
  .ksa-app .ar strong { font-weight: bold !important; }
  .ksa-app .inner td { border: 0 !important; padding: 0; font-weight: bold; }
  /* Passport No. column — the reference emphasises this field with a bolder,
     darker vertical divider on its left edge (Date of expiry | Passport No.).
     Scoped to .ppno cells only, so no other cell/border is affected. */
  .ksa-app .bdr td.ppno { border-left: 1.6pt solid #000; }
  /* Duration / Payment / Mahram / Destination block: the reference renders these
     rows as open text lines — outer frame + horizontal row rules only, with NO
     internal vertical dividers. Remove vertical cell borders, then restore the
     outer left/right frame on the first/last cell of each row. Scoped to .hrows
     so the passport grid above and dependents grid below stay untouched. */
  .ksa-app .hrows td { border-left: 0; border-right: 0; }
  .ksa-app .hrows td:first-child { border-left: 0.9pt solid #000; }
  .ksa-app .hrows td:last-child  { border-right: 0.9pt solid #000; }
  /* Signature row — reference draws NO box around it (clean, borderless). */
  .ksa-app .sig td { border: 0; padding: 2.4pt 4pt; font-size: 7.6pt; vertical-align: middle; }
  /* "For official use only" — reference has NO vertical grid: just a dashed
     separator line on top and thin solid horizontal rules between rows. */
  .ksa-app .offc { border-top: 1px dashed #000; }
  .ksa-app .offc td { border: 0; border-bottom: 1px solid #000; padding: 2.4pt 4pt; font-size: 7.6pt; vertical-align: middle; }
  .ksa-app .offc .hdr td { border-bottom: 1px solid #000; padding-top: 3pt; }
  /* Purpose-of-Travel option boxes — bordered table cells, AR over EN.
     Reference packs 7 options into the 50% middle block with "Residence"/
     "Diplomacy" on one line; at 7pt they wrapped once the block narrowed to 50%,
     so match the reference's compact sizing (6pt) + tighter padding. NOTE: in
     this mPDF the font-size must sit on the CELL — a size on the nested .pe/.pa
     <span> alone is ignored — so it is set on td.box here. */
  .ksa-app .pt td.box { border: 1px solid #000 !important; text-align: center; padding: 1pt 1pt; line-height: 1.05; font-size: 6pt; }
  .ksa-app .pt td.box .pa { font-size: 5.5pt; font-weight: normal; }
  .ksa-app .pt td.box .pe { font-size: 6pt; font-weight: bold; }
  .ksa-app .pt td.sel { background: #404040; }
  .ksa-app .pt td.sel .pa, .ksa-app .pt td.sel .pe { color: #fff; }
</style>
<div class="ksa-app">

{{-- Reference leaves a little breathing space above the header block. --}}
<div style="height:2mm;"></div>

{{-- ── HEADER: photo (left) · barcode (center) · embassy (right) ─────────────
     Photo and MOFA columns are near-equal width so the barcode sits centered
     on the page, and EMBASSY/CONSULAR are pushed down to line up with the
     barcode number / New Application, exactly like the reference. --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:8pt;">
  <tr>
    <td style="width:30%;vertical-align:top;padding:0;">
      {{-- Passport-size photo box (~35mm × 41mm), thin black border, top-left --}}
      <table style="width:100pt;border-collapse:collapse;"><tr>
        <td style="width:100pt;height:111pt;border:1px solid #000;text-align:center;vertical-align:middle;font-size:8pt;color:#555;padding:2pt;">
          Photo
        </td>
      </tr></table>
    </td>
    <td style="width:36%;text-align:center;vertical-align:top;padding:6pt 4pt 4pt 4pt;">
      {{-- Nested table: row-1 fixed height reliably pushes "New Application" down
           (mPDF ignores margin/padding between sibling divs inside a cell). --}}
      <table style="width:100%;border-collapse:collapse;">
        <tr><td style="height:54pt;text-align:center;vertical-align:top;border:0;padding:0;">
          @if(!empty($topBarcodeSrc))
            <img src="{{ $topBarcodeSrc }}" style="width:46mm;height:12mm;display:block;margin:0 auto;">
          @elseif(!empty($topBarcodeText))
            <div style="width:46mm;height:12mm;border:1px dashed #aaa;margin:0 auto;text-align:center;line-height:12mm;font-size:7pt;">{{ $topBarcodeText }}</div>
          @endif
          <div style="text-align:center;font-weight:bold;font-size:10pt;margin-top:1pt;letter-spacing:0.5pt;">{{ $topBarcodeText ?? '' }}</div>
        </td></tr>
        <tr><td style="text-align:center;vertical-align:top;border:0;padding:0;">
          <div style="font-size:12pt;font-weight:bold;text-align:center;">New Application</div>
        </td></tr>
      </table>
    </td>
    <td style="width:34%;text-align:right;vertical-align:top;padding:2pt 0 2pt 4pt;">
      @if(!empty($application_no))
      <div style="font-size:19pt;font-weight:bold;letter-spacing:0.5pt;">{{ $application_no }}</div>
      @endif
      <div style="font-size:11.5pt;font-weight:bold;margin-top:36pt;white-space:nowrap;">EMBASSY OF SAUDI ARABIA</div>
      <div style="font-size:11pt;font-weight:bold;margin-top:4pt;white-space:nowrap;">CONSULAR SECTION</div>
    </td>
  </tr>
</table>

{{-- ── MAIN IDENTITY TABLE (6-col grid: label | value | arabic ×2) ─────────── --}}
{{-- .pi = Personal-Info scope: Section-1 Latin text uses the heavier DejaVu Sans
     Bold (see .pi rule below); other .bdr tables stay FreeSans. --}}
<table class="bdr pi" style="table-layout:fixed;">
  {{-- Reference (ksa-complete-file-reference-0001.pdf) measures the identity
       grid as SIX EQUAL columns — grid lines fall at 0/16.7/33.3/50/66.7/83.3/100%.
       Label, value and Arabic columns are the same width (16.67% each); the label
       column is NOT wider. Keep all six equal so every row lines up. --}}
  <colgroup>
    <col style="width:16.66%"><col style="width:16.67%"><col style="width:16.67%">
    <col style="width:16.66%"><col style="width:16.67%"><col style="width:16.67%">
  </colgroup>
  <tbody>
    {{-- Zero-height sizing row: mPDF 8.x IGNORES <colgroup> widths and, with a
         colspan cell in the first visible row, cannot derive an even grid — it
         falls back to content sizing, which is what made the label column bulge
         wide. This invisible 6-cell row is the real first row, so mPDF pins all
         six columns to the equal 16.67% grid the reference uses. Borderless /
         zero-height so it never shows. --}}
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:16.66%;"></td><td style="border:0;padding:0;width:16.67%;"></td><td style="border:0;padding:0;width:16.67%;"></td>
      <td style="border:0;padding:0;width:16.66%;"></td><td style="border:0;padding:0;width:16.67%;"></td><td style="border:0;padding:0;width:16.67%;"></td>
    </tr>
    {{-- Full Name (value — incl. "S/O. <father>" — rendered bold) --}}
    <tr>
      <td class="lbl">Full Name:</td>
      <td colspan="4" class="val"><span class="nmval">{{ $fullNameDisplay }}</span></td>
      <td class="ar">اسم الكامل :</td>
    </tr>
    {{-- Mother's Name (value rendered bold + uppercase) --}}
    <tr>
      <td class="lbl">Mother's Name:</td>
      <td colspan="4" class="val"><span class="nmval">{{ $U($mother_name) }}</span></td>
      <td class="ar">اسم الأم :</td>
    </tr>
    {{-- Date of Birth | Place of Birth --}}
    <tr>
      <td class="lbl">Date of Birth:</td>
      <td class="val">{{ $date_of_birth }}</td>
      <td class="ar">تاريخ الولادة :</td>
      <td class="lbl">Place of Birth:</td>
      <td class="val">{{ $U($place_of_birth) }}</td>
      <td class="ar">محل الولادة :</td>
    </tr>
    {{-- Previous | Present Nationality --}}
    <tr>
      <td class="lbl">Previous Nationality:</td>
      <td class="val">{{ $U($previous_nationality) }}</td>
      <td class="ar">الجنسية السابقة :</td>
      <td class="lbl">Present Nationality:</td>
      <td class="val">{{ $U($nationality) }}</td>
      <td class="ar">الجنسية الحالية :</td>
    </tr>
    {{-- Sex | Marital Status --}}
    <tr>
      <td class="lbl">Sex:</td>
      <td class="val">{{ $T($gender) }}</td>
      <td class="ar">الجنس :</td>
      <td class="lbl">Marital Status:</td>
      <td class="val">{{ $T($marital_status) }}</td>
      <td class="ar">الحالة الاجتماعية :</td>
    </tr>
    {{-- Sect | Religion --}}
    <tr>
      <td class="lbl">Sect:</td>
      <td class="val">{{ $T($sect) }}</td>
      <td class="ar">المذهب :</td>
      <td class="lbl">Religion:</td>
      <td class="val">{{ $T($religion) }}</td>
      <td class="ar">الديانة :</td>
    </tr>
  </tbody>
</table>

{{-- ── PROFESSION + ADDRESS + PURPOSE (independent grid — its OWN colgroup) ───
     A SEPARATE table so its wide label column ("Business address & phone No.")
     and full-width value/arabic cells do NOT distort the fixed 6-col identity
     grid above (and vice-versa). Column widths here are deliberately kept
     independent of the identity table — this mirrors the reference, where the
     address/profession rows use their own column widths, not the identity
     table's. Auto layout (no table-layout:fixed) lets the long labels size
     to content without wrapping. --}}
<table class="bdr pi" style="margin-top:0;table-layout:fixed;">
  {{-- 6-col grid tuned so the ADDRESS rows resolve to 25% / 50% / 25%
       (left label · middle value · right Arabic label — left==right, matching
       the reference). Address rows map as c1 | colspan3(c2+c3+c4) | colspan2(c5+c6),
       so c1=25, c2+c3+c4=50, c5+c6=25. Purpose row uses c1 | colspan4 | c6, and
       the profession rows use colspan5|c6 / colspan6 — all still valid on this grid.
       table-layout:fixed + the zero-height sizing row below make mPDF honour these
       widths (auto layout ignored the colgroup and let the label column bulge). --}}
  <colgroup>
    <col style="width:25%"><col style="width:17%"><col style="width:16%">
    <col style="width:17%"><col style="width:12.5%"><col style="width:12.5%">
  </colgroup>
  <tbody>
    {{-- Zero-height sizing row: pins all six columns to the colgroup grid under
         table-layout:fixed (the first visible row starts with a colspan cell, so
         without this mPDF cannot derive the grid). Borderless / zero-height. --}}
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:17%;"></td><td style="border:0;padding:0;width:16%;"></td>
      <td style="border:0;padding:0;width:17%;"></td><td style="border:0;padding:0;width:12.5%;"></td><td style="border:0;padding:0;width:12.5%;"></td>
    </tr>
    {{-- Profession block — arabic labels + arabic profession value.
         Reference has NO internal vertical grid across this row: the whole
         left area is one open cell (borderless inner table for positioning),
         and only the right arabic-label column (المهنة) keeps its divider. --}}
    {{-- border-right/left:none on this pair removes ONLY the shared internal
         divider between the open left area and the المهنة label — the reference
         renders this Arabic row as one continuous line (no vertical divider
         before المهنة). Outer table left/top/bottom/right borders are kept. --}}
    <tr>
      <td colspan="5" style="padding:2.6pt 5pt;border-right:none;">
        <table class="inner" dir="ltr" style="width:100%;">
          <tr>
            <td style="width:28%;">&nbsp;</td>
            <td class="ar" style="width:22%;text-align:center;">مصدره :</td>
            <td class="ar" style="width:20%;text-align:center;">المؤهل العلمي :</td>
            <td class="ar" style="width:30%;text-align:center;"><strong>{{ $profession_ar ?: '' }}</strong></td>
          </tr>
        </table>
      </td>
      <td class="ar" style="border-left:none;">المهنة :</td>
    </tr>
    {{-- Profession block — english labels + english profession value.
         Reference: full-width open row, no internal vertical dividers, value
         (profession) extends to the right edge. --}}
    <tr>
      <td colspan="6" style="padding:2.6pt 5pt;">
        <table class="inner" dir="ltr" style="width:100%;">
          <tr>
            <td class="lbl" style="width:28%;">Place of Issue:</td>
            <td class="lbl" style="width:22%;">Qualification:@if($qualification_en) {{ $qualification_en }}@endif</td>
            <td class="lbl" style="width:20%;">Profession:</td>
            <td class="val" style="width:30%;text-align:left;">{{ $profession_en ?: ($occupation ?: '') }}</td>
          </tr>
        </table>
      </td>
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
         Reference proportions are 25 / 50 / 25 (label · boxes · arabic). On this
         6-col grid that is c1 | colspan3(c2+c3+c4)=50% | colspan2(c5+c6)=25% — the
         same colspan pattern the address rows use, so it aligns with them and the
         arabic heading is no longer squeezed. Selected purpose is grey-filled. --}}
    <tr>
      <td class="lbl" style="white-space:nowrap;">Purpose of Travel:</td>
      <td colspan="3" style="padding:2pt 4pt;vertical-align:middle;">
        <table class="pt" style="width:100%;border-collapse:collapse;"><tr>
          @foreach($purposeOpts as $opt)
          <td class="box{{ $tp === $opt['val'] ? ' sel' : '' }}" style="width:{{ number_format(100/count($purposeOpts),2) }}%;">
            <span class="pa">{{ $opt['ar'] }}</span><br><span class="pe">{{ $opt['en'] }}</span>
          </td>
          @endforeach
        </tr></table>
      </td>
      <td colspan="2" class="ar" style="white-space:nowrap;">الغاية من السفر :</td>
    </tr>
  </tbody>
</table>

{{-- ── PASSPORT INFO (own 4-EQUAL-column grid — reference = 25/25/25/25) ───────
     Split into a dedicated table so the label + value rows sit on an exact
     4-equal-column grid (table-layout:fixed + zero-height sizing row make mPDF
     honour the colgroup). The duration/payment/destination rows below keep their
     own independent 5-column table untouched — those rows genuinely need 5 cells
     and their Arabic labels would overflow a forced 4-col grid. --}}
<table class="bdr" style="margin-top:0;margin-bottom:0;table-layout:fixed;">
  <colgroup>
    <col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%">
  </colgroup>
  <tbody>
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td>
    </tr>
    {{-- Passport field headers: english + arabic label in one cell --}}
    <tr>
      <td><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Place of issue:</td><td class="ar">محل الإصدار :</td></tr></table></td>
      <td><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Date of issue:</td><td class="ar">تاريخ الإصدار :</td></tr></table></td>
      <td><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Date of expiry:</td><td class="ar">تاريخ انتهاء الصلاحية :</td></tr></table></td>
      <td class="ppno"><table class="inner" style="width:100%"><tr><td class="lbl" style="text-align:left;">Passport No.:</td><td class="ar">رقم الجواز :</td></tr></table></td>
    </tr>
    {{-- Passport field values (centered) --}}
    <tr>
      <td class="val">{{ $U($passport_issue_place) }}</td>
      <td class="val">{{ $passport_issue_date ?: '' }}</td>
      <td class="val">{{ $passport_expiry_date ?: '' }}</td>
      <td class="val ppno">{{ $U($passport_no) }}</td>
    </tr>
  </tbody>
</table>

{{-- ── VISA / DURATION / PAYMENT / DESTINATION (independent 5-column table) ────
     .hrows = horizontal-rules-only: reference shows these rows with no internal
     vertical dividers (open text lines), just the outer frame + row separators. --}}
<table class="bdr hrows" style="margin-top:0;">
  <colgroup>
    <col style="width:22%"><col style="width:16%"><col style="width:22%">
    <col style="width:22%"><col style="width:18%">
  </colgroup>
  <tbody>
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
    {{-- Mode of payment — english (Mode of payment on the LEFT).
         Matches the reference exactly: "Free Cash Cheque No. | Date | No. | Date"
         with NO underscore/placeholder lines after Date/No. --}}
    <tr>
      <td class="lbl">Mode of payment:</td>
      <td style="font-size:7pt;"><strong>{{ $payment_mode ?: 'Free' }}</strong> Cash  Cheque No.</td>
      <td class="lbl" style="font-weight:normal;">Date</td>
      <td class="lbl" style="font-weight:normal;">No.</td>
      <td class="lbl" style="font-weight:normal;">Date</td>
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
    {{-- Trailing empty rows — reference shows a taller dependents block. --}}
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
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
      <td style="width:60%;text-align:center;">I the undersigned hereby that all the information I have provided are correct. I will abide by laws of the kingdom during the period of my residence in it.</td>
      <td style="width:40%;">أنا الموقع أدناه أقر بأن كل المعلومات التي زودتها صحيحة وسأكون ملتزماً بقوانين المملكة العربية السعودية خلال فترة وجودي بها.</td>
    </tr>
  </tbody>
</table>

{{-- ── SIGNATURE (single horizontal row — borderless, like the reference) ──── --}}
<table class="sig" style="margin-top:1pt;font-size:7.5pt;">
  <colgroup>
    <col style="width:14%"><col style="width:8%"><col style="width:18%">
    <col style="width:8%"><col style="width:44%"><col style="width:8%">
  </colgroup>
  <tbody>
    <tr>
      <td class="lbl">Date:</td>
      <td class="ar">التاريخ :</td>
      <td class="lbl">Signature:</td>
      <td class="ar">التوقيع :</td>
      <td class="lbl"><strong>Name:</strong> {{ $U($full_name_en) }}</td>
      <td class="ar">الاسم :</td>
    </tr>
  </tbody>
</table>

{{-- ── FOR OFFICIAL USE ONLY (borderless grid: dashed top + horizontal rules) --}}
<table class="offc" style="margin-top:1pt;font-size:7.5pt;">
  <colgroup>
    <col style="width:12%"><col style="width:16%"><col style="width:12%">
    <col style="width:14%"><col style="width:16%"><col style="width:30%">
  </colgroup>
  <tbody>
    <tr class="hdr">
      <td colspan="3" style="font-weight:bold;font-size:8pt;text-decoration:underline;">For official use only</td>
      <td colspan="3" class="ar" style="font-weight:bold;font-size:8pt;text-decoration:underline;">للاستعمال الرسمي فقط</td>
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
      <td colspan="4" class="val">@if(!empty($sponsor_name_ar))<span class="ar"><strong>{{ $sponsor_name_ar }}</strong></span>@else{{ $sponsor_name ?: '' }}@endif</td>
      <td class="ar">لزيارة :</td>
    </tr>
    <tr>
      <td class="lbl">Date:</td>
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
