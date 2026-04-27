<?php

declare(strict_types=1);

/**
 * Devuelve el base path de la app (ej: "/controlsalud" o "").
 */
function base_path(): string
{
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    $cfg = require dirname(__DIR__) . '/config/config.php';
    $raw = trim((string) ($cfg['app']['base_path'] ?? ''));
    if ($raw === '' || $raw === '/') {
        $basePath = '';

        return $basePath;
    }

    $basePath = '/' . trim($raw, '/');

    return $basePath;
}

/**
 * Genera una URL absoluta dentro de la app respetando base_path.
 */
function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');

    return base_path() . $path;
}

/**
 * Path actual de request normalizado sin base_path.
 */
function request_path(): string
{
    $path = strtolower((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
    $base = strtolower(base_path());
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = (string) substr($path, strlen($base));
        if ($path === '') {
            $path = '/';
        }
    }

    return $path;
}

/**
 * Prefija URLs root-relative en HTML con base_path para soportar subcarpeta.
 */
function rewrite_html_with_base_path(string $html): string
{
    $base = base_path();
    if ($base === '') {
        return $html;
    }

    if (stripos($html, '<html') === false && stripos($html, '<!doctype html') === false) {
        return $html;
    }

    $patternAttrs = '/\b(href|src|action)\s*=\s*(["\'])\/(?!\/|#)([^"\']*)\2/i';
    $html = (string) preg_replace_callback($patternAttrs, static function (array $m) use ($base): string {
        return $m[1] . '=' . $m[2] . $base . '/' . $m[3] . $m[2];
    }, $html);

    $patternJs = '/\b(location(?:\.href)?)\s*=\s*(["\'])\/(?!\/)([^"\']*)\2/i';
    $html = (string) preg_replace_callback($patternJs, static function (array $m) use ($base): string {
        return $m[1] . '=' . $m[2] . $base . '/' . $m[3] . $m[2];
    }, $html);

    return $html;
}
