<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
 
start_secure_session();
send_security_headers();
 
// Verrouillé dès qu'un compte administrateur existe.
if (is_file(ADMIN_FILE)) {
    redirect('login.php');
}
 
$errors = [];
$configured = SETUP_CODE !== '' && SETUP_CODE !== '__SETUP_CODE__';
if (!$configured) {
    $errors[] = "Le code d'installation n'est pas défini dans admin/config.php.";
}
$username = '';
 
if ($configured && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');
    $code = (string)($_POST['setup_code'] ?? '');
 
    if (lock_remaining() > 0) {
        $errors[] = 'Trop de tentatives. Réessayez plus tard.';
    } elseif (!hash_equals(SETUP_CODE, $code)) {
        register_failure();
        $errors[] = "Code d'installation incorrect.";
    } else {
        if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
            $errors[] = "L'identifiant doit faire 3 à 32 caractères (lettres, chiffres, . _ -).";
        }
        if ($pass !== $confirm) {
            $errors[] = 'Les deux mots de passe ne correspondent pas.';
        } elseif (($p = password_problem($pass, $username)) !== null) {
            $errors[] = $p;
        }
        if (!$errors) {
            try {
                if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0750, true)) {
                    throw new RuntimeException('Dossier data/ introuvable.');
                }
                json_save(ADMIN_FILE, [
                    'user' => $username,
                    'hash' => password_hash($pass, PASSWORD_DEFAULT),
                    'created_at' => date('c'),
                ]);
                @chmod(ADMIN_FILE, 0640);
                flash('success', 'Compte créé. Vous pouvez vous connecter.');
                redirect('login.php');
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
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
<title>Installation · IliAuto Admin</title>
<link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="brand">ILI<span>ADMIN</span></div>
  <h1>Créer le compte administrateur</h1>
  <p class="muted">À faire une seule fois. Cette page se verrouille ensuite automatiquement.</p>
 
  <?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= h($e) ?></div>
  <?php endforeach; ?>
 
  <?php if ($configured): ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label class="field">
      <span>Code d'installation (voir admin/config.php)</span>
      <input type="password" name="setup_code" required autocomplete="off">
    </label>
    <label class="field">
      <span>Identifiant choisi</span>
      <input type="text" name="username" value="<?= h($username) ?>" required autocomplete="username">
    </label>
    <label class="field">
      <span>Mot de passe (<?= MIN_PASSWORD ?> caractères minimum)</span>
      <input type="password" name="password" required minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password">
    </label>
    <label class="field">
      <span>Confirmer le mot de passe</span>
      <input type="password" name="confirm" required minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password">
    </label>
    <button type="submit" class="btn btn-primary btn-block">Créer le compte</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
 