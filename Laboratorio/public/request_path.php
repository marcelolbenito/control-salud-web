<?php

declare(strict_types=1);

use App\Helpers\LabUrl;

/**
 * Ruta interna del lab (/pedidos/nuevo, /api/determinaciones).
 * En Nginx, ?r=pedidos/nuevo a veces se trunca en $_GET['r']; se lee QUERY_STRING completo.
 */
function lab_request_path(string $requestUri): string
{
    $fromQuery = lab_route_from_query();
    if ($fromQuery !== null) {
        return $fromQuery;
    }

    $pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
    if ($pathInfo !== '') {
        return '/' . $pathInfo;
    }

    $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
    $base = LabUrl::basePath();
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = substr($path, strlen($base)) ?: '/';
    }

    $path = rtrim($path, '/') ?: '/';
    if ($path === '/index.php') {
        return '/';
    }

    return $path;
}

/**
 * Convierte token de ruta (?r=pedidos.nuevo) a /pedidos/nuevo.
 * Puntos evitan barras en la query (Nginx las suele perder).
 */
function lab_route_token_to_path(string $token): string
{
    $token = trim(rawurldecode($token));
    if ($token === '') {
        return '/';
    }

    if (str_contains($token, '.')) {
        return '/' . str_replace('.', '/', $token);
    }

    return '/' . ltrim($token, '/');
}

function lab_route_from_query(): ?string
{
    $sources = [
        (string) ($_SERVER['QUERY_STRING'] ?? ''),
        (string) ($_SERVER['REDIRECT_QUERY_STRING'] ?? ''),
    ];

    $uriQuery = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    if (is_string($uriQuery) && $uriQuery !== '') {
        $sources[] = $uriQuery;
    }

    foreach ($sources as $qs) {
        if ($qs === '') {
            continue;
        }
        if (preg_match('/(?:^|&)r=([^&]*)/', $qs, $m)) {
            return lab_route_token_to_path($m[1]);
        }
    }

    if (isset($_GET['r']) && is_string($_GET['r']) && trim($_GET['r']) !== '') {
        return lab_route_token_to_path($_GET['r']);
    }

    return null;
}
