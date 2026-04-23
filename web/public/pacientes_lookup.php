<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/TurnosRepository.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$q = trim((string) ($_GET['q'] ?? ''));
if ($q === '') {
    echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$repo = new TurnosRepository(db(), user_clinica_id(auth_user()));
$items = $repo->buscarPacientesTurno($q, 12);

echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);

