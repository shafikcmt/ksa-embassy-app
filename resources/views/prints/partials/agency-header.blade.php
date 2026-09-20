{{--
    Shared PDF header — centered agency branding pulled from the logged-in
    agency's own profile. Reusable across ERP PDF exports (built reusable here;
    only the Credit Voucher uses it for now — NOT retrofitted into the existing
    Daily/Monthly Summary / HR / Embassy List headers yet).

    Expects:
      $agency  — App\Models\Agency

    The logo is embedded as a base64 data URI (mPDF-safe — no remote URL / path
    pitfalls), guarded by the agency's print_logo toggle + file existence. When
    absent, the logo cell simply renders empty and the centered text stays
    centered. Encoding lives here so every caller stays a one-liner @include.

    Centered across the full width via a 3-cell row (logo left / text centered /
    empty balancing cell right), with a bottom rule closing the band.
--}}
@php
    $logoSrc = null;
    if ($agency->print_logo && $agency->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($agency->logo)) {
        $ext  = strtolower(pathinfo($agency->logo, PATHINFO_EXTENSION)) ?: 'png';
        $mime = $ext === 'jpg' ? 'jpeg' : $ext;
        $logoSrc = 'data:image/' . $mime . ';base64,'
            . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($agency->logo));
    }
@endphp
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:17%; vertical-align:middle; padding:5pt 6pt; text-align:left; border-bottom:0.8px solid #cbd5e1;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" style="max-width:20mm; max-height:14mm;">
            @endif
        </td>
        <td style="width:66%; vertical-align:middle; padding:6pt 2pt; text-align:center; border-bottom:0.8px solid #cbd5e1;">
            <div style="font-size:12pt; font-weight:bold; letter-spacing:0pt; line-height:1.0; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $agency->name }}</div>
            <div style="font-size:10.5pt; font-weight:bold; margin-top:3pt; color:#334155;">Recruiting Licence No. : {{ $agency->rl_number ?: '—' }}</div>
            @if($agency->address)
                <div style="font-size:10.5pt; font-style:italic; margin-top:2pt; color:#475569;">{{ $agency->address }}</div>
            @endif
        </td>
        <td style="width:17%; border-bottom:0.8px solid #cbd5e1;"></td>
    </tr>
</table>
