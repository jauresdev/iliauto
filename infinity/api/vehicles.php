<?php
declare(strict_types=1);

/**
 * API publique en LECTURE SEULE : véhicules + coordonnées du garage.
 * Aucune donnée d'administration n'est exposée ici.
 */
require_once __DIR__ . '/../admin/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=60');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$public = [];
foreach (load_vehicles() as $v) {
    $images = array_values(array_filter((array)($v['images'] ?? []), fn($i) => is_string($i) && valid_image_ref($i)));
    $public[] = [
        'id'           => (int)$v['id'],
        'title'        => (string)$v['title'],
        'brand'        => (string)$v['brand'],
        'price'        => (int)$v['price'],
        'year'         => (int)$v['year'],
        'km'           => (string)$v['km'],
        'fuel'         => (string)$v['fuel'],
        'transmission' => (string)$v['transmission'],
        'engine'       => (string)$v['engine'],
        'status'       => isset(STATUSES[$v['status'] ?? '']) ? $v['status'] : 'available',
        'description'  => (string)$v['description'],
        'images'       => $images,
    ];
}

echo json_encode(
    ['vehicles' => $public, 'settings' => load_settings()],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
