<?php

declare(strict_types=1);

use App\Controllers\LabConfigController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\LabConfigRepository;
use App\Services\AuditoriaService;
use App\Services\LabConfigService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$controller = new LabConfigController(
    new LabConfigService(
        new LabConfigRepository($db),
        new AuditoriaService($db),
    )
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';

switch (true) {
    case $method === 'GET':
        $controller->index();
        break;

    case $method === 'POST' && $accion === 'subir_logo':
        if (!isset($_FILES['logo'])) {
            Response::error('Falta archivo logo', 400);
            break;
        }
        /** @var array{tmp_name:string,name:string,error:int} $file */
        $file = $_FILES['logo'];
        $controller->subirLogo($file);
        break;

    case $method === 'POST' && $accion === 'subir_firma':
        if (!isset($_FILES['firma'])) {
            Response::error('Falta archivo firma', 400);
            break;
        }
        /** @var array{tmp_name:string,name:string,error:int} $file */
        $file = $_FILES['firma'];
        $controller->subirFirma($file);
        break;

    case $method === 'POST':
        $raw = file_get_contents('php://input');
        $input = [];
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $input = $decoded;
        }
        $controller->actualizar($input);
        break;

    default:
        Response::error("Combinacion invalida: $method ?accion=$accion", 405);
}
