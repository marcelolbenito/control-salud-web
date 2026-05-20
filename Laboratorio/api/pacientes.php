<?php

declare(strict_types=1);

use App\Controllers\PacienteController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\PacienteRepository;
use App\Services\PacienteService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$service = new PacienteService(
    pacienteRepo: new PacienteRepository($db),
);
$controller = new PacienteController($service);

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'buscar':
        $controller->buscar($_GET);
        break;

    case 'obras_sociales':
        $controller->obrasSociales();
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
        Response::error("Accion invalida: '$accion'. Validas: buscar, obras_sociales, obtener", 400);
}
