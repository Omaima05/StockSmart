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
  .quick-actions { display:flex; gap:.75rem; margin-bottom:1.5rem; flex-wrap:wrap; }
  .btn-dash      { padding:10px 18px; border-radius:9px; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer; font-family:inherit; transition:all .15s; }
  .btn-dash-red  { background:#e94560; color:#fff; }
  .btn-dash-red:hover { box-shadow:0 4px 14px rgba(233,69,96,.3); transform:translateY(-1px); }
  .btn-dash-outline { background:#fff; color:#0d1117; border:1px solid var(--border); }
  .kpi-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1.5rem; }
  .kpi      { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.25rem; position:relative; overflow:hidden; }
  .kpi-bar  { position:absolute; top:0; left:0; right:0; height:3px; border-radius:14px 14px 0 0; }
  .kpi-label{ font-size:10px; font-weight:600; color:var(--mid); text-transform:uppercase; letter-spacing:.5px; margin-bottom:.5rem; }
  .kpi-val  { font-size:26px; font-weight:900; color:#0d1117; line-height:1; }
  .kpi-sub  { font-size:11px; color:var(--mid); margin-top:5px; }
  .kpi-icon { position:absolute; top:1rem; right:1rem; width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
  .kpi-icon svg { width:16px; height:16px; }
  /* 2 colonnes : gauche large, droite fixe */
  .main-grid { display:grid; grid-template-columns:1fr 300px; gap:1.5rem; }
  .card        { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.5rem; }
  .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; }
  .card-title  { font-size:14px; font-weight:700; color:#0d1117; }
  .card-link   { font-size:12px; color:#e94560; text-decoration:none; font-weight:600; }
  .prod-cell { display:flex; align-items:center; gap:10px; }
  .img-wrap  { width:38px; height:38px; border-radius:8px; overflow:hidden; background:#f1f5f9; border:1px solid var(--border); flex-shrink:0; position:relative; }
  .img-wrap::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent); background-size:200% 100%; animation:shimmer 1.2s infinite; opacity:1; transition:opacity .3s; }
  .img-wrap.loaded::after { opacity:0; }
  @keyframes shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }
  .prod-img-sm { width:38px; height:38px; object-fit:contain; padding:3px; display:block; }
  .prod-name { font-size:13px; font-weight:700; color:#0d1117; }
  .prod-cat  { font-size:11px; color:var(--mid); margin-top:1px; }
  .stock-bw  { width:56px; height:4px; background:#f0f0f0; border-radius:2px; margin-top:3px; }
  .stock-bf  { height:4px; border-radius:2px; }
  .act-list { display:flex; flex-direction:column; gap:7px; }
  .act-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; background:var(--light); }
  .act-dir  { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .act-dir svg { width:13px; height:13px; }
  .act-prod { font-size:12px; font-weight:700; color:#0d1117; }
  .act-meta { font-size:10px; color:var(--mid); margin-top:1px; }
  .act-qty  { font-size:13px; font-weight:900; padding:3px 8px; border-radius:6px; margin-left:auto; }
  .short-list { display:flex; flex-direction:column; gap:7px; }
  .short-item { display:flex; align-items:center; gap:10px; padding:11px 14px; background:var(--light); border-radius:10px; text-decoration:none; color:#0d1117; font-size:13px; font-weight:600; border:1px solid var(--border); transition:border-color .15s; }
  .short-item:hover { border-color:#e94560; color:#e94560; }
  .short-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .short-icon svg { width:14px; height:14px; }
  @media(max-width:900px) { .kpi-grid{grid-template-columns:1fr 1fr;} .main-grid{grid-template-columns:1fr;} .container{padding:1.5rem;} }
</style>

<div class="container">

  <?php if ($showCode): ?>
  <div class="invite-banner" id="inviteBanner">
    <div>
      <div class="invite-title">Votre enseigne est creee !</div>
      <div class="invite-sub">Partagez ce code a vos employes pour qu'ils rejoignent votre espace</div>
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

  <div class="kpi-grid">
    <div class="kpi">
      <div class="kpi-bar" style="background:#6366f1;"></div>
      <div class="kpi-icon" style="background:#ede9fe;"><svg viewBox="0 0 24 24" fill="#6366f1"><path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg></div>
      <div class="kpi-label">Total produits</div>
      <div class="kpi-val"><?= $totalProduits ?></div>
      <div class="kpi-sub">en catalogue</div>
    </div>
    <div class="kpi">
      <div class="kpi-bar" style="background:#0ea5e9;"></div>
      <div class="kpi-icon" style="background:#e0f2fe;"><svg viewBox="0 0 24 24" fill="#0ea5e9"><path d="M4 6h16M4 12h16M4 18h7"/></svg></div>
      <div class="kpi-label">Categories</div>
      <div class="kpi-val"><?= $totalCategories ?></div>
      <div class="kpi-sub">actives</div>
    </div>
    <div class="kpi">
      <div class="kpi-bar" style="background:#22c55e;"></div>
      <div class="kpi-icon" style="background:#dcfce7;"><svg viewBox="0 0 24 24" fill="#22c55e"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1"/></svg></div>
      <div class="kpi-label">Valeur stock</div>
      <div class="kpi-val"><?= number_format($valeurStock, 0, ',', ' ') ?> €</div>
      <div class="kpi-sub">valeur totale</div>
    </div>
    <div class="kpi">
      <div class="kpi-bar" style="background:<?= ($stockFaible+$ruptures>0)?'#e94560':'#22c55e' ?>;"></div>
      <div class="kpi-icon" style="background:<?= ($stockFaible+$ruptures>0)?'#fee2e2':'#dcfce7' ?>;"><svg viewBox="0 0 24 24" fill="<?= ($stockFaible+$ruptures>0)?'#e94560':'#22c55e' ?>"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
      <div class="kpi-label">Alertes</div>
      <div class="kpi-val" style="color:<?= ($stockFaible+$ruptures>0)?'#e94560':'#22c55e' ?>;"><?= $stockFaible+$ruptures ?></div>
      <div class="kpi-sub"><?= ($stockFaible+$ruptures>0)?'a traiter':'stock optimal' ?></div>
    </div>
  </div>

  <div class="main-grid">

    <!-- Colonne gauche : stocks critiques + activite -->
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
                $search = trim(($p['marque']??'').' '.$p['nom']);
                $hasLocal = !empty($p['image']) && file_exists($p['image']);
                $avatar = 'https://ui-avatars.com/api/?name='.urlencode(mb_substr($p['nom'],0,2)).'&background=f1f5f9&color=64748b&size=60&bold=true';
                $src = $hasLocal ? $p['image'] : $avatar;
                $wid = 'dw_'.md5($p['nom']);
                $pct = $p['seuil_alerte']>0 ? min(100,round(($p['quantite']/$p['seuil_alerte'])*100)) : 0;
                $color = $p['quantite']==0 ? '#e94560' : '#f59e0b';
              ?>
              <tr>
                <td>
                  <div class="prod-cell">
                    <div class="img-wrap <?= $hasLocal?'loaded':'' ?>" id="<?= $wid ?>">
                      <img class="prod-img-sm" src="<?= $src ?>" alt="<?= h($p['nom']) ?>"
                           <?= !$hasLocal?'data-search="'.h($search).'" data-wrapid="'.$wid.'"':'' ?>>
                    </div>
                    <div>
                      <div class="prod-name"><?= h($p['nom']) ?></div>
                      <div class="prod-cat"><?= h($p['categorie']??'') ?></div>
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
            <?php foreach ($derniersMouvements as $m): $e=$m['type_mouvement']==='entree'; ?>
            <div class="act-item">
              <div class="act-dir" style="background:<?= $e?'#dcfce7':'#fee2e2' ?>;">
                <svg viewBox="0 0 24 24" fill="<?= $e?'#16a34a':'#991b1b' ?>">
                  <?= $e?'<path d="M7 16l-4-4m0 0l4-4m-4 4h18"/>':'<path d="M17 8l4 4m0 0l-4 4m4-4H3"/>' ?>
                </svg>
              </div>
              <div>
                <div class="act-prod"><?= h($m['produit_nom']) ?></div>
                <div class="act-meta"><?= date('d/m H:i', strtotime($m['date_mouvement'])) ?></div>
              </div>
              <span class="act-qty" style="background:<?= $e?'#dcfce7':'#fee2e2' ?>;color:<?= $e?'#16a34a':'#991b1b' ?>;">
                <?= $e?'+':'-' ?><?= $m['quantite'] ?>
              </span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Colonne droite : uniquement les raccourcis -->
    <div>
      <div class="card">
        <div class="card-header"><div class="card-title">Raccourcis</div></div>
        <div class="short-list">
          <a href="pages/mouvements.php" class="short-item">
            <div class="short-icon" style="background:#fee2e2;"><svg viewBox="0 0 24 24" fill="#e94560"><path d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg></div>
            Enregistrer un mouvement
          </a>
          <a href="pages/produits.php" class="short-item">
            <div class="short-icon" style="background:#ede9fe;"><svg viewBox="0 0 24 24" fill="#6366f1"><path d="M12 4v16m8-8H4"/></svg></div>
            Ajouter un produit
          </a>
          <a href="pages/categories.php" class="short-item">
            <div class="short-icon" style="background:#e0f2fe;"><svg viewBox="0 0 24 24" fill="#0ea5e9"><path d="M4 6h16M4 12h16M4 18h7"/></svg></div>
            Gerer les categories
          </a>
          <?php if (isAdmin()): ?>
          <a href="pages/admin.php" class="short-item" style="border-color:#fecdd3;">
            <div class="short-icon" style="background:#fee2e2;"><svg viewBox="0 0 24 24" fill="#e94560"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg></div>
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
  navigator.clipboard.writeText(el.textContent.trim()).then(function() {
    var orig = el.textContent;
    el.textContent = 'Copie !';
    setTimeout(function(){ el.textContent = orig; }, 1500);
  });
}
</script>
</body>
</html>