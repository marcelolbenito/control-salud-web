<?php

declare(strict_types=1);

use App\Controllers\HistorialController;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\InformeRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\ResultadoRepository;
use App\Repositories\ValorReferenciaRepository;
use App\Services\HistorialService;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();

$service = new HistorialService(
    pedidoRepo: new PedidoRepository($db),
    resultadoRepo: new ResultadoRepository($db),
    informeRepo: new InformeRepository($db),
    rangoRepo: new ValorReferenciaRepository($db),
);

$controller = new HistorialController($service);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    Response::error('Metodo no permitido', 405);
    return;
}

$pacienteId = isset($_GET['paciente_id']) ? (int) $_GET['paciente_id'] : 0;
$pedidoId = isset($_GET['pedido_id']) ? (int) $_GET['pedido_id'] : 0;
$numero = isset($_GET['numero']) ? trim((string) $_GET['numero']) : '';

if ($numero !== '') {
    $controller->dossierByNumero($numero);
} elseif ($pedidoId > 0) {
    $controller->dossier($pedidoId);
} elseif ($pacienteId > 0) {
    $controller->listarPorPaciente($pacienteId, $_GET);
} else {
    Response::error('Falta query param ?paciente_id=<int>, ?pedido_id=<int> o ?numero=<string>', 400);
}
