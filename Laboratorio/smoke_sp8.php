<?php

declare(strict_types=1);

/**
 * Smoke E2E para SP8. Corre contra la BD real `clinica`.
 *
 * Como el service usa beginTransaction() internamente, no podemos envolver
 * todo en una outer transaction (PDO no soporta nested). En su lugar, al
 * terminar limpiamos explicitamente lo que creamos: lab_pagos del lote,
 * pivot, lote, y restauramos estado_seguro del pedido.
 *
 * Ejercita: generar / quitarPedido / cobrar / 409 lote cobrado / PDF + CSV.
 *
 * Uso:
 *   & "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" smoke_sp8.php
 */

require __DIR__ . '/config/bootstrap.php';

use App\Helpers\Database;
use App\Repositories\LoteOsRepository;
use App\Repositories\PagoRepository;
use App\Repositories\PedidoRepository;
use App\Services\AuditoriaService;
use App\Services\FacturacionOsService;
use App\Services\LoteOsCsvRenderer;
use App\Services\LoteOsPdfRenderer;
use App\Helpers\PdfRenderer;

$db = Database::connection();

$loteRepo = new LoteOsRepository($db);
$service = new FacturacionOsService(
    db: $db,
    loteRepo: $loteRepo,
    pedidoRepo: new PedidoRepository($db),
    pagoRepo: new PagoRepository($db),
    auditoria: new AuditoriaService($db),
);
$pdfRenderer = new LoteOsPdfRenderer(
    loteRepo: $loteRepo,
    pdfRenderer: new PdfRenderer(),
    db: $db,
    templatePath: __DIR__ . '/public/views/pdf/lote-os.php',
);
$csvRenderer = new LoteOsCsvRenderer($loteRepo);

$failed = [];
$passed = [];

/** @var int|null $loteId */
$loteId = null;
/** @var array<int,string> $estadoSeguroOriginal */
$estadoSeguroOriginal = [];

try {
    // Buscar 2 pedidos elegibles para el smoke.
    $stmt = $db->query(
        "SELECT id, obra_social_id, estado_seguro FROM lab_pedidos
         WHERE deleted_at IS NULL AND obra_social_id IS NOT NULL
           AND estado_seguro IN ('A','F') AND monto_seguro > 0
           AND id NOT IN (
             SELECT pedido_id FROM lab_lote_pedidos lp
             INNER JOIN lab_lotes_os l ON l.id = lp.lote_id
             WHERE l.estado IN ('abierto','cobrado') AND l.deleted_at IS NULL
           )
         LIMIT 2"
    );
    $pedidos = $stmt->fetchAll();
    if (count($pedidos) < 2) {
        echo "SKIP: no hay 2 pedidos elegibles en BD para correr smoke.\n";
        exit(0);
    }

    $obraSocialId = (int) $pedidos[0]['obra_social_id'];
    $pedidoIds = [(int) $pedidos[0]['id'], (int) $pedidos[1]['id']];
    foreach ($pedidos as $p) {
        $estadoSeguroOriginal[(int) $p['id']] = (string) $p['estado_seguro'];
    }

    // Caso A: generar lote
    $r = $service->generar([
        'obra_social_id' => $obraSocialId,
        'fecha_desde' => '2000-01-01',
        'fecha_hasta' => date('Y-m-d'),
        'pedido_ids' => $pedidoIds,
    ], usuarioId: null);
    $loteId = $r['id'];
    if ($r['cantidad_pedidos'] !== 2 || $r['monto_total'] <= 0) {
        $failed[] = "A: generar devolvio cantidad={$r['cantidad_pedidos']}, monto={$r['monto_total']}";
    } else {
        $passed[] = "A: generar OK ({$r['numero']}, monto={$r['monto_total']})";
    }

    // Caso B: quitar 1 pedido, recalcular
    $service->quitarPedido($loteId, $pedidoIds[1], null);
    $lote = $loteRepo->findById($loteId);
    if ($lote === null || $lote->cantidadPedidos !== 1) {
        $failed[] = "B: tras quitar cantidad={$lote?->cantidadPedidos}";
    } else {
        $passed[] = "B: quitar OK (cantidad=1)";
    }

    // Caso C: cobrar
    $service->cobrar($loteId, [
        'fecha_pago' => date('Y-m-d'),
        'medio_pago' => 'transferencia',
        'referencia' => 'SMOKE-TEST-SP8',
    ], usuarioId: null);
    $lote = $loteRepo->findById($loteId);
    if ($lote === null || $lote->estado !== 'cobrado') {
        $failed[] = "C: estado tras cobrar = {$lote?->estado}";
    } else {
        $passed[] = "C: cobrar OK (estado=cobrado)";
    }
    $stmt = $db->prepare("SELECT COUNT(*) c FROM lab_pagos WHERE lote_id = ?");
    $stmt->execute([$loteId]);
    $cnt = (int) $stmt->fetch()['c'];
    if ($cnt !== 1) {
        $failed[] = "C: cantidad de lab_pagos = $cnt (esperado 1)";
    } else {
        $passed[] = "C: 1 fila en lab_pagos con lote_id";
    }

    // Caso D: intentar quitar pedido del lote ya cobrado -> 409
    try {
        $service->quitarPedido($loteId, $pedidoIds[0], null);
        $failed[] = "D: no lanzo excepcion al quitar de lote cobrado";
    } catch (\App\Exceptions\ValidationException $e) {
        if ($e->getCode() === 409) {
            $passed[] = "D: 409 al quitar de lote cobrado";
        } else {
            $failed[] = "D: codigo {$e->getCode()} (esperado 409)";
        }
    }

    // Caso E: PDF + CSV (size > 0 y MIME basico)
    $bytes = $pdfRenderer->generar($loteId);
    if (strlen($bytes) === 0 || substr($bytes, 0, 4) !== '%PDF') {
        $failed[] = "E-PDF: bytes invalidos";
    } else {
        $passed[] = "E-PDF: " . strlen($bytes) . " bytes, header %PDF OK";
    }
    $csv = $csvRenderer->generar($loteId);
    if (strlen($csv) === 0 || substr($csv, 0, 3) !== "\xEF\xBB\xBF") {
        $failed[] = "E-CSV: bytes invalidos";
    } else {
        $passed[] = "E-CSV: " . strlen($csv) . " bytes, BOM UTF-8 OK";
    }
} catch (Throwable $e) {
    $failed[] = "FATAL: " . $e->getMessage();
}

// Cleanup explicito (siempre, haya pasado lo que sea)
if ($loteId !== null) {
    try {
        $db->prepare("DELETE FROM lab_pagos WHERE lote_id = ?")->execute([$loteId]);
        $db->prepare("DELETE FROM lab_lote_pedidos WHERE lote_id = ?")->execute([$loteId]);
        $db->prepare("DELETE FROM lab_lotes_os WHERE id = ?")->execute([$loteId]);
        foreach ($estadoSeguroOriginal as $pid => $estado) {
            $db->prepare("UPDATE lab_pedidos SET estado_seguro = ? WHERE id = ?")
               ->execute([$estado, $pid]);
        }
        $db->prepare("DELETE FROM lab_auditoria WHERE tabla_afectada = 'lab_lotes_os' AND registro_id = ?")
           ->execute([$loteId]);
    } catch (Throwable $e) {
        $failed[] = "CLEANUP: " . $e->getMessage();
    }
}

echo "\n=== SP8 SMOKE RESULTS ===\n";
foreach ($passed as $p) echo "PASS  $p\n";
foreach ($failed as $f) echo "FAIL  $f\n";
echo "\n" . count($passed) . " pass, " . count($failed) . " fail\n";

exit(count($failed) === 0 ? 0 : 1);
