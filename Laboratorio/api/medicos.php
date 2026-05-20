<?php

declare(strict_types=1);

use App\Controllers\MedicoController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\MedicoRepository;
use App\Services\MedicoService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$controller = new MedicoController(new MedicoService(new MedicoRepository($db)));

$accion = $_GET['accion'] ?? 'buscar';

switch ($accion) {
    case 'buscar':
        $controller->buscar($_GET);
        break;

    case 'especialidades':
        $controller->especialidades();
        break;

    case 'obtener':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            Response::error('Falta query param ?id=<int>', 400);
            break;
        }
        $controller->obtener($id);
        break;

    default:
        Response::error("Accion invalida: '$accion'", 400);
}
