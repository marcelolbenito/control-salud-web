<?php

declare(strict_types=1);

use App\Controllers\PdfController;
use App\Helpers\Database;
use App\Helpers\PdfRenderer;
use App\Helpers\Response;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
use App\Services\PlanillaTrabajoService;
use App\Services\PortadaService;
use App\Services\TalonService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$pdfRenderer = new PdfRenderer();
$pedidoRepo  = new PedidoRepository($db);
$perfilRepo  = new PerfilRepository($db);
$tplDir = __DIR__ . '/../public/views/pdf';

$pdfController = new PdfController(
    new PortadaService($pedidoRepo, $pdfRenderer, "$tplDir/portada.php"),
    new TalonService($pedidoRepo, $pdfRenderer, "$tplDir/talon.php"),
    new PlanillaTrabajoService($pedidoRepo, $perfilRepo, $pdfRenderer, "$tplDir/planilla-perfil.php"),
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';

if ($method !== 'POST' || $accion !== 'por_perfil') {
    Response::error('Use POST /api/planillas?accion=por_perfil con body {perfil_id, pedido_ids[]}', 400);
    return;
}

$raw = file_get_contents('php://input');
$body = [];
if ($raw !== false && $raw !== '') {
    try {
        $body = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        Response::error('Body invalido (se esperaba JSON): ' . $e->getMessage(), 400);
        return;
    }
}
if (!is_array($body)) {
    Response::error('Body invalido (se esperaba un objeto JSON)', 400);
    return;
}

$pdfController->planillaPorPerfil($body);
