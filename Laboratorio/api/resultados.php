<?php

declare(strict_types=1);

use App\Controllers\ResultadoController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\PedidoItemRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use App\Repositories\ValorReferenciaRepository;
use App\Services\AuditoriaService;
use App\Services\ResultadoService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();

$service = new ResultadoService(
    db: $db,
    resultadoRepo: new ResultadoRepository($db),
    itemRepo: new PedidoItemRepository($db),
    pedidoRepo: new PedidoRepository($db),
    rangoRepo: new ValorReferenciaRepository($db),
    auditoria: new AuditoriaService($db),
);

$controller = new ResultadoController($service);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pedidoId = isset($_GET['pedido_id']) ? (int) $_GET['pedido_id'] : 0;
$pedidoItemId = isset($_GET['pedido_item_id']) ? (int) $_GET['pedido_item_id'] : 0;

switch ($method) {
    case 'POST':
        $controller->cargar();
        break;

    case 'DELETE':
        if ($pedidoItemId <= 0) {
            Response::error('Falta query param ?pedido_item_id=<int>', 400);
            break;
        }
        $controller->quitar($pedidoItemId);
        break;

    case 'GET':
        if ($pedidoId <= 0) {
            Response::error('Falta query param ?pedido_id=<int>', 400);
            break;
        }
        $controller->listarPorPedido($pedidoId);
        break;

    default:
        Response::error('Metodo no permitido', 405);
}
