<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/TurnosRepository.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$fecha = trim((string) ($_GET['fecha'] ?? ''));
$doctor = (int) ($_GET['doctor'] ?? 0);
$excludeId = (int) ($_GET['exclude_id'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $doctor < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros fecha o profesional inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = db();
$repo = new TurnosRepository($pdo);
$d = $repo->disponibilidadVisual($fecha, $doctor, $excludeId);

echo json_encode(
    [
        'ok' => true,
        'slots' => $d['slots'],
        'occupied' => $d['occupied'],
        'source' => $d['source'],
        'step' => $d['step'],
        'sin_franja_dia' => $d['sin_franja_dia'],
    ],
    JSON_UNESCAPED_UNICODE
);
