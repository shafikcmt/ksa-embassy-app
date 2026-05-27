@extends('prints.layouts.print')

@section('content')
<div class="page">

{{-- Screen toolbar --}}
<div class="no-print" style="background:#1a1f2e;color:#fff;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
    <span style="font-size:.85rem;"><strong>Application Form</strong> — {{ $full_name_en }}</span>
    <div style="display:flex;gap:8px;">
        <button onclick="window.print()" style="background:#2563eb;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;">&#128424; Print</button>
        <a href="{{ url()->previous() }}" style="background:#374151;color:#fff;border:none;padding:6px 16px;border-radius:4px;cursor:pointer;font-size:.8rem;text-decoration:none;">&#8592; Back</a>
    </div>
</div>

{{-- Header --}}
<div class="header-bar text-center">
    <div style="font-size:13pt;font-weight:bold;letter-spacing:1px;">EMBASSY OF THE KINGDOM OF SAUDI ARABIA</div>
    <div style="font-size:10pt;margin-top:2px;">Consular Section — Visa Application Form</div>
    <div class="ar" style="font-size:11pt;margin-top:2px;">سفارة المملكة العربية السعودية — قسم القنصلية</div>
</div>

<table style="margin-bottom:6px;">
    <tr>
        <td style="width:80px;vertical-align:top;">
            <div class="photo-box">Photo<br>صورة</div>
        </td>
        <td style="padding-left:10px;">
            <table>
                <tr>
                    <td style="width:50%;padding-right:8px;">
                        <div class="field-row">
                            <span class="field-label">Application No / File No:</span>
                            <span class="field-val">{{ $file_number }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Full Name (EN):</span>
                            <span class="field-val">{{ $full_name_en }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Full Name (AR):</span>
                            <span class="field-val ar">{{ $full_name_ar ?: '—' }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Father's Name:</span>
                            <span class="field-val">{{ $father_name ?: '—' }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Mother's Name:</span>
                            <span class="field-val">{{ $mother_name ?: '—' }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Date of Birth:</span>
                            <span class="field-val">{{ $date_of_birth }}  (Age: {{ $age }})</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Place of Birth:</span>
                            <span class="field-val">{{ $place_of_birth ?: '—' }}</span>
                        </div>
                    </td>
                    <td style="width:50%;">
                        <div class="field-row">
                            <span class="field-label">Present Nationality:</span>
                            <span class="field-val">{{ $nationality }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Previous Nationality:</span>
                            <span class="field-val">{{ $previous_nationality ?: '—' }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Sex:</span>
                            <span class="field-val">{{ $gender }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Marital Status:</span>
                            <span class="field-val">{{ $marital_status ?: '—' }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Religion:</span>
                            <span class="field-val">{{ $religion ?: '—' }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Phone / Home Address:</span>
                            <span class="field-val">{{ $phone ?: '—' }}</span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Agent:</span>
                            <span class="field-val">{{ $agent_name ?: '—' }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width:110px;vertical-align:top;text-align:center;padding-left:6px;">
            @if($passport_no)
            <div style="font-size:7pt;color:#666;margin-bottom:2px;">Passport No Barcode</div>
            <barcode code="{{ $passport_no }}" type="C128" size="0.6" height="0.35in" />
            <div style="font-size:7.5pt;font-weight:bold;margin-top:2px;">{{ $passport_no }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- Passport Section --}}
<div class="section-title">Passport Information &nbsp;<span class="ar" style="font-size:9pt;">معلومات جواز السفر</span></div>
<table class="bordered" style="margin-bottom:6px;">
    <tr>
        <td class="label">Passport No.</td>
        <td class="val">{{ $passport_no ?: '—' }}</td>
        <td class="label">Type</td>
        <td class="val">{{ $passport_type ?: '—' }}</td>
        <td class="label">Issue Place</td>
        <td class="val">{{ $passport_issue_place ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Issue Date</td>
        <td class="val">{{ $passport_issue_date ?: '—' }}</td>
        <td class="label">Expiry Date</td>
        <td class="val">{{ $passport_expiry_date ?: '—' }}</td>
        <td class="label">Validity (Yrs)</td>
        <td class="val">{{ $passport_validity_years ?: '—' }}</td>
    </tr>
</table>

{{-- Visa Section --}}
<div class="section-title">Visa Information &nbsp;<span class="ar" style="font-size:9pt;">معلومات التأشيرة</span></div>
<table class="bordered" style="margin-bottom:6px;">
    <tr>
        <td class="label">Visa No.</td>
        <td class="val">{{ $visa_no ?: '—' }}</td>
        <td class="label">Visa Date</td>
        <td class="val">{{ $visa_date ?: '—' }}</td>
        <td class="label">Expiry</td>
        <td class="val">{{ $visa_expiry_date ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Issue Place (EN)</td>
        <td class="val">{{ $visa_issue_place_en ?: '—' }}</td>
        <td class="label">Issue Place (AR)</td>
        <td class="val ar">{{ $visa_issue_place_ar ?: '—' }}</td>
        <td class="label">Border No.</td>
        <td class="val">{{ $border_number ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Sponsor Name</td>
        <td class="val" colspan="3">{{ $sponsor_name ?: '—' }}</td>
        <td class="label">Sponsor ID</td>
        <td class="val">{{ $sponsor_id ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Profession (EN)</td>
        <td class="val">{{ $profession_en ?: '—' }}</td>
        <td class="label">Profession (AR)</td>
        <td class="val ar">{{ $profession_ar ?: '—' }}</td>
        <td class="label">Travel Purpose</td>
        <td class="val">{{ $travel_purpose ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Musaned No.</td>
        <td class="val">{{ $musaned_no ?: '—' }}</td>
        <td class="label">Wakala No.</td>
        <td class="val">{{ $wakala_no ?: '—' }}</td>
        <td class="label">Qualification</td>
        <td class="val">{{ $qualification_en ?: '—' }}</td>
    </tr>
</table>

{{-- Duration / Travel --}}
<div class="section-title">Duration of Stay &amp; Travel &nbsp;<span class="ar" style="font-size:9pt;">مدة الإقامة والسفر</span></div>
<table class="bordered" style="margin-bottom:6px;">
    <tr>
        <td class="label">Duration of Stay (EN)</td>
        <td class="val">{{ $duration_stay_en ?: '—' }}</td>
        <td class="label">Duration (AR)</td>
        <td class="val ar">{{ $duration_stay_ar ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Destination City</td>
        <td class="val">{{ $destination_city ?: ($work_city ?: '—') }}</td>
        <td class="label">Carrier</td>
        <td class="val">{{ $carrier ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Date of Arrival</td>
        <td class="val">{{ $arrival_date ?: '—' }}</td>
        <td class="label">Date of Departure</td>
        <td class="val">{{ $departure_date ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Company/Individual in KSA</td>
        <td class="val">{{ $employer_name ?: ($sponsor_name ?: '—') }}</td>
        <td class="label">Payment Mode</td>
        <td class="val">{{ $payment_mode ?: '—' }}</td>
    </tr>
</table>

{{-- Agency / Signature --}}
<div class="section-title">Submitted By &nbsp;<span class="ar" style="font-size:9pt;">مقدمة من</span></div>
<table style="margin-bottom:4px;">
    <tr>
        <td style="width:60%;">
            <div class="field-row"><span class="field-label">Agency Name:</span> <span class="field-val">{{ $agency_name }}</span></div>
            <div class="field-row"><span class="field-label">RL No / License:</span> <span class="field-val">{{ $agency_rl }} / {{ $agency_license }}</span></div>
            <div class="field-row"><span class="field-label">Phone / Email:</span> <span class="field-val">{{ $agency_phone }} {{ $agency_email ? '· '.$agency_email : '' }}</span></div>
        </td>
        <td style="width:40%;border:1px solid #ccc;padding:6px;">
            <div style="font-size:8pt;color:#777;">Official Use Only / للاستخدام الرسمي فقط</div>
            <div style="height:30px;"></div>
            <div style="border-top:1px solid #ccc;font-size:7.5pt;padding-top:3px;">Stamp / Visa Officer</div>
        </td>
    </tr>
</table>

<table class="signature-row">
    <tr>
        <td class="sig-cell"><div class="sig-line">Applicant Signature<br>توقيع المتقدم</div></td>
        <td class="sig-cell"><div class="sig-line">Agency Signature &amp; Stamp<br>توقيع الوكالة وختمها</div></td>
        <td class="sig-cell"><div class="sig-line">Date / التاريخ<br>{{ now()->format('d/m/Y') }}</div></td>
    </tr>
</table>

</div>
@endsection
