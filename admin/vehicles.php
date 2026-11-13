<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    try {
        $removed = with_lock(function () use ($id) {
            $list = json_load(VEHICLES_FILE, []);
            $found = null;
            $rest = [];
            foreach ($list as $v) {
                if (($v['id'] ?? 0) === $id) {
                    $found = $v;
                } else {
                    $rest[] = $v;
                }
            }
            if ($found) {
                save_vehicles($rest);
            }
            return $found;
        });
        if ($removed) {
            foreach (($removed['images'] ?? []) as $img) {
                delete_upload((string)$img);
            }
            flash('success', 'Véhicule supprimé.');
        } else {
            flash('error', 'Véhicule introuvable.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('vehicles.php');
}

$vehicles = load_vehicles();
admin_header('Véhicules', 'vehicles');
?>
<div class="row-between">
  <h1>Gestion du parc automobile</h1>
  <a class="btn btn-primary" href="vehicle-edit.php">+ Ajouter un véhicule</a>
</div>

<div class="table-wrap">
  <table>
    <thead><tr><th>Photo</th><th>Marque &amp; modèle</th><th>Année</th><th>Prix</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($vehicles as $v): ?>
      <tr>
        <td><img class="thumb" src="<?= h(admin_img((string)($v['images'][0] ?? ''))) ?>" alt=""></td>
        <td><strong><?= h($v['title']) ?></strong></td>
        <td><?= h($v['year']) ?></td>
        <td><?= h(format_price((int)$v['price'])) ?></td>
        <td><?= status_badge((string)$v['status']) ?></td>
        <td>
          <div class="actions">
            <a class="btn btn-small" href="vehicle-edit.php?id=<?= (int)$v['id'] ?>">Modifier</a>
            <form method="post" data-confirm="Supprimer définitivement ce véhicule et ses photos ?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
              <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$vehicles): ?>
      <tr><td colspan="6" class="muted">Aucun véhicule. Cliquez sur « Ajouter un véhicule ».</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
