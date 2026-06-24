<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#1e293b;background:#f1f5f9;padding:0;margin:0;">
<div style="max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">
    <div style="background:#dc2626;padding:20px 24px;">
        <div style="color:#fff;font-size:1.1rem;font-weight:700;">VisaDeskPro</div>
        <div style="color:#fca5a5;font-size:.82rem;">Subscription Expired</div>
    </div>
    <div style="padding:24px;">
        <p>Dear <strong>{{ $agency->name }}</strong>,</p>
        <p>Your subscription has <strong style="color:#dc2626;">expired</strong>. You can still view your existing data, but creating new records and generating PDFs has been disabled.</p>

        <div style="background:#fee2e2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:4px;margin:16px 0;">
            <strong>Plan:</strong> {{ $subscription->plan->name ?? '—' }}<br>
            <strong>Expired on:</strong> {{ $subscription->end_date->format('d M Y') }}
        </div>

        <p>Please contact your system administrator to renew your subscription and restore full access.</p>
        <p style="color:#64748b;font-size:.82rem;">This is an automated message. Please do not reply to this email.</p>
    </div>
    <div style="background:#f8fafc;padding:12px 24px;border-top:1px solid #e2e8f0;font-size:.75rem;color:#94a3b8;">
        VisaDeskPro — Agency, HR &amp; Visa Document Management System
    </div>
</div>
</body>
</html>
