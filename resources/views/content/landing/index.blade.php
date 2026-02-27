<!DOCTYPE html>
<html lang="fr" class="light-style layout-wide" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title>{{ config('variables.templateName') }} | {{ config('variables.templateSuffix') }}</title>

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
  <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icons & Core CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --primary:#7367f0;
      --primary-soft:rgba(115,103,240,.09);
      --dark:#0f172a;
      --text:#475569;
      --muted:#64748b;
      --light-bg:#f8fafc;
      --border:#e2e8f0;

      --ok:#16a34a;
      --warn:#f59e0b;
      --info:#0ea5e9;

      --shadow: 0 24px 50px rgba(15,23,42,.10);
      --shadow-soft: 0 18px 32px rgba(15,23,42,.08);
    }

    body{
      font-family:'Inter',sans-serif;
      background:#fff;
      color:var(--text);
      overflow-x:hidden;
      scroll-behavior:smooth;
    }
    h1,h2,h3,h4,.navbar-brand{font-family:'Plus Jakarta Sans',sans-serif;color:var(--dark);letter-spacing:-0.02em;}
    .text-gradient{
      background:linear-gradient(135deg,#7367f0 0%,#a095ff 100%);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
    }

    /* Navbar glass */
    .navbar{
      background:rgba(255,255,255,.78);
      backdrop-filter:blur(12px);
      border-bottom:1px solid var(--border);
      transition:all .35s cubic-bezier(.4,0,.2,1);
      padding:1.05rem 0;
      z-index:1050;
    }
    .navbar.scrolled{
      padding:.72rem 0;
      background:rgba(255,255,255,.94);
      box-shadow:0 10px 15px -3px rgba(0,0,0,.05);
    }
    .nav-link{
      font-weight:800;
      color:#1e293b !important;
      padding:.5rem 1.05rem !important;
      font-size:.95rem;
      transition:transform .2s, color .2s;
      white-space:nowrap;
    }
    .nav-link:hover{color:var(--primary)!important;transform:translateY(-1px);}
    .nav-link.is-active{color:var(--primary)!important;}

    .app-brand-logo svg{
      width:36px;height:36px;
      filter:drop-shadow(0 4px 6px rgba(115,103,240,.2));
    }

    /* Hero */
    .hero-section{
      padding:170px 0 96px;
      background:
        radial-gradient(circle at 80% 20%, rgba(115,103,240,.14), transparent 40%),
        radial-gradient(circle at 10% 80%, rgba(14,165,233,.10), transparent 42%),
        radial-gradient(circle at 50% 30%, rgba(22,163,74,.06), transparent 45%);
      position:relative;
    }
    .hero-title{
      font-size:clamp(2.25rem,5vw,4.05rem);
      font-weight:900;
      line-height:1.08;
      letter-spacing:-0.035em;
      margin-bottom:1rem;
    }
    .hero-sub{
      font-size:1.08rem;
      color:var(--muted);
      line-height:1.8;
      max-width:42rem;
    }

    /* section padding reduced */
    .section-pad{padding:72px 0;}
    .section-soft{background:var(--light-bg);}

    /* “bento” mini cards */
    .bento{
      border:1px solid var(--border);
      border-radius:20px;
      background:rgba(255,255,255,.78);
      backdrop-filter:blur(10px);
      padding:1rem 1.05rem;
      height:100%;
      transition:transform .22s, box-shadow .22s, border-color .22s;
    }
    .bento:hover{
      transform:translateY(-4px);
      border-color:rgba(115,103,240,.45);
      box-shadow:var(--shadow-soft);
    }
    .bento .icon{
      width:46px;height:46px;border-radius:16px;
      display:flex;align-items:center;justify-content:center;
      background:var(--primary-soft);
      color:var(--primary);
      font-size:1.4rem;
      margin-bottom:.8rem;
    }

    /* Trusted */
    .trusted-by{
      padding:1.8rem 0;
      border-top:1px solid var(--border);
      border-bottom:1px solid var(--border);
      background:var(--light-bg);
    }

    /* Feature cards */
    .step-card{
      padding:2.25rem 1.85rem;
      border-radius:26px;
      background:#fff;
      border:1px solid var(--border);
      transition:all .26s cubic-bezier(.4,0,.2,1);
      height:100%;
    }
    .step-card:hover{
      border-color:var(--primary);
      box-shadow:var(--shadow-soft);
      transform:translateY(-6px);
    }
    .step-icon{
      width:62px;height:62px;border-radius:20px;
      display:flex;align-items:center;justify-content:center;
      background:var(--primary-soft);
      color:var(--primary);
      font-size:1.75rem;
      margin-bottom:1.15rem;
      position:relative;
      overflow:hidden;
    }
    .step-icon::after{
      content:'';
      position:absolute; inset:-30%;
      background: radial-gradient(circle at 30% 30%, rgba(255,255,255,.55), transparent 55%);
      transform:rotate(18deg);
      opacity:.35;
    }

    /* Modules illustrations (icon tiles) */
    .module-card{
      border:1px solid var(--border);
      border-radius:26px;
      background:#fff;
      padding:1.35rem 1.25rem;
      height:100%;
      transition:transform .22s, box-shadow .22s, border-color .22s;
      position:relative;
      overflow:hidden;
    }
    .module-card:hover{
      transform:translateY(-5px);
      box-shadow:var(--shadow-soft);
      border-color:rgba(115,103,240,.55);
    }
    .module-illu{
      width:72px;height:72px;border-radius:22px;
      display:flex;align-items:center;justify-content:center;
      background:linear-gradient(145deg, rgba(115,103,240,.16), rgba(115,103,240,.06));
      border:1px solid rgba(115,103,240,.18);
      position:relative;
      overflow:hidden;
      flex:0 0 auto;
    }
    .module-illu i{
      font-size:2rem;
      color:var(--primary);
      filter: drop-shadow(0 8px 18px rgba(115,103,240,.25));
    }
    .module-illu::after{
      content:'';
      position:absolute;
      width:120px;height:120px;
      right:-70px;top:-70px;
      background:rgba(115,103,240,.18);
      filter:blur(0);
      border-radius:999px;
      opacity:.55;
    }
    .module-meta .title{font-weight:900;color:var(--dark);}
    .module-meta .desc{color:var(--muted);font-size:.95rem;line-height:1.55;}
    .module-tags{
      display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.75rem;
    }
    .tag{
      font-size:.78rem;
      font-weight:800;
      padding:.35rem .6rem;
      border-radius:999px;
      border:1px solid var(--border);
      background:#fff;
      color:#0f172a;
      white-space:nowrap;
    }

    /* Pricing – more modern + compact */
    .pricing-grid{
      display:grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 18px;
    }
    @media (max-width: 992px){
      .pricing-grid{grid-template-columns: repeat(2, minmax(0,1fr));}
    }
    @media (max-width: 576px){
      .pricing-grid{grid-template-columns: 1fr;}
    }

    .pricing-card{
      border:1px solid var(--border);
      border-radius:28px;
      background:#fff;
      padding:2.25rem 1.7rem; /* reduced height */
      transition:transform .25s, box-shadow .25s, border-color .25s;
      position:relative;
      overflow:hidden;
      height:100%;
      isolation:isolate;
    }
    .pricing-card::before{
      content:'';
      position:absolute; inset:-1px;
      background: radial-gradient(circle at 20% 10%, rgba(115,103,240,.20), transparent 38%),
                  radial-gradient(circle at 90% 40%, rgba(14,165,233,.12), transparent 46%),
                  radial-gradient(circle at 30% 110%, rgba(22,163,74,.10), transparent 45%);
      opacity:.55;
      z-index:-1;
    }
    .pricing-card:hover{
      transform:translateY(-6px);
      box-shadow:var(--shadow);
      border-color:rgba(115,103,240,.55);
    }
    .pricing-card.popular{
      border:2.5px solid var(--primary);
      box-shadow:0 30px 60px rgba(115,103,240,.18);
      transform:translateY(-8px);
    }
    .popular-badge{
      position:absolute;
      top:18px;
      right:18px;
      background:rgba(115,103,240,.14);
      border:1px solid rgba(115,103,240,.35);
      color:var(--primary);
      padding:7px 12px;
      border-radius:999px;
      font-size:.75rem;
      font-weight:900;
      letter-spacing:.05em;
      text-transform:uppercase;
      backdrop-filter: blur(10px);
    }
    .plan-head{
      display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:.65rem;
    }
    .plan-name{
      font-weight:950; letter-spacing:.02em;
      margin:0;
    }
    .plan-icon{
      width:44px;height:44px;border-radius:16px;
      display:flex;align-items:center;justify-content:center;
      background:rgba(255,255,255,.75);
      border:1px solid rgba(226,232,240,.9);
      box-shadow:0 12px 22px rgba(15,23,42,.06);
      flex:0 0 auto;
    }
    .plan-icon i{font-size:1.35rem;color:var(--dark);opacity:.9;}
    .price-row{display:flex; align-items:flex-end; gap:.55rem; margin: .55rem 0 1rem;}
    .price-val{font-size:2.55rem;font-weight:950;color:var(--dark);letter-spacing:-1.5px;line-height:1;}
    .price-unit{color:var(--primary);font-size:1rem;font-weight:900;}
    .subline{color:var(--muted);font-size:.98rem;line-height:1.6;margin-bottom:1.05rem;}

    .feature-list{margin:0;padding:0;list-style:none;display:grid;gap:.55rem;}
    .feature-item{display:flex;align-items:flex-start;gap:.55rem;color:#0f172a;}
    .feature-item i{margin-top:.18rem;}
    .feature-item small{color:var(--muted);}
    .mini-divider{height:1px;background:rgba(226,232,240,.85);margin:1.05rem 0;}
    .cta-btn{
      width:100%;
      border-radius:16px;
      font-weight:950;
      padding:.95rem 1.2rem;
    }

    /* Toggle */
    .price-toggle{
      display:inline-flex;
      align-items:center;
      gap:.65rem;
      padding:.45rem .65rem;
      border:1px solid var(--border);
      border-radius:999px;
      background:#fff;
      flex-wrap:wrap;
      justify-content:center;
    }
    .toggle-btn{
      border:0;
      background:transparent;
      font-weight:950;
      padding:.5rem .9rem;
      border-radius:999px;
      color:var(--muted);
      transition:background .2s, color .2s, transform .2s;
      font-size:.92rem;
      white-space:nowrap;
    }
    .toggle-btn.active{
      background:var(--primary-soft);
      color:var(--primary);
      border:1px solid rgba(115,103,240,.22);
    }
    .toggle-btn:hover{transform:translateY(-1px);}

    /* Comparison – modern + compact + responsive */
    .compare-card{
      border:1px solid var(--border);
      border-radius:22px;
      background:#fff;
      overflow:hidden;
      box-shadow: 0 16px 30px rgba(15,23,42,.06);
    }
    .compare-head{
      padding:1rem 1.1rem; /* reduced height */
      border-bottom:1px solid var(--border);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:.8rem;
      background:linear-gradient(180deg, rgba(247,248,255,.9), rgba(255,255,255,.95));
    }
    .compare-head h3{margin:0;font-weight:950;}
    .compare-note{color:var(--muted);font-size:.95rem;margin:0;}
    .compare-controls{
      display:flex; gap:.5rem; flex-wrap:wrap; justify-content:flex-end;
    }
    .chip{
      border:1px solid var(--border);
      background:#fff;
      color:#0f172a;
      font-weight:900;
      padding:.4rem .7rem;
      border-radius:999px;
      font-size:.82rem;
      cursor:pointer;
      transition:transform .18s, border-color .18s, box-shadow .18s, background .18s;
      user-select:none;
      white-space:nowrap;
    }
    .chip:hover{transform:translateY(-1px);box-shadow:0 12px 18px rgba(15,23,42,.06);}
    .chip.active{
      background:var(--primary-soft);
      border-color:rgba(115,103,240,.30);
      color:var(--primary);
    }

    .compare-table{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
    }
    .compare-table thead th{
      padding:.85rem .9rem; /* reduced */
      font-weight:950;
      background:#fff;
      border-bottom:1px solid var(--border);
      text-align:center;
      position:sticky;
      top:0;
      z-index:2;
    }
    .compare-table thead th:first-child{text-align:left;}
    .compare-table tbody td{
      padding:.85rem .9rem; /* reduced */
      border-bottom:1px solid rgba(226,232,240,.75);
      vertical-align:middle;
    }
    .compare-label{
      font-weight:950;color:var(--dark);
      display:flex; align-items:center; gap:.6rem;
    }
    .compare-ico{
      width:34px;height:34px;border-radius:12px;
      display:flex;align-items:center;justify-content:center;
      background:rgba(115,103,240,.10);
      color:var(--primary);
      border:1px solid rgba(115,103,240,.16);
      flex:0 0 auto;
    }
    .val-pill{
      display:inline-flex;align-items:center;gap:.45rem;
      border:1px solid var(--border);
      background:#fff;
      padding:.35rem .6rem;
      border-radius:999px;
      font-weight:900;
      font-size:.9rem;
      white-space:nowrap;
    }

    /* small helpers */
    .pill{
      display:inline-flex;align-items:center;gap:.5rem;
      background:var(--primary-soft);
      color:var(--primary);
      border:1px solid rgba(115,103,240,.22);
      border-radius:999px;
      padding:.48rem .78rem;
      font-weight:950;
      font-size:.82rem;
      flex-wrap:wrap;
      justify-content:center;
    }
    .text-muted2{color:var(--muted)!important;}

    /* CTA */
    .cta-container{
      background:var(--dark);
      border-radius:34px;
      padding:2.9rem 2rem; /* slightly reduced */
      position:relative;
      overflow:hidden;
      color:#fff;
    }
    .cta-container::after{
      content:'';
      position:absolute;
      top:-40px; right:-40px;
      width:330px;height:330px;
      background:var(--primary);
      filter:blur(160px);
      opacity:.22;
    }

    /* Lead capture (NEW) */
    .lead-box{
      border:1px solid rgba(226,232,240,.65);
      border-radius:26px;
      background:linear-gradient(180deg, rgba(247,248,255,.75), rgba(255,255,255,.95));
      box-shadow: 0 18px 34px rgba(15,23,42,.08);
      overflow:hidden;
      position:relative;
    }
    .lead-box::after{
      content:'';
      position:absolute;
      right:-60px; top:-60px;
      width:220px; height:220px;
      background:rgba(115,103,240,.16);
      filter:blur(0);
      border-radius:999px;
      opacity:.7;
      pointer-events:none;
    }
    .lead-inner{
      padding:1.35rem 1.25rem; /* compact */
      position:relative;
    }
    .lead-title{
      font-weight:950;
      color:var(--dark);
      margin:0 0 .35rem 0;
    }
    .lead-sub{
      color:var(--muted);
      margin:0;
      line-height:1.6;
      font-size:1rem;
    }
    .lead-form .form-control{
      border-radius:14px;
      padding: .85rem 1rem;
      border:1px solid var(--border);
    }
    .lead-form .input-group-text{
      border-radius:14px;
      border:1px solid var(--border);
      background:#fff;
      color:var(--muted);
      font-weight:900;
    }
    .lead-form .btn{
      border-radius:14px;
      font-weight:950;
      padding:.9rem 1.1rem;
      white-space:nowrap;
    }

    /* Footer */
    .footer{
      background:#000;
      color:#94a3b8;
      padding:80px 0 34px;
    }
    .footer-link{
      color:#94a3b8;text-decoration:none;
      transition:.2s;
      font-size:.95rem;
      display:inline-block;
    }
    .footer-link:hover{color:#fff;transform:translateX(4px);}
    .hover-opacity-100:hover{opacity:1!important;}

    /* Buttons */
    .btn-primary{
      background:var(--primary);
      border:none;
      padding:1rem 2rem;
      border-radius:14px;
      font-weight:950;
      box-shadow:0 10px 15px -3px rgba(115,103,240,.28);
      transition:transform .25s, box-shadow .25s, background .2s;
    }
    .btn-primary:hover{
      background:#5e50ee;
      transform:translateY(-2px);
      box-shadow:0 22px 28px -10px rgba(115,103,240,.4);
    }
    .btn-outline-dark{
      border-radius:14px;
      padding:1rem 2rem;
      font-weight:950;
    }
    .btn-white{
      background:#fff;color:var(--dark);
      border-radius:14px;
      padding:1rem 2.5rem;
      font-weight:950;
      transition:transform .25s;
    }
    .btn-white:hover{transform:translateY(-2px);}

    /* Animations & reveal */
    .animate-float{animation:float 6.5s ease-in-out infinite;}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-18px)}}
    [data-reveal]{opacity:0;transform:translateY(16px);transition:opacity .7s ease, transform .7s ease;}
    [data-reveal].is-visible{opacity:1;transform:translateY(0);}

    /* Back to top (NEW) */
    .back-to-top{
      position:fixed;
      right:18px;
      bottom:18px;
      width:48px;height:48px;
      border-radius:16px;
      border:1px solid rgba(226,232,240,.8);
      background:rgba(255,255,255,.88);
      backdrop-filter: blur(10px);
      box-shadow: 0 18px 28px rgba(15,23,42,.12);
      display:flex;
      align-items:center;
      justify-content:center;
      color:var(--dark);
      cursor:pointer;
      z-index:1100;
      opacity:0;
      transform: translateY(10px) scale(.98);
      pointer-events:none;
      transition: opacity .22s ease, transform .22s ease, border-color .22s ease;
    }
    .back-to-top:hover{
      border-color: rgba(115,103,240,.5);
      transform: translateY(0) scale(1.02);
    }
    .back-to-top.is-visible{
      opacity:1;
      transform: translateY(0) scale(1);
      pointer-events:auto;
    }
    .back-to-top i{font-size:1.2rem;}

    /* Responsive adjustments */
    @media (max-width: 992px){
      .hero-section{padding:150px 0 86px;}
      .section-pad{padding:64px 0;}
      .back-to-top{right:14px;bottom:14px;}
    }
    @media (max-width: 576px){
      .hero-title{letter-spacing:-0.03em;}
      .navbar .btn{padding:.85rem 1.1rem;}
      .price-val{font-size:2.35rem;}
      .pricing-card{padding:2.05rem 1.45rem;}
      .compare-head{flex-direction:column;align-items:flex-start;}
      .compare-controls{justify-content:flex-start;}
      .compare-table thead th{font-size:.9rem}
      .lead-inner{padding:1.15rem 1.05rem;}
      .back-to-top{width:46px;height:46px;border-radius:14px;}
    }
  </style>
</head>

@php
  use Illuminate\Support\Str;

  $appName = config('variables.templateName');
  $plans = collect($plans ?? [])->values();

  $tierOrder = ['starter','pro','isp'];

  $resolveTier = function ($plan, $index) {
    $slug = Str::lower((string)($plan->slug ?? ''));
    $name = Str::lower((string)($plan->name ?? ''));
    if (Str::contains($slug,'starter') || Str::contains($name,'starter')) return 'starter';
    if (Str::contains($slug,'pro') || Str::contains($name,'pro')) return 'pro';
    if (Str::contains($slug,'isp') || Str::contains($name,'isp')) return 'isp';
    return ['starter','pro','isp'][$index] ?? 'pro';
  };

  $isUnlimited = function ($value) {
    if ($value === null || $value === '') return false;
    return in_array(Str::lower(trim((string)$value)), ['-1','illimite','illimité','unlimited','infini','∞'], true);
  };

  $formatLimit = function ($value) use ($isUnlimited) {
    if ($value === null || $value === '') return '—';
    if ($isUnlimited($value)) return 'Illimité';
    return is_numeric($value) ? number_format((int)$value, 0, ',', ' ') : (string)$value;
  };

  // Map per tier for comparison
  $planByTier = [];
  foreach ($plans as $idx => $plan) $planByTier[$resolveTier($plan, $idx)] = $plan;

  // Comparison rows + icons
  $comparisonRows = [
    ['key'=>'price', 'label'=>'Prix', 'type'=>'price', 'icon'=>'ti ti-currency-franc'],
    ['key'=>'routers', 'label'=>'Routeurs', 'feature'=>'routers', 'icon'=>'ti ti-router'],
    ['key'=>'hotspot', 'label'=>'Hotspot & Vouchers', 'feature'=>'hotspot', 'type'=>'bool', 'icon'=>'ti ti-wifi'],
    ['key'=>'pppoe', 'label'=>'PPPoE', 'feature'=>'pppoe', 'type'=>'bool', 'icon'=>'ti ti-plug-connected'],
    ['key'=>'vpn', 'label'=>'Comptes VPN', 'feature'=>'vpn_accounts', 'icon'=>'ti ti-shield-lock'],
    ['key'=>'users', 'label'=>'Utilisateurs actifs', 'feature'=>'active_users', 'icon'=>'ti ti-users'],
    ['key'=>'portal', 'label'=>'Portail captif / Page de vente', 'feature'=>'sale_page', 'type'=>'bool', 'icon'=>'ti ti-browser'],
    ['key'=>'payments', 'label'=>'Paiements & Wallet', 'feature'=>'payments', 'type'=>'bool', 'icon'=>'ti ti-credit-card'],
    ['key'=>'reports', 'label'=>'Rapports & Analytics', 'feature'=>'advanced_reports', 'type'=>'bool', 'icon'=>'ti ti-chart-bar'],
    ['key'=>'support', 'label'=>'Support', 'feature'=>'support_level', 'icon'=>'ti ti-headset'],
  ];

  // Pricing icons per tier
  $tierIcon = [
    'starter' => 'ti ti-sparkles',
    'pro' => 'ti ti-bolt',
    'isp' => 'ti ti-building-broadcast-tower',
  ];
@endphp

<body>

  <!-- Back to top (NEW) -->
  <button class="back-to-top" id="backToTop" type="button" aria-label="Retour en haut">
    <i class="ti ti-arrow-up"></i>
  </button>

  <!-- NAV -->
  <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
        <span class="app-brand-logo me-2">@include('_partials.macros', ['height' => 36])</span>
        <span class="fw-bold fs-4 text-dark">{{ $appName }}</span>
      </a>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Menu">
        <i class="ti ti-menu-2"></i>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item"><a class="nav-link" href="#features">Avantages</a></li>
          <!--<li class="nav-item"><a class="nav-link" href="#modules">Modules</a></li>-->
          <li class="nav-item"><a class="nav-link" href="#how-it-works">Fonctionnement</a></li>
          <li class="nav-item"><a class="nav-link" href="#pricing">Offres</a></li>
          <!--<li class="nav-item"><a class="nav-link" href="#comparison">Comparaison</a></li>-->
          <li class="nav-item"><a class="nav-link" href="#faq">Aide</a></li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary d-flex align-items-center">
              <i class="ti ti-layout-dashboard me-2"></i> Mon Dashboard
            </a>
          @else
            <a href="{{ route('login') }}" class="btn btn-link text-dark fw-bold text-decoration-none px-3">Connexion</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Essai Gratuit</a>
          @endauth
        </div>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero-section" id="home">
    <div class="container">
      <div class="row align-items-center gy-5">
        <div class="col-lg-6" data-reveal>
          <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill mb-4 pill">
            <i class="ti ti-bolt"></i>
            <span>Hotspot • PPPoE • VPN • Paiements</span>
          </div>

          <h1 class="hero-title">
            Gérez votre <span class="text-gradient">réseau</span>, vos <span class="text-gradient">ventes</span> et vos <span class="text-gradient">abonnés</span> en un seul endroit.
          </h1>

          <p class="hero-sub mb-4">
            Une solution professionnelle pensée pour les WISP, ISP locaux, cybercafés et intégrateurs : gestion Hotspot/PPPoE, AAA RADIUS, vouchers, portail captif, paiements Mobile Money et reporting.
          </p>

          <div class="d-flex flex-column flex-sm-row gap-3">
            @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg shadow-lg px-5 py-3">
              Mon Dashboard
            </a>
            @else
            <a href="#pricing" class="btn btn-primary btn-lg shadow-lg px-5 py-3">
              Essai gratuit
            </a>
            @endauth
            <a href="#how-it-works" class="btn btn-outline-dark btn-lg">
              Fonctionnement <i class="ti ti-arrow-right ms-2"></i>
            </a>
          </div>

          <div class="row mt-5 pt-4 g-4 border-top border-light">
            <div class="col-4">
              <h4 class="fw-bold mb-0"><span class="kpi-counter" data-target="99.9" data-decimals="1">0</span>%</h4>
              <small class="text-muted2">Disponibilité</small>
            </div>
            <div class="col-4 border-start border-light">
              <h4 class="fw-bold mb-0"><span class="kpi-counter" data-target="3" data-decimals="0">0</span> min</h4>
              <small class="text-muted2">Onboarding</small>
            </div>
            <div class="col-4 border-start border-light">
              <h4 class="fw-bold mb-0"><span class="kpi-counter" data-target="24" data-decimals="0">0</span>/7</h4>
              <small class="text-muted2">Support</small>
            </div>
          </div>
        </div>

        <div class="col-lg-6 text-center" data-reveal>
          <div class="position-relative d-inline-block">
            <div class="position-absolute top-50 start-50 translate-middle w-100 h-100 rounded-circle"
                 style="background: rgba(115,103,240,.10); filter: blur(90px);"></div>

            <img
              src="{{ asset('assets/img/illustrations/hero-dash-board-light.png') }}"
              alt="Dashboard PhenixSPOT"
              class="img-fluid position-relative animate-float"
              style="max-width: 94%; border-radius: 26px; box-shadow: 0 30px 60px rgba(15,23,42,.14);"
            >
          </div>

          <div class="row g-3 mt-4 text-start">
            <div class="col-md-4" data-reveal>
              <div class="bento">
                <div class="icon"><i class="ti ti-ticket"></i></div>
                <div class="fw-bold">Vouchers</div>
                <div class="small text-muted2">Génération • Stock • Vente</div>
              </div>
            </div>
            <div class="col-md-4" data-reveal>
              <div class="bento">
                <div class="icon" style="background:rgba(14,165,233,.10);color:var(--info)"><i class="ti ti-wifi"></i></div>
                <div class="fw-bold">Hotspot</div>
                <div class="small text-muted2">Portail captif + Paiement</div>
              </div>
            </div>
            <div class="col-md-4" data-reveal>
              <div class="bento">
                <div class="icon" style="background:rgba(22,163,74,.10);color:var(--ok)"><i class="ti ti-shield-lock"></i></div>
                <div class="fw-bold">AAA RADIUS</div>
                <div class="small text-muted2">PPPoE • Hotspot • Logs</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- TRUSTED -->
  <div class="trusted-by">
    <div class="container text-center" data-reveal>
      <p class="small fw-bold text-muted text-uppercase mb-4">Compatible avec votre stack réseau</p>
      <div class="d-flex flex-wrap justify-content-center align-items-center gap-5 opacity-50">
        <span class="h5 fw-bold mb-0 d-flex align-items-center gap-2"><i class="ti ti-router"></i>MikroTik</span>
        <span class="h5 fw-bold mb-0 d-flex align-items-center gap-2"><i class="ti ti-access-point"></i>Ubiquiti UniFi</span>
        <span class="h5 fw-bold mb-0 d-flex align-items-center gap-2"><i class="ti ti-access-point"></i>TP-Link Omada</span>
        <span class="h5 fw-bold mb-0 d-flex align-items-center gap-2"><i class="ti ti-access-point"></i>Cisco Meraki </span>
        <span class="h5 fw-bold mb-0 d-flex align-items-center gap-2"><i class="ti ti-lock"></i>RADIUS</span>
      </div>
    </div>
  </div>

  <!-- FEATURES -->
  <section id="features" class="section-pad">
    <div class="container">
      <div class="text-center mb-5" data-reveal>
        <span class="pill mb-3">B2B • ISP • Cybercafés • WISP</span>
        <h2 class="display-6 fw-bold mb-3">Pourquoi {{ $appName }} pour votre réseau ?</h2>
        <p class="text-muted2 fs-5 mx-auto" style="max-width: 820px;">
          Parce que vous ne vendez pas “du WiFi”, vous vendez une expérience : stabilité, contrôle, facturation, support et visibilité sur vos performances.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-md-4" data-reveal>
          <div class="step-card">
            <div class="step-icon"><i class="ti ti-rocket"></i></div>
            <h4 class="fw-bold mb-2">Déploiement rapide</h4>
            <p class="mb-0 text-muted2">Ajoutez vos routeurs, connectez RADIUS, activez Hotspot/PPPoE/VPN et commencez à vendre en quelques minutes.</p>
          </div>
        </div>

        <div class="col-md-4" data-reveal>
          <div class="step-card">
            <div class="step-icon" style="background:rgba(22,163,74,.10);color:var(--ok)"><i class="ti ti-shield-check"></i></div>
            <h4 class="fw-bold mb-2">Sécurité & AAA</h4>
            <p class="mb-0 text-muted2">Authentification, autorisation, comptabilité. Traçabilité des sessions et contrôle des accès à l’échelle.</p>
          </div>
        </div>

        <div class="col-md-4" data-reveal>
          <div class="step-card">
            <div class="step-icon" style="background:rgba(245,158,11,.12);color:var(--warn)"><i class="ti ti-chart-bar"></i></div>
            <h4 class="fw-bold mb-2">Monétisation</h4>
            <p class="mb-0 text-muted2">Vouchers, page de vente, wallet, mobile money, rapports et optimisation de revenus par zone.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MODULES (with illustrations) -->
  <section id="modules" class="section-pad section-soft">
    <div class="container">
      <div class="row align-items-center gy-5">
        <div class="col-lg-6" data-reveal>
          <span class="pill mb-3">Modules</span>
          <h2 class="display-6 fw-bold mb-3">Hotspot, PPPoE et VPN — modulaires.</h2>
          <p class="text-muted2 fs-5 mb-4">
            Activez uniquement ce dont vous avez besoin aujourd’hui. Montez en charge sans refaire votre architecture demain.
          </p>

          <div class="row g-3">
            <div class="col-md-6" data-reveal>
              <div class="module-card">
                <div class="d-flex gap-3">
                  <div class="module-illu">
                    <i class="ti ti-wifi"></i>
                  </div>
                  <div class="module-meta">
                    <div class="title">Hotspot</div>
                    <div class="desc">Portail captif, profils, limitations, page de login et branding.</div>
                    <div class="module-tags">
                      <span class="tag">Captive Portal</span>
                      <span class="tag">Limites</span>
                      <span class="tag">Branding</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6" data-reveal>
              <div class="module-card">
                <div class="d-flex gap-3">
                  <div class="module-illu" style="background:linear-gradient(145deg, rgba(14,165,233,.18), rgba(14,165,233,.06)); border-color: rgba(14,165,233,.18);">
                    <i class="ti ti-plug-connected" style="color:var(--info)"></i>
                  </div>
                  <div class="module-meta">
                    <div class="title">PPPoE</div>
                    <div class="desc">AAA RADIUS, abonnements, profils vitesse, suspensions, reprise.</div>
                    <div class="module-tags">
                      <span class="tag">AAA</span>
                      <span class="tag">Abonnés</span>
                      <span class="tag">Profils</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6" data-reveal>
              <div class="module-card">
                <div class="d-flex gap-3">
                  <div class="module-illu" style="background:linear-gradient(145deg, rgba(22,163,74,.18), rgba(22,163,74,.06)); border-color: rgba(22,163,74,.18);">
                    <i class="ti ti-shield-lock" style="color:var(--ok)"></i>
                  </div>
                  <div class="module-meta">
                    <div class="title">VPN</div>
                    <div class="desc">Comptes, scripts RouterOS, monitoring, renouvellements.</div>
                    <div class="module-tags">
                      <span class="tag">L2TP</span>
                      <span class="tag">Scripts</span>
                      <span class="tag">Monitoring</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6" data-reveal>
              <div class="module-card">
                <div class="d-flex gap-3">
                  <div class="module-illu" style="background:linear-gradient(145deg, rgba(245,158,11,.18), rgba(245,158,11,.06)); border-color: rgba(245,158,11,.18);">
                    <i class="ti ti-credit-card" style="color:var(--warn)"></i>
                  </div>
                  <div class="module-meta">
                    <div class="title">Paiements & Wallet</div>
                    <div class="desc">recharges, tracking, facturation.</div>
                    <div class="module-tags">
                      <span class="tag">Mobile Money</span>
                      <span class="tag">Achat de codes</span>
                      <span class="tag">Wallet</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="col-lg-6 text-center" data-reveal>
          <img
            src="{{ asset('assets/img/illustrations/auth-register-illustration-light.png') }}"
            alt="UI"
            class="img-fluid animate-float"
            style="max-width: 88%;"
          >
        </div>
      </div>
    </div>
  </section>

  <!-- HOW -->
  <section id="how-it-works" class="section-pad">
    <div class="container">
      <div class="row align-items-center gy-4">
        <div class="col-lg-5" data-reveal>
          <span class="pill mb-3">Mise en route</span>
          <h2 class="display-6 fw-bold mb-3">De zéro à “vente en ligne” en 3 étapes.</h2>
          <p class="text-muted2 fs-5 mb-4">
            Le système relie automatiquement : Plan → Quotas → Routeurs → Services (Hotspot/PPPoE) → Vente.
          </p>

          <div class="vstack gap-4">
            <div class="d-flex align-items-start">
              <span class="badge bg-primary rounded-circle p-2 me-3 mt-1">1</span>
              <div>
                <h6 class="fw-bold mb-1">Créez votre compte</h6>
                <p class="small text-muted2 mb-0">Espace client isolé, prêt pour l’exploitation.</p>
              </div>
            </div>

            <div class="d-flex align-items-start">
              <span class="badge bg-primary rounded-circle p-2 me-3 mt-1">2</span>
              <div>
                <h6 class="fw-bold mb-1">Connectez votre Routeur + RADIUS</h6>
                <p class="small text-muted2 mb-0">Ajout NAS, secret, profils et règles d’accès.</p>
              </div>
            </div>

            <div class="d-flex align-items-start">
              <span class="badge bg-primary rounded-circle p-2 me-3 mt-1">3</span>
              <div>
                <h6 class="fw-bold mb-1">Activez la vente</h6>
                <p class="small text-muted2 mb-0">Page publique, vouchers, paiement, wallet et reporting.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6 offset-lg-1" data-reveal>
          <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 26px;">
            <img
              src="{{ asset('assets/img/illustrations/page-misc-under-maintenance.png') }}"
              alt="Dashboard"
              class="img-fluid"
            >
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PRICING (modern + compact + dynamic) -->
  <section id="pricing" class="section-pad section-soft">
    <div class="container">
      <div class="text-center mb-4" data-reveal>
        <h2 class="display-6 fw-bold mb-2">Nos Offres <!-- SaaS (Hotspot • PPPoE • VPN)--></h2>
        <p class="text-muted2 fs-5 mb-3">Plans (mensuel/annuel) + quotas & modules.</p>

        <div class="price-toggle mx-auto">
          <span class="small fw-bold text-muted2">Facturation</span>
          <button class="toggle-btn active" type="button" data-billing="monthly">Mensuel</button>
          <button class="toggle-btn" type="button" data-billing="yearly">Annuel <span class="ms-1 badge text-bg-success">-15%</span></button>
        </div>
      </div>

      <div class="pricing-grid" data-reveal>
        @forelse($plans as $plan)
          @php
            $tier = $resolveTier($plan, $loop->index);
            $features = (array)($plan->features ?? []);

            $monthly = (float)($plan->price_monthly ?? 0);
            $yearlyDb = $plan->price_yearly ?? null;
            $yearly = $yearlyDb !== null ? (float)$yearlyDb : round($monthly * 12 * 0.85);

            $hasHotspot  = !empty($features['hotspot']);
            $hasPppoe    = !empty($features['pppoe']);
            $hasPayments = !empty($features['payments']);
            $hasSalePage = !empty($features['sale_page']);
            $hasReports  = !empty($features['advanced_reports']);

            $planIcon = $tierIcon[$tier] ?? 'ti ti-stars';
          @endphp

          <div class="pricing-card {{ $tier === 'pro' ? 'popular' : '' }}">
            @if($tier === 'pro')<div class="popular-badge">Meilleur choix</div>@endif

            <div class="plan-head">
              <div>
                <p class="mb-1 text-uppercase fw-bold" style="letter-spacing:.08em; color:var(--muted); font-size:.78rem;">
                  {{ strtoupper($tier) }}
                </p>
                <h5 class="plan-name">{{ $plan->name }}</h5>
              </div>
              <div class="plan-icon" aria-hidden="true"><i class="{{ $planIcon }}"></i></div>
            </div>

            <div class="price-row">
              <span class="price-val" data-price="monthly">{{ number_format($monthly, 0, ',', ' ') }}</span>
              <span class="price-val d-none" data-price="yearly">{{ number_format($yearly, 0, ',', ' ') }}</span>
              <span class="price-unit">
                FCFA
                <span class="text-muted2 fw-bold" data-unit="monthly">/ mois</span>
                <span class="text-muted2 fw-bold d-none" data-unit="yearly">/ an</span>
              </span>
            </div>

            <p class="subline">
              {{ $tier === 'starter' ? 'Pour démarrer et tester votre zone.' : ($tier === 'pro' ? 'Pour scaler ventes & opérations.' : 'Pour industrialiser multi-zones.') }}
            </p>

            <div class="mini-divider"></div>

            <ul class="feature-list">
              <li class="feature-item"><i class="ti ti-router text-primary"></i> <div><strong>{{ $formatLimit($features['routers'] ?? null) }}</strong> <small>routeur(s)</small></div></li>
              <li class="feature-item"><i class="ti ti-users text-primary"></i> <div><strong>{{ $formatLimit($features['active_users'] ?? null) }}</strong> <small>utilisateurs actifs</small></div></li>
              <li class="feature-item"><i class="ti ti-shield-lock text-primary"></i> <div><strong>{{ $formatLimit($features['vpn_accounts'] ?? null) }}</strong> <small>comptes VPN</small></div></li>

              <li class="feature-item"><i class="ti {{ $hasHotspot ? 'ti-check text-success' : 'ti-x text-muted' }}"></i> <div><strong>Hotspot</strong> <small>& vouchers</small></div></li>
              <li class="feature-item"><i class="ti {{ $hasPppoe ? 'ti-check text-success' : 'ti-x text-muted' }}"></i> <div><strong>PPPoE</strong> <small>Abonnement</small></div></li>
              <li class="feature-item"><i class="ti {{ $hasPayments ? 'ti-check text-success' : 'ti-x text-muted' }}"></i> <div><strong>Paiements</strong> <small>wallet</small></div></li>
              <li class="feature-item"><i class="ti {{ $hasSalePage ? 'ti-check text-success' : 'ti-x text-muted' }}"></i> <div><strong>Portail</strong> <small>vente/captif</small></div></li>
              <li class="feature-item"><i class="ti {{ $hasReports ? 'ti-check text-success' : 'ti-x text-muted' }}"></i> <div><strong>Analytics</strong> <small>rapports</small></div></li>
            </ul>

            <div class="mini-divider"></div>

            <a
              href="{{ auth()->check() ? route('user.payment', ['plan' => $plan->id, 'duration' => 'monthly']) : route('register') }}"
              class="btn {{ $tier === 'pro' ? 'btn-primary' : 'btn btn-outline-dark' }} cta-btn"
              data-cta="monthly"
            >
              {{ auth()->check() ? 'Choisir ce plan' : 'Démarrer' }}
            </a>
          </div>
        @empty
          <div class="alert alert-info mb-0">Aucun plan actif disponible pour le moment.</div>
        @endforelse
      </div>
    </div>
  </section>

  <!-- COMPARISON (modern + filter + compact + responsive) -->
  <section id="comparison" class="section-pad">
    <div class="container">
      <div class="text-center mb-4" data-reveal>
        <span class="pill mb-3">Comparaison</span>
        <h2 class="display-6 fw-bold mb-2">Choisissez vite le bon plan.</h2>
        <p class="text-muted2 fs-5 mx-auto" style="max-width:820px;">
          Filtrez les modules pour voir uniquement ce qui vous intéresse.
        </p>
      </div>

      <div class="compare-card" data-reveal>
        <div class="compare-head">
          <div>
            <h3 class="h5 fw-bold">Tableau comparatif</h3>
            <p class="compare-note">Astuce : active/désactive des catégories pour simplifier la vue.</p>
          </div>

          <div class="compare-controls" role="tablist" aria-label="Filtres comparaison">
            <span class="chip active" data-compare-filter="all">Tout</span>
            <span class="chip" data-compare-filter="modules">Modules</span>
            <span class="chip" data-compare-filter="quotas">Quotas</span>
            <span class="chip" data-compare-filter="billing">Paiements</span>
            <span class="chip" data-compare-filter="ops">Ops & Support</span>
          </div>
        </div>

        <div class="table-responsive" style="max-height: 520px;">
          <table class="compare-table">
            <thead>
              <tr>
                <th class="text-start">Fonctionnalités</th>
                <th>STARTER</th>
                <th>PRO</th>
                <th>ISP</th>
              </tr>
            </thead>
            <tbody>
              @foreach($comparisonRows as $row)
                @php
                  // assign row groups for filter
                  $group = match($row['key']) {
                    'routers', 'users', 'vpn' => 'quotas',
                    'hotspot', 'pppoe', 'portal' => 'modules',
                    'payments' => 'billing',
                    'reports', 'support' => 'ops',
                    default => 'all',
                  };
                @endphp
                <tr data-compare-row="{{ $group }}">
                  <td class="compare-label">
                    <span class="compare-ico"><i class="{{ $row['icon'] }}"></i></span>
                    <span>{{ $row['label'] }}</span>
                  </td>

                  @foreach($tierOrder as $tier)
                    @php
                      $tierPlan = $planByTier[$tier] ?? null;
                      $tierFeatures = (array)($tierPlan?->features ?? []);
                      $type = $row['type'] ?? 'text';

                      if (!$tierPlan) {
                        $display = '—';
                      } elseif ($type === 'price') {
                        $display = number_format((float)($tierPlan->price_monthly ?? 0), 0, ',', ' ') . ' FCFA';
                      } elseif ($type === 'bool') {
                        $display = !empty($tierFeatures[$row['feature']]);
                      } else {
                        $display = $formatLimit($tierFeatures[$row['feature']] ?? null);
                      }
                    @endphp

                    <td class="text-center">
                      @if(($row['type'] ?? null) === 'bool')
                        {!! $display
                          ? '<span class="val-pill"><i class="ti ti-check text-success"></i> Oui</span>'
                          : '<span class="val-pill"><i class="ti ti-x text-danger"></i> Non</span>' !!}
                      @elseif(($row['type'] ?? null) === 'price')
                        <span class="val-pill"><i class="ti ti-currency-franc text-primary"></i> {{ $display }}</span>
                      @else
                        <span class="val-pill"><i class="ti ti-star text-primary"></i> {{ $display }}</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="text-center mt-3" data-reveal>
        <small class="text-muted2">
          Besoin d’un plan sur-mesure (multi-zones, revendeurs, SLA) ?
          <a class="fw-bold text-decoration-none" href="#contact" style="color:var(--primary)">Parlons-en</a>.
        </small>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="section-pad section-soft">
    <div class="container">
      <div class="text-center mb-5" data-reveal>
        <span class="pill mb-3">Support</span>
        <h2 class="display-6 fw-bold mb-3">Questions fréquentes</h2>
        <p class="text-muted2 fs-5 mx-auto" style="max-width:720px;">
          Les réponses aux points importants : RADIUS, Mikrotik, NAS, Vouchers, Paiements.
        </p>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-9" data-reveal>
          <div class="accordion" id="accordionFaq">
            <div class="accordion-item border-0 mb-3 shadow-sm rounded-4 overflow-hidden">
              <h2 class="accordion-header">
                <button class="accordion-button fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#f1" aria-expanded="true">
                  Est-ce compatible Hotspot et PPPoE en même temps ?
                </button>
              </h2>
              <div id="f1" class="accordion-collapse collapse show" data-bs-parent="#accordionFaq">
                <div class="accordion-body text-muted2 lh-lg">
                  Oui. Vous pouvez activer Hotspot (vouchers + portail captif) et PPPoE (AAA RADIUS) sur les mêmes routeurs ou sur des routeurs différents.
                </div>
              </div>
            </div>

            <div class="accordion-item border-0 mb-3 shadow-sm rounded-4 overflow-hidden">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#f2">
                  Comment s’opèrent le crédit des ventes et l’imputation des frais de transaction ?
                </button>
              </h2>
              <div id="f2" class="accordion-collapse collapse" data-bs-parent="#accordionFaq">
                <div class="accordion-body text-muted2 lh-lg">
                  Les ventes (vouchers/abonnements) créditent le wallet. Vous choisissez qui supporte les frais (vous ou le client final), avec transparence côté checkout.
                </div>
              </div>
            </div>

            <div class="accordion-item border-0 mb-3 shadow-sm rounded-4 overflow-hidden">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#f3">
                  Je peux commencer petit puis passer à Pro/ISP ?
                </button>
              </h2>
              <div id="f3" class="accordion-collapse collapse" data-bs-parent="#accordionFaq">
                <div class="accordion-body text-muted2 lh-lg">
                  Oui. Le changement de plan ajuste vos quotas et modules sans casser vos routeurs, vos comptes PPPoE/VPN ou vos vouchers existants.
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <div class="container mb-5 pb-4" id="contact" data-reveal>
    <div class="cta-container text-center">
      <h2 class="display-6 fw-bold text-white mb-3">Prêt à vendre du WiFi comme un vrai opérateur ?</h2>
      <p class="text-white text-opacity-75 mb-4 mx-auto fs-5" style="max-width: 760px;">
        Lancez votre reseau Hotspot, PPPoE avec paiement mobile money et reporting.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        @auth
          <a href="{{ route('dashboard') }}" class="btn btn-white btn-lg px-5">Mon Dashboard</a>
        @else
          <a href="{{ route('register') }}" class="btn btn-white btn-lg px-5">Essayer gratuitement</a>
          <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg px-5" style="border-radius:14px;font-weight:950;">Connexion</a>
        @endauth
      </div>
    </div>
  </div>

  <!-- Lead capture (NEW) -->
  <section class="pb-5">
    <div class="container" data-reveal>
      <div class="lead-box">
        <div class="lead-inner">
          <div class="row align-items-center g-3">
            <div class="col-lg-5">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="pill"><i class="ti ti-bolt"></i> Démo & offres</span>
              </div>
              <h3 class="lead-title">Recevez une démo + une offre adaptée à votre zone</h3>
              <p class="lead-sub">
                Laissez votre WhatsApp et votre email. On vous envoie une proposition (plans, quotas, mise en place) rapidement.
              </p>
            </div>

            <div class="col-lg-7">
              <form class="lead-form" method="POST" action="{{-- route('leads.store') --}}">
                @csrf
                <div class="row g-2">
                  <div class="col-md-6">
                    <div class="input-group">
                      <span class="input-group-text"><i class="ti ti-brand-whatsapp"></i></span>
                      <input
                        type="tel"
                        inputmode="tel"
                        name="whatsapp"
                        class="form-control"
                        placeholder="WhatsApp (ex: +225 07 00 00 00 00)"
                        required
                        pattern="^[0-9+()\\s-]{7,20}$"
                        aria-label="WhatsApp"
                      >
                    </div>
                    <small class="text-muted2 d-block mt-1">Format libre (avec indicatif recommandé)</small>
                  </div>

                  <div class="col-md-6">
                    <div class="input-group">
                      <span class="input-group-text"><i class="ti ti-mail"></i></span>
                      <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="Email (ex: nom@domaine.com)"
                        required
                        aria-label="Email"
                      >
                    </div>
                    <small class="text-muted2 d-block mt-1">On ne spam pas. 1 message utile.</small>
                  </div>

                  <div class="col-12 d-flex flex-column flex-sm-row gap-2 mt-2">
                      <a
                      class="btn btn-outline-success"
                      href="https://wa.me/+22501219793?text={{ urlencode('Bonjour, je veux une démo de '.$appName.' (Hotspot/PPPoE/VPN).') }}"
                      target="_blank"
                      rel="noopener"
                    >
                      <i class="ti ti-brand-whatsapp me-2"></i> Écrire sur WhatsApp
                    </a>
                    <button type="submit" class="btn btn-primary flex-grow-1">
                      <i class="ti ti-send me-2"></i> Recevoir la démo
                    </button>
                  </div>

                  <input type="hidden" name="source" value="landing_lead_box">
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="text-center mt-3">
        <small class="text-muted2">
          En envoyant vos infos, vous acceptez d’être recontacté pour une démo. <a href="#" class="fw-bold text-decoration-none" style="color:var(--primary)">Confidentialité</a>
        </small>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="row g-5 mb-5">
        <div class="col-lg-5">
          <a class="navbar-brand d-flex align-items-center text-white mb-4" href="#home">
            @include('_partials.macros', ['height' => 30])
            <span class="fw-bold fs-4 ms-2">{{ $appName }}</span>
          </a>
          <p class="mb-4" style="max-width: 420px;">
        {{ config('variables.templateSuffix') }} : vouchers, paiements, wallet, reporting et croissance maîtrisée.
          </p>
          <div class="d-flex gap-3">
            <a href="#" class="text-white opacity-50 hover-opacity-100 fs-4" aria-label="Facebook"><i class="ti ti-brand-facebook"></i></a>
            <a href="#" class="text-white opacity-50 hover-opacity-100 fs-4" aria-label="WhatsApp"><i class="ti ti-brand-whatsapp"></i></a>
            <a href="#" class="text-white opacity-50 hover-opacity-100 fs-4" aria-label="Telegram"><i class="ti ti-brand-telegram"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-md-4">
          <h6 class="text-white fw-bold mb-4">Navigation</h6>
          <ul class="list-unstyled vstack gap-3">
            <li><a href="#features" class="footer-link">Avantages</a></li>
            <li><a href="#modules" class="footer-link">Modules</a></li>
            <li><a href="#pricing" class="footer-link">Offres</a></li>
            <li><a href="#comparison" class="footer-link">Comparaison</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-4">
          <h6 class="text-white fw-bold mb-4">Aide & Légal</h6>
          <ul class="list-unstyled vstack gap-3">
            <li><a href="#faq" class="footer-link">FAQ</a></li>
            @guest
              <li><a href="{{ route('login') }}" class="footer-link">Support Client</a></li>
            @else
              <li><a href="{{ route('dashboard') }}" class="footer-link">Support Client</a></li>
            @endguest
            <li><a href="#" class="footer-link">Confidentialité</a></li>
            <li><a href="#" class="footer-link">CGU</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-4">
          <h6 class="text-white fw-bold mb-4">Contact</h6>
          <p class="small mb-1"><i class="ti ti-mail me-2"></i> support@phenixspot.com</p>
          <p class="small mb-0"><i class="ti ti-map-pin me-2"></i> Grand-Bassam, Côte d'Ivoire</p>
        </div>
      </div>

      <div class="pt-4 border-top border-secondary border-opacity-25 d-flex flex-column flex-md-row justify-content-between align-items-center">
        <p class="small mb-0">© {{ date('Y') }} {{ $appName }}. Propulsé par Phenix IT Solutions.</p>
        <div class="d-flex gap-4 mt-3 mt-md-0">
          <span class="small opacity-50"><i class="ti ti-shield-lock me-1"></i> SSL / Sécurisé</span>
          <span class="small opacity-50"><i class="ti ti-router me-1"></i> MikroTik Optimized</span>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Navbar scroll effect
    window.addEventListener('scroll', function () {
      const nav = document.getElementById('mainNavbar');
      if (!nav) return;
      if (window.scrollY > 50) nav.classList.add('scrolled');
      else nav.classList.remove('scrolled');
    });

    // Back to top (NEW)
    (() => {
      const btn = document.getElementById('backToTop');
      if (!btn) return;

      const toggle = () => {
        if (window.scrollY > 450) btn.classList.add('is-visible');
        else btn.classList.remove('is-visible');
      };

      window.addEventListener('scroll', toggle, { passive: true });
      toggle();

      btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    })();

    // Reveal on scroll
    (() => {
      const els = document.querySelectorAll('[data-reveal]');
      const io = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('is-visible'); });
      }, { threshold: 0.12 });
      els.forEach(el => io.observe(el));
    })();

    // KPI counter
    (() => {
      const counters = document.querySelectorAll('.kpi-counter');
      const animate = (el) => {
        const target = parseFloat(el.dataset.target || '0');
        const decimals = parseInt(el.dataset.decimals || '0', 10);
        const duration = 900;
        const start = performance.now();

        const step = (now) => {
          const p = Math.min((now - start) / duration, 1);
          const eased = 1 - Math.pow(1 - p, 3);
          const val = target * eased;
          el.textContent = val.toFixed(decimals);
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      };

      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const el = entry.target;
          if (el.dataset.done) return;
          el.dataset.done = '1';
          animate(el);
        });
      }, { threshold: 0.35 });

      counters.forEach(c => io.observe(c));
    })();

    // Active nav link
    (() => {
      const links = Array.from(document.querySelectorAll('#mainNavbar .nav-link[href^="#"]'));
      const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

      const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const id = entry.target.getAttribute('id');
          links.forEach(l => l.classList.remove('is-active'));
          const active = links.find(l => l.getAttribute('href') === '#' + id);
          if (active) active.classList.add('is-active');
        });
      }, { rootMargin: '-35% 0px -60% 0px', threshold: 0.01 });

      sections.forEach(s => io.observe(s));
    })();

    // Billing toggle (monthly/yearly)
    (() => {
      const buttons = document.querySelectorAll('[data-billing]');
      const monthlyPrices = document.querySelectorAll('[data-price="monthly"]');
      const yearlyPrices = document.querySelectorAll('[data-price="yearly"]');
      const monthlyUnits = document.querySelectorAll('[data-unit="monthly"]');
      const yearlyUnits = document.querySelectorAll('[data-unit="yearly"]');
      const ctas = document.querySelectorAll('[data-cta]');

      const setBilling = (mode) => {
        buttons.forEach(b => b.classList.toggle('active', b.dataset.billing === mode));
        const showMonthly = mode === 'monthly';

        monthlyPrices.forEach(el => el.classList.toggle('d-none', !showMonthly));
        yearlyPrices.forEach(el => el.classList.toggle('d-none', showMonthly));
        monthlyUnits.forEach(el => el.classList.toggle('d-none', !showMonthly));
        yearlyUnits.forEach(el => el.classList.toggle('d-none', showMonthly));

        // Update duration query param only if present (logged-in payment route)
        ctas.forEach(a => {
          const href = a.getAttribute('href') || '';
          if (!href.includes('duration=')) return;
          const url = new URL(href, window.location.origin);
          url.searchParams.set('duration', mode === 'monthly' ? 'monthly' : 'yearly');
          a.setAttribute('href', url.pathname + url.search);
        });
      };

      buttons.forEach(btn => btn.addEventListener('click', () => setBilling(btn.dataset.billing)));
    })();

    // Comparison filters (compact & modern)
    (() => {
      const chips = document.querySelectorAll('.chip[data-compare-filter]');
      const rows = document.querySelectorAll('tr[data-compare-row]');

      const setFilter = (key) => {
        chips.forEach(c => c.classList.toggle('active', c.dataset.compareFilter === key));
        rows.forEach(r => {
          const g = r.dataset.compareRow;
          if (key === 'all') r.classList.remove('d-none');
          else r.classList.toggle('d-none', g !== key && g !== 'all');
        });
      };

      chips.forEach(ch => ch.addEventListener('click', () => setFilter(ch.dataset.compareFilter)));
    })();
  </script>
</body>
</html>