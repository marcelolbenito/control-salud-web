<?php

declare(strict_types=1);

/**
 * Puente: /laboratorio/ → Laboratorio/public/index.php
 * Ver lab_path.local.example.php si el módulo está en otra ruta del servidor.
 */
require __DIR__ . '/resolve_lab.php';

$resolved = laboratorio_resolve_entry();
$entry = $resolved['entry'];

if ($entry !== null) {
    foreach (
        [
            (string) ($_SERVER['QUERY_STRING'] ?? ''),
            (string) ($_SERVER['REDIRECT_QUERY_STRING'] ?? ''),
            (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY) ?? ''),
        ] as $qs
    ) {
        if ($qs !== '' && preg_match('/(?:^|&)r=([^&]*)/', $qs, $m)) {
            $_GET['r'] = rawurldecode($m[1]);
            break;
        }
    }
    $labPublicDir = dirname($entry);
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    if (preg_match('#/assets/(.+)$#', $uriPath, $m)) {
        require $labPublicDir . '/serve_static.php';
        if (lab_serve_public_file($labPublicDir . '/assets/' . str_replace(['..', '\\'], '', $m[1]))) {
            return;
        }
    }
    require $entry;
    return;
}

http_response_code(500);
header('Content-Type: text/plain; charset=utf-8');
echo "No se encontró Laboratorio/public/index.php.\n\n";
echo "Estructura recomendada en el servidor:\n";
echo "  cuenta/\n";
echo "    public_html/          (contenido de web/public)\n";
echo "      laboratorio/        ← puente (este index.php)\n";
echo "    Laboratorio/          ← módulo COMPLETO (vendor, src, public, .env)\n";
echo "      public/index.php\n\n";
echo "Error frecuente: subir solo web/public/laboratorio/ o solo Laboratorio/public/\n";
echo "dentro de public_html. Hace falta la carpeta Laboratorio/ entera FUERA de public_html.\n\n";
echo "Rutas que probó este script:\n";
foreach ($resolved['tried'] as $p) {
    echo '  - ' . $p . (is_file($p) ? ' [existe pero no es index.php]' : '') . "\n";
}
echo "\nSi Laboratorio está en otro sitio, copiá lab_path.local.example.php → lab_path.local.php\n";
echo "y poné la ruta absoluta a Laboratorio/public/index.php\n";
