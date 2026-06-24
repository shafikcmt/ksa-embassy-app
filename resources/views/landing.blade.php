<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="VisaDeskPro — a secure SaaS platform to manage HR records, agency applications, visa/application documents, payment requests and print-ready PDFs from one dashboard.">
<title>{{ config('app.name', 'VisaDeskPro') }} — Smart Agency &amp; Visa Document Management Platform</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
<style>
  :root{
    --indigo:#4f46e5; --indigo-d:#4338ca; --indigo-l:#eef2ff;
    --emerald:#10b981; --emerald-d:#059669; --emerald-l:#ecfdf5;
    --ink:#0f172a; --slate:#475569; --muted:#64748b;
    --line:#e2e8f0; --bg:#ffffff; --soft:#f8fafc;
    --radius:16px; --shadow:0 10px 30px -12px rgba(15,23,42,.18);
    --shadow-sm:0 2px 10px -4px rgba(15,23,42,.12);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html{scroll-behavior:smooth}
  body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--ink);background:var(--bg);line-height:1.6;-webkit-font-smoothing:antialiased}
  a{text-decoration:none;color:inherit}
  img,svg{display:block}
  .wrap{max-width:1200px;margin:0 auto;padding:0 20px}
  .sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}

  /* Buttons */
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-weight:600;font-size:15px;padding:12px 22px;border-radius:12px;border:1px solid transparent;cursor:pointer;transition:.18s ease;white-space:nowrap}
  .btn-primary{background:var(--indigo);color:#fff;box-shadow:0 8px 20px -8px rgba(79,70,229,.6)}
  .btn-primary:hover{background:var(--indigo-d);transform:translateY(-1px)}
  .btn-ghost{background:#fff;color:var(--ink);border-color:var(--line)}
  .btn-ghost:hover{border-color:#cbd5e1;background:var(--soft)}
  .btn-light{background:rgba(255,255,255,.14);color:#fff;border-color:rgba(255,255,255,.3)}
  .btn-light:hover{background:rgba(255,255,255,.24)}
  .btn-white{background:#fff;color:var(--indigo-d)}
  .btn-white:hover{transform:translateY(-1px);box-shadow:0 10px 24px -10px rgba(0,0,0,.4)}
  .btn-sm{padding:9px 16px;font-size:14px}

  /* Navbar */
  header.nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.85);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
  .nav-in{display:flex;align-items:center;justify-content:space-between;height:68px}
  .brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:18px;letter-spacing:-.02em}
  .brand .logo{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--indigo),var(--emerald));display:grid;place-items:center;color:#fff;flex:0 0 auto}
  .brand .pro{color:var(--indigo)}
  footer .brand .pro{color:#818cf8}
  .nav-links{display:flex;align-items:center;gap:28px}
  .nav-links a{font-size:15px;color:var(--slate);font-weight:500;transition:.15s}
  .nav-links a:hover{color:var(--indigo)}
  .nav-cta{display:flex;align-items:center;gap:10px}
  .hamburger{display:none;background:none;border:0;cursor:pointer;padding:8px}
  .hamburger span{display:block;width:24px;height:2px;background:var(--ink);margin:5px 0;border-radius:2px;transition:.2s}
  #mnav{display:none}
  #mnav:checked ~ .mobile-menu{display:block}
  .mobile-menu{display:none;border-top:1px solid var(--line);background:#fff;padding:14px 20px 22px}
  .mobile-menu a{display:block;padding:11px 4px;color:var(--slate);font-weight:500;border-bottom:1px solid var(--soft)}
  .mobile-menu .mrow{display:flex;gap:10px;margin-top:14px}
  .mobile-menu .mrow .btn{flex:1}

  /* Hero */
  .hero{position:relative;overflow:hidden;background:
     radial-gradient(1100px 500px at 85% -10%,rgba(16,185,129,.12),transparent 60%),
     radial-gradient(900px 500px at 5% 0%,rgba(79,70,229,.12),transparent 55%),
     linear-gradient(180deg,#fff, var(--soft))}
  .hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:54px;align-items:center;padding:78px 0 84px}
  .eyebrow{display:inline-flex;align-items:center;gap:8px;background:var(--indigo-l);color:var(--indigo-d);font-weight:600;font-size:13px;padding:7px 14px;border-radius:999px;margin-bottom:20px}
  .eyebrow .dot{width:7px;height:7px;border-radius:50%;background:var(--emerald)}
  h1.hero-title{font-size:48px;line-height:1.08;letter-spacing:-.03em;font-weight:800;margin-bottom:18px}
  h1.hero-title .grad{background:linear-gradient(120deg,var(--indigo),var(--emerald));-webkit-background-clip:text;background-clip:text;color:transparent}
  .hero p.lead{font-size:18px;color:var(--slate);max-width:560px;margin-bottom:28px}
  .hero-cta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:26px}
  .hero-mini{display:flex;flex-wrap:wrap;gap:18px;color:var(--muted);font-size:14px;font-weight:500}
  .hero-mini span{display:inline-flex;align-items:center;gap:7px}
  .check{color:var(--emerald);flex:0 0 auto}

  /* Hero mockup */
  .mock{position:relative}
  .mock-card{background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow);overflow:hidden}
  .mock-top{display:flex;align-items:center;gap:8px;padding:14px 16px;border-bottom:1px solid var(--line);background:var(--soft)}
  .mock-top i{width:11px;height:11px;border-radius:50%;background:#cbd5e1;display:block}
  .mock-top i:nth-child(1){background:#f87171}.mock-top i:nth-child(2){background:#fbbf24}.mock-top i:nth-child(3){background:#34d399}
  .mock-top b{margin-left:8px;font-size:13px;color:var(--muted);font-weight:600}
  .mock-body{padding:18px}
  .doc-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px}
  .doc-photo{width:52px;height:64px;border:1px solid var(--line);border-radius:6px;display:grid;place-items:center;font-size:9px;color:var(--muted);text-align:center;background:var(--soft)}
  .barcode{height:34px;width:150px;margin:0 auto 3px;background:repeating-linear-gradient(90deg,#0f172a 0 2px,#fff 2px 4px,#0f172a 4px 5px,#fff 5px 9px,#0f172a 9px 12px,#fff 12px 14px)}
  .barcode-no{text-align:center;font-size:11px;font-weight:700;letter-spacing:1px;color:var(--ink)}
  .doc-title{text-align:right;font-size:11px;font-weight:700;color:var(--indigo-d);line-height:1.3}
  .doc-rows{display:grid;gap:7px}
  .drow{display:flex;justify-content:space-between;gap:10px;font-size:11px;border:1px solid var(--line);border-radius:8px;padding:8px 10px}
  .drow span:first-child{color:var(--muted)}
  .drow span:last-child{font-weight:600}
  .mock-foot{display:flex;align-items:center;gap:8px;margin-top:14px;padding-top:13px;border-top:1px dashed var(--line)}
  .pill{font-size:11px;font-weight:600;padding:5px 10px;border-radius:999px;background:var(--emerald-l);color:var(--emerald-d)}
  .pill.indigo{background:var(--indigo-l);color:var(--indigo-d)}
  .float{position:absolute;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:var(--shadow);padding:12px 14px;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600}
  .float .ic{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;color:#fff;flex:0 0 auto}
  .float.f1{top:-22px;left:-26px}
  .float.f2{bottom:-24px;right:-22px}
  .float small{display:block;font-weight:500;color:var(--muted);font-size:11px}

  /* Stats */
  .stats{margin-top:-34px;position:relative;z-index:5;padding-bottom:10px}
  .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:var(--shadow-sm);display:flex;gap:13px;align-items:center}
  .stat .ic{width:42px;height:42px;border-radius:11px;display:grid;place-items:center;color:#fff;flex:0 0 auto}
  .stat b{display:block;font-size:15px;line-height:1.25}
  .stat small{color:var(--muted);font-size:12.5px}

  /* Section shell */
  section{padding:84px 0}
  .sec-head{text-align:center;max-width:680px;margin:0 auto 50px}
  .sec-tag{font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--indigo)}
  .sec-head h2{font-size:36px;letter-spacing:-.02em;font-weight:800;margin:10px 0 12px}
  .sec-head p{color:var(--slate);font-size:17px}
  .soft{background:var(--soft)}

  /* Features */
  .feat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
  .card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:24px;transition:.2s}
  .card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:#dbe1ea}
  .card .ic{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;color:#fff;margin-bottom:15px}
  .card h3{font-size:16.5px;font-weight:700;margin-bottom:7px}
  .card p{color:var(--slate);font-size:14px}
  .ic-indigo{background:linear-gradient(135deg,#6366f1,#4338ca)}
  .ic-emerald{background:linear-gradient(135deg,#34d399,#059669)}
  .ic-amber{background:linear-gradient(135deg,#fbbf24,#d97706)}
  .ic-sky{background:linear-gradient(135deg,#38bdf8,#0284c7)}

  /* Documents */
  .doc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
  .doccard{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:24px;position:relative;overflow:hidden;transition:.2s}
  .doccard:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
  .doccard .num{position:absolute;top:14px;right:18px;font-size:46px;font-weight:800;color:var(--soft)}
  .doccard .ic{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;color:#fff;margin-bottom:16px}
  .doccard h3{font-size:16px;font-weight:700;margin-bottom:8px;position:relative}
  .doccard p{color:var(--slate);font-size:13.5px;margin-bottom:16px;position:relative}
  .tag{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--emerald-d);background:var(--emerald-l);padding:5px 10px;border-radius:999px}

  /* How it works */
  .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;position:relative}
  .step{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:26px 22px;position:relative}
  .step .n{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--indigo),var(--emerald));color:#fff;font-weight:800;display:grid;place-items:center;font-size:18px;margin-bottom:16px}
  .step h3{font-size:16px;font-weight:700;margin-bottom:7px}
  .step p{color:var(--slate);font-size:14px}
  .step .arrow{position:absolute;top:42px;right:-14px;color:#cbd5e1;z-index:2}

  /* Before / After */
  .ba{display:grid;grid-template-columns:1fr 1fr;gap:22px}
  .ba-col{border-radius:var(--radius);padding:30px;border:1px solid var(--line)}
  .ba-before{background:#fff}
  .ba-after{background:linear-gradient(160deg,var(--indigo-l),#fff);border-color:#dfe3ff}
  .ba-col h3{font-size:19px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:10px}
  .ba-col ul{list-style:none;display:grid;gap:13px}
  .ba-col li{display:flex;gap:11px;align-items:flex-start;font-size:15px;color:var(--slate)}
  .x{color:#ef4444;flex:0 0 auto;margin-top:2px}
  .v{color:var(--emerald);flex:0 0 auto;margin-top:2px}
  .badge-bad{font-size:12px;font-weight:700;color:#b91c1c;background:#fee2e2;padding:4px 11px;border-radius:999px}
  .badge-good{font-size:12px;font-weight:700;color:var(--emerald-d);background:var(--emerald-l);padding:4px 11px;border-radius:999px}

  /* CTA */
  .cta{background:linear-gradient(125deg,var(--indigo-d),var(--indigo) 55%,var(--emerald));border-radius:26px;padding:60px 40px;text-align:center;color:#fff;position:relative;overflow:hidden}
  .cta:after{content:"";position:absolute;inset:0;background:radial-gradient(600px 240px at 80% 0,rgba(255,255,255,.18),transparent 60%)}
  .cta h2{font-size:34px;font-weight:800;letter-spacing:-.02em;margin-bottom:12px;position:relative}
  .cta p{font-size:17px;max-width:620px;margin:0 auto 28px;color:rgba(255,255,255,.9);position:relative}
  .cta .hero-cta{justify-content:center;position:relative}

  /* Footer */
  footer{background:#0b1220;color:#cbd5e1;padding:60px 0 26px}
  .foot-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:36px;margin-bottom:40px}
  footer .brand{color:#fff;margin-bottom:14px}
  footer p.desc{font-size:14px;color:#94a3b8;max-width:300px}
  .foot-col h4{font-size:14px;font-weight:700;color:#fff;margin-bottom:16px;letter-spacing:.02em}
  .foot-col a,.foot-col span{display:block;font-size:14px;color:#94a3b8;padding:5px 0;transition:.15s}
  .foot-col a:hover{color:#fff}
  .foot-bottom{border-top:1px solid #1e293b;padding-top:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:13px;color:#94a3b8}

  /* Responsive */
  @media(max-width:1000px){
    .feat-grid,.doc-grid,.steps{grid-template-columns:repeat(2,1fr)}
    .stats-grid{grid-template-columns:repeat(2,1fr)}
    .step .arrow{display:none}
    .foot-grid{grid-template-columns:1fr 1fr}
  }
  @media(max-width:860px){
    .nav-links,.nav-cta{display:none}
    .hamburger{display:block}
    .hero-grid{grid-template-columns:1fr;gap:48px;padding:54px 0 64px}
    h1.hero-title{font-size:36px}
    .mock{max-width:420px;margin:0 auto}
    .ba{grid-template-columns:1fr}
  }
  @media(max-width:560px){
    .feat-grid,.doc-grid,.steps,.stats-grid{grid-template-columns:1fr}
    .sec-head h2,.cta h2{font-size:27px}
    h1.hero-title{font-size:31px}
    .hero p.lead{font-size:16px}
    section{padding:60px 0}
    .cta{padding:44px 22px}
    .foot-grid{grid-template-columns:1fr;gap:26px}
    .float{display:none}
  }
</style>
</head>
<body>

@php
  // Resolve a safe destination for already-authenticated visitors.
  $dashUrl = url('/dashboard');
@endphp

{{-- ===================== NAVBAR ===================== --}}
<header class="nav">
  <div class="wrap nav-in">
    <a href="#top" class="brand">
      <span class="logo">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
      </span>
      VisaDesk<span class="pro">Pro</span>
    </a>

    <nav class="nav-links">
      <a href="#features">Features</a>
      <a href="#how">How It Works</a>
      <a href="#documents">Documents</a>
      <a href="#get-app">Get App</a>
      <a href="#contact">Contact</a>
    </nav>

    <div class="nav-cta">
      @auth
        <a href="{{ $dashUrl }}" class="btn btn-primary btn-sm">Go to Dashboard</a>
      @else
        <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Login</a>
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Get Started</a>
        @endif
      @endauth
    </div>

    <input type="checkbox" id="mnav">
    <label for="mnav" class="hamburger" aria-label="Menu"><span></span><span></span><span></span></label>
    <div class="mobile-menu">
      <a href="#features">Features</a>
      <a href="#how">How It Works</a>
      <a href="#documents">Documents</a>
      <a href="#get-app">Get App</a>
      <a href="#contact">Contact</a>
      <div class="mrow">
        @auth
          <a href="{{ $dashUrl }}" class="btn btn-primary">Go to Dashboard</a>
        @else
          <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
          @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
          @endif
        @endauth
      </div>
    </div>
  </div>
</header>

<a id="top"></a>

{{-- ===================== HERO ===================== --}}
<section class="hero" style="padding:0">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow"><span class="dot"></span> Built for recruiting &amp; manpower agencies</span>
      <h1 class="hero-title"><span class="grad">VisaDeskPro</span> — Smart Agency &amp; Visa Document Management Platform</h1>
      <p class="lead">Manage HR records, agency applications, embassy/application formats, payment requests, and print-ready documents from one secure SaaS dashboard.</p>
      <div class="hero-cta">
        @auth
          <a href="{{ $dashUrl }}" class="btn btn-primary">Login to Dashboard</a>
        @else
          @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn btn-primary">Get Started
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
          @endif
          <a href="{{ route('login') }}" class="btn btn-ghost">Login to Dashboard</a>
        @endauth
        <a href="#features" class="btn btn-ghost">View Features</a>
      </div>
      <div class="hero-mini">
        <span><svg class="check" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> 4 documents, one input</span>
        <span><svg class="check" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Auto barcode</span>
        <span><svg class="check" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> A4 print-ready PDF</span>
      </div>
    </div>

    {{-- Hero mockup --}}
    <div class="mock">
      <div class="float f1">
        <span class="ic ic-emerald">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a1 1 0 0 1 1-1h2M4 17v2a1 1 0 0 0 1 1h2M20 7V5a1 1 0 0 0-1-1h-2M20 17v2a1 1 0 0 1-1 1h-2"/><path d="M7 8v8M10 8v8M13 8v8M16 8v8"/></svg>
        </span>
        <div>Auto Barcode<small>VS &amp; Passport No.</small></div>
      </div>
      <div class="float f2">
        <span class="ic ic-indigo">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        </span>
        <div>4 Documents<small>Ready to print</small></div>
      </div>

      <div class="mock-card">
        <div class="mock-top"><i></i><i></i><i></i><b>Embassy Application — Preview</b></div>
        <div class="mock-body">
          <div class="doc-head">
            <div class="doc-photo">Photo</div>
            <div style="flex:1">
              <div class="barcode"></div>
              <div class="barcode-no">1306141188</div>
            </div>
            <div class="doc-title">E817745856<br><span style="font-weight:600;color:var(--muted)">EMBASSY OF<br>SAUDI ARABIA</span></div>
          </div>
          <div class="doc-rows">
            <div class="drow"><span>Full Name</span><span>MD Saydul Khan</span></div>
            <div class="drow"><span>Passport No.</span><span>A19101335</span></div>
            <div class="drow"><span>Profession</span><span>Load &amp; Unload Worker</span></div>
            <div class="drow"><span>Visa No.</span><span>1306141188</span></div>
          </div>
          <div class="mock-foot">
            <span class="pill indigo">Application</span>
            <span class="pill indigo">Forwarding</span>
            <span class="pill">Agreement</span>
            <span class="pill">Checklist</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ===================== STATS ===================== --}}
<div class="stats">
  <div class="wrap stats-grid">
    <div class="stat">
      <span class="ic ic-indigo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
      <div><b>4 Documents</b><small>from 1 input</small></div>
    </div>
    <div class="stat">
      <span class="ic ic-emerald"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a1 1 0 0 1 1-1h2M20 7V5a1 1 0 0 0-1-1h-2M4 17v2a1 1 0 0 0 1 1h2M20 17v2a1 1 0 0 1-1 1h-2"/><path d="M7 8v8M11 8v8M15 8v8"/></svg></span>
      <div><b>Auto Barcode</b><small>visa &amp; passport</small></div>
    </div>
    <div class="stat">
      <span class="ic ic-amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6m0 0 3-3m-3 3L9 5"/><path d="M5 12h14a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2z"/></svg></span>
      <div><b>PDF Preview</b><small>&amp; A4 download</small></div>
    </div>
    <div class="stat">
      <span class="ic ic-sky"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></span>
      <div><b>Secure Backup</b><small>agency records</small></div>
    </div>
  </div>
</div>

{{-- ===================== FEATURES ===================== --}}
<section id="features">
  <div class="wrap">
    <div class="sec-head">
      <span class="sec-tag">Features</span>
      <h2>Everything your agency needs in one platform</h2>
      <p>From HR records to print-ready visa documents, VisaDeskPro brings your whole agency workflow into one clean, secure SaaS dashboard.</p>
    </div>
    <div class="feat-grid">
      @php
        $features = [
          ['ic-indigo','M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2|M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8','HR Record Management','Store, search and manage every candidate and HR profile from one organised place.'],
          ['ic-emerald','M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z|M14 2v6h6M8 13h8M8 17h5','Application Document Generation','Generate application and visa documents automatically from a single candidate input.'],
          ['ic-amber','M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z','Agency Dashboard','Track agents, candidates, documents and payments from one clear overview.'],
          ['ic-sky','M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2|M9 7a4 4 0 1 0 0 .01|M23 21v-2a4 4 0 0 0-3-3.87|M16 3.13a4 4 0 0 1 0 7.75','Staff Workflow','Give your team a simple, role-aware workflow for everyday agency operations.'],
          ['ic-indigo','M6 9V2h12v7|M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2|M6 14h12v8H6z','Print Preview & PDF Download','Preview every document on screen and export print-ready A4 PDFs in one click.'],
          ['ic-emerald','M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z|m9 12 2 2 4-4','Role Based Access','Super admin, agency admin and staff each get exactly the access they need.'],
          ['ic-sky','M5 11h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2z|M7 11V7a5 5 0 0 1 10 0v4','Secure SaaS Dashboard','Manage your whole agency securely from any device, with data kept safe.'],
        ];
      @endphp
      @foreach($features as $f)
        <div class="card">
          <span class="ic {{ $f[0] }}">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
              @foreach(explode('|',$f[1]) as $d)<path d="{{ $d }}"/>@endforeach
            </svg>
          </span>
          <h3>{{ $f[2] }}</h3>
          <p>{{ $f[3] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== DOCUMENTS ===================== --}}
<section id="documents" class="soft">
  <div class="wrap">
    <div class="sec-head">
      <span class="sec-tag">Documents</span>
      <h2>All Embassy Documents in One Place</h2>
      <p>One candidate profile powers the full Saudi embassy file — generated, previewed and exported together.</p>
    </div>
    <div class="doc-grid">
      @php
        $docs = [
          ['ic-indigo','Saudi Embassy Application Form','The official bilingual application with photo box, centered barcode and full candidate details.'],
          ['ic-emerald','Forwarding Letter','Professional forwarding letter to the consular section, auto-filled from the candidate file.'],
          ['ic-amber','Employment Agreement','Standard terms &amp; conditions agreement with both-party signature blocks.'],
          ['ic-sky','Checklist','Bilingual attachment checklist covering passport, visa, medical, clearance and more.'],
        ];
      @endphp
      @foreach($docs as $i => $d)
        <div class="doccard">
          <span class="num">0{{ $i+1 }}</span>
          <span class="ic {{ $d[0] }}">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
          </span>
          <h3>{{ $d[1] }}</h3>
          <p>{!! $d[2] !!}</p>
          <span class="tag">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            Preview &amp; PDF
          </span>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== HOW IT WORKS ===================== --}}
<section id="how">
  <div class="wrap">
    <div class="sec-head">
      <span class="sec-tag">How It Works</span>
      <h2>From candidate data to complete file in 4 steps</h2>
      <p>A simple, repeatable workflow your whole team can follow.</p>
    </div>
    <div class="steps">
      @php
        $steps = [
          ['Add Candidate Information','Enter the candidate&rsquo;s personal, passport and visa details a single time.'],
          ['Review Auto Documents','The system builds all four documents with barcode, age and dates filled in.'],
          ['Preview Embassy File','Open each document on screen and confirm everything looks correct.'],
          ['Download PDF','Export a single document or the complete file as one A4 print-ready PDF.'],
        ];
      @endphp
      @foreach($steps as $i => $s)
        <div class="step">
          <div class="n">{{ $i+1 }}</div>
          <h3>{{ $s[0] }}</h3>
          <p>{!! $s[1] !!}</p>
          @if($i < 3)
          <svg class="arrow" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ===================== BEFORE / AFTER ===================== --}}
<section class="soft">
  <div class="wrap">
    <div class="sec-head">
      <span class="sec-tag">Why Switch</span>
      <h2>From manual paperwork to a one-click file</h2>
      <p>See how much faster embassy file preparation becomes.</p>
    </div>
    <div class="ba">
      <div class="ba-col ba-before">
        <h3><span class="badge-bad">Before</span> The manual way</h3>
        <ul>
          @foreach(['Manual document typing','Repeated data entry for every page','Barcode alignment headaches','Inconsistent PDF formatting','Slow, time-consuming file preparation'] as $b)
          <li><svg class="x" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>{{ $b }}</li>
          @endforeach
        </ul>
      </div>
      <div class="ba-col ba-after">
        <h3><span class="badge-good">After</span> With VisaDeskPro</h3>
        <ul>
          @foreach(['One-time input for all documents','Automatic barcode generation','Consistent A4 print format','Complete file in one click','Faster, smoother agency workflow'] as $a)
          <li><svg class="v" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>{{ $a }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
</section>

{{-- ===================== CTA ===================== --}}
<section id="get-app">
  <div class="wrap">
    <div class="cta">
      <h2>Ready to create embassy files faster?</h2>
      <p>Start preparing professional Saudi Embassy files with automatic barcode, on-screen preview and one-click PDF download.</p>
      <div class="hero-cta">
        @auth
          <a href="{{ $dashUrl }}" class="btn btn-white">Go to Dashboard</a>
        @else
          @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn btn-white">Register Now</a>
          @endif
          <a href="{{ route('login') }}" class="btn btn-light">Login</a>
        @endauth
      </div>
    </div>
  </div>
</section>

{{-- ===================== FOOTER ===================== --}}
<footer id="contact">
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <div class="brand">
          <span class="logo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg></span>
          VisaDesk<span class="pro">Pro</span>
        </div>
        <p class="desc">A secure SaaS platform for recruiting, HR and manpower agencies — manage HR records, agency applications, visa/application documents, payment requests and print-ready PDFs from one dashboard.</p>
      </div>
      <div class="foot-col">
        <h4>Product</h4>
        <a href="#features">Features</a>
        <a href="#documents">Documents</a>
        <a href="#how">How It Works</a>
        <a href="#get-app">Get App</a>
      </div>
      <div class="foot-col">
        <h4>Account</h4>
        <a href="{{ route('login') }}">Login</a>
        @if (Route::has('register'))<a href="{{ route('register') }}">Register</a>@endif
        @auth<a href="{{ $dashUrl }}">Dashboard</a>@endauth
      </div>
      <div class="foot-col">
        <h4>Contact</h4>
        <span>Support &amp; sales</span>
        <a href="mailto:mdshafiqulislam822@gmail.com">mdshafiqulislam822@gmail.com</a>
        <span>Sat–Thu, 9am–6pm</span>
      </div>
    </div>
    <div class="foot-bottom">
      <span>&copy; {{ date('Y') }} {{ config('app.name', 'VisaDeskPro') }}. All rights reserved.</span>
      <span>Agency, HR &amp; visa document management.</span>
    </div>
  </div>
</footer>

</body>
</html>
