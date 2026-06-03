<?php

declare(strict_types=1);

use App\Helpers\Database;
use App\Helpers\Response;
use App\Repositories\DeterminacionRepository;
use App\Repositories\NbuDeterminacionRepository;

require __DIR__ . '/../config/bootstrap.php';

$db = Database::connection();
$repo = new DeterminacionRepository($db);
$nbuRepo = new NbuDeterminacionRepository($db);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = $_GET['accion'] ?? '';

if ($method === 'GET') {
    switch ($accion) {
        case '':
            Response::success(['determinaciones' => $repo->findAllActivas()]);
            return;
        case 'abm':
            Response::success(['determinaciones' => $repo->findAllParaAbm()]);
            return;
        case 'areas':
            Response::success(['areas' => $repo->listarAreas()]);
            return;
        case 'obtener':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                Response::error('Falta query param ?id=<int>', 400);
                return;
            }
            $row = $repo->findByIdParaAbm($id);
            if ($row === null) {
                Response::error("Determinacion $id no encontrada", 404);
                return;
            }
            Response::success($row);
            return;
        default:
            Response::error("Accion GET invalida: '$accion'", 400);
            return;
    }
}

if ($method === 'POST' && in_array($accion, ['crear', 'actualizar'], true)) {
    $input = leerJsonDet();

    $id        = isset($input['id']) ? (int) $input['id'] : 0;
    $areaId    = isset($input['area_id']) ? (int) $input['area_id'] : 0;
    $codigo    = trim((string) ($input['codigo'] ?? ''));
    $nombre    = trim((string) ($input['nombre'] ?? ''));
    $corto     = trim((string) ($input['nombre_corto'] ?? ''));
    $unidad    = trim((string) ($input['unidad'] ?? ''));
    $metodo    = trim((string) ($input['metodo'] ?? ''));
    $tipo      = (string) ($input['tipo_resultado'] ?? 'numerico');
    $decimales = isset($input['decimales']) ? (int) $input['decimales'] : 2;
    $precioRaw = $input['precio'] ?? null;
    $nbuRaw    = $input['nbu_unidades'] ?? null;

    $tiposValidos = ['numerico', 'texto', 'seleccion'];

    $errors = [];
    if ($areaId <= 0) {
        $errors['area_id'] = 'Elegi un area';
    }
    if ($codigo === '' || mb_strlen($codigo) > 20) {
        $errors['codigo'] = 'Codigo obligatorio (max 20 caracteres)';
    } elseif ($repo->codigoExiste($codigo, $accion === 'actualizar' ? $id : null)) {
        $errors['codigo'] = 'Ya existe una determinacion con ese codigo';
    }
    if ($nombre === '' || mb_strlen($nombre) > 150) {
        $errors['nombre'] = 'Nombre obligatorio (max 150 caracteres)';
    }
    if (!in_array($tipo, $tiposValidos, true)) {
        $errors['tipo_resultado'] = 'Tipo invalido';
    }
    if ($decimales < 0 || $decimales > 6) {
        $errors['decimales'] = 'Decimales entre 0 y 6';
    }
    if ($precioRaw !== null && $precioRaw !== '' && !is_numeric($precioRaw)) {
        $errors['precio'] = 'Precio debe ser numerico';
    }
    if ($nbuRaw !== null && $nbuRaw !== '' && (!is_numeric($nbuRaw) || (float) $nbuRaw < 0)) {
        $errors['nbu_unidades'] = 'NBU debe ser numerico >= 0';
    }
    if ($accion === 'actualizar' && ($id <= 0 || $repo->findByIdParaAbm($id) === null)) {
        $errors['id'] = "Determinacion $id no encontrada";
    }

    if ($errors !== []) {
        Response::error('Errores de validacion', 422, $errors, 'VALIDATION');
        return;
    }

    $datos = [
        'area_id'        => $areaId,
        'codigo'         => $codigo,
        'nombre'         => $nombre,
        'nombre_corto'   => $corto !== '' ? $corto : null,
        'unidad'         => $unidad,
        'metodo'         => $metodo !== '' ? $metodo : null,
        'tipo_resultado' => $tipo,
        'decimales'      => $decimales,
        'precio'         => ($precioRaw !== null && $precioRaw !== '') ? (float) $precioRaw : null,
        'solo_facturacion' => !empty($input['solo_facturacion']),
    ];

    $db->beginTransaction();
    try {
        if ($accion === 'crear') {
            $id = $repo->crear($datos);
        } else {
            $repo->actualizar($id, $datos);
        }
        if ($nbuRaw !== null && $nbuRaw !== '') {
            $nbuRepo->upsert($id, round((float) $nbuRaw, 2));
        }
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
    ], $accion === 'crear' ? 201 : 200);
    return;
}

Response::error("Combinacion invalida: $method ?accion=$accion", 405);

/**
 * @return array<string,mixed>
 */
function leerJsonDet(): array
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
