<?php

declare(strict_types=1);

use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\DeterminacionRepository;
use App\Repositories\PerfilRepository;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$repo = new PerfilRepository($db);
$detRepo = new DeterminacionRepository($db);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';

if ($method === 'GET') {
    Response::success([
        'perfiles' => $repo->findAllActivosConDeterminaciones(),
    ]);
    return;
}

if ($method === 'POST' && in_array($accion, ['crear', 'actualizar'], true)) {
    $input = leerJson();

    $id        = isset($input['id']) ? (int) $input['id'] : 0;
    $codigo    = trim((string) ($input['codigo'] ?? ''));
    $nombre    = trim((string) ($input['nombre'] ?? ''));
    $descRaw   = trim((string) ($input['descripcion'] ?? ''));
    $descripcion = $descRaw !== '' ? $descRaw : null;
    $detIds    = is_array($input['determinaciones'] ?? null)
        ? array_values(array_unique(array_map('intval', $input['determinaciones'])))
        : [];

    $errors = [];
    if ($codigo === '' || mb_strlen($codigo) > 20) {
        $errors['codigo'] = 'Codigo obligatorio (max 20 caracteres)';
    } elseif ($repo->codigoExiste($codigo, $accion === 'actualizar' ? $id : null)) {
        $errors['codigo'] = 'Ya existe un perfil con ese codigo';
    }
    if ($nombre === '' || mb_strlen($nombre) > 150) {
        $errors['nombre'] = 'Nombre obligatorio (max 150 caracteres)';
    }
    $detIds = array_values(array_filter($detIds, static fn (int $v): bool => $v > 0));
    if ($detIds === []) {
        $errors['determinaciones'] = 'Elegi al menos una determinacion';
    } else {
        $validas = $detRepo->findActivasByIds($detIds);
        $idsValidos = array_map('intval', array_column($validas, 'id'));
        $ausentes = array_diff($detIds, $idsValidos);
        if ($ausentes !== []) {
            $errors['determinaciones'] = 'Determinaciones invalidas: ' . implode(',', $ausentes);
        }
    }
    if ($accion === 'actualizar' && $id <= 0) {
        $errors['id'] = 'id invalido';
    }
    if ($accion === 'actualizar' && $id > 0 && $repo->findById($id) === null) {
        $errors['id'] = "Perfil $id no encontrado";
    }

    if ($errors !== []) {
        Response::error('Errores de validacion', 422, $errors, 'VALIDATION');
        return;
    }

    $db->beginTransaction();
    try {
        if ($accion === 'crear') {
            $id = $repo->crear($codigo, $nombre, $descripcion);
        } else {
            $repo->actualizar($id, $codigo, $nombre, $descripcion);
        }
        $repo->reemplazarDeterminaciones($id, $detIds);
        $db->commit();
    } catch (\Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }

    Response::success([
        'id'     => $id,
        'codigo' => $codigo,
        'nombre' => $nombre,
        'items'  => count($detIds),
    ], $accion === 'crear' ? 201 : 200);
    return;
}

Response::error("Combinacion invalida: $method ?accion=$accion", 405);

/**
 * @return array<string,mixed>
 */
function leerJson(): array
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
