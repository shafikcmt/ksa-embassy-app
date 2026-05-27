@extends('prints.layouts.print')

@section('content')
<div class="page">

<div class="no-print" style="background:#1a1f2e;color:#fff;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
    <span style="font-size:.85rem;"><strong>Forwarding Letter</strong> — {{ $full_name_en }}</span>
    <div style="display:flex;gap:8px;">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;">&#128424; Print</button>
        <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;text-decoration:none;">&#8592; Back</a>
    </div>
</div>

{{-- Agency letterhead --}}
<div class="header-bar">
    <table>
        <tr>
            <td style="width:60%;">
                <div style="font-size:13pt;font-weight:bold;">{{ $agency_name }}</div>
                @if($agency_rl)<div style="font-size:9pt;">RL No: {{ $agency_rl }}</div>@endif
                @if($agency_license)<div style="font-size:9pt;">License: {{ $agency_license }}</div>@endif
                @if($agency_address)<div style="font-size:9pt;">{{ $agency_address }}</div>@endif
                @if($agency_phone || $agency_email)
                <div style="font-size:9pt;">{{ $agency_phone }} {{ $agency_email ? '· '.$agency_email : '' }}</div>
                @endif
            </td>
            <td style="width:40%;text-align:right;vertical-align:top;">
                <div style="font-size:9pt;">Date: <strong>{{ now()->format('d F Y') }}</strong></div>
                @if($visa_no)<div style="font-size:9pt;margin-top:4px;">Ref: Visa No. <strong>{{ $visa_no }}</strong></div>@endif
            </td>
        </tr>
    </table>
</div>

{{-- Addressee --}}
<div style="margin:16px 0 12px;">
    <div><strong>To,</strong></div>
    <div>The Chief of Consular Section,</div>
    <div>Embassy of the Kingdom of Saudi Arabia,</div>
    <div>Consular Section.</div>
</div>

{{-- Subject --}}
<div style="margin-bottom:12px;">
    <strong>Subject: Forwarding of Embassy Application File</strong>
    @if($visa_no) — Visa No. {{ $visa_no }} @endif
</div>

{{-- Body --}}
<div style="text-align:justify;line-height:1.7;">
    <p style="margin-bottom:10px;">
        Dear Sir / Madam,
    </p>
    <p style="margin-bottom:10px;">
        With due respect, we <strong>{{ $agency_name }}</strong> herewith forward the visa application
        and all required documents of the following employee for your kind consideration and
        necessary action:
    </p>

    <table class="bordered" style="margin:10px 0;">
        <tr>
            <td class="label" style="width:35%;">Employee Full Name</td>
            <td class="val">{{ $full_name_en }}
                @if($full_name_ar) &nbsp; <span class="ar">{{ $full_name_ar }}</span> @endif
            </td>
        </tr>
        <tr>
            <td class="label">Passport No. / Issue Date</td>
            <td class="val">{{ $passport_no ?: '—' }} &nbsp; / &nbsp; {{ $passport_issue_date ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Visa No. / Date</td>
            <td class="val">{{ $visa_no ?: '—' }} &nbsp; / &nbsp; {{ $visa_date ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Profession</td>
            <td class="val">{{ $profession_en ?: ($occupation ?: '—') }}
                @if($profession_ar) &nbsp; <span class="ar">{{ $profession_ar }}</span> @endif
            </td>
        </tr>
        <tr>
            <td class="label">Nationality</td>
            <td class="val">{{ $nationality }}</td>
        </tr>
        <tr>
            <td class="label">Religion</td>
            <td class="val">{{ $religion ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Sponsor Name / ID</td>
            <td class="val">{{ $sponsor_name ?: '—' }} &nbsp; / &nbsp; {{ $sponsor_id ?: '—' }}</td>
        </tr>
    </table>

    <p style="margin-bottom:10px;">
        We hereby declare that all the submitted documents are genuine and accurate to the best of
        our knowledge. We take full responsibility for the authenticity of this application.
    </p>
    <p style="margin-bottom:10px;">
        Kindly process the visa and return the stamped passport at your earliest convenience.
    </p>
    <p style="margin-bottom:30px;">
        Thanking you and hoping for your positive cooperation.
    </p>
    <p><strong>Yours Faithfully,</strong></p>
</div>

<table class="signature-row">
    <tr>
        <td class="sig-cell">
            <div class="sig-line">
                Authorized Signatory<br>
                <small>{{ $agency_name }}</small>
            </div>
        </td>
        <td class="sig-cell">
            <div class="sig-line">Agency Stamp<br>&nbsp;</div>
        </td>
        <td class="sig-cell">
            <div class="sig-line">Date<br>{{ now()->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

</div>
@endsection
