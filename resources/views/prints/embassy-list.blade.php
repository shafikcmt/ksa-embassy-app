<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #000; margin: 0; padding: 0; line-height: 1.5; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 3pt 5pt; vertical-align: middle; font-size: 9pt; }
.ar { direction: rtl; text-align: right; font-family: DejaVu Sans, sans-serif; }
.bdr td, .bdr th { border: 1px solid #000; }
.sl-col { width: 28px; text-align: center; }
@media screen {
  body { background: #e5e7eb; }
  .a4-page { width: 210mm; min-height: 297mm; margin: 10mm auto; background: #fff; box-shadow: 0 0 12px rgba(0,0,0,.15); padding: 10mm 12mm; box-sizing: border-box; overflow: hidden; }
}
{{-- @page is emitted ONLY for the browser. mPDF's constructor already sets
     A4 + 10mm margins; feeding it an @page rule makes this mPDF version spray
     blank pages, so it must be hidden from the PDF render. --}}
@if(empty($_pdf))
@page { size: A4; margin: 0; }
@endif
@media print {
  body { background: #fff; margin: 0; padding: 0; }
  .no-print { display: none !important; }
  .a4-page { width: 100%; margin: 0; padding: 10mm 12mm; box-shadow: none; box-sizing: border-box; page-break-after: always; }
  .a4-page:last-child { page-break-after: auto; }
}
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
<div style="text-align:center;margin-bottom:8pt;direction:rtl;">
  <div style="font-size:13pt;font-weight:bold;font-family:DejaVu Sans,sans-serif;">بيان بالجوازات المقدمة</div>
</div>

{{-- Single combined table: office/license header (borderless) + column header + bilingual category bars + rows + group totals.
     The office header lives INSIDE this table so its cells share the same columns and align exactly (mPDF sizes columns by content, so two separate tables cannot be aligned). --}}
<table class="bdr" style="direction:rtl;font-size:8.5pt;font-family:DejaVu Sans,sans-serif;">
  <colgroup>
    <col style="width:28px"><col style="width:16%"><col><col style="width:15%"><col style="width:9%"><col style="width:20%">
  </colgroup>
  <thead>
    {{-- Office / license / date / signature header — borderless rows sharing the grid columns (RTL) --}}
    <tr>
      <td colspan="2" style="border:0;text-align:right;padding:3pt 6pt;font-weight:bold;font-size:10.5pt;direction:rtl;">اسم المكتب :</td>
      <td style="border:0;text-align:left;padding:3pt 6pt;font-weight:bold;font-size:11pt;direction:ltr;white-space:nowrap;">{{ $agency->name }}</td>
      <td colspan="2" style="border:0;text-align:left;padding:10pt 29pt;font-weight:bold;font-size:9.5pt;direction:rtl;">رقم الرخصة :</td>
      <td style="border:0;text-align:right;padding:3pt 6pt;font-weight:600;font-size:10pt;direction:ltr;">{{ $agency->rl_number ?: '—' }}</td>
    </tr>
    <tr>
      <td colspan="2" style="border:0;text-align:right;padding:3pt 6pt;font-weight:bold;font-size:10.5pt;direction:rtl;">توقيع :</td>
      <td style="border:0;padding:3pt 6pt;">&nbsp;</td>
      <td colspan="2" style="border:0;text-align:left;padding:3pt 47pt;font-weight:bold;font-size:9.5pt;direction:rtl;">التاريخ :</td>
      <td style="border:0;text-align:right;padding:3pt 6pt;font-weight:600;font-size:10pt;direction:ltr;">{{ $list->list_date->format('d M, Y') }}</td>
    </tr>
    {{-- spacer to keep a gap before the column-header row --}}
    <tr><td colspan="6" style="border:0;padding:0;height:15pt;font-size:1pt;line-height:15pt;">&nbsp;</td></tr>
    <tr style="background:#e9e9e9;">
      <th style="border:1px solid #000;padding:2pt 4pt;text-align:center;">ت<br><span style="font-size:8pt;direction:ltr;unicode-bidi:embed;">SL.</span></th>
      <th style="border:1px solid #000;padding:2pt 4pt;text-align:center;">رقم الجوازات<br><span style="font-size:8pt;direction:ltr;unicode-bidi:embed;">Passport No.</span></th>
      <th style="border:1px solid #000;padding:2pt 4pt;text-align:center;">اسم الكفيل<br><span style="font-size:8pt;direction:ltr;unicode-bidi:embed;">Sponsor Name</span></th>
      <th style="border:1px solid #000;padding:2pt 4pt;text-align:center;">رقم التأشيرة<br><span style="font-size:8pt;direction:ltr;unicode-bidi:embed;">Visa No</span></th>
      <th style="border:1px solid #000;padding:2pt 4pt;text-align:center;">التاريخ<br><span style="font-size:8pt;direction:ltr;unicode-bidi:embed;">Year</span></th>
      <th style="border:1px solid #000;padding:2pt 4pt;text-align:center;">المهنة<br><span style="font-size:8pt;direction:ltr;unicode-bidi:embed;">Profession</span></th>
    </tr>
  </thead>
  <tbody>
    @foreach($categoryOrder as $category)
    @php $items = $itemsByCategory[$category] ?? collect(); @endphp
    {{-- bilingual category bar — always shown, like the reference (incl. empty Cancellation) --}}
    <tr>
      <td colspan="6" style="border:1px solid #000;padding:3pt 4pt;text-align:center;font-weight:bold;direction:rtl;">{{ $categoryLabelsBi[$category] }}</td>
    </tr>
    @foreach($items as $item)
    @php
      // Bilingual profession: stored Arabic wins; else fall back to the En→Ar map (config/professions.php).
      $profEn = $item->snapshot_profession_en;
      $profAr = $item->snapshot_profession_ar ?: ($profEn ? (config('professions')[mb_strtolower(trim($profEn))] ?? null) : null);
    @endphp
    <tr>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;">{{ $loop->iteration }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;direction:ltr;font-weight:bold;">{{ $item->snapshot_passport_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 6pt;text-align:right;">{{ $item->snapshot_sponsor_name_ar ?? $item->snapshot_sponsor_name ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;direction:ltr;">{{ $item->snapshot_visa_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;direction:ltr;">{{ $hijriYear ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 6pt;text-align:center;white-space:nowrap;">{{ $profAr ?: ($profEn ?: '—') }}</td>
    </tr>
    @endforeach
    @if($items->count() > 0)
    <tr style="font-weight:bold;">
      <td colspan="6" style="border:1px solid #000;padding:2pt 6pt;text-align:right;direction:rtl;">المجموعة : {{ $items->count() }}</td>
    </tr>
    @endif
    @endforeach
  </tbody>
</table>

{{-- Arabic signatures — 2 columns × 3 rows (matches reference) --}}
<table style="margin-top:22pt;direction:rtl;font-size:10pt;width:60%;font-family:DejaVu Sans,sans-serif;">
  <tr>
    <td style="text-align:right;padding:8pt 6pt;">المستلم :</td>
    <td style="text-align:right;padding:8pt 6pt;">الختم :</td>
  </tr>
  <tr>
    <td style="text-align:right;padding:8pt 6pt;">المدقق :</td>
    <td style="text-align:right;padding:8pt 6pt;">التعبئة :</td>
  </tr>
  <tr>
    <td style="text-align:right;padding:8pt 6pt;">المسئول :</td>
    <td style="text-align:right;padding:8pt 6pt;">التسجيل :</td>
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
<div style="text-align:center;margin-bottom:10pt;">
  @if($agency->print_logo && $agency->logo)
    <img src="{{ empty($_pdf) ? asset('storage/'.$agency->logo) : public_path('storage/'.$agency->logo) }}"
         style="max-height:20mm;max-width:60mm;margin-bottom:4pt;" alt="">
  @endif
  <div style="font-size:18pt;font-weight:bold;">{{ $agency->name }}{{ $rlSuffix }}</div>
  <div style="font-size:13pt;font-weight:bold;margin-top:2pt;">Embassy List - {{ $list->list_date->format('d M, Y') }}</div>
</div>

{{-- Single combined table: column header + bilingual category bars + rows + group totals --}}
<table class="bdr" style="border-collapse:collapse;font-size:8.5pt;">
  <colgroup>
    <col style="width:28px"><col style="width:20%"><col><col style="width:15%"><col style="width:14%"><col style="width:16%">
  </colgroup>
  <thead>
    <tr style="background:#e9e9e9;">
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;">SL.</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;">Agent Name</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;">Name</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;">Passport No.</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;">Visa No</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;">Profession</th>
    </tr>
  </thead>
  <tbody>
    @foreach($categoryOrder as $category)
    @php $items = $itemsByCategory[$category] ?? collect(); @endphp
    {{-- bilingual category bar — always shown, like the reference (incl. empty Cancellation) --}}
    <tr>
      <td colspan="6" style="border:1px solid #000;padding:3pt 4pt;text-align:center;font-weight:bold;">{{ $categoryLabelsBi[$category] }}</td>
    </tr>
    @foreach($items as $item)
    @php
      // Arabic-only profession (same fallback logic as page 1): stored Arabic wins, else En→Ar map.
      $profEn = $item->snapshot_profession_en;
      $profAr = $item->snapshot_profession_ar ?: ($profEn ? (config('professions')[mb_strtolower(trim($profEn))] ?? null) : null);
    @endphp
    <tr>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;">{{ $loop->iteration }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;">{{ $item->snapshot_agent_name ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;">{{ $item->snapshot_candidate_name }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;font-weight:bold;">{{ $item->snapshot_passport_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 4pt;text-align:center;">{{ $item->snapshot_visa_no ?? '—' }}</td>
      <td style="border-top:0;border-bottom:0;border-left:1px solid #000;border-right:1px solid #000;padding:2pt 6pt;text-align:right;direction:rtl;font-family:DejaVu Sans,sans-serif;">{{ $profAr ?: ($profEn ?: '—') }}</td>
    </tr>
    @endforeach
    @if($items->count() > 0)
    <tr style="font-weight:bold;">
      <td colspan="6" style="border:1px solid #000;padding:2pt 6pt;text-align:right;direction:rtl;font-family:DejaVu Sans,sans-serif;">المجموعة : {{ $items->count() }}</td>
    </tr>
    @endif
    @endforeach
  </tbody>
</table>

@if(empty($_pdf))</div>@endif
</body>
</html>
