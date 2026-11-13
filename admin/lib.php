<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Fonctions communes de l'espace admin IliAuto.
 * Ce fichier ne produit aucune sortie : il est inclus par les pages admin
 * et par l'API publique (lecture seule).
 */
 
const SESSION_IDLE = 1800;        // déconnexion après 30 min d'inactivité
const MAX_ATTEMPTS = 5;           // tentatives de connexion avant blocage
const LOCK_SECONDS = 900;         // durée du blocage (15 min)
const MAX_UPLOAD   = 4 * 1024 * 1024; // 4 Mo par photo
const MAX_IMAGES   = 8;           // photos max par véhicule
const MIN_PASSWORD = 12;
 
define('ROOT_DIR', dirname(__DIR__));
// Idéalement, déplacez "data" HORS du dossier public et changez ce chemin.
define('DATA_DIR', ROOT_DIR . '/data');
define('UPLOAD_DIR', ROOT_DIR . '/uploads');
define('ADMIN_FILE', DATA_DIR . '/admin.json');
define('VEHICLES_FILE', DATA_DIR . '/vehicles.json');
define('SETTINGS_FILE', DATA_DIR . '/settings.json');
define('ATTEMPTS_FILE', DATA_DIR . '/attempts.json');
define('LOCK_FILE', DATA_DIR . '/.lock');
 
const FUELS = ['Essence', 'Diesel', 'Hybride'];
const TRANSMISSIONS = ['Automatique', 'Manuelle'];
const STATUSES = ['available' => 'Disponible', 'reserved' => 'Réservé', 'sold' => 'Vendu'];
const DEFAULT_SETTINGS = [
    'waNumber'     => '22900000000',
    'phoneDisplay' => '+229 00 00 00 00',
    'address'      => 'Parakou, Quartier Zongo, Bénin',
    'hours'        => 'Lun - Sam : 08h00 - 19h00',
];
 
/* ------------------------------------------------------------------ */
/* Utilitaires                                                         */
/* ------------------------------------------------------------------ */
 
function h(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
 
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}
 
function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; "
        . "style-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'none'");
}
 
/* ------------------------------------------------------------------ */
/* Session, messages flash, CSRF                                       */
/* ------------------------------------------------------------------ */
 
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
 
    session_name('iliauto_admin');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $path,
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
 
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['t' => $type, 'm' => $msg];
}
 
function take_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
 
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
 
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}
 
function csrf_check(): void
{
    $sent = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $sent)) {
        http_response_code(419);
        exit('Session expirée ou requête invalide. Retournez en arrière et rechargez la page.');
    }
}
 
function logout_now(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
    session_destroy();
}
 
/** À appeler en tête de TOUTE page réservée à l'administrateur. */
function require_admin(): void
{
    start_secure_session();
    send_security_headers();
    if (!is_file(ADMIN_FILE)) {
        redirect('setup.php');
    }
    if (empty($_SESSION['admin'])) {
        redirect('login.php');
    }
    if (time() - (int)($_SESSION['last'] ?? 0) > SESSION_IDLE) {
        logout_now();
        redirect('login.php?expired=1');
    }
    $_SESSION['last'] = time();
}
 
/* ------------------------------------------------------------------ */
/* Stockage JSON (verrouillé, écriture atomique)                       */
/* ------------------------------------------------------------------ */
 
function json_load(string $file, array $default = []): array
{
    if (!is_file($file)) {
        return $default;
    }
    $fp = fopen($file, 'r');
    if ($fp === false) {
        return $default;
    }
    flock($fp, LOCK_SH);
    $raw = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $raw = (string)$raw;
    // Tolère le caractère invisible (BOM) ajouté par certains éditeurs Windows.
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}
 
function json_save(string $file, array $data): void
{
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $file)) {
        @unlink($tmp);
        throw new RuntimeException("Écriture impossible dans le dossier data/ (vérifiez les droits d'écriture).");
    }
}
 
function with_lock(callable $fn): mixed
{
    $fp = fopen(LOCK_FILE, 'c');
    if ($fp === false) {
        throw new RuntimeException("Impossible de créer le verrou dans data/.");
    }
    flock($fp, LOCK_EX);
    try {
        return $fn();
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
 
/* ------------------------------------------------------------------ */
/* Véhicules et paramètres                                             */
/* ------------------------------------------------------------------ */
 
function load_vehicles(): array
{
    $list = json_load(VEHICLES_FILE, []);
    usort($list, fn($a, $b) => ($b['id'] ?? 0) <=> ($a['id'] ?? 0));
    return array_values($list);
}
 
function find_vehicle(int $id): ?array
{
    foreach (load_vehicles() as $v) {
        if (($v['id'] ?? 0) === $id) {
            return $v;
        }
    }
    return null;
}
 
function save_vehicles(array $list): void
{
    json_save(VEHICLES_FILE, array_values($list));
}
 
function next_vehicle_id(array $list): int
{
    return $list ? (int)max(array_column($list, 'id')) + 1 : 1;
}
 
function load_settings(): array
{
    return array_merge(DEFAULT_SETTINGS, json_load(SETTINGS_FILE, []));
}
 
/* ------------------------------------------------------------------ */
/* Authentification et limitation des tentatives                       */
/* ------------------------------------------------------------------ */
 
function load_admin(): ?array
{
    return is_file(ADMIN_FILE) ? json_load(ADMIN_FILE) : null;
}
 
/** Le fichier du compte contient-il un identifiant et un mot de passe haché valides ? */
function admin_file_valid(?array $admin): bool
{
    return is_array($admin)
        && is_string($admin['user'] ?? null) && $admin['user'] !== ''
        && is_string($admin['hash'] ?? null)
        && (password_get_info($admin['hash'])['algo'] ?? null) !== null;
}
 
function client_key(): string
{
    // REMOTE_ADDR uniquement : les en-têtes X-Forwarded-For sont falsifiables.
    return hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}
 
function lock_remaining(): int
{
    $e = json_load(ATTEMPTS_FILE)[client_key()] ?? null;
    return ($e && ($e['until'] ?? 0) > time()) ? (int)$e['until'] - time() : 0;
}
 
function register_failure(): void
{
    with_lock(function () {
        $all = json_load(ATTEMPTS_FILE);
        $now = time();
        foreach ($all as $k => $e) {
            if (($e['last'] ?? 0) < $now - 86400) {
                unset($all[$k]);
            }
        }
        $k = client_key();
        $e = $all[$k] ?? ['n' => 0, 'until' => 0];
        if (($e['until'] ?? 0) && $e['until'] <= $now) {
            $e = ['n' => 0, 'until' => 0];
        }
        $e['n']++;
        $e['last'] = $now;
        if ($e['n'] >= MAX_ATTEMPTS) {
            $e['until'] = $now + LOCK_SECONDS;
            $e['n'] = 0;
        }
        $all[$k] = $e;
        json_save(ATTEMPTS_FILE, $all);
    });
}
 
function clear_failures(): void
{
    with_lock(function () {
        $all = json_load(ATTEMPTS_FILE);
        unset($all[client_key()]);
        json_save(ATTEMPTS_FILE, $all);
    });
}
 
function password_problem(string $pw, string $user): ?string
{
    if (mb_strlen($pw) < MIN_PASSWORD) {
        return 'Le mot de passe doit contenir au moins ' . MIN_PASSWORD . ' caractères.';
    }
    if (strcasecmp($pw, $user) === 0) {
        return "Le mot de passe ne doit pas être identique à l'identifiant.";
    }
    return null;
}
 
/* ------------------------------------------------------------------ */
/* Images                                                              */
/* ------------------------------------------------------------------ */
 
function valid_image_ref(string $s): bool
{
    return (bool)preg_match('#^(https://[^\s"\'<>]{1,500}|uploads/[a-f0-9]{24}\.(jpg|png|webp))$#', $s);
}
 
/** Chemin d'une image vu depuis le dossier admin/. */
function admin_img(string $s): string
{
    return str_starts_with($s, 'uploads/') ? '../' . $s : $s;
}
 
function delete_upload(string $ref): void
{
    if (preg_match('#^uploads/([a-f0-9]{24}\.(jpg|png|webp))$#', $ref, $m)) {
        @unlink(UPLOAD_DIR . '/' . $m[1]);
    }
}
 
/**
 * Enregistre les photos envoyées. Contrôle : taille, type MIME réel (pas
 * l'extension), image valide, nom aléatoire.
 * @return array{0: string[], 1: string[]} [chemins enregistrés, erreurs]
 */
function store_uploads(array $files, int $room): array
{
    if (empty($files['name'])) {
        return [[], []];
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        return [[], ["Le dossier uploads/ est introuvable et n'a pas pu être créé."]];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $paths = [];
    $errors = [];
    $names = (array)$files['name'];
 
    foreach ($names as $i => $name) {
        $err = $files['error'][$i];
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if (count($paths) >= $room) {
            $errors[] = 'Maximum ' . MAX_IMAGES . ' photos par véhicule : les photos en trop ont été ignorées.';
            break;
        }
        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = "Échec de l'envoi de « $name » (fichier trop gros pour le serveur ?).";
            continue;
        }
        $tmp = $files['tmp_name'][$i];
        if (!is_uploaded_file($tmp) || $files['size'][$i] > MAX_UPLOAD) {
            $errors[] = "« $name » dépasse " . MAX_UPLOAD / 1048576 . ' Mo.';
            continue;
        }
        $mime = $finfo->file($tmp);
        if (!isset($ext[$mime]) || @getimagesize($tmp) === false) {
            $errors[] = "« $name » n'est pas une image JPG, PNG ou WebP valide.";
            continue;
        }
        $file = bin2hex(random_bytes(12)) . '.' . $ext[$mime];
        if (!move_uploaded_file($tmp, UPLOAD_DIR . '/' . $file)) {
            $errors[] = "« $name » n'a pas pu être enregistrée (droits d'écriture sur uploads/ ?).";
            continue;
        }
        $paths[] = 'uploads/' . $file;
    }
    return [$paths, $errors];
}
 
/* ------------------------------------------------------------------ */
/* Validation d'un véhicule                                            */
/* ------------------------------------------------------------------ */
 
/** @return array{0: array, 1: string[]} [données nettoyées, erreurs] */
function validate_vehicle(array $in): array
{
    $errors = [];
    $title = trim((string)($in['title'] ?? ''));
    $brand = trim((string)($in['brand'] ?? ''));
    $km = trim((string)($in['km'] ?? ''));
    $engine = trim((string)($in['engine'] ?? ''));
    $desc = trim((string)($in['description'] ?? ''));
 
    if (mb_strlen($title) < 2 || mb_strlen($title) > 80) {
        $errors[] = 'Le nom du véhicule doit contenir entre 2 et 80 caractères.';
    }
    if ($brand === '') {
        $brand = strtok($title, ' ') ?: '';
    }
    if (mb_strlen($brand) < 2 || mb_strlen($brand) > 40) {
        $errors[] = 'La marque doit contenir entre 2 et 40 caractères.';
    }
    $price = filter_var($in['price'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2000000000]]);
    if ($price === false) {
        $errors[] = 'Le prix doit être un nombre entier (en FCFA).';
    }
    $year = filter_var($in['year'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1980, 'max_range' => (int)date('Y') + 1]]);
    if ($year === false) {
        $errors[] = "L'année n'est pas valide.";
    }
    if (mb_strlen($km) > 20) {
        $errors[] = 'Le kilométrage est trop long (20 caractères max).';
    }
    if (mb_strlen($engine) > 40) {
        $errors[] = 'La motorisation est trop longue (40 caractères max).';
    }
    if (mb_strlen($desc) > 1500) {
        $errors[] = 'La description est trop longue (1 500 caractères max).';
    }
    $fuel = (string)($in['fuel'] ?? '');
    $trans = (string)($in['transmission'] ?? '');
    $status = (string)($in['status'] ?? '');
    if (!in_array($fuel, FUELS, true)) {
        $errors[] = 'Carburant invalide.';
    }
    if (!in_array($trans, TRANSMISSIONS, true)) {
        $errors[] = 'Transmission invalide.';
    }
    if (!isset(STATUSES[$status])) {
        $errors[] = 'Statut invalide.';
    }
 
    $data = [
        'title'        => $title,
        'brand'        => $brand,
        'price'        => $price === false ? 0 : $price,
        'year'         => $year === false ? (int)date('Y') : $year,
        'km'           => $km !== '' ? $km : '—',
        'fuel'         => $fuel,
        'transmission' => $trans,
        'engine'       => $engine,
        'status'       => $status,
        'description'  => $desc !== '' ? $desc : "Véhicule disponible dans le parc automobile d'IliAuto à Parakou.",
    ];
    return [$data, $errors];
}