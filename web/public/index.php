<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_auth();

$user = auth_user();
ob_start();

$fmtN = static function (int $n): string {
    return number_format($n, 0, ',', '.');
};

$pacientes = 0;
$pacientesActivos = 0;
$doctores = 0;
$turnosHoy = 0;
$turnosProx7 = 0;
$dashboard = [
    'labels14' => [],
    'turnos14' => [],
    'estadoLabels' => [],
    'estadoCounts' => [],
    'estadoTotal' => 0,
    'doctorLabels' => [],
    'doctorCounts' => [],
];

try {
    $pdo = db();
    $pacientes = (int) $pdo->query('SELECT COUNT(*) c FROM pacientes')->fetch()['c'];
    $pacientesActivos = (int) $pdo->query('SELECT COUNT(*) c FROM pacientes WHERE activo = 1')->fetch()['c'];
    $doctores = (int) $pdo->query('SELECT COUNT(*) c FROM lista_doctores')->fetch()['c'];
    $turnosHoy = (int) $pdo->query('SELECT COUNT(*) c FROM agenda_turnos WHERE Fecha = CURDATE()')->fetch()['c'];
    $turnosProx7 = (int) $pdo->query(
        'SELECT COUNT(*) c FROM agenda_turnos WHERE Fecha > CURDATE() AND Fecha <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)'
    )->fetch()['c'];

    $byDay = [];
    $stDay = $pdo->query(
        'SELECT Fecha, COUNT(*) AS c FROM agenda_turnos
         WHERE Fecha >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND Fecha <= CURDATE()
         GROUP BY Fecha'
    );
    while ($row = $stDay->fetch(PDO::FETCH_ASSOC)) {
        $byDay[(string) $row['Fecha']] = (int) $row['c'];
    }

    for ($i = 13; $i >= 0; $i--) {
        $d = new DateTimeImmutable("{$i} days ago");
        $key = $d->format('Y-m-d');
        $dashboard['labels14'][] = $d->format('d/m');
        $dashboard['turnos14'][] = $byDay[$key] ?? 0;
    }

    $stEst = $pdo->query(
        "SELECT COALESCE(NULLIF(TRIM(estado), ''), 'pendiente') AS estado, COUNT(*) AS c
         FROM agenda_turnos WHERE Fecha = CURDATE() GROUP BY estado ORDER BY c DESC"
    );
    while ($row = $stEst->fetch(PDO::FETCH_ASSOC)) {
        $dashboard['estadoLabels'][] = $row['estado'];
        $dashboard['estadoCounts'][] = (int) $row['c'];
    }
    foreach ($dashboard['estadoCounts'] as $n) {
        $dashboard['estadoTotal'] += $n;
    }

    $stDoc = $pdo->query(
        'SELECT d.nombre AS nombre, COUNT(*) AS c
         FROM agenda_turnos t
         INNER JOIN lista_doctores d ON d.id = t.Doctor
         WHERE t.Fecha >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY t.Doctor, d.nombre
         ORDER BY c DESC
         LIMIT 8'
    );
    while ($row = $stDoc->fetch(PDO::FETCH_ASSOC)) {
        $nombre = (string) $row['nombre'];
        if (function_exists('mb_strimwidth')) {
            $nombre = mb_strimwidth($nombre, 0, 28, '…', 'UTF-8');
        } elseif (strlen($nombre) > 28) {
            $nombre = substr($nombre, 0, 25) . '...';
        }
        $dashboard['doctorLabels'][] = $nombre ?: '—';
        $dashboard['doctorCounts'][] = (int) $row['c'];
}
} catch (Throwable $e) {
    $dbError = true;
    error_log('[control-salud] dashboard DB error: ' . $e->getMessage());
}

$dashboardJson = '';
$extraFooter = '';
if (!isset($dbError)) {
    $dashboardJson = json_encode(
        $dashboard,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
    );
    $extraFooter = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4" crossorigin="anonymous"></script>'
        . '<script src="/js/dashboard.js" defer></script>';
}

?>
<div class="container dashboard-page">
    <?php if (isset($dbError)): ?>
        <h1>Inicio</h1>
        <p class="alert alert-error">No se pudo leer la base de datos. Revisá <code>web/config/config.local.php</code> y que exista la BD con el esquema importado.</p>
    <?php else: ?>
        <header class="dashboard-head">
            <div class="dashboard-head-text">
                <p class="dashboard-eyebrow"><i class="bi bi-speedometer2" aria-hidden="true"></i> Panel</p>
                <h1>Bienvenido<?= $user['nombre'] !== '' && $user['nombre'] !== null ? ', ' . h($user['nombre']) : '' ?></h1>
                <p class="dashboard-subtitle">Resumen de actividad y turnos — Control Salud</p>
            </div>
            <div class="dashboard-head-actions">
                <a class="btn btn-primary" href="/agenda.php"><i class="bi bi-calendar3-event" aria-hidden="true"></i> Agenda</a>
                <a class="btn btn-ghost" href="/pacientes.php"><i class="bi bi-people" aria-hidden="true"></i> Pacientes</a>
            </div>
        </header>

        <div class="dashboard-stat-grid">
            <a class="stat-card stat-card-kpi stat-card-kpi--pacientes" href="/pacientes.php">
                <div class="stat-card-kpi-head">
                    <span class="stat-card-kpi-label">Pacientes</span>
                    <span class="stat-card-kpi-ico" aria-hidden="true"><i class="bi bi-people-fill"></i></span>
                </div>
                <div class="stat-card-kpi-display">
                    <span class="stat-card-kpi-value"><?= $fmtN($pacientes) ?></span>
                </div>
                <div class="stat-card-kpi-foot">
                    <span class="stat-card-kpi-pill"><?= $fmtN($pacientesActivos) ?> con ficha activa</span>
                </div>
            </a>
            <a class="stat-card stat-card-kpi stat-card-kpi--doctores" href="/doctores.php">
                <div class="stat-card-kpi-head">
                    <span class="stat-card-kpi-label">Profesionales</span>
                    <span class="stat-card-kpi-ico" aria-hidden="true"><i class="bi bi-person-badge-fill"></i></span>
                </div>
                <div class="stat-card-kpi-display">
                    <span class="stat-card-kpi-value"><?= $fmtN($doctores) ?></span>
                </div>
                <div class="stat-card-kpi-foot">
                    <span class="stat-card-kpi-hint">En lista de doctores</span>
                </div>
            </a>
            <a class="stat-card stat-card-kpi stat-card-kpi--hoy" href="/agenda.php">
                <div class="stat-card-kpi-head">
                    <span class="stat-card-kpi-label">Turnos hoy</span>
                    <span class="stat-card-kpi-ico" aria-hidden="true"><i class="bi bi-calendar-day-fill"></i></span>
                </div>
                <div class="stat-card-kpi-display">
                    <span class="stat-card-kpi-value"><?= $fmtN($turnosHoy) ?></span>
                </div>
                <div class="stat-card-kpi-foot">
                    <span class="stat-card-kpi-hint">Agenda del día</span>
                </div>
            </a>
            <a class="stat-card stat-card-kpi stat-card-kpi--prox" href="/agenda.php">
                <div class="stat-card-kpi-head">
                    <span class="stat-card-kpi-label">Próximos 7 días</span>
                    <span class="stat-card-kpi-ico" aria-hidden="true"><i class="bi bi-calendar-week-fill"></i></span>
                </div>
                <div class="stat-card-kpi-display">
                    <span class="stat-card-kpi-value"><?= $fmtN($turnosProx7) ?></span>
                </div>
                <div class="stat-card-kpi-foot">
                    <span class="stat-card-kpi-hint">A partir de mañana</span>
                </div>
            </a>
        </div>

        <div class="dashboard-grid">
            <section class="dash-card">
                <h2 class="dash-card-title"><i class="bi bi-graph-up" aria-hidden="true"></i> Turnos por día</h2>
                <p class="dash-card-desc">Últimas 2 semanas</p>
                <div class="chart-wrap chart-wrap-tall">
                    <canvas id="chart-turnos-dia" aria-label="Gráfico de turnos por día"></canvas>
                </div>
            </section>
            <section class="dash-card">
                <h2 class="dash-card-title"><i class="bi bi-pie-chart" aria-hidden="true"></i> Hoy por estado</h2>
                <p class="dash-card-desc">Distribución del día actual</p>
                <div class="chart-wrap chart-wrap-doughnut">
                    <canvas id="chart-estado-hoy" aria-label="Gráfico de turnos por estado hoy"></canvas>
                </div>
            </section>
            <section class="dash-card dash-card-wide">
                <h2 class="dash-card-title"><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Top profesionales</h2>
                <p class="dash-card-desc">Más turnos en los últimos 30 días</p>
                <div class="chart-wrap chart-wrap-bar">
                    <canvas id="chart-doctores" aria-label="Gráfico de turnos por profesional"></canvas>
                </div>
            </section>
        </div>

        <script type="application/json" id="dashboard-data"><?= $dashboardJson ?></script>
    <?php endif; ?>
</div>
<?php
$body = ob_get_clean();
$layoutOpts = ['skip_datatables' => true];
if ($extraFooter !== '') {
    $layoutOpts['extra_footer_html'] = $extraFooter;
}
layout_render('Inicio', $body, $user, $layoutOpts);
