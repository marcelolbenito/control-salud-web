<?php

/**
 * Smoke regression — Step 3 de Task 21
 *
 * Verifica que las rutas principales siguen respondiendo JSON valido
 * usando los servicios directamente (sin HTTP server).
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Repositories\DeterminacionRepository;
use App\Repositories\NbuDeterminacionRepository;
use App\Repositories\NbuValorOsRepository;
use App\Repositories\PedidoRepository;
use App\Repositories\PerfilRepository;
use App\Services\AranceladorService;
use App\Services\AuditoriaService;
use App\Services\PedidoService;

$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=clinica;charset=utf8mb4';
$db  = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

$pass = 0;
$fail = 0;

// -----------------------------------------------------------------------
// Test 1 — PedidoService::buscar() equivalente a GET /api/pedidos?accion=listar
// -----------------------------------------------------------------------
echo "=== Regresion 1: PedidoService::buscar (listar pedidos) ===\n";
try {
    $nbuDetRepo  = new NbuDeterminacionRepository($db);
    $nbuValOsRepo = new NbuValorOsRepository($db);
    $detRepo     = new DeterminacionRepository($db);
    $arancelador = new AranceladorService($nbuDetRepo, $nbuValOsRepo, $detRepo);

    $service = new PedidoService(
        db: $db,
        pedidoRepo: new PedidoRepository($db),
        determinacionRepo: $detRepo,
        perfilRepo: new PerfilRepository($db),
        auditoria: new AuditoriaService($db),
        arancelador: $arancelador,
    );

    $result = $service->buscar([]);
    $isValid = isset($result['pedidos']) && is_array($result['pedidos'])
            && isset($result['total'])   && is_int($result['total'])
            && isset($result['page'])    && is_int($result['page']);

    if ($isValid) {
        $count = count($result['pedidos']);
        echo "  [OK] Retorna array con pedidos=$count, total={$result['total']}, page={$result['page']}\n";
        $pass++;
    } else {
        echo "  [FAIL] Estructura de respuesta invalida: " . json_encode(array_keys($result)) . "\n";
        $fail++;
    }
} catch (Throwable $e) {
    echo "  [FAIL] " . $e::class . ': ' . $e->getMessage() . "\n";
    $fail++;
}

// -----------------------------------------------------------------------
// Test 2 — NbuDeterminacionRepository::listAll() equivalente a GET /api/aranceles?accion=nbu-determinaciones
// -----------------------------------------------------------------------
echo "=== Regresion 2: NbuDeterminacionRepository::listAll (aranceles NBU) ===\n";
try {
    $nbuDetRepo = new NbuDeterminacionRepository($db);
    $rows = $nbuDetRepo->listAll();

    $isValid = is_array($rows) && count($rows) > 0
            && isset($rows[0]['determinacion_id'])
            && isset($rows[0]['codigo'])
            && isset($rows[0]['nombre']);

    if ($isValid) {
        $withUnidades = count(array_filter($rows, fn($r) => $r['unidades'] !== null));
        echo "  [OK] Retorna " . count($rows) . " determinaciones, $withUnidades con NBU cargado\n";
        $pass++;
    } else {
        echo "  [FAIL] Array vacio o estructura incorrecta\n";
        $fail++;
    }
} catch (Throwable $e) {
    echo "  [FAIL] " . $e::class . ': ' . $e->getMessage() . "\n";
    $fail++;
}

// -----------------------------------------------------------------------
// Test 3 — NbuValorOsRepository::listAll() equivalente a GET /api/aranceles?accion=valores-os
// -----------------------------------------------------------------------
echo "=== Regresion 3: NbuValorOsRepository::listAll (valores OS) ===\n";
try {
    $nbuValOsRepo = new NbuValorOsRepository($db);
    $rows = $nbuValOsRepo->listAll();

    $isValid = is_array($rows) && count($rows) > 0
            && isset($rows[0]['obra_social_id'])
            && isset($rows[0]['nombre']);

    if ($isValid) {
        $withValor = count(array_filter($rows, fn($r) => $r['valor_unitario'] !== null));
        echo "  [OK] Retorna " . count($rows) . " obras sociales, $withValor con valor NBU\n";
        $pass++;
    } else {
        echo "  [FAIL] Array vacio o estructura incorrecta\n";
        $fail++;
    }
} catch (Throwable $e) {
    echo "  [FAIL] " . $e::class . ': ' . $e->getMessage() . "\n";
    $fail++;
}

echo "\n";
echo str_repeat('=', 50) . "\n";
echo "REGRESION: $pass PASS, $fail FAIL\n";
echo str_repeat('=', 50) . "\n";

exit($fail > 0 ? 1 : 0);
