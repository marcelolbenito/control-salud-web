<?php

declare(strict_types=1);

use App\Controllers\AranceladorController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\NbuDeterminacionRepository;
use App\Repositories\NbuValorOsRepository;
use App\Repositories\PedidoRepository;
use App\Services\AuditoriaService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();

$controller = new AranceladorController(
    nbuDetRepo: new NbuDeterminacionRepository($db),
    nbuOsRepo: new NbuValorOsRepository($db),
    pedidoRepo: new PedidoRepository($db),
    auditoria: new AuditoriaService($db),
);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';
$usuarioId = isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;

switch (true) {
    case $method === 'GET'  && $accion === 'nbu-determinaciones':
        $controller->listarNbuDeterminaciones();
        break;
    case $method === 'POST' && $accion === 'nbu-determinaciones':
        $controller->upsertNbuDeterminacion($usuarioId);
        break;
    case $method === 'GET'  && $accion === 'valores-os':
        $controller->listarValoresOs();
        break;
    case $method === 'POST' && $accion === 'valores-os':
        $controller->crearVigenciaOs($usuarioId);
        break;
    case $method === 'GET'  && $accion === 'vigencias-os':
        $osId = isset($_GET['os_id']) ? (int) $_GET['os_id'] : 0;
        $controller->listarVigencias($osId);
        break;
    default:
        Response::error("Combinacion invalida: $method ?accion=$accion", 405);
}
