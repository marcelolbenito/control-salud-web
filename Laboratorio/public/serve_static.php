<?php

declare(strict_types=1);

/**
 * Sirve un archivo bajo Laboratorio/public/ (css, js, imágenes).
 * Necesario cuando las peticiones pasan por el puente web/public/laboratorio/.
 */
function lab_serve_public_file(string $absolutePath): bool
{
    if (!is_file($absolutePath)) {
        return false;
    }

    $real = realpath($absolutePath);
    $base = realpath(__DIR__);
    if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
        return false;
    }

    $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        default => 'application/octet-stream',
    };

    $mtime = filemtime($real);
    if ($mtime !== false) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($real));
    readfile($real);

    return true;
}
