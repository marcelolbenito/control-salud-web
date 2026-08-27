<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/waha_helpers.php';

$msg = '';
$pairCode = '';
$session = waha_session_get();
$status = is_array($session) ? (string) ($session['status'] ?? '') : '';
$connected = waha_is_connected($session);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'reiniciar') {
        $r = waha_session_restart_for_qr();
        $msg = $r['ok'] ? 'Sesión reiniciada. Escaneá el QR nuevo en los próximos 2 minutos.' : 'Error al reiniciar: ' . $r['raw'];
        sleep(2);
        $session = waha_session_get();
        $status = is_array($session) ? (string) ($session['status'] ?? '') : '';
        $connected = waha_is_connected($session);
    } elseif ($accion === 'codigo') {
        $tel = trim((string) ($_POST['telefono'] ?? ''));
        $pr = waha_request_pairing_code($tel);
        if ($pr['ok']) {
            $pairCode = (string) $pr['code'];
            $msg = 'Ingresá este código en WhatsApp (Dispositivos vinculados → Vincular con número de teléfono).';
        } else {
            $msg = 'No se pudo obtener código: ' . (string) $pr['error'];
        }
        $session = waha_session_get();
        $status = is_array($session) ? (string) ($session['status'] ?? '') : '';
        $connected = waha_is_connected($session);
    }
}

// Auto-reinicio si quedó en FAILED (QR expiró sin escanear)
if (!$connected && $status === 'FAILED' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_GET['noauto'])) {
    waha_session_restart_for_qr();
    sleep(2);
    $session = waha_session_get();
    $status = is_array($session) ? (string) ($session['status'] ?? '') : '';
    $connected = waha_is_connected($session);
    $msg = 'La sesión había expirado (FAILED). Se generó un QR nuevo — escanealo ahora.';
}

header('Content-Type: text/html; charset=utf-8');
$qrTs = time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vincular WhatsApp — Control Salud</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 480px; margin: 1.5rem auto; padding: 0 1rem; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        img { display: block; margin: 0 auto; max-width: 280px; border: 1px solid #ccc; border-radius: 8px; }
        .ok { color: #0a7; font-weight: bold; }
        .warn { color: #c60; font-weight: bold; }
        .err { color: #c00; }
        .muted { color: #666; font-size: 0.9rem; }
        .code { font-size: 2rem; letter-spacing: 0.2em; text-align: center; font-weight: bold; margin: 1rem 0; }
        input[type=text] { width: 100%; padding: 0.5rem; box-sizing: border-box; }
        button { margin-top: 0.5rem; padding: 0.5rem 1rem; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Vincular WhatsApp</h1>

    <?php if ($msg !== ''): ?>
        <p class="muted"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($connected): ?>
        <p class="ok">Sesión conectada.</p>
        <?php if (is_array($session['me'] ?? null)): ?>
            <p class="muted">Emisor: <?= htmlspecialchars((string) ($session['me']['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p><a href="/recordatorios.php">Ir a Recordatorios</a></p>
    <?php else: ?>
        <p>Estado: <span class="<?= $status === 'FAILED' ? 'err' : 'warn' ?>"><?= htmlspecialchars($status !== '' ? $status : '—', ENT_QUOTES, 'UTF-8') ?></span></p>

        <?php if ($status === 'FAILED'): ?>
            <p class="err">El QR expiró (WhatsApp permite ~6 códigos, ~2 min). Reiniciá y escaneá el nuevo de inmediato.</p>
            <form method="post"><input type="hidden" name="accion" value="reiniciar"><button type="submit">Generar QR nuevo</button></form>
        <?php elseif (in_array($status, ['SCAN_QR_CODE', 'STARTING'], true)): ?>
            <div class="card">
                <h2 style="margin-top:0">Opción A — QR (rápido)</h2>
                <p class="muted">Celular: WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
                <p><img id="qr" src="/waha-qr-live.php?t=<?= $qrTs ?>" alt="QR" width="280" height="280"></p>
                <p class="muted">El QR se renueva cada 8 segundos. Tené el celular listo antes de escanear.</p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 style="margin-top:0">Opción B — Código (sin escanear)</h2>
            <p class="muted">WhatsApp → Dispositivos vinculados → Vincular con número de teléfono</p>
            <form method="post">
                <input type="hidden" name="accion" value="codigo">
                <label>Número del celular emisor (con código país, ej. 5493512419001)
                    <input type="text" name="telefono" placeholder="5493512419001" required>
                </label>
                <button type="submit">Pedir código de 8 dígitos</button>
            </form>
            <?php if ($pairCode !== ''): ?>
                <p class="code"><?= htmlspecialchars($pairCode, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <form method="post" style="margin-top:1rem;">
            <input type="hidden" name="accion" value="reiniciar">
            <button type="submit">Reiniciar sesión WAHA</button>
        </form>
    <?php endif; ?>

    <script>
    (function () {
        var img = document.getElementById('qr');
        if (!img) return;
        setInterval(function () {
            img.src = '/waha-qr-live.php?t=' + Date.now();
        }, 8000);
        setInterval(function () {
            if (!document.hidden) location.reload();
        }, 12000);
    })();
    </script>
</body>
</html>
