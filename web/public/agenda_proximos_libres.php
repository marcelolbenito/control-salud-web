<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/TurnosRepository.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$doctor = (int) ($_GET['doctor'] ?? 0);
$desde = trim((string) ($_GET['desde'] ?? date('Y-m-d')));
$excludeId = (int) ($_GET['exclude_id'] ?? 0);
$limite = (int) ($_GET['limite'] ?? 5);
$dias = (int) ($_GET['dias'] ?? 30);

if ($doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$repo = new TurnosRepository(db());
$items = $repo->proximosLibres($doctor, $desde, $dias, $limite, $excludeId);

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
