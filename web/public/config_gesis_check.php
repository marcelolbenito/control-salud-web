<?php

declare(strict_types=1);

/**
 * Diagnóstico temporal de config Gesis (sin exponer contraseñas).
 * Subir a public/ y abrir en el navegador. BORRAR cuando termine el deploy.
 */
header('Content-Type: application/json; charset=utf-8');

$appRoot = dirname(__DIR__);
$configLocal = $appRoot . '/config/config.local.php';
$configPhp = $appRoot . '/config/config.php';

$out = [
    'ok' => false,
    'time' => date('c'),
    'app_root' => $appRoot,
    'document_root' => (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''),
    'config_local_path' => $configLocal,
    'config_local_exists' => is_file($configLocal),
    'config_local_bytes' => is_file($configLocal) ? (int) filesize($configLocal) : 0,
    'config_local_mtime' => is_file($configLocal) ? date('c', (int) filemtime($configLocal)) : null,
    'raw_contains_gesis_whatsapp' => false,
    'php_array_has_gesis_whatsapp' => false,
    'php_has_email' => false,
    'php_has_password' => false,
    'gesis_whatsapp_configured' => false,
    'recordatorios_modo' => null,
    'php_load_error' => null,
    'config_local_md5' => is_file($configLocal) ? md5_file($configLocal) : null,
    'gesis_local_path' => $appRoot . '/config/gesis.local.php',
    'gesis_local_exists' => is_file($appRoot . '/config/gesis.local.php'),
    'db_error' => null,
];

$raw = '';
if ($out['config_local_exists']) {
    $raw = (string) @file_get_contents($configLocal);
    $out['config_local_md5'] = md5_file($configLocal);
    $out['expected_md5_correct_file'] = 'FDE3430D06D260BDFAD0A9557AE28D53';
    $out['md5_matches_template'] = $out['config_local_md5'] === 'FDE3430D06D260BDFAD0A9557AE28D53';
    $out['raw_contains_email'] = str_contains($raw, 'centrosalud@gmail.com');
    if ($out['raw_contains_gesis_whatsapp'] && preg_match("/'gesis_whatsapp'\\s*=>/m", $raw, $m, PREG_OFFSET_CAPTURE)) {
        $pos = (int) $m[0][1];
        $snippet = substr($raw, max(0, $pos - 40), 220);
        $snippet = preg_replace("/'password'\\s*=>\\s*'[^']*'/", "'password' => '***'", $snippet) ?? $snippet;
        $out['raw_snippet_near_gesis'] = $snippet;
        $before = substr($raw, 0, $pos);
        $out['gesis_after_return_close'] = str_contains($before, '];');
    }
}

if ($out['config_local_exists'] && function_exists('opcache_invalidate')) {
    $out['opcache_invalidated'] = opcache_invalidate($configLocal, true);
}

try {
    $cfgDirect = require $configLocal;
    $out['direct_top_level_keys'] = is_array($cfgDirect) ? array_keys($cfgDirect) : [];
} catch (Throwable $e) {
    $out['direct_load_error'] = $e->getMessage();
}

try {
    $cfg = require $configPhp;
    $out['php_top_level_keys'] = array_keys($cfg);
    $g = $cfg['gesis_whatsapp'] ?? null;
    $out['php_array_has_gesis_whatsapp'] = is_array($g);
    $out['php_has_email'] = is_array($g) && trim((string) ($g['email'] ?? '')) !== '';
    $out['php_has_password'] = is_array($g) && (string) ($g['password'] ?? '') !== '';
    if (is_file($appRoot . '/includes/gesis_whatsapp_helpers.php')) {
        require_once $appRoot . '/includes/gesis_whatsapp_helpers.php';
        $out['gesis_whatsapp_configured'] = gesis_whatsapp_configured();
        $out['gesis_test_only_nro_hc'] = gesis_whatsapp_test_only_nro_hc();
        $out['gesis_from_file'] = is_file($appRoot . '/config/gesis.local.php') ? 'gesis.local.php' : 'config.local.php';
    }
} catch (Throwable $e) {
    $out['php_load_error'] = $e->getMessage();
}

try {
    if (is_file($appRoot . '/config/database.php')) {
        require_once $appRoot . '/config/database.php';
        require_once $appRoot . '/includes/db_schema.php';
        $pdo = db();
        if (is_file($appRoot . '/includes/recordatorio_helpers.php')) {
            require_once $appRoot . '/includes/recordatorio_helpers.php';
            $out['recordatorios_modo'] = recordatorio_modo($pdo, 1);
        }
    }
} catch (Throwable $e) {
    $out['db_error'] = $e->getMessage();
}

$out['ok'] = $out['gesis_whatsapp_configured'] === true;

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
