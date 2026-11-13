<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
 
start_secure_session();
send_security_headers();
 
if (!is_file(ADMIN_FILE)) {
    redirect('setup.php');
}
if (!empty($_SESSION['admin'])) {
    redirect('index.php');
}
 
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $wait = lock_remaining();
    if ($wait > 0) {
        $error = 'Trop de tentatives. Réessayez dans ' . (int)ceil($wait / 60) . ' minute(s).';
    } else {
        $admin = load_admin() ?? [];
        $user = trim((string)($_POST['username'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        if (!admin_file_valid($admin)) {
            // Fichier du compte abîmé : inutile de compter une tentative ratée.
            $error = "Le fichier du compte (data/admin.json) est illisible ou incomplet. "
                . "Supprimez-le sur le serveur, puis recréez le compte avec setup.php.";
            $okPass = $okUser = false;
        } else {
            // Toujours vérifier le mot de passe (même durée si l'identifiant est faux).
            $okPass = password_verify($pass, (string)($admin['hash'] ?? ''));
            $okUser = hash_equals(strtolower((string)($admin['user'] ?? '')), strtolower($user));
        }
        if ($okPass && $okUser) {
            clear_failures();
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['last'] = time();
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            redirect('index.php');
        }
        if ($error === '') {
            register_failure();
            $error = 'Identifiant ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Connexion · IliAuto Admin</title>
<link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="brand">ILI<span>ADMIN</span></div>
  <h1>Connexion</h1>
  <p class="muted">Espace réservé à l'administrateur.</p>
 
  <?php foreach (take_flash() as $f): ?>
    <div class="alert alert-<?= h($f['t']) ?>"><?= h($f['m']) ?></div>
  <?php endforeach; ?>
  <?php if (isset($_GET['expired'])): ?>
    <div class="alert alert-error">Session expirée par inactivité. Reconnectez-vous.</div>
  <?php endif; ?>
  <?php if ($error !== ''): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
  <?php endif; ?>
 
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label class="field">
      <span>Identifiant</span>
      <input type="text" name="username" required autofocus autocomplete="username">
    </label>
    <label class="field">
      <span>Mot de passe</span>
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
  </form>
</div>
</body>
</html>