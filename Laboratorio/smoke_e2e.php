<?php

/**
 * Smoke E2E – Task 21 (SP7)
 *
 * Ejecutar con:
 *   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe smoke_e2e.php
 *
 * Cubre 4 casos:
 *   A – pedido con OS y NBU completo           -> monto_item=300.00, monto_seguro=300.00
 *   B – pedido particular (sin OS)             -> monto_item=0.00 (precio NULL), monto_paciente=0.00
 *   C – pedido con item sin arancel            -> pedido creado, monto_item=0.00, snapshots NULL
 *   D – editar monto manual + bloqueo pagado   -> editarMontoItem OK, luego 409 si estado_seguro='P'
 *
 * Estrategia de limpieza:
 *   PedidoService::crear() abre su propia transaccion internamente.
 *   Por eso NO podemos envolver todo en una transaccion externa.
 *   En cambio, registramos todos los IDs de pedidos creados y los borramos
 *   (DELETE fisico, no soft-delete) al final del script.
 *   Los registros de auditoria generados tambien se limpian.
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

// -----------------------------------------------------------------------
// 1. Conectar a la BD real
// -----------------------------------------------------------------------
$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=clinica;charset=utf8mb4';
$db  = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// -----------------------------------------------------------------------
// 2. Instanciar servicios con deps reales
// -----------------------------------------------------------------------
use App\Repositories\DeterminacionRepository;
use App\Repositories\NbuDeterminacionRepository;
use App\Repositories\NbuValorOsRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
use App\Services\AranceladorService;
use App\Services\AuditoriaService;
use App\Services\PedidoService;
use App\Exceptions\ValidationException;

$pedidoRepo    = new PedidoRepository($db);
$detRepo       = new DeterminacionRepository($db);
$perfilRepo    = new PerfilRepository($db);
$auditoriaServ = new AuditoriaService($db);
$nbuDetRepo    = new NbuDeterminacionRepository($db);
$nbuValOsRepo  = new NbuValorOsRepository($db);
$arancelador   = new AranceladorService($nbuDetRepo, $nbuValOsRepo, $detRepo);

$service = new PedidoService(
    db:               $db,
    pedidoRepo:       $pedidoRepo,
    determinacionRepo: $detRepo,
    perfilRepo:       $perfilRepo,
    auditoria:        $auditoriaServ,
    arancelador:      $arancelador,
);

// -----------------------------------------------------------------------
// 3. Constantes de datos reales
//    - GLU = determinacion_id 11 (lab_nbu_determinaciones: unidades=2.50)
//    - OSDE = obra_social_id 1   (lab_nbu_valores_os: valor_unitario=120.00)
//    - HB  = determinacion_id 1  (sin entrada en lab_nbu_determinaciones)
//    - paciente_id = 1, usuario_id = 1
// -----------------------------------------------------------------------
$GLU_DET_ID  = 11;
$HB_DET_ID   = 1;   // sin NBU -> sinArancel
$OSDE_OS_ID  = 1;
$PACIENTE_ID = 1;
$USUARIO_ID  = 1;

$snapshotBase = [
    'nombre'    => 'Test Smoke E2E SP7',
    'sexo'      => 'M',
    'fecha_nac' => '1990-01-01',
];

// Registro de pedidos creados para limpieza al final
$pedidosCreados = [];

// -----------------------------------------------------------------------
// 4. Helpers de assertion
// -----------------------------------------------------------------------
$pass  = 0;
$fail  = 0;

function assertEq(string $label, string $expected, string $actual): bool
{
    if ($expected === $actual) {
        echo "  [OK]   $label: $actual\n";
        return true;
    }
    echo "  [FAIL] $label — esperado=$expected actual=$actual\n";
    return false;
}

function fmt(mixed $v): string
{
    if ($v === null) {
        return 'NULL';
    }
    return number_format((float) $v, 2, '.', '');
}

// -----------------------------------------------------------------------
// 5. Queries reutilizables
// -----------------------------------------------------------------------
$qItem = $db->prepare(
    'SELECT id, monto_item, nbu_unidades_snapshot, nbu_valor_unit_snapshot
     FROM lab_pedido_items
     WHERE pedido_id = :pid AND deleted_at IS NULL
     ORDER BY id ASC LIMIT 1'
);

$qPed = $db->prepare(
    'SELECT monto_seguro, monto_paciente FROM lab_pedidos WHERE id = :id'
);

// -----------------------------------------------------------------------
// 6. CASO A — pedido con OS y NBU completo
//    GLU (det=11) unidades=2.50, OSDE valor=120.00  => monto=300.00
// -----------------------------------------------------------------------
echo "\n=== CASO A: pedido con OS y NBU completo ===\n";
try {
    $resultA = $service->crear([
        'paciente_id'       => $PACIENTE_ID,
        'obra_social_id'    => $OSDE_OS_ID,
        'prioridad'         => 'rutina',
        'snapshot_paciente' => $snapshotBase,
        'items'             => [['determinacion_id' => $GLU_DET_ID]],
    ], $USUARIO_ID);

    $pedidoIdA = $resultA['id'];
    $pedidosCreados[] = $pedidoIdA;

    $qItem->execute([':pid' => $pedidoIdA]);
    $itemA = $qItem->fetch();
    $qPed->execute([':id' => $pedidoIdA]);
    $pedA = $qPed->fetch();

    $okA = true;
    $okA = assertEq('monto_item',              '300.00', fmt($itemA['monto_item']))              && $okA;
    $okA = assertEq('monto_seguro (pedido)',   '300.00', fmt($pedA['monto_seguro']))             && $okA;
    $okA = assertEq('nbu_unidades_snapshot',   '2.50',   fmt($itemA['nbu_unidades_snapshot']))   && $okA;
    $okA = assertEq('nbu_valor_unit_snapshot', '120.00', fmt($itemA['nbu_valor_unit_snapshot'])) && $okA;

    if ($okA) { $pass++; echo "  => CASO A: PASS\n"; }
    else       { $fail++; echo "  => CASO A: FAIL\n"; }

    $itemIdA = (int) $itemA['id'];

} catch (Throwable $e) {
    $fail++;
    echo "  [EXCEPCION] " . $e::class . ': ' . $e->getMessage() . "\n";
    echo "  => CASO A: FAIL\n";
    $pedidoIdA = null;
    $itemIdA   = null;
}

// -----------------------------------------------------------------------
// 7. CASO B — pedido particular (sin OS)
//    GLU no tiene precio -> monto_item = 0.00, snapshots NULL
// -----------------------------------------------------------------------
echo "\n=== CASO B: pedido particular (sin OS) ===\n";
try {
    $resultB = $service->crear([
        'paciente_id'       => $PACIENTE_ID,
        'prioridad'         => 'rutina',
        'snapshot_paciente' => $snapshotBase,
        'items'             => [['determinacion_id' => $GLU_DET_ID]],
        // sin obra_social_id
    ], $USUARIO_ID);

    $pedidoIdB = $resultB['id'];
    $pedidosCreados[] = $pedidoIdB;

    $qItem->execute([':pid' => $pedidoIdB]);
    $itemB = $qItem->fetch();
    $qPed->execute([':id' => $pedidoIdB]);
    $pedB = $qPed->fetch();

    $okB = true;
    // monto_item = 0 (precio particular es NULL en BD - ninguna det tiene precio cargado)
    $okB = assertEq('monto_item',      '0.00', fmt($itemB['monto_item']))    && $okB;
    $okB = assertEq('monto_paciente',  '0.00', fmt($pedB['monto_paciente'])) && $okB;
    // snapshots deben ser NULL (no aplica NBU para sin-OS)
    $okB = assertEq('nbu_unidades_snapshot = NULL',   'NULL', fmt($itemB['nbu_unidades_snapshot']))   && $okB;
    $okB = assertEq('nbu_valor_unit_snapshot = NULL', 'NULL', fmt($itemB['nbu_valor_unit_snapshot'])) && $okB;

    if ($okB) {
        $pass++;
        echo "  (NOTA: precio=NULL ya que lab_determinaciones no tiene precios cargados — comportamiento correcto)\n";
        echo "  => CASO B: PASS\n";
    } else {
        $fail++;
        echo "  => CASO B: FAIL\n";
    }

} catch (Throwable $e) {
    $fail++;
    echo "  [EXCEPCION] " . $e::class . ': ' . $e->getMessage() . "\n";
    echo "  => CASO B: FAIL\n";
}

// -----------------------------------------------------------------------
// 8. CASO C — pedido con OS pero item sin entrada NBU (HB, det_id=1)
//    => pedido creado sin error, monto_item=0.00, snapshots NULL
// -----------------------------------------------------------------------
echo "\n=== CASO C: pedido con item sin arancel NBU (HB) ===\n";
try {
    $resultC = $service->crear([
        'paciente_id'       => $PACIENTE_ID,
        'obra_social_id'    => $OSDE_OS_ID,
        'prioridad'         => 'rutina',
        'snapshot_paciente' => $snapshotBase,
        'items'             => [['determinacion_id' => $HB_DET_ID]],
    ], $USUARIO_ID);

    $pedidoIdC = $resultC['id'];
    $pedidosCreados[] = $pedidoIdC;

    $qItem->execute([':pid' => $pedidoIdC]);
    $itemC = $qItem->fetch();
    $qPed->execute([':id' => $pedidoIdC]);
    $pedC = $qPed->fetch();

    $okC = true;
    $okC = assertEq('pedido creado (id > 0)',          '1',    $pedidoIdC > 0 ? '1' : '0') && $okC;
    $okC = assertEq('monto_item',                      '0.00', fmt($itemC['monto_item']))   && $okC;
    $okC = assertEq('nbu_unidades_snapshot = NULL',    'NULL', fmt($itemC['nbu_unidades_snapshot']))   && $okC;
    $okC = assertEq('nbu_valor_unit_snapshot = NULL',  'NULL', fmt($itemC['nbu_valor_unit_snapshot'])) && $okC;

    if ($okC) { $pass++; echo "  => CASO C: PASS\n"; }
    else       { $fail++; echo "  => CASO C: FAIL\n"; }

} catch (Throwable $e) {
    $fail++;
    echo "  [EXCEPCION] " . $e::class . ': ' . $e->getMessage() . "\n";
    echo "  => CASO C: FAIL\n";
}

// -----------------------------------------------------------------------
// 9. CASO D — editar monto manual + bloqueo si ya cobrado
//    Requiere que Caso A haya creado un pedido con obra_social_id
// -----------------------------------------------------------------------
echo "\n=== CASO D: editarMontoItem + bloqueo cobrado ===\n";

if (!isset($itemIdA) || $itemIdA === null || !isset($pedidoIdA) || $pedidoIdA === null) {
    $fail++;
    echo "  [SKIP] Caso A no genero pedido — no se puede ejecutar Caso D\n";
    echo "  => CASO D: FAIL (dependencia)\n";
} else {
    try {
        $okD = true;

        // D.1 — editarMontoItem a 500.00
        $totalesD1 = $service->editarMontoItem($itemIdA, 500.0, $USUARIO_ID);
        $okD = assertEq('retorno monto_seguro',   '500.00', fmt($totalesD1['monto_seguro']))   && $okD;
        $okD = assertEq('retorno monto_paciente', '0.00',   fmt($totalesD1['monto_paciente'])) && $okD;

        // Verificar en BD
        $stmtVerify = $db->prepare('SELECT monto_item FROM lab_pedido_items WHERE id = :id');
        $stmtVerify->execute([':id' => $itemIdA]);
        $rowV = $stmtVerify->fetch();
        $okD = assertEq('monto_item en BD tras editar', '500.00', fmt($rowV['monto_item'])) && $okD;

        // D.2 — SET estado_seguro = 'P' -> intento de edicion debe lanzar ValidationException(409)
        $db->prepare("UPDATE lab_pedidos SET estado_seguro = 'P' WHERE id = :id")
           ->execute([':id' => $pedidoIdA]);

        $threw409 = false;
        try {
            $service->editarMontoItem($itemIdA, 600.0, $USUARIO_ID);
            echo "  [FAIL] No lanzo excepcion (esperado ValidationException 409)\n";
        } catch (ValidationException $e) {
            if ($e->getCode() === 409) {
                $threw409 = true;
            } else {
                echo "  [FAIL] ValidationException con code=" . $e->getCode() . " (esperado 409)\n";
            }
        }
        $okD = assertEq('bloqueo 409 al editar cobrado', '1', $threw409 ? '1' : '0') && $okD;

        // D.3 — Restaurar estado_seguro = 'A' (cleanup del test)
        $db->prepare("UPDATE lab_pedidos SET estado_seguro = 'A' WHERE id = :id")
           ->execute([':id' => $pedidoIdA]);

        if ($okD) { $pass++; echo "  => CASO D: PASS\n"; }
        else       { $fail++; echo "  => CASO D: FAIL\n"; }

    } catch (Throwable $e) {
        $fail++;
        echo "  [EXCEPCION] " . $e::class . ': ' . $e->getMessage() . "\n";
        echo "  => CASO D: FAIL\n";
    }
}

// -----------------------------------------------------------------------
// 10. Limpieza — borrar fisicamente los pedidos de prueba
// -----------------------------------------------------------------------
echo "\n--- Limpieza ---\n";
if (!empty($pedidosCreados)) {
    $placeholders = implode(',', array_fill(0, count($pedidosCreados), '?'));

    // Borrar items primero (FK)
    $db->prepare("DELETE FROM lab_pedido_items WHERE pedido_id IN ($placeholders)")
       ->execute($pedidosCreados);

    // Borrar auditoria de esos pedidos (tabla independiente, no FK)
    // (los registros de auditoria para lab_pedidos y lab_pedido_items de estos IDs)
    // La auditoria usa registro_id que coincide con el pedido o item — limpiamos por pedido
    $db->prepare("DELETE FROM lab_auditoria WHERE tabla_afectada = 'lab_pedidos' AND registro_id IN ($placeholders)")
       ->execute($pedidosCreados);

    // Borrar pedidos
    $db->prepare("DELETE FROM lab_pedidos WHERE id IN ($placeholders)")
       ->execute($pedidosCreados);

    echo "Pedidos eliminados: " . implode(', ', $pedidosCreados) . "\n";
    echo "BD restaurada correctamente.\n";
} else {
    echo "No hay pedidos que limpiar.\n";
}

// -----------------------------------------------------------------------
// 11. Resumen final
// -----------------------------------------------------------------------
echo "\n";
echo str_repeat('=', 50) . "\n";
echo "RESULTADO FINAL: $pass PASS, $fail FAIL\n";
echo str_repeat('=', 50) . "\n";

exit($fail > 0 ? 1 : 0);
