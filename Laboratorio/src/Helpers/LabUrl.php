<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Prefijo de URL cuando el módulo se monta bajo /laboratorio (APP_BASE_PATH).
 */
final class LabUrl
{
    public static function basePath(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $raw = trim((string) ($_ENV['APP_BASE_PATH'] ?? ''));
        if ($raw === '' || $raw === '/') {
            $base = '';

            return $base;
        }

        $base = '/' . trim($raw, '/');

        return $base;
    }

    /** Nginx sin try_files: rutas como /laboratorio/index.php?r=pedidos/nuevo */
    public static function usesQueryRouter(): bool
    {
        return filter_var($_ENV['LAB_QUERY_ROUTER'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function path(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        $base = self::basePath();

        // CSS/JS: URL directa; en el servidor copiar assets a public/laboratorio/assets/
        if (str_starts_with($path, '/assets/')) {
            return $base . $path;
        }

        if (self::usesQueryRouter()) {
            // Usar /laboratorio/?r=... (no index.php): en Nginx index.php?r=... pierde la query y vuelve al inicio.
            $entry = $base === '' ? '/' : $base . '/';
            if ($path === '/') {
                return $entry;
            }

            $token = str_replace('/', '.', ltrim($path, '/'));

            return $entry . '?r=' . rawurlencode($token);
        }

        return $base . $path;
    }
}
