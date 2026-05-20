<?php

declare(strict_types=1);

use App\Controllers\PdfController;
use App\Controllers\PedidoController;
use App\Helpers\Database;
use App\Helpers\PdfRenderer;
use App\Helpers\Response;
use App\Repositories\DeterminacionRepository;
use App\Repositories\NbuDeterminacionRepository;
use App\Repositories\NbuValorOsRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
use App\Services\AranceladorService;
use App\Services\AuditoriaService;
use App\Services\PedidoService;
use App\Services\PlanillaTrabajoService;
use App\Services\PortadaService;
use App\Services\TalonService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();

$detRepo = new DeterminacionRepository($db);

$arancelador = new AranceladorService(
    new NbuDeterminacionRepository($db),
    new NbuValorOsRepository($db),
    $detRepo,
);

$service = new PedidoService(
    db: $db,
    pedidoRepo: new PedidoRepository($db),
    determinacionRepo: $detRepo,
    perfilRepo: new PerfilRepository($db),
    auditoria: new AuditoriaService($db),
    arancelador: $arancelador,
);

$controller = new PedidoController($service);

// Sub-proyecto 3: PDFs (portada / talon).
$pdfRenderer = new PdfRenderer();
$pedidoRepo = new PedidoRepository($db);
$perfilRepo = new PerfilRepository($db);
$tplDir = __DIR__ . '/../public/views/pdf';
$pdfController = new PdfController(
    new PortadaService($pedidoRepo, $pdfRenderer, "$tplDir/portada.php"),
    new TalonService($pedidoRepo, $pdfRenderer, "$tplDir/talon.php"),
    new PlanillaTrabajoService($pedidoRepo, $perfilRepo, $pdfRenderer, "$tplDir/planilla-perfil.php"),
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';

// Sub-proyecto 2: nuevas acciones (listar / anular / eliminar) por ?accion=...
// Cuando no hay accion, mantenemos el contrato original (POST = crear, GET id = obtener).

switch (true) {
    case $method === 'GET' && $accion === 'listar':
        $controller->listar($_GET);
        break;

    case $method === 'POST' && $accion === 'anular':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $controller->anular($id);
        break;

    case $method === 'POST' && $accion === 'eliminar':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $controller->eliminar($id);
        break;

    case $method === 'GET' && $accion === 'portada':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) { Response::error('Falta query param ?id=<int>', 400); break; }
        $pdfController->portada($id);
        break;

    case $method === 'GET' && $accion === 'talon':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) { Response::error('Falta query param ?id=<int>', 400); break; }
        $pdfController->talon($id);
        break;

    case $method === 'POST' && $accion === '':
        $controller->crear();
        break;

    case $method === 'GET' && $accion === '':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            Response::error('Falta query param ?id=<int> o ?accion=listar', 400);
            break;
        }
        $controller->obtener($id);
        break;

    default:
        Response::error("Combinacion invalida: $method ?accion=$accion", 405);
}
