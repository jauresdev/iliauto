<?php
declare(strict_types=1);

function admin_header(string $title, string $active): void
{
    $host = strtolower((string)parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
    $siteUrl = in_array($host, ['localhost', '127.0.0.1'], true)
        ? 'http://127.0.0.1:4173/'
        : '../';
    $nav = [
        'dashboard' => ['index.php', 'Tableau de bord'],
        'vehicles'  => ['vehicles.php', 'Véhicules'],
        'settings'  => ['settings.php', 'Paramètres'],
    ];
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> · IliAuto Admin</title>
<link rel="stylesheet" href="admin.css">
<script src="admin.js" defer></script>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="brand">ILI<span>ADMIN</span></div>
    <nav class="menu">
      <?php foreach ($nav as $key => [$href, $label]): ?>
        <a href="<?= h($href) ?>" class="<?= $key === $active ? 'active' : '' ?>"><?= h($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= h($siteUrl) ?>" target="_blank" rel="noopener">Voir le site ↗</a>
    </nav>
    <form method="post" action="logout.php" class="logout">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-ghost">Se déconnecter</button>
    </form>
  </aside>
  <main class="content">
    <?php foreach (take_flash() as $f): ?>
      <div class="alert alert-<?= h($f['t']) ?>"><?= h($f['m']) ?></div>
    <?php endforeach; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
</body>
</html>
<?php
}

function status_badge(string $status): string
{
    $label = STATUSES[$status] ?? STATUSES['available'];
    $cls = isset(STATUSES[$status]) ? $status : 'available';
    return '<span class="badge badge-' . h($cls) . '">' . h($label) . '</span>';
}

function format_price(int|float $n): string
{
    return number_format((float)$n, 0, ',', ' ') . ' FCFA';
}
