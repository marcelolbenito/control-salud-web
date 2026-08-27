<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/gesis_whatsapp_helpers.php';
require_auth();

$user = auth_user();
if (!in_array(auth_user_role($user), ['superadmin', 'admin_clinica'], true)) {
    http_response_code(403);
    exit('Sin permiso');
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (!gesis_whatsapp_configured()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Configurá gesis_whatsapp en config.local.php (email y password).');
}

$qr = gesis_whatsapp_qr();
if (!$qr['ok']) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    exit('QR no disponible: ' . $qr['error']);
}

if (gesis_whatsapp_is_connected($qr['status'])) {
    http_response_code(204);
    exit;
}

$b64 = $qr['qr_base64'];
if ($b64 === null) {
    $con = gesis_whatsapp_conectar();
    $b64 = $con['qr_base64'];
}

if ($b64 === null) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Sin QR (estado: ' . ($qr['status'] !== '' ? $qr['status'] : 'desconocido') . '). Reintentá en unos segundos.');
}

$png = base64_decode($b64, true);
if ($png === false || $png === '') {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    exit('QR inválido');
}

header('Content-Type: image/png');
echo $png;
