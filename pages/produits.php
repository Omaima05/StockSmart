<?php
require_once '../config.php';
requireLogin();

// On récupère l'ID de l'enseigne de l'utilisateur connecté
$enseigne_id = $_SESSION['enseigne_id'] ?? 0; 

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/StockSmart/assets/img/produits/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$success = null;
$error   = null;

// --- LOGIQUE D'AJOUT ET MODIFICATION ---
if (isset($_POST['ajouter']) || isset($_POST['modifier'])) {
    $id          = $_POST['id']              ?? null;
    $ref         = trim($_POST['reference']    ?? '');
    $nom         = trim($_POST['nom']          ?? '');
    $marque      = trim($_POST['marque']       ?? '');
    $fournisseur = trim($_POST['fournisseur']  ?? '');
    $cat         = (int)($_POST['categorie_id'] ?? 0);
    $qte         = (int)($_POST['quantite']    ?? 0);
    $seuil       = (int)($_POST['seuil_alerte'] ?? 5);
    $prix        = (float)($_POST['prix']      ?? 0);
    $imagePath   = $_POST['ancienne_image']    ?? '';

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $fileName = uniqid('img_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName))
                $imagePath = 'assets/img/produits/' . $fileName;
        }
    }

    if (isset($_POST['ajouter'])) {
        try {
            $sql = "INSERT INTO produits (reference, nom, marque, fournisseur, categorie_id, quantite, seuil_alerte, prix, image, enseigne_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$ref, $nom, $marque, $fournisseur, $cat, $qte, $seuil, $prix, $imagePath, $enseigne_id]);
            $success = "Produit ajouté avec succès !";
        } catch (PDOException $e) { $error = "Erreur : Référence déjà existante."; }
    } else {
        $sql = "UPDATE produits SET reference=?, nom=?, marque=?, fournisseur=?, categorie_id=?, quantite=?, seuil_alerte=?, prix=?, image=? 
                WHERE id=? AND enseigne_id=?";
        $pdo->prepare($sql)->execute([$ref, $nom, $marque, $fournisseur, $cat, $qte, $seuil, $prix, $imagePath, $id, $enseigne_id]);
        $success = "Produit mis à jour.";
    }
}

// --- SUPPRESSION SÉCURISÉE (CORRIGÉE) ---
if (isset($_GET['supprimer']) && canDelete()) {
    $id_a_supprimer = (int)$_GET['supprimer'];
    try {
        $pdo->beginTransaction();
        
        // 1. Supprimer d'abord les mouvements liés (les "enfants")
        $stmtMouv = $pdo->prepare("DELETE FROM mouvements WHERE produit_id = ? AND enseigne_id = ?");
        $stmtMouv->execute([$id_a_supprimer, $enseigne_id]);

        // 2. Supprimer le produit (le "parent")
        $stmtProd = $pdo->prepare("DELETE FROM produits WHERE id = ? AND enseigne_id = ?");
        $stmtProd->execute([$id_a_supprimer, $enseigne_id]);

        $pdo->commit();
        $success = "Produit et historique supprimés.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la suppression.";
    }
}

// 1. Les produits : filtrés par enseigne
$stmt = $pdo->prepare("SELECT p.*, c.nom AS cat_nom FROM produits p LEFT JOIN categories c ON p.categorie_id=c.id WHERE p.enseigne_id = ? ORDER BY p.nom ASC");
$stmt->execute([$enseigne_id]);
$produits = $stmt->fetchAll();

// 2. Les catégories : GLOBALES
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom ASC")->fetchAll();

$edit_p = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM produits WHERE id=? AND enseigne_id=?");
    $s->execute([(int)$_GET['edit'], $enseigne_id]);
    $edit_p = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inventaire — StockSmart Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--red:#e94560;--mid:#64748b;--border:#e2e8f0;--light:#f8fafc;}
    body{font-family:'DM Sans',system-ui,sans-serif;background:var(--light);color:#0d1117;}
    .container{max-width:1300px;margin:2rem auto;padding:0 2rem;}
    .page-title{font-size:20px;font-weight:800;margin-bottom:1.5rem;}
    .msg-success{background:#dcfce7;color:#166534;padding:14px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:1.5rem;border:1px solid #bbf7d0;}
    .msg-error{background:#fee2e2;color:#991b1b;padding:14px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:1.5rem;border:1px solid #fecdd3;}
    .form-card{background:#fff;border-radius:14px;border:1px solid var(--border);padding:1.5rem;margin-bottom:1.5rem;}
    .form-card h3{font-size:15px;font-weight:800;margin-bottom:1rem;}
    .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;}
    .fg label{display:block;font-size:11px;font-weight:700;color:var(--mid);margin-bottom:5px;text-transform:uppercase;}
    .fg input,.fg select{width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--border);font-size:13px;outline:none;}
    .btn-red{background:var(--red);color:#fff;padding:10px 20px;border-radius:8px;border:none;font-weight:700;cursor:pointer;}
    .table-wrap{background:#fff;border-radius:14px;border:1px solid var(--border);overflow:hidden;}
    table{width:100%;border-collapse:collapse;}
    thead th{background:var(--light);font-size:11px;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);}
    tbody td{padding:14px;font-size:13px;border-bottom:1px solid #f1f5f9;}

    .img-wrap{
        width: 65px; height: 65px; border-radius: 12px; overflow: hidden;
        background: #f1f5f9; border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
    }
    .prod-img{ max-width: 90%; max-height: 90%; object-fit: contain; display: block; }

    #live-preview-container {
      width: 150px; height: 150px; border: 2px dashed var(--border);
      border-radius: 12px; background: #fff;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    #live-preview-img { max-width: 90%; max-height: 90%; object-fit: contain; display: none; }
    .preview-icon { width: 48px; height: 48px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; }
  </style>
</head>
<body>
<?php require_once '../_nav.php'; ?>

<div class="container">
  <div class="page-title">Gestion de l'inventaire</div>

  <?php if ($success): ?><div class="msg-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="msg-error"><?= $error ?></div><?php endif; ?>

  <div class="form-card">
    <div style="display:grid; grid-template-columns: 1fr 150px; gap: 20px;">
        <div>
            <h3><?= $edit_p ? 'Modifier le produit' : 'Ajouter un produit' ?></h3>
            <form method="POST" enctype="multipart/form-data">
              <?php if ($edit_p): ?>
                <input type="hidden" name="id" value="<?= $edit_p['id'] ?>">
                <input type="hidden" name="ancienne_image" value="<?= htmlspecialchars($edit_p['image'] ?? '') ?>">
              <?php endif; ?>
              <div class="form-grid">
                <div class="fg"><label>Référence *</label><input type="text" name="reference" value="<?= htmlspecialchars($edit_p['reference'] ?? '') ?>" required></div>
                <div class="fg"><label>Nom *</label><input type="text" id="input_nom" name="nom" value="<?= htmlspecialchars($edit_p['nom'] ?? '') ?>" required></div>
                <div class="fg"><label>Marque</label><input type="text" id="input_marque" name="marque" value="<?= htmlspecialchars($edit_p['marque'] ?? '') ?>"></div>
                <div class="fg">
                  <label>Catégorie *</label>
                  <select name="categorie_id" required>
                    <option value="">Sélectionner...</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= $c['id'] ?>" <?= ($edit_p && $edit_p['categorie_id']==$c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nom']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="fg"><label>Quantité</label><input type="number" name="quantite" value="<?= $edit_p['quantite'] ?? 0 ?>"></div>
                <div class="fg"><label>Prix (€)</label><input type="number" name="prix" value="<?= $edit_p['prix'] ?? 0 ?>" step="0.01"></div>
                <div class="fg"><label>Photo manuelle</label><input type="file" name="image" accept="image/*"></div>
              </div>
              <button type="submit" name="<?= $edit_p ? 'modifier' : 'ajouter' ?>" class="btn-red" style="margin-top:20px;"><?= $edit_p ? 'Enregistrer' : 'Ajouter au stock' ?></button>
            </form>
        </div>
        
        <div id="live-preview-container">
            <div class="preview-icon" id="preview-icon-wrapper">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20Z" fill="#CBD5E1"/>
                    <path d="M12 17C14.7614 17 17 14.7614 17 12C17 9.23858 14.7614 7 12 7C9.23858 7 7 9.23858 7 12C7 14.7614 9.23858 17 12 17Z" fill="#CBD5E1"/>
                </svg>
            </div>
            <img id="live-preview-img" src="">
        </div>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Photo</th><th>Désignation</th><th>Catégorie</th><th>Stock</th><th>Prix</th><th style="text-align:right;">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($produits as $p): 
            $imgSrc = !empty($p['image']) && file_exists('../'.$p['image']) ? '../'.$p['image'] : '';
        ?>
        <tr>
          <td><div class="img-wrap"><img class="prod-img" src="<?= $imgSrc ?>" data-search="<?= htmlspecialchars($p['marque'] . ' ' . $p['nom']) ?>"></div></td>
          <td><strong><?= htmlspecialchars($p['nom']) ?></strong><br><small><?= htmlspecialchars($p['reference']) ?> | <?= htmlspecialchars($p['marque']) ?></small></td>
          <td><?= htmlspecialchars($p['cat_nom'] ?? 'Divers') ?></td>
          <td><?= $p['quantite'] ?> unités</td>
          <td><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
          <td style="text-align:right;">
            <a href="?edit=<?= $p['id'] ?>">Modifier</a> | 
            <a href="?supprimer=<?= $p['id'] ?>" style="color:#b91c1c;" onclick="return confirm('Supprimer ce produit et son historique ?')">Supprimer</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="../js/off-photos.js"></script>
</body>
</html>