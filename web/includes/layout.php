<?php

declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * @param array<string, mixed>|null $layout Opciones: skip_datatables (bool), extra_footer_html (string), extra_head_html (string), body_class (string)
 */
function layout_render(string $title, string $bodyHtml, ?array $user, ?array $layout = null): void
{
    $layout = $layout ?? [];
    $skipDatatables = !empty($layout['skip_datatables']);
    $extraFooter = (string) ($layout['extra_footer_html'] ?? '');
    $extraHead = (string) ($layout['extra_head_html'] ?? '');
    $bodyClass = trim((string) ($layout['body_class'] ?? ''));
    $idClinicaPublica = max(0, (int) ($layout['id_clinica'] ?? 0));
    $cfg = require dirname(__DIR__) . '/config/config.php';
    $appName = $cfg['app']['name'] ?? 'Control Salud Web';
    $brandName = $appName;
    $brandLogoUrl = null;
    if ($user !== null) {
        $branding = clinica_branding(db(), user_clinica_id($user));
        $brandName = (string) ($branding['nombre'] ?? $appName);
        $brandLogoUrl = $branding['logo_url'] ?? null;
    } elseif ($idClinicaPublica > 0) {
        $branding = clinica_branding(db(), $idClinicaPublica);
        $brandName = (string) ($branding['nombre'] ?? $appName);
        $brandLogoUrl = $branding['logo_url'] ?? null;
    }
    $fullTitle = $title === '' ? $brandName : $title . ' · ' . $brandName;
    $currentPath = request_path();
    $isPath = static function (string $p) use ($currentPath): bool {
        return $currentPath === strtolower($p);
    };
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($fullTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@10.0.0/dist/style.css" rel="stylesheet" integrity="sha384-CinX6s1UC1GpaQwL9eKshF7JhtC8H2xchDD0dWqlCgZ8t+GCCcjluxCgmsbVpdEh" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/app.css">
    <?= $extraHead ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . h($bodyClass) . '"' : '' ?>>
    <?php $rol = $user !== null ? auth_user_role($user) : ''; ?>
    <div class="app-shell<?= $user !== null ? ' is-auth' : '' ?>">
        <?php if ($user !== null): ?>
        <aside class="sidebar" id="sidebarNav">
            <button type="button" class="sidebar-collapse-arrow" id="sidebarCompactToggle" aria-label="Contraer menú lateral" title="Contraer/expandir menú">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <div class="brand<?= $brandLogoUrl !== null ? ' brand-has-logo' : '' ?>">
                <?php if ($brandLogoUrl !== null): ?>
                    <img src="<?= h($brandLogoUrl) ?>" alt="<?= h($brandName) ?>" class="brand-logo">
                <?php else: ?>
                    <a class="brand-fallback" href="/index.php">
                        <i class="bi bi-heart-pulse-fill" aria-hidden="true"></i>
                        <span><?= h($brandName) ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <nav class="side-nav">
                <a class="side-link<?= $isPath('/index.php') || $isPath('/') ? ' is-active' : '' ?>" href="/index.php"><i class="bi bi-house-door" aria-hidden="true"></i><span class="side-label">Inicio</span></a>
                <a class="side-link<?= $isPath('/pacientes.php') ? ' is-active' : '' ?>" href="/pacientes.php"><i class="bi bi-people" aria-hidden="true"></i><span class="side-label">Pacientes</span></a>
                <?php if ($rol === 'doctor'): ?>
                    <details class="side-group"<?= $isPath('/agenda.php') || $isPath('/anunciador.php') ? ' open' : '' ?>>
                        <summary><i class="bi bi-calendar3-event" aria-hidden="true"></i><span class="side-label">Agenda</span></summary>
                        <a class="side-sublink<?= $isPath('/agenda.php') ? ' is-active' : '' ?>" href="/agenda.php">Agenda diaria</a>
                        <a class="side-sublink<?= $isPath('/anunciador.php') ? ' is-active' : '' ?>" href="/anunciador.php">Anunciador</a>
                        <a class="side-sublink" href="<?= h(portal_paciente_url(user_clinica_id($user))) ?>" target="_blank" rel="noopener">Portal pacientes</a>
                    </details>
                <?php else: ?>
                    <details class="side-group"<?= $isPath('/agenda.php') || $isPath('/agenda_bloqueos.php') || $isPath('/anunciador.php') || $isPath('/recordatorios.php') || $isPath('/recordatorios_plantillas.php') || $isPath('/control_administrativo.php') ? ' open' : '' ?>>
                        <summary><i class="bi bi-calendar3-event" aria-hidden="true"></i><span class="side-label">Agenda</span></summary>
                        <a class="side-sublink<?= $isPath('/agenda.php') ? ' is-active' : '' ?>" href="/agenda.php">Agenda diaria</a>
                        <a class="side-sublink<?= $isPath('/control_administrativo.php') ? ' is-active' : '' ?>" href="/control_administrativo.php">Control diario</a>
                        <a class="side-sublink<?= $isPath('/anunciador.php') ? ' is-active' : '' ?>" href="/anunciador.php">Anunciador</a>
                        <a class="side-sublink<?= $isPath('/recordatorios.php') ? ' is-active' : '' ?>" href="/recordatorios.php">Recordatorios</a>
                        <a class="side-sublink<?= $isPath('/recordatorios_plantillas.php') ? ' is-active' : '' ?>" href="/recordatorios_plantillas.php">Plantillas WhatsApp</a>
                        <a class="side-sublink<?= $isPath('/agenda_bloqueos.php') ? ' is-active' : '' ?>" href="/agenda_bloqueos.php">Bloqueos</a>
                        <a class="side-sublink" href="<?= h(portal_paciente_url(user_clinica_id($user))) ?>" target="_blank" rel="noopener">Portal pacientes</a>
                    </details>
                <?php endif; ?>
                <?php if ($rol !== 'doctor'): ?>
                    <a class="side-link<?= $isPath('/doctores.php') ? ' is-active' : '' ?>" href="/doctores.php"><i class="bi bi-person-badge" aria-hidden="true"></i><span class="side-label">Doctores</span></a>
                    <details class="side-group"<?= $isPath('/ordenes.php') || $isPath('/orden_form.php') || $isPath('/facturacion_ordenes.php') || $isPath('/sesiones.php') || $isPath('/sesion_form.php') ? ' open' : '' ?>>
                        <summary><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span class="side-label">Prestaciones</span></summary>
                        <a class="side-sublink<?= $isPath('/ordenes.php') || $isPath('/orden_form.php') ? ' is-active' : '' ?>" href="/ordenes.php">Órdenes</a>
                        <a class="side-sublink<?= $isPath('/facturacion_ordenes.php') ? ' is-active' : '' ?>" href="/facturacion_ordenes.php">Facturación OS</a>
                        <a class="side-sublink<?= $isPath('/sesiones.php') || $isPath('/sesion_form.php') ? ' is-active' : '' ?>" href="/sesiones.php">Sesiones</a>
                    </details>
                    <details class="side-group"<?= $isPath('/caja.php') || $isPath('/caja_form.php') || $isPath('/caja_cierre.php') || $isPath('/pagos.php') || $isPath('/pagos_form.php') ? ' open' : '' ?>>
                        <summary><i class="bi bi-cash-coin" aria-hidden="true"></i><span class="side-label">Finanzas</span></summary>
                        <a class="side-sublink<?= $isPath('/caja.php') || $isPath('/caja_form.php') ? ' is-active' : '' ?>" href="/caja.php">Caja</a>
                        <a class="side-sublink<?= $isPath('/caja_cierre.php') ? ' is-active' : '' ?>" href="/caja_cierre.php">Cierre de caja</a>
                        <a class="side-sublink<?= $isPath('/pagos.php') || $isPath('/pagos_form.php') ? ' is-active' : '' ?>" href="/pagos.php">Pagos</a>
                    </details>
                    <details class="side-group"<?= $isPath('/catalogos.php') || $isPath('/aranceles.php') || $isPath('/sistema.php') ? ' open' : '' ?>>
                        <summary><i class="bi bi-gear" aria-hidden="true"></i><span class="side-label">Sistema</span></summary>
                        <a class="side-sublink<?= $isPath('/catalogos.php') ? ' is-active' : '' ?>" href="/catalogos.php">Tablas auxiliares</a>
                        <a class="side-sublink<?= $isPath('/aranceles.php') ? ' is-active' : '' ?>" href="/aranceles.php">Aranceles (precios OS)</a>
                        <a class="side-sublink<?= $isPath('/sistema.php') ? ' is-active' : '' ?>" href="/sistema.php">Configuración</a>
                    </details>
                    <?php
                    $labCfg = $cfg['laboratorio'] ?? [];
                    if (!empty($labCfg['enabled'])) {
                        $labPath = trim((string) ($labCfg['path'] ?? '/laboratorio'));
                        $labHref = url(rtrim($labPath, '/') . '/');
                        ?>
                    <a class="side-link" href="<?= h($labHref) ?>"><i class="bi bi-droplet-half" aria-hidden="true"></i><span class="side-label">Laboratorio</span></a>
                    <?php } ?>
                <?php endif; ?>
                <a class="side-link<?= $isPath('/ayuda.php') ? ' is-active' : '' ?>" href="/ayuda.php"><i class="bi bi-question-circle" aria-hidden="true"></i><span class="side-label">Ayuda</span></a>
            </nav>
        </aside>
        <?php endif; ?>
        <div class="app-main">
            <header class="topbar<?= $user === null && $idClinicaPublica > 0 ? ' topbar-public' : '' ?>">
                <?php if ($user !== null): ?>
                    <div class="topbar-start">
                        <button type="button" class="btn btn-ghost btn-sm sidebar-toggle" id="sidebarToggle"><i class="bi bi-list" aria-hidden="true"></i> Menú</button>
                        <?php if ($brandLogoUrl !== null): ?>
                            <div class="topbar-logo-wrap">
                                <img src="<?= h($brandLogoUrl) ?>" alt="<?= h($brandName) ?>" class="topbar-logo">
                            </div>
                        <?php elseif ($brandName !== $appName): ?>
                            <span class="topbar-fallback-name"><?= h($brandName) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user">
                        <span class="user-name"><i class="bi bi-person-circle" aria-hidden="true"></i> <?= h($user['nombre'] ?: $user['usuario']) ?> <small class="muted">(<?= h((string) $rol) ?>)</small></span>
                        <a class="btn btn-ghost btn-sm" href="/logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Salir</a>
                    </div>
                <?php elseif ($idClinicaPublica > 0 && ($brandLogoUrl !== null || $brandName !== $appName)): ?>
                    <div class="topbar-start topbar-start-public">
                        <?php if ($brandLogoUrl !== null): ?>
                            <div class="topbar-logo-wrap">
                                <img src="<?= h($brandLogoUrl) ?>" alt="<?= h($brandName) ?>" class="topbar-logo">
                            </div>
                        <?php else: ?>
                            <span class="topbar-fallback-name"><?= h($brandName) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>
            <main class="site-main">
        <?php
        $flash = flash_take();
        if ($flash !== null): ?>
            <div class="container"><p class="alert alert-success"><?= h($flash) ?></p></div>
        <?php endif; ?>
        <?= $bodyHtml ?>
            </main>
        </div>
    </div>
    <?php if (!$skipDatatables): ?>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@10.0.0/dist/umd/simple-datatables.js" defer integrity="sha384-zC4qlxpbZLn7OW9yPcJveO8HidLdXKcboqjS6ypXOEqQUNNj4TGnW7XH51eSHVoE" crossorigin="anonymous"></script>
    <script src="/js/app.js" defer></script>
    <?php endif; ?>
    <script>
    (function () {
        var btn = document.getElementById('sidebarToggle');
        var compactBtn = document.getElementById('sidebarCompactToggle');
        var sidebar = document.getElementById('sidebarNav');
        var compactKey = 'cs_sidebar_compact';
        try {
            if (window.localStorage && window.localStorage.getItem(compactKey) === '1') {
                document.body.classList.add('sidebar-compact');
            }
        } catch (e) {}
        if (btn && sidebar) {
            btn.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-open');
            });
        }
        if (compactBtn && sidebar) {
            compactBtn.addEventListener('click', function () {
                document.body.classList.toggle('sidebar-compact');
                try {
                    if (window.localStorage) {
                        window.localStorage.setItem(compactKey, document.body.classList.contains('sidebar-compact') ? '1' : '0');
                    }
                } catch (e) {}
            });
        }
    })();
    </script>
    <?= $extraFooter ?>
</body>
</html>
<?php
}
