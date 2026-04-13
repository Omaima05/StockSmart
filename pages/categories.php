<?php
require_once '../config.php';
requireLogin();

$success = '';
$error   = '';

// ── AJOUT (Global) ───────────────────────────────────────────
if (isset($_POST['ajouter'])) {
    if (!isAdmin()) {
        $error = "Seul un administrateur peut ajouter des catégories.";
    } else {
        $nom = trim($_POST['nom'] ?? '');
        if ($nom === '') {
            $error = "Le nom ne peut pas être vide.";
        } else {
            try {
                // On n'insère pas d'enseigne_id pour que ce soit global
                $pdo->prepare("INSERT INTO categories (nom) VALUES (?)")->execute([$nom]);
                $success = "Catégorie <strong>" . h($nom) . "</strong> ajoutée !";
            } catch (PDOException) {
                $error = "Cette catégorie existe déjà.";
            }
        }
    }
}

// ── MODIFICATION ─────────────────────────────────────────────
if (isset($_POST['modifier'])) {
    if (!isAdmin()) {
        $error = "Action non autorisée.";
    } else {
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
}

// ── SUPPRESSION ──────────────────────────────────────────────
if (isset($_GET['supprimer']) && canDelete()) {
    $id = (int)$_GET['supprimer'];
    // On vérifie si des produits (toutes enseignes confondues) utilisent cette catégorie
    $nb = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = ?");
    $nb->execute([$id]);
    if ($nb->fetchColumn() > 0) {
        $error = "Impossible : cette catégorie est utilisée par des produits.";
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        $success = "Catégorie supprimée.";
    }
}

$edit_cat = null;
if (isset($_GET['edit']) && isAdmin()) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_cat = $stmt->fetch();
}

// Liste globale des catégories avec le compte des produits
$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM produits WHERE categorie_id = c.id) AS nb_produits FROM categories c ORDER BY c.nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Catégories — StockSmart Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root { --red:#e94560; --mid:#64748b; --border:#e2e8f0; --light:#f8fafc; }
    body { font-family:'Inter',sans-serif; background:var(--light); color:#0d1117; }
    .container { max-width:900px; margin:2rem auto; padding:0 2rem; }
    .page-title { font-size:20px; font-weight:800; margin-bottom:1.5rem; }
    .msg-success { background:#dcfce7; color:#166534; padding:14px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:1.5rem; border:1px solid #bbf7d0; }
    .msg-error   { background:#fee2e2; color:#991b1b; padding:14px 18px; border-radius:10px; font-size:13px; font-weight:600; margin-bottom:1.5rem; border:1px solid #fecdd3; }
    .grid { display:grid; grid-template-columns:1fr 340px; gap:1.5rem; }
    .card { background:#fff; border-radius:14px; border:1px solid var(--border); overflow:hidden; }
    .card-header { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border); }
    .card-title  { font-size:15px; font-weight:800; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:var(--light); font-size:11px; font-weight:600; color:var(--mid); text-transform:uppercase; padding:10px 16px; text-align:left; border-bottom:1px solid var(--border); }
    tbody td { padding:12px 16px; font-size:13px; border-bottom:1px solid #f5f7fa; }
    .badge-count { background:var(--light); color:var(--mid); font-size:11px; font-weight:700; padding:3px 8px; border-radius:8px; }
    .form-card { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.5rem; height: fit-content; }
    .form-card h3 { font-size:15px; font-weight:800; margin-bottom:1.25rem; }
    label { display:block; font-size:11px; font-weight:700; color:var(--mid); margin-bottom:7px; text-transform:uppercase; }
    input[type=text] { width:100%; padding:11px 13px; border-radius:9px; border:1px solid var(--border); outline:none; }
    .btn { padding:11px 20px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; border:none; text-decoration:none; display:inline-flex; align-items:center; gap:6px; margin-top:12px; }
    .btn-red  { background:var(--red); color:#fff; }
    .btn-gray { background:var(--light); color:var(--mid); border:1px solid var(--border); }
    .btn-sm   { padding:5px 11px; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; }
    .btn-edit   { background:#ede9fe; color:#5b21b6; }
    .btn-delete { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>
<?php require_once '../_nav.php'; ?>

<div class="container">
  <div class="page-title">Gestion des catégories</div>

  <?php if ($success): ?><div class="msg-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="msg-error"><?= $error ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <div class="card-header"><div class="card-title">Liste globale</div></div>
      <table>
        <thead><tr><th>Nom</th><th>Produits</th><?php if(isAdmin()): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
          <tr>
            <td style="font-weight:700;"><?= h($cat['nom']) ?></td>
            <td><span class="badge-count"><?= $cat['nb_produits'] ?> produit(s) au total</span></td>
            <?php if(isAdmin()): ?>
            <td>
              <a href="?edit=<?= $cat['id'] ?>" class="btn-sm btn-edit">Modifier</a>
              <?php if ($cat['nb_produits'] == 0): ?>
                <a href="?supprimer=<?= $cat['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Supprimer ?')">Supprimer</a>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if(isAdmin()): ?>
    <div class="form-card">
      <h3><?= $edit_cat ? 'Modifier' : 'Ajouter' ?></h3>
      <form method="POST">
        <?php if ($edit_cat): ?><input type="hidden" name="id" value="<?= $edit_cat['id'] ?>"><?php endif; ?>
        <label>Nom de la catégorie</label>
        <input type="text" name="nom" value="<?= $edit_cat ? h($edit_cat['nom']) : '' ?>" placeholder="Ex : Boissons" required>
        <div style="display:flex;gap:10px;">
          <button type="submit" name="<?= $edit_cat ? 'modifier' : 'ajouter' ?>" class="btn btn-red"><?= $edit_cat ? 'Enregistrer' : 'Ajouter' ?></button>
          <?php if ($edit_cat): ?><a href="categories.php" class="btn btn-gray">Annuler</a><?php endif; ?>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="form-card" style="background: #f0f9ff; border-color: #bae6fd;">
        <p style="font-size: 12px; color: #0369a1;">Seul un <strong>Administrateur</strong> peut gérer les catégories globales.</p>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
