<?php

declare(strict_types=1);

use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\DeterminacionRepository;

require __DIR__ . '/../config/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    Response::error('Metodo no permitido', 405);
    return;
}

$repo = new DeterminacionRepository(Database::connection());

Response::success([
    'determinaciones' => $repo->findAllActivas(),
]);
