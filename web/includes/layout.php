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
    $cfg = require dirname(__DIR__) . '/config/config.php';
    $appName = $cfg['app']['name'] ?? 'Control Salud Web';
    $fullTitle = $title === '' ? $appName : $title . ' · ' . $appName;
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
    <header class="site-header">
        <div class="inner">
            <a class="brand" href="/"><i class="bi bi-heart-pulse-fill" aria-hidden="true"></i><span><?= h($appName) ?></span></a>
            <?php if ($user !== null): ?>
                <nav class="nav">
                    <a href="/index.php"><i class="bi bi-house-door" aria-hidden="true"></i> Inicio</a>
                    <a href="/pacientes.php"><i class="bi bi-people" aria-hidden="true"></i> Pacientes</a>
                    <a href="/doctores.php"><i class="bi bi-person-badge" aria-hidden="true"></i> Doctores</a>
                    <a href="/agenda.php"><i class="bi bi-calendar3-event" aria-hidden="true"></i> Agenda</a>
                    <a href="/ordenes.php"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Órdenes</a>
                    <a href="/catalogos.php"><i class="bi bi-journals" aria-hidden="true"></i> Tablas auxiliares</a>
                    <a href="/sistema.php"><i class="bi bi-gear" aria-hidden="true"></i> Sistema</a>
                </nav>
                <div class="user">
                    <span class="user-name"><i class="bi bi-person-circle" aria-hidden="true"></i> <?= h($user['nombre'] ?: $user['usuario']) ?></span>
                    <a class="btn btn-ghost btn-sm" href="/logout.php"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Salir</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    <main class="site-main">
        <?php
        $flash = flash_take();
        if ($flash !== null): ?>
            <div class="container"><p class="alert alert-success"><?= h($flash) ?></p></div>
        <?php endif; ?>
        <?= $bodyHtml ?>
    </main>
    <?php if (!$skipDatatables): ?>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@10.0.0/dist/umd/simple-datatables.js" defer integrity="sha384-zC4qlxpbZLn7OW9yPcJveO8HidLdXKcboqjS6ypXOEqQUNNj4TGnW7XH51eSHVoE" crossorigin="anonymous"></script>
    <script src="/js/app.js" defer></script>
    <?php endif; ?>
    <?= $extraFooter ?>
</body>
</html>
<?php
}
