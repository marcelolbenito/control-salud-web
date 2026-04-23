<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/TurnosRepository.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$repo = new TurnosRepository(db(), user_clinica_id(auth_user()));
$repo->deleteById($id);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

