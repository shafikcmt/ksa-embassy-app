<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#1e293b;background:#f1f5f9;padding:0;margin:0;">
<div style="max-width:640px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">
    <div style="background:#0f172a;padding:20px 24px;">
        <div style="color:#fff;font-size:1.1rem;font-weight:700;">KSA Embassy File System</div>
        <div style="color:#94a3b8;font-size:.82rem;">Passport Expiry Alert</div>
    </div>
    <div style="padding:24px;">
        <p>Dear <strong>{{ $agency->name }}</strong>,</p>
        <p>The following <strong>{{ $passports->count() }} candidate passport(s)</strong> are expiring within the next 30 days. Please take action to renew or update the records.</p>

        <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:.82rem;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="padding:8px 10px;text-align:left;border:1px solid #e2e8f0;">Candidate Name</th>
                    <th style="padding:8px 10px;text-align:left;border:1px solid #e2e8f0;">Passport No.</th>
                    <th style="padding:8px 10px;text-align:left;border:1px solid #e2e8f0;">Expiry Date</th>
                    <th style="padding:8px 10px;text-align:left;border:1px solid #e2e8f0;">Days Left</th>
                </tr>
            </thead>
            <tbody>
                @foreach($passports as $passport)
                @php
                    $days = (int) now()->diffInDays($passport->expiry_date, false);
                    $color = $days <= 7 ? '#dc2626' : ($days <= 14 ? '#d97706' : '#1e293b');
                @endphp
                <tr>
                    <td style="padding:8px 10px;border:1px solid #e2e8f0;">{{ $passport->hrProfile->full_name_en ?? '—' }}</td>
                    <td style="padding:8px 10px;border:1px solid #e2e8f0;">{{ $passport->passport_number ?? '—' }}</td>
                    <td style="padding:8px 10px;border:1px solid #e2e8f0;">{{ $passport->expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td style="padding:8px 10px;border:1px solid #e2e8f0;font-weight:700;color:{{ $color }};">{{ $days }} days</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p>Please log in to the system to update or renew the passports before expiry.</p>
        <p style="color:#64748b;font-size:.82rem;">This is an automated message. Please do not reply to this email.</p>
    </div>
    <div style="background:#f8fafc;padding:12px 24px;border-top:1px solid #e2e8f0;font-size:.75rem;color:#94a3b8;">
        KSA Embassy File Management System
    </div>
</div>
</body>
</html>
