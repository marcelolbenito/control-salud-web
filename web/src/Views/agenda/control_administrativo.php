<?php

declare(strict_types=1);

/** @var string $fecha */
/** @var string $fechaPrev */
/** @var string $fechaNext */
/** @var int $doctorFiltro */
/** @var string $filtro */
/** @var list<array<string,mixed>> $doctores */
/** @var list<array<string,mixed>> $rows */
/** @var array<string,int> $resumen */
/** @var bool $agendaDisponible */

$fmtMoney = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '0,00';
    }

    return number_format((float) $v, 2, ',', '.');
};
$filtroLabels = [
    'todos' => 'Todos los turnos',
    'atendidos_sin_gestion' => 'Atendidos sin orden ni pago',
    'atendidos_sin_orden' => 'Atendidos sin orden',
    'atendidos_sin_pago' => 'Atendidos sin pago',
    'pagos_sin_caja' => 'Pagos sin caja',
    'llegados_sin_gestion' => 'Llegaron sin gestión',
    'con_orden_sin_pago' => 'Con orden sin pago',
];
$doctorQs = $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '';
?>
<div class="container container-wide">
    <div class="page-head">
        <div>
            <h1>Control diario administrativo</h1>
            <p class="muted">Revisión del día para detectar turnos sin orden, sin pago o sin movimiento de caja.</p>
        </div>
        <p class="muted"><a href="/agenda.php?fecha=<?= rawurlencode($fecha) ?><?= $doctorQs ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Agenda</a></p>
    </div>

    <?php if (!$agendaDisponible): ?>
        <p class="alert alert-error">Falta la tabla <code>agenda_turnos</code>.</p>
    <?php else: ?>
        <form method="get" class="agenda-filters form-card" action="/control_administrativo.php">
            <div class="filter-row">
                <label>
                    Fecha
                    <input type="date" name="fecha" value="<?= h($fecha) ?>">
                </label>
                <div class="agenda-day-nav">
                    <a class="btn btn-ghost btn-sm" href="/control_administrativo.php?fecha=<?= rawurlencode($fechaPrev) ?><?= $doctorQs ?>&filtro=<?= h($filtro) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i> Día anterior</a>
                    <a class="btn btn-ghost btn-sm" href="/control_administrativo.php?fecha=<?= rawurlencode($fechaNext) ?><?= $doctorQs ?>&filtro=<?= h($filtro) ?>"><i class="bi bi-chevron-right" aria-hidden="true"></i> Día siguiente</a>
                </div>
                <label>
                    Profesional
                    <select name="doctor">
                        <option value="0">Todos</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= $doctorFiltro === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Ver
                    <select name="filtro">
                        <?php foreach ($filtroLabels as $k => $label): ?>
                            <option value="<?= h($k) ?>"<?= $filtro === $k ? ' selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Revisar</button>
            </div>
        </form>

        <div class="agenda-summary">
            <a class="agenda-kpi agenda-kpi-link" href="/control_administrativo.php?fecha=<?= rawurlencode($fecha) ?><?= $doctorQs ?>&filtro=todos"><span>Total</span><strong><?= (int) $resumen['total'] ?></strong></a>
            <a class="agenda-kpi agenda-kpi-link" href="/control_administrativo.php?fecha=<?= rawurlencode($fecha) ?><?= $doctorQs ?>&filtro=atendidos_sin_gestion"><span>Sin orden/pago</span><strong><?= (int) $resumen['atendidos_sin_gestion'] ?></strong></a>
            <a class="agenda-kpi agenda-kpi-link" href="/control_administrativo.php?fecha=<?= rawurlencode($fecha) ?><?= $doctorQs ?>&filtro=atendidos_sin_orden"><span>Sin orden</span><strong><?= (int) $resumen['atendidos_sin_orden'] ?></strong></a>
            <a class="agenda-kpi agenda-kpi-link" href="/control_administrativo.php?fecha=<?= rawurlencode($fecha) ?><?= $doctorQs ?>&filtro=atendidos_sin_pago"><span>Sin pago</span><strong><?= (int) $resumen['atendidos_sin_pago'] ?></strong></a>
            <a class="agenda-kpi agenda-kpi-link" href="/control_administrativo.php?fecha=<?= rawurlencode($fecha) ?><?= $doctorQs ?>&filtro=pagos_sin_caja"><span>Pago sin caja</span><strong><?= (int) $resumen['pagos_sin_caja'] ?></strong></a>
            <a class="agenda-kpi agenda-kpi-link" href="/control_administrativo.php?fecha=<?= rawurlencode($fecha) ?><?= $doctorQs ?>&filtro=llegados_sin_gestion"><span>Llegó sin gestión</span><strong><?= (int) $resumen['llegados_sin_gestion'] ?></strong></a>
        </div>

        <div class="page-actions">
            <a class="btn btn-ghost" href="/caja.php?fecha_desde=<?= rawurlencode($fecha) ?>&fecha_hasta=<?= rawurlencode($fecha) ?>"><i class="bi bi-cash-coin" aria-hidden="true"></i> Ver caja del día</a>
            <a class="btn btn-ghost" href="/pagos.php?fecha_desde=<?= rawurlencode($fecha) ?>&fecha_hasta=<?= rawurlencode($fecha) ?>"><i class="bi bi-receipt" aria-hidden="true"></i> Ver pagos del día</a>
        </div>

        <?php if ($rows === []): ?>
            <p class="empty-state">No hay turnos para el filtro seleccionado.</p>
        <?php else: ?>
            <div class="table-wrap table-wrap-datatable">
                <table id="tbl-control-admin" class="table">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>HC</th>
                            <th>Profesional</th>
                            <th>Turno</th>
                            <th>Orden</th>
                            <th>Pago</th>
                            <th>Caja</th>
                            <th>Gestión</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <?php
                            $s = ControlAdministrativoController::estadoAdmin($r);
                            $turnoId = (int) ($r['id'] ?? 0);
                            $nroHc = (int) ($r['NroHC'] ?? 0);
                            $doctorId = (int) ($r['Doctor'] ?? 0);
                            $ordenId = (int) ($r['orden_referencia'] ?? 0);
                            $isNoAsistio = !empty($r['falta_turno']) || (string) ($r['estado'] ?? '') === 'no_asistio';
                            $estadoTurno = $isNoAsistio ? 'No asistió' : ($s['atendido'] ? 'Atendido' : ($s['llegado'] ? 'Llegó' : (string) ($r['estado'] ?? 'Pendiente')));
                            $gestion = 'OK';
                            $gestionClass = 'estado-atendido';
                            if ($isNoAsistio) {
                                $gestion = 'No aplica';
                                $gestionClass = 'estado-no_asistio';
                            } elseif (!$s['tiene_orden'] && !$s['tiene_pago']) {
                                $gestion = $s['atendido'] ? 'Falta orden o pago' : 'Sin gestión';
                                $gestionClass = 'estado-pendiente';
                            } elseif ($s['tiene_orden'] && !$s['tiene_pago']) {
                                $gestion = 'Orden sin pago';
                                $gestionClass = 'estado-pendiente';
                            } elseif (!$s['tiene_orden'] && $s['tiene_pago']) {
                                $gestion = 'Pago sin orden';
                                $gestionClass = 'estado-pendiente';
                            } elseif ($s['tiene_pago'] && !$s['tiene_caja']) {
                                $gestion = 'Pago sin caja';
                                $gestionClass = 'estado-pendiente';
                            }
                            $ordenUrl = $ordenId > 0
                                ? '/orden_form.php?id=' . $ordenId . '&turno=' . $turnoId
                                : '/orden_form.php?nrohc=' . $nroHc . '&fecha=' . rawurlencode($fecha) . ($doctorId > 0 ? '&doctor=' . $doctorId : '') . '&turno=' . $turnoId;
                            $pagosUrl = '/pagos.php?nrohc=' . $nroHc . '&fecha_desde=' . rawurlencode($fecha) . '&fecha_hasta=' . rawurlencode($fecha);
                            $cajaTexto = $ordenId > 0 ? 'Orden #' . $ordenId : 'Turno #' . $turnoId;
                            $cajaUrl = '/caja.php?fecha_desde=' . rawurlencode($fecha) . '&fecha_hasta=' . rawurlencode($fecha) . '&q=' . rawurlencode($cajaTexto);
                            ?>
                            <tr>
                                <td><?= !empty($r['hora']) ? h(substr((string) $r['hora'], 0, 5)) : '—' ?></td>
                                <td><?= h((string) ($r['paciente_nombre'] ?? '—')) ?></td>
                                <td><?= $nroHc ?></td>
                                <td><?= h(trim((string) ($r['doctor_nombre'] ?? '')) ?: '—') ?></td>
                                <td><span class="badge-estado estado-<?= $isNoAsistio ? 'no_asistio' : ($s['atendido'] ? 'atendido' : ($s['llegado'] ? 'llego' : 'pendiente')) ?>"><?= h($estadoTurno) ?></span></td>
                                <td><?= $s['tiene_orden'] ? '<a href="' . h($ordenUrl) . '">#' . $ordenId . '</a>' : '—' ?></td>
                                <td><a href="<?= h($pagosUrl) ?>"><?= $s['tiene_pago'] ? h($fmtMoney($r['pagos_total'] ?? 0)) : '—' ?></a></td>
                                <td><a href="<?= h($cajaUrl) ?>"><?= $s['tiene_caja'] ? h($fmtMoney($r['caja_total'] ?? 0)) : '—' ?></a></td>
                                <td><span class="badge-estado <?= h($gestionClass) ?>"><?= h($gestion) ?></span></td>
                                <td class="table-actions">
                                    <a class="btn btn-sm btn-primary" href="/recepcion_turno.php?turno=<?= $turnoId ?>"><i class="bi bi-person-vcard" aria-hidden="true"></i> Cobro / Orden</a>
                                    <a class="btn btn-sm btn-ghost btn-icon" title="<?= $ordenId > 0 ? 'Ver orden' : 'Cargar orden completa' ?>" href="<?= h($ordenUrl) ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span class="btn-label"> Orden</span></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
