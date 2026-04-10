<?php
require_once 'config.php';
requireLogin();

$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$user_role   = $_SESSION['user_role']   ?? 'employe';

$totalProduits   = (int)$pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
$totalCategories = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$stockFaible     = (int)$pdo->query("SELECT COUNT(*) FROM produits WHERE quantite > 0 AND quantite <= seuil_alerte")->fetchColumn();
$ruptures        = (int)$pdo->query("SELECT COUNT(*) FROM produits WHERE quantite = 0")->fetchColumn();
$valeurStock     = (float)($pdo->query("SELECT SUM(prix * quantite) FROM produits")->fetchColumn() ?: 0);

$derniersMouvements = $pdo->query("
    SELECT m.date_mouvement, m.type_mouvement, m.quantite, p.nom AS produit_nom
    FROM mouvements m JOIN produits p ON m.produit_id = p.id
    ORDER BY m.date_mouvement DESC LIMIT 8
")->fetchAll();

$produitsAlerte = $pdo->query("
    SELECT p.nom, p.quantite, p.seuil_alerte, p.marque, p.image, c.nom AS categorie
    FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id
    WHERE p.quantite <= p.seuil_alerte
    ORDER BY p.quantite ASC LIMIT 6
")->fetchAll();

$showCode  = isset($_SESSION['code_invitation']) && $_SESSION['user_role'] === 'admin';
$codeInvit = $_SESSION['code_invitation'] ?? '';
if ($showCode) unset($_SESSION['code_invitation']);
?>
<?php require_once '_nav.php'; ?>

<style>
  .container { padding:2rem 2.5rem; max-width:1400px; margin:0 auto; }

  .invite-banner { background:#0d1117; border:1px solid rgba(233,69,96,.25); border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
  .invite-title  { font-size:14px; font-weight:700; color:#fff; margin-bottom:3px; }
  .invite-sub    { font-size:12px; color:rgba(255,255,255,.4); }
  .invite-code   { font-size:20px; font-weight:900; color:#e94560; letter-spacing:2px; background:rgba(233,69,96,.1); padding:8px 18px; border-radius:10px; border:1px solid rgba(233,69,96,.2); cursor:pointer; user-select:all; }
  .invite-close  { width:28px; height:28px; border-radius:6px; background:rgba(255,255,255,.06); border:none; color:rgba(255,255,255,.4); cursor:pointer; font-size:14px; flex-shrink:0; }

  .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.75rem; }
  .page-title  { font-size:20px; font-weight:800; color:#0d1117; }
  .page-sub    { font-size:13px; color:var(--mid); margin-top:3px; }
  .user-pill   { background:#fff; padding:7px 16px; border-radius:50px; border:1px solid var(--border); font-weight:700; font-size:13px; display:flex; align-items:center; gap:8px; }
  .role-badge  { background:#fee2e2; color:#e94560; font-size:10px; padding:2px 8px; border-radius:5px; font-weight:800; text-transform:uppercase; }

  .quick-actions  { display:flex; gap:.75rem; margin-bottom:1.5rem; flex-wrap:wrap; }
  .btn-dash       { padding:10px 18px; border-radius:9px; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer; font-family:inherit; transition:all .15s; }
  .btn-dash-red   { background:#e94560; color:#fff; }
  .btn-dash-red:hover { box-shadow:0 4px 14px rgba(233,69,96,.3); transform:translateY(-1px); }
  .btn-dash-outline { background:#fff; color:#0d1117; border:1px solid var(--border); }

  .kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1.5rem; }
  .kpi      { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.25rem; position:relative; overflow:hidden; }
  .kpi-bar  { position:absolute; top:0; left:0; right:0; height:3px; border-radius:14px 14px 0 0; }
  .kpi-label{ font-size:10px; font-weight:600; color:var(--mid); text-transform:uppercase; letter-spacing:.5px; margin-bottom:.5rem; }
  .kpi-val  { font-size:26px; font-weight:900; color:#0d1117; line-height:1; }
  .kpi-sub  { font-size:11px; color:var(--mid); margin-top:5px; }
  .kpi-icon { position:absolute; top:1rem; right:1rem; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; }

  .main-grid { display:grid; grid-template-columns:1fr 300px; gap:1.5rem; }

  .card        { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.5rem; }
  .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
  .card-title  { font-size:14px; font-weight:700; color:#0d1117; }
  .card-link   { font-size:12px; color:#e94560; text-decoration:none; font-weight:600; }

  .prod-cell  { display:flex; align-items:center; gap:10px; }
  .img-wrap   { width:38px; height:38px; border-radius:8px; overflow:hidden; background:#f1f5f9; border:1px solid var(--border); flex-shrink:0; position:relative; }
  .img-wrap::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent); background-size:200% 100%; animation:shimmer 1.2s infinite; opacity:1; transition:opacity .3s; }
  .img-wrap.loaded::after { opacity:0; }
  @keyframes shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }
  .prod-img-sm { width:38px; height:38px; object-fit:contain; padding:3px; display:block; }
  .prod-name  { font-size:13px; font-weight:700; color:#0d1117; }
  .prod-cat   { font-size:11px; color:var(--mid); margin-top:1px; }
  .stock-bw   { width:56px; height:4px; background:#f0f0f0; border-radius:2px; margin-top:3px; }
  .stock-bf   { height:4px; border-radius:2px; }

  .act-list { display:flex; flex-direction:column; gap:7px; }
  .act-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; background:var(--light); }
  .act-dir  { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:14px; font-weight:800; }
  .act-prod { font-size:12px; font-weight:700; color:#0d1117; }
  .act-meta { font-size:10px; color:var(--mid); margin-top:1px; }
  .act-qty  { font-size:13px; font-weight:900; padding:3px 8px; border-radius:6px; margin-left:auto; }

  /* Raccourcis — icones en texte, pas en SVG */
  .short-list { display:flex; flex-direction:column; gap:7px; }
  .short-item { display:flex; align-items:center; gap:12px; padding:12px 14px; background:var(--light); border-radius:10px; text-decoration:none; color:#0d1117; font-size:13px; font-weight:600; border:1px solid var(--border); transition:border-color .15s; }
  .short-item:hover { border-color:#e94560; color:#e94560; }
  .short-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:17px; line-height:1; }

  .badge     { display:inline-flex; align-items:center; padding:3px 9px; border-radius:10px; font-size:11px; font-weight:700; }
  .badge-ok  { background:#dcfce7; color:#166534; }
  .badge-warn{ background:#fef3c7; color:#92400e; }
  .badge-crit{ background:#fee2e2; color:#991b1b; }

  @media(max-width:900px) {
    .kpi-grid { grid-template-columns:1fr 1fr; }
    .main-grid { grid-template-columns:1fr; }
    .container { padding:1.5rem; }
  }
</style>

<div class="container">

  <?php if ($showCode): ?>
  <div class="invite-banner" id="inviteBanner">
    <div>
      <div class="invite-title">Votre enseigne est creee !</div>
      <div class="invite-sub">Partagez ce code a vos employes — ils le saisissent lors de leur inscription</div>
    </div>
    <div class="invite-code" onclick="copyCode(this)"><?= h($codeInvit) ?></div>
    <button class="invite-close" onclick="document.getElementById('inviteBanner').remove()">x</button>
  </div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <div class="page-title">Tableau de bord</div>
      <div class="page-sub"><?= date('d F Y') ?> — Bonjour <?= h($user_prenom) ?></div>
    </div>
    <div class="user-pill">
      <?= h($user_prenom) ?>
      <span class="role-badge"><?= strtoupper($user_role) ?></span>
    </div>
  </div>

  <div class="quick-actions">
    <a href="pages/mouvements.php" class="btn-dash btn-dash-red">Enregistrer un mouvement</a>
    <?php if (canEdit()): ?>
      <a href="pages/produits.php" class="btn-dash btn-dash-outline">Gerer les produits</a>
    <?php endif; ?>
    <?php if (isAdmin()): ?>
      <a href="pages/admin.php" class="btn-dash btn-dash-outline">Administration</a>
    <?php endif; ?>
  </div>

  <!-- KPIs -->
<div class="kpi-grid">
  <div class="kpi">
    <div class="kpi-bar" style="background:#6366f1;"></div>
    <div class="kpi-icon" style="background:#ede9fe;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
      </svg>
    </div>
    <div class="kpi-label">Total produits</div>
    <div class="kpi-val"><?= $totalProduits ?></div>
    <div class="kpi-sub">en catalogue</div>
  </div>

  <div class="kpi">
    <div class="kpi-bar" style="background:#0ea5e9;"></div>
    <div class="kpi-icon" style="background:#e0f2fe;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#0ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 6h16M4 12h16M4 18h10"/>
      </svg>
    </div>
    <div class="kpi-label">Categories</div>
    <div class="kpi-val"><?= $totalCategories ?></div>
    <div class="kpi-sub">actives</div>
  </div>

  <div class="kpi">
    <div class="kpi-bar" style="background:#22c55e;"></div>
    <div class="kpi-icon" style="background:#dcfce7;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
      </svg>
    </div>
    <div class="kpi-label">Valeur stock</div>
    <div class="kpi-val"><?= number_format($valeurStock, 0, ',', ' ') ?> €</div>
    <div class="kpi-sub">valeur totale</div>
  </div>

  <div class="kpi">
    <div class="kpi-bar" style="background:<?= ($stockFaible+$ruptures>0)?'#e94560':'#22c55e' ?>;"></div>
    <div class="kpi-icon" style="background:<?= ($stockFaible+$ruptures>0)?'#fee2e2':'#dcfce7' ?>;">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="<?= ($stockFaible+$ruptures>0)?'#e94560':'#22c55e' ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </div>
    <div class="kpi-label">Alertes</div>
    <div class="kpi-val" style="color:<?= ($stockFaible+$ruptures>0)?'#e94560':'#22c55e' ?>;"><?= $stockFaible+$ruptures ?></div>
    <div class="kpi-sub"><?= ($stockFaible+$ruptures>0)?'a traiter':'stock optimal' ?></div>
  </div>
</div>

  <div class="main-grid">

    <!-- Colonne gauche -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <div class="card">
        <div class="card-header">
          <div class="card-title">Stocks critiques</div>
          <a href="pages/produits.php" class="card-link">Voir tous</a>
        </div>
        <?php if (empty($produitsAlerte)): ?>
          <p style="text-align:center;padding:2rem;color:#22c55e;font-weight:600;">Tous les stocks sont au niveau optimal.</p>
        <?php else: ?>
          <table>
            <thead><tr><th>Article</th><th>Stock</th><th>Niveau</th><th>Etat</th></tr></thead>
            <tbody>
              <?php foreach ($produitsAlerte as $p):
                $search   = trim(($p['marque'] ?? '') . ' ' . $p['nom']);
                $localImg = !empty($p['image']) ? $p['image'] : '';
                $hasLocal = $localImg && file_exists($localImg);
                $avatar   = 'https://ui-avatars.com/api/?name=' . urlencode(mb_substr($p['nom'],0,2)) . '&background=f1f5f9&color=64748b&size=60&bold=true';
                $src      = $hasLocal ? $localImg : $avatar;
                $wid      = 'dw_' . md5($p['nom']);
                $pct      = $p['seuil_alerte'] > 0 ? min(100, round(($p['quantite']/$p['seuil_alerte'])*100)) : 0;
                $color    = $p['quantite'] == 0 ? '#e94560' : '#f59e0b';
              ?>
              <tr>
                <td>
                  <div class="prod-cell">
                    <div class="img-wrap <?= $hasLocal?'loaded':'' ?>" id="<?= $wid ?>">
                      <img class="prod-img-sm" src="<?= $src ?>" alt="<?= h($p['nom']) ?>"
                           <?= !$hasLocal ? 'data-search="'.h($search).'" data-wrapid="'.$wid.'"' : '' ?>>
                    </div>
                    <div>
                      <div class="prod-name"><?= h($p['nom']) ?></div>
                      <div class="prod-cat"><?= h($p['categorie'] ?? '') ?></div>
                    </div>
                  </div>
                </td>
                <td style="font-size:13px;white-space:nowrap;"><?= $p['quantite'] ?> / <?= $p['seuil_alerte'] ?></td>
                <td><div class="stock-bw"><div class="stock-bf" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div></td>
                <td><span class="badge <?= $p['quantite']==0?'badge-crit':'badge-warn' ?>"><?= $p['quantite']==0?'RUPTURE':'FAIBLE' ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">Activite recente</div>
          <a href="pages/mouvements.php" class="card-link">Voir tout</a>
        </div>
        <div class="act-list">
          <?php if (empty($derniersMouvements)): ?>
            <p style="text-align:center;padding:1rem;color:var(--mid);">Aucune activite enregistree.</p>
          <?php else: ?>
            <?php foreach ($derniersMouvements as $m):
              $entree = $m['type_mouvement'] === 'entree';
            ?>
            <div class="act-item">
              <div class="act-dir" style="background:<?= $entree?'#dcfce7':'#fee2e2' ?>;color:<?= $entree?'#16a34a':'#991b1b' ?>;">
                <?= $entree ? '↓' : '↑' ?>
              </div>
              <div>
                <div class="act-prod"><?= h($m['produit_nom']) ?></div>
                <div class="act-meta"><?= date('d/m H:i', strtotime($m['date_mouvement'])) ?></div>
              </div>
              <span class="act-qty" style="background:<?= $entree?'#dcfce7':'#fee2e2' ?>;color:<?= $entree?'#16a34a':'#991b1b' ?>;">
                <?= $entree?'+':'-' ?><?= $m['quantite'] ?>
              </span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Colonne droite : raccourcis avec icones texte -->
    <div>
      <div class="card">
        <div class="card-header"><div class="card-title">Raccourcis</div></div>
        <div class="short-list">

          <a href="pages/mouvements.php" class="short-item">
            <div class="short-icon" style="background:#fee2e2;color:#e94560;">↕</div>
            Enregistrer un mouvement
          </a>

          <a href="pages/produits.php" class="short-item">
            <div class="short-icon" style="background:#ede9fe;color:#6366f1;">+</div>
            Ajouter un produit
          </a>

          <a href="pages/categories.php" class="short-item">
            <div class="short-icon" style="background:#e0f2fe;color:#0ea5e9;">≡</div>
            Gerer les categories
          </a>

          <?php if (isAdmin()): ?>
          <a href="pages/admin.php" class="short-item" style="border-color:#fecdd3;">
            <div class="short-icon" style="background:#fee2e2;color:#e94560;">⚙</div>
            Administration
          </a>
          <?php endif; ?>

        </div>
      </div>
    </div>

  </div>
</div>

<footer>© <?= date('Y') ?> StockSmart Pro</footer>

<script src="js/script.js"></script>
<script>
function copyCode(el) {
  navigator.clipboard.writeText(el.textContent.trim()).then(function () {
    var orig = el.textContent;
    el.textContent = 'Copie !';
    setTimeout(function () { el.textContent = orig; }, 1500);
  });
}
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('img[data-search]').forEach(function(img, i) {
    setTimeout(function() {
      var search = img.getAttribute('data-search');
      var wid    = img.getAttribute('data-wrapid');
      var wrap   = wid ? document.getElementById(wid) : null;
      var cached = localStorage.getItem('off_' + search);
      if (cached) {
        img.src = cached;
        img.style.objectFit = 'contain';
        if (wrap) wrap.classList.add('loaded');
      } else {
        fetch('https://world.openfoodfacts.org/cgi/search.pl?search_terms=' + encodeURIComponent(search) + '&search_simple=1&action=process&json=1&page_size=3')
          .then(function(r) { return r.json(); })
          .then(function(data) {
            for (var j = 0; j < (data.products||[]).length; j++) {
              var url = data.products[j].image_front_url || data.products[j].image_url;
              if (url && url.startsWith('http')) {
                img.src = url;
                img.style.objectFit = 'contain';
                try { localStorage.setItem('off_' + search, url); } catch(e) {}
                break;
              }
            }
          })
          .catch(function(){})
          .finally(function() { if (wrap) wrap.classList.add('loaded'); });
      }
    }, i * 150);
  });
});
</script>
</body>
</html>