<?php
require_once '../config.php';
requireLogin();

$success = '';
$error   = '';

// ── AJOUT ────────────────────────────────────────────────────
if (isset($_POST['ajouter'])) {
    $nom = trim($_POST['nom'] ?? '');
    if ($nom === '') {
        $error = "Le nom ne peut pas être vide.";
    } else {
        try {
            $pdo->prepare("INSERT INTO categories (nom) VALUES (?)")->execute([$nom]);
            $success = "Catégorie <strong>" . h($nom) . "</strong> ajoutée !";
        } catch (PDOException) {
            $error = "Cette catégorie existe déjà.";
        }
    }
}

// ── MODIFICATION ─────────────────────────────────────────────
if (isset($_POST['modifier'])) {
    $id  = (int)$_POST['id'];
    $nom = trim($_POST['nom'] ?? '');
    if ($nom === '') {
        $error = "Le nom ne peut pas être vide.";
    } else {
        try {
            $pdo->prepare("UPDATE categories SET nom = ? WHERE id = ?")->execute([$nom, $id]);
            $success = "Catégorie modifiée !";
        } catch (PDOException) {
            $error = "Ce nom est déjà utilisé.";
        }
    }
}

// ── SUPPRESSION ──────────────────────────────────────────────
if (isset($_GET['supprimer']) && canDelete()) {
    $id = (int)$_GET['supprimer'];
    $nb = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ?");
    $nb->execute([$id]);
    if ($nb->fetchColumn() > 0) {
        $error = "Impossible : cette catégorie contient des produits.";
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $success = "Catégorie supprimée.";
    }
}

$edit_cat = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_cat = $stmt->fetch();
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) AS nb_produits FROM categories c ORDER BY c.nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catégories — StockSmart Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root { --red:#e94560; --mid:#64748b; --border:#e2e8f0; --light:#f8fafc; }
    body { font-family:'Inter',system-ui,sans-serif; background:var(--light); color:#0d1117; }
    .container { max-width:900px; margin:2rem auto; padding:0 2rem; }
    .page-title { font-size:20px; font-weight:800; margin-bottom:1.5rem; }
    .msg-success { background:#dcfce7; color:#166534; padding:14px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:1.5rem; border:1px solid #bbf7d0; }
    .msg-error   { background:#fee2e2; color:#991b1b; padding:14px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:1.5rem; border:1px solid #fecdd3; }

    .grid { display:grid; grid-template-columns:1fr 340px; gap:1.5rem; }

    /* Table */
    .card { background:#fff; border-radius:14px; border:1px solid var(--border); overflow:hidden; }
    .card-header { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .card-title  { font-size:15px; font-weight:800; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:var(--light); font-size:11px; font-weight:600; color:var(--mid); text-transform:uppercase; letter-spacing:.4px; padding:10px 16px; text-align:left; border-bottom:1px solid var(--border); }
    tbody tr { border-bottom:1px solid #f5f7fa; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafbff; }
    tbody td { padding:12px 16px; font-size:13px; }
    .badge-count { background:var(--light); color:var(--mid); font-size:11px; font-weight:700; padding:3px 8px; border-radius:8px; }

    /* Form */
    .form-card { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.5rem; }
    .form-card h3 { font-size:15px; font-weight:800; margin-bottom:1.25rem; }
    label { display:block; font-size:11px; font-weight:700; color:var(--mid); margin-bottom:7px; text-transform:uppercase; letter-spacing:.4px; }
    input[type=text] { width:100%; padding:11px 13px; border-radius:9px; border:1px solid var(--border); font-family:inherit; font-size:14px; outline:none; transition:border-color .15s; }
    input[type=text]:focus { border-color:var(--red); }
    .btn { padding:11px 20px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; border:none; font-family:inherit; text-decoration:none; display:inline-flex; align-items:center; gap:6px; transition:all .15s; margin-top:12px; }
    .btn-red  { background:var(--red); color:#fff; }
    .btn-red:hover { box-shadow:0 4px 14px rgba(233,69,96,.35); }
    .btn-gray { background:var(--light); color:var(--mid); border:1px solid var(--border); }
    .btn-sm   { padding:5px 11px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; border:none; text-decoration:none; display:inline-flex; align-items:center; gap:3px; }
    .btn-edit   { background:#ede9fe; color:#5b21b6; }
    .btn-delete { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>
<?php require_once '../_nav.php'; ?>

<div class="container">
  <div class="page-title">Gestion des categories</div>

  <?php if ($success): ?><div class="msg-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="msg-error"><?= $error ?></div><?php endif; ?>

  <div class="grid">
    <!-- Liste -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Catégories (<?= count($categories) ?>)</div>
      </div>
      <table>
        <thead><tr><th>#</th><th>Nom</th><th>Produits</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
          <tr>
            <td style="color:var(--mid);font-size:12px;">#<?= $cat['id'] ?></td>
            <td style="font-weight:700;"><?= h($cat['nom']) ?></td>
            <td><span class="badge-count"><?= $cat['nb_produits'] ?> produit(s)</span></td>
            <td>
              <a href="?edit=<?= $cat['id'] ?>" class="btn-sm btn-edit">Modifier</a>
              <?php if (canDelete() && $cat['nb_produits'] == 0): ?>
                <a href="?supprimer=<?= $cat['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Supprimer <?= h($cat['nom']) ?> ?')"></a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($categories)): ?>
            <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--mid);">Aucune catégorie.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
      <h3><?= $edit_cat ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h3>
      <form method="POST">
        <?php if ($edit_cat): ?>
          <input type="hidden" name="id" value="<?= $edit_cat['id'] ?>">
        <?php endif; ?>
        <label>Nom de la catégorie</label>
        <input type="text" name="nom" value="<?= $edit_cat ? h($edit_cat['nom']) : '' ?>" placeholder="Ex : Boissons, Snacks..." required>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button type="submit" name="<?= $edit_cat ? 'modifier' : 'ajouter' ?>" class="btn btn-red">
            <?= $edit_cat ? 'Enregistrer' : 'Ajouter' ?>
          </button>
          <?php if ($edit_cat): ?>
            <a href="categories.php" class="btn btn-gray">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<footer style="text-align:center;padding:2rem;color:#94a3b8;font-size:12px;">© <?= date('Y') ?> StockSmart Pro</footer>
</body>
</html>
