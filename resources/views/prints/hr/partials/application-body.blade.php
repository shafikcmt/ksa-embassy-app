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
    ['en' => 'Visit',      'ar' => 'يزور',        'val' => 'visit'],
    ['en' => 'Umrah',      'ar' => 'العمرة',      'val' => 'umrah'],
    ['en' => 'Residence',  'ar' => 'إقامة',       'val' => 'residence'],
    ['en' => 'Hajj',       'ar' => 'الحج',        'val' => 'hajj'],
    ['en' => 'Diplomacy',  'ar' => 'الدبلوماسية', 'val' => 'diplomacy'],
  ];
@endphp

{{-- ── Page-1 scoped typography/layout — matched to docs/references/CHECK.pdf ──
     Defined here (inside the shared partial) so single preview, single PDF,
     complete-file preview and complete-file PDF all render identically. Every
     selector is under .ksa-app, so pages 2–4 are unaffected. Placed after the
     host <head> CSS, so these rules win on equal specificity.
     Reference type scale: labels Roboto Medium 9pt, values Roboto Bold 9pt,
     headline values (names, dates, passport/visa numbers) Roboto Black 9.7pt,
     all in #212529; grid lines 0.7pt #212529. All coordinates quoted below are
     CHECK.pdf points (A4 = 595 × 842pt, 10mm = 28.35pt print margin). --}}
<style>
@if(empty($_pdf))
  /* BROWSER-ONLY: embed the exact TTFs mPDF renders with, so the on-screen
     preview and Ctrl+P print match the downloaded PDF glyph-for-glyph. mPDF
     loads these from public/fonts via PdfGeneratorService's fontdata, so it must
     not see url()-based font-face rules. */
  @font-face { font-family: ksaroboto;      font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Medium.ttf') format('truetype'); }
  @font-face { font-family: ksaroboto;      font-weight: bold;   font-style: normal; src: url('/fonts/Roboto-Bold.ttf') format('truetype'); }
  @font-face { font-family: ksarobotoblack; font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Black.ttf') format('truetype'); }
  @font-face { font-family: ksarobotoblack; font-weight: bold;   font-style: normal; src: url('/fonts/Roboto-Black.ttf') format('truetype'); }
  @font-face { font-family: 'DejaVu Sans'; font-weight: normal; font-style: normal; src: url('/fonts/DejaVuSans.ttf') format('truetype'); }
  @font-face { font-family: 'DejaVu Sans'; font-weight: bold;   font-style: normal; src: url('/fonts/DejaVuSans-Bold.ttf') format('truetype'); }
  /* Arabic Naskh: XB Riyaz — the font mPDF renders Arabic with. */
  @font-face { font-family: xbriyaz; font-weight: normal; font-style: normal; src: url('/fonts/XBRiyaz.ttf') format('truetype'); }
  @font-face { font-family: xbriyaz; font-weight: bold;   font-style: normal; src: url('/fonts/XBRiyaz-Bold.ttf') format('truetype'); }
  /* Browser print only: page 1 is a fixed-height flex column whose ONLY
     shrinkable item is the empty .gap-band, so a long name/address eats that
     band first instead of pushing the footer barcode onto a 2nd sheet. mPDF
     never sees this block (it fits those cases on its own). */
  @media print {
    .ksa-app { display: flex; flex-direction: column; height: 276.5mm; }
    .ksa-app > * { flex: none; }
    .ksa-app > .gap-band { flex: 0 1 28.3pt; min-height: 0; }
  }
@endif
  /* CRITICAL mPDF quirk: table cells do NOT inherit font-family from an
     ancestor — they fall back to the host body font (DejaVu Sans). So the family
     is declared DIRECTLY on every element type. Arabic glyphs auto-substitute to
     mPDF's Arabic font via autoLangToFont regardless of this.
     ksaroboto = Roboto Medium (normal) + Roboto Bold (bold) in browser AND mPDF.
     WARNING: keep the `bold`/`normal` keywords — numeric weights (500/700/900)
     fall back to the regular face in this mPDF. Black is its own family. */
  .ksa-app,
  .ksa-app table, .ksa-app td, .ksa-app th,
  .ksa-app div, .ksa-app span, .ksa-app p { font-family: ksaroboto, sans-serif; }
  .ksa-app { line-height: 1.2; color: #212529; font-weight: normal; }
  .ksa-app table { width: 100%; border-collapse: collapse; }
  /* Bordered grid: 9pt Medium, 0.7pt rules; 1.5pt vertical padding gives the
     reference's ~14.5–15pt row pitch, 7.5pt side padding its label inset. */
  .ksa-app .bdr td, .ksa-app .bdr th { border: 0.7pt solid #212529; padding: 1.5pt 7.5pt; font-size: 9pt; font-weight: normal; vertical-align: middle; }
  /* Seam de-duplication: where two SEPARATE stacked tables meet, drop the upper
     table's bottom border so the seam stays a single 0.7pt line (not doubled). */
  .ksa-app .seam-merge td { border-bottom: 0 !important; }
  .ksa-app .lbl { font-weight: normal; text-align: left; white-space: nowrap; }
  /* td.val / td.big out-rank (and follow) the grid's `.bdr td` weight rule. */
  .ksa-app .val, .ksa-app td.val { font-weight: bold; text-align: center; }
  /* Roboto Black headline values (Full Name, DOB, passport dates/no., official
     values). Separate family so mPDF (no numeric weights) resolves it too. */
  .ksa-app .blk { font-family: ksarobotoblack, ksaroboto, sans-serif; font-size: 9.7pt; }
  /* 9.7pt Bold values (Mother's Name, Profession, business name/email). */
  .ksa-app .big, .ksa-app td.big { font-size: 9.7pt; font-weight: bold; }
  /* Arabic labels are regular weight (lighter than the Latin), right-aligned.
     !important beats the grid rule's weight; Arabic VALUES that ARE bold in the
     reference are wrapped in <strong> and restored below. */
  .ksa-app .ar  { direction: rtl; text-align: right; font-family: xbriyaz, 'DejaVu Sans', sans-serif; font-weight: normal !important; font-size: 8.5pt; white-space: nowrap; }
  .ksa-app .ar strong { font-weight: bold !important; }
  /* Arabic inside spans (purpose boxes, underlined official heading) must not
     pick up the Roboto `span` rule above — Roboto has no Arabic glyphs, so the
     browser would fall back to Arial. */
  .ksa-app .ar span, .ksa-app .pt .pa { font-family: xbriyaz, 'DejaVu Sans', sans-serif; }
  .ksa-app .inner td { border: 0 !important; padding: 0; }
  /* Open-row blocks (Duration / Mahram / Destination): outer frame + horizontal
     rules only, NO internal vertical dividers. */
  .ksa-app .hrows td { border-left: 0; border-right: 0; }
  .ksa-app .hrows td:first-child { border-left: 0.7pt solid #212529; }
  .ksa-app .hrows td:last-child  { border-right: 0.7pt solid #212529; }
  /* Merged field groups: an Arabic-label row + its English row form ONE group
     with no divider between them — drop both sides of that seam. */
  .ksa-app .hrows tr.mrow-ar td { border-bottom: 0 !important; padding-bottom: 0.6pt; }
  .ksa-app .hrows tr.mrow-en td { border-top: 0 !important; padding-top: 0.6pt; }
  /* Purpose-of-Travel option boxes: separate 0.7pt boxes (AR over EN, 6.7pt
     Medium) with ~3pt gaps, as in the reference. In this mPDF the font-size must
     sit on the CELL — a size on the nested span alone is ignored. */
  .ksa-app table.pt { border-collapse: separate; border-spacing: 3pt 0; }
  .ksa-app .pt td.box { border: 0.7pt solid #212529 !important; text-align: center; padding: 3.2pt 0; line-height: 1.25; font-size: 6.7pt; font-weight: normal; white-space: nowrap; }
  .ksa-app .pt td.box .pa { font-size: 6.7pt; font-weight: normal; }
  .ksa-app .pt td.box .pe { font-size: 6.7pt; font-weight: normal; }
  /* print-color-adjust:exact forces the browser to PRINT the dark fill even when
     "Background graphics" is off. mPDF ignores these properties. */
  .ksa-app .pt td.sel { background: #444444 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }
  .ksa-app .pt td.sel .pa, .ksa-app .pt td.sel .pe { color: #fff !important; }
  /* Signature + "For official use only": borderless rows (no vertical grid), a
     dashed separator above and full-width 0.7pt rules between official rows.
     Each official row is its own table so each keeps the reference's own column
     positions. */
  .ksa-app .sig td { border: 0; padding: 1.2pt 0; font-size: 9pt; vertical-align: top; }
  .ksa-app .offc { border-top: 0.75pt dashed #212529; }
  .ksa-app .offr { border-bottom: 0.7pt solid #212529; }
  .ksa-app .offr td, .ksa-app .offc td { border: 0; padding: 1.4pt 0; font-size: 9pt; vertical-align: middle; }
  .ksa-app .offr .ar, .ksa-app .sig .ar { padding-right: 1pt; }
</style>
<div class="ksa-app" style="width:100%;margin:0 auto;">

{{-- ── HEADER: photo (left) · barcode (center) · embassy (right) ─────────────
     Reference: photo box 98.7×115.3pt inset 7.3pt from the margin; barcode bars
     44mm×9.7mm centred, 14pt below the margin, number directly under it;
     "New Application" (Black 9.7pt) at y≈112.5; application no. (Black 20.4pt)
     and EMBASSY/CONSULAR (Bold 11.7pt) right-aligned 6.6pt inside the margin. --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:5.8pt;">
  <tr>
    <td style="width:32%;vertical-align:top;padding:7.3pt 0 0 7.2pt;">
      <table style="width:98.7pt;border-collapse:collapse;"><tr>
        <td style="width:98.7pt;height:115.3pt;border:0.7pt solid #212529;text-align:center;vertical-align:middle;font-size:9pt;color:#212529;padding:0;">
          Photo
        </td>
      </tr></table>
    </td>
    <td style="width:36%;text-align:center;vertical-align:top;padding:13.9pt 0 0 0;">
      {{-- Nested table: row-1 fixed height reliably places "New Application"
           (mPDF ignores margin/padding between sibling divs inside a cell). --}}
      <table style="width:100%;border-collapse:collapse;">
        <tr><td style="height:70.2pt;text-align:center;vertical-align:top;border:0;padding:0;">
          @if(!empty($topBarcodeSrc))
            <img src="{{ $topBarcodeSrc }}" style="width:44mm;height:9.7mm;display:block;margin:0 auto;">
          @elseif(!empty($topBarcodeText))
            <div style="width:44mm;height:9.7mm;border:1px dashed #aaa;margin:0 auto;text-align:center;line-height:9.7mm;font-size:7pt;">{{ $topBarcodeText }}</div>
          @endif
          <div style="text-align:center;font-weight:bold;font-size:9.7pt;color:#000;">{{ $topBarcodeText ?? '' }}</div>
        </td></tr>
        <tr><td style="text-align:center;vertical-align:top;border:0;padding:0;">
          <div class="blk" style="text-align:center;">New Application</div>
        </td></tr>
      </table>
    </td>
    <td style="width:32%;text-align:right;vertical-align:top;padding:6.7pt 6.6pt 0 0;">
      {{-- Nested table (same reason as the barcode column): row heights/padding
           place EMBASSY / CONSULAR identically in browser print and mPDF. --}}
      <table style="width:100%;border-collapse:collapse;">
        <tr><td style="height:43.6pt;text-align:right;vertical-align:top;border:0;padding:0;">
          <div class="blk" style="font-size:20.4pt;line-height:1.2;text-align:right;">{{ $application_no ?: "\u{00A0}" }}</div>
        </td></tr>
        <tr><td style="text-align:right;border:0;padding:0;font-size:11.7pt;font-weight:bold;white-space:nowrap;">EMBASSY OF SAUDI ARABIA</td></tr>
        <tr><td style="text-align:right;border:0;padding:3.5pt 0 0 0;font-size:11.7pt;font-weight:bold;white-space:nowrap;">CONSULAR SECTION</td></tr>
      </table>
    </td>
  </tr>
</table>

{{-- ── MAIN IDENTITY TABLE (6 equal columns: label | value | arabic ×2) ────── --}}
<table class="bdr pi" style="table-layout:fixed;">
  <colgroup>
    <col style="width:16.66%"><col style="width:16.67%"><col style="width:16.67%">
    <col style="width:16.66%"><col style="width:16.67%"><col style="width:16.67%">
  </colgroup>
  <tbody>
    {{-- Zero-height sizing row: mPDF 8.x IGNORES <colgroup> widths; this
         invisible 6-cell first row pins the equal 16.67% grid. --}}
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:16.66%;"></td><td style="border:0;padding:0;width:16.67%;"></td><td style="border:0;padding:0;width:16.67%;"></td>
      <td style="border:0;padding:0;width:16.66%;"></td><td style="border:0;padding:0;width:16.67%;"></td><td style="border:0;padding:0;width:16.67%;"></td>
    </tr>
    {{-- Full Name (incl. "S/O. <father>") — Roboto Black 9.7pt --}}
    <tr>
      <td class="lbl">Full Name:</td>
      <td colspan="4" class="val"><span class="blk">{{ $fullNameDisplay }}</span></td>
      <td class="ar">اسم الكامل :</td>
    </tr>
    {{-- Mother's Name — Roboto Bold 9.7pt (not Black, per the reference) --}}
    <tr>
      <td class="lbl">Mother's Name:</td>
      <td colspan="4" class="val"><span class="big">{{ $U($mother_name) }}</span></td>
      <td class="ar">اسم الأم :</td>
    </tr>
    <tr>
      <td class="lbl">Date of Birth:</td>
      <td class="val"><span class="blk">{{ $date_of_birth }}</span></td>
      <td class="ar">تاريخ الولادة :</td>
      <td class="lbl">Place of Birth:</td>
      <td class="val">{{ $U($place_of_birth) }}</td>
      <td class="ar">محل الولادة :</td>
    </tr>
    {{-- The nationality labels are the longest identity labels; the reference
         shrinks them (8.1 / 8.2pt) so they fit the 16.67% column. --}}
    <tr>
      <td class="lbl" style="font-size:8.1pt;">Previous Nationality:</td>
      <td class="val">{{ $U($previous_nationality) }}</td>
      <td class="ar">الجنسية السابقة :</td>
      <td class="lbl" style="font-size:8.2pt;">Present Nationality:</td>
      <td class="val">{{ $U($nationality) }}</td>
      <td class="ar">الجنسية الحالية :</td>
    </tr>
    <tr>
      <td class="lbl">Sex:</td>
      <td class="val">{{ $T($gender) }}</td>
      <td class="ar">الجنس :</td>
      <td class="lbl">Marital Status:</td>
      <td class="val">{{ $T($marital_status) }}</td>
      <td class="ar">الحالة الاجتماعية :</td>
    </tr>
    <tr class="seam-merge">
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
     Address rows resolve to 25% / 50% / 25% (label · value · Arabic label), as
     c1 | colspan3 | colspan2. table-layout:fixed + the zero-height sizing row
     make mPDF honour these widths. --}}
<table class="bdr pi" style="margin-top:0;table-layout:fixed;">
  <colgroup>
    <col style="width:25%"><col style="width:17%"><col style="width:16%">
    <col style="width:17%"><col style="width:12.5%"><col style="width:12.5%">
  </colgroup>
  <tbody>
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:17%;"></td><td style="border:0;padding:0;width:16%;"></td>
      <td style="border:0;padding:0;width:17%;"></td><td style="border:0;padding:0;width:12.5%;"></td><td style="border:0;padding:0;width:12.5%;"></td>
    </tr>
    {{-- Profession — Arabic labels + Arabic profession value (bold 11pt). One
         continuous open row: no vertical divider before المهنة. --}}
    <tr>
      <td colspan="5" style="padding:2.2pt 7.5pt;border-right:none;line-height:1;">
        <table class="inner" dir="ltr" style="width:100%;">
          <tr>
            <td class="ar" style="width:26.4%;padding-right:6pt;">مصدره :</td>
            <td class="ar" style="width:26.4%;padding-right:6pt;">المؤهل العلمي :</td>
            <td class="ar" style="width:47.2%;text-align:center;font-size:11pt;line-height:1;"><strong>{{ $profession_ar ?: '' }}</strong></td>
          </tr>
        </table>
      </td>
      <td class="ar" style="border-left:none;">المهنة :</td>
    </tr>
    {{-- Profession — English labels (Medium) + English profession (Bold 9.7pt).
         Column starts follow the reference: 36 / 175 / 291 / 410pt. --}}
    <tr>
      <td colspan="6">
        <table class="inner" dir="ltr" style="width:100%;">
          <tr>
            <td class="lbl" style="width:26.4%;">Place of Issue:</td>
            <td class="lbl" style="width:22.1%;">Qualification:@if($qualification_en) {{ $qualification_en }}@endif</td>
            <td class="lbl" style="width:22.8%;">Profession:</td>
            <td class="val big" style="width:28.7%;text-align:left;">{{ $profession_en ?: ($occupation ?: '') }}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class="lbl">Home address &amp; phone No.:</td>
      <td colspan="3" class="val">{{ $home_address ?: ($phone ?: '') }}</td>
      <td colspan="2" class="ar">عنوان المنزل ورقم التلفون :</td>
    </tr>
    {{-- Business address — label shrunk to 8.6pt to fit the 25% column (as in
         the reference); agency name/RL and email are Bold 9.7pt. --}}
    <tr>
      <td class="lbl" style="font-size:8.6pt;vertical-align:top;">Business address &amp; phone No.:</td>
      <td colspan="3" class="val big" style="line-height:1.5;padding-top:0;padding-bottom:0;">
        @if($business_address_en){{-- stored value already includes RL; don't append it again --}}
          {{ $business_address_en }}@if($agency_email)<br>{{ $agency_email }}@endif
        @else
          {{ $agency_name }}@if($agency_rl) &nbsp; RL: {{ $agency_rl }}@endif @if($agency_email)<br>{{ $agency_email }}@endif
        @endif
      </td>
      <td colspan="2" class="ar" style="vertical-align:top;font-size:8pt;padding-left:2pt;">عنوان الشركة (المؤسسة) ورقم التلفون :</td>
    </tr>
    {{-- Full agency address (single centered line, Bold 9pt) --}}
    @if($agency_address)
    <tr>
      <td colspan="6" class="val">{{ $agency_address }}</td>
    </tr>
    @endif
    {{-- Purpose of Travel — label · compact boxes (AR+EN) · Arabic label, on
         the same 25 / 50 / 25 split. Labels sit at the TOP of the row as in the
         reference; the selected purpose is filled dark grey. --}}
    <tr class="seam-merge">
      <td class="lbl" style="vertical-align:top;">Purpose of Travel:</td>
      <td colspan="3" style="padding:2.2pt 4pt;vertical-align:middle;">
        <table class="pt" style="width:100%;"><tr>
          @foreach($purposeOpts as $opt)
          @php $isSel = $tp === $opt['val']; @endphp
          <td class="box{{ $isSel ? ' sel' : '' }}" style="width:{{ number_format(100/count($purposeOpts),2) }}%;{{ $isSel ? 'background:#444444;' : '' }}">
            <span class="pa" style="{{ $isSel ? 'color:#fff;' : '' }}">{{ $opt['ar'] }}</span><br><span class="pe" style="{{ $isSel ? 'color:#fff;' : '' }}">{{ $opt['en'] }}</span>
          </td>
          @endforeach
        </tr></table>
      </td>
      <td colspan="2" class="ar" style="vertical-align:top;">الغاية من السفر :</td>
    </tr>
  </tbody>
</table>

{{-- ── PASSPORT INFO (4 equal columns) — header cells hold the English label
     and its Arabic label side by side, centred; values centred below. --}}
<table class="bdr ppt" style="margin-top:0;margin-bottom:0;table-layout:fixed;">
  <colgroup>
    <col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%">
  </colgroup>
  <tbody>
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td>
    </tr>
    <tr>
      <td style="text-align:center;white-space:nowrap;padding-left:2pt;padding-right:2pt;">Place of issue: <span class="ar">محل الإصدار</span></td>
      <td style="text-align:center;white-space:nowrap;padding-left:2pt;padding-right:2pt;">Date of issue: <span class="ar">تاريخ الإصدار</span></td>
      <td style="text-align:center;white-space:nowrap;padding-left:2pt;padding-right:2pt;font-size:8.6pt;">Date of expiry: <span class="ar" style="font-size:8.1pt;">تاريخ انتهاء الصلاحية</span></td>
      <td style="text-align:center;white-space:nowrap;padding-left:2pt;padding-right:2pt;">Passport No.: <span class="ar">رقم الجواز</span></td>
    </tr>
    <tr>
      <td class="val" style="border-bottom:0;">{{ $U($passport_issue_place) }}</td>
      <td class="val" style="border-bottom:0;"><span class="blk">{{ $passport_issue_date ?: '' }}</span></td>
      <td class="val" style="border-bottom:0;"><span class="blk">{{ $passport_expiry_date ?: '' }}</span></td>
      <td class="val" style="border-bottom:0;"><span class="blk">{{ $U($passport_no) }}</span></td>
    </tr>
  </tbody>
</table>

{{-- ── DURATION / ARRIVAL / DEPARTURE (own 4-col grid: 33/17/25/25) ──────────
     One merged field group (Arabic-label row over English row, no divider).
     Arabic labels right-aligned to the reference's x positions. --}}
<table class="bdr hrows" style="margin-top:0;table-layout:fixed;">
  <colgroup>
    <col style="width:33%"><col style="width:17%"><col style="width:25%"><col style="width:25%">
  </colgroup>
  <tbody>
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:33%;"></td><td style="border:0;padding:0;width:17%;"></td><td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td>
    </tr>
    <tr class="mrow-ar">
      <td colspan="2" class="ar" style="padding-right:108pt;">مدة الإقامة بالمملكة :</td>
      <td class="ar" style="padding-right:80pt;">تاريخ الوصول :</td>
      <td class="ar" style="padding-right:36pt;">تاريخ المغادرة :</td>
    </tr>
    <tr class="mrow-en seam-merge">
      <td class="lbl">Duration of stay in the kingdom:</td>
      <td class="val">{{ $duration_stay_en ?: '' }}@if(!empty($duration_stay_ar)) <span class="ar">({{ $duration_stay_ar }})</span>@endif</td>
      <td class="lbl">Date of arrival: <strong>{{ $arrival_date ?: ($arrival_date_ar ?: '') }}</strong></td>
      <td class="lbl">Date of departure: <strong>{{ $departure_date ?: ($departure_date_ar ?: '') }}</strong></td>
    </tr>
  </tbody>
</table>

{{-- Empty framed band (no content): occupies the vertical slot the reference
     uses for its rows between Duration and Mahram, so every block below sits at
     the reference's position. Side rules continue the frame; its top rule is
     the seam with the Duration table (whose last row drops its bottom border)
     and the Mahram table's top rule closes it. --}}
<div class="gap-band" style="height:28.3pt;border-top:0.7pt solid #212529;border-left:0.7pt solid #212529;border-right:0.7pt solid #212529;"></div>

{{-- ── MAHRAM / DESTINATION (6-col grid 25/12.5/12.5/25/12.5/12.5) ───────────
     Reference: صلته and the relationship value start at the 25% line; اسم
     المحرم at the 75% line; Destination | value | جهة الوصول in the left half,
     Carrier's | اسم الشركة الناقلة in the right half. --}}
<table class="bdr hrows" style="margin-top:0;table-layout:fixed;">
  <colgroup>
    <col style="width:25%"><col style="width:12.5%"><col style="width:12.5%">
    <col style="width:25%"><col style="width:12.5%"><col style="width:12.5%">
  </colgroup>
  <tbody>
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:12.5%;"></td><td style="border:0;padding:0;width:12.5%;"></td>
      <td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:12.5%;"></td><td style="border:0;padding:0;width:12.5%;"></td>
    </tr>
    <tr class="mrow-ar">
      <td>&nbsp;</td>
      <td colspan="3" class="ar" style="text-align:left;padding-left:6.7pt;">صلته :</td>
      <td colspan="2" class="ar" style="text-align:left;padding-left:6.5pt;">اسم المحرم :</td>
    </tr>
    <tr class="mrow-en">
      <td class="lbl">Relationship:</td>
      <td colspan="5" class="val" style="text-align:left;padding-left:6.7pt;">{{ $relationship ?: 'EMPLOYER AND EMPLOYEE' }}</td>
    </tr>
    <tr class="seam-merge">
      <td class="lbl">Destination:</td>
      <td class="val">{{ $destination_city ?: ($work_city ?: '') }}</td>
      <td class="ar">جهة الوصول :</td>
      <td colspan="2" class="lbl" style="padding-left:6.8pt;">Carrier's: <strong>{{ $carrier ?: '' }}</strong></td>
      <td class="ar">اسم الشركة الناقلة :</td>
    </tr>
  </tbody>
</table>

{{-- ── DEPENDENTS (4 equal columns, as in the reference) ─────────────────────── --}}
<table class="bdr" style="margin-top:0;table-layout:fixed;">
  <colgroup>
    <col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%">
  </colgroup>
  <tbody>
    <tr style="font-size:0;line-height:0;">
      <td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td><td style="border:0;padding:0;width:25%;"></td>
    </tr>
    <tr>
      <td class="lbl" colspan="2" style="border-right:0;text-align:right;">Dependents traveling in the same passport</td>
      <td colspan="2" class="ar" style="border-left:0;text-align:left;">إصاحبات تخص أفراد العائلة المنتقلين في نفس جواز السفر :</td>
    </tr>
    <tr>
      <td class="val" style="padding-top:3pt;padding-bottom:3pt;"><span class="ar"><strong>نوع الصلة</strong></span><br>Relationship</td>
      <td class="val" style="padding-top:3pt;padding-bottom:3pt;"><span class="ar"><strong>تاريخ الميلاد</strong></span><br>Date of Birth</td>
      <td class="val" style="padding-top:3pt;padding-bottom:3pt;"><span class="ar"><strong>الجنس</strong></span><br>Sex</td>
      <td class="val" style="padding-top:3pt;padding-bottom:3pt;"><span class="ar"><strong>الاسم الكامل</strong></span><br>Full Name</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td class="val">CITY: {{ $work_city ?: '' }}, K.S.A</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td class="val">TEL:</td>
      <td>&nbsp;</td><td>&nbsp;</td>
    </tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    {{-- seam-merge: the kingdom table below supplies the single seam line. --}}
    <tr class="seam-merge"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
  </tbody>
</table>

{{-- ── NAME AND ADDRESS IN KINGDOM ───────────────────────────────────────────
     Attached directly to the dependents table (no gap), so the outer frame runs
     unbroken from the identity grid down to the declaration, as in the
     reference. The .gap-band above keeps this block at the reference's
     vertical position. --}}
<table class="bdr" style="margin-top:0;table-layout:fixed;">
  <colgroup><col style="width:50%"><col style="width:50%"></colgroup>
  <tbody>
    <tr>
      <td style="border-right:0;text-align:right;">Name and address of company or individual in the kingdom</td>
      <td style="border-left:0;text-align:left;" class="ar">اسم وعنوان الشركة أو اسم الشخص وعنوانه بالمملكة :</td>
    </tr>
    <tr>
      <td style="border-right:0;" class="val">{{ $kingdom_address_en ?: '' }}&nbsp;</td>
      <td style="border-left:0;" class="ar"><strong>{{ $kingdom_address_ar ?: '' }}</strong></td>
    </tr>
  </tbody>
</table>

{{-- ── DECLARATION (english right-aligned | arabic) — boxed by the side rules,
     no divider between the two halves. --}}
<table class="bdr" style="margin-top:0;table-layout:fixed;">
  <colgroup><col style="width:66.8%"><col style="width:33.2%"></colgroup>
  <tbody>
    <tr>
      <td style="text-align:right;border-right:0;border-top:0;padding-top:2.6pt;padding-bottom:2.6pt;">I the undersigned hereby that all the information I have provided are correct. I will abide by laws of the kingdom during the period of my residence in it.</td>
      <td class="ar" style="border-left:0;border-top:0;text-align:left;white-space:normal;padding-left:3pt;font-size:8pt;line-height:1.05;">أنا الموقع أدناه أقر بأن كل المعلومات التي زودتها صحيحة وسأكون ملتزماً بقوانين المملكة العربية السعودية خلال فترة وجودي بها.</td>
    </tr>
  </tbody>
</table>

{{-- ── SIGNATURE (single borderless row, text at the top of the band) ─────────
     Reference x: Date 35 · التاريخ ends 162 · Signature 166 · التوقيع ends 300 ·
     Name 308 · value 351 · الاسم ends 560. --}}
<table class="sig" style="margin-top:0;table-layout:fixed;">
  <colgroup>
    <col style="width:17.6%"><col style="width:7.8%"><col style="width:19.1%"><col style="width:6.6%">
    <col style="width:8.1%"><col style="width:35.0%"><col style="width:5.8%">
  </colgroup>
  <tbody>
    <tr>
      <td class="lbl" style="padding-left:7pt;height:19pt;">Date:</td>
      <td class="ar">التاريخ :</td>
      <td class="lbl" style="padding-left:4pt;">Signature:</td>
      <td class="ar">التوقيع :</td>
      <td class="lbl" style="padding-left:6.5pt;">Name:</td>
      <td style="padding-left:0;"><span class="blk">{{ $U($full_name_en) }}</span></td>
      <td class="ar" style="padding-right:7pt;">الاسم :</td>
    </tr>
  </tbody>
</table>

{{-- ── FOR OFFICIAL USE ONLY (dashed separator, rows = full-width rules) ────── --}}
<table class="offc" style="margin-top:0;">
  <tbody>
    <tr>
      <td style="padding-left:7pt;padding-top:2.4pt;padding-bottom:0;"><span style="text-decoration:underline;">For official use only</span></td>
      <td class="ar" style="padding-right:7pt;padding-top:2.4pt;padding-bottom:0;"><span style="text-decoration:underline;">للاستعمال الرسمي فقط</span></td>
    </tr>
  </tbody>
</table>
<table class="offr" style="table-layout:fixed;">
  <colgroup><col style="width:11.9%"><col style="width:15.8%"><col style="width:6.4%"><col style="width:19.1%"><col style="width:21.3%"><col style="width:25.5%"></colgroup>
  <tr>
    <td class="lbl" style="padding-left:7pt;padding-top:3pt;">Date:</td>
    <td class="val" style="text-align:left;"><span class="blk">{{ $visa_date_hijri ?: '' }}</span></td>
    <td class="ar">التاريخ :</td>
    <td class="lbl" style="padding-left:3.3pt;">Visa No:</td>
    <td class="val" style="text-align:left;"><span class="blk">{{ $visa_no ?: '' }}</span></td>
    <td class="ar" style="padding-right:7pt;">رقم الأمر المعتمد عليه في إعطاء التأشيرة :</td>
  </tr>
</table>
<table class="offr" style="table-layout:fixed;">
  <colgroup><col style="width:22.6%"><col style="width:55.7%"><col style="width:21.7%"></colgroup>
  <tr>
    <td class="lbl" style="padding-left:7pt;">Visit/Work for:</td>
    <td class="val" style="line-height:1;">@if(!empty($sponsor_name_ar))<span class="ar" style="font-size:9.7pt;"><strong>{{ $sponsor_name_ar }}</strong></span>@else{{ $sponsor_name ?: '' }}@endif</td>
    <td class="ar" style="padding-right:7pt;">لزيارة :</td>
  </tr>
</table>
<table class="offr" style="table-layout:fixed;">
  <colgroup><col style="width:44.0%"><col style="width:6.1%"><col style="width:19.8%"><col style="width:21.3%"><col style="width:8.8%"></colgroup>
  <tr>
    <td class="lbl" style="padding-left:7pt;">Date:</td>
    <td class="ar">التاريخ :</td>
    <td class="lbl" style="padding-left:7pt;">Id Number:</td>
    <td class="val" style="text-align:left;"><span class="blk">{{ $sponsor_id ?: '' }}</span></td>
    <td class="ar" style="padding-right:7pt;">أشير برقم :</td>
  </tr>
</table>
<table class="offr" style="table-layout:fixed;">
  <colgroup><col style="width:32.7%"><col style="width:10.6%"><col style="width:22.6%"><col style="width:6.0%"><col style="width:22.9%"><col style="width:5.2%"></colgroup>
  <tr>
    <td class="lbl" style="padding-left:7pt;">Fee Collected:</td>
    <td class="ar">المبلغ المحصل :</td>
    <td class="lbl" style="padding-left:3.8pt;">Type:</td>
    <td class="ar">نوعها :</td>
    <td class="lbl" style="padding-left:3.8pt;">Duration:</td>
    <td class="ar" style="padding-right:7pt;">مدتها :</td>
  </tr>
</table>

{{-- ── HEAD OF CONSULAR / BOTTOM BARCODE / CHECKED BY ─────────────────────────
     Reference: short 0.7pt rules above the two Arabic captions (57pt left,
     90pt right), 7pt inside the margin; barcode bars 49mm × 9.7mm centred,
     passport number (Bold 9.7pt) directly under them. --}}
<table style="margin-top:7.5pt;width:100%;border-collapse:collapse;">
  <tr>
    <td style="width:33%;vertical-align:top;font-size:9pt;padding:0 0 0 7pt;">
      <div style="width:57.3pt;border-top:0.7pt solid #212529;height:0;font-size:0;line-height:0;"></div>
      <div class="ar" style="text-align:left;font-size:8.5pt;">رئيس القسم القنصلي</div>
      <div style="padding-top:1pt;">Head of consular section</div>
    </td>
    <td style="width:34%;text-align:center;vertical-align:top;padding:2.5pt 0 0 0;">
      @if(!empty($bottomBarcodeSrc))
        <img src="{{ $bottomBarcodeSrc }}" style="width:49.2mm;height:9.7mm;display:block;margin:0 auto;">
      @endif
      <div style="text-align:center;font-size:9.7pt;font-weight:bold;color:#000;">{{ $bottomBarcodeText ?? '' }}</div>
    </td>
    <td style="width:33%;text-align:right;vertical-align:top;font-size:9pt;padding:0 7pt 0 0;">
      <table style="width:89.7pt;border-collapse:collapse;margin-left:auto;"><tr><td style="border-top:0.7pt solid #212529;padding:0;font-size:0;line-height:0;height:0;"></td></tr></table>
      <div class="ar">مدقق البيانات رقم صاحب العمل</div>
      <div style="padding-top:1pt;">Checked by</div>
    </td>
  </tr>
</table>

</div>{{-- /.ksa-app --}}
