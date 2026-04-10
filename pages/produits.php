<?php
require_once '../config.php';
requireLogin();

$uploadDir = '../assets/img/produits/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$success = null;
$error   = null;

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
            $pdo->prepare("INSERT INTO produits (reference,nom,marque,fournisseur,categorie_id,quantite,seuil_alerte,prix,image) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$ref,$nom,$marque,$fournisseur,$cat,$qte,$seuil,$prix,$imagePath]);
            $success = "Produit <strong>" . h($nom) . "</strong> ajouté !";
        } catch (PDOException) { $error = "Référence déjà utilisée ou données invalides."; }
    } else {
        $pdo->prepare("UPDATE produits SET reference=?,nom=?,marque=?,fournisseur=?,categorie_id=?,quantite=?,seuil_alerte=?,prix=?,image=? WHERE id=?")
            ->execute([$ref,$nom,$marque,$fournisseur,$cat,$qte,$seuil,$prix,$imagePath,$id]);
        $success = "Produit mis à jour.";
    }
}

if (isset($_GET['supprimer']) && canDelete()) {
    $pdo->prepare("DELETE FROM produits WHERE id=?")->execute([(int)$_GET['supprimer']]);
    $success = "Produit supprimé.";
}

$produits   = $pdo->query("SELECT p.*, c.nom AS cat_nom FROM produits p LEFT JOIN categories c ON p.categorie_id=c.id ORDER BY p.nom ASC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$edit_p = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM produits WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit_p = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventaire — StockSmart Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--red:#e94560;--mid:#64748b;--border:#e2e8f0;--light:#f8fafc;}
    body{font-family:'Inter',system-ui,sans-serif;background:var(--light);color:#0d1117;}
    .container{max-width:1300px;margin:2rem auto;padding:0 2rem;}
    .page-title{font-size:20px;font-weight:800;margin-bottom:1.5rem;}
    .msg-success{background:#dcfce7;color:#166534;padding:14px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:1.5rem;border:1px solid #bbf7d0;}
    .msg-error{background:#fee2e2;color:#991b1b;padding:14px 18px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:1.5rem;border:1px solid #fecdd3;}
    .form-card{background:#fff;border-radius:14px;border:1px solid var(--border);padding:1.5rem;margin-bottom:1.5rem;}
    .form-card h3{font-size:15px;font-weight:800;margin-bottom:1rem;}
    .off-tip{font-size:12px;color:var(--mid);background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px 14px;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;}
    .off-tip svg{width:16px;height:16px;fill:#0ea5e9;flex-shrink:0;}
    .form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;}
    .fg label{display:block;font-size:11px;font-weight:700;color:var(--mid);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;}
    .fg input,.fg select{width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:#fff;font-family:inherit;font-size:13px;outline:none;transition:border-color .15s;}
    .fg input:focus,.fg select:focus{border-color:var(--red);}
    .form-actions{display:flex;gap:10px;margin-top:1.25rem;}
    .btn{padding:10px 20px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
    .btn-red{background:var(--red);color:#fff;}
    .btn-gray{background:var(--light);color:var(--mid);border:1px solid var(--border);}
    .search-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;gap:1rem;}
    .search-input{padding:10px 14px;border-radius:10px;border:1px solid var(--border);font-family:inherit;font-size:14px;outline:none;width:300px;}
    .search-input:focus{border-color:var(--red);}
    .table-wrap{background:#fff;border-radius:14px;border:1px solid var(--border);overflow:hidden;}
    table{width:100%;border-collapse:collapse;}
    thead th{background:var(--light);font-size:11px;font-weight:600;color:var(--mid);text-transform:uppercase;letter-spacing:.4px;padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);}
    tbody tr{border-bottom:1px solid #f5f7fa;}
    tbody tr:last-child{border-bottom:none;}
    tbody tr:hover{background:#fafbff;}
    tbody td{padding:12px 14px;font-size:13px;vertical-align:middle;}

    /* ── Photo avec skeleton loader ── */
    .img-wrap{position:relative;width:52px;height:52px;border-radius:10px;overflow:hidden;background:#f1f5f9;border:1px solid var(--border);}
    .img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,.6) 50%,transparent 100%);background-size:200% 100%;animation:shimmer 1.2s infinite;opacity:1;transition:opacity .3s;}
    .img-wrap.loaded::after{opacity:0;pointer-events:none;}
    @keyframes shimmer{0%{background-position:200% 0;}100%{background-position:-200% 0;}}
    .prod-img{width:52px;height:52px;object-fit:contain;padding:4px;display:block;}

    .prod-name{font-size:13px;font-weight:700;}
    .prod-ref{font-size:11px;color:var(--mid);margin-top:2px;}
    .badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:10px;font-size:11px;font-weight:700;}
    .badge-ok{background:#dcfce7;color:#166534;}
    .badge-warn{background:#fef3c7;color:#92400e;}
    .badge-crit{background:#fee2e2;color:#991b1b;}
    .stock-bw{width:60px;height:4px;background:#f0f0f0;border-radius:2px;margin-top:4px;}
    .stock-bf{height:4px;border-radius:2px;}
    .btn-sm{padding:6px 12px;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
    .btn-edit{background:#ede9fe;color:#5b21b6;}
    .btn-delete{background:#fee2e2;color:#991b1b;}
  </style>
</head>
<body>
<?php require_once '../_nav.php'; ?>

<div class="container">
  <div class="page-title">Inventaire produits</div>

  <?php if ($success): ?><div class="msg-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="msg-error"><?= $error ?></div><?php endif; ?>

  <?php if (canEdit()): ?>
  <div class="form-card">
    <h3><?= $edit_p ? 'Modifier le produit' : 'Nouveau produit' ?></h3>
    <div class="off-tip">
      <svg viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span><strong>Photo automatique :</strong> renseignez le <strong>Nom</strong> et la <strong>Marque</strong> — la photo du vrai paquet sera récupérée automatiquement.</span>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <?php if ($edit_p): ?>
        <input type="hidden" name="id" value="<?= $edit_p['id'] ?>">
        <input type="hidden" name="ancienne_image" value="<?= h($edit_p['image'] ?? '') ?>">
      <?php endif; ?>
      <div class="form-grid">
        <div class="fg"><label>Référence *</label><input type="text" name="reference" value="<?= h($edit_p['reference'] ?? '') ?>" placeholder="P001" required></div>
        <div class="fg"><label>Nom du produit *</label><input type="text" name="nom" value="<?= h($edit_p['nom'] ?? '') ?>" placeholder="Jus Orange 1L" required></div>
        <div class="fg"><label>Marque </label><input type="text" name="marque" value="<?= h($edit_p['marque'] ?? '') ?>" placeholder="Tropicana, Herta..."></div>
        <div class="fg"><label>Fournisseur</label><input type="text" name="fournisseur" value="<?= h($edit_p['fournisseur'] ?? '') ?>"></div>
        <div class="fg">
          <label>Catégorie *</label>
          <select name="categorie_id" required>
            <option value="">Choisir...</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_p && $edit_p['categorie_id']==$c['id']) ? 'selected' : '' ?>><?= h($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label>Quantité</label><input type="number" name="quantite" value="<?= $edit_p['quantite'] ?? 0 ?>" min="0"></div>
        <div class="fg"><label>Seuil alerte</label><input type="number" name="seuil_alerte" value="<?= $edit_p['seuil_alerte'] ?? 5 ?>" min="0"></div>
        <div class="fg"><label>Prix (€)</label><input type="number" name="prix" value="<?= $edit_p['prix'] ?? 0 ?>" step="0.01" min="0"></div>
        <div class="fg"><label>Photo manuelle</label><input type="file" name="image" accept="image/*"></div>
      </div>
      <div class="form-actions">
        <button type="submit" name="<?= $edit_p ? 'modifier' : 'ajouter' ?>" class="btn btn-red"><?= $edit_p ? 'Enregistrer' : 'Ajouter' ?></button>
        <?php if ($edit_p): ?><a href="produits.php" class="btn btn-gray">Annuler</a><?php endif; ?>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="search-row">
    <input type="text" id="searchInput" class="search-input" placeholder="🔍 Rechercher un produit...">
    <span style="font-size:13px;color:var(--mid);"><?= count($produits) ?> produit(s)</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Photo</th><th>Produit</th><th>Catégorie</th><th>Stock</th><th>Niveau</th><th>Prix</th><th style="text-align:right;">Actions</th></tr></thead>
      <tbody id="productTable">
        <?php foreach ($produits as $p):
          $searchTerm = trim(($p['marque'] ?? '') . ' ' . $p['nom']);
          $localImg   = '../' . ($p['image'] ?? '');
          $hasLocal   = !empty($p['image']) && file_exists($localImg) && strpos($p['image'],'no-image')===false;
          $avatarUrl  = 'https://ui-avatars.com/api/?name='.urlencode(mb_substr($p['nom'],0,2)).'&background=f1f5f9&color=64748b&size=80&bold=true';
          $srcInit    = $hasLocal ? $localImg : $avatarUrl;
        ?>
        <tr class="product-row">
          <td>
            <!-- data-search déclenche le script OFF automatiquement -->
            <div class="img-wrap <?= $hasLocal ? 'loaded' : '' ?>" id="wrap_<?= $p['id'] ?>">
              <img
                class="prod-img"
                src="<?= $srcInit ?>"
                alt="<?= h($p['nom']) ?>"
                <?= !$hasLocal ? 'data-search="' . h($searchTerm) . '" data-wrapid="wrap_' . $p['id'] . '"' : '' ?>
              >
            </div>
          </td>
          <td>
            <div class="prod-name"><?= h($p['nom']) ?></div>
            <div class="prod-ref"><?= h($p['reference']) ?><?= !empty($p['marque']) ? ' · '.h($p['marque']) : '' ?></div>
          </td>
          <td style="font-weight:600;"><?= h($p['cat_nom'] ?? '—') ?></td>
          <td><span class="badge <?= $p['quantite']==0?'badge-crit':($p['quantite']<=$p['seuil_alerte']?'badge-warn':'badge-ok') ?>"><?= $p['quantite'] ?> en stock</span></td>
          <td>
            <div class="stock-bw"><div class="stock-bf" style="width:<?= $p['seuil_alerte']>0?min(100,round(($p['quantite']/$p['seuil_alerte'])*100)):100 ?>%;background:<?= $p['quantite']==0?'#e94560':($p['quantite']<=$p['seuil_alerte']?'#f59e0b':'#22c55e') ?>;"></div></div>
            <div style="font-size:10px;color:var(--mid);margin-top:2px;">seuil: <?= $p['seuil_alerte'] ?></div>
          </td>
          <td style="font-weight:700;"><?= number_format($p['prix'],2,',',' ') ?> €</td>
          <td style="text-align:right;">
            <?php if (canEdit()): ?><a href="?edit=<?= $p['id'] ?>" class="btn-sm btn-edit">Modifier</a><?php endif; ?>
            <?php if (canDelete()): ?><a href="?supprimer=<?= $p['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Supprimer <?= h($p['nom']) ?> ?')"></a><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($produits)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--mid);">Aucun produit. Ajoutez votre premier produit ci-dessus.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<footer style="text-align:center;padding:2rem;color:#94a3b8;font-size:12px;">© <?= date('Y') ?> StockSmart Pro</footer>

<!-- ═══ OPEN FOOD FACTS AUTO PHOTO ═══ -->
<script src="../js/script.js"></script>
<script>
const OFF_CACHE = {};

async function loadProductPhoto(img) {
  const search  = img.getAttribute('data-search');
  const wrapId  = img.getAttribute('data-wrapid');
  const wrap    = wrapId ? document.getElementById(wrapId) : null;
  if (!search) return;

  try {
    // Chercher dans le cache mémoire d'abord
    if (OFF_CACHE[search]) {
      img.src = OFF_CACHE[search];
      if (wrap) wrap.classList.add('loaded');
      return;
    }

    const url  = `https://world.openfoodfacts.org/cgi/search.pl?search_terms=${encodeURIComponent(search)}&search_simple=1&action=process&json=1&page_size=5`;
    const res  = await fetch(url);
    const data = await res.json();

    let photoUrl = null;
    for (const product of (data.products || [])) {
      const candidate = product.image_front_url || product.image_url;
      if (candidate && candidate.startsWith('http')) {
        photoUrl = candidate;
        break;
      }
    }

    if (photoUrl) {
      img.src = photoUrl;
      img.style.objectFit = 'contain';
      OFF_CACHE[search] = photoUrl;
    }
  } catch (e) {
    // Pas de connexion → avatar par défaut reste affiché
  } finally {
    if (wrap) wrap.classList.add('loaded');
  }
}

// Lancer le chargement des photos avec délai progressif
document.addEventListener('DOMContentLoaded', () => {
  const images = document.querySelectorAll('img[data-search]');
  images.forEach((img, i) => {
    setTimeout(() => loadProductPhoto(img), i * 100);
  });

  // Recherche dynamique
  document.getElementById('searchInput').addEventListener('keyup', function() {
    const f = this.value.toLowerCase();
    document.querySelectorAll('.product-row').forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(f) ? '' : 'none';
    });
  });
});
</script>
</body>
</html>
