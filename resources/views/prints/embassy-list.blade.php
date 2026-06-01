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

{{-- Arabic header --}}
<div style="text-align:center;margin-bottom:10pt;direction:rtl;">
  <div style="font-size:15pt;font-weight:bold;font-family:DejaVu Sans,sans-serif;">بيان بالجوازات المقدمة</div>
  <div style="font-size:10pt;margin-top:4pt;font-family:DejaVu Sans,sans-serif;">
    {{ $agency->name_ar ?? $agency->name }}
    @if($agency->rl_number) &nbsp;&nbsp; رقم الرخصة : {{ $agency->rl_number }} @endif
  </div>
  <div style="font-size:9.5pt;margin-top:2pt;font-family:DejaVu Sans,sans-serif;">
    التاريخ : {{ $list->list_date->format('d / m / Y') }}
    &nbsp;&nbsp; رقم القائمة : {{ $list->list_no }}
  </div>
</div>

@php $arGrandTotal = 0; @endphp

@foreach($categoryOrder as $category)
@if(isset($itemsByCategory[$category]) && $itemsByCategory[$category]->count() > 0)
@php $items = $itemsByCategory[$category]; $arGrandTotal += $items->count(); @endphp

{{-- Arabic category heading --}}
@php
$arLabels = ['new' => 'تأشيرات جديدة', 'restamping' => 'تجديد التأشيرة', 'cancellation' => 'إلغاء التأشيرة'];
$arCatLabel = $arLabels[$category] ?? $categoryLabels[$category];
@endphp
<div style="direction:rtl;font-size:10pt;font-weight:bold;padding:3pt 6pt;margin:8pt 0 3pt;border-right:4pt solid #000;background:#f0f0f0;font-family:DejaVu Sans,sans-serif;">{{ $arCatLabel }}</div>

<table class="bdr" style="direction:rtl;font-size:8.5pt;">
  <thead>
    <tr style="background:#ddd;">
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;width:28px;font-family:DejaVu Sans,sans-serif;">ت</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:right;width:18%;font-family:DejaVu Sans,sans-serif;">رقم الجوازات</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:right;font-family:DejaVu Sans,sans-serif;">اسم الكفيل</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:right;width:16%;font-family:DejaVu Sans,sans-serif;">رقم التأشيرة</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:center;width:13%;font-family:DejaVu Sans,sans-serif;">التاريخ</th>
      <th style="border:1px solid #000;padding:3pt 4pt;text-align:right;width:16%;font-family:DejaVu Sans,sans-serif;">المهنة</th>
    </tr>
  </thead>
  <tbody>
    @foreach($items as $item)
    <tr @if($loop->even) style="background:#f9f9f9;" @endif>
      <td style="border:1px solid #000;padding:2pt 4pt;text-align:center;">{{ $loop->iteration }}</td>
      <td style="border:1px solid #000;padding:2pt 4pt;text-align:right;direction:ltr;font-weight:bold;">{{ $item->snapshot_passport_no ?? '—' }}</td>
      <td style="border:1px solid #000;padding:2pt 4pt;text-align:right;font-family:DejaVu Sans,sans-serif;">{{ $item->snapshot_sponsor_name ?? '—' }}</td>
      <td style="border:1px solid #000;padding:2pt 4pt;text-align:right;direction:ltr;">{{ $item->snapshot_visa_no ?? '—' }}</td>
      <td style="border:1px solid #000;padding:2pt 4pt;text-align:center;direction:ltr;">{{ $list->list_date->format('d/m/Y') }}</td>
      <td style="border:1px solid #000;padding:2pt 4pt;text-align:right;font-family:DejaVu Sans,sans-serif;">{{ $item->snapshot_profession_ar ?? $item->snapshot_profession_en ?? '—' }}</td>
    </tr>
    @endforeach
    <tr style="font-weight:bold;background:#ececec;">
      <td colspan="5" style="border:1px solid #000;padding:2pt 4pt;text-align:right;direction:rtl;font-family:DejaVu Sans,sans-serif;">المجموعة :</td>
      <td style="border:1px solid #000;padding:2pt 4pt;text-align:center;">{{ $items->count() }}</td>
    </tr>
  </tbody>
</table>

@endif
@endforeach

{{-- Arabic grand total --}}
<div style="margin-top:8pt;direction:rtl;font-size:10pt;font-family:DejaVu Sans,sans-serif;">
  <strong>المجموع الكلي : {{ $arGrandTotal }}</strong>
</div>

{{-- Arabic signatures --}}
<table style="margin-top:20pt;font-size:9pt;">
  <tr>
    <td style="width:16%;text-align:center;vertical-align:bottom;padding:0 3pt;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-family:DejaVu Sans,sans-serif;">المستلم</div>
    </td>
    <td style="width:16%;text-align:center;vertical-align:bottom;padding:0 3pt;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-family:DejaVu Sans,sans-serif;">الختم</div>
    </td>
    <td style="width:17%;text-align:center;vertical-align:bottom;padding:0 3pt;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-family:DejaVu Sans,sans-serif;">المدقق</div>
    </td>
    <td style="width:17%;text-align:center;vertical-align:bottom;padding:0 3pt;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-family:DejaVu Sans,sans-serif;">التعبئة</div>
    </td>
    <td style="width:17%;text-align:center;vertical-align:bottom;padding:0 3pt;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-family:DejaVu Sans,sans-serif;">المسئول</div>
    </td>
    <td style="width:17%;text-align:center;vertical-align:bottom;padding:0 3pt;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-family:DejaVu Sans,sans-serif;">التسجيل</div>
    </td>
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
<div style="text-align:center;margin-bottom:8pt;border-bottom:2px solid #000;padding-bottom:8pt;">
  <div style="font-size:15pt;font-weight:bold;">{{ $agency->name }}{{ $agency->rl_number ? ' - RL' . $agency->rl_number : '' }}</div>
  @if($agency->license_number)<div style="font-size:9pt;">License: {{ $agency->license_number }}</div>@endif
  <div style="font-size:12pt;font-weight:bold;margin-top:4pt;text-transform:uppercase;letter-spacing:1pt;">Embassy List</div>
  <div style="font-size:10pt;margin-top:2pt;">
    List No: <strong>{{ $list->list_no }}</strong>
    &nbsp;&nbsp;&nbsp;
    Date: <strong>{{ $list->list_date->format('d / m / Y') }}</strong>
    @if($list->title) &nbsp;&nbsp;&nbsp; {{ $list->title }} @endif
  </div>
</div>

@php $grandTotal = 0; @endphp

@foreach($categoryOrder as $category)
@if(isset($itemsByCategory[$category]) && $itemsByCategory[$category]->count() > 0)
@php $items = $itemsByCategory[$category]; $grandTotal += $items->count(); @endphp

<div style="font-size:10pt;font-weight:bold;padding:3pt 6pt;margin:8pt 0 3pt;border-left:4pt solid #000;background:#f0f0f0;">
  {{ $categoryLabels[$category] }}
</div>

<table style="border-collapse:collapse;font-size:8.5pt;">
  <thead>
    <tr style="background:#ddd;">
      <th style="border:1px solid #888;padding:3pt 4pt;text-align:center;width:28px;">SL</th>
      <th style="border:1px solid #888;padding:3pt 4pt;text-align:left;width:18%;">Agent Name</th>
      <th style="border:1px solid #888;padding:3pt 4pt;text-align:left;">Candidate Name</th>
      <th style="border:1px solid #888;padding:3pt 4pt;text-align:left;width:15%;">Passport No.</th>
      <th style="border:1px solid #888;padding:3pt 4pt;text-align:left;width:14%;">Visa No.</th>
      <th style="border:1px solid #888;padding:3pt 4pt;text-align:left;width:16%;">Profession</th>
    </tr>
  </thead>
  <tbody>
    @foreach($items as $item)
    <tr @if($loop->even) style="background:#f9f9f9;" @endif>
      <td style="border:1px solid #bbb;padding:2pt 4pt;text-align:center;">{{ $loop->iteration }}</td>
      <td style="border:1px solid #bbb;padding:2pt 4pt;">{{ $item->snapshot_agent_name ?? '—' }}</td>
      <td style="border:1px solid #bbb;padding:2pt 4pt;">
        {{ $item->snapshot_candidate_name }}
        @if($item->snapshot_candidate_name_ar)
        <br><span style="font-size:8pt;color:#555;direction:rtl;font-family:DejaVu Sans,sans-serif;">{{ $item->snapshot_candidate_name_ar }}</span>
        @endif
      </td>
      <td style="border:1px solid #bbb;padding:2pt 4pt;font-weight:bold;">{{ $item->snapshot_passport_no ?? '—' }}</td>
      <td style="border:1px solid #bbb;padding:2pt 4pt;">{{ $item->snapshot_visa_no ?? '—' }}</td>
      <td style="border:1px solid #bbb;padding:2pt 4pt;">{{ $item->snapshot_profession_en ?? '—' }}</td>
    </tr>
    @endforeach
    <tr style="font-weight:bold;background:#ececec;">
      <td colspan="5" style="border:1px solid #bbb;padding:2pt 4pt;text-align:right;">{{ $categoryLabels[$category] }} Total:</td>
      <td style="border:1px solid #bbb;padding:2pt 4pt;">{{ $items->count() }}</td>
    </tr>
  </tbody>
</table>

@endif
@endforeach

{{-- English grand total --}}
<div style="margin-top:8pt;padding:5pt 8pt;border:2px solid #000;font-size:10.5pt;">
  Grand Total: <strong>{{ $grandTotal }}</strong> Candidate(s)
  @if($list->total_new > 0) &nbsp;|&nbsp; New: {{ $list->total_new }} @endif
  @if($list->total_restamping > 0) &nbsp;|&nbsp; Re-stamp: {{ $list->total_restamping }} @endif
  @if($list->total_cancellation > 0) &nbsp;|&nbsp; Cancel: {{ $list->total_cancellation }} @endif
</div>

{{-- English signatures --}}
<table style="margin-top:20pt;">
  <tr>
    <td style="width:33%;text-align:center;padding:0 6pt;vertical-align:bottom;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-size:8.5pt;">Agency Representative<br>Name &amp; Stamp</div>
    </td>
    <td style="width:33%;text-align:center;padding:0 6pt;vertical-align:bottom;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-size:8.5pt;">Prepared By<br><small>{{ $list->createdBy?->name ?? '—' }}</small></div>
    </td>
    <td style="width:33%;text-align:center;padding:0 6pt;vertical-align:bottom;">
      <div style="border-top:1px solid #000;padding-top:3pt;font-size:8.5pt;">Embassy Stamp<br>&nbsp;</div>
    </td>
  </tr>
</table>

@if(empty($_pdf))</div>@endif
</body>
</html>
