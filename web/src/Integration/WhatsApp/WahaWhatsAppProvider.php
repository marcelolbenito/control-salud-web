<?php

declare(strict_types=1);

require_once __DIR__ . '/WhatsAppWebProvider.php';

final class WahaWhatsAppProvider implements WhatsAppWebProvider
{
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $apiKey;
    /** @var string */
    private $session;

    public function __construct(string $baseUrl, string $apiKey = '', string $session = 'default')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->session = $session !== '' ? $session : 'default';
    }

    /**
     * @param array<string, mixed> $cfg
     */
    public static function fromConfig(array $cfg): self
    {
        $w = $cfg['whatsapp_web'] ?? [];

        return new self(
            (string) ($w['base_url'] ?? 'http://127.0.0.1:3000'),
            (string) ($w['api_key'] ?? ''),
            (string) ($w['session'] ?? 'default')
        );
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '';
    }

    public function sessionStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'connected' => false, 'message' => 'WAHA no configurado (base_url vacío).'];
        }
        $resp = $this->request('GET', '/api/sessions/' . rawurlencode($this->session));
        if (!$resp['ok']) {
            return ['ok' => false, 'connected' => false, 'message' => $resp['error'] ?? 'Sin respuesta de WAHA.'];
        }
        $data = $resp['json'];
        $status = is_array($data) ? (string) ($data['status'] ?? '') : '';
        $engineState = '';
        if (is_array($data) && isset($data['engine']) && is_array($data['engine'])) {
            $engineState = strtoupper((string) ($data['engine']['state'] ?? ''));
        }
        $blocked = in_array(strtoupper($status), ['FAILED', 'SCAN_QR_CODE', 'STARTING', 'STOPPED'], true);
        $connected = !$blocked && (
            in_array(strtoupper($status), ['WORKING', 'CONNECTED', 'AUTHENTICATED'], true)
            || in_array($engineState, ['CONNECTED', 'OPEN'], true)
            || (is_array($data) && !empty($data['me']))
        );

        return [
            'ok' => true,
            'connected' => $connected,
            'message' => $status !== '' ? $status : ($engineState !== '' ? $engineState : 'desconocido'),
        ];
    }

    public function sendText(string $telefonoE164, string $texto): array
    {
        $chatId = $this->chatId($telefonoE164);
        if ($chatId === null) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Teléfono inválido.'];
        }
        $resp = $this->request('POST', '/api/sendText', [
            'session' => $this->session,
            'chatId' => $chatId,
            'text' => $texto,
        ]);
        if (!$resp['ok']) {
            return ['ok' => false, 'message_id' => null, 'error' => $resp['error'] ?? 'Error al enviar.'];
        }
        $json = $resp['json'];
        $id = null;
        if (is_array($json)) {
            $id = isset($json['id']) ? (string) $json['id'] : (isset($json['messageId']) ? (string) $json['messageId'] : null);
        }

        return ['ok' => true, 'message_id' => $id, 'error' => null];
    }

    public function qrImageUrl(): string
    {
        return $this->baseUrl . '/api/' . rawurlencode($this->session) . '/auth/qr?format=image';
    }

    public function dashboardUrl(): string
    {
        return $this->baseUrl;
    }

    private function chatId(string $telefonoE164): ?string
    {
        $digits = preg_replace('/\D+/', '', $telefonoE164);
        if ($digits === null || strlen($digits) < 10) {
            return null;
        }

        return $digits . '@c.us';
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{ok:bool,json:?array,error:?string}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'json' => null, 'error' => 'Extensión curl no disponible en PHP.'];
        }
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'json' => null, 'error' => 'No se pudo iniciar curl.'];
        }
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($this->apiKey !== '') {
            $headers[] = 'X-Api-Key: ' . $this->apiKey;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            return ['ok' => false, 'json' => null, 'error' => $err !== '' ? $err : 'Error de red hacia WAHA.'];
        }
        $json = json_decode($raw, true);
        if ($code >= 400) {
            $msg = is_array($json) ? (string) ($json['message'] ?? $json['error'] ?? $raw) : $raw;

            return ['ok' => false, 'json' => is_array($json) ? $json : null, 'error' => 'HTTP ' . $code . ': ' . mb_substr($msg, 0, 200)];
        }

        return ['ok' => true, 'json' => is_array($json) ? $json : null, 'error' => null];
    }
}
