<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10pt;
    color: #000;
    background: #fff;
}
.ar { font-family: 'DejaVu Sans', Arial, sans-serif; direction: rtl; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 4px 6px; vertical-align: middle; }
.header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }
.header .agency-name { font-size: 14pt; font-weight: bold; }
.header .list-title { font-size: 12pt; font-weight: bold; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }
.header .list-meta { font-size: 9.5pt; margin-top: 3px; }
.category-heading { font-size: 10.5pt; font-weight: bold; padding: 4px 8px; margin: 10px 0 3px; border-left: 4px solid #000; background: #f0f0f0; }
.data-table thead tr th { background: #ddd; border: 1px solid #888; font-size: 9pt; font-weight: bold; text-align: left; }
.data-table tbody tr td { border: 1px solid #bbb; font-size: 9pt; }
.data-table tbody tr:nth-child(even) td { background: #f9f9f9; }
.totals-row td { font-weight: bold; background: #ececec !important; border-top: 2px solid #666; }
.sl-col { width: 32px; text-align: center; }
.grand-total { margin-top: 8px; padding: 7px 10px; border: 2px solid #000; display: inline-block; font-size: 10.5pt; }
.sig-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 3px; font-size: 8.5pt; text-align: center; }
@page { size: A4 portrait; margin: 10mm; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="agency-name">{{ $agency->name }}</div>
    <div style="font-size:9pt;">
        @if($agency->rl_number) RL No: {{ $agency->rl_number }} @endif
        @if($agency->license_number) &nbsp;|&nbsp; License: {{ $agency->license_number }} @endif
    </div>
    <div class="list-title">Embassy Submission List</div>
    <div class="list-meta">
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

<div class="category-heading">{{ $categoryLabels[$category] }}</div>
<table class="data-table">
    <thead>
        <tr>
            <th class="sl-col">SL</th>
            <th style="width:18%;">Agent Name</th>
            <th style="width:24%;">Candidate Name</th>
            <th style="width:14%;">Passport No.</th>
            <th style="width:14%;">Visa No.</th>
            <th>Profession</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            <td class="sl-col">{{ $loop->iteration }}</td>
            <td>{{ $item->snapshot_agent_name ?? '—' }}</td>
            <td>
                {{ $item->snapshot_candidate_name }}
                @if($item->snapshot_candidate_name_ar)
                <br><span class="ar" style="font-size:8.5pt;color:#555;">{{ $item->snapshot_candidate_name_ar }}</span>
                @endif
            </td>
            <td><strong>{{ $item->snapshot_passport_no ?? '—' }}</strong></td>
            <td>{{ $item->snapshot_visa_no ?? '—' }}</td>
            <td>{{ $item->snapshot_profession_en ?? '—' }}</td>
        </tr>
        @endforeach
        <tr class="totals-row">
            <td colspan="5" style="text-align:right;">{{ $categoryLabels[$category] }} Total:</td>
            <td>{{ $items->count() }}</td>
        </tr>
    </tbody>
</table>

@endif
@endforeach

{{-- Grand Total --}}
<div style="margin-top:8px;">
    <div class="grand-total">
        Grand Total: <strong>{{ $grandTotal }}</strong> Candidate(s)
        @if($list->total_new > 0) &nbsp;|&nbsp; New: {{ $list->total_new }} @endif
        @if($list->total_restamping > 0) &nbsp;|&nbsp; Re-stamp: {{ $list->total_restamping }} @endif
        @if($list->total_cancellation > 0) &nbsp;|&nbsp; Cancel: {{ $list->total_cancellation }} @endif
    </div>
</div>

{{-- Signatures --}}
<table style="margin-top:10px;">
    <tr>
        <td style="width:33%;text-align:center;"><div class="sig-line">Agency Representative<br>Name &amp; Stamp</div></td>
        <td style="width:33%;text-align:center;"><div class="sig-line">Prepared By<br><small>{{ $list->createdBy?->name ?? '—' }}</small></div></td>
        <td style="width:33%;text-align:center;"><div class="sig-line">Embassy Stamp<br>&nbsp;</div></td>
    </tr>
</table>

</body>
</html>
