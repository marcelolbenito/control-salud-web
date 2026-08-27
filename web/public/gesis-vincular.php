<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/gesis_whatsapp_helpers.php';
require_auth();

$user = auth_user();
$rol = auth_user_role($user);
if (!in_array($rol, ['superadmin', 'admin_clinica'], true)) {
    http_response_code(403);
    exit('Solo administradores pueden vincular WhatsApp.');
}

$msg = '';
$estado = ['ok' => false, 'status' => '', 'id_emisor' => null, 'provider' => '', 'error' => ''];
$connected = false;
$configLocalPath = dirname(__DIR__) . '/config/config.local.php';
$configDiag = [
    'path' => $configLocalPath,
    'exists' => is_file($configLocalPath),
    'bytes' => is_file($configLocalPath) ? (int) filesize($configLocalPath) : 0,
    'raw_has_gesis_key' => false,
    'has_block' => false,
    'has_email' => false,
    'has_password' => false,
];
if ($configDiag['exists']) {
    $raw = (string) @file_get_contents($configLocalPath);
    $configDiag['raw_has_gesis_key'] = str_contains($raw, 'gesis_whatsapp');
}
try {
    $cfgCheck = require dirname(__DIR__) . '/config/config.php';
    $gCheck = $cfgCheck['gesis_whatsapp'] ?? null;
    $configDiag['has_block'] = is_array($gCheck);
    $configDiag['has_email'] = is_array($gCheck) && trim((string) ($gCheck['email'] ?? '')) !== '';
    $configDiag['has_password'] = is_array($gCheck) && (string) ($gCheck['password'] ?? '') !== '';
} catch (Throwable $e) {
    $configDiag['load_error'] = $e->getMessage();
}

if (!gesis_whatsapp_configured()) {
    $msg = 'Falta configurar gesis_whatsapp en config.local.php (ver config.example.php).';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accionPost = (string) ($_POST['accion'] ?? '');
        if ($accionPost === 'reiniciar') {
            $con = gesis_whatsapp_conectar();
            $msg = $con['ok']
                ? 'Conexión reiniciada. Escaneá el QR en los próximos segundos.'
                : 'Error al reiniciar: ' . $con['error'];
        } elseif ($accionPost === 'registrar_webhook') {
            $reg = gesis_whatsapp_registrar_callback();
            $msg = $reg['ok'] ? $reg['message'] : 'Webhook: ' . $reg['message'];
        }
    }

    $estado = gesis_whatsapp_estado();
    $status = $estado['status'];
    $connected = gesis_whatsapp_is_connected($status);

    if (!$connected && gesis_whatsapp_needs_connect($status) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $con = gesis_whatsapp_conectar();
        if ($con['ok']) {
            $estado['status'] = $con['status'];
            $msg = $msg === '' ? 'Sesión iniciada. Escaneá el QR cuando aparezca.' : $msg;
        } elseif ($msg === '') {
            $msg = 'No se pudo iniciar conexión: ' . $con['error'];
        }
    } elseif (!$connected && strtolower($status) === 'connecting' && $msg === '') {
        gesis_whatsapp_conectar();
    }
}

header('Content-Type: text/html; charset=utf-8');
$statusLabel = (string) ($estado['status'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vincular WhatsApp (Gesis) — Control Salud</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 520px; margin: 1.5rem auto; padding: 0 1rem; line-height: 1.45; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        img { display: block; margin: 0 auto; max-width: 300px; border: 1px solid #ccc; border-radius: 8px; min-height: 280px; background: #f8f8f8; }
        .ok { color: #0a7; font-weight: bold; }
        .warn { color: #c60; font-weight: bold; }
        .err { color: #c00; }
        .muted { color: #666; font-size: 0.9rem; }
        button { margin-top: 0.5rem; padding: 0.5rem 1rem; cursor: pointer; }
        ol { padding-left: 1.2rem; }
        #qr-wrap { text-align: center; }
        #qr-timer { font-size: 0.85rem; color: #888; }
    </style>
</head>
<body>
    <h1>Vincular WhatsApp (Gesis)</h1>

    <?php if ($msg !== ''): ?>
        <p class="muted"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!gesis_whatsapp_configured()): ?>
        <div class="card err">
            <p>La web busca credenciales en:</p>
            <p><code><?= htmlspecialchars($configLocalPath, ENT_QUOTES, 'UTF-8') ?></code></p>
            <ul class="muted">
                <li>Archivo existe: <?= $configDiag['exists'] ? 'sí' : '<strong>no</strong>' ?></li>
                <li>Tamaño en disco: <?= (int) $configDiag['bytes'] ?> bytes (con Gesis suele ser ~900–1100)</li>
                <li>Texto <code>gesis_whatsapp</code> en el archivo: <?= $configDiag['raw_has_gesis_key'] ? 'sí' : '<strong>no — hay que subir/guardar el archivo</strong>' ?></li>
                <li>Clave <code>gesis_whatsapp</code> al cargar PHP: <?= $configDiag['has_block'] ? 'sí' : '<strong>no</strong>' ?></li>
                <li><code>email</code> con valor: <?= $configDiag['has_email'] ? 'sí' : '<strong>no</strong>' ?></li>
                <li><code>password</code> con valor: <?= $configDiag['has_password'] ? 'sí' : '<strong>no</strong>' ?></li>
            </ul>
            <?php if (!empty($configDiag['load_error'])): ?>
                <p class="err">Error al cargar config: <?= htmlspecialchars((string) $configDiag['load_error'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <p>En el servidor la ruta es <code>config/config.local.php</code> (no <code>web/config/</code>). Ejemplo:</p>
            <pre style="overflow:auto;font-size:0.8rem;">'gesis_whatsapp' => [
    'base_url' => 'https://servicios.gesis2.com',
    'email' => 'centrosalud@gmail.com',
    'password' => '***',
    'custom_cuit' => '',
    'test_only_nro_hc' => 16059,
],</pre>
        </div>
    <?php elseif ($connected): ?>
        <p class="ok">WhatsApp conectado.</p>
        <?php if ($estado['id_emisor'] !== null): ?>
            <p class="muted">Emisor #<?= (int) $estado['id_emisor'] ?> · <?= htmlspecialchars((string) $estado['provider'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <div class="card">
            <h2 style="margin-top:0">Webhook (respuestas SI/NO)</h2>
            <p class="muted">Para que el paciente confirme o cancele por WhatsApp, Gesis debe poder llamar a tu servidor.</p>
            <ul class="muted">
                <li>URL: <code><?= htmlspecialchars(gesis_whatsapp_webhook_url() ?: '(definir webhook_url)', ENT_QUOTES, 'UTF-8') ?></code></li>
                <li>Secret configurado: <?= strlen(gesis_whatsapp_webhook_secret()) >= 32 ? '<strong>sí</strong>' : '<strong>no — agregá webhook_secret en gesis.local.php</strong>' ?></li>
            </ul>
            <?php if (gesis_whatsapp_webhook_configured()): ?>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="accion" value="registrar_webhook">
                    <button type="submit">Registrar webhook en Gesis</button>
                </form>
            <?php else: ?>
                <p class="err">Editá <code>config/gesis.local.php</code>: <code>webhook_url</code> y <code>webhook_secret</code> (mín. 32 caracteres).</p>
            <?php endif; ?>
        </div>

        <p><a href="/recordatorios.php">Ir a Recordatorios</a></p>
    <?php else: ?>
        <p>Estado: <span id="status-label" class="warn"><?= htmlspecialchars($statusLabel !== '' ? $statusLabel : '—', ENT_QUOTES, 'UTF-8') ?></span></p>

        <div class="card">
            <h2 style="margin-top:0">Antes de escanear</h2>
            <ol class="muted">
                <li>En el celular: <strong>WhatsApp → Dispositivos vinculados</strong> → cerrá sesiones viejas (Web, Recordatorios.exe, pruebas WAHA).</li>
                <li>WhatsApp actualizado y con buena señal.</li>
                <li>Tené el celular listo: el QR <strong>caduca cada ~30 s</strong>.</li>
            </ol>
        </div>

        <div class="card" id="qr-wrap">
            <h2 style="margin-top:0">Escanear QR</h2>
            <p class="muted">WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
            <p><img id="qr" src="/gesis-qr-live.php?t=<?= time() ?>" alt="QR WhatsApp" width="300" height="300"></p>
            <p id="qr-timer" class="muted">Actualizando QR…</p>
        </div>

        <form method="post">
            <input type="hidden" name="accion" value="reiniciar">
            <button type="submit">Reiniciar conexión (QR nuevo)</button>
        </form>

        <p class="muted" style="margin-top:1.5rem;">
            Si sigue fallando en el celular, pedí al admin de <strong>servicios.gesis2.com</strong> que reinicie la sesión Evolution del emisor.
        </p>
    <?php endif; ?>

    <?php if (gesis_whatsapp_configured() && !$connected): ?>
    <script>
    (function () {
        var img = document.getElementById('qr');
        var statusEl = document.getElementById('status-label');
        var timerEl = document.getElementById('qr-timer');
        var seconds = 3;

        function refreshQr() {
            if (!img) return;
            img.src = '/gesis-qr-live.php?t=' + Date.now();
            seconds = 3;
        }

        function pollStatus() {
            fetch('/gesis-status.php', { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.status && statusEl) {
                        statusEl.textContent = data.status;
                        statusEl.className = data.connected ? 'ok' : 'warn';
                    }
                    if (data.connected) {
                        window.location.reload();
                    }
                })
                .catch(function () {});
        }

        setInterval(function () {
            seconds -= 1;
            if (timerEl) {
                timerEl.textContent = seconds > 0
                    ? 'Próximo QR en ' + seconds + ' s'
                    : 'Renovando QR…';
            }
            if (seconds <= 0) {
                refreshQr();
            }
        }, 1000);

        setInterval(pollStatus, 3000);
        pollStatus();
    })();
    </script>
    <?php endif; ?>
</body>
</html>
