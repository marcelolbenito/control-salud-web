<?php

declare(strict_types=1);

/**
 * Webhook WAHA → respuestas SI/NO de pacientes.
 * Sin login; WAHA llama desde la red Docker (WHATSAPP_HOOK_URL).
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/db_schema.php';
require_once dirname(__DIR__) . '/includes/recordatorio_helpers.php';
require_once dirname(__DIR__) . '/src/Repositories/RecordatorioRepository.php';
require_once dirname(__DIR__) . '/src/Services/RecordatorioWebhookService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST only']);
    exit;
}

$cfg = require dirname(__DIR__) . '/config/config.php';
$w = $cfg['whatsapp_web'] ?? [];
$expectedKey = (string) ($w['api_key'] ?? '');
if ($expectedKey !== '') {
    $got = (string) ($_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '');
    if ($got !== $expectedKey) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

$raw = file_get_contents('php://input');
$data = json_decode($raw !== false ? $raw : '', true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'JSON inválido']);
    exit;
}

$event = (string) ($data['event'] ?? '');
if ($event !== '' && $event !== 'message') {
    echo json_encode(['ok' => true, 'message' => 'Evento ignorado']);
    exit;
}

$payload = $data['payload'] ?? $data;
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Payload inválido']);
    exit;
}

$pdo = db();
$svc = new RecordatorioWebhookService($pdo, 1);
$result = $svc->procesarMensajeEntrante($payload);

http_response_code($result['ok'] ? 200 : 422);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
