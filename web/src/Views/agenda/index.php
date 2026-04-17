<?php

declare(strict_types=1);
?>
<div class="container">
    <div class="page-head">
        <h1>Agenda diaria</h1>
        <p class="muted">Vista operativa por fecha y profesional, con acciones rápidas tipo escritorio.</p>
        <?php if (!$extAgenda): ?>
            <p class="muted" style="font-size:0.9rem;">Columnas Atendido/Llegó/Confirmado/Ausente aparecen tras <code>sql/migration_003_doctores_agenda_exe.sql</code>.</p>
        <?php endif; ?>
    </div>

    <form method="get" class="agenda-filters form-card" action="/agenda.php">
        <div class="filter-row">
            <label class="agenda-fecha-label">
                Fecha
                <input type="date" name="fecha" value="<?= h($fecha) ?>">
            </label>
            <div class="agenda-day-nav">
                <a class="btn btn-ghost btn-sm" href="/agenda.php?fecha=<?= rawurlencode($fechaPrev) ?><?= $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '' ?>"><i class="bi bi-chevron-left"></i> Día anterior</a>
                <a class="btn btn-ghost btn-sm" href="/agenda.php?fecha=<?= rawurlencode($fechaNext) ?><?= $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '' ?>">Día siguiente <i class="bi bi-chevron-right"></i></a>
            </div>
            <label>
                Profesional
                <select name="doctor">
                    <option value="0">Todos</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"<?= $doctorFiltro === (int) $d['id'] ? ' selected' : '' ?>>
                            <?= h($d['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Ver</button>
        </div>
    </form>

    <div class="agenda-summary">
        <div class="agenda-kpi"><span>Total</span><strong><?= (int) $resumen['total'] ?></strong></div>
        <div class="agenda-kpi"><span>Pendientes</span><strong><?= (int) $resumen['pendientes'] ?></strong></div>
        <div class="agenda-kpi"><span>Atendidos</span><strong><?= (int) $resumen['atendidos'] ?></strong></div>
        <div class="agenda-kpi"><span>No asistió</span><strong><?= (int) $resumen['no_asistio'] ?></strong></div>
        <?php if ($extAgenda): ?>
            <div class="agenda-kpi"><span>Llegados</span><strong><?= (int) $resumen['llegados'] ?></strong></div>
            <div class="agenda-kpi"><span>Confirmados</span><strong><?= (int) $resumen['confirmados'] ?></strong></div>
        <?php endif; ?>
    </div>

    <div class="page-actions">
        <a class="btn btn-primary" href="/turno_form.php?fecha=<?= urlencode($fecha) ?><?= $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '' ?>"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Nuevo turno</a>
        <a class="btn btn-ghost" href="/ordenes.php"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Órdenes</a>
        <a class="btn btn-primary" href="/orden_form.php?fecha=<?= urlencode($fecha) ?><?= $doctorFiltro > 0 ? '&doctor=' . (int) $doctorFiltro : '' ?>"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Nueva orden</a>
    </div>

    <div class="agenda-layout">
        <section class="agenda-main">
            <?php if ($rows === []): ?>
                <p class="empty-state">No hay turnos para esta fecha<?= $doctorFiltro > 0 ? ' y profesional' : '' ?>.</p>
            <?php else: ?>
                <div class="table-wrap table-wrap-datatable">
                    <table id="tbl-agenda" class="table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>Nro HC</th>
                        <th>Profesional</th>
                        <th>Estado</th>
                        <?php if ($extAgenda): ?>
                            <th>Atendido</th>
                            <th>Llegó</th>
                            <th>Confirmado</th>
                            <th>Ausente</th>
                        <?php endif; ?>
                        <th>Orden médica</th>
                        <th>Obs.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $obs = (string) ($r['observaciones'] ?? '');
                        $obsOut = function_exists('mb_strimwidth')
                            ? mb_strimwidth($obs, 0, 40, '…', 'UTF-8')
                            : (strlen($obs) > 40 ? substr($obs, 0, 37) . '...' : $obs);
                        $estadoSlug = preg_replace('/[^a-z_]/i', '', (string) $r['estado']) ?: 'pendiente';
                        $turnoLink = '/agenda.php?fecha=' . rawurlencode($fecha)
                            . ($doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '')
                            . '&turno=' . (int) $r['id'];
                        $trClass = 'agenda-row estado-' . $estadoSlug;
                        if ($turnoSel && (int) $turnoSel['id'] === (int) $r['id']) {
                            $trClass .= ' is-selected';
                        }
                        ?>
                        <tr
                            class="<?= h($trClass) ?> agenda-row-selectable"
                            data-turno-link="<?= h($turnoLink) ?>"
                            tabindex="0"
                            role="link"
                            aria-label="Ver detalle del turno de <?= h((string) ($r['paciente_nombre'] ?? 'paciente')) ?>"
                        >
                            <td><?= $r['hora'] ? h(substr((string) $r['hora'], 0, 5)) : '—' ?></td>
                            <td><a class="agenda-paciente-link" href="<?= h($turnoLink) ?>"><?= h($r['paciente_nombre'] ?? '') ?></a></td>
                            <td><?= (int) $r['NroHC'] ?></td>
                            <td><?= h($r['doctor_nombre'] ?? '—') ?></td>
                            <td><span class="badge-estado estado-<?= h($estadoSlug) ?>"><?= h((string) $r['estado']) ?></span></td>
                            <?php if ($extAgenda): ?>
                                <td title="Atendido"><?= !empty($r['atendido']) ? 'Sí' : '—' ?></td>
                                <td title="Llegó"><?= !empty($r['llegado']) ? 'Sí' : '—' ?></td>
                                <td title="Confirmado"><?= !empty($r['confirmado']) ? 'Sí' : '—' ?></td>
                                <td title="Faltó"><?= !empty($r['falta_turno']) ? 'Sí' : '—' ?></td>
                            <?php endif; ?>
                            <td><?= $r['idorden'] !== null ? (int) $r['idorden'] : '—' ?></td>
                            <td class="cell-clip" title="<?= h($obs) ?>"><?= h($obsOut) ?></td>
                            <td class="table-actions">
                                <?php
                                $nroHcRow = (int) $r['NroHC'];
                                $idOrdenRow = isset($r['idorden']) && $r['idorden'] !== null && $r['idorden'] !== '' ? (int) $r['idorden'] : 0;
                                $idDocRow = (int) ($r['Doctor'] ?? $r['doctor'] ?? 0);
                                if ($idOrdenRow > 0) {
                                    $hrefOrden = '/orden_form.php?id=' . $idOrdenRow;
                                    $titleOrden = 'Ver / editar orden vinculada';
                                } else {
                                    $qOrden = 'nrohc=' . $nroHcRow . '&fecha=' . rawurlencode($fecha);
                                    if ($idDocRow > 0) {
                                        $qOrden .= '&doctor=' . $idDocRow;
                                    }
                                    $hrefOrden = '/orden_form.php?' . $qOrden;
                                    $titleOrden = 'Nueva orden para este paciente y turno';
                                }
                                ?>
                                <?php if ($extAgenda): ?>
                                    <form action="/agenda.php?a=quick_status" method="post" class="table-action-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                                        <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
                                        <input type="hidden" name="accion" value="llego">
                                        <button type="submit" class="btn btn-sm btn-ghost btn-icon" title="Marcar llegó"><i class="bi bi-person-check"></i><span class="btn-label"> Llegó</span></button>
                                    </form>
                                    <form action="/agenda.php?a=quick_status" method="post" class="table-action-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                                        <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
                                        <input type="hidden" name="accion" value="atendido">
                                        <button type="submit" class="btn btn-sm btn-ghost btn-icon" title="Marcar atendido"><i class="bi bi-check2-square"></i><span class="btn-label"> Atendido</span></button>
                                    </form>
                                    <form action="/agenda.php?a=quick_status" method="post" class="table-action-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                        <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                                        <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
                                        <input type="hidden" name="accion" value="ausente">
                                        <button type="submit" class="btn btn-sm btn-ghost btn-icon" title="Marcar no asistió"><i class="bi bi-person-x"></i><span class="btn-label"> Ausente</span></button>
                                    </form>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-ghost btn-icon" title="<?= h($titleOrden) ?>" href="<?= h($hrefOrden) ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span class="btn-label"> Orden</span></a>
                                <a class="btn btn-sm btn-ghost btn-icon" title="Editar turno" href="/turno_form.php?id=<?= (int) $r['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                <form action="/turno_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar este turno?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i><span class="btn-label"> Eliminar</span></button>
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
            <h2>Detalle del turno</h2>
            <?php if ($turnoSel): ?>
                <p><strong>Hora:</strong> <?= $turnoSel['hora'] ? h(substr((string) $turnoSel['hora'], 0, 5)) : '—' ?></p>
                <p><strong>Paciente:</strong> <?= h((string) ($turnoSel['paciente_nombre'] ?? '—')) ?></p>
                <p><strong>Nro HC:</strong> <?= (int) ($turnoSel['NroHC'] ?? 0) ?></p>
                <p><strong>Profesional:</strong> <?= h((string) ($turnoSel['doctor_nombre'] ?? '—')) ?></p>
                <p><strong>Estado:</strong> <?= h((string) ($turnoSel['estado'] ?? '—')) ?></p>
                <?php if ($extAgenda): ?>
                    <p class="muted small">
                        Llegó: <?= !empty($turnoSel['llegado']) ? 'Sí' : 'No' ?> ·
                        Confirmado: <?= !empty($turnoSel['confirmado']) ? 'Sí' : 'No' ?> ·
                        Atendido: <?= !empty($turnoSel['atendido']) ? 'Sí' : 'No' ?>
                    </p>
                <?php endif; ?>
                <p><strong>Obs.:</strong><br><?= h((string) ($turnoSel['observaciones'] ?? '—')) ?></p>
                <p class="agenda-side-actions">
                    <a class="btn btn-sm btn-primary" href="/turno_form.php?id=<?= (int) $turnoSel['id'] ?>">Editar turno</a>
                </p>
            <?php else: ?>
                <p class="muted">Seleccioná un turno con click en la fila (o Enter desde teclado) para ver el detalle rápido, como en la pantalla del exe.</p>
            <?php endif; ?>
        </aside>
    </div>
</div>

