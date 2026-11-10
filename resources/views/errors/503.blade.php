<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="30">
    <title>Updating — {{ config('app.name', 'VisaDeskPro') }}</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#0f172a;
            background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 50%,#eef2ff 100%)}
        .card{width:100%;max-width:520px;text-align:center;background:#fff;border:1px solid #e2e8f0;
            border-radius:20px;padding:44px 32px;box-shadow:0 20px 50px -20px rgba(30,64,175,.25)}
        .icon{width:76px;height:76px;margin:0 auto 22px;border-radius:50%;background:#eff6ff;
            display:flex;align-items:center;justify-content:center}
        .icon svg{width:38px;height:38px;color:#2563eb;animation:spin 3s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
        h1{font-size:1.6rem;font-weight:700;margin-bottom:10px}
        p{color:#475569;line-height:1.6;font-size:1rem}
        .badge{display:inline-flex;align-items:center;gap:8px;margin-top:22px;padding:8px 16px;border-radius:999px;
            background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:.85rem;font-weight:600}
        .dot{width:8px;height:8px;border-radius:50%;background:#f59e0b;animation:pulse 1.4s ease-in-out infinite}
        @keyframes pulse{50%{opacity:.25}}
        .bn{margin-top:18px;padding-top:18px;border-top:1px dashed #e2e8f0;font-size:.95rem}
        small{display:block;margin-top:20px;color:#94a3b8;font-size:.8rem}
    </style>
</head>
<body>
    <main class="card" role="alert">
        <div class="icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>
            </svg>
        </div>
        <h1>We’re updating the system</h1>
        <p>{{ config('app.name', 'VisaDeskPro') }} is undergoing scheduled maintenance to bring you new features and improvements. Please don’t close your work — we’ll be back in a few minutes.</p>
        <div class="badge"><span class="dot"></span> Update in progress</div>
        <p class="bn">সাইটে আপডেট/মেইনটেন্যান্স চলছে। অনুগ্রহ করে কয়েক মিনিট পরে আবার চেষ্টা করুন।</p>
        <small>This page refreshes automatically every 30 seconds.</small>
    </main>
</body>
</html>
