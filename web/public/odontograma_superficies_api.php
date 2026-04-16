<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/OdontogramaRepository.php';
require_once dirname(__DIR__) . '/src/Repositories/PacientesRepository.php';

require_auth();

header('Content-Type: application/json; charset=utf-8');

$user = auth_user();
$uid = $user !== null ? (int) ($user['id'] ?? 0) : 0;
$uid = $uid > 0 ? $uid : null;

$pdo = db();
$repo = new OdontogramaRepository($pdo);
$pacRepo = new PacientesRepository($pdo);

$jsonError = static function (string $msg, int $http = 400): void {
    http_response_code($http);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
};

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $nroHC = (int) ($_GET['nrohc'] ?? 0);
    if ($nroHC < 1) {
        $jsonError('Indicá Nro. HC válido.');
    }
    if ($pacRepo->findByNroHC($nroHC) === null) {
        $jsonError('Paciente no encontrado.', 404);
    }
    if (!$repo->tablaSuperficiesExiste()) {
        echo json_encode(['ok' => false, 'error' => 'Mapa de superficies no disponible (ejecutá migration_016).', 'celdas' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $celdas = $repo->listSuperficiesParaMapa($nroHC);
    echo json_encode(['ok' => true, 'celdas' => $celdas], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $jsonError('JSON inválido.');
    }
    $nroHC = (int) ($data['nrohc'] ?? 0);
    if ($nroHC < 1) {
        $jsonError('Indicá Nro. HC válido.');
    }
    if ($pacRepo->findByNroHC($nroHC) === null) {
        $jsonError('Paciente no encontrado.', 404);
    }
    $celdas = $data['celdas'] ?? null;
    if (!is_array($celdas)) {
        $jsonError('Falta el arreglo celdas.');
    }
    try {
        $repo->guardarSuperficiesMapa($nroHC, $celdas, $uid);
    } catch (InvalidArgumentException $e) {
        $jsonError($e->getMessage());
    } catch (Throwable $e) {
        $jsonError('No se pudo guardar.', 500);
    }
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
