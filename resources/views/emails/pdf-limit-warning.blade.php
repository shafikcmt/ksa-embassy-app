<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#1e293b;background:#f1f5f9;padding:0;margin:0;">
<div style="max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">
    <div style="background:#7c3aed;padding:20px 24px;">
        <div style="color:#fff;font-size:1.1rem;font-weight:700;">VisaDeskPro</div>
        <div style="color:#ddd6fe;font-size:.82rem;">PDF Generation Limit Warning</div>
    </div>
    <div style="padding:24px;">
        <p>Dear <strong>{{ $agency->name }}</strong>,</p>
        <p>You have used <strong style="color:#7c3aed;">{{ $percentUsed }}%</strong> of your monthly PDF generation limit.</p>

        <div style="background:#ede9fe;border-left:4px solid #7c3aed;padding:12px 16px;border-radius:4px;margin:16px 0;">
            <strong>Plan:</strong> {{ $subscription->plan->name ?? '—' }}<br>
            <strong>PDFs used this month:</strong> {{ $used }} / {{ $limit }}<br>
            <strong>Usage:</strong> {{ $percentUsed }}%
        </div>

        @if($percentUsed >= 100)
        <p style="color:#dc2626;">Your PDF limit has been reached. You will not be able to generate new PDFs until next month or until your plan is upgraded.</p>
        @else
        <p>You are approaching your monthly PDF limit. Consider upgrading your plan if you need to generate more documents this month.</p>
        @endif

        <p style="color:#64748b;font-size:.82rem;">This is an automated message. Please do not reply to this email.</p>
    </div>
    <div style="background:#f8fafc;padding:12px 24px;border-top:1px solid #e2e8f0;font-size:.75rem;color:#94a3b8;">
        VisaDeskPro — Agency, HR &amp; Visa Document Management System
    </div>
</div>
</body>
</html>
