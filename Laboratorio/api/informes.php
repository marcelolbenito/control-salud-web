<?php

declare(strict_types=1);

use App\Controllers\InformeController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\InformeRepository;
use App\Repositories\LabConfigRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use App\Services\AuditoriaService;
use App\Services\InformePdfRenderer;
use App\Services\InformeService;
use App\Services\LabConfigService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$storageDir = dirname(__DIR__) . '/storage/informes';

$labConfigService = new LabConfigService(
    new LabConfigRepository($db),
    new AuditoriaService($db),
);

$service = new InformeService(
    db: $db,
    informeRepo: new InformeRepository($db),
    resultadoRepo: new ResultadoRepository($db),
    pedidoRepo: new PedidoRepository($db),
    renderer: new InformePdfRenderer($labConfigService),
    auditoria: new AuditoriaService($db),
    storageDir: $storageDir,
);

$controller = new InformeController($service, $storageDir);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = isset($_GET['accion']) ? (string) $_GET['accion'] : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pedidoId = isset($_GET['pedido_id']) ? (int) $_GET['pedido_id'] : 0;
$download = isset($_GET['download']) && (int) $_GET['download'] === 1;

switch ($method) {
    case 'POST':
        if ($accion === 'marcar-entregado') {
            $controller->marcarEntregado($id);
            break;
        }
        $controller->generar();
        break;

    case 'GET':
        if ($id > 0 && $download) {
            $controller->descargar($id);
            break;
        }
        if ($pedidoId > 0) {
            $controller->listarPorPedido($pedidoId);
            break;
        }
        Response::error('Falta query param ?id=<int>&download=1 o ?pedido_id=<int>', 400);
        break;

    default:
        Response::error('Metodo no permitido', 405);
}
