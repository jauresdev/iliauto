<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/_layout.php';
require_admin();

$vehicles = load_vehicles();
$count = ['available' => 0, 'reserved' => 0, 'sold' => 0];
foreach ($vehicles as $v) {
    $s = $v['status'] ?? 'available';
    if (isset($count[$s])) {
        $count[$s]++;
    }
}

admin_header('Tableau de bord', 'dashboard');
?>
<h1>Tableau de bord</h1>

<div class="kpis">
  <div class="kpi"><div class="kpi-label">Total véhicules</div><div class="kpi-val"><?= count($vehicles) ?></div></div>
  <div class="kpi"><div class="kpi-label">Disponibles</div><div class="kpi-val"><?= $count['available'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Réservés</div><div class="kpi-val"><?= $count['reserved'] ?></div></div>
  <div class="kpi"><div class="kpi-label">Vendus</div><div class="kpi-val"><?= $count['sold'] ?></div></div>
</div>

<div class="row-between">
  <h2>Derniers véhicules ajoutés</h2>
  <a class="btn btn-primary" href="vehicle-edit.php">+ Ajouter un véhicule</a>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>Photo</th><th>Modèle</th><th>Prix</th><th>Statut</th></tr></thead>
    <tbody>
    <?php foreach (array_slice($vehicles, 0, 5) as $v): ?>
      <tr>
        <td><img class="thumb" src="<?= h(admin_img((string)($v['images'][0] ?? ''))) ?>" alt=""></td>
        <td><strong><?= h($v['title']) ?></strong></td>
        <td><?= h(format_price((int)$v['price'])) ?></td>
        <td><?= status_badge((string)$v['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$vehicles): ?>
      <tr><td colspan="4" class="muted">Aucun véhicule pour le moment.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
