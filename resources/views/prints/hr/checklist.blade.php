@extends('prints.layouts.print')

@section('content')
<div class="page">

@if(empty($_pdf))
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
    <span style="font-size:.85rem;"><strong>Checklist / Attachment</strong> — {{ $full_name_en }}</span>
    <div style="display:flex;gap:8px;">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;">&#128424; Print</button>
        <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;text-decoration:none;">&#8592; Back</a>
    </div>
</div>
@endif

{{-- Arabic heading --}}
<div class="text-center" style="margin-bottom:8px;">
    <div class="ar" style="font-size:14pt;font-weight:bold;">قائمة المرفقات والمستندات المطلوبة</div>
    <div style="font-size:11pt;font-weight:bold;margin-top:2px;">ATTACHMENT &amp; DOCUMENT CHECKLIST</div>
</div>

{{-- Candidate summary --}}
<table class="bordered" style="margin-bottom:8px;font-size:9pt;">
    <tr>
        <td class="label" style="width:22%;">Candidate Name</td>
        <td class="val" style="width:28%;">{{ $full_name_en }}</td>
        <td class="label" style="width:22%;">Passport No.</td>
        <td class="val" style="width:28%;">{{ $passport_no ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Visa No.</td>
        <td class="val">{{ $visa_no ?: '—' }}</td>
        <td class="label">Nationality</td>
        <td class="val">{{ $nationality }}</td>
    </tr>
    <tr>
        <td class="label">Agency</td>
        <td class="val">{{ $agency_name }}</td>
        <td class="label">RL / License</td>
        <td class="val">{{ $agency_rl }} / {{ $agency_license }}</td>
    </tr>
</table>

{{-- Checklist table --}}
<table class="bordered" style="font-size:9pt;">
    <thead>
        <tr style="background:#2c3e50;color:#fff;">
            <th style="width:32%;">Document / المستند</th>
            <th style="width:24%;">Value / القيمة</th>
            <th style="width:12%;text-align:center;">Agency ✓</th>
            <th style="width:12%;text-align:center;">Port ✓</th>
            <th style="width:20%;">Notes / ملاحظات</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Application / File Number</td>
            <td class="val">{{ $file_number ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr style="background:#fafafa;">
            <td>Visa No.</td>
            <td class="val">{{ $visa_no ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr>
            <td>Passport Holder Name</td>
            <td class="val">{{ $full_name_en }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr style="background:#fafafa;">
            <td>Passport Number</td>
            <td class="val">{{ $passport_no ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr>
            <td>Passport Validity</td>
            <td class="val">{{ $passport_expiry_date ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr style="background:#fafafa;">
            <td>Age</td>
            <td class="val">{{ $age }} years</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr>
            <td>Sex</td>
            <td class="val">{{ $gender }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr style="background:#fafafa;">
            <td>Musaned No.</td>
            <td class="val">{{ $musaned_no ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr>
            <td>Al-Wakala No.</td>
            <td class="val">{{ $wakala_no ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr style="background:#fafafa;">
            <td>Medical Report</td>
            <td class="val">{{ $medical_fit ?: '—' }}
                @if($medical_date) ({{ $medical_date }}) @endif
            </td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td>{{ $medical_center ?: '' }}</td>
        </tr>
        <tr>
            <td>Police Clearance (PC)</td>
            <td class="val">{{ $pc_number ?: '—' }}
                @if($pc_expiry_date) exp. {{ $pc_expiry_date }} @endif
            </td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td>{{ $pc_country ?: '' }}</td>
        </tr>
        <tr style="background:#fafafa;">
            <td>License Type</td>
            <td class="val">{{ $license_type ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr>
            <td>Profession / Occupation</td>
            <td class="val">{{ $profession_en ?: ($occupation ?: '—') }}
                @if($profession_ar) <span class="ar"> / {{ $profession_ar }}</span> @endif
            </td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr style="background:#fafafa;">
            <td>Experience Certificate</td>
            <td class="val">{{ $qualification_en ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
        <tr>
            <td>Fingerprint</td>
            <td class="val">{{ $fingerprint ?: '—' }}</td>
            <td style="text-align:center;">&#9744;</td>
            <td style="text-align:center;">&#9744;</td>
            <td></td>
        </tr>
    </tbody>
</table>

{{-- PC QR Code if available --}}
@if($pc_qr_code)
<div style="margin-top:8px;text-align:left;">
    <div style="font-size:7.5pt;color:#666;margin-bottom:2px;">Police Clearance QR Code</div>
    <barcode code="{{ $pc_qr_code }}" type="QR" size="0.8" />
    <div style="font-size:7pt;color:#888;">{{ $pc_number }}</div>
</div>
@endif

<table class="signature-row">
    <tr>
        <td class="sig-cell">
            <div class="sig-line">
                Verified By (Agency)<br>
                <small>{{ $agency_name }}</small>
            </div>
        </td>
        <td class="sig-cell">
            <div class="sig-line">Agency Stamp<br>&nbsp;</div>
        </td>
        <td class="sig-cell">
            <div class="sig-line">Date / التاريخ<br>{{ now()->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

</div>
@endsection
