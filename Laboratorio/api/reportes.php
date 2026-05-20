<?php

declare(strict_types=1);

use App\Controllers\ReporteController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\ReporteRepository;
use App\Services\ReporteService;

require __DIR__ . '/../config/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    Response::error('Metodo no permitido', 405);
    return;
}

$db = Database::connection();

$controller = new ReporteController(
    new ReporteService(new ReporteRepository($db))
);

$controller->index($_GET);
