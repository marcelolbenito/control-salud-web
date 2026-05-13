<?php

declare(strict_types=1);
?>
<div class="container">
    <div class="page-head">
        <h1>Anunciador de sala</h1>
        <p class="muted">Modo operador: gestión de llamados.</p>
    </div>

    <?php if (!$tablaDisponible): ?>
        <p class="alert alert-error">Falta la tabla <code>agenda_llamados</code>. Ejecutá <code>sql/migration_030_agenda_llamados.sql</code>.</p>
    <?php else: ?>
        <div class="page-actions">
            <a class="btn btn-ghost" href="/agenda.php"><i class="bi bi-calendar3-event" aria-hidden="true"></i> Volver a Agenda</a>
            <a class="btn btn-ghost" href="/anunciador.php?modo=monitor&refresh=<?= (int) $autoRefresh ?>" target="_blank" rel="noopener"><i class="bi bi-display" aria-hidden="true"></i> Abrir monitor puro</a>
            <a class="btn btn-primary" href="/anunciador.php?modo=operador&refresh=<?= (int) $autoRefresh ?>"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Actualizar</a>
            <span class="muted">Auto-refresh: <?= (int) $autoRefresh ?>s</span>
        </div>

        <meta http-equiv="refresh" content="<?= (int) $autoRefresh ?>">

        <div class="agenda-layout">
            <section class="agenda-main">
                <h2>Llamando</h2>
                <?php if ($rows === []): ?>
                    <p class="empty-state">No hay llamados activos en sala.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Consultorio</th>
                                    <th>Profesional</th>
                                    <th>Hora llamado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><strong><?= h((string) ($r['paciente_display'] ?? '—')) ?></strong></td>
                                        <td><?= h((string) ($r['consultorio'] ?? '—')) ?></td>
                                        <td><?= h((string) ($r['doctor_nombre'] ?? '—')) ?></td>
                                        <td><?= h((string) ($r['llamado_en'] ?? '—')) ?></td>
                                        <td class="table-actions">
                                            <form action="/anunciador.php?a=estado" method="post" class="table-action-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                <input type="hidden" name="estado" value="en_consultorio">
                                                <button type="submit" class="btn btn-sm btn-ghost">En consultorio</button>
                                            </form>
                                            <form action="/anunciador.php?a=estado" method="post" class="table-action-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                <input type="hidden" name="estado" value="finalizado">
                                                <button type="submit" class="btn btn-sm btn-primary">Finalizar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="agenda-side card-like">
                <h2>En consultorio</h2>
                <?php if ($rowsEnConsultorio === []): ?>
                    <p class="muted">Sin ingresos recientes.</p>
                <?php else: ?>
                    <ul class="list-unstyled">
                        <?php foreach ($rowsEnConsultorio as $r): ?>
                            <li>
                                <strong><?= h((string) ($r['paciente_display'] ?? '—')) ?></strong>
                                <span class="muted"> · <?= h((string) ($r['consultorio'] ?? '—')) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</div>

