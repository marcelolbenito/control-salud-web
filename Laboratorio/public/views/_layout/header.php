<?php
/**
 * Cabecera comun para todas las vistas del modulo.
 */


require_once __DIR__ . '/lab_url.php';

/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Laboratorio';
$esc = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$labBase = lab_url('/');
$userName = 'Invitado';
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $userName = (string) ($_SESSION['user']['nombre'] ?? $_SESSION['user']['usuario'] ?? 'Usuario');
}
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="lab-base" content="<?= $esc(lab_base_path()) ?>">
    <meta name="lab-query-router" content="<?= lab_uses_query_router() ? '1' : '0' ?>">
    <meta name="lab-api-js" content="<?= $esc(lab_scripts_version()) ?>">
    <meta name="lab-api-bridge" content="<?= $esc(lab_api_bridge()) ?>">
    <title><?= $esc($pageTitle) ?> · Laboratorio Clinico</title>
    <script>
    (function () {
        const base = (document.querySelector('meta[name="lab-base"]')?.getAttribute('content') || '').replace(/\/$/, '');
        const bridge = (document.querySelector('meta[name="lab-api-bridge"]')?.getAttribute('content') || '').replace(/\/$/, '');
        if (!base || !bridge) return;

        function toApiPhp(url) {
            try {
                const u = new URL(url, window.location.origin);
                const path = u.pathname;
                const idx = path.indexOf(base + '/api/');
                if (idx !== -1 && path.indexOf('/api.php') === -1) {
                    const ep = path.slice(idx + base.length + 5).replace(/\/$/, '');
                    u.pathname = bridge;
                    u.search = 'e=' + encodeURIComponent(ep) + (u.search ? '&' + u.search.slice(1) : '');
                    return u.toString();
                }
                if (path === base || path === base + '/') {
                    const r = u.searchParams.get('r');
                    if (r && r.indexOf('api.') === 0) {
                        const ep = r.slice(4).split('.')[0];
                        u.pathname = bridge;
                        u.searchParams.delete('r');
                        const rest = u.searchParams.toString();
                        u.search = 'e=' + encodeURIComponent(ep) + (rest ? '&' + rest : '');
                        return u.toString();
                    }
                }
            } catch (e) { /* ignore */ }
            return url;
        }

        const orig = window.fetch.bind(window);
        window.fetch = function (input, init) {
            let url = typeof input === 'string' ? input : (input instanceof Request ? input.url : '');
            const fixed = toApiPhp(url);
            if (fixed !== url) {
                input = typeof input === 'string' ? fixed : new Request(fixed, input);
            }
            return orig(input, init);
        };
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= lab_asset_h('/assets/css/app.css') ?>">
    <script type="importmap">
    {
        "imports": {
            "/assets/js/": "<?= $esc(lab_asset_url('/assets/js/')) ?>"
        }
    }
    </script>
</head>
<body>
    <header class="topbar">
        <a href="<?= lab_h('/') ?>" class="brand">
            <i class="bi bi-droplet-half"></i>
            <span>Laboratorio</span>
        </a>
        <div class="topbar-actions">
            <a href="/index.php" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left"></i> Control Salud</a>
            <span class="user">
                <i class="bi bi-person-circle"></i>
                <span class="user-name"><?= $esc($userName) ?></span>
            </span>
        </div>
    </header>
    <main class="site-main">
