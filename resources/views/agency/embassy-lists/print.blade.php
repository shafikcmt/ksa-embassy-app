<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embassy List — {{ $embassyList->list_no }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; background: #fff; }

        .print-page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm 15mm 20mm; }

        /* Header */
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 12px; }
        .header .agency-name { font-size: 16pt; font-weight: bold; letter-spacing: .5px; }
        .header .agency-sub { font-size: 10pt; color: #444; margin-top: 2px; }
        .header .list-title { font-size: 14pt; font-weight: bold; margin-top: 8px; letter-spacing: 1px; text-transform: uppercase; }
        .header .list-meta { font-size: 10pt; margin-top: 4px; color: #333; }

        /* Category section */
        .category-section { margin-bottom: 14px; }
        .category-heading { font-size: 11pt; font-weight: bold; padding: 4px 8px; margin-bottom: 4px; border-left: 4px solid #000; background: #f5f5f5; }

        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table thead tr th { background: #e8e8e8; border: 1px solid #999; padding: 5px 6px; font-weight: bold; text-align: left; font-size: 10pt; }
        table tbody tr td { border: 1px solid #bbb; padding: 4px 6px; vertical-align: top; }
        table tbody tr:nth-child(even) td { background: #fafafa; }
        .sl-col { width: 35px; text-align: center; }
        .totals-row td { font-weight: bold; background: #efefef !important; border-top: 2px solid #666; }

        /* Grand total */
        .grand-total { margin-top: 10px; padding: 8px 10px; border: 2px solid #000; display: inline-block; font-size: 11pt; }
        .grand-total strong { font-size: 12pt; }

        /* Signature area */
        .signature-area { margin-top: 30px; display: flex; justify-content: space-between; }
        .sig-box { width: 30%; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 45px; padding-top: 4px; font-size: 9pt; }

        /* Print controls (screen only) */
        .print-controls { background: #1a1f2e; color: #fff; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; }
        .print-controls .info { font-size: .875rem; }

        @media print {
            .print-controls { display: none !important; }
            .print-page { width: 100%; padding: 10mm; }
            body { font-size: 10pt; }
            @page { size: A4 portrait; margin: 10mm; }
        }
    </style>
</head>
<body>

{{-- Screen-only toolbar --}}
<div class="print-controls">
    <div class="info">
        <strong>{{ $embassyList->list_no }}</strong>
        &nbsp;·&nbsp;{{ $embassyList->list_date->format('d M Y') }}
        &nbsp;·&nbsp;{{ $embassyList->total_items }} candidates
        <span class="badge ms-2" style="background:#{{ $embassyList->status === 'finalized' ? '198754' : ($embassyList->status === 'printed' ? '0dcaf0' : 'ffc107') }};color:{{ $embassyList->status === 'draft' ? '#000' : '#fff' }};">
            {{ ucfirst($embassyList->status) }}
        </span>
    </div>
    <div class="d-flex gap-2" style="display:flex;gap:8px;">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:7px 18px;border-radius:4px;cursor:pointer;font-size:.875rem;">
            🖨 Print
        </button>
        <a href="{{ route('embassy-lists.show', $embassyList) }}"
           style="background:#374151;color:#fff;border:none;padding:7px 18px;border-radius:4px;cursor:pointer;font-size:.875rem;text-decoration:none;">
            ← Back
        </a>
    </div>
</div>

<div class="print-page">

    {{-- Header --}}
    <div class="header">
        <div class="agency-name">{{ $embassyList->agency->name }}</div>
        <div class="agency-sub">
            @if($embassyList->agency->rl_number) RL No: {{ $embassyList->agency->rl_number }} @endif
            @if($embassyList->agency->license_number) &nbsp;|&nbsp; License: {{ $embassyList->agency->license_number }} @endif
        </div>
        <div class="list-title">Embassy Submission List</div>
        <div class="list-meta">
            List No: <strong>{{ $embassyList->list_no }}</strong>
            &nbsp;&nbsp;&nbsp;
            Date: <strong>{{ $embassyList->list_date->format('d / m / Y') }}</strong>
            @if($embassyList->title)
                &nbsp;&nbsp;&nbsp; {{ $embassyList->title }}
            @endif
        </div>
    </div>

    @php
        $categoryOrder  = ['restamping', 'new', 'cancellation'];
        $categoryLabels = ['restamping' => 'Re-Stamping', 'new' => 'New', 'cancellation' => 'Cancellation'];
        $grandTotal     = 0;
    @endphp

    {{-- Category sections --}}
    @foreach($categoryOrder as $category)
    @if(isset($itemsByCategory[$category]) && $itemsByCategory[$category]->count() > 0)
    @php $items = $itemsByCategory[$category]; @endphp
    <div class="category-section">
        <div class="category-heading">
            {{ $categoryLabels[$category] }}
        </div>
        <table>
            <thead>
                <tr>
                    <th class="sl-col">SL</th>
                    <th>Agent Name</th>
                    <th>Candidate Name</th>
                    <th>Passport No.</th>
                    <th>Visa No.</th>
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
                        <br><span style="font-size:9pt;color:#555;direction:rtl;">{{ $item->snapshot_candidate_name_ar }}</span>
                        @endif
                    </td>
                    <td><strong>{{ $item->snapshot_passport_no ?? '—' }}</strong></td>
                    <td>{{ $item->snapshot_visa_no ?? '—' }}</td>
                    <td>{{ $item->snapshot_profession_en ?? '—' }}</td>
                </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="5" class="text-right" style="text-align:right;">
                        {{ $categoryLabels[$category] }} Total:
                    </td>
                    <td>{{ $items->count() }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @php $grandTotal += $items->count(); @endphp
    @endif
    @endforeach

    {{-- Grand Total --}}
    <div style="margin-top:8px;">
        <div class="grand-total">
            Grand Total: <strong>{{ $grandTotal }}</strong> Candidate(s)
            @if($embassyList->total_new > 0) &nbsp;|&nbsp; New: {{ $embassyList->total_new }} @endif
            @if($embassyList->total_restamping > 0) &nbsp;|&nbsp; Re-stamp: {{ $embassyList->total_restamping }} @endif
            @if($embassyList->total_cancellation > 0) &nbsp;|&nbsp; Cancel: {{ $embassyList->total_cancellation }} @endif
        </div>
    </div>

    {{-- Signature area --}}
    <div class="signature-area">
        <div class="sig-box">
            <div class="sig-line">Agency Representative<br>Name &amp; Stamp</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Prepared By<br><small>{{ $embassyList->createdBy?->name ?? '—' }}</small></div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Embassy Stamp<br>&nbsp;</div>
        </div>
    </div>

</div>

</body>
</html>
