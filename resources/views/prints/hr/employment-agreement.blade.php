@extends('prints.layouts.print')

@section('content')
<div class="page">

<div class="no-print" style="background:#1a1f2e;color:#fff;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
    <span style="font-size:.85rem;"><strong>Employment Agreement</strong> — {{ $full_name_en }}</span>
    <div style="display:flex;gap:8px;">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;">&#128424; Print</button>
        <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;text-decoration:none;">&#8592; Back</a>
    </div>
</div>

{{-- Title --}}
<div class="header-bar text-center">
    <div style="font-size:13pt;font-weight:bold;letter-spacing:1px;">EMPLOYMENT AGREEMENT</div>
    <div style="font-size:9pt;margin-top:2px;">عقد عمل</div>
</div>

{{-- Parties --}}
<div style="margin:10px 0 8px;">
    <strong>THIS AGREEMENT</strong> is made between the following parties:
</div>

<table class="bordered" style="margin-bottom:8px;">
    <tr>
        <td style="width:20%;font-weight:bold;background:#f5f5f5;">First Party<br><span style="font-weight:normal;font-size:8.5pt;">(Employer / Company)</span></td>
        <td>
            <div><strong>{{ $employer_name ?: ($agency_name) }}</strong></div>
            @if($sponsor_id)<div style="font-size:8.5pt;">ID: {{ $sponsor_id }}</div>@endif
            @if($work_city)<div style="font-size:8.5pt;">Location: {{ $work_city }}</div>@endif
        </td>
    </tr>
    <tr>
        <td style="font-weight:bold;background:#f5f5f5;">Second Party<br><span style="font-weight:normal;font-size:8.5pt;">(Employee)</span></td>
        <td>
            <div><strong>{{ $full_name_en }}</strong>
                @if($full_name_ar) &nbsp; <span class="ar">{{ $full_name_ar }}</span> @endif
            </div>
            <div style="font-size:8.5pt;">
                Passport No: <strong>{{ $passport_no ?: '—' }}</strong> &nbsp;
                Nationality: <strong>{{ $nationality }}</strong> &nbsp;
                Profession: <strong>{{ $profession_en ?: ($occupation ?: '—') }}</strong>
            </div>
        </td>
    </tr>
</table>

<div style="margin-bottom:6px;font-size:9pt;">
    Both parties agree to the following terms and conditions:
</div>

{{-- Terms --}}
<table class="bordered" style="font-size:9pt;margin-bottom:8px;">
    <tr style="background:#2c3e50;color:#fff;">
        <th style="width:28px;text-align:center;">#</th>
        <th style="width:35%;">Term / الشرط</th>
        <th>Details / التفاصيل</th>
    </tr>
    <tr>
        <td class="text-center bold">1</td>
        <td>Monthly Salary</td>
        <td><strong>{{ $salary ?: '—' }}</strong></td>
    </tr>
    <tr>
        <td class="text-center bold">2</td>
        <td>Food &amp; Accommodation</td>
        <td>Provided by First Party as per KSA Labour Law</td>
    </tr>
    <tr>
        <td class="text-center bold">3</td>
        <td>Air Passage</td>
        <td>One-way passage provided; return on completion of contract</td>
    </tr>
    <tr>
        <td class="text-center bold">4</td>
        <td>Duty Hours</td>
        <td>As per KSA Labour Law (max 8 hrs/day, 48 hrs/week)</td>
    </tr>
    <tr>
        <td class="text-center bold">5</td>
        <td>Weekly Holiday</td>
        <td>One day rest per week (Friday)</td>
    </tr>
    <tr>
        <td class="text-center bold">6</td>
        <td>Annual Leave</td>
        <td>21 days paid leave per year as per KSA Labour Law</td>
    </tr>
    <tr>
        <td class="text-center bold">7</td>
        <td>Overtime &amp; Benefits</td>
        <td>Overtime as per KSA Labour Law (minimum 150% of basic rate)</td>
    </tr>
    <tr>
        <td class="text-center bold">8</td>
        <td>Medical Facilities</td>
        <td>Provided by First Party at no cost to employee</td>
    </tr>
    <tr>
        <td class="text-center bold">9</td>
        <td>Period of Contract</td>
        <td><strong>{{ $contract_period ?: $duration_stay_en ?: '2 Years' }}</strong>
            from date of arrival: {{ $arrival_date ?: '—' }}</td>
    </tr>
    <tr>
        <td class="text-center bold">10</td>
        <td>Repatriation</td>
        <td>Employee shall be repatriated upon completion or early termination of contract</td>
    </tr>
</table>

<div style="font-size:8.5pt;margin-bottom:12px;text-align:justify;">
    Both parties confirm that they have read, understood, and agreed to all the above terms and conditions.
    This agreement is governed by the Labour Law of the Kingdom of Saudi Arabia.
</div>

<table class="signature-row">
    <tr>
        <td class="sig-cell">
            <div class="sig-line">
                <strong>First Party Signature</strong><br>
                <small>{{ $employer_name ?: $agency_name }}</small><br>
                <small>Name &amp; Stamp</small>
            </div>
        </td>
        <td class="sig-cell">
            <div class="sig-line">
                <strong>Second Party Signature</strong><br>
                <small>{{ $full_name_en }}</small><br>
                <small>Passport: {{ $passport_no ?: '—' }}</small>
            </div>
        </td>
        <td class="sig-cell">
            <div class="sig-line">
                Date / التاريخ<br>
                {{ now()->format('d/m/Y') }}<br>&nbsp;
            </div>
        </td>
    </tr>
</table>

<div style="margin-top:10px;font-size:8pt;color:#777;border-top:1px dashed #ccc;padding-top:6px;">
    Submitted by: {{ $agency_name }}
    @if($agency_rl) &nbsp;·&nbsp; RL: {{ $agency_rl }} @endif
    @if($agency_license) &nbsp;·&nbsp; License: {{ $agency_license }} @endif
</div>

</div>
@endsection
