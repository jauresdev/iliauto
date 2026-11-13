<?php
declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/_layout.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehicle = $id ? find_vehicle($id) : null;
if ($id && !$vehicle) {
    flash('error', 'Véhicule introuvable.');
    redirect('vehicles.php');
}

$fields = ['title', 'brand', 'price', 'year', 'km', 'engine', 'fuel', 'transmission', 'status', 'description'];
$form = $vehicle ?? [
    'title' => '', 'brand' => '', 'price' => '', 'year' => (int)date('Y'), 'km' => '',
    'fuel' => 'Essence', 'transmission' => 'Automatique', 'engine' => '', 'status' => 'available',
    'description' => '', 'images' => [],
];
if (($form['km'] ?? '') === '—') {
    $form['km'] = '';
}
$existing = $vehicle['images'] ?? [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = 'Les photos envoyées dépassent la taille maximale acceptée par le serveur (post_max_size). Envoyez moins de photos à la fois.';
    } else {
        csrf_check();
        [$data, $errors] = validate_vehicle($_POST);

        // Photos conservées / supprimées / principale
        $remove = array_map('intval', (array)($_POST['remove'] ?? []));
        $keep = [];
        $toDelete = [];
        foreach ($existing as $i => $img) {
            if (in_array($i, $remove, true)) {
                $toDelete[] = $img;
            } else {
                $keep[] = $img;
            }
        }
        if (isset($_POST['main']) && ctype_digit((string)$_POST['main'])) {
            $m = (int)$_POST['main'];
            if (isset($existing[$m]) && !in_array($m, $remove, true)) {
                $keep = array_merge([$existing[$m]], array_values(array_diff($keep, [$existing[$m]])));
            }
        }

        // Photo par adresse web (facultatif)
        $url = trim((string)($_POST['image_url'] ?? ''));
        if ($url !== '') {
            if (!str_starts_with($url, 'https://') || !valid_image_ref($url)) {
                $errors[] = "L'adresse de la photo doit commencer par https:// et ne pas contenir d'espaces.";
            } elseif (count($keep) < MAX_IMAGES) {
                $keep[] = $url;
            }
        }

        $images = $keep;
        if (!$errors) {
            [$uploaded, $upErrors] = store_uploads($_FILES['photos'] ?? [], MAX_IMAGES - count($keep));
            $images = array_merge($keep, $uploaded);
            if (!$images) {
                $errors = array_merge($upErrors, ['Ajoutez au moins une photo du véhicule.']);
            } elseif ($upErrors) {
                flash('error', implode(' ', $upErrors));
            }
        }

        if (!$errors) {
            $data['images'] = array_values($images);
            try {
                with_lock(function () use ($vehicle, $data) {
                    $list = json_load(VEHICLES_FILE, []);
                    if ($vehicle) {
                        foreach ($list as $k => $v) {
                            if (($v['id'] ?? 0) === $vehicle['id']) {
                                $list[$k] = $data + ['id' => $vehicle['id'], 'created_at' => $v['created_at'] ?? date('c')];
                            }
                        }
                    } else {
                        $list[] = $data + ['id' => next_vehicle_id($list), 'created_at' => date('c')];
                    }
                    save_vehicles($list);
                });
                foreach ($toDelete as $img) {
                    delete_upload((string)$img);
                }
                flash('success', $vehicle ? 'Véhicule mis à jour.' : 'Véhicule ajouté.');
                redirect('vehicles.php');
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Réaffichage du formulaire avec les valeurs saisies
        foreach ($fields as $k) {
            if (isset($_POST[$k])) {
                $form[$k] = (string)$_POST[$k];
            }
        }
    }
}

$brands = array_values(array_unique(array_filter(array_column(load_vehicles(), 'brand'))));
sort($brands);
$title = $vehicle ? 'Modifier le véhicule' : 'Ajouter un véhicule';
admin_header($title, 'vehicles');
?>
<div class="row-between">
  <h1><?= h($title) ?></h1>
  <a class="btn" href="vehicles.php">← Retour à la liste</a>
</div>

<?php foreach ($errors as $e): ?>
  <div class="alert alert-error"><?= h($e) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="card form-grid">
  <?= csrf_field() ?>

  <label class="field span-2">
    <span>Marque et modèle</span>
    <input type="text" name="title" value="<?= h($form['title']) ?>" required maxlength="80" placeholder="Ex : Toyota Camry">
  </label>

  <label class="field">
    <span>Marque</span>
    <input type="text" name="brand" value="<?= h($form['brand']) ?>" maxlength="40" list="brands" placeholder="Ex : Toyota (vide = 1er mot du nom)">
    <datalist id="brands">
      <?php foreach ($brands as $b): ?><option value="<?= h($b) ?>"><?php endforeach; ?>
    </datalist>
  </label>

  <label class="field">
    <span>Prix (FCFA)</span>
    <input type="number" name="price" value="<?= h($form['price']) ?>" required min="0" step="1" placeholder="8500000">
  </label>

  <label class="field">
    <span>Année</span>
    <input type="number" name="year" value="<?= h($form['year']) ?>" required min="1980" max="<?= (int)date('Y') + 1 ?>">
  </label>

  <label class="field">
    <span>Kilométrage</span>
    <input type="text" name="km" value="<?= h($form['km']) ?>" maxlength="20" placeholder="Ex : 45 000 km">
  </label>

  <label class="field">
    <span>Motorisation</span>
    <input type="text" name="engine" value="<?= h($form['engine']) ?>" maxlength="40" placeholder="Ex : 2.0L 4-Cylindres">
  </label>

  <label class="field">
    <span>Carburant</span>
    <select name="fuel">
      <?php foreach (FUELS as $f): ?><option <?= $form['fuel'] === $f ? 'selected' : '' ?>><?= h($f) ?></option><?php endforeach; ?>
    </select>
  </label>

  <label class="field">
    <span>Transmission</span>
    <select name="transmission">
      <?php foreach (TRANSMISSIONS as $t): ?><option <?= $form['transmission'] === $t ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?>
    </select>
  </label>

  <label class="field">
    <span>Statut</span>
    <select name="status">
      <?php foreach (STATUSES as $key => $label): ?>
        <option value="<?= h($key) ?>" <?= $form['status'] === $key ? 'selected' : '' ?>><?= h($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label class="field span-2">
    <span>Description</span>
    <textarea name="description" rows="4" maxlength="1500" placeholder="État général, équipements, historique d'entretien..."><?= h($form['description']) ?></textarea>
  </label>

  <?php if ($existing): ?>
  <div class="field span-2">
    <span>Photos actuelles</span>
    <div class="photo-grid">
      <?php foreach ($existing as $i => $img): ?>
        <div class="photo-item">
          <img src="<?= h(admin_img((string)$img)) ?>" alt="">
          <label class="inline"><input type="radio" name="main" value="<?= (int)$i ?>" <?= $i === 0 ? 'checked' : '' ?>> Principale</label>
          <label class="inline"><input type="checkbox" name="remove[]" value="<?= (int)$i ?>"> Supprimer</label>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <label class="field span-2">
    <span>Ajouter des photos (JPG, PNG ou WebP, <?= MAX_UPLOAD / 1048576 ?> Mo max chacune, <?= MAX_IMAGES ?> photos max au total)</span>
    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
  </label>

  <label class="field span-2">
    <span>Ou une photo par adresse web (facultatif, https:// uniquement)</span>
    <input type="url" name="image_url" placeholder="https://...">
  </label>

  <div class="span-2 form-actions">
    <a class="btn" href="vehicles.php">Annuler</a>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
  </div>
</form>
<?php admin_footer(); ?>
