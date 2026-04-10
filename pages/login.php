<?php
require_once '../config.php';

if (isLoggedIn()) {
    header('Location: ../dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']        ?? '');
    $mdp   = $_POST['mot_de_passe']      ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND actif = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $hash = $user['mot_de_passe'] ?? $user['password'] ?? '';

        if ($user && password_verify($mdp, $hash)) {
            // ✅ LOGIN RÉUSSI
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_prenom'] = $user['prenom'] ?? 'Utilisateur';
            $_SESSION['user_nom']    = trim(($user['prenom'] ?? '') . ' ' . $user['nom']);
            $_SESSION['user_role']   = $user['role'];
            $_SESSION['enseigne_id'] = $user['enseigne_id'] ?? 1;

            // Infos enseigne
            $stmtE = $pdo->prepare("SELECT * FROM enseignes WHERE id = ?");
            $stmtE->execute([$user['enseigne_id'] ?? 1]);
            $ens = $stmtE->fetch();
            if ($ens) {
                $_SESSION['enseigne_nom']  = $ens['nom'];
                $_SESSION['enseigne_logo'] = $ens['logo_url'] ?? '';
            }

            header('Location: ../dashboard.php');
            exit;
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — StockSmart Pro</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    :root { --red:#e94560; --dark:#0d1117; }
    body { font-family:'Inter',sans-serif; background:var(--dark); color:#fff; display:flex; flex-direction:column; min-height:100vh; }

    /* Top bar retour vitrine */
    .topbar { background:#161b22; border-bottom:1px solid rgba(255,255,255,.06); padding:10px 2rem; display:flex; align-items:center; justify-content:space-between; }
    .topbar-logo { font-weight:900; font-size:15px; color:#fff; text-decoration:none; }
    .topbar-logo span { color:var(--red); }
    .topbar-back { font-size:13px; color:rgba(255,255,255,.4); text-decoration:none; }
    .topbar-back:hover { color:rgba(255,255,255,.8); }

    .wrap  { flex:1; display:flex; align-items:center; justify-content:center; padding:30px 16px; }
    .card  { background:#161b22; border:1px solid rgba(255,255,255,.08); border-radius:24px; padding:45px; width:100%; max-width:400px; box-shadow:0 25px 50px rgba(0,0,0,.5); }

    .logo-area { text-align:center; margin-bottom:30px; }
    .logo-sq   { width:48px; height:48px; background:var(--red); border-radius:12px; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
    .logo-sq svg { width:24px; height:24px; fill:#fff; }
    .brand     { font-weight:900; font-size:20px; color:#fff; }
    .brand span { color:var(--red); }

    h2 { font-size:22px; font-weight:700; text-align:center; margin-bottom:6px; }
    .subtitle { color:#8b949e; font-size:13px; text-align:center; margin-bottom:30px; }

    .field { margin-bottom:18px; }
    label  { display:block; font-size:12px; font-weight:700; color:#8b949e; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
    input  { width:100%; padding:14px; border-radius:12px; background:#0d1117; border:1px solid rgba(255,255,255,.1); color:#fff; font-size:15px; font-family:inherit; outline:none; transition:border-color .2s; }
    input:focus { border-color:var(--red); box-shadow:0 0 0 3px rgba(233,69,96,.2); }
    input::placeholder { color:rgba(255,255,255,.25); }

    .error-box { background:rgba(233,69,96,.1); color:#f87171; padding:12px; border-radius:10px; font-size:13px; text-align:center; margin-bottom:18px; border:1px solid rgba(233,69,96,.2); }

    button { width:100%; padding:16px; border-radius:12px; border:none; background:var(--red); color:#fff; font-size:16px; font-weight:800; cursor:pointer; font-family:inherit; transition:all .2s; margin-top:4px; }
    button:hover { opacity:.9; transform:translateY(-2px); box-shadow:0 8px 24px rgba(233,69,96,.4); }

    .hint { text-align:center; margin-top:22px; font-size:13px; color:#8b949e; }
    .hint a { color:var(--red); text-decoration:none; font-weight:700; }
    .hint a:hover { text-decoration:underline; }

    /* Demo credentials */
    .demo-box { background:rgba(99,102,241,.1); border:1px solid rgba(99,102,241,.2); border-radius:10px; padding:12px; margin-top:20px; font-size:12px; color:rgba(255,255,255,.5); text-align:center; }
    .demo-box strong { color:rgba(255,255,255,.8); }

    footer { text-align:center; padding:20px; color:#444; font-size:12px; }
  </style>
</head>
<body>

<div class="topbar">
  <a href="../index.php" class="topbar-logo">StockSmart <span>Pro</span></a>
  <a href="../index.php" class="topbar-back">← Retour à l'accueil</a>
</div>

<div class="wrap">
  <div class="card">
    <div class="logo-area">
      <div class="logo-sq"><svg viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zm-10 7H6v-4h4v4zm8 0h-6v-4h6v4z"/></svg></div>
      <div class="brand">StockSmart <span>Pro</span></div>
    </div>
    <h2>Espace Professionnel</h2>
    <p class="subtitle">Accédez à votre gestionnaire de stock</p>

    <?php if ($erreur): ?>
      <div class="error-box">⚠ <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="field">
        <label>Email professionnel</label>
        <input type="email" name="email" placeholder="nom@enseigne.com" required
               value="<?= isset($_POST['email']) ? h($_POST['email']) : '' ?>">
      </div>
      <div class="field">
        <label>Mot de passe</label>
        <input type="password" name="mot_de_passe" placeholder="••••••••" required>
      </div>
      <button type="submit">Se connecter →</button>
    </form>

    <div class="demo-box">
      <strong>Compte démo admin :</strong><br>
      admin@stocksmart.pro · mot de passe : <strong>password</strong>
    </div>

    <p class="hint">Pas encore membre ? <a href="register.php">Créer un compte</a></p>
  </div>
</div>

<footer>© <?= date('Y') ?> StockSmart Pro · Système de gestion multi-enseignes sécurisé</footer>
</body>
</html>
