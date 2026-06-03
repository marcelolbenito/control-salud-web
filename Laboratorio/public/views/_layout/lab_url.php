<?php


use App\Helpers\LabUrl;

if (!function_exists('lab_url')) {
    function lab_url(string $path = '/'): string
    {
        return LabUrl::path($path);
    }
}

if (!function_exists('lab_h')) {
    function lab_h(string $path = '/'): string
    {
        return htmlspecialchars(lab_url($path), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lab_base_path')) {
    function lab_base_path(): string
    {
        return LabUrl::basePath();
    }
}

if (!function_exists('lab_uses_query_router')) {
    function lab_uses_query_router(): bool
    {
        return LabUrl::usesQueryRouter();
    }
}

/** Assets siempre URL directa (nunca ?r=). */
if (!function_exists('lab_asset_url')) {
    function lab_asset_url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');

        return lab_base_path() . $path;
    }
}

if (!function_exists('lab_asset_h')) {
    function lab_asset_h(string $path = '/'): string
    {
        return htmlspecialchars(lab_asset_url($path), ENT_QUOTES, 'UTF-8');
    }
}

/** Puente API en hosting Nginx (archivo físico api.php). */
if (!function_exists('lab_api_bridge')) {
    function lab_api_bridge(): string
    {
        return lab_base_path() . '/api.php';
    }
}

if (!function_exists('lab_scripts_version')) {
    function lab_scripts_version(): string
    {
        return '12';
    }
}
