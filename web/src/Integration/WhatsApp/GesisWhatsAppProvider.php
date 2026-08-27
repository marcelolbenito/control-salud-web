<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/gesis_whatsapp_helpers.php';
require_once __DIR__ . '/WhatsAppWebProvider.php';

final class GesisWhatsAppProvider implements WhatsAppWebProvider
{
    public function isConfigured(): bool
    {
        return gesis_whatsapp_configured();
    }

    public function sessionStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'connected' => false, 'message' => 'gesis_whatsapp no configurado.'];
        }
        $estado = gesis_whatsapp_estado();
        if (!$estado['ok']) {
            return ['ok' => false, 'connected' => false, 'message' => $estado['error']];
        }
        $connected = gesis_whatsapp_is_connected($estado['status']);

        return [
            'ok' => true,
            'connected' => $connected,
            'message' => $estado['status'] !== '' ? $estado['status'] : 'desconocido',
        ];
    }

    public function sendText(string $telefonoE164, string $texto): array
    {
        $send = gesis_whatsapp_enviar_mensaje($telefonoE164, $texto);

        return [
            'ok' => $send['ok'],
            'message_id' => $send['message_id'],
            'error' => $send['error'] !== '' ? $send['error'] : null,
        ];
    }

    public function dashboardUrl(): string
    {
        return '/gesis-vincular.php';
    }
}
