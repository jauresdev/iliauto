<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/_layout.php';
require_admin();

$settings = load_settings();
$errors = [];
$pwErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'settings') {
        $wa = preg_replace('/\D+/', '', (string)($_POST['waNumber'] ?? ''));
        $phone = trim((string)($_POST['phoneDisplay'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $hours = trim((string)($_POST['hours'] ?? ''));

        if (strlen($wa) < 8 || strlen($wa) > 15) {
            $errors[] = "Le numéro WhatsApp doit contenir 8 à 15 chiffres, avec l'indicatif du pays (ex : 22997000000).";
        }
        if ($phone === '' || mb_strlen($phone) > 30) {
            $errors[] = "Le numéro affiché est obligatoire (30 caractères max).";
        }
        if ($address === '' || mb_strlen($address) > 120) {
            $errors[] = "L'adresse est obligatoire (120 caractères max).";
        }
        if ($hours === '' || mb_strlen($hours) > 60) {
            $errors[] = "Les horaires sont obligatoires (60 caractères max).";
        }
        $settings = ['waNumber' => $wa, 'phoneDisplay' => $phone, 'address' => $address, 'hours' => $hours];

        if (!$errors) {
            try {
                with_lock(fn() => json_save(SETTINGS_FILE, $settings));
                flash('success', 'Paramètres enregistrés.');
                redirect('settings.php');
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif ($action === 'password') {
        $admin = load_admin() ?? [];
        $current = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        $confirm = (string)($_POST['confirm'] ?? '');

        if (!password_verify($current, (string)($admin['hash'] ?? ''))) {
            $pwErrors[] = 'Le mot de passe actuel est incorrect.';
        }
        if ($new !== $confirm) {
            $pwErrors[] = 'Les deux nouveaux mots de passe ne correspondent pas.';
        } elseif (($p = password_problem($new, (string)($admin['user'] ?? ''))) !== null) {
            $pwErrors[] = $p;
        }
        if (!$pwErrors) {
            try {
                $admin['hash'] = password_hash($new, PASSWORD_DEFAULT);
                $admin['updated_at'] = date('c');
                with_lock(fn() => json_save(ADMIN_FILE, $admin));
                session_regenerate_id(true);
                flash('success', 'Mot de passe modifié.');
                redirect('settings.php');
            } catch (Throwable $e) {
                $pwErrors[] = $e->getMessage();
            }
        }
    }
}

admin_header('Paramètres', 'settings');
?>
<h1>Paramètres du garage</h1>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error"><?= h($e) ?></div>
<?php endforeach; ?>

<form method="post" class="card narrow">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="settings">
  <h2>Coordonnées affichées sur le site</h2>
  <label class="field">
    <span>Numéro WhatsApp (format international, chiffres seulement)</span>
    <input type="text" name="waNumber" value="<?= h($settings['waNumber']) ?>" required inputmode="numeric" placeholder="22997000000">
  </label>
  <label class="field">
    <span>Numéro affiché</span>
    <input type="text" name="phoneDisplay" value="<?= h($settings['phoneDisplay']) ?>" required maxlength="30">
  </label>
  <label class="field">
    <span>Adresse à Parakou</span>
    <input type="text" name="address" value="<?= h($settings['address']) ?>" required maxlength="120">
  </label>
  <label class="field">
    <span>Horaires d'ouverture</span>
    <input type="text" name="hours" value="<?= h($settings['hours']) ?>" required maxlength="60">
  </label>
  <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
</form>

<?php foreach ($pwErrors as $e): ?>
  <div class="alert alert-error"><?= h($e) ?></div>
<?php endforeach; ?>

<form method="post" class="card narrow" autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <h2>Changer le mot de passe</h2>
  <label class="field">
    <span>Mot de passe actuel</span>
    <input type="password" name="current" required autocomplete="current-password">
  </label>
  <label class="field">
    <span>Nouveau mot de passe (<?= MIN_PASSWORD ?> caractères minimum)</span>
    <input type="password" name="new" required minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password">
  </label>
  <label class="field">
    <span>Confirmer le nouveau mot de passe</span>
    <input type="password" name="confirm" required minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password">
  </label>
  <button type="submit" class="btn btn-primary">Modifier le mot de passe</button>
</form>
<?php admin_footer(); ?>
