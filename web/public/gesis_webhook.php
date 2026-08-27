<?php

declare(strict_types=1);

/**
 * Webhook Gesis → respuestas SI/NO y eventos de sesión.
 * URL pública: https://tu-dominio/gesis_webhook.php
 * Registrar en Gesis desde /gesis-vincular.php o bin/gesis_registrar_webhook.php
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/db_schema.php';
require_once dirname(__DIR__) . '/includes/gesis_whatsapp_helpers.php';
require_once dirname(__DIR__) . '/includes/recordatorio_helpers.php';
require_once dirname(__DIR__) . '/src/Repositories/RecordatorioRepository.php';
require_once dirname(__DIR__) . '/src/Services/RecordatorioWebhookService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST only']);
    exit;
}

if (!gesis_whatsapp_webhook_configured()) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Webhook no configurado (webhook_secret y webhook_url en gesis.local.php).']);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    $raw = '';
}

$sig = (string) ($_SERVER['HTTP_X_GESIS_SIGNATURE'] ?? '');
if (!gesis_whatsapp_verificar_firma_webhook($raw, $sig)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Firma inválida']);
    exit;
}

$evento = json_decode($raw, true);
if (!is_array($evento)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'JSON inválido']);
    exit;
}

$pdo = db();
$svc = new RecordatorioWebhookService($pdo, 1);
$result = $svc->procesarEventoGesis($evento);

http_response_code($result['ok'] ? 200 : 422);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
