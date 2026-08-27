<?php

declare(strict_types=1);

/** Proxy del QR actual de WAHA (sin caché). */

$cfg = require dirname(__DIR__) . '/config/config.php';
$w = $cfg['whatsapp_web'] ?? [];
$base = rtrim((string) ($w['base_url'] ?? 'http://127.0.0.1:3000'), '/');
$key = (string) ($w['api_key'] ?? '');
$session = (string) ($w['session'] ?? 'default');

$path = '/api/' . rawurlencode($session) . '/auth/qr?format=image';
$ch = curl_init($base . $path);
if ($ch === false) {
    http_response_code(502);
    exit('WAHA no disponible');
}
$headers = [];
if ($key !== '') {
    $headers[] = 'X-Api-Key: ' . $key;
}
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 20,
]);
$img = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!is_string($img) || $code >= 400) {
  require_once dirname(__DIR__) . '/includes/waha_helpers.php';
  $st = waha_session_get();
  $status = is_array($st) ? (string) ($st['status'] ?? '') : '';
  if ($status === 'FAILED') {
    waha_session_restart_for_qr();
    sleep(2);
    $ch2 = curl_init($base . $path);
    curl_setopt_array($ch2, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_TIMEOUT => 20,
    ]);
    $img = curl_exec($ch2);
    $code = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
  }
}

if (!is_string($img) || $code >= 400) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    exit('QR no disponible (estado FAILED). Volvé a waha-vincular.php y tocá Reiniciar.');
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
echo $img;
