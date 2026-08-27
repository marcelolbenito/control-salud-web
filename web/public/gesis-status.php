<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/gesis_whatsapp_helpers.php';
require_auth();

$user = auth_user();
$rol = auth_user_role($user);
if (!in_array($rol, ['superadmin', 'admin_clinica'], true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sin permiso']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!gesis_whatsapp_configured()) {
    http_response_code(503);
    echo json_encode(['error' => 'gesis_whatsapp no configurado']);
    exit;
}

$estado = gesis_whatsapp_estado();
$status = $estado['status'];
$connected = gesis_whatsapp_is_connected($status);

echo json_encode([
    'ok' => $estado['ok'],
    'status' => $status,
    'connected' => $connected,
    'id_emisor' => $estado['id_emisor'],
    'provider' => $estado['provider'],
    'error' => $estado['error'],
], JSON_UNESCAPED_UNICODE);
