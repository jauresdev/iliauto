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
$recoveryConfigured = setup_code_configured();

if ($recoveryConfigured && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $wait = lock_remaining();
    if ($wait > 0) {
        $error = 'Trop de tentatives. Réessayez dans ' . (int)ceil($wait / 60) . ' minute(s).';
    } else {
        $code = (string)($_POST['recovery_code'] ?? '');
        if (!hash_equals(SETUP_CODE, $code)) {
            register_failure();
            $error = 'Code de récupération incorrect.';
        } else {
            $admin = load_admin() ?? [];
            if (!is_string($admin['user'] ?? null) || !is_string($admin['hash'] ?? null)) {
                $error = "Le compte administrateur est invalide. Contactez l'hébergeur.";
            } else {
                $password = (string)($_POST['password'] ?? '');
                $confirm = (string)($_POST['confirm'] ?? '');

                if ($password !== $confirm) {
                    $error = 'Les deux mots de passe ne correspondent pas.';
                } elseif (($problem = password_problem($password, $admin['user'])) !== null) {
                    $error = $problem;
                } else {
                    try {
                        $admin['hash'] = password_hash($password, PASSWORD_DEFAULT);
                        $admin['updated_at'] = date('c');
                        with_lock(fn() => json_save(ADMIN_FILE, $admin));
                        clear_failures();
                        flash('success', 'Mot de passe réinitialisé. Vous pouvez vous connecter.');
                        redirect('login.php');
                    } catch (Throwable $e) {
                        $error = $e->getMessage();
                    }
                }
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
<title>Mot de passe oublié · IliAuto Admin</title>
<link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="brand">ILI<span>ADMIN</span></div>
  <h1>Réinitialiser le mot de passe</h1>
  <p class="muted">Saisissez le code secret configuré dans admin/config.php.</p>

  <?php if (!$recoveryConfigured): ?>
    <div class="alert alert-error">La récupération n'est pas configurée. Définissez dans admin/config.php un code secret aléatoire d'au moins 32 caractères, puis rechargez cette page.</div>
  <?php else: ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label class="field">
        <span>Code de récupération</span>
        <input type="password" name="recovery_code" required autocomplete="off">
      </label>
      <label class="field">
        <span>Nouveau mot de passe (<?= MIN_PASSWORD ?> caractères minimum)</span>
        <input type="password" name="password" required minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password">
      </label>
      <label class="field">
        <span>Confirmer le nouveau mot de passe</span>
        <input type="password" name="confirm" required minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password">
      </label>
      <button type="submit" class="btn btn-primary btn-block">Réinitialiser le mot de passe</button>
    </form>
  <?php endif; ?>
  <p class="auth-links"><a href="login.php">Retour à la connexion</a></p>
</div>
</body>
</html>
