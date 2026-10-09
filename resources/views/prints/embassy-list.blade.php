<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<style>
@if(empty($_pdf))
/* BROWSER-ONLY @font-face: load the exact TTFs mPDF renders Arabic with (XB Riyaz)
   so the browser preview matches the PDF. mPDF ignores these — it has xbriyaz built in. */
@font-face { font-family: xbriyaz; font-weight: normal; font-style: normal; src: url('/fonts/XBRiyaz.ttf') format('truetype'); }
@font-face { font-family: xbriyaz; font-weight: bold;   font-style: normal; src: url('/fonts/XBRiyaz-Bold.ttf') format('truetype'); }
@font-face { font-family: ksaroboto; font-weight: normal; font-style: normal; src: url('/fonts/Roboto-Medium.ttf') format('truetype'); }
@font-face { font-family: ksaroboto; font-weight: bold; font-style: normal; src: url('/fonts/Roboto-Bold.ttf') format('truetype'); }
@endif
body { font-family: ksaroboto, sans-serif; font-size: 9.3pt; color: #212529; margin: 0; padding: 0; line-height: 1.5; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 3pt 5pt; vertical-align: middle; font-size: 9pt; }
.ar { direction: rtl; text-align: right; font-family: xbriyaz, 'DejaVu Sans', sans-serif; }
.bdr td, .bdr th { border: 0.72pt solid #212529; }
.sl-col { width: 28px; text-align: center; }
@media screen {
  body { background: #e5e7eb; }
  .a4-page { width: 215.9mm; min-height: 279.4mm; margin: 10mm auto; background: #fff; padding: 9.525mm 12.065mm 12.7mm 12.573mm; box-sizing: border-box; }
}
{{-- @page is emitted ONLY for the browser. mPDF's constructor already sets
     A4 + 10mm margins; feeding it an @page rule makes this mPDF version spray
     blank pages, so it must be hidden from the PDF render. --}}
@if(empty($_pdf))
@page { size: Letter; margin: 0; }
@endif
@media print {
  body { background: #fff; margin: 0; padding: 0; }
  .no-print { display: none !important; }
  .a4-page { width: 100%; margin: 0; padding: 9.525mm 12.065mm 12.7mm 12.573mm; box-sizing: border-box; page-break-after: always; }
  .a4-page:last-child { page-break-after: auto; }
}
/* Explicit cell fonts keep mPDF and browser metrics aligned. */
.embassy-arabic td, .embassy-english td, .embassy-arabic th, .embassy-english th { font-family: ksaroboto, sans-serif; font-size:9.3pt; line-height:14.2pt; }
</style>
</head>
<body>

@if(empty($_pdf))
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:7pt 12pt;margin-bottom:6pt;font-size:8pt;">
  <strong>Embassy List</strong> — {{ $list->list_no }} &nbsp; ({{ $list->list_date->format('d M Y') }})
  &nbsp;&nbsp;
  <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:3pt 10pt;border-radius:3pt;cursor:pointer;">&#128424; Print</button>
  &nbsp;
  <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8592; Back</a>
  &nbsp;
  <a href="{{ route('embassy-lists.download-pdf', $list) }}" style="background:#16a34a;color:#fff;padding:3pt 10pt;border-radius:3pt;text-decoration:none;">&#8595; PDF</a>
</div>
@endif

{{-- ═══════════════════════════════════════ --}}
{{-- PAGE 1: ARABIC --}}
{{-- ═══════════════════════════════════════ --}}
@if(empty($_pdf))<div class="a4-page">@endif

{{-- Arabic title --}}
<div style="text-align:center;padding-top:4pt;margin-bottom:7pt;line-height:16pt;direction:rtl;">
  <div style="font-size:10.7pt;font-weight:bold;font-family:xbriyaz,'DejaVu Sans',sans-serif;">بيان بالجوازات المقدمة</div>
</div>

{{-- Single combined table: office/license header (borderless) + column header + bilingual category bars + rows + group totals.
     The office header lives INSIDE this table so its cells share the same columns and align exactly (mPDF sizes columns by content, so two separate tables cannot be aligned). --}}
<table class="bdr embassy-arabic" style="table-layout:fixed;direction:rtl;font-size:9.3pt;font-family:ksaroboto,sans-serif;">
  <colgroup>
    <col style="width:4.2%"><col style="width:14.6%"><col style="width:38.8%"><col style="width:14%"><col style="width:8.4%"><col style="width:20%">
  </colgroup>
  <thead>
    {{-- Office / license / date / signature header — borderless rows sharing the grid columns (RTL) --}}
    <tr>
      <td colspan="2" style="height:18.12pt;line-height:18.12pt;border:0;text-align:right;padding:0;font-weight:bold;font-size:9.5pt;direction:rtl;font-family:xbriyaz,'DejaVu Sans',sans-serif;">اسم المكتب :</td>
      <td style="height:18.12pt;line-height:18.12pt;border:0;text-align:left;padding:0 0 0 34pt;font-weight:bold;font-size:12pt;direction:ltr;white-space:nowrap;">{{ $agency->name }}</td>
      <td colspan="2" style="height:18.12pt;line-height:18.12pt;border:0;text-align:left;padding:0 0 0 32pt;font-weight:bold;font-size:10pt;direction:rtl;font-family:xbriyaz,'DejaVu Sans',sans-serif;">رقم الرخصة :</td>
      <td style="height:18.12pt;line-height:18.12pt;border:0;text-align:right;padding:0 32pt;font-weight:bold;font-size:12pt;direction:ltr;">{{ $agency->rl_number ?: '—' }}</td>
    </tr>
    <tr>
      <td colspan="2" style="height:18.12pt;line-height:18.12pt;border:0;text-align:right;padding:0;font-weight:bold;font-size:9.5pt;direction:rtl;font-family:xbriyaz,'DejaVu Sans',sans-serif;">توقيع :</td>
      <td style="height:18.12pt;line-height:18.12pt;border:0;padding:0 4pt;">&nbsp;</td>
      <td colspan="2" style="height:18.12pt;line-height:18.12pt;border:0;text-align:left;padding:0 0 0 53pt;font-weight:bold;font-size:10pt;direction:rtl;font-family:xbriyaz,'DejaVu Sans',sans-serif;">التاريخ :</td>
      <td style="height:18.12pt;line-height:18.12pt;border:0;text-align:right;padding:0 10pt;font-weight:bold;font-size:12pt;direction:ltr;">{{ $list->list_date->format('d M, Y') }}</td>
    </tr>
    {{-- spacer to keep a gap before the column-header row --}}
    <tr><td colspan="6" style="border:0;padding:0;height:{{ !empty($_pdf) ? '29.5pt' : '28.5pt' }};font-size:1pt;line-height:{{ !empty($_pdf) ? '29.5pt' : '28.5pt' }};">&nbsp;</td></tr>
    <tr style="background:#fff;">
      <th style="width:4.2%;border:0.72pt solid #212529;padding:0.75pt 3pt;text-align:center;background-color:#fff;font-family:xbriyaz,'DejaVu Sans',sans-serif;">ت<br><span style="font-size:9.3pt;direction:ltr;unicode-bidi:embed;font-family:ksaroboto,sans-serif;">SL.</span></th>
      <th style="width:14.6%;border:0.72pt solid #212529;padding:0.75pt 3pt;text-align:center;background-color:#fff;font-family:xbriyaz,'DejaVu Sans',sans-serif;">رقم الجوازات<br><span style="font-size:9.3pt;direction:ltr;unicode-bidi:embed;font-family:ksaroboto,sans-serif;">Passport No.</span></th>
      <th style="width:38.8%;border:0.72pt solid #212529;padding:0.75pt 3pt;text-align:center;background-color:#fff;font-family:xbriyaz,'DejaVu Sans',sans-serif;">اسم الكفيل<br><span style="font-size:9.3pt;direction:ltr;unicode-bidi:embed;font-family:ksaroboto,sans-serif;">Sponsor Name</span></th>
      <th style="width:14%;border:0.72pt solid #212529;padding:0.75pt 3pt;text-align:center;background-color:#fff;font-family:xbriyaz,'DejaVu Sans',sans-serif;">رقم التأشيرة<br><span style="font-size:9.3pt;direction:ltr;unicode-bidi:embed;font-family:ksaroboto,sans-serif;">Visa No</span></th>
      <th style="width:8.4%;border:0.72pt solid #212529;padding:0.75pt 3pt;text-align:center;background-color:#fff;font-family:xbriyaz,'DejaVu Sans',sans-serif;">التاريخ<br><span style="font-size:9.3pt;direction:ltr;unicode-bidi:embed;font-family:ksaroboto,sans-serif;">Year</span></th>
      <th style="width:20%;border:0.72pt solid #212529;padding:0.75pt 3pt;text-align:center;background-color:#fff;font-family:xbriyaz,'DejaVu Sans',sans-serif;">المهنة<br><span style="font-size:9.3pt;direction:ltr;unicode-bidi:embed;font-family:ksaroboto,sans-serif;">Profession</span></th>
    </tr>
  </thead>
  <tbody>
    @foreach($categoryOrder as $category)
    @php $items = $itemsByCategory[$category] ?? collect(); @endphp
    {{-- bilingual category bar — always shown, like the reference (incl. empty Cancellation) --}}
    <tr>
      <td colspan="6" style="border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;font-weight:bold;direction:ltr;font-family:xbriyaz,'DejaVu Sans',sans-serif;"><span style="direction:ltr;unicode-bidi:embed;">{{ $categoryLabelsBi[$category] }}</span></td>
    </tr>
    @foreach($items as $item)
    @php
      // Bilingual profession: stored Arabic wins; else fall back to the En→Ar map (config/professions.php).
      $profEn = $item->snapshot_profession_en;
      $profAr = $item->snapshot_profession_ar ?: ($profEn ? (config('professions')[mb_strtolower(trim($profEn))] ?? null) : null);
    @endphp
    <tr class="data-row">
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;">{{ $loop->iteration }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;direction:ltr;font-weight:normal;">{{ $item->snapshot_passport_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 4pt;text-align:center;font-family:xbriyaz,'DejaVu Sans',sans-serif;">{{ $item->snapshot_sponsor_name_ar ?? $item->snapshot_sponsor_name ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;direction:ltr;">{{ $item->snapshot_visa_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;direction:ltr;">{{ $hijriYear ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 4pt;text-align:center;white-space:nowrap;font-family:xbriyaz,'DejaVu Sans',sans-serif;">{{ $profAr ?: ($profEn ?: '—') }}</td>
    </tr>
    @endforeach
    @if($items->count() > 0)
    <tr style="font-weight:bold;">
      <td colspan="6" style="border:0.72pt solid #212529;padding:0.75pt 4pt;text-align:right;font-weight:bold;direction:rtl;font-family:xbriyaz,'DejaVu Sans',sans-serif;">المجموعة : {{ $items->count() }}</td>
    </tr>
    @endif
    @endforeach
  </tbody>
</table>

{{-- Arabic signatures — 2 columns × 3 rows (matches reference) --}}
<table style="margin-top:23.4pt;margin-left:0;direction:rtl;font-size:9.3pt;width:84%;font-family:xbriyaz,'DejaVu Sans',sans-serif;">
  <tr>
    <td style="text-align:right;padding:2pt 55pt 2pt 0;line-height:14.2pt;">المستلم :</td>
    <td style="text-align:right;padding:2pt 55pt 2pt 0;line-height:14.2pt;">الختم :</td>
  </tr>
  <tr>
    <td style="text-align:right;padding:2pt 55pt 2pt 0;line-height:14.2pt;">المدقق :</td>
    <td style="text-align:right;padding:2pt 55pt 2pt 0;line-height:14.2pt;">التعبئة :</td>
  </tr>
  <tr>
    <td style="text-align:right;padding:2pt 55pt 2pt 0;line-height:14.2pt;">المسئول :</td>
    <td style="text-align:right;padding:2pt 55pt 2pt 0;line-height:14.2pt;">التسجيل :</td>
  </tr>
</table>

{{-- ═══════════════════════════════════════ --}}
{{-- PAGE BREAK --}}
{{-- ═══════════════════════════════════════ --}}
@if(!empty($_pdf))<pagebreak />@else</div><div class="a4-page">@endif

{{-- ═══════════════════════════════════════ --}}
{{-- PAGE 2: ENGLISH --}}
{{-- ═══════════════════════════════════════ --}}

{{-- English header --}}
@php
  // Reference shows "<name> - RL1001". Avoid "RLRL…" when rl_number already starts with RL.
  $rl = $agency->rl_number;
  $rlSuffix = $rl ? (\Illuminate\Support\Str::startsWith(strtoupper($rl), 'RL') ? ' - ' . $rl : ' - RL' . $rl) : '';
@endphp
<div style="text-align:center;margin-bottom:24.5pt;line-height:24pt;font-family:ksaroboto,sans-serif;">
  <div style="font-size:20.4pt;font-weight:bold;font-family:ksaroboto,sans-serif;">{{ $agency->name }}{{ $rlSuffix }}</div>
  <div style="font-size:15pt;font-weight:bold;font-family:ksaroboto,sans-serif;margin-top:0;line-height:24pt;">Embassy List - {{ $list->list_date->format('d M, Y') }}</div>
</div>

{{-- Single combined table: column header + bilingual category bars + rows + group totals --}}
<table class="bdr embassy-english" style="table-layout:fixed;border-collapse:collapse;font-size:9.3pt;">
  <colgroup>
    <col style="width:3.6%"><col style="width:23.2%"><col style="width:30%"><col style="width:13%"><col style="width:12.6%"><col style="width:17.6%">
  </colgroup>
  <thead>
    <tr style="background:#fff;">
      <th style="width:3.6%;border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;background-color:#fff;">SL.</th>
      <th style="width:23.2%;border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;background-color:#fff;">Agent Name</th>
      <th style="width:30%;border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;background-color:#fff;">Name</th>
      <th style="width:13%;border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;background-color:#fff;">Passport No.</th>
      <th style="width:12.6%;border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;background-color:#fff;">Visa No</th>
      <th style="width:17.6%;border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;background-color:#fff;">Profession</th>
    </tr>
  </thead>
  <tbody>
    @foreach($categoryOrder as $category)
    @php $items = $itemsByCategory[$category] ?? collect(); @endphp
    {{-- bilingual category bar — always shown, like the reference (incl. empty Cancellation) --}}
    <tr>
      <td colspan="6" style="border:0.72pt solid #212529;padding:0.9pt 3pt;text-align:center;font-weight:bold;direction:ltr;font-family:xbriyaz,'DejaVu Sans',sans-serif;"><span style="direction:ltr;unicode-bidi:embed;">{{ $categoryLabelsBi[$category] }}</span></td>
    </tr>
    @foreach($items as $item)
    @php
      // Arabic-only profession (same fallback logic as page 1): stored Arabic wins, else En→Ar map.
      $profEn = $item->snapshot_profession_en;
      $profAr = $item->snapshot_profession_ar ?: ($profEn ? (config('professions')[mb_strtolower(trim($profEn))] ?? null) : null);
    @endphp
    <tr class="data-row">
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;">{{ $loop->iteration }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;">{{ $item->snapshot_agent_name ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;">{{ $item->snapshot_candidate_name }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;font-weight:normal;">{{ $item->snapshot_passport_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 3pt;text-align:center;">{{ $item->snapshot_visa_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:0.72pt solid #212529;border-right:0.72pt solid #212529;padding:{{ !empty($_pdf) ? '0.78pt' : '0.75pt' }} 4pt;text-align:center;direction:rtl;font-family:xbriyaz,'DejaVu Sans',sans-serif;">{{ $profAr ?: ($profEn ?: '—') }}</td>
    </tr>
    @endforeach
    @if($items->count() > 0)
    <tr style="font-weight:bold;">
      <td colspan="6" style="border:0.72pt solid #212529;padding:0.75pt 4pt;text-align:right;font-weight:bold;direction:rtl;font-family:xbriyaz,'DejaVu Sans',sans-serif;">المجموعة : {{ $items->count() }}</td>
    </tr>
    @endif
    @endforeach
  </tbody>
</table>

@if(empty($_pdf))</div>@endif
@if(empty($_pdf) && request()->boolean('autoprint'))
{{-- List "Print" buttons pass ?autoprint=1 → open the print dialog once images/fonts load. --}}
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endif
</body>
</html>
