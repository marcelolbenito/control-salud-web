<?php

declare(strict_types=1);

/**
 * Diagnóstico de enrutado Nginx. Borrar en producción cuando no haga falta.
 * GET /laboratorio/probe.php
 * Si Nginx está bien configurado, /laboratorio/pedidos/nuevo también llega al lab (no a CS).
 */
header('Content-Type: application/json; charset=utf-8');

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$docRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');

$bridgeOk = is_file(__DIR__ . '/index.php') && is_file(__DIR__ . '/api.php');
$assetsOk = is_file(__DIR__ . '/assets/css/app.css');

$labEntry = null;
if (is_file(__DIR__ . '/resolve_lab.php')) {
    require __DIR__ . '/resolve_lab.php';
    $resolved = laboratorio_resolve_entry();
    $labEntry = $resolved['entry'];
}

echo json_encode([
    'ok' => $bridgeOk && $labEntry !== null,
    'probe' => 'laboratorio-bridge',
    'request_uri' => $uri,
    'script_name' => $script,
    'document_root' => $docRoot,
    'bridge' => [
        'index' => $bridgeOk,
        'assets_css' => $assetsOk,
        'lab_module' => $labEntry,
    ],
    'tests' => [
        'open_pedidos_nuevo' => '/laboratorio/pedidos/nuevo',
        'expect' => 'HTML del lab (titulo Nuevo pedido), NO login de Control Salud',
        'open_api_pacientes' => '/laboratorio/api.php?e=pacientes&accion=buscar&q=a&limite=3',
        'expect_api' => 'JSON success',
    ],
    'nginx' => [
        'hint' => 'Si pedidos/nuevo va al login de CS, falta el bloque Nginx en web/deploy/nginx-laboratorio-produccion.conf',
        'config_file' => 'web/deploy/nginx-laboratorio-produccion.conf',
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
