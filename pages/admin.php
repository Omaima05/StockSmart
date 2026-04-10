<?php
require_once '../config.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ../dashboard.php');
    exit;
}

$success = null;

// ── SUPPRESSION USER ─────────────────────────────────────────
if (isset($_GET['supp_user'])) {
    $id = (int)$_GET['supp_user'];
    if ($_SESSION['user_id'] != $id) {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?")->execute([$id]);
        $success = "✅ Utilisateur supprimé avec succès.";
    }
}

// ── STATS GLOBALES ───────────────────────────────────────────
$nb_users      = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$nb_produits   = $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
$nb_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$nb_mouvements = $pdo->query("SELECT COUNT(*) FROM mouvements")->fetchColumn();
$nb_enseignes  = $pdo->query("SELECT COUNT(*) FROM enseignes")->fetchColumn();

$utilisateurs  = $pdo->query("SELECT u.*, e.nom AS enseigne_nom FROM utilisateurs u LEFT JOIN enseignes e ON u.enseigne_id = e.id ORDER BY u.role ASC, u.nom ASC")->fetchAll();
$enseignes     = $pdo->query("SELECT * FROM enseignes ORDER BY nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administration — StockSmart Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root { --red:#e94560; --mid:#64748b; --border:#e2e8f0; --light:#f8fafc; }
    body { font-family:'Inter',system-ui,sans-serif; background:var(--light); color:#0d1117; }
    .container { max-width:1200px; margin:2rem auto; padding:0 2rem; }
    .page-title { font-size:20px; font-weight:800; margin-bottom:1.5rem; }
    .msg-success { background:#dcfce7; color:#166534; padding:14px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:1.5rem; border:1px solid #bbf7d0; }

    /* KPI */
    .kpi-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:1rem; margin-bottom:2rem; }
    .kpi { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.25rem; text-align:center; }
    .kpi-val { font-size:28px; font-weight:900; color:#0d1117; }
    .kpi-lbl { font-size:12px; color:var(--mid); margin-top:5px; }

    /* Cards */
    .card { background:#fff; border-radius:14px; border:1px solid var(--border); overflow:hidden; margin-bottom:1.5rem; }
    .card-header { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border); font-size:15px; font-weight:800; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:var(--light); font-size:11px; font-weight:600; color:var(--mid); text-transform:uppercase; letter-spacing:.4px; padding:10px 16px; text-align:left; border-bottom:1px solid var(--border); }
    tbody tr { border-bottom:1px solid #f5f7fa; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafbff; }
    tbody td { padding:12px 16px; font-size:13px; }

    /* Badges rôles */
    .role-badge { padding:4px 10px; border-radius:10px; font-size:11px; font-weight:800; text-transform:uppercase; }
    .role-admin  { background:#fee2e2; color:#991b1b; }
    .role-gerant { background:#fef3c7; color:#92400e; }
    .role-employe { background:#dcfce7; color:#166534; }

    .btn-delete { background:#fee2e2; color:#991b1b; font-size:11px; font-weight:700; padding:5px 12px; border-radius:6px; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
    .btn-delete:hover { background:#fecdd3; }

    @media(max-width:900px) { .kpi-grid { grid-template-columns:repeat(2,1fr); } }
  </style>
</head>
<body>
<?php require_once '../_nav.php'; ?>

<div class="container">
  <div class="page-title">Administration</div>

  <?php if ($success): ?><div class="msg-success"><?= $success ?></div><?php endif; ?>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi"><div class="kpi-val"><?= $nb_enseignes ?></div><div class="kpi-lbl">Enseignes</div></div>
    <div class="kpi"><div class="kpi-val"><?= $nb_users ?></div><div class="kpi-lbl">Utilisateurs</div></div>
    <div class="kpi"><div class="kpi-val"><?= $nb_produits ?></div><div class="kpi-lbl">Produits</div></div>
    <div class="kpi"><div class="kpi-val"><?= $nb_categories ?></div><div class="kpi-lbl">Catégories</div></div>
    <div class="kpi"><div class="kpi-val"><?= $nb_mouvements ?></div><div class="kpi-lbl">Mouvements</div></div>
  </div>

  <!-- Enseignes -->
  <div class="card">
    <div class="card-header">Enseignes actives (<?= count($enseignes) ?>)</div>
    <table>
      <thead><tr><th>#</th><th>Nom de l'enseigne</th><th>Couleur</th><th>Statut</th></tr></thead>
      <tbody>
        <?php foreach ($enseignes as $e): ?>
        <tr>
          <td style="color:var(--mid);font-size:12px;">#<?= $e['id'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <?php if (!empty($e['logo_url'])): ?>
                <img src="<?= h($e['logo_url']) ?>" alt="" style="width:28px;height:28px;border-radius:6px;object-fit:cover;">
              <?php endif; ?>
              <strong><?= h($e['nom']) ?></strong>
            </div>
          </td>
          <td><div style="width:20px;height:20px;border-radius:5px;background:<?= h($e['couleur'] ?? '#e94560') ?>;display:inline-block;"></div> <?= h($e['couleur'] ?? '') ?></td>
          <td><span style="background:<?= $e['actif'] ? '#dcfce7' : '#fee2e2' ?>;color:<?= $e['actif'] ? '#166534' : '#991b1b' ?>;padding:3px 9px;border-radius:8px;font-size:11px;font-weight:700;"><?= $e['actif'] ? 'Actif' : 'Inactif' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Utilisateurs -->
  <div class="card">
    <div class="card-header">Gestion des utilisateurs (<?= count($utilisateurs) ?>)</div>
    <table>
      <thead><tr><th>#</th><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Enseigne</th><th>Inscription</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($utilisateurs as $user): ?>
        <tr>
          <td style="color:var(--mid);font-size:12px;">#<?= $user['id'] ?></td>
          <td style="font-weight:700;"><?= h($user['prenom'] . ' ' . $user['nom']) ?></td>
          <td style="font-size:12px;color:var(--mid);"><?= h($user['email']) ?></td>
          <td>
            <span class="role-badge role-<?= $user['role'] ?>"><?= strtoupper($user['role']) ?></span>
          </td>
          <td style="font-size:12px;"><?= h($user['enseigne_nom'] ?? '—') ?></td>
          <td style="font-size:12px;color:var(--mid);">
            <?php
              $date = $user['created_at'] ?? $user['date_creation'] ?? null;
              echo $date ? date('d/m/Y', strtotime($date)) : '—';
            ?>
          </td>
          <td>
            <?php if ($_SESSION['user_id'] != $user['id']): ?>
              <a href="?supp_user=<?= $user['id'] ?>" class="btn-delete"
                 onclick="return confirm('Supprimer <?= h($user['prenom']) ?> ?')">
                Supprimer
              </a>
            <?php else: ?>
              <em style="color:var(--mid);font-size:12px;">(Vous)</em>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<footer>© <?= date("Y") ?> StockSmart Pro</footer>
<script src="../js/script.js"></script>
</body>
</html>
