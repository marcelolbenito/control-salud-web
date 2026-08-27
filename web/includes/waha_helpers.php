<?php

declare(strict_types=1);

/** @return array{ok:bool,code:int,json:?array,raw:string} */
function waha_api(string $method, string $path, ?array $body = null): array
{
    $cfg = require dirname(__DIR__) . '/config/config.php';
    $w = $cfg['whatsapp_web'] ?? [];
    $base = rtrim((string) ($w['base_url'] ?? ''), '/');
    $key = (string) ($w['api_key'] ?? '');
    if ($base === '') {
        return ['ok' => false, 'code' => 0, 'json' => null, 'raw' => 'base_url vacío'];
    }
    $ch = curl_init($base . $path);
    if ($ch === false) {
        return ['ok' => false, 'code' => 0, 'json' => null, 'raw' => 'curl init'];
    }
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($key !== '') {
        $headers[] = 'X-Api-Key: ' . $key;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 45,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $raw = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($raw, true);

    return ['ok' => $code > 0 && $code < 400, 'code' => $code, 'json' => is_array($json) ? $json : null, 'raw' => $raw];
}

function waha_session_name(): string
{
    $cfg = require dirname(__DIR__) . '/config/config.php';
    $w = $cfg['whatsapp_web'] ?? [];
    $s = (string) ($w['session'] ?? 'default');

    return $s !== '' ? $s : 'default';
}

/** @return array<string, mixed>|null */
function waha_session_get(): ?array
{
    $s = rawurlencode(waha_session_name());
    $r = waha_api('GET', '/api/sessions/' . $s);

    return $r['json'];
}

function waha_session_restart_for_qr(): array
{
    $s = rawurlencode(waha_session_name());
    waha_api('POST', '/api/sessions/' . $s . '/stop');
    waha_api('POST', '/api/sessions/' . $s . '/logout');
    waha_api('DELETE', '/api/sessions/' . $s);

    $body = [
        'name' => waha_session_name(),
        'start' => true,
        'config' => [
            'noweb' => [
                'markOnline' => true,
                'store' => ['enabled' => true, 'fullSync' => false],
            ],
        ],
    ];
    $created = waha_api('POST', '/api/sessions', $body);

    return [
        'ok' => $created['ok'],
        'status' => (string) ($created['json']['status'] ?? ''),
        'raw' => $created['raw'],
    ];
}

/** @return array{ok:bool,code:?string,error:?string} */
function waha_request_pairing_code(string $phoneDigits): array
{
    $digits = preg_replace('/\D+/', '', $phoneDigits);
    if ($digits === null || strlen($digits) < 10) {
        return ['ok' => false, 'code' => null, 'error' => 'Número inválido'];
    }
    $s = rawurlencode(waha_session_name());
    $st = waha_session_get();
    $status = is_array($st) ? (string) ($st['status'] ?? '') : '';
    if (in_array($status, ['FAILED', 'STOPPED', ''], true)) {
        waha_session_restart_for_qr();
        sleep(2);
    }
    $r = waha_api('POST', '/api/' . $s . '/auth/request-code', ['phoneNumber' => $digits]);
    if (!$r['ok']) {
        $msg = is_array($r['json']) ? (string) ($r['json']['message'] ?? $r['json']['error'] ?? $r['raw']) : $r['raw'];

        return ['ok' => false, 'code' => null, 'error' => mb_substr($msg, 0, 300)];
    }
    $code = is_array($r['json']) ? (string) ($r['json']['code'] ?? '') : '';

    return ['ok' => $code !== '', 'code' => $code !== '' ? $code : null, 'error' => $code === '' ? 'Sin código en respuesta' : null];
}

function waha_is_connected(?array $session): bool
{
    if (!is_array($session)) {
        return false;
    }
    $status = strtoupper((string) ($session['status'] ?? ''));
    if (in_array($status, ['FAILED', 'SCAN_QR_CODE', 'STARTING', 'STOPPED'], true)) {
        return false;
    }
    if (in_array($status, ['WORKING', 'CONNECTED'], true)) {
        return true;
    }
    if (!empty($session['me']) && is_array($session['me'])) {
        return true;
    }
    if (isset($session['engine']) && is_array($session['engine'])) {
        $es = strtoupper((string) ($session['engine']['state'] ?? ''));

        return in_array($es, ['CONNECTED', 'OPEN'], true);
    }

    return false;
}
