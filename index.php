<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StockSmart Pro — Gestion de stock multi-enseignes</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --red:    #e94560;
      --dark:   #0d1117;
      --dark2:  #161b22;
      --mid:    #64748b;
      --border: #e2e8f0;
      --light:  #f8fafc;
      --rose:   #fff7f5;
    }
    body { font-family: 'DM Sans', system-ui, sans-serif; background: #fff; color: #0d1117; line-height: 1.6; overflow-x: hidden; }

    /* ANIMATIONS */
    @keyframes fadeUp  { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
    @keyframes float   { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-8px);} }
    @keyframes pulse   { 0%,100%{opacity:1;} 50%{opacity:.35;} }
    @keyframes ticker  { 0%{transform:translateX(0);} 100%{transform:translateX(-50%);} }
    .a1{animation:fadeUp .7s .05s both;} .a2{animation:fadeUp .7s .15s both;}
    .a3{animation:fadeUp .7s .25s both;} .a4{animation:fadeUp .7s .35s both;}
    .a5{animation:fadeUp .7s .5s both;}
    .float1{animation:float 4s ease-in-out infinite;}
    .float2{animation:float 4s 1s ease-in-out infinite;}
    .float3{animation:float 4s 2s ease-in-out infinite;}
    .livepulse{animation:pulse 2s infinite;}

    /* NAV */
    .nav {
      display:flex; align-items:center; justify-content:space-between;
      padding:0 2.5rem; height:64px;
      background:rgba(255,255,255,.96);
      border-bottom:1px solid var(--border);
      backdrop-filter:blur(12px);
      position:sticky; top:0; z-index:100;
    }
    .logo { display:flex; align-items:center; gap:9px; text-decoration:none; }
    .logo-sq { width:32px; height:32px; background:var(--red); border-radius:8px; display:flex; align-items:center; justify-content:center; }
    .logo-sq svg { width:16px; height:16px; fill:#fff; }
    .logo-name { font-size:16px; font-weight:800; color:#0d1117; letter-spacing:-.3px; }
    .logo-name span { color:var(--red); }
    .nav-links { display:flex; gap:2rem; }
    .nav-links a { font-size:14px; color:var(--mid); text-decoration:none; font-weight:500; transition:color .15s; }
    .nav-links a:hover { color:#0d1117; }
    .nav-right { display:flex; gap:10px; align-items:center; }
    .btn-ghost { font-size:14px; color:#0d1117; padding:8px 16px; border-radius:8px; border:1px solid var(--border); background:#fff; font-weight:600; text-decoration:none; }
    .btn-cta   { font-size:14px; color:#fff; padding:9px 20px; border-radius:8px; border:none; background:var(--red); font-weight:700; text-decoration:none; transition:box-shadow .15s,transform .15s; }
    .btn-cta:hover { box-shadow:0 6px 20px rgba(233,69,96,.35); transform:translateY(-1px); }

    /* HERO */
    .hero {
      background:var(--rose);
      min-height:92vh;
      display:grid; grid-template-columns:1fr 1fr;
      align-items:center;
      padding:0 2.5rem 0 3rem;
      position:relative; overflow:hidden;
    }
    .hero-blob1 { position:absolute; width:500px; height:500px; border-radius:50%; background:rgba(233,69,96,.07); top:-150px; right:20%; pointer-events:none; }
    .hero-blob2 { position:absolute; width:300px; height:300px; border-radius:50%; background:rgba(233,69,96,.05); bottom:-80px; left:10%; pointer-events:none; }
    .hero-left  { padding:4rem 3rem 4rem 0; position:relative; z-index:2; }
    .hero-pill  { display:inline-flex; align-items:center; gap:7px; background:#fff; border:1px solid #fecdd3; border-radius:20px; padding:5px 14px 5px 8px; margin-bottom:1.75rem; }
    .pill-dot   { width:20px; height:20px; border-radius:50%; background:var(--red); display:flex; align-items:center; justify-content:center; }
    .pill-dot svg { width:10px; height:10px; fill:#fff; }
    .hero-pill span { font-size:12px; color:var(--red); font-weight:700; }
    .hero h1   { font-size:52px; font-weight:900; color:#0d1117; line-height:1.08; letter-spacing:-2px; margin-bottom:1.25rem; }
    .hero h1 em { color:var(--red); font-style:normal; }
    .hero-sub  { font-size:16px; color:var(--mid); line-height:1.8; margin-bottom:2rem; max-width:420px; }
    .hero-ctas { display:flex; gap:12px; margin-bottom:2rem; flex-wrap:wrap; }
    .btn-hero-main { padding:14px 28px; border-radius:10px; background:var(--red); color:#fff; font-size:15px; font-weight:700; border:none; cursor:pointer; text-decoration:none; display:inline-block; transition:box-shadow .15s,transform .15s; }
    .btn-hero-main:hover { box-shadow:0 10px 28px rgba(233,69,96,.4); transform:translateY(-2px); }
    .btn-hero-sec { padding:14px 28px; border-radius:10px; background:#fff; color:#0d1117; font-size:15px; font-weight:600; border:1.5px solid var(--border); cursor:pointer; text-decoration:none; display:inline-block; }
    .btn-hero-sec:hover { border-color:var(--red); color:var(--red); }
    .hero-trust { display:flex; gap:1.25rem; flex-wrap:wrap; }
    .trust-i { display:flex; align-items:center; gap:5px; font-size:13px; color:#94a3b8; }
    .trust-i svg { width:13px; height:13px; fill:#22c55e; }

    .hero-right { position:relative; height:560px; display:flex; align-items:center; justify-content:center; }
    .hero-img-wrap { width:100%; height:460px; border-radius:24px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.12); }
    .hero-img-wrap img { width:100%; height:100%; object-fit:cover; object-position:center; }

    /* Floating cards */
    .fc { position:absolute; background:#fff; border-radius:14px; border:1px solid var(--border); padding:12px 16px; box-shadow:0 8px 30px rgba(0,0,0,.08); }
    .fc-alert { top:30px; left:-55px; min-width:175px; z-index:5; }
    .fc-alert-top { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .fc-alert-icon { width:30px; height:30px; border-radius:8px; background:#fee2e2; display:flex; align-items:center; justify-content:center; }
    .fc-alert-icon svg { width:14px; height:14px; fill:var(--red); }
    .fc-alert-title { font-size:12px; font-weight:700; color:#0d1117; }
    .fc-alert-sub { font-size:10px; color:#aaa; margin-top:1px; }
    .fc-row { display:flex; justify-content:space-between; align-items:center; margin-top:5px; }
    .fc-name { font-size:11px; color:#555; }
    .fc-b { font-size:9px; padding:2px 7px; border-radius:7px; font-weight:700; }
    .fc-stat { bottom:80px; left:-65px; display:flex; align-items:center; gap:10px; z-index:5; }
    .fc-stat-icon { width:36px; height:36px; border-radius:10px; background:#dcfce7; display:flex; align-items:center; justify-content:center; }
    .fc-stat-icon svg { width:18px; height:18px; fill:#16a34a; }
    .fc-stat-val { font-size:22px; font-weight:900; color:#0d1117; line-height:1; }
    .fc-stat-lbl { font-size:10px; color:#aaa; margin-top:2px; }
    .fc-move { top:45%; right:-55px; transform:translateY(-50%); background:#0d1117; min-width:160px; z-index:5; }
    .fc-move-lbl { font-size:9px; color:rgba(255,255,255,.35); text-transform:uppercase; letter-spacing:.5px; margin-bottom:7px; }
    .fc-move-row { display:flex; justify-content:space-between; margin-bottom:4px; }
    .fc-move-name { font-size:11px; color:rgba(255,255,255,.7); }
    .fc-move-val  { font-size:11px; font-weight:800; }

    /* TICKER */
    .ticker { background:#fff; border-top:1px solid var(--border); border-bottom:1px solid var(--border); padding:1rem 0; overflow:hidden; }
    .ticker-inner { display:flex; gap:3.5rem; animation:ticker 22s linear infinite; width:max-content; }
    .t-item { display:flex; align-items:center; gap:8px; white-space:nowrap; font-size:14px; font-weight:700; letter-spacing:-.2px; }
    .t-dot { width:7px; height:7px; border-radius:50%; }

    /* STATS */
    .stats { background:var(--dark); padding:2.5rem; display:grid; grid-template-columns:repeat(3,1fr); text-align:center; gap:1rem; }
    .stat-val { font-size:34px; font-weight:900; color:#fff; letter-spacing:-1.5px; }
    .stat-val em { color:var(--red); font-style:normal; }
    .stat-lbl { font-size:13px; color:rgba(255,255,255,.35); margin-top:4px; }

    /* SECTION TITLES */
    .eyebrow { font-size:11px; font-weight:700; color:var(--red); text-transform:uppercase; letter-spacing:1px; margin-bottom:.6rem; }
    .sec-h   { font-size:36px; font-weight:900; color:#0d1117; letter-spacing:-1.5px; line-height:1.1; margin-bottom:.75rem; }
    .sec-h.w { color:#fff; }
    .sec-desc { font-size:15px; color:var(--mid); max-width:460px; margin:0 auto; line-height:1.75; }
    .sec-desc.w { color:rgba(255,255,255,.4); }

    /* GESKO CARDS */
    .gesko-section { padding:5rem 2.5rem; background:#fff; }
    .gesko-header  { margin-bottom:3rem; }
    .gesko-title   { font-size:34px; font-weight:900; color:#0d1117; letter-spacing:-1.5px; margin-bottom:.5rem; }
    .gesko-sub     { font-size:15px; color:var(--mid); }
    .gesko-grid    { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1.25rem; }
    .gesko-card    { background:#1a2332; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; transition:transform .2s; }
    .gesko-card:hover { transform:translateY(-4px); }
    .gesko-body    { padding:1.75rem 1.75rem 1.25rem; flex:1; }
    .gesko-tag     { font-size:10px; font-weight:700; color:var(--red); text-transform:uppercase; letter-spacing:.8px; margin-bottom:.75rem; }
    .gesko-name    { font-size:17px; font-weight:800; color:#fff; margin-bottom:1rem; line-height:1.3; }
    .gesko-bullet  { display:flex; align-items:flex-start; gap:8px; margin-bottom:.6rem; }
    .gesko-check   { width:15px; height:15px; border-radius:50%; background:rgba(233,69,96,.18); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:3px; }
    .gesko-check svg { width:8px; height:8px; fill:var(--red); }
    .gesko-text    { font-size:13px; color:rgba(255,255,255,.55); line-height:1.55; }
    .gesko-img     { height:190px; overflow:hidden; flex-shrink:0; }
    .gesko-img img { width:100%; height:100%; object-fit:cover; transition:transform .3s; }
    .gesko-card:hover .gesko-img img { transform:scale(1.04); }

    /* SPLIT SECTIONS */
    .split { padding:5rem 2.5rem; display:grid; grid-template-columns:1fr 1fr; gap:5rem; align-items:center; }
    .split.rev { direction:rtl; }
    .split.rev > * { direction:ltr; }
    .split.bg-light { background:var(--light); }
    .split-img { border-radius:20px; overflow:hidden; height:400px; position:relative; box-shadow:0 12px 40px rgba(0,0,0,.1); }
    .split-img img { width:100%; height:100%; object-fit:cover; }
    .split-fc { position:absolute; background:#fff; border-radius:12px; border:1px solid var(--border); padding:12px 16px; box-shadow:0 4px 20px rgba(0,0,0,.08); }
    .split-fc.tl { top:16px; left:16px; right:16px; }
    .split-fc.br { bottom:16px; left:16px; right:16px; background:#0d1117; border-color:rgba(255,255,255,.06); }
    .split-eyebrow { font-size:11px; font-weight:700; color:var(--red); text-transform:uppercase; letter-spacing:.8px; margin-bottom:.75rem; display:flex; align-items:center; gap:8px; }
    .split-eyebrow::before { content:''; width:20px; height:2px; background:var(--red); border-radius:2px; }
    .split-title { font-size:28px; font-weight:900; letter-spacing:-.8px; line-height:1.15; margin-bottom:.9rem; color:#0d1117; }
    .split-desc  { font-size:15px; color:var(--mid); line-height:1.8; margin-bottom:1.5rem; }
    .split-list  { display:flex; flex-direction:column; gap:10px; }
    .split-item  { display:flex; align-items:flex-start; gap:10px; }
    .split-check { width:18px; height:18px; border-radius:5px; background:rgba(233,69,96,.1); display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:3px; }
    .split-check svg { width:10px; height:10px; fill:var(--red); }
    .split-text  { font-size:14px; color:#374151; line-height:1.6; }
    .split-link  { display:inline-flex; align-items:center; gap:6px; font-size:14px; font-weight:600; color:var(--red); margin-top:1.5rem; text-decoration:none; }
    .split-link svg { width:14px; height:14px; fill:var(--red); }

    /* STEPS */
    .steps { padding:5rem 2.5rem; background:var(--dark); }
    .steps-center { text-align:center; margin-bottom:3rem; }
    .steps-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1px; background:rgba(255,255,255,.05); border-radius:16px; overflow:hidden; }
    .step-box   { background:var(--dark); padding:2rem 1.5rem; position:relative; }
    .step-n     { font-size:64px; font-weight:900; color:rgba(255,255,255,.03); position:absolute; top:.25rem; right:.75rem; line-height:1; }
    .step-icon  { width:42px; height:42px; border-radius:10px; background:rgba(233,69,96,.12); border:1px solid rgba(233,69,96,.2); display:flex; align-items:center; justify-content:center; margin-bottom:1rem; }
    .step-icon svg { width:20px; height:20px; fill:var(--red); }
    .step-h { font-size:14px; font-weight:700; color:#fff; margin-bottom:.4rem; }
    .step-p { font-size:13px; color:rgba(255,255,255,.35); line-height:1.65; }

    /* FAQ */
    .faq { padding:5rem 2.5rem; background:var(--light); }
    .faq-center { text-align:center; margin-bottom:3rem; }
    .faq-list { max-width:660px; margin:0 auto; display:flex; flex-direction:column; gap:.6rem; }
    .faq-item { background:#fff; border-radius:12px; border:1px solid var(--border); overflow:hidden; }
    .faq-q { padding:1.1rem 1.25rem; font-size:14px; font-weight:600; color:#0d1117; display:flex; align-items:center; justify-content:space-between; cursor:pointer; user-select:none; }
    .faq-q svg { width:16px; height:16px; fill:#aaa; flex-shrink:0; transition:transform .2s; }
    .faq-q.open svg { transform:rotate(180deg); }
    .faq-a { display:none; padding:0 1.25rem 1.1rem; font-size:13px; color:var(--mid); line-height:1.75; }
    .faq-a.open { display:block; }

    /* CTA */
    .cta { background:var(--rose); padding:5rem 2.5rem; text-align:center; position:relative; overflow:hidden; }
    .cta-blob { position:absolute; width:600px; height:400px; border-radius:50%; background:rgba(233,69,96,.06); top:50%; left:50%; transform:translate(-50%,-50%); }
    .cta h2   { font-size:40px; font-weight:900; color:#0d1117; letter-spacing:-1.5px; margin-bottom:.75rem; position:relative; }
    .cta h2 em { color:var(--red); font-style:normal; }
    .cta p    { font-size:15px; color:var(--mid); margin-bottom:2rem; position:relative; }
    .cta-btns { display:flex; justify-content:center; gap:12px; position:relative; flex-wrap:wrap; }
    .cta-btn-r { padding:14px 30px; border-radius:10px; background:var(--red); color:#fff; font-size:15px; font-weight:700; border:none; cursor:pointer; text-decoration:none; display:inline-block; transition:all .15s; }
    .cta-btn-r:hover { box-shadow:0 10px 30px rgba(233,69,96,.4); transform:translateY(-2px); }
    .cta-btn-o { padding:14px 30px; border-radius:10px; background:#fff; color:#0d1117; font-size:14px; font-weight:600; border:1.5px solid var(--border); cursor:pointer; text-decoration:none; display:inline-block; }
    .cta-btn-o:hover { border-color:var(--red); color:var(--red); }

    /* FOOTER */
    .footer { background:var(--dark); padding:3rem 2.5rem; }
    .footer-top { display:grid; grid-template-columns:2fr 1fr 1fr; gap:3rem; margin-bottom:2.5rem; }
    .footer-brand { font-size:15px; font-weight:800; color:#fff; margin-bottom:.5rem; }
    .footer-brand span { color:var(--red); }
    .footer-desc { font-size:13px; color:rgba(255,255,255,.28); line-height:1.7; max-width:220px; }
    .footer-col-title { font-size:11px; font-weight:700; color:rgba(255,255,255,.35); text-transform:uppercase; letter-spacing:.6px; margin-bottom:.875rem; }
    .footer-col a { display:block; font-size:13px; color:rgba(255,255,255,.22); text-decoration:none; margin-bottom:.5rem; transition:color .15s; }
    .footer-col a:hover { color:rgba(255,255,255,.65); }
    .footer-bottom { display:flex; align-items:center; justify-content:space-between; padding-top:2rem; border-top:1px solid rgba(255,255,255,.05); }
    .footer-copy { font-size:12px; color:rgba(255,255,255,.18); }
    .footer-copy em { color:var(--red); font-style:normal; }

    /* RESPONSIVE */
    @media(max-width:900px){
      .hero{grid-template-columns:1fr;min-height:auto;padding:3rem 1.5rem;}
      .hero-right{display:none;}
      .hero h1{font-size:36px;}
      .gesko-grid{grid-template-columns:1fr;}
      .split{grid-template-columns:1fr;gap:2rem;}
      .split.rev{direction:ltr;}
      .steps-grid{grid-template-columns:1fr 1fr;}
      .footer-top{grid-template-columns:1fr 1fr;gap:2rem;}
      .nav-links{display:none;}
      .stats{grid-template-columns:1fr 1fr;}
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="nav">
  <a href="index.php" class="logo">
    <div class="logo-sq"><svg viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zm-10 7H6v-4h4v4zm8 0h-6v-4h6v4zM4 3h16v2H4zM4 19h16v2H4z"/></svg></div>
    <span class="logo-name">StockSmart <span>Pro</span></span>
  </a>
  <div class="nav-links">
    <a href="#fonctionnalites">Fonctionnalites</a>
    <a href="#comment">Comment ca marche</a>
    <a href="#faq">FAQ</a>
  </div>
  <div class="nav-right">
    <a href="pages/login.php" class="btn-ghost">Se connecter</a>
    <a href="pages/register.php" class="btn-cta">Commencer</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-blob1"></div>
  <div class="hero-blob2"></div>
  <div class="hero-left">
    <div class="hero-pill a1">
      <div class="pill-dot"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div>
      <span>SaaS · Multi-enseignes · Temps reel</span>
    </div>
    <h1 class="a2">La gestion de stock<br>qui vous <em>simplifie</em><br>la vie.</h1>
    <p class="hero-sub a3">Photos automatiques, alertes instantanees, interface personnalisee pour chaque enseigne. Concu pour les equipes terrain.</p>
    <div class="hero-ctas a4">
      <a href="pages/register.php" class="btn-hero-main">Creer mon espace</a>
      <a href="pages/login.php" class="btn-hero-sec">Se connecter</a>
    </div>
    <div class="hero-trust a4">
      <div class="trust-i"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg>Données sécurisées</div>
      <div class="trust-i"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg>5 min pour demarrer</div>
      <div class="trust-i"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg>Multi-enseignes natif</div>
    </div>
  </div>
  <div class="hero-right a5">
    <div class="fc fc-alert float1">
      <div class="fc-alert-top">
        <div class="fc-alert-icon"><svg viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
        <div><div class="fc-alert-title">3 alertes stock</div><div class="fc-alert-sub">Aujourd'hui</div></div>
      </div>
      <div class="fc-row"><span class="fc-name">Jus Orange 1L</span><span class="fc-b" style="background:#fee2e2;color:#991b1b;">5/50</span></div>
      <div class="fc-row"><span class="fc-name">Pain de mie</span><span class="fc-b" style="background:#fee2e2;color:#991b1b;">8/45</span></div>
      <div class="fc-row"><span class="fc-name">Cahier 96p</span><span class="fc-b" style="background:#fef3c7;color:#92400e;">12/30</span></div>
    </div>
    <div class="hero-img-wrap">
      <img src="assets/img/hero.jpg" alt="Employe en magasin"/>
    </div>
    <div class="fc fc-stat float2">
      <div class="fc-stat-icon"><svg viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg></div>
      <div><div class="fc-stat-val">+12%</div><div class="fc-stat-lbl">Valeur stock ce mois</div></div>
    </div>
    <div class="fc fc-move float3">
      <div class="fc-move-lbl">Mouvements · Auj.</div>
      <div class="fc-move-row"><span class="fc-move-name">Filets cabillaud</span><span class="fc-move-val" style="color:#22c55e;">+6</span></div>
      <div class="fc-move-row"><span class="fc-move-name">Cahier 96p</span><span class="fc-move-val" style="color:#e94560;">-5</span></div>
      <div class="fc-move-row"><span class="fc-move-name">Yaourt nature</span><span class="fc-move-val" style="color:#22c55e;">+12</span></div>
    </div>
  </div>
</section>

<!-- TICKER -->
<div class="ticker">
  <div class="ticker-inner">
    <div class="t-item"><div class="t-dot" style="background:#003189;"></div><span style="color:#003189;">CARREFOUR</span></div>
    <div class="t-item"><div class="t-dot" style="background:#009900;"></div><span style="color:#009900;">LECLERC</span></div>
    <div class="t-item"><div class="t-dot" style="background:#e30613;"></div><span style="color:#e30613;">AUCHAN</span></div>
    <div class="t-item"><div class="t-dot" style="background:#ff6b00;"></div><span style="color:#ff6b00;">INTERMARCH&Eacute;</span></div>
    <div class="t-item"><div class="t-dot" style="background:#6b2d8b;"></div><span style="color:#6b2d8b;">CASINO</span></div>
    <div class="t-item"><div class="t-dot" style="background:#003189;"></div><span style="color:#003189;">CARREFOUR</span></div>
    <div class="t-item"><div class="t-dot" style="background:#009900;"></div><span style="color:#009900;">LECLERC</span></div>
    <div class="t-item"><div class="t-dot" style="background:#e30613;"></div><span style="color:#e30613;">AUCHAN</span></div>
    <div class="t-item"><div class="t-dot" style="background:#ff6b00;"></div><span style="color:#ff6b00;">INTERMARCH&Eacute;</span></div>
    <div class="t-item"><div class="t-dot" style="background:#6b2d8b;"></div><span style="color:#6b2d8b;">CASINO</span></div>
  </div>
</div>

<!-- STATS — sans "gratuit", juste les stats pro -->
<div class="stats">
  <div><div class="stat-val">+<em>500</em></div><div class="stat-lbl">Magasins actifs</div></div>
  <div><div class="stat-val">99.<em>9</em>%</div><div class="stat-lbl">Disponibilite garantie</div></div>
  <div><div class="stat-val"><em>5</em> min</div><div class="stat-lbl">Pour demarrer</div></div>
</div>

<!-- FONCTIONNALITES -->
<section class="gesko-section" id="fonctionnalites">
  <div class="gesko-header">
    <div class="eyebrow">Fonctionnalites</div>
    <div class="gesko-title">Optimisez la gestion de votre stock.</div>
    <div class="gesko-sub">Tout ce dont vos equipes ont besoin, en un seul endroit.</div>
  </div>
  <div class="gesko-grid">
    <div class="gesko-card">
      <div class="gesko-body">
        <div class="gesko-tag">Inventaire</div>
        <div class="gesko-name">Stock Temps Reel</div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Niveaux de stock avec jauges visuelles colorees</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Alertes automatiques en cas de rupture imminente</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Photos produits via Open Food Facts automatiquement</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Seuils critiques personnalisables par produit</div></div>
      </div>
      <div class="gesko-img"><img src="assets/img/card-stock.jpg" alt="Gestion stock"/></div>
    </div>
    <div class="gesko-card">
      <div class="gesko-body">
        <div class="gesko-tag">Tracabilite</div>
        <div class="gesko-name">Suivi Complet des Mouvements</div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Chaque entree et sortie tracee avec nom et heure</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Historique complet par produit et par employe</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Distinction visuelle entrees et sorties</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Export rapports CSV et PDF</div></div>
      </div>
      <div class="gesko-img"><img src="assets/img/card-tracabilite.jpg" alt="Tracabilite"/></div>
    </div>
    <div class="gesko-card">
      <div class="gesko-body">
        <div class="gesko-tag">Multi-enseignes</div>
        <div class="gesko-name">Un Outil, Plusieurs Enseignes</div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Espaces cloisonnes et securises par enseigne</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Interface personnalisee avec logo de l'enseigne</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Roles Admin et Employe par enseigne</div></div>
        <div class="gesko-bullet"><div class="gesko-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><div class="gesko-text">Nouvelle enseigne deployee en 5 minutes</div></div>
      </div>
      <div class="gesko-img"><img src="assets/img/card-multienseignes.jpg" alt="Multi-enseignes"/></div>
    </div>
  </div>
</section>

<!-- SPLIT 1 -->
<section class="split bg-light" id="comment">
  <div class="split-img">
    <img src="assets/img/split-inventaire.jpg" alt="Employe gerant l'inventaire"/>
    <div class="split-fc tl">
      <div style="display:flex;align-items:center;gap:5px;margin-bottom:5px;">
        <div class="livepulse" style="width:7px;height:7px;border-radius:50%;background:#22c55e;"></div>
        <span style="font-size:9px;color:#aaa;text-transform:uppercase;letter-spacing:.4px;">Activite en direct</span>
      </div>
      <div style="font-size:13px;font-weight:700;color:#0d1117;">Filets cabillaud +6 unites</div>
      <div style="font-size:11px;color:#aaa;margin-top:2px;">Thomas R. · Il y a 2 min</div>
    </div>
  </div>
  <div>
    <div class="split-eyebrow">Inventaire intelligent</div>
    <h2 class="split-title">Votre stock, toujours sous controle.</h2>
    <p class="split-desc">Visualisez l'etat de chaque produit d'un coup d'oeil. Les jauges colorees indiquent ce qui est critique, faible ou normal.</p>
    <div class="split-list">
      <div class="split-item"><div class="split-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><span class="split-text">Jauges visuelles avec seuils personnalisables par produit</span></div>
      <div class="split-item"><div class="split-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><span class="split-text">Alertes automatiques des qu'un produit passe en zone critique</span></div>
      <div class="split-item"><div class="split-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><span class="split-text">Photos produits recuperees via Open Food Facts, sans import</span></div>
    </div>
    <a href="pages/register.php" class="split-link">Commencer maintenant <svg viewBox="0 0 16 16"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
  </div>
</section>

<!-- SPLIT 2 -->
<section class="split rev">
  <div class="split-img">
    <img src="assets/img/tracabilite-expert.jpg" alt="Suivi des mouvements de stock"/>
    <div class="split-fc br">
      <div style="font-size:9px;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px;">Mouvements · Aujourd'hui</div>
      <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid rgba(255,255,255,.06);"><span style="font-size:11px;color:rgba(255,255,255,.7);">Yaourt nature x8</span><span style="font-size:12px;font-weight:800;color:#22c55e;">+12</span></div>
      <div style="display:flex;justify-content:space-between;padding:5px 0;"><span style="font-size:11px;color:rgba(255,255,255,.7);">Cahier 96p</span><span style="font-size:12px;font-weight:800;color:#e94560;">-5</span></div>
    </div>
  </div>
  <div>
    <div class="split-eyebrow">Tracabilite totale</div>
    <h2 class="split-title">Chaque mouvement, signe et date.</h2>
    <p class="split-desc">Qui a sorti quoi ? A quelle heure ? Chaque operation est enregistree avec le nom de l'employe, l'heure exacte et la quantite.</p>
    <div class="split-list">
      <div class="split-item"><div class="split-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><span class="split-text">Historique complet par produit, par employe, par date</span></div>
      <div class="split-item"><div class="split-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><span class="split-text">Entrees et sorties distinguees visuellement</span></div>
      <div class="split-item"><div class="split-check"><svg viewBox="0 0 12 12"><path d="M2 6l3 3 5-5"/></svg></div><span class="split-text">Export CSV ou PDF de l'historique complet</span></div>
    </div>
    <a href="pages/register.php" class="split-link">Commencer maintenant <svg viewBox="0 0 16 16"><path d="M3 8h10M9 4l4 4-4 4"/></svg></a>
  </div>
</section>

<!-- STEPS -->
<section class="steps">
  <div class="steps-center">
    <div class="eyebrow" style="color:rgba(233,69,96,.8);">Comment ca marche</div>
    <h2 class="sec-h w">Operationnel en 4 etapes.</h2>
    <p class="sec-desc w">Pas de formation, pas de migration. 5 minutes chrono.</p>
  </div>
  <div class="steps-grid">
    <div class="step-box"><div class="step-n">01</div><div class="step-icon"><svg viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg></div><div class="step-h">Creez votre enseigne</div><div class="step-p">Nom, couleur, logo genere automatiquement. Votre espace isole est pret en 2 minutes.</div></div>
    <div class="step-box"><div class="step-n">02</div><div class="step-icon"><svg viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16"/></svg></div><div class="step-h">Ajoutez vos produits</div><div class="step-p">Tapez le nom, les photos arrivent seules. Definissez les seuils d'alerte par article.</div></div>
    <div class="step-box"><div class="step-n">03</div><div class="step-icon"><svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857"/></svg></div><div class="step-h">Invitez vos equipes</div><div class="step-p">Partagez votre code d'invitation. Vos employes rejoignent votre espace en quelques clics.</div></div>
    <div class="step-box"><div class="step-n">04</div><div class="step-icon"><svg viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg></div><div class="step-h">Gerez en temps reel</div><div class="step-p">Mouvements, alertes, rapports. Tout en live depuis n'importe quel appareil.</div></div>
  </div>
</section>

<!-- FAQ -->
<section class="faq" id="faq">
  <div class="faq-center">
    <div class="eyebrow">FAQ</div>
    <h2 class="sec-h">Questions frequentes.</h2>
    <p class="sec-desc">Tout ce que vous voulez savoir avant de commencer.</p>
  </div>
  <div class="faq-list">
    <div class="faq-item"><div class="faq-q" onclick="toggleFaq(this)">C'est quoi StockSmart Pro ?<svg viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg></div><div class="faq-a">StockSmart Pro est une plateforme de gestion de stock multi-enseignes. Chaque enseigne dispose de son propre espace cloisonne pour gerer son inventaire, ses mouvements et ses equipes.</div></div>
    <div class="faq-item"><div class="faq-q" onclick="toggleFaq(this)">Comment fonctionnent les photos automatiques ?<svg viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg></div><div class="faq-a">Quand vous ajoutez un produit avec son nom et sa marque, le systeme interroge l'API Open Food Facts. Si une photo est trouvee, elle s'affiche immediatement. Sinon, un avatar avec les initiales prend le relais.</div></div>
    <div class="faq-item"><div class="faq-q" onclick="toggleFaq(this)">Mes donnees sont-elles separees des autres enseignes ?<svg viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg></div><div class="faq-a">Oui, completement. Chaque enseigne est identifiee par un ID unique. Toutes les requetes sont filtrees par cet ID — il est techniquement impossible pour une enseigne d'acceder aux donnees d'une autre.</div></div>
    <div class="faq-item"><div class="faq-q" onclick="toggleFaq(this)">Comment inviter des employes dans mon enseigne ?<svg viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg></div><div class="faq-a">Apres avoir cree votre enseigne, vous recevez un code d'invitation unique (ex: NARMIN-2026). Partagez ce code a vos employes. Ils le saisissent lors de leur inscription et rejoignent automatiquement votre espace.</div></div>
    <div class="faq-item"><div class="faq-q" onclick="toggleFaq(this)">Est-ce que ca marche sur mobile ?<svg viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg></div><div class="faq-a">Oui, l'interface est entierement responsive. Les employes peuvent enregistrer leurs mouvements directement depuis leur telephone ou tablette, sans installer aucune application.</div></div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <div class="cta-blob"></div>
  <h2>Pret a moderniser<br>votre <em>stock</em> ?</h2>
  <p>Creez votre enseigne en 5 minutes. Sans engagement.</p>
  <div class="cta-btns">
    <a href="pages/register.php" class="cta-btn-r">Creer mon enseigne</a>
    <a href="pages/login.php" class="cta-btn-o">Se connecter</a>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-top">
    <div>
      <div class="footer-brand">StockSmart <span>Pro</span></div>
      <div class="footer-desc">La plateforme de gestion de stock multi-enseignes pour les equipes terrain.</div>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">Produit</div>
      <a href="#fonctionnalites">Fonctionnalites</a>
      <a href="#comment">Comment ca marche</a>
      <a href="#faq">FAQ</a>
    </div>
    <div class="footer-col">
      <div class="footer-col-title">Compte</div>
      <a href="pages/login.php">Se connecter</a>
      <a href="pages/register.php">Creer un compte</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">© <?php echo date('Y'); ?> StockSmart <em>Pro</em> · Tous droits reserves</div>
    <div class="footer-copy">Fait avec passion</div>
  </div>
</footer>

<script>
function toggleFaq(el) {
  const a = el.nextElementSibling;
  const open = a.classList.contains('open');
  document.querySelectorAll('.faq-a').forEach(x=>x.classList.remove('open'));
  document.querySelectorAll('.faq-q').forEach(x=>x.classList.remove('open'));
  if (!open) { a.classList.add('open'); el.classList.add('open'); }
}
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    const t = document.querySelector(a.getAttribute('href'));
    if (t) t.scrollIntoView({behavior:'smooth',block:'start'});
  });
});
</script>
</body>
</html>
