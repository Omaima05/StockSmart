<?php
require_once '../config.php';
requireLogin();

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produit_id  = (int)($_POST['produit_id']      ?? 0);
    $type        = $_POST['type_mouvement']          ?? '';
    $quantite    = (int)($_POST['quantite']          ?? 0);
    $commentaire = trim($_POST['commentaire']        ?? '');

    $typesValides = ['entree','vente','perte','casse','sortie'];

    if (!$produit_id || !in_array($type,$typesValides) || $quantite <= 0) {
        $message = "Donnees invalides. Verifiez tous les champs.";
    } else {
        $stmtProd = $pdo->prepare("SELECT nom, quantite FROM produits WHERE id = ?");
        $stmtProd->execute([$produit_id]);
        $produit  = $stmtProd->fetch();

        if (!$produit) {
            $message = "Produit introuvable.";
        } elseif ($type !== 'entree' && $quantite > $produit['quantite']) {
            $message = "Stock insuffisant. Stock actuel : <strong>{$produit['quantite']}</strong> unite(s).";
        } else {
            $pdo->prepare("INSERT INTO mouvements (produit_id, utilisateur_id, type_mouvement, quantite, commentaire) VALUES (?,?,?,?,?)")
                ->execute([$produit_id, $_SESSION['user_id'], $type, $quantite, $commentaire]);
            $action  = $type === 'entree' ? 'ajoute au' : 'retire du';
            $message = "{$quantite} unite(s) de <strong>" . h($produit['nom']) . "</strong> $action stock.";
            $success = true;
        }
    }
}

$produits   = $pdo->query("SELECT id, nom, quantite FROM produits ORDER BY nom")->fetchAll();
$historique = $pdo->query("
    SELECT m.date_mouvement, m.type_mouvement, m.quantite, m.commentaire,
           p.nom AS produit_nom,
           CONCAT(u.prenom, ' ', u.nom) AS utilisateur
    FROM mouvements m
    JOIN produits p ON m.produit_id = p.id
    LEFT JOIN utilisateurs u ON m.utilisateur_id = u.id
    ORDER BY m.date_mouvement DESC
    LIMIT 50
")->fetchAll();
?>
<?php require_once '../_nav.php'; ?>

<style>
  .container { max-width:1100px; margin:2rem auto; padding:0 2rem; }
  .page-title { font-size:20px; font-weight:800; margin-bottom:1.5rem; }
  .grid { display:grid; grid-template-columns:360px 1fr; gap:1.5rem; }

  .form-card { background:#fff; border-radius:14px; border:1px solid var(--border); padding:1.5rem; position:sticky; top:75px; }
  .form-card h3 { font-size:15px; font-weight:700; margin-bottom:1.25rem; }

  .field { margin-bottom:14px; }
  .field label { display:block; font-size:11px; font-weight:700; color:var(--mid); margin-bottom:7px; text-transform:uppercase; letter-spacing:.4px; }
  .field select,
  .field input { width:100%; padding:11px 13px; border-radius:9px; border:1px solid var(--border); font-family:inherit; font-size:13px; outline:none; transition:border-color .15s; background:#fff; }
  .field select:focus,
  .field input:focus { border-color:#e94560; }

  .btn-submit { width:100%; padding:13px; border-radius:10px; border:none; background:#e94560; color:#fff; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit; transition:all .15s; }
  .btn-submit:hover { box-shadow:0 4px 14px rgba(233,69,96,.35); transform:translateY(-1px); }

  .table-card { background:#fff; border-radius:14px; border:1px solid var(--border); overflow:hidden; }
  .table-header { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
  .table-title  { font-size:15px; font-weight:700; }

  .type-pill { display:inline-flex; align-items:center; padding:4px 10px; border-radius:10px; font-size:11px; font-weight:700; }
  .type-entree { background:#dcfce7; color:#166534; }
  .type-sortie,
  .type-vente,
  .type-perte,
  .type-casse  { background:#fee2e2; color:#991b1b; }

  @media(max-width:800px) { .grid{grid-template-columns:1fr;} .form-card{position:static;} }
</style>

<div class="container">
  <div class="page-title">Mouvements de stock</div>

  <?php if ($message): ?>
    <div class="<?= $success?'msg-success':'msg-error' ?>"><?= $message ?></div>
  <?php endif; ?>

  <div class="grid">

    <!-- Formulaire -->
    <?php if (canEdit()): ?>
    <div class="form-card">
      <h3>Enregistrer un mouvement</h3>
      <form method="POST">
        <div class="field">
          <label>Produit</label>
          <select name="produit_id" required>
            <option value="">Choisir un produit...</option>
            <?php foreach ($produits as $p): ?>
              <option value="<?= $p['id'] ?>"><?= h($p['nom']) ?> (Stock : <?= $p['quantite'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Type de mouvement</label>
          <select name="type_mouvement" required>
            <option value="">Choisir...</option>
            <option value="entree">Entree — Approvisionnement</option>
            <option value="vente">Sortie — Vente</option>
            <option value="perte">Sortie — Perte</option>
            <option value="casse">Sortie — Casse</option>
          </select>
        </div>
        <div class="field">
          <label>Quantite</label>
          <input type="number" name="quantite" min="1" placeholder="Ex: 6" required>
        </div>
        <div class="field">
          <label>Commentaire (optionnel)</label>
          <input type="text" name="commentaire" placeholder="Ex: Livraison fournisseur #42">
        </div>
        <button type="submit" class="btn-submit">Enregistrer le mouvement</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- Historique -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-title">Historique (<?= count($historique) ?>)</div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Produit</th>
            <th>Type</th>
            <th>Qte</th>
            <th>Employe</th>
            <th>Commentaire</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($historique)): ?>
            <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--mid);">Aucun mouvement enregistre.</td></tr>
          <?php else: ?>
            <?php foreach ($historique as $m): $entree = $m['type_mouvement']==='entree'; ?>
            <tr>
              <td style="white-space:nowrap;color:var(--mid);font-size:12px;"><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
              <td style="font-weight:700;"><?= h($m['produit_nom']) ?></td>
              <td>
                <span class="type-pill type-<?= $m['type_mouvement'] ?>">
                  <?= $entree ? 'Entree' : ucfirst(h($m['type_mouvement'])) ?>
                </span>
              </td>
              <td style="font-weight:800;color:<?= $entree?'#16a34a':'#991b1b' ?>;">
                <?= $entree?'+':'-' ?><?= (int)$m['quantite'] ?>
              </td>
              <td style="font-size:12px;"><?= h($m['utilisateur'] ?? 'Systeme') ?></td>
              <td style="font-size:12px;color:var(--mid);"><?= h($m['commentaire'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<footer>© <?= date('Y') ?> StockSmart Pro</footer>
<script src="../js/script.js"></script>
</body>
</html>
