<?php

declare(strict_types=1);

/**
 * Health del laboratorio sin depender de rutas amigables (/laboratorio/health).
 * Borrar en producción cuando ya no haga falta.
 * URL: https://tu-dominio/lab_health.php?deep=1
 */
header('Content-Type: application/json; charset=utf-8');

$resolve = __DIR__ . '/laboratorio/resolve_lab.php';
if (!is_file($resolve)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Falta web/public/laboratorio/resolve_lab.php'], JSON_UNESCAPED_UNICODE);
    exit;
}

require $resolve;
$resolved = laboratorio_resolve_entry();
if ($resolved['entry'] === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No se encontró Laboratorio/public/index.php'], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SERVER['REQUEST_URI'] = '/laboratorio/health' . (isset($_GET['deep']) ? '?deep=1' : '');
if (!isset($_GET['deep'])) {
    $_GET['deep'] = '1';
}

ob_start();
require $resolved['entry'];
$out = ob_get_clean();
if ($out !== '') {
    echo $out;
}
