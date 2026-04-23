<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $doctores */
/** @var string $fd */
/** @var string $fh */
/** @var int $doctor */
/** @var string $qs query tipo ?fd=... o vacío */
$qsTail = $qs !== '' ? substr($qs, 1) : '';
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Bloqueos de agenda</h1>
        <p class="muted">Marcá días u horarios en los que un profesional no atiende; esos huecos no se ofrecen al cargar turnos.</p>
    </div>

    <form method="get" class="form-card agenda-filters" action="/agenda_bloqueos.php">
        <div class="filter-row">
            <label>Desde
                <input type="date" name="fd" value="<?= h($fd) ?>">
            </label>
            <label>Hasta
                <input type="date" name="fh" value="<?= h($fh) ?>">
            </label>
            <label>Profesional
                <select name="doctor">
                    <option value="0">Todos</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"<?= $doctor === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Filtrar</button>
        </div>
    </form>

    <div class="page-actions">
        <a class="btn btn-primary" href="/agenda_bloqueo_form.php?id=0<?= $qsTail !== '' ? '&' . h($qsTail) : '' ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo bloqueo</a>
        <a class="btn btn-ghost" href="/agenda.php"><i class="bi bi-calendar3-event" aria-hidden="true"></i> Agenda</a>
    </div>

    <?php if ($rows === []): ?>
        <p class="muted">No hay bloqueos en el rango seleccionado.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Profesional</th>
                        <th>Desde</th>
                        <th>Hasta</th>
                        <th>Horario</th>
                        <th>Motivo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $hd = $r['hora_desde'] ?? null;
                        $hh = $r['hora_hasta'] ?? null;
                        $whole = ($hd === null || $hd === '') && ($hh === null || $hh === '');
                        $horarioTxt = $whole ? 'Todo el día' : (substr((string) $hd, 0, 5) . ' — ' . substr((string) $hh, 0, 5) . ' (fin exclusivo)');
                        $editHref = '/agenda_bloqueo_form.php?id=' . (int) $r['id'] . ($qsTail !== '' ? '&' . $qsTail : '');
                        ?>
                        <tr>
                            <td><?= h((string) ($r['doctor_nombre'] ?? '')) ?></td>
                            <td><?= h(substr((string) ($r['fecha_desde'] ?? ''), 0, 10)) ?></td>
                            <td><?= h(substr((string) ($r['fecha_hasta'] ?? ''), 0, 10)) ?></td>
                            <td><?= h($horarioTxt) ?></td>
                            <td class="cell-clip"><?= h((string) ($r['motivo'] ?? '')) ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost" href="<?= h($editHref) ?>">Editar</a>
                                <form method="post" action="/agenda_bloqueo_eliminar.php" class="inline-form" onsubmit="return confirm('¿Eliminar este bloqueo?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
