<?php

declare(strict_types=1);

/** @return array<string, mixed> */
function gesis_whatsapp_raw_settings(): array
{
    $gesisFile = dirname(__DIR__) . '/config/gesis.local.php';
    if (is_file($gesisFile)) {
        $g = require $gesisFile;
        if (is_array($g)) {
            return $g;
        }
    }

    $cfg = require dirname(__DIR__) . '/config/config.php';
    $g = $cfg['gesis_whatsapp'] ?? [];

    return is_array($g) ? $g : [];
}

/** @return array{base_url:string,email:string,password:string,custom_cuit:string} */
function gesis_whatsapp_config(): array
{
    $g = gesis_whatsapp_raw_settings();

    return [
        'base_url' => rtrim((string) ($g['base_url'] ?? 'https://servicios.gesis2.com'), '/'),
        'email' => trim((string) ($g['email'] ?? '')),
        'password' => (string) ($g['password'] ?? ''),
        'custom_cuit' => trim((string) ($g['custom_cuit'] ?? '')),
    ];
}

function gesis_whatsapp_test_only_nro_hc(): int
{
    $g = gesis_whatsapp_raw_settings();

    return max(0, (int) ($g['test_only_nro_hc'] ?? 0));
}

function gesis_whatsapp_configured(): bool
{
    $g = gesis_whatsapp_config();

    return $g['email'] !== '' && $g['password'] !== '';
}

function gesis_whatsapp_query_suffix(): string
{
    $cuit = gesis_whatsapp_config()['custom_cuit'];
    if ($cuit === '') {
        return '';
    }

    return '?custom_cuit=' . rawurlencode($cuit);
}

function gesis_whatsapp_token(bool $forceRefresh = false): ?string
{
    $g = gesis_whatsapp_config();
    if ($g['email'] === '' || $g['password'] === '') {
        return null;
    }

    $cacheFile = sys_get_temp_dir() . '/gesis_whatsapp_token_' . md5($g['email'] . '|' . $g['base_url']) . '.json';
    if (!$forceRefresh && is_file($cacheFile)) {
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        if (is_array($cached) && (int) ($cached['expires_at'] ?? 0) > time() + 60) {
            return (string) ($cached['token'] ?? '');
        }
    }

    $ch = curl_init($g['base_url'] . '/api/v1/auth/token');
    if ($ch === false) {
        return null;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['email' => $g['email'], 'password' => $g['password']]),
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        return null;
    }
    $data = json_decode($raw, true);
    $token = is_array($data) ? (string) ($data['access_token'] ?? '') : '';
    if ($token === '') {
        return null;
    }

    file_put_contents($cacheFile, json_encode([
        'token' => $token,
        'expires_at' => time() + (25 * 60),
    ]));

    return $token;
}

/** @return array{ok:bool,code:int,body:?array,raw:string} */
function gesis_whatsapp_api(string $method, string $path, ?array $body = null, bool $retry = true): array
{
    $g = gesis_whatsapp_config();
    $token = gesis_whatsapp_token();
    if ($token === null || $token === '') {
        return ['ok' => false, 'code' => 0, 'body' => null, 'raw' => 'Credenciales gesis_whatsapp no configuradas'];
    }

    $suffix = gesis_whatsapp_query_suffix();
    if ($suffix !== '' && strpos($path, '?') === false) {
        $path .= $suffix;
    }

    $ch = curl_init($g['base_url'] . $path);
    if ($ch === false) {
        return ['ok' => false, 'code' => 0, 'body' => null, 'raw' => 'curl init'];
    }

    $headers = ['Accept: application/json', 'Authorization: Bearer ' . $token];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 45,
    ]);
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 401 && $retry) {
        gesis_whatsapp_token(true);

        return gesis_whatsapp_api($method, $path, $body, false);
    }

    $decoded = json_decode($raw, true);

    return [
        'ok' => $code >= 200 && $code < 300,
        'code' => $code,
        'body' => is_array($decoded) ? $decoded : null,
        'raw' => $raw,
    ];
}

/** @return array{ok:bool,status:string,id_emisor:?int,provider:string,error:string} */
function gesis_whatsapp_estado(): array
{
    $r = gesis_whatsapp_api('GET', '/api/v1/whatsapp/emisores/estado');
    if (!$r['ok'] || !is_array($r['body'])) {
        return [
            'ok' => false,
            'status' => '',
            'id_emisor' => null,
            'provider' => '',
            'error' => $r['raw'] !== '' ? $r['raw'] : 'No se pudo consultar estado',
        ];
    }

    return [
        'ok' => true,
        'status' => (string) ($r['body']['status'] ?? ''),
        'id_emisor' => isset($r['body']['id_emisor']) ? (int) $r['body']['id_emisor'] : null,
        'provider' => (string) ($r['body']['provider'] ?? ''),
        'error' => '',
    ];
}

/** @return array{ok:bool,status:string,qr_base64:?string,error:string} */
function gesis_whatsapp_qr(): array
{
    $r = gesis_whatsapp_api('GET', '/api/v1/whatsapp/emisores/qr');
    if (!$r['ok'] || !is_array($r['body'])) {
        return [
            'ok' => false,
            'status' => '',
            'qr_base64' => null,
            'error' => $r['raw'] !== '' ? $r['raw'] : 'No se pudo obtener QR',
        ];
    }

    $b64 = $r['body']['qr_base64'] ?? null;

    return [
        'ok' => true,
        'status' => (string) ($r['body']['status'] ?? ''),
        'qr_base64' => is_string($b64) && $b64 !== '' ? $b64 : null,
        'error' => '',
    ];
}

/** @return array{ok:bool,status:string,qr_base64:?string,error:string} */
function gesis_whatsapp_conectar(): array
{
    $r = gesis_whatsapp_api('POST', '/api/v1/whatsapp/emisores/conectar');
    if (!$r['ok'] || !is_array($r['body'])) {
        return [
            'ok' => false,
            'status' => '',
            'qr_base64' => null,
            'error' => $r['raw'] !== '' ? $r['raw'] : 'No se pudo iniciar conexión',
        ];
    }

    $b64 = $r['body']['qr_base64'] ?? null;

    return [
        'ok' => true,
        'status' => (string) ($r['body']['status'] ?? ''),
        'qr_base64' => is_string($b64) && $b64 !== '' ? $b64 : null,
        'error' => '',
    ];
}

function gesis_whatsapp_is_connected(string $status): bool
{
    return strtolower($status) === 'connected';
}

function gesis_whatsapp_needs_connect(string $status): bool
{
    $s = strtolower($status);

    return $s === '' || in_array($s, ['pending', 'disconnected'], true);
}

function gesis_whatsapp_webhook_secret(): string
{
    $g = gesis_whatsapp_raw_settings();

    return (string) ($g['webhook_secret'] ?? '');
}

function gesis_whatsapp_webhook_url(): string
{
    $g = gesis_whatsapp_raw_settings();
    $explicit = trim((string) ($g['webhook_url'] ?? ''));
    if ($explicit !== '') {
        return $explicit;
    }
    if (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!function_exists('url')) {
            require_once __DIR__ . '/url.php';
        }

        return $scheme . '://' . $_SERVER['HTTP_HOST'] . url('/gesis_webhook.php');
    }

    return '';
}

function gesis_whatsapp_webhook_configured(): bool
{
    return strlen(gesis_whatsapp_webhook_secret()) >= 32 && gesis_whatsapp_webhook_url() !== '';
}

function gesis_whatsapp_verificar_firma_webhook(string $rawBody, string $signature): bool
{
    $secret = gesis_whatsapp_webhook_secret();
    if ($secret === '' || $signature === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $rawBody, $secret);

    return hash_equals($expected, $signature);
}

/** @return array{ok:bool,message:string,body:?array<string,mixed>} */
function gesis_whatsapp_registrar_callback(): array
{
    $url = gesis_whatsapp_webhook_url();
    $secret = gesis_whatsapp_webhook_secret();
    if ($url === '') {
        return ['ok' => false, 'message' => 'Falta webhook_url en gesis.local.php (URL pública del sitio).', 'body' => null];
    }
    if (strlen($secret) < 32) {
        return ['ok' => false, 'message' => 'webhook_secret debe tener al menos 32 caracteres en gesis.local.php.', 'body' => null];
    }

    $r = gesis_whatsapp_api('POST', '/api/v1/whatsapp/callbacks', [
        'url' => $url,
        'secret' => $secret,
        'events' => 'inbound,status,session',
    ]);
    if (!$r['ok']) {
        return [
            'ok' => false,
            'message' => $r['raw'] !== '' ? $r['raw'] : 'No se pudo registrar el callback.',
            'body' => is_array($r['body']) ? $r['body'] : null,
        ];
    }

    return [
        'ok' => true,
        'message' => 'Callback registrado en Gesis.',
        'body' => is_array($r['body']) ? $r['body'] : null,
    ];
}

/** @return array{ok:bool,message_id:?string,status:string,error:string} */
function gesis_whatsapp_enviar_mensaje(string $to, string $text): array
{
    $to = preg_replace('/\D+/', '', $to) ?? '';
    if ($to === '') {
        return ['ok' => false, 'message_id' => null, 'status' => '', 'error' => 'Teléfono destino vacío'];
    }

    $estado = gesis_whatsapp_estado();
    if (!$estado['ok'] || !gesis_whatsapp_is_connected($estado['status'])) {
        return [
            'ok' => false,
            'message_id' => null,
            'status' => $estado['status'],
            'error' => 'Emisor no conectado (estado: ' . ($estado['status'] !== '' ? $estado['status'] : 'desconocido') . ')',
        ];
    }

    $r = gesis_whatsapp_api('POST', '/api/v1/whatsapp/enviar-mensaje', [
        'to' => $to,
        'text' => $text,
    ]);
    if (!$r['ok'] || !is_array($r['body'])) {
        return [
            'ok' => false,
            'message_id' => null,
            'status' => '',
            'error' => $r['raw'] !== '' ? $r['raw'] : 'No se pudo enviar mensaje',
        ];
    }

    $id = $r['body']['id'] ?? null;

    return [
        'ok' => true,
        'message_id' => $id !== null ? (string) $id : null,
        'status' => (string) ($r['body']['status'] ?? 'sent'),
        'error' => '',
    ];
}
