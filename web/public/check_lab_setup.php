<?php

declare(strict_types=1);

/**
 * Verificación paso a paso del laboratorio en el servidor.
 * Abrir: https://tu-dominio/check_lab_setup.php
 * BORRAR este archivo cuando todo esté en verde.
 */
header('Content-Type: text/html; charset=utf-8');

$publicDir = __DIR__;
$bridgeDir = $publicDir . '/laboratorio';

function ok(bool $v): string
{
    return $v ? '<span style="color:green">OK</span>' : '<span style="color:red">FALTA</span>';
}

$checks = [];

$bridgeFiles = ['index.php', 'resolve_lab.php', 'health.php', 'api.php', 'api-ping.php', 'probe.php'];
foreach ($bridgeFiles as $f) {
    $checks[] = ['Puente: laboratorio/' . $f, is_file($bridgeDir . '/' . $f)];
}

$checks[] = ['Puente: laboratorio/assets/css/app.css', is_file($bridgeDir . '/assets/css/app.css')];

$labEntry = null;
$labRoot = null;
if (is_file($bridgeDir . '/resolve_lab.php')) {
    require $bridgeDir . '/resolve_lab.php';
    $resolved = laboratorio_resolve_entry();
    $labEntry = $resolved['entry'];
    $labRoot = $labEntry !== null ? dirname(dirname($labEntry)) : null;
}

$checks[] = ['Módulo: Laboratorio/public/index.php', $labEntry !== null];
$checks[] = ['Módulo: vendor/autoload.php', $labRoot !== null && is_file($labRoot . '/vendor/autoload.php')];
$checks[] = ['Módulo: .env', $labRoot !== null && is_file($labRoot . '/.env')];

$queryRouter = false;
$basePath = '';
$dbHost = '';
if ($labRoot !== null && is_file($labRoot . '/.env')) {
    $envLines = @file($labRoot . '/.env', FILE_IGNORE_NEW_LINES) ?: [];
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, 'LAB_QUERY_ROUTER=')) {
            $v = trim(substr($line, strlen('LAB_QUERY_ROUTER=')));
            $queryRouter = in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
        }
        if (str_starts_with($line, 'APP_BASE_PATH=')) {
            $basePath = trim(substr($line, strlen('APP_BASE_PATH=')), " \t\"'");
        }
        if (str_starts_with($line, 'DB_HOST=')) {
            $dbHost = trim(substr($line, strlen('DB_HOST=')), " \t\"'");
        }
    }
}

$checks[] = ['.env → APP_BASE_PATH=/laboratorio', $basePath === '/laboratorio'];
$checks[] = ['.env → DB_HOST no es "mysql" (Docker)', $dbHost !== '' && strtolower($dbHost) !== 'mysql'];

$nginxMode = !$queryRouter ? 'Nginx recomendado (LAB_QUERY_ROUTER=false)' : 'Modo degradado (?r= en URLs)';
$checks[] = ['Modo rutas: ' . $nginxMode, true];

// --- Diagnóstico Control Salud Web (login 500, archivos mal subidos) ---
$appRoot = dirname($publicDir);
$csFiles = [
    'includes/bootstrap.php' => 4254,
    'includes/flash.php' => 334,
    'includes/layout.php' => 11515,
    'includes/recordatorio_helpers.php' => 0,
    'includes/caja_helpers.php' => 0,
    'includes/gesis_whatsapp_helpers.php' => 0,
    'config/config.local.php' => 0,
    'public/login.php' => 2859,
];
$csChecks = [];
foreach ($csFiles as $rel => $expectedBytes) {
    $abs = $appRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $exists = is_file($abs);
    $size = $exists ? (int) filesize($abs) : 0;
    $label = $rel;
    if ($exists && $expectedBytes > 0 && $size < (int) ($expectedBytes * 0.85)) {
        $csChecks[] = [$label . ' (' . $size . ' bytes; esperado ~' . $expectedBytes . ' — ¿subida incompleta?)', false];
    } else {
        $csChecks[] = [$label . ($exists ? ' (' . $size . ' bytes)' : ''), $exists];
    }
}
$csChecks[] = ['función flash_take() disponible', function_exists('flash_take')];
$wrongWebTree = is_dir($appRoot . '/web/public');
$csChecks[] = ['Carpeta web/public/ en servidor (ruta equivocada de deploy)', !$wrongWebTree];

$csDiagError = '';
$csDiagOk = '';
try {
    require_once $appRoot . '/includes/bootstrap.php';
    $pdoCs = db();
    ob_start();
    ?>
<div class="container"><h1>Diag</h1><?= csrf_field() ?></div>
    <?php
    $bodyCs = ob_get_clean();
    if (!is_string($bodyCs)) {
        throw new RuntimeException('login body: ob_get_clean falló');
    }
    ob_start();
    layout_render('Diag CS', $bodyCs, null);
    $htmlCs = ob_get_clean();
    if (!is_string($htmlCs) || strlen($htmlCs) < 2000) {
        throw new RuntimeException('layout_render devolvió solo ' . (is_string($htmlCs) ? strlen($htmlCs) : 0) . ' bytes');
    }
    $csDiagOk = 'bootstrap + db + layout_render OK (' . strlen($htmlCs) . ' bytes)';
} catch (Throwable $e) {
    $csDiagError = $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']';
}
$configLint = '';
$configPath = $appRoot . '/config/config.local.php';
if (is_file($configPath) && function_exists('shell_exec')) {
    $out = @shell_exec('php -l ' . escapeshellarg($configPath) . ' 2>&1');
    if (is_string($out) && $out !== '') {
        $configLint = trim($out);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Checklist Laboratorio</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        h1 { font-size: 1.35rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        td, th { border: 1px solid #ccc; padding: 0.5rem 0.75rem; text-align: left; }
        th { background: #f4f4f4; }
        .steps { background: #f9f9f6; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .warn { background: #fff8e6; border: 1px solid #e6c200; padding: 1rem; border-radius: 8px; }
        .err { background: #fde8e8; border: 1px solid #c00; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .okbox { background: #e8f5e9; border: 1px solid #2e7d32; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        code { background: #eee; padding: 0.1em 0.35em; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Checklist — Laboratorio en el servidor</h1>
    <p>DocumentRoot: <code><?= htmlspecialchars($publicDir, ENT_QUOTES, 'UTF-8') ?></code></p>
    <p>Raíz app CS: <code><?= htmlspecialchars($appRoot, ENT_QUOTES, 'UTF-8') ?></code></p>
    <?php if ($labRoot !== null): ?>
    <p>Módulo: <code><?= htmlspecialchars($labRoot, ENT_QUOTES, 'UTF-8') ?></code></p>
    <?php endif; ?>

    <table>
        <tr><th>Qué</th><th>Estado</th></tr>
        <?php foreach ($checks as [$label, $pass]): ?>
        <tr><td><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></td><td><?= ok($pass) ?></td></tr>
        <?php endforeach; ?>
    </table>

    <h2>Control Salud Web — diagnóstico login</h2>
    <p>Subí los PHP a <code>public/</code> y <code>includes/</code> (no a <code>web/public/</code>).</p>
    <table>
        <tr><th>Archivo / prueba</th><th>Estado</th></tr>
        <?php foreach ($csChecks as [$label, $pass]): ?>
        <tr><td><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></td><td><?= ok($pass) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php if ($configLint !== ''): ?>
    <p><strong>php -l config.local.php:</strong> <code><?= htmlspecialchars($configLint, ENT_QUOTES, 'UTF-8') ?></code></p>
    <?php endif; ?>
    <?php if ($csDiagError !== ''): ?>
    <div class="err"><strong>Error al probar layout (misma causa que login 500):</strong><br><?= htmlspecialchars($csDiagError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif ($csDiagOk !== ''): ?>
    <div class="okbox"><?= htmlspecialchars($csDiagOk, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="warn">
        <strong>Para que funcione como en local</strong>, el hosting debe aplicar el bloque Nginx de
        <code>web/deploy/nginx-laboratorio-produccion.conf</code> (Opción A).
        Sin eso, solo funcionan URLs con archivo físico (<code>api.php</code>, <code>/?r=...</code>).
    </div>

    <div class="steps">
        <h2>En tu PC (antes de subir)</h2>
        <pre><code>.\web\deploy\preparar-lab-servidor.ps1</code></pre>

        <h2>Probar en el navegador</h2>
        <ul>
            <li><a href="/laboratorio/probe.php">laboratorio/probe.php</a> → JSON, <code>lab_module</code> con ruta</li>
            <li><a href="/lab_health.php?deep=1">lab_health.php?deep=1</a> → JSON, <code>db: ok</code></li>
            <li><a href="/laboratorio/api-ping.php">api-ping.php</a> → JSON ping</li>
            <li><a href="/laboratorio/api.php?e=determinaciones">api.php?e=determinaciones</a> → JSON</li>
            <li><a href="/laboratorio/api.php?e=pacientes&amp;accion=buscar&amp;q=a&amp;limite=5">api.php pacientes</a> → JSON</li>
            <li><a href="/laboratorio/pedidos/nuevo"><strong>/laboratorio/pedidos/nuevo</strong></a> → pantalla del lab (si muestra login de CS, falta Nginx)</li>
            <li><a href="/laboratorio/assets/css/app.css">CSS</a> → texto CSS, no HTML</li>
        </ul>

        <h2>Guía completa</h2>
        <p>Ver en el repo: <code>web/deploy/DESPLIEGUE_LABORATORIO.md</code></p>
    </div>

    <?php if ($labEntry === null): ?>
    <p>El módulo no se detectó. Copiá <code>lab_path.local.example.php</code> → <code>lab_path.local.php</code> con la ruta absoluta a <code>Laboratorio/public/index.php</code> (ej. <code>/var/www/php8/clinica/Laboratorio/public/index.php</code>).</p>
    <?php endif; ?>
</body>
</html>
