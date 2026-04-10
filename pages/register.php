<?php
require_once '../config.php';

if (isLoggedIn()) { header('Location: ../dashboard.php'); exit; }

$erreurs = [];
$step    = $_POST['step'] ?? '1';

// ── TRAITEMENT FINAL ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {

    $mode   = $_POST['mode']    ?? '';
    $nom    = trim($_POST['nom']    ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $mdp    = $_POST['password']    ?? '';
    $mdp2   = $_POST['password2']   ?? '';

    if (empty($prenom))                                          $erreurs['prenom']    = "Prénom obligatoire";
    if (empty($nom))                                             $erreurs['nom']       = "Nom obligatoire";
    if (empty($email)||!filter_var($email,FILTER_VALIDATE_EMAIL))$erreurs['email']     = "Email invalide";
    if (strlen($mdp) < 6)                                        $erreurs['password']  = "6 caractères minimum";
    if ($mdp !== $mdp2)                                          $erreurs['password2'] = "Les mots de passe ne correspondent pas";

    if (empty($erreurs)) {
        $s = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $s->execute([$email]);
        if ($s->fetch()) $erreurs['email'] = "Cet email est déjà utilisé";
    }

    // ── MODE A : Créer une nouvelle enseigne ─────────────────
    if ($mode === 'create') {
        $enseigne_nom = trim($_POST['enseigne_nom'] ?? '');
        if (empty($enseigne_nom)) $erreurs['enseigne_nom'] = "Le nom de l'enseigne est obligatoire";

        if (empty($erreurs['enseigne_nom'])) {
            $s = $pdo->prepare("SELECT id FROM enseignes WHERE nom = ?");
            $s->execute([$enseigne_nom]);
            if ($s->fetch()) $erreurs['enseigne_nom'] = "Ce nom d'enseigne est déjà pris";
        }

        if (empty($erreurs)) {
            $couleur   = $_POST['couleur'] ?? '#e94560';
            $logoUrl   = 'https://ui-avatars.com/api/?name='.urlencode(mb_substr($enseigne_nom,0,2)).'&background='.ltrim($couleur,'#').'&color=fff&size=64&bold=true';
            // Code invitation = 4 premiers chars + 4 chiffres aléatoires
            $codeInvit = strtoupper(preg_replace('/[^A-Z0-9]/', '', $enseigne_nom));
            $codeInvit = substr($codeInvit, 0, 6) . '-' . rand(1000,9999);

            $pdo->prepare("INSERT INTO enseignes (nom, logo_url, couleur, code_invitation) VALUES (?,?,?,?)")
                ->execute([$enseigne_nom, $logoUrl, $couleur, $codeInvit]);
            $eid = $pdo->lastInsertId();

            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,role,enseigne_id) VALUES (?,?,?,?,'admin',?)")
                ->execute([$nom,$prenom,$email,$hash,$eid]);
            $uid = $pdo->lastInsertId();

            $_SESSION['user_id']      = $uid;
            $_SESSION['user_prenom']  = $prenom;
            $_SESSION['user_nom']     = "$prenom $nom";
            $_SESSION['user_role']    = 'admin';
            $_SESSION['enseigne_id']  = $eid;
            $_SESSION['enseigne_nom'] = $enseigne_nom;
            $_SESSION['enseigne_logo']= $logoUrl;
            $_SESSION['code_invitation'] = $codeInvit; // pour l'afficher au 1er login

            header('Location: ../dashboard.php');
            exit;
        }

    // ── MODE B : Rejoindre une enseigne existante ────────────
    } elseif ($mode === 'join') {
        $code = strtoupper(trim($_POST['code_invitation'] ?? ''));

        if (empty($code)) {
            $erreurs['code_invitation'] = "Le code d'invitation est obligatoire";
        } else {
            $s = $pdo->prepare("SELECT * FROM enseignes WHERE code_invitation = ? AND actif = 1");
            $s->execute([$code]);
            $enseigne = $s->fetch();
            if (!$enseigne) $erreurs['code_invitation'] = "Code invalide. Demandez-le à votre administrateur.";
        }

        if (empty($erreurs)) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO utilisateurs (nom,prenom,email,mot_de_passe,role,enseigne_id) VALUES (?,?,?,?,'employe',?)")
                ->execute([$nom,$prenom,$email,$hash,$enseigne['id']]);
            $uid = $pdo->lastInsertId();

            $_SESSION['user_id']      = $uid;
            $_SESSION['user_prenom']  = $prenom;
            $_SESSION['user_nom']     = "$prenom $nom";
            $_SESSION['user_role']    = 'employe';
            $_SESSION['enseigne_id']  = $enseigne['id'];
            $_SESSION['enseigne_nom'] = $enseigne['nom'];
            $_SESSION['enseigne_logo']= $enseigne['logo_url'] ?? '';

            header('Location: ../dashboard.php');
            exit;
        }
    }
}

$mode = $_POST['mode'] ?? $_GET['mode'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription — StockSmart Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--red:#e94560;--dark:#0d1117;}
    body{font-family:'Inter',sans-serif;background:var(--dark);color:#fff;min-height:100vh;display:flex;flex-direction:column;}
    .topbar{background:#161b22;border-bottom:1px solid rgba(255,255,255,.06);padding:12px 2rem;display:flex;align-items:center;justify-content:space-between;}
    .topbar-logo{font-weight:900;font-size:15px;color:#fff;text-decoration:none;}
    .topbar-logo span{color:var(--red);}
    .topbar-back{font-size:13px;color:rgba(255,255,255,.4);text-decoration:none;}
    .topbar-back:hover{color:rgba(255,255,255,.7);}
    .wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;}

    /* STEP 1 */
    .step1{width:100%;max-width:580px;text-align:center;}
    .step1 h1{font-size:28px;font-weight:900;margin-bottom:.5rem;letter-spacing:-.8px;}
    .step1 h1 em{color:var(--red);font-style:normal;}
    .step1 > p{font-size:15px;color:rgba(255,255,255,.4);margin-bottom:2.5rem;}
    .choice-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;}
    .choice-card{background:#161b22;border:2px solid rgba(255,255,255,.08);border-radius:18px;padding:2rem 1.5rem;text-align:left;cursor:pointer;text-decoration:none;transition:border-color .2s,transform .15s;display:block;}
    .choice-card:hover{border-color:var(--red);transform:translateY(-3px);}
    .choice-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem;}
    .choice-icon svg{width:26px;height:26px;}
    .choice-card h3{font-size:17px;font-weight:800;color:#fff;margin-bottom:.5rem;}
    .choice-card p{font-size:13px;color:rgba(255,255,255,.4);line-height:1.6;margin-bottom:1rem;}
    .choice-badge{display:inline-block;font-size:11px;font-weight:800;padding:4px 10px;border-radius:8px;text-transform:uppercase;letter-spacing:.3px;}

    /* STEP 2 */
    .card{background:#161b22;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:40px;width:100%;max-width:480px;box-shadow:0 25px 50px rgba(0,0,0,.5);}
    .back-btn{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:rgba(255,255,255,.4);text-decoration:none;margin-bottom:1.5rem;background:none;border:none;cursor:pointer;font-family:inherit;padding:0;}
    .back-btn:hover{color:rgba(255,255,255,.7);}
    .mode-banner{border-radius:12px;padding:12px 16px;margin-bottom:1.5rem;display:flex;align-items:center;gap:10px;}
    .mode-banner svg{width:18px;height:18px;flex-shrink:0;}
    .mode-banner-title{font-size:14px;font-weight:800;}
    .mode-banner-sub{font-size:12px;opacity:.7;margin-top:2px;}
    .section-label{font-size:11px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;}
    .section-sep{height:1px;background:rgba(255,255,255,.06);margin:18px 0;}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .field{margin-bottom:14px;}
    label{display:block;font-size:11px;font-weight:700;color:rgba(255,255,255,.4);margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px;}
    input[type=text],input[type=email],input[type=password]{width:100%;padding:13px;border-radius:10px;background:#0d1117;border:1px solid rgba(255,255,255,.1);color:#fff;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s;}
    input:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(233,69,96,.15);}
    input::placeholder{color:rgba(255,255,255,.2);}
    .color-row{display:flex;align-items:center;gap:12px;}
    input[type=color]{width:44px;height:40px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:none;cursor:pointer;padding:2px;}
    .color-preview{font-size:12px;color:rgba(255,255,255,.4);}
    .field-error{color:#f87171;font-size:12px;margin-top:5px;display:block;}
    .code-hint{font-size:12px;color:rgba(255,255,255,.3);margin-top:6px;line-height:1.55;}
    button[type=submit]{width:100%;padding:15px;border-radius:12px;border:none;background:var(--red);color:#fff;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;transition:all .2s;margin-top:6px;}
    button[type=submit]:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 24px rgba(233,69,96,.4);}
    .hint{text-align:center;margin-top:18px;font-size:13px;color:rgba(255,255,255,.35);}
    .hint a{color:var(--red);text-decoration:none;font-weight:700;}
    footer{text-align:center;padding:1.5rem;color:rgba(255,255,255,.15);font-size:12px;}
    @media(max-width:520px){.choice-grid{grid-template-columns:1fr;}.form-row{grid-template-columns:1fr;}.card{padding:28px 20px;}}
  </style>
</head>
<body>

<div class="topbar">
  <a href="../index.php" class="topbar-logo">StockSmart <span>Pro</span></a>
  <a href="../index.php" class="topbar-back">← Retour à l'accueil</a>
</div>

<div class="wrap">

<?php if (empty($mode)): ?>
<!-- ═══ ÉTAPE 1 : CHOIX ═══ -->
<div class="step1">
  <h1>Rejoindre <em>StockSmart Pro</em></h1>
  <p>Comment souhaitez-vous utiliser la plateforme ?</p>
  <div class="choice-grid">

    <a href="register.php?mode=create" class="choice-card">
      <div class="choice-icon" style="background:rgba(233,69,96,.15);">
        <svg viewBox="0 0 24 24" fill="#e94560"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      </div>
      <h3>Créer mon enseigne</h3>
      <p>Je monte ma propre enseigne. Je gérerai mes produits et mon équipe.</p>
      <span class="choice-badge" style="background:rgba(233,69,96,.15);color:#e94560;">Admin · Nouveau</span>
    </a>

    <a href="register.php?mode=join" class="choice-card">
      <div class="choice-icon" style="background:rgba(34,197,94,.12);">
        <svg viewBox="0 0 24 24" fill="#22c55e"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
      </div>
      <h3>Rejoindre une enseigne</h3>
      <p>Mon employeur utilise déjà StockSmart Pro. J'ai reçu un code d'invitation.</p>
      <span class="choice-badge" style="background:rgba(34,197,94,.12);color:#22c55e;">Employé · Code requis</span>
    </a>

  </div>
  <p class="hint" style="margin-top:2rem;">Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>

<?php elseif ($mode === 'create'): ?>
<!-- ═══ ÉTAPE 2A : CRÉER UNE ENSEIGNE ═══ -->
<div class="card">
  <a href="register.php" class="back-btn">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Retour
  </a>

  <div class="mode-banner" style="background:rgba(233,69,96,.1);border:1px solid rgba(233,69,96,.2);">
    <svg viewBox="0 0 24 24" fill="#e94560"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
    <div>
      <div class="mode-banner-title" style="color:#e94560;">Créer mon enseigne</div>
      <div class="mode-banner-sub">Vous serez administrateur de votre espace</div>
    </div>
  </div>

  <form method="POST">
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="mode" value="create">

    <div class="section-label">🏢 Votre enseigne</div>
    <div class="field">
      <label>Nom de l'enseigne *</label>
      <input type="text" name="enseigne_nom" value="<?= h($_POST['enseigne_nom'] ?? '') ?>"
             placeholder="Ex: Narmin, MonMarché, SuperStock..." required>
      <?php if (isset($erreurs['enseigne_nom'])): ?><span class="field-error"><?= h($erreurs['enseigne_nom']) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label>Couleur de l'enseigne</label>
      <div class="color-row">
        <input type="color" name="couleur" value="<?= h($_POST['couleur'] ?? '#e94560') ?>" id="colorPicker">
        <span class="color-preview" id="colorPreview">Couleur affichée sur votre interface</span>
      </div>
    </div>

    <div class="section-sep"></div>
    <div class="section-label">👤 Votre compte administrateur</div>

    <div class="form-row">
      <div class="field">
        <label>Prénom *</label>
        <input type="text" name="prenom" value="<?= h($_POST['prenom'] ?? '') ?>" placeholder="Marie" required>
        <?php if (isset($erreurs['prenom'])): ?><span class="field-error"><?= h($erreurs['prenom']) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label>Nom *</label>
        <input type="text" name="nom" value="<?= h($_POST['nom'] ?? '') ?>" placeholder="Lambert" required>
        <?php if (isset($erreurs['nom'])): ?><span class="field-error"><?= h($erreurs['nom']) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="field">
      <label>Email *</label>
      <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" placeholder="admin@narmin.fr" required>
      <?php if (isset($erreurs['email'])): ?><span class="field-error"><?= h($erreurs['email']) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label>Mot de passe * (6 min)</label>
      <input type="password" name="password" placeholder="••••••••" required>
      <?php if (isset($erreurs['password'])): ?><span class="field-error"><?= h($erreurs['password']) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label>Confirmer *</label>
      <input type="password" name="password2" placeholder="••••••••" required>
      <?php if (isset($erreurs['password2'])): ?><span class="field-error"><?= h($erreurs['password2']) ?></span><?php endif; ?>
    </div>

    <button type="submit">🚀 Créer mon enseigne et mon compte</button>
  </form>
  <p class="hint">Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>

<?php elseif ($mode === 'join'): ?>
<!-- ═══ ÉTAPE 2B : REJOINDRE ═══ -->
<div class="card">
  <a href="register.php" class="back-btn">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    Retour
  </a>

  <div class="mode-banner" style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);">
    <svg viewBox="0 0 24 24" fill="#22c55e"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7"/></svg>
    <div>
      <div class="mode-banner-title" style="color:#22c55e;">Rejoindre une enseigne</div>
      <div class="mode-banner-sub">Demandez le code à votre administrateur</div>
    </div>
  </div>

  <form method="POST">
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="mode" value="join">

    <div class="section-label">🔑 Code d'invitation</div>
    <div class="field">
      <label>Code d'invitation *</label>
      <input type="text" name="code_invitation" value="<?= h($_POST['code_invitation'] ?? '') ?>"
             placeholder="Ex: NARMIN-2026" style="text-transform:uppercase;letter-spacing:1px;" required>
      <div class="code-hint">
        Ce code est fourni par l'admin de votre enseigne.<br>
        Il se trouve dans <strong>Admin → Gérer les employés</strong>.
      </div>
      <?php if (isset($erreurs['code_invitation'])): ?><span class="field-error"><?= h($erreurs['code_invitation']) ?></span><?php endif; ?>
    </div>

    <div class="section-sep"></div>
    <div class="section-label">👤 Votre compte</div>

    <div class="form-row">
      <div class="field">
        <label>Prénom *</label>
        <input type="text" name="prenom" value="<?= h($_POST['prenom'] ?? '') ?>" placeholder="Thomas" required>
        <?php if (isset($erreurs['prenom'])): ?><span class="field-error"><?= h($erreurs['prenom']) ?></span><?php endif; ?>
      </div>
      <div class="field">
        <label>Nom *</label>
        <input type="text" name="nom" value="<?= h($_POST['nom'] ?? '') ?>" placeholder="Renault" required>
        <?php if (isset($erreurs['nom'])): ?><span class="field-error"><?= h($erreurs['nom']) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="field">
      <label>Email *</label>
      <input type="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" placeholder="thomas@narmin.fr" required>
      <?php if (isset($erreurs['email'])): ?><span class="field-error"><?= h($erreurs['email']) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label>Mot de passe * (6 min)</label>
      <input type="password" name="password" placeholder="••••••••" required>
      <?php if (isset($erreurs['password'])): ?><span class="field-error"><?= h($erreurs['password']) ?></span><?php endif; ?>
    </div>
    <div class="field">
      <label>Confirmer *</label>
      <input type="password" name="password2" placeholder="••••••••" required>
      <?php if (isset($erreurs['password2'])): ?><span class="field-error"><?= h($erreurs['password2']) ?></span><?php endif; ?>
    </div>

    <button type="submit">✅ Rejoindre l'enseigne</button>
  </form>
  <p class="hint">Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>

<?php endif; ?>

</div>
<footer>© <?= date('Y') ?> StockSmart Pro</footer>

<script>
const picker  = document.getElementById('colorPicker');
const preview = document.getElementById('colorPreview');
if (picker && preview) {
  picker.addEventListener('input', () => {
    preview.style.color = picker.value;
    preview.textContent = 'Couleur sélectionnée : ' + picker.value;
  });
}
</script>
</body>
</html>
