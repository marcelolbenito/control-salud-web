<?php

declare(strict_types=1);
?>
<div class="container">
    <div class="page-head">
        <h1>Agenda</h1>
        <p class="muted">Turnos por día y profesional.</p>
        <?php if (!$extAgenda): ?>
            <p class="muted" style="font-size:0.9rem;">Columnas At./Lleg./Conf. (como Access) aparecen tras <code>sql/migration_003_doctores_agenda_exe.sql</code>.</p>
        <?php endif; ?>
    </div>

    <form method="get" class="agenda-filters form-card" action="/agenda.php">
        <div class="filter-row">
            <label>
                Fecha
                <input type="date" name="fecha" value="<?= h($fecha) ?>">
            </label>
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

    <div class="page-actions">
        <a class="btn btn-primary" href="/turno_form.php?fecha=<?= urlencode($fecha) ?><?= $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '' ?>"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Nuevo turno</a>
        <a class="btn btn-ghost" href="/ordenes.php"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Órdenes</a>
        <a class="btn btn-primary" href="/orden_form.php?fecha=<?= urlencode($fecha) ?><?= $doctorFiltro > 0 ? '&doctor=' . (int) $doctorFiltro : '' ?>"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Nueva orden</a>
    </div>

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
                            <th>At.</th>
                            <th>Lleg.</th>
                            <th>Conf.</th>
                            <th>Falt.</th>
                        <?php endif; ?>
                        <th>Orden</th>
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
                        ?>
                        <tr>
                            <td><?= $r['hora'] ? h(substr((string) $r['hora'], 0, 5)) : '—' ?></td>
                            <td><?= h($r['paciente_nombre'] ?? '') ?></td>
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
                                <a class="btn btn-sm btn-ghost btn-icon" title="<?= h($titleOrden) ?>" href="<?= h($hrefOrden) ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span class="btn-label"> Orden</span></a>
                                <a class="btn btn-sm btn-ghost btn-icon" title="Editar turno" href="/turno_form.php?id=<?= (int) $r['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                <form action="/turno_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar este turno?');">
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
</div>

