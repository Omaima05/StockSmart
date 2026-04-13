<?php
require_once 'config.php';
requireLogin();

$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$user_role   = $_SESSION['user_role']   ?? 'employe';
// On récupère l'ID de l'enseigne de l'utilisateur connecté
$enseigne_id = $_SESSION['enseigne_id'] ?? 0; 

// --- 1. Statistiques filtrées (KPIs) ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE enseigne_id = ?");
$stmt->execute([$enseigne_id]);
$totalProduits = (int)$stmt->fetchColumn();

// Catégories (Globales)
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Stock faible
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE quantite > 0 AND quantite <= seuil_alerte AND enseigne_id = ?");
$stmt->execute([$enseigne_id]);
$stockFaible = (int)$stmt->fetchColumn();

// Ruptures
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE quantite = 0 AND enseigne_id = ?");
$stmt->execute([$enseigne_id]);
$ruptures = (int)$stmt->fetchColumn();

// Valeur stock
$stmt = $pdo->prepare("SELECT SUM(prix * quantite) FROM produits WHERE enseigne_id = ?");
$stmt->execute([$enseigne_id]);
$valeurStock = (float)($stmt->fetchColumn() ?: 0);

// --- 2. Derniers mouvements ---
$stmt = $pdo->prepare("
    SELECT m.date_mouvement, m.type_mouvement, m.quantite, p.nom AS produit_nom
    FROM mouvements m 
    JOIN produits p ON m.produit_id = p.id
    WHERE p.enseigne_id = ?
    ORDER BY m.date_mouvement DESC LIMIT 8
");
$stmt->execute([$enseigne_id]);
$derniersMouvements = $stmt->fetchAll();

// --- 3. Produits en alerte (Stocks critiques) ---
$stmt = $pdo->prepare("
    SELECT p.nom, p.quantite, p.seuil_alerte, p.marque, p.image, c.nom AS categorie
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id
    WHERE p.quantite <= p.seuil_alerte AND p.enseigne_id = ?
    ORDER BY p.quantite ASC LIMIT 6
");
$stmt->execute([$enseigne_id]);
$produitsAlerte = $stmt->fetchAll();
?>
<?php require_once '_nav.php'; ?>

<style>
  .container { padding:2rem 2.5rem; max-width:1400px; margin:0 auto; }
  .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; }
  .page-title  { font-size:20px; font-weight:800; color:#0d1117; }
  .page-sub    { font-size:13px; color:#64748b; margin-top:3px; }
  .user-pill   { background:#fff; padding:7px 16px; border-radius:50px; border:1px solid #e2e8f0; font-weight:700; font-size:13px; display:flex; align-items:center; gap:8px; }
  .role-badge  { background:#fee2e2; color:#e94560; font-size:10px; padding:2px 8px; border-radius:5px; font-weight:800; text-transform:uppercase; }

  .quick-actions  { display:flex; gap:.75rem; margin-bottom:1.5rem; }
  .btn-dash       { padding:10px 18px; border-radius:9px; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
  .btn-dash-red   { background:#e94560; color:#fff; }
  .btn-dash-outline { background:#fff; color:#0d1117; border:1px solid #e2e8f0; }

  /* --- STYLE KPIs D'ORIGINE --- */
  .kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
  .kpi      { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:1.25rem; position:relative; overflow:hidden; }
  .kpi-bar  { position:absolute; top:0; left:0; right:0; height:3px; }
  .kpi-label{ font-size:10px; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:.5rem; }
  .kpi-val  { font-size:26px; font-weight:900; color:#0d1117; }
  .kpi-sub  { font-size:11px; color:#64748b; margin-top:5px; }
  .kpi-icon { position:absolute; top:1rem; right:1rem; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; }

  .main-grid { display:grid; grid-template-columns:1fr 300px; gap:1.5rem; }
  .card      { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:1.5rem; }
  .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
  .card-title  { font-size:14px; font-weight:700; color:#0d1117; }

  /* --- STYLE TABLEAU D'ORIGINE --- */
  table { width:100%; border-collapse:collapse; }
  thead th { text-align:left; font-size:11px; color:#64748b; text-transform:uppercase; padding-bottom:12px; border-bottom:1px solid #f1f5f9; }
  td { padding:14px 0; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

  .prod-cell { display:flex; align-items:center; gap:12px; }
  .img-wrap  { width:50px; height:50px; border-radius:10px; overflow:hidden; background:#f8fafc; border:1px solid #e2e8f0; flex-shrink:0; }
  .prod-img-sm { width:100%; height:100%; object-fit:cover; display:block; } /* Fix Cover */

  .badge { padding:3px 9px; border-radius:10px; font-size:11px; font-weight:700; }
  .badge-crit { background:#fee2e2; color:#991b1b; }
  .badge-warn { background:#fef3c7; color:#92400e; }

  /* Niveau d'origine */
  .stock-bar { width:100px; height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden; }
  .stock-progress { height:100%; border-radius:3px; }

  /* --- STYLE RACCOURCIS D'ORIGINE --- */
  .short-item { display:flex; align-items:center; gap:12px; padding:12px 14px; background:#f8fafc; border-radius:10px; text-decoration:none; color:#0d1117; font-size:13px; font-weight:600; border:1px solid #e2e8f0; margin-bottom:8px; }
  .short-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:17px; }
</style>

<div class="container">
  <div class="page-header">
    <div>
      <div class="page-title">Tableau de bord</div>
      <div class="page-sub"><?= date('d F Y') ?> — Bonjour <?= htmlspecialchars($user_prenom) ?></div>
    </div>
    <div class="user-pill"><?= htmlspecialchars($user_prenom) ?> <span class="role-badge"><?= strtoupper($user_role) ?></span></div>
  </div>

  <div class="quick-actions">
    <a href="pages/mouvements.php" class="btn-dash btn-dash-red">Enregistrer un mouvement</a>
    <a href="pages/produits.php" class="btn-dash btn-dash-outline">Gérer les produits</a>
  </div>

  <div class="kpi-grid">
    <div class="kpi">
      <div class="kpi-bar" style="background:#6366f1;"></div>
      
      <div class="kpi-icon" style="background:#ede9fe;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 4V3C6 1.89543 6.89543 1 8 1H12C13.1046 1 14 1.89543 14 3V4M6 4H4C2.89543 4 2 4.89543 2 6V15C2 16.1046 2.89543 17 4 17H16C17.1046 17 18 16.1046 18 15V6C18 4.89543 17.1046 4 16 4H14M6 4H14M10 11C11.1046 11 12 10.1046 12 9C12 7.89543 11.1046 7 10 7C8.89543 7 8 7.89543 8 9C8 10.1046 8.89543 11 10 11Z" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="kpi-label">Total produits</div>
      <div class="kpi-val"><?= $totalProduits ?></div>
      <div class="kpi-sub">en catalogue</div>
    </div>
    <div class="kpi">
      <div class="kpi-bar" style="background:#0ea5e9;"></div>
      <div class="kpi-icon" style="background:#e0f2fe; color:#0ea5e9;">≡</div> <div class="kpi-label">Catégories</div>
      <div class="kpi-val"><?= $totalCategories ?></div>
      <div class="kpi-sub">actives</div>
    </div>
    <div class="kpi">
      <div class="kpi-bar" style="background:#22c55e;"></div>
      <div class="kpi-icon" style="background:#dcfce7; color:#166534;">$</div> <div class="kpi-label">Valeur stock</div>
      <div class="kpi-val"><?= number_format($valeurStock, 0, ',', ' ') ?> €</div>
      <div class="kpi-sub">valeur totale</div>
    </div>
    <div class="kpi">
      <div class="kpi-bar" style="background:#e94560;"></div>
      <div class="kpi-icon" style="background:#fee2e2; color:#991b1b;">▲</div> <div class="kpi-label">Alertes</div>
      <div class="kpi-val" style="color:#e94560;"><?= $stockFaible+$ruptures ?></div>
      <div class="kpi-sub">à traiter</div>
    </div>
  </div>

  <div class="main-grid">
    <div class="card">
      <div class="card-header"><div class="card-title" style="color:#e94560;">Stocks critiques</div></div>
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
                  <img class="prod-img-sm" src="<?= $img ?: 'https://ui-avatars.com/api/?name='.urlencode($p['nom']) ?>" 
                       data-search="<?= $search ?>" alt="">
                </div>
                <div>
                  <div style="font-weight:700; font-size:13px;"><?= htmlspecialchars($p['nom']) ?></div>
                  <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($p['categorie'] ?? 'Snacks') ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:13px;"><?= $p['quantite'] ?> / <?= $p['seuil_alerte'] ?></td>
            <td>
                <div class="stock-bar">
                    <div class="stock-progress" style="width:<?= $percent ?>%; background:<?= $p['quantite']==0?'#e94560':($p['quantite']<=$p['seuil_alerte']?'#f59e0b':'#16a34a') ?>;"></div>
                </div>
            </td>
            <td><span class="badge <?= $p['quantite']==0?'badge-crit':'badge-warn' ?>"><?= $p['quantite']==0?'RUPTURE':'FAIBLE' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div>
      <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-title">Raccourcis</div>
        <a href="pages/mouvements.php" class="short-item"><div class="short-icon" style="background:#fee2e2;color:#e94560;">↕</div> Enregistrer un mouvement</a>
        <a href="pages/produits.php" class="short-item"><div class="short-icon" style="background:#ede9fe;color:#6366f1;">+</div> Ajouter un produit</a>
        <a href="pages/categories.php" class="short-item"><div class="short-icon" style="background:#e0f2fe;color:#0ea5e9;">≡</div> Gérer les catégories</a>
      </div>

      <div class="card">
        <div class="card-title">Activité récente</div>
        <?php foreach ($derniersMouvements as $m): ?>
          <div style="display:flex; justify-content:space-between; font-size:12px; margin-bottom:8px; padding:8px; background:#f8fafc; border-radius:8px;">
            <span style="font-weight:700;"><?= htmlspecialchars($m['produit_nom']) ?></span>
            <span style="color:<?= $m['type_mouvement']=='entree'?'#16a34a':'#e94560' ?>; font-weight:900;">
              <?= $m['type_mouvement']=='entree'?'+':'-' ?><?= $m['quantite'] ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script src="js/off-photos.js"></script>
</body>
</html>