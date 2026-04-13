<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/TurnosRepository.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$nroHc = (int) ($_GET['nrohc'] ?? 0);
if ($nroHc < 1) {
    echo json_encode(['ok' => true, 'item' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

$repo = new TurnosRepository(db());
$item = $repo->pacientePorNroHC($nroHc);

echo json_encode(['ok' => true, 'item' => $item], JSON_UNESCAPED_UNICODE);
