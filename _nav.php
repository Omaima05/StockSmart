<?php
/**
 * _nav.php — Navigation partagée
 * Inclure avec : require_once '../_nav.php'; (depuis pages/)
 *                require_once '_nav.php';    (depuis racine)
 */
$current = basename($_SERVER['PHP_SELF']);
$isPages = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$base    = $isPages ? '../' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php
    $titles = [
      'dashboard.php'  => 'Dashboard',
      'produits.php'   => 'Inventaire',
      'categories.php' => 'Categories',
      'mouvements.php' => 'Mouvements',
      'admin.php'      => 'Administration',
    ];
    echo 'StockSmart Pro' . (isset($titles[$current]) ? ' — ' . $titles[$current] : '');
  ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body>

<header style="background:#0d1117;color:#fff;padding:0 2rem;height:56px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.06);">
  <a href="<?= $base ?>index.php" style="display:flex;align-items:center;gap:9px;text-decoration:none;">
    <div style="width:28px;height:28px;background:#e94560;border-radius:7px;display:flex;align-items:center;justify-content:center;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="#fff"><path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zm-10 7H6v-4h4v4zm8 0h-6v-4h6v4z"/></svg>
    </div>
    <span style="font-weight:800;font-size:14px;color:#fff;letter-spacing:-.3px;">StockSmart <span style="color:#e94560;">Pro</span></span>
  </a>

  <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
    <?php if (!empty($_SESSION['enseigne_logo'])): ?>
      <img src="<?= htmlspecialchars($_SESSION['enseigne_logo']) ?>" alt="logo enseigne" style="height:26px;object-fit:contain;border-radius:4px;background:#fff;padding:1px;">
    <?php endif; ?>
    <?php if (!empty($_SESSION['enseigne_nom'])): ?>
      <span style="background:rgba(255,255,255,.08);padding:4px 11px;border-radius:20px;font-weight:700;color:rgba(255,255,255,.9);font-size:12px;"><?= htmlspecialchars($_SESSION['enseigne_nom']) ?></span>
    <?php endif; ?>
    <span style="color:rgba(255,255,255,.35);font-size:11px;"><?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?></span>
    <span style="background:rgba(233,69,96,.15);color:#e94560;padding:3px 9px;border-radius:6px;font-size:10px;font-weight:800;text-transform:uppercase;"><?= htmlspecialchars($_SESSION['user_role'] ?? '') ?></span>
    <a href="<?= $base ?>pages/logout.php" style="color:rgba(255,100,100,.8);text-decoration:none;font-weight:600;font-size:12px;padding:5px 10px;border:1px solid rgba(255,100,100,.2);border-radius:6px;">Deconnexion</a>
  </div>
</header>

<nav style="background:#161b22;padding:0 2rem;display:flex;gap:2px;border-bottom:1px solid rgba(255,255,255,.05);">
  <?php
  $links = [
    'index.php'      => ['Vitrine',       $base . 'index.php'],
    'dashboard.php'  => ['Dashboard',     $base . 'dashboard.php'],
    'produits.php'   => ['Produits',      $base . 'pages/produits.php'],
    'categories.php' => ['Categories',   $base . 'pages/categories.php'],
    'mouvements.php' => ['Mouvements',    $base . 'pages/mouvements.php'],
  ];
  if (isAdmin()) {
    $links['admin.php'] = ['Admin', $base . 'pages/admin.php'];
  }
  foreach ($links as $file => [$label, $href]):
    $active = ($current === $file);
    $style  = $active
      ? 'background:rgba(233,69,96,.12);color:#e94560;'
      : 'color:rgba(255,255,255,.45);';
  ?>
    <a href="<?= $href ?>"
       style="<?= $style ?> text-decoration:none;font-size:13px;font-weight:600;padding:12px 15px;display:flex;align-items:center;transition:color .15s;">
      <?= $label ?>
    </a>
  <?php endforeach; ?>
</nav>
