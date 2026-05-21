<?php

declare(strict_types=1);

/**
 * API JSON del laboratorio (Docker/Apache: Alias /laboratorio → public/).
 * GET: /laboratorio/api.php?e=determinaciones
 */
$ep = isset($_GET['e']) ? (string) $_GET['e'] : '';
if (!preg_match('/^[a-z0-9_-]+$/i', $ep)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo '{"success":false,"data":null,"error":{"code":"BAD_REQUEST","message":"Parametro e invalido","fields":{}}}';
    exit;
}

$apiFile = dirname(__DIR__) . '/api/' . $ep . '.php';

if (!is_file($apiFile)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo '{"success":false,"data":null,"error":{"code":"NOT_FOUND","message":"API ' . $ep . ' no existe","fields":{}}}';
    exit;
}

require $apiFile;
