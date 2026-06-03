<?php

declare(strict_types=1);

use App\Exceptions\ValidationException;
use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\DeterminacionRepository;
use App\Repositories\ValorReferenciaRepository;
use App\Services\AuditoriaService;
use App\Services\ValorReferenciaService;

require __DIR__ . '/../config/bootstrap.php';

const USUARIO_ID_HARDCODED = 1;

$db = Database::connection();
$repo = new ValorReferenciaRepository($db);
$detRepo = new DeterminacionRepository($db);
$service = new ValorReferenciaService($repo, new AuditoriaService($db));

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';

try {
    if ($method === 'GET' && $accion === 'listar') {
        $detId = isset($_GET['determinacion_id']) ? (int) $_GET['determinacion_id'] : 0;
        if ($detId <= 0) {
            Response::error('Falta query param ?determinacion_id=<int>', 400);
            return;
        }
        Response::success(['rangos' => $service->listar($detId)]);
        return;
    }

    if ($method === 'POST' && $accion === 'crear') {
        $input = leerJsonVr();
        $detId = (int) ($input['determinacion_id'] ?? 0);
        if ($detId <= 0 || $detRepo->findByIdParaAbm($detId) === null) {
            Response::error('Determinacion invalida', 422, ['determinacion_id' => 'No existe la determinacion'], 'VALIDATION');
            return;
        }
        $id = $service->crear($input, USUARIO_ID_HARDCODED);
        Response::success(['id' => $id], 201);
        return;
    }

    if ($method === 'POST' && $accion === 'actualizar') {
        $input = leerJsonVr();
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            Response::error('Falta id', 400);
            return;
        }
        $service->actualizar($id, $input, USUARIO_ID_HARDCODED);
        Response::success(['id' => $id]);
        return;
    }

    if ($method === 'POST' && $accion === 'eliminar') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            Response::error('Falta query param ?id=<int>', 400);
            return;
        }
        $service->eliminar($id, USUARIO_ID_HARDCODED);
        Response::success(['ok' => true]);
        return;
    }

    Response::error("Combinacion invalida: $method ?accion=$accion", 405);
} catch (ValidationException $e) {
    Response::error($e->getMessage(), $e->getCode() ?: 422, $e->getFields(), 'VALIDATION');
}

/**
 * @return array<string,mixed>
 */
function leerJsonVr(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        Response::error('Body invalido (se esperaba JSON): ' . $e->getMessage(), 400);
        exit;
    }
    return is_array($data) ? $data : [];
}
