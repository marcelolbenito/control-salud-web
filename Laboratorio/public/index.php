<?php

declare(strict_types=1);

use App\Helpers\LabUrl;
use App\Helpers\Response;
use App\Integration\ControlSaludIntegration;

// Bootstrap antes de rutas (autoload, .env, sesión compartida con Control Salud Web).
$bootstrap = require __DIR__ . '/../config/bootstrap.php';
$appConfig = $bootstrap['app'];

require __DIR__ . '/request_path.php';
$path = lab_request_path((string) ($_SERVER['REQUEST_URI'] ?? '/'));
$base = LabUrl::basePath();

// Archivos estáticos bajo public/ (css, js). Con puente FTP no alcanza return false.
require __DIR__ . '/serve_static.php';
$publicFile = __DIR__ . $path;
if ($path !== '/' && str_starts_with($path, '/assets/')) {
    if (lab_serve_public_file($publicFile)) {
        return;
    }
}

// API: /api/<endpoint>
if (preg_match('#^/api/([a-z_-]+)/?$#', $path, $m)) {
    $apiFile = __DIR__ . '/../api/' . $m[1] . '.php';
    if (is_file($apiFile)) {
        require $apiFile;
        return;
    }
    Response::error("Endpoint /api/{$m[1]} no existe", 404);
    return;
}

$viewMap = [
    '/'                    => __DIR__ . '/views/inicio.php',
    '/pacientes'           => __DIR__ . '/views/pacientes/buscar.php',
    '/pedidos'             => __DIR__ . '/views/pedidos/listado.php',
    '/pedidos/ver'         => __DIR__ . '/views/pedidos/ver.php',
    '/pedidos/nuevo'       => __DIR__ . '/views/pedidos/nuevo.php',
    '/resultados/cargar'   => __DIR__ . '/views/resultados/cargar.php',
    '/historial'           => __DIR__ . '/views/historial/index.php',
    '/informes'            => __DIR__ . '/views/informes/index.php',
    '/reportes'            => __DIR__ . '/views/reportes/index.php',
    '/aranceles'           => __DIR__ . '/views/aranceles/index.php',
    '/facturacion-os'      => __DIR__ . '/views/facturacion-os/index.php',
    '/facturacion-os/ver'  => __DIR__ . '/views/facturacion-os/ver.php',
    '/configuracion'       => __DIR__ . '/views/configuracion/index.php',
];

if (isset($viewMap[$path]) && is_file($viewMap[$path])) {
    if (ControlSaludIntegration::enabled() && !isset($_SESSION['user'])) {
        $login = trim((string) ($_ENV['LAB_WEB_LOGIN'] ?? '/login.php'));
        if ($login === '') {
            $login = '/login.php';
        }
        header('Location: ' . $login, true, 302);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    require $viewMap[$path];
    return;
}

if ($path === '/health' || $path === '/api/health') {
    $payload = [
        'module'      => 'laboratorio',
        'version'     => '0.1.0',
        'status'      => 'ok',
        'env'         => $appConfig['env'],
        'integration' => strtolower(trim((string) ($_ENV['LAB_INTEGRATION'] ?? ''))),
        'base_path'   => $base,
    ];
    if (isset($_GET['deep']) && (string) $_GET['deep'] === '1') {
        $payload['checks'] = lab_health_checks();
    }
    Response::success($payload);
    return;
}

/**
 * @return array<string, mixed>
 */
function lab_health_checks(): array
{
    $checks = [
        'php'     => PHP_VERSION,
        'storage' => is_writable(dirname(__DIR__) . '/storage/logs') ? 'ok' : 'no_writable',
    ];
    try {
        $db = \App\Helpers\Database::connection();
        $checks['db'] = 'ok';
        $tables = ['lab_areas', 'lab_determinaciones', 'lab_perfiles', 'lab_pedidos'];
        foreach ($tables as $t) {
            $stmt = $db->query("SHOW TABLES LIKE " . $db->quote($t));
            $checks['table_' . $t] = $stmt->fetch() ? 'ok' : 'missing';
        }
    } catch (\Throwable $e) {
        $checks['db'] = 'error';
        $checks['db_message'] = $e->getMessage();
    }

    return $checks;
}

Response::error('Not found', 404);
