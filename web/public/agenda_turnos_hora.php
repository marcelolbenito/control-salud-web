<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/TurnosRepository.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$fecha = trim((string) ($_GET['fecha'] ?? ''));
$doctor = (int) ($_GET['doctor'] ?? 0);
$hora = trim((string) ($_GET['hora'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $doctor < 1 || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$repo = new TurnosRepository(db(), user_clinica_id(auth_user()));
$items = $repo->turnosEnHorario($fecha, $doctor, $hora);

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);

