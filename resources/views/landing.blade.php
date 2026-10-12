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
    --navy:#15497E; --navy-d:#0E3358; --navy-l:#EAF1F8;
    --emerald:#0E9F6E; --emerald-d:#0B7D57; --emerald-l:#E7F6EF;
    --ink:#0f172a; --slate:#475569; --muted:#64748b;
    --line:#e2e8f0; --bg:#ffffff; --soft:#f8fafc;
    --radius:16px; --shadow:0 10px 30px -12px rgba(15,23,42,.18);
    --shadow-sm:0 2px 10px -4px rgba(15,23,42,.12);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html{scroll-behavior:smooth}
  section[id],[id="top"]{scroll-margin-top:84px}
  body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--ink);background:var(--bg);line-height:1.6;-webkit-font-smoothing:antialiased}
  a{text-decoration:none;color:inherit}
  a,button,.btn,label{touch-action:manipulation;-webkit-tap-highlight-color:transparent}
  img,svg{display:block}
  .wrap{max-width:1200px;margin:0 auto;padding:0 20px}
  .sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}

  /* Buttons — ported from shadcn/ui Button (rounded-md, font-medium, focus ring, size variants) */
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;font-weight:500;font-size:14px;height:42px;padding:0 20px;border-radius:8px;border:1px solid transparent;cursor:pointer;transition:background-color .18s ease,color .18s ease,border-color .18s ease,box-shadow .18s ease,transform .18s ease;white-space:nowrap;outline:none}
  .btn:focus-visible{box-shadow:0 0 0 3px rgba(21,73,126,.35)}
  /* default variant — primary, strong hover (darken + lift + tinted shadow) */
  .btn-primary{background:var(--navy);color:#fff;box-shadow:0 4px 12px -6px rgba(21,73,126,.35)}
  .btn-primary:hover{background:var(--navy-d);transform:translateY(-1px);box-shadow:0 10px 22px -8px rgba(21,73,126,.55)}
  .btn-primary:active{transform:translateY(0);box-shadow:0 4px 10px -6px rgba(21,73,126,.5)}
  /* secondary variant — subtle muted fill, no border */
  .btn-ghost{background:#eef2f7;color:var(--ink);border-color:transparent}
  .btn-ghost:hover{background:#e3e9f1}
  /* outline/ghost on dark CTA band */
  .btn-light{background:rgba(255,255,255,.12);color:#fff;border-color:rgba(255,255,255,.28)}
  .btn-light:hover{background:rgba(255,255,255,.2)}
  .btn-white{background:#fff;color:var(--navy-d)}
  .btn-white:hover{background:#f1f5f9;transform:translateY(-1px);box-shadow:0 10px 24px -10px rgba(0,0,0,.45)}
  /* sm size variant */
  .btn-sm{height:34px;padding:0 14px;font-size:13.5px}

  /* Navbar */
  header.nav{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.85);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
  .nav-in{display:flex;align-items:center;justify-content:space-between;height:68px}
  .brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:18px;letter-spacing:-.02em}
  .brand .logo{width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--navy),var(--emerald));display:grid;place-items:center;color:#fff;flex:0 0 auto}
  .brand .pro{color:var(--navy)}
  footer .brand .pro{color:#818cf8}
  .nav-links{display:flex;align-items:center;gap:28px}
  .nav-links a{font-size:15px;color:var(--slate);font-weight:500;transition:color .15s ease}
  .nav-links a:hover{color:var(--navy)}
  .nav-cta{display:flex;align-items:center;gap:10px}
  .hamburger{display:none;background:none;border:0;cursor:pointer;padding:8px}
  .hamburger span{display:block;width:24px;height:2px;background:var(--ink);margin:5px 0;border-radius:2px;transition:transform .2s ease,opacity .2s ease}
  #mnav{display:none}
  #mnav:checked ~ .mobile-menu{display:block}
  .mobile-menu{display:none;border-top:1px solid var(--line);background:#fff;padding:14px 20px 22px}
  .mobile-menu a{display:block;padding:11px 4px;color:var(--slate);font-weight:500;border-bottom:1px solid var(--soft)}
  .mobile-menu .mrow{display:flex;gap:10px;margin-top:14px}
  .mobile-menu .mrow .btn{flex:1}

  /* Hero */
  .hero{position:relative;overflow:hidden;background:
     radial-gradient(1100px 500px at 85% -10%,rgba(14,159,110,.10),transparent 60%),
     radial-gradient(900px 500px at 5% 0%,rgba(21,73,126,.12),transparent 55%),
     linear-gradient(180deg,#fff, var(--soft))}
  .hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:54px;align-items:center;padding:78px 0 84px}
  /* Badge (secondary variant) — shadcn: rounded-full, text-xs, font-medium */
  .eyebrow{display:inline-flex;align-items:center;gap:8px;background:var(--navy-l);color:var(--navy-d);font-weight:500;font-size:12.5px;padding:5px 12px;border:1px solid transparent;border-radius:999px;margin-bottom:20px}
  .eyebrow .dot{width:7px;height:7px;border-radius:50%;background:var(--emerald)}
  .hero-title{font-size:48px;line-height:1.08;letter-spacing:-.03em;font-weight:800;margin-bottom:18px}
  h1.hero-title .grad{color:var(--navy-d)}
  .hero-link{display:inline-flex;align-items:center;gap:6px;font-weight:600;font-size:15px;color:var(--navy-d);padding:12px 6px;transition:color .18s ease}
  .hero-link:hover{color:var(--navy)}
  .hero-link svg{transition:transform .18s ease}
  .hero-link:hover svg{transform:translateX(3px)}
  .hero p.lead{font-size:18px;color:var(--slate);max-width:560px;margin-bottom:28px}
  .hero-cta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:26px}
  .hero-mini{display:flex;flex-wrap:wrap;gap:18px;color:var(--muted);font-size:14px;font-weight:500}
  .hero-mini span{display:inline-flex;align-items:center;gap:7px}
  .check{color:var(--emerald);flex:0 0 auto}

  /* Hero mockup */
  .mock{position:relative}
  .mock-card{position:relative;z-index:1;background:#fff;border:1px solid rgba(15,23,42,.06);border-radius:20px;overflow:hidden;
     box-shadow:0 1px 2px rgba(15,23,42,.05),0 10px 24px -10px rgba(15,23,42,.14),0 30px 55px -22px rgba(15,23,42,.22),inset 0 1px 0 rgba(255,255,255,.7);
     transition:transform .35s cubic-bezier(.16,1,.3,1),box-shadow .35s cubic-bezier(.16,1,.3,1)}
  .mock:hover .mock-card{transform:translateY(-5px);
     box-shadow:0 2px 4px rgba(15,23,42,.06),0 16px 32px -12px rgba(15,23,42,.18),0 40px 70px -24px rgba(15,23,42,.28),inset 0 1px 0 rgba(255,255,255,.7)}
  .mock-top{display:flex;align-items:center;gap:8px;padding:14px 16px;border-bottom:1px solid var(--line);background:var(--soft)}
  .mock-top i{width:11px;height:11px;border-radius:50%;background:#cbd5e1;display:block}
  .mock-top i:nth-child(1){background:#f87171}.mock-top i:nth-child(2){background:#fbbf24}.mock-top i:nth-child(3){background:#34d399}
  .mock-top b{margin-left:8px;font-size:13px;color:var(--muted);font-weight:600}
  .mock-body{padding:18px}
  .doc-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px}
  .doc-photo{width:52px;height:64px;border:1px solid var(--line);border-radius:6px;display:grid;place-items:center;font-size:9px;color:var(--muted);text-align:center;background:var(--soft)}
  .barcode{height:34px;width:150px;margin:0 auto 3px;background:repeating-linear-gradient(90deg,#0f172a 0 2px,#fff 2px 4px,#0f172a 4px 5px,#fff 5px 9px,#0f172a 9px 12px,#fff 12px 14px)}
  .barcode-no{text-align:center;font-size:11px;font-weight:700;letter-spacing:1px;color:var(--ink)}
  .doc-title{text-align:right;font-size:11px;font-weight:700;color:var(--navy-d);line-height:1.3}
  .doc-rows{display:grid;gap:7px}
  .drow{display:flex;justify-content:space-between;gap:10px;font-size:11px;border:1px solid var(--line);border-radius:8px;padding:8px 10px}
  .drow span:first-child{color:var(--muted)}
  .drow span:last-child{font-weight:600}
  .mock-foot{display:flex;align-items:center;gap:8px;margin-top:14px;padding-top:13px;border-top:1px dashed var(--line)}
  .pill{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:3px 9px;border:1px solid transparent;border-radius:999px;background:var(--emerald-l);color:var(--emerald-d)}
  .pill.indigo{background:var(--navy-l);color:var(--navy-d)}
  .float{position:absolute;z-index:2;background:#fff;border:1px solid rgba(15,23,42,.07);border-radius:14px;padding:12px 14px;display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;
     box-shadow:0 2px 4px rgba(15,23,42,.06),0 14px 26px -10px rgba(15,23,42,.20),0 22px 42px -18px rgba(15,23,42,.22),inset 0 1px 0 rgba(255,255,255,.8);
     transition:transform .35s cubic-bezier(.16,1,.3,1),box-shadow .35s cubic-bezier(.16,1,.3,1)}
  .float .ic{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;color:#fff;flex:0 0 auto}
  .float.f1{top:-22px;left:-26px}
  .float.f2{bottom:-24px;right:-22px}
  .mock:hover .float.f1{transform:translate(-5px,-7px)}
  .mock:hover .float.f2{transform:translate(5px,7px)}
  /* Entrance on page load: fade-in + slight upward slide (CSS only, staggered).
     fill-mode backwards holds the hidden state during the delay and reverts to
     base afterwards so the hover parallax transforms still work. */
  @keyframes riseIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  .mock-card{animation:riseIn .6s cubic-bezier(.16,1,.3,1) .05s backwards}
  .float{animation:riseIn .55s cubic-bezier(.16,1,.3,1) backwards}
  .float.f1{animation-delay:.22s}
  .float.f2{animation-delay:.34s}
  @media (prefers-reduced-motion:reduce){
    .mock-card,.float{transition:none;animation:none}
    .mock:hover .mock-card,.mock:hover .float.f1,.mock:hover .float.f2{transform:none}
  }
  .float small{display:block;font-weight:500;color:var(--muted);font-size:11px}

  /* Stats */
  .stats{margin-top:-34px;position:relative;z-index:5;padding-bottom:10px}
  .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:var(--shadow-sm);display:flex;gap:13px;align-items:center}
  .stat .ic{width:42px;height:42px;border-radius:11px;display:grid;place-items:center;color:#fff;flex:0 0 auto}
  .stat b{display:block;font-size:15px;line-height:1.25}
  .stat small{color:var(--muted);font-size:12.5px}
  /* Scroll-reveal initial state (GSAP ScrollTrigger animates these in, staggered) */
  .reveal-up{opacity:0;transform:translateY(24px);will-change:transform,opacity}
  @media (prefers-reduced-motion:reduce){.reveal-up{opacity:1;transform:none}}

  /* Section shell */
  section{padding:84px 0}
  .sec-head{text-align:center;max-width:680px;margin:0 auto 50px}
  .sec-tag{font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--navy)}
  .sec-head h2{font-size:36px;letter-spacing:-.02em;font-weight:800;margin:10px 0 12px}
  .sec-head p{color:var(--slate);font-size:17px}
  .soft{background:var(--soft)}

  /* Features */
  .feat-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;max-width:940px;margin:0 auto}
  .card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:24px;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}
  .card:hover{transform:translateY(-4px);box-shadow:var(--shadow);border-color:#dbe1ea}
  .card .ic{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;color:#fff;margin-bottom:15px;transition:transform .2s ease}
  .card:hover .ic{transform:scale(1.08)}
  .card h3{font-size:16.5px;font-weight:700;margin-bottom:7px}
  .card p{color:var(--slate);font-size:14px}
  .ic-indigo{background:linear-gradient(135deg,#1E5A97,#0E3358)}
  .ic-emerald{background:linear-gradient(135deg,#2BB985,#0B7D57)}
  .ic-amber{background:linear-gradient(135deg,#1E5A97,#0E3358)}
  .ic-sky{background:linear-gradient(135deg,#2BB985,#0B7D57)}

  /* Documents */
  .doc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
  .doccard{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:24px;position:relative;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease}
  /* Gradient top accent line (grows slightly on hover) */
  .doccard::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--navy),var(--emerald));transform-origin:top;transition:transform .25s ease}
  .doccard:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
  .doccard:hover::before{transform:scaleY(1.8)}
  /* Soft gradient-fill background numeral */
  .doccard .num{position:absolute;top:10px;right:16px;font-size:52px;font-weight:800;line-height:1;color:transparent;background:linear-gradient(180deg,#e8eef6,#d7e2f0);-webkit-background-clip:text;background-clip:text;opacity:.9;transition:opacity .2s ease}
  .doccard:hover .num{opacity:1}
  .doccard .ic{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;color:#fff;margin-bottom:16px}
  .doccard h3{font-size:16px;font-weight:700;margin-bottom:8px;position:relative}
  .doccard p{color:var(--slate);font-size:13.5px;margin-bottom:16px;position:relative}
  .tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:500;color:var(--emerald-d);background:var(--emerald-l);padding:3px 10px;border:1px solid transparent;border-radius:999px}

  /* How it works */
  .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;position:relative}
  .step{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:26px 22px;position:relative}
  .step .n{width:42px;height:42px;border-radius:12px;background:var(--navy);color:#fff;font-weight:800;display:grid;place-items:center;font-size:18px;margin-bottom:16px}
  .step h3{font-size:16px;font-weight:700;margin-bottom:7px}
  .step p{color:var(--slate);font-size:14px}
  .step .arrow{position:absolute;top:42px;right:-14px;color:#cbd5e1;z-index:2}

  /* Before / After */
  .ba{display:grid;grid-template-columns:1fr 1fr;gap:22px}
  .ba-col{border-radius:var(--radius);padding:30px;border:1px solid var(--line);border-left-width:4px}
  .ba-before{background:#fffafa;border-color:#f2c7c7;border-left-color:#ef4444}
  .ba-after{background:linear-gradient(160deg,var(--emerald-l),#fff);border-color:#b7eccf;border-left-color:var(--emerald)}
  .ba-col h3{font-size:19px;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:10px}
  .ba-col ul{list-style:none;display:grid;gap:13px}
  .ba-col li{display:flex;gap:11px;align-items:flex-start;font-size:15px;color:var(--slate)}
  .x{color:#ef4444;flex:0 0 auto;margin-top:2px}
  .v{color:var(--emerald);flex:0 0 auto;margin-top:2px}
  .badge-bad{display:inline-flex;align-items:center;font-size:12px;font-weight:600;color:#b91c1c;background:#fee2e2;padding:3px 10px;border:1px solid transparent;border-radius:999px}
  .badge-good{display:inline-flex;align-items:center;font-size:12px;font-weight:600;color:var(--emerald-d);background:var(--emerald-l);padding:3px 10px;border:1px solid transparent;border-radius:999px}

  /* CTA */
  .cta{background:linear-gradient(125deg,var(--navy-d),var(--navy));border-radius:26px;padding:60px 40px;text-align:center;color:#fff;position:relative;overflow:hidden}
  .cta:after{content:"";position:absolute;inset:0;background:radial-gradient(600px 240px at 80% 0,rgba(255,255,255,.18),transparent 60%)}
  .cta h2{font-size:34px;font-weight:800;letter-spacing:-.02em;margin-bottom:12px;position:relative}
  .cta p{font-size:17px;max-width:620px;margin:0 auto 28px;color:rgba(255,255,255,.9);position:relative}
  .cta .hero-cta{justify-content:center;position:relative}

  /* Trusted-by / stat strip (fills the gap between CTA and footer) */
  #get-app{padding-bottom:46px}
  .trust{padding:0 0 64px;text-align:center}
  .trust-eyebrow{font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:22px}
  .trust-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;max-width:820px;margin:0 auto}
  .trust-stat b{display:block;font-size:30px;font-weight:800;letter-spacing:-.02em;line-height:1.1;color:var(--navy);font-variant-numeric:tabular-nums}
  .trust-stat small{display:block;margin-top:5px;font-size:13.5px;color:var(--slate)}

  /* Footer */
  footer{background:#0b1220;color:#cbd5e1;padding:60px 0 26px}
  .foot-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:36px;margin-bottom:40px}
  footer .brand{color:#fff;margin-bottom:14px}
  footer p.desc{font-size:14px;color:#94a3b8;max-width:300px}
  .foot-col h4{font-size:14px;font-weight:700;color:#fff;margin-bottom:16px;letter-spacing:.02em}
  .foot-col a,.foot-col span{display:block;font-size:14px;color:#94a3b8;padding:5px 0;transition:color .15s ease}
  .foot-col a:hover{color:#fff}
  .foot-bottom{border-top:1px solid #1e293b;padding-top:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:13px;color:#94a3b8}


  /* ───── Hero slider ───── */
  .hero-slider{position:relative;overflow:hidden;background:#06233f;color:#fff}
  .hero-slider .slides{display:grid}
  .slide{grid-area:1/1;position:relative;overflow:hidden;opacity:0;visibility:hidden;transition:opacity .8s ease,visibility 0s linear .8s;min-height:600px;display:flex;align-items:center}
  .slide.is-active{opacity:1;visibility:visible;transition:opacity .8s ease}
  .slide.theme-riyadh{background:linear-gradient(160deg,#06233f 0%,#0e3a66 55%,#0b6b66 120%)}
  .slide.theme-haram{background:linear-gradient(160deg,#052e22 0%,#065f46 60%,#0e9f6e 130%)}
  .slide.theme-desert{background:linear-gradient(170deg,#3b1d0a 0%,#9a4a12 55%,#f59e0b 140%)}
  .slide.theme-flight{background:linear-gradient(170deg,#0b3a73 0%,#1d78c9 60%,#8ccbf5 130%)}
  .slide-photo{position:absolute;inset:0;background-size:cover;background-position:center}
  .slide-photo::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(6,20,36,.82),rgba(6,20,36,.35) 70%,rgba(6,20,36,.2))}
  .slide-scene{position:absolute;left:0;right:0;bottom:0;height:62%;pointer-events:none;opacity:.95}
  .slide-scene svg{width:100%;height:100%}
  .slide.is-active .slide-scene svg{animation:sceneIn 1.2s cubic-bezier(.16,1,.3,1) both}
  @keyframes sceneIn{from{transform:translateY(22px);opacity:0}to{transform:none;opacity:1}}
  .slide-grid{position:relative;z-index:2;display:block;padding:76px 20px 104px;width:100%}
  .slide-grid.has-mock{display:grid;grid-template-columns:1.05fr .95fr;gap:54px;align-items:center}
  .slide-copy{max-width:640px}
  .slide.is-active .slide-copy>*{animation:riseIn .7s cubic-bezier(.16,1,.3,1) both}
  .slide.is-active .slide-copy>*:nth-child(2){animation-delay:.08s}
  .slide.is-active .slide-copy>*:nth-child(3){animation-delay:.16s}
  .slide.is-active .slide-copy>*:nth-child(4){animation-delay:.24s}
  .slide.is-active .slide-copy>*:nth-child(5){animation-delay:.32s}
  .hero-slider .eyebrow{background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(4px)}
  .hero-slider .eyebrow .dot{background:#34d399}
  .hero-slider .hero-title{color:#fff;text-shadow:0 2px 24px rgba(0,0,0,.25)}
  .hero-slider h1.hero-title .grad,.hero-slider .hero-title .grad{color:#6ee7b7}
  .hero-slider .hero p.lead,.hero-slider p.lead{color:rgba(255,255,255,.9);max-width:560px}
  .hero-slider .btn-white{color:var(--navy-d);font-weight:600}
  .hero-link.on-dark{color:#fff}.hero-link.on-dark:hover{color:#d1fae5}
  .hero-mini.on-dark{color:rgba(255,255,255,.88)}
  .hero-mini.on-dark .check{color:#6ee7b7}
  .hero-slider .mock{position:relative;z-index:2;color:var(--ink);text-shadow:none}
  .hs-arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:5;width:46px;height:46px;border-radius:50%;border:1px solid rgba(255,255,255,.3);background:rgba(255,255,255,.14);color:#fff;display:grid;place-items:center;cursor:pointer;backdrop-filter:blur(6px);transition:background .18s ease,transform .18s ease}
  .hs-arrow:hover{background:rgba(255,255,255,.28)}
  .hs-arrow:focus-visible,.hs-dots button:focus-visible{outline:3px solid #6ee7b7;outline-offset:2px}
  .hs-prev{left:18px}.hs-next{right:18px}
  .hs-dots{position:absolute;left:0;right:0;bottom:46px;z-index:5;display:flex;justify-content:center;gap:10px}
  .hs-dots button{width:11px;height:11px;border-radius:999px;border:0;background:rgba(255,255,255,.5);cursor:pointer;padding:0;transition:width .25s ease,background .25s ease}
  .hs-dots button.is-active{width:34px;background:#fff}
  .hs-progress{position:absolute;left:0;right:0;bottom:0;height:4px;background:rgba(255,255,255,.15);z-index:5}
  .hs-progress i{display:block;height:100%;width:0;background:#34d399}
  @media(max-width:1000px){.hs-arrow{display:none}}
  @media(max-width:860px){
    .slide{min-height:640px}
    .slide-grid.has-mock{grid-template-columns:1fr}
    .slide-grid{padding:56px 20px 96px}
    .hero-slider .mock{display:none}
    .slide-scene{height:44%}
  }
  @media(max-width:560px){.slide{min-height:620px}.hero-slider .hero-cta .btn{flex:1 1 100%}}
  @media (prefers-reduced-motion:reduce){.slide,.slide.is-active{transition:none}.slide.is-active .slide-copy>*,.slide.is-active .slide-scene svg{animation:none}}

  /* ERP section */
  .erp-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
  .erp-card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:26px;transition:transform .2s ease,box-shadow .2s ease}
  .erp-card:hover{transform:translateY(-4px);box-shadow:var(--shadow)}
  .erp-card .ic{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;color:#fff;margin-bottom:14px}
  .erp-card h3{font-size:18px;font-weight:800;margin-bottom:4px}
  .erp-card>p{color:var(--muted);font-size:14px;margin-bottom:14px}
  .erp-card ul{list-style:none;display:grid;gap:10px}
  .erp-card li{display:flex;gap:10px;align-items:flex-start;font-size:15px;color:var(--ink)}
  .erp-card li small{display:block;color:var(--slate);font-size:13.5px;font-weight:400;line-height:1.45}
  .erp-card li b{font-weight:600}
  .erp-card li .v{margin-top:3px}
  .erp-strip{display:flex;flex-wrap:wrap;justify-content:center;gap:12px 28px;margin-top:34px;color:var(--navy-d);font-weight:600;font-size:15px}
  .erp-strip span{display:inline-flex;align-items:center;gap:8px}
  @media(max-width:1000px){.erp-grid{grid-template-columns:1fr 1fr}}
  @media(max-width:700px){.erp-grid{grid-template-columns:1fr}}

  /* Important links */
  .links-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;max-width:1100px;margin:0 auto}
  .link-btn{display:flex;align-items:center;justify-content:center;gap:10px;height:56px;padding:0 18px;border-radius:999px;background:var(--navy);color:#fff;font-weight:700;font-size:16px;box-shadow:0 8px 18px -10px rgba(21,73,126,.6);transition:transform .18s ease,background .18s ease,box-shadow .18s ease}
  .link-btn:hover{background:var(--emerald-d);transform:translateY(-2px);box-shadow:0 14px 24px -10px rgba(11,125,87,.6)}
  .link-btn:focus-visible{outline:3px solid rgba(14,159,110,.45);outline-offset:3px}
  .link-btn svg{flex:0 0 auto;opacity:.85}
  @media(max-width:1000px){.links-grid{grid-template-columns:repeat(2,1fr)}}
  @media(max-width:560px){.links-grid{grid-template-columns:1fr}}

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
    /* Keep the toggle keyboard-focusable & operable (sr-only, not display:none) */
    #mnav{display:block;position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);border:0}
    #mnav:focus-visible + .hamburger{box-shadow:0 0 0 3px rgba(21,73,126,.35);border-radius:8px}
    .hero-grid{grid-template-columns:1fr;gap:48px;padding:54px 0 64px}
    .hero-title{font-size:36px}
    .mock{max-width:420px;margin:0 auto}
    .ba{grid-template-columns:1fr}
  }
  @media(max-width:560px){
    .feat-grid,.doc-grid,.steps,.stats-grid{grid-template-columns:1fr}
    .sec-head h2,.cta h2{font-size:27px}
    .hero-title{font-size:31px}
    .hero p.lead{font-size:16px}
    section{padding:60px 0}
    .cta{padding:44px 22px}
    .trust-grid{grid-template-columns:repeat(2,1fr);gap:16px}
    .foot-grid{grid-template-columns:1fr;gap:26px}
    .float{display:none}
  }
</style>
@vite(['resources/js/landing.js'])
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
      <a href="#erp">ERP</a>
      <a href="#documents">Documents</a>
      <a href="#links">Links</a>
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
      <a href="#erp">ERP</a>
      <a href="#documents">Documents</a>
      <a href="#links">Links</a>
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

{{-- ===================== HERO SLIDER ===================== --}}
@php
  // Optional real photos: drop files into public/images/hero/ (slide-1.jpg … slide-4.jpg) and they replace the illustrations.
  $slides = [
    ['scene' => 'riyadh', 'theme' => 'riyadh', 'eyebrow' => 'Built for recruiting & manpower agencies', 'photo' => 'slide-1.jpg',
     'title' => '<span class="grad">VisaDeskPro</span> — Smart Agency & Visa Document Management',
     'lead' => 'Manage HR records, agency applications, embassy/application formats, payment requests, and print-ready documents from one secure SaaS dashboard.',
     'tags' => ['4 documents, one input', 'Auto barcode', 'A4 print-ready PDF'], 'mock' => true],
    ['scene' => 'haram', 'theme' => 'haram', 'eyebrow' => 'Makkah · Madinah · Kingdom of Saudi Arabia', 'photo' => 'slide-2.jpg',
     'title' => 'Embassy-format applications, <span class="grad">exactly as required</span>',
     'lead' => 'Application, Forwarding, Agreement and Checklist generated in the official KSA layout — A4, barcode and photo placed correctly every time.',
     'tags' => ['Official KSA format', 'Barcode & photo', 'Preview before print']],
    ['scene' => 'desert', 'theme' => 'desert', 'eyebrow' => 'Riyadh · AlUla · Jeddah', 'photo' => 'slide-3.jpg',
     'title' => 'MOFA, Medical, Stamping &amp; Delivery — <span class="grad">in one place</span>',
     'lead' => 'Type a passport number once. Name, DOB, visa and agent details fill in everywhere, so your team never retypes the same data.',
     'tags' => ['Auto-fill from passport', 'Agent-wise filters', 'Invoices & vouchers']],
    ['scene' => 'flight', 'theme' => 'flight', 'eyebrow' => 'From file to flight', 'photo' => 'slide-4.jpg',
     'title' => 'Secure, multi-agency and <span class="grad">ready to grow</span>',
     'lead' => 'Every agency sees only its own records. Role-based access, subscription control and secure backups keep your business safe.',
     'tags' => ['Multi-agency isolation', 'Role-based access', 'Secure backup']],
  ];
  $tick = '<svg class="check" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
@endphp
<section class="hero hero-slider" style="padding:0" data-hero-slider aria-roledescription="carousel" aria-label="VisaDeskPro highlights">
  <div class="slides">
    @foreach($slides as $i => $s)
      @php
        $photoFile = collect(['jpg', 'jpeg', 'png', 'webp'])->map(fn ($e) => 'images/hero/slide-'.($i + 1).'.'.$e)->first(fn ($f) => file_exists(public_path($f)));
        $hasPhoto = (bool) $photoFile;
      @endphp
      <div class="slide theme-{{ $s['theme'] }} {{ $i === 0 ? 'is-active' : '' }}" data-slide role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}" @if($i) aria-hidden="true" @endif>
        @if($hasPhoto)<div class="slide-photo" style="background-image:url('{{ asset($photoFile) }}')"></div>@endif
        <div class="slide-scene">@include('landing._hero-scenes', ['scene' => $s['scene']])</div>
        <div class="wrap slide-grid {{ ($s['mock'] ?? false) ? 'has-mock' : '' }}">
          <div class="slide-copy">
            <span class="eyebrow"><span class="dot"></span> {{ $s['eyebrow'] }}</span>
            @if($i === 0)<h1 class="hero-title">{!! $s['title'] !!}</h1>@else<h2 class="hero-title">{!! $s['title'] !!}</h2>@endif
            <p class="lead">{{ $s['lead'] }}</p>
            <div class="hero-cta">
              @auth
                <a href="{{ $dashUrl }}" class="btn btn-white">Login to Dashboard</a>
              @else
                @if (Route::has('register'))
                  <a href="{{ route('register') }}" class="btn btn-white">Get Started
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                  </a>
                @endif
                <a href="{{ route('login') }}" class="btn btn-light">Login to Dashboard</a>
              @endauth
              <a href="#features" class="hero-link on-dark">View Features
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </a>
            </div>
            <div class="hero-mini on-dark">
              @foreach($s['tags'] as $t)<span>{!! $tick !!} {{ $t }}</span>@endforeach
            </div>
          </div>

          @if($s['mock'] ?? false)
          <div class="mock">
            <div class="float f1">
              <span class="ic ic-emerald"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a1 1 0 0 1 1-1h2M4 17v2a1 1 0 0 0 1 1h2M20 7V5a1 1 0 0 0-1-1h-2M20 17v2a1 1 0 0 1-1 1h-2"/><path d="M7 8v8M10 8v8M13 8v8M16 8v8"/></svg></span>
              <div>Auto Barcode<small>VS &amp; Passport No.</small></div>
            </div>
            <div class="float f2">
              <span class="ic ic-indigo"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
              <div>4 Documents<small>Ready to print</small></div>
            </div>
            <div class="mock-card">
              <div class="mock-top"><i></i><i></i><i></i><b>Embassy Application — Preview</b></div>
              <div class="mock-body">
                <div class="doc-head">
                  <div class="doc-photo">Photo</div>
                  <div style="flex:1"><div class="barcode"></div><div class="barcode-no">1306141188</div></div>
                  <div class="doc-title">E817745856<br><span style="font-weight:600;color:var(--muted)">EMBASSY OF<br>SAUDI ARABIA</span></div>
                </div>
                <div class="doc-rows">
                  <div class="drow"><span>Full Name</span><span>MD Saydul Khan</span></div>
                  <div class="drow"><span>Passport No.</span><span>A19101335</span></div>
                  <div class="drow"><span>Profession</span><span>Load &amp; Unload Worker</span></div>
                  <div class="drow"><span>Visa No.</span><span>1306141188</span></div>
                </div>
                <div class="mock-foot"><span class="pill indigo">Application</span><span class="pill indigo">Forwarding</span><span class="pill">Agreement</span><span class="pill">Checklist</span></div>
              </div>
            </div>
          </div>
          @endif
        </div>
      </div>
    @endforeach
  </div>

  <button type="button" class="hs-arrow hs-prev" data-hs-prev aria-label="Previous slide"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></button>
  <button type="button" class="hs-arrow hs-next" data-hs-next aria-label="Next slide"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></button>
  <div class="hs-dots" role="tablist" aria-label="Choose slide">
    @foreach($slides as $i => $s)<button type="button" role="tab" data-hs-dot="{{ $i }}" aria-label="Slide {{ $i + 1 }}" class="{{ $i === 0 ? 'is-active' : '' }}"></button>@endforeach
  </div>
  <div class="hs-progress"><i data-hs-bar></i></div>
</section>

{{-- ===================== STATS ===================== --}}
<div class="stats">
  <div class="wrap stats-grid">
    <div class="stat reveal-up">
      <span class="ic ic-indigo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
      <div><b>4 Documents</b><small>from 1 input</small></div>
    </div>
    <div class="stat reveal-up">
      <span class="ic ic-emerald"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a1 1 0 0 1 1-1h2M20 7V5a1 1 0 0 0-1-1h-2M4 17v2a1 1 0 0 0 1 1h2M20 17v2a1 1 0 0 1-1 1h-2"/><path d="M7 8v8M11 8v8M15 8v8"/></svg></span>
      <div><b>Auto Barcode</b><small>visa &amp; passport</small></div>
    </div>
    <div class="stat reveal-up">
      <span class="ic ic-amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v6m0 0 3-3m-3 3L9 5"/><path d="M5 12h14a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2z"/></svg></span>
      <div><b>PDF Preview</b><small>&amp; A4 download</small></div>
    </div>
    <div class="stat reveal-up">
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
        // The 4 core capabilities, mapped to the product's main promise:
        // manage records -> generate documents -> preview/print PDF -> secure dashboard.
        $features = [
          ['ic-indigo','M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2|M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8','HR Record Management','Store, search and manage every candidate and HR profile from one organised place.'],
          ['ic-emerald','M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z|M14 2v6h6M8 13h8M8 17h5','Application Document Generation','Generate application and visa documents automatically from a single candidate input.'],
          ['ic-emerald','M6 9V2h12v7|M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2|M6 14h12v8H6z','Print Preview & PDF Download','Preview every document on screen and export print-ready A4 PDFs in one click.'],
          ['ic-indigo','M5 11h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2z|M7 11V7a5 5 0 0 1 10 0v4','Secure SaaS Dashboard','Manage your whole agency securely from any device, with data kept safe.'],
        ];
      @endphp
      @foreach($features as $f)
        <div class="card reveal-up">
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
{{-- ===================== ERP ===================== --}}
@php
  $erpTick = '<svg class="v" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
  $erpGroups = [
    ['ic-indigo', 'M3 7h18M3 12h18M3 17h12', 'Processing', 'Track every passenger through each stage', [
      ['Medical', 'Centre, status, issue &amp; expiry dates'],
      ['MOFA &amp; Double MOFA', 'Visa entries with auto-filled passport data'],
      ['Visa Stamping', 'Visa no., issue date, 90-day expiry auto-counted'],
      ['BMET Clearance', 'Manpower clearance records and filters'],
      ['Delivery', 'Handover tracking with amounts and status'],
    ]],
    ['ic-emerald', 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6', 'Accounts', 'Know exactly who owes what', [
      ['Agent Khata', 'Agent-wise ledger, balance and statements'],
      ['Invoices', 'Print one or many — 2 per A4 page'],
      ['Payment Vouchers', 'Multi-select print, ready for signature'],
      ['Expenses &amp; Due List', 'Daily expenses and outstanding dues'],
    ]],
    ['ic-indigo', 'M3 3v18h18|M7 15l4-4 3 3 5-6', 'Insights', 'Decide with live numbers', [
      ['Profit / Loss', 'Income vs expense, per period'],
      ['Reports', 'Filter by date and agent, print or export'],
      ['Smart Notes', 'Reminders and notes beside your work'],
      ['ERP Settings', 'Expense heads, defaults and preferences'],
    ]],
  ];
@endphp
<section id="erp">
  <div class="wrap">
    <div class="sec-head">
      <span class="sec-tag">ERP</span>
      <h2>Run the whole agency desk, not just the paperwork</h2>
      <p>Medical, MOFA, stamping, delivery, agent accounts and invoices in one ERP — type a passport number once and it fills in everywhere.</p>
    </div>
    <div class="erp-grid">
      @foreach($erpGroups as $g)
        <div class="erp-card reveal-up">
          <span class="ic {{ $g[0] }}">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">@foreach(explode('|',$g[1]) as $d)<path d="{{ $d }}"/>@endforeach</svg>
          </span>
          <h3>{{ $g[2] }}</h3>
          <p>{{ $g[3] }}</p>
          <ul>
            @foreach($g[4] as $m)
              <li>{!! $erpTick !!}<span><b>{!! $m[0] !!}</b><small>{!! $m[1] !!}</small></span></li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>
    <div class="erp-strip">
      <span>{!! $erpTick !!} Auto-fill from passport</span>
      <span>{!! $erpTick !!} Agent-wise filters everywhere</span>
      <span>{!! $erpTick !!} Instant list filtering, no page reload</span>
      <span>{!! $erpTick !!} Each agency sees only its own data</span>
    </div>
  </div>
</section>

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
        <div class="doccard reveal-up">
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
{{-- ===================== IMPORTANT LINKS ===================== --}}
<section id="links" style="background:linear-gradient(180deg,#fff,#f1f6fb)">
  <div class="wrap">
    <div class="sec-head">
      <span class="sec-tag">Important Links</span>
      <h2>Check Status On Time</h2>
      <p>Official visa, medical, training and embassy portals — one click away, opens in a new tab.</p>
    </div>
    <div class="links-grid">
      @foreach(array_slice(config('important_links', []), 0, 8) as $l)
        <a class="link-btn" href="{{ $l['url'] }}" target="_blank" rel="noopener noreferrer">{{ $l['label'] }}
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg>
        </a>
      @endforeach
    </div>
  </div>
</section>

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
        <div class="step reveal-up">
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

{{-- ===================== TRUSTED BY / STATS STRIP ===================== --}}
{{-- NOTE: replace these figures with real metrics before launch --}}
<div class="trust">
  <div class="wrap">
    <p class="trust-eyebrow">Trusted by recruiting &amp; manpower agencies</p>
    <div class="trust-grid">
      @php
        $trust = [
          ['500+','Embassy files generated'],
          ['40+','Agencies onboard'],
          ['4','Documents per candidate'],
          ['1-click','A4 PDF export'],
        ];
      @endphp
      @foreach($trust as $t)
        <div class="trust-stat"><b>{{ $t[0] }}</b><small>{{ $t[1] }}</small></div>
      @endforeach
    </div>
  </div>
</div>

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
      <a href="#links">Links</a>
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

{{-- No-JS fallback: reveal cards if JavaScript is disabled. Reveal logic is bundled via Vite in resources/js/landing.js --}}
<noscript><style>.reveal-up{opacity:1;transform:none}</style></noscript>

</body>
</html>
