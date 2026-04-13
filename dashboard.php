<?php
require_once 'config.php';
requireLogin();

$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$user_role   = $_SESSION['user_role']   ?? 'employe';
$enseigne_id = $_SESSION['enseigne_id'] ?? 0; 

// --- 1. Statistiques (KPIs) ---
// On compte les produits de l'enseigne + les produits de base (0 ou NULL)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE enseigne_id = ? OR enseigne_id = 0 OR enseigne_id IS NULL");
$stmt->execute([$enseigne_id]);
$totalProduits = (int)$stmt->fetchColumn();

$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Valeur stock (Enseigne + base)
$stmt = $pdo->prepare("SELECT SUM(prix * quantite) FROM produits WHERE enseigne_id = ? OR enseigne_id = 0 OR enseigne_id IS NULL");
$stmt->execute([$enseigne_id]);
$valeurStock = (float)($stmt->fetchColumn() ?: 0);

// Alertes (Enseigne + base)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE quantite <= seuil_alerte AND (enseigne_id = ? OR enseigne_id = 0 OR enseigne_id IS NULL)");
$stmt->execute([$enseigne_id]);
$totalAlertes = (int)$stmt->fetchColumn();

// --- 2. Stocks critiques (RETOUR DES PRODUITS DE BASE) ---
$stmt = $pdo->prepare("
    SELECT p.nom, p.quantite, p.seuil_alerte, p.marque, p.image, c.nom AS categorie
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id
    WHERE (p.enseigne_id = ? OR p.enseigne_id = 0 OR p.enseigne_id IS NULL)
    AND p.quantite <= p.seuil_alerte
    ORDER BY p.quantite ASC LIMIT 6
");
$stmt->execute([$enseigne_id]);
$produitsAlerte = $stmt->fetchAll();
?>
<?php require_once '_nav.php'; ?>

<style>
  /* Le CSS reste identique à ta version précédente */
  .container { padding:2rem 2.5rem; max-width:1400px; margin:0 auto; }
  .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; }
  .page-title  { font-size:20px; font-weight:800; color:#0d1117; }
  .user-pill   { background:#fff; padding:7px 16px; border-radius:50px; border:1px solid #e2e8f0; font-weight:700; font-size:13px; }
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
  .kpi      { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:1.25rem; position:relative; }
  .kpi-icon { position:absolute; top:1rem; right:1rem; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
  .main-grid { display:grid; grid-template-columns:1fr 300px; gap:1.5rem; }
  .card      { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:1.5rem; }
  table { width:100%; border-collapse:collapse; }
  thead th { text-align:left; font-size:11px; color:#64748b; text-transform:uppercase; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }
  td { padding:14px 0; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
  .prod-cell { display:flex; align-items:center; gap:12px; }
  .img-wrap  { width:50px; height:50px; border-radius:10px; overflow:hidden; background:#f8fafc; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; }
  .prod-img-sm { max-width:90%; max-height:90%; object-fit:contain; }
  .badge { padding:3px 9px; border-radius:10px; font-size:11px; font-weight:700; }
  .badge-crit { background:#fee2e2; color:#991b1b; }
  .badge-warn { background:#fef3c7; color:#92400e; }
  .stock-bar { width:100px; height:6px; background:#f1f5f9; border-radius:3px; }
  .stock-progress { height:100%; border-radius:3px; }
  .short-item { display:flex; align-items:center; gap:12px; padding:12px; background:#f8fafc; border-radius:10px; text-decoration:none; color:#0d1117; font-size:13px; font-weight:600; border:1px solid #e2e8f0; margin-bottom:8px; }
  .short-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
</style>

<div class="container">
  <div class="page-header">
    <div>
      <div class="page-title">Tableau de bord</div>
      <div style="font-size:13px; color:#64748b;"><?= date('d F Y') ?> — Bonjour <?= htmlspecialchars($user_prenom) ?></div>
    </div>
    <div class="user-pill"><?= htmlspecialchars($user_prenom) ?> <span style="color:#e94560; font-size:10px; margin-left:5px;">● <?= strtoupper($user_role) ?></span></div>
  </div>

  <div class="kpi-grid">
    <div class="kpi">
      <div class="kpi-icon" style="background:#ede9fe;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
      </div>
      <div class="kpi-label">Total produits</div>
      <div class="kpi-val"><?= $totalProduits ?></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:#e0f2fe; color:#0ea5e9;">≡</div>
      <div class="kpi-label">Catégories</div>
      <div class="kpi-val"><?= $totalCategories ?></div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:#dcfce7; color:#166534;">$</div>
      <div class="kpi-label">Valeur stock</div>
      <div class="kpi-val"><?= number_format($valeurStock, 0, ',', ' ') ?> €</div>
    </div>
    <div class="kpi">
      <div class="kpi-icon" style="background:#fee2e2; color:#991b1b;">▲</div>
      <div class="kpi-label">Alertes</div>
      <div class="kpi-val" style="color:#e94560;"><?= $totalAlertes ?></div>
    </div>
  </div>

  <div class="main-grid">
    <div class="card">
      <div style="font-size:14px; font-weight:700; color:#e94560; margin-bottom:1.25rem;">Stocks critiques</div>
      <table>
        <thead><tr><th>Article</th><th>Stock</th><th>Niveau</th><th>Etat</th></tr></thead>
        <tbody>
          <?php foreach ($produitsAlerte as $p): 
            $img = !empty($p['image']) && file_exists($p['image']) ? $p['image'] : '';
            $search = htmlspecialchars($p['marque'] . ' ' . $p['nom']);
            $percent = $p['seuil_alerte'] > 0 ? min(100, ($p['quantite'] / $p['seuil_alerte']) * 100) : 100;
          ?>
          <tr>
            <td>
              <div class="prod-cell">
                <div class="img-wrap">
                  <img class="prod-img-sm" src="<?= $img ?: 'https://ui-avatars.com/api/?name='.urlencode($p['nom']).'&background=f1f5f9&color=64748b' ?>" 
                       data-search="<?= $search ?>">
                </div>
                <div>
                  <div style="font-weight:700; font-size:13px;"><?= htmlspecialchars($p['nom']) ?></div>
                  <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($p['categorie'] ?? 'Divers') ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:13px;"><?= $p['quantite'] ?> / <?= $p['seuil_alerte'] ?></td>
            <td>
                <div class="stock-bar">
                    <div class="stock-progress" style="width:<?= $percent ?>%; background:<?= $p['quantite']==0?'#e94560':'#f59e0b' ?>;"></div>
                </div>
            </td>
            <td><span class="badge <?= $p['quantite']==0?'badge-crit':'badge-warn' ?>"><?= $p['quantite']==0?'RUPTURE':'FAIBLE' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div>
      <div class="card">
        <div style="font-size:14px; font-weight:700; margin-bottom:1rem;">Raccourcis</div>
        <a href="pages/mouvements.php" class="short-item"><div class="short-icon" style="background:#fee2e2;color:#e94560;">↕</div> Mouvements</a>
        <a href="pages/produits.php" class="short-item"><div class="short-icon" style="background:#ede9fe;color:#6366f1;">+</div> Produits</a>
        <a href="pages/categories.php" class="short-item"><div class="short-icon" style="background:#e0f2fe;color:#0ea5e9;">≡</div> Catégories</a>
      </div>
    </div>
  </div>
</div>

<script src="js/off-photos.js"></script>
</body>
</html>