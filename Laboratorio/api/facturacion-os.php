<?php

declare(strict_types=1);

use App\Controllers\FacturacionOsController;
use App\Helpers\Database;
use App\Helpers\PdfRenderer;
use App\Helpers\Response;
use App\Repositories\LoteOsRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PedidoRepository;
use App\Services\AuditoriaService;
use App\Services\FacturacionOsService;
use App\Services\LoteOsCsvRenderer;
use App\Services\LoteOsPdfRenderer;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$loteRepo = new LoteOsRepository($db);

$service = new FacturacionOsService(
    db: $db,
    loteRepo: $loteRepo,
    pedidoRepo: new PedidoRepository($db),
    pagoRepo: new PagoRepository($db),
    auditoria: new AuditoriaService($db),
);

$pdfRenderer = new LoteOsPdfRenderer(
    loteRepo: $loteRepo,
    pdfRenderer: new PdfRenderer(),
    db: $db,
    templatePath: __DIR__ . '/../public/views/pdf/lote-os.php',
);

$csvRenderer = new LoteOsCsvRenderer($loteRepo);

$controller = new FacturacionOsController($service, $pdfRenderer, $csvRenderer);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pedidoId = isset($_GET['pedido_id']) ? (int) $_GET['pedido_id'] : 0;
$usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;

switch (true) {
    case $method === 'GET'  && $accion === 'elegibles':
        $controller->elegibles();
        break;
    case $method === 'GET'  && $accion === 'listar':
        $controller->listar();
        break;
    case $method === 'GET'  && $accion === 'ver':
        if ($id <= 0) { Response::error('Falta query param ?id=<int>', 400); break; }
        $controller->ver($id);
        break;
    case $method === 'POST' && $accion === 'generar':
        $controller->generar($usuarioId);
        break;
    case $method === 'POST' && $accion === 'quitar-pedido':
        if ($id <= 0 || $pedidoId <= 0) { Response::error('Falta ?id=<int>&pedido_id=<int>', 400); break; }
        $controller->quitarPedido($id, $pedidoId, $usuarioId);
        break;
    case $method === 'POST' && $accion === 'anular':
        if ($id <= 0) { Response::error('Falta ?id=<int>', 400); break; }
        $controller->anular($id, $usuarioId);
        break;
    case $method === 'POST' && $accion === 'cobrar':
        if ($id <= 0) { Response::error('Falta ?id=<int>', 400); break; }
        $controller->cobrar($id, $usuarioId);
        break;
    case $method === 'GET'  && $accion === 'pdf':
        if ($id <= 0) { Response::error('Falta ?id=<int>', 400); break; }
        $controller->pdf($id);
        break;
    case $method === 'GET'  && $accion === 'csv':
        if ($id <= 0) { Response::error('Falta ?id=<int>', 400); break; }
        $controller->csv($id);
        break;
    case $method === 'GET'  && $accion === 'items-pedido':
        if ($id <= 0 || $pedidoId <= 0) { Response::error('Falta ?id=<int>&pedido_id=<int>', 400); break; }
        $controller->itemsPedido($id, $pedidoId);
        break;
    case $method === 'POST' && $accion === 'excluir-item':
        $itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
        if ($id <= 0 || $itemId <= 0) { Response::error('Falta ?id=<int>&item_id=<int>', 400); break; }
        $controller->excluirItem($id, $itemId, $usuarioId);
        break;
    case $method === 'POST' && $accion === 'incluir-item':
        $itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
        if ($id <= 0 || $itemId <= 0) { Response::error('Falta ?id=<int>&item_id=<int>', 400); break; }
        $controller->incluirItem($id, $itemId, $usuarioId);
        break;
    default:
        Response::error("Combinacion invalida: $method ?accion=$accion", 405);
}
