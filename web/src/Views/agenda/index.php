<?php

declare(strict_types=1);
?>
<div class="container">
    <div class="page-head">
        <h1>Agenda diaria</h1>
        <p class="muted">Vista operativa por fecha y profesional, con acciones rápidas tipo escritorio.</p>
        <?php if (auth_user_role(auth_user()) === 'doctor' && $doctores === []): ?>
            <p class="alert alert-error">Tu usuario doctor no tiene profesional vinculado. Pedí al administrador que asocie un doctor en Sistema > Usuarios.</p>
        <?php endif; ?>
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
                <a class="btn btn-ghost btn-sm" href="/agenda.php?fecha=<?= rawurlencode($fechaPrev) ?><?= $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '' ?><?= $consultorio !== '' ? '&consultorio=' . rawurlencode($consultorio) : '' ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i> Día anterior</a>
                <a class="btn btn-ghost btn-sm" href="/agenda.php?fecha=<?= rawurlencode($fechaNext) ?><?= $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '' ?><?= $consultorio !== '' ? '&consultorio=' . rawurlencode($consultorio) : '' ?>"><i class="bi bi-chevron-right" aria-hidden="true"></i> Día siguiente</a>
            </div>
            <label>
                Profesional
                <select name="doctor">
                    <?php if (auth_user_role(auth_user()) !== 'doctor'): ?>
                    <option value="0">Todos</option>
                    <?php endif; ?>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"<?= $doctorFiltro === (int) $d['id'] ? ' selected' : '' ?>>
                            <?= h($d['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Ver</button>
            <label>
                Consultorio llamado
                <input type="text" name="consultorio" value="<?= h($consultorio) ?>" maxlength="40" placeholder="Consultorio 1">
            </label>
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
        <a class="btn btn-ghost" href="/agenda_bloqueos.php?fd=<?= rawurlencode($fecha) ?>&fh=<?= rawurlencode($fecha) ?><?= $doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '' ?>"><i class="bi bi-calendar-x" aria-hidden="true"></i> Bloqueos</a>
        <a class="btn btn-ghost" href="/anunciador.php" target="_blank" rel="noopener"><i class="bi bi-megaphone" aria-hidden="true"></i> Anunciador</a>
        <?php if (auth_user_role(auth_user()) !== 'doctor'): ?>
            <a class="btn btn-ghost" href="/control_administrativo.php?fecha=<?= urlencode($fecha) ?><?= $doctorFiltro > 0 ? '&doctor=' . (int) $doctorFiltro : '' ?>"><i class="bi bi-clipboard2-check" aria-hidden="true"></i> Control diario</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="/ordenes.php"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Órdenes</a>
        <a class="btn btn-primary" title="Crear una orden fuera del circuito del turno" href="/orden_form.php?fecha=<?= urlencode($fecha) ?><?= $doctorFiltro > 0 ? '&doctor=' . (int) $doctorFiltro : '' ?>"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Nueva orden manual</a>
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
                        $estadoUi = (string) ($r['estado'] ?? 'pendiente');
                        if ($extAgenda) {
                            if (!empty($r['atendido'])) {
                                $estadoUi = 'atendido';
                            } elseif (!empty($r['falta_turno'])) {
                                $estadoUi = 'no_asistio';
                            } elseif (!empty($r['llegado'])) {
                                $estadoUi = 'llego';
                            }
                        }
                        $estadoSlug = preg_replace('/[^a-z_]/i', '', $estadoUi) ?: 'pendiente';
                        $turnoLink = '/agenda.php?fecha=' . rawurlencode($fecha)
                            . ($doctorFiltro > 0 ? '&doctor=' . $doctorFiltro : '')
                            . '&turno=' . (int) $r['id'];
                        $trClass = 'agenda-row estado-' . $estadoSlug;
                        $isAtendidoRow = !empty($r['atendido']) || (string) ($r['estado'] ?? '') === 'atendido';
                        if ($extAgenda && !empty($r['llegado']) && !$isAtendidoRow) {
                            $trClass .= ' agenda-row-llegado';
                        }
                        if ($extAgenda && $isAtendidoRow) {
                            $trClass .= ' agenda-row-atendido';
                        }
                        if ($turnoSel && (int) $turnoSel['id'] === (int) $r['id']) {
                            $trClass .= ' is-selected';
                        }
                        $pacienteId = (int) ($r['paciente_id'] ?? 0);
                        $nroHc = (int) ($r['NroHC'] ?? 0);
                        $puedeEditarPaciente = auth_user_role(auth_user()) !== 'doctor';
                        $pacienteUrl = $puedeEditarPaciente && $pacienteId > 0
                            ? '/paciente_form.php?id=' . $pacienteId
                            : '/pacientes.php?nrohc=' . $nroHc;
                        ?>
                        <tr
                            class="<?= h($trClass) ?> agenda-row-selectable"
                            data-turno-link="<?= h($turnoLink) ?>"
                            tabindex="0"
                            role="link"
                            aria-label="Ver detalle del turno de <?= h((string) ($r['paciente_nombre'] ?? 'paciente')) ?>"
                        >
                            <td><?= $r['hora'] ? h(substr((string) $r['hora'], 0, 5)) : '—' ?></td>
                            <td><a class="agenda-paciente-link" href="<?= h($pacienteUrl) ?>" target="_blank" rel="noopener" title="Abrir ficha del paciente"><i class="bi bi-person" aria-hidden="true"></i> <?= h($r['paciente_nombre'] ?? '') ?></a></td>
                            <td><?= $nroHc ?></td>
                            <td><?= h($r['doctor_nombre'] ?? '—') ?></td>
                            <td><span class="badge-estado estado-<?= h($estadoSlug) ?>"><?= h((string) $estadoUi) ?></span></td>
                            <?php if ($extAgenda): ?>
                                <td title="Atendido"><?= !empty($r['atendido']) ? 'Sí' : '—' ?></td>
                                <td title="Llegó"><?= !empty($r['llegado']) ? 'Sí' : '—' ?></td>
                                <td title="Confirmado"><?= !empty($r['confirmado']) ? 'Sí' : '—' ?></td>
                                <td title="Faltó"><?= !empty($r['falta_turno']) ? 'Sí' : '—' ?></td>
                            <?php endif; ?>
                            <td><?= $r['idorden'] !== null ? (int) $r['idorden'] : '—' ?></td>
                            <td class="cell-clip" title="<?= h($obs) ?>">
                                <button
                                    type="button"
                                    class="agenda-obs-btn"
                                    data-turno-id="<?= (int) $r['id'] ?>"
                                    data-paciente="<?= h((string) ($r['paciente_nombre'] ?? 'paciente')) ?>"
                                    data-observaciones="<?= h($obs) ?>"
                                    title="Editar observaciones"
                                ><?= $obsOut !== '' ? h($obsOut) : '<span class="muted">Agregar obs.</span>' ?></button>
                            </td>
                            <td class="table-actions">
                                <?php
                                $isLlegado = $extAgenda && !empty($r['llegado']);
                                $isAtendido = !empty($r['atendido']) || ((string) ($r['estado'] ?? '') === 'atendido');
                                $isNoAsistio = !empty($r['falta_turno']) || ((string) ($r['estado'] ?? '') === 'no_asistio');
                                $fueLlamado = !empty($r['fue_llamado']);
                                $llamadoActivo = !empty($r['llamado_activo']);
                                ?>
                                <?php if (!$isNoAsistio): ?>
                                    <a class="btn btn-sm btn-primary" title="Registrar cobro u orden del turno" href="/recepcion_turno.php?turno=<?= (int) $r['id'] ?>"><i class="bi bi-person-vcard" aria-hidden="true"></i> Cobro / Orden</a>
                                <?php endif; ?>
                                <?php if ($extAgenda): ?>
                                    <?php if (!$isAtendido): ?>
                                        <?php if (!$isLlegado): ?>
                                            <form action="/agenda.php?a=quick_status" method="post" class="table-action-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                                                <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
                                                <input type="hidden" name="consultorio" value="<?= h($consultorio) ?>">
                                                <input type="hidden" name="accion" value="llego">
                                                <button type="submit" class="btn btn-sm btn-ghost btn-icon" title="Marcar llegó"><i class="bi bi-person-check"></i><span class="btn-label"> Llegó</span></button>
                                            </form>
                                            <form action="/agenda.php?a=quick_status" method="post" class="table-action-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                                                <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
                                                <input type="hidden" name="consultorio" value="<?= h($consultorio) ?>">
                                                <input type="hidden" name="accion" value="ausente">
                                                <button type="submit" class="btn btn-sm btn-ghost btn-icon" title="Marcar no asistió"><i class="bi bi-person-x"></i><span class="btn-label"> Ausente</span></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($fueLlamado): ?>
                                            <form action="/agenda.php?a=quick_status" method="post" class="table-action-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                                                <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
                                                <input type="hidden" name="consultorio" value="<?= h($consultorio) ?>">
                                                <input type="hidden" name="accion" value="atendido">
                                                <button type="submit" class="btn btn-sm btn-ghost btn-icon" title="Marcar atendido"><i class="bi bi-check2-square"></i><span class="btn-label"> Atendido</span></button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($isLlegado && !$llamadoActivo): ?>
                                            <form action="/agenda.php?a=quick_status" method="post" class="table-action-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                                <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                                                <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
                                                <input type="hidden" name="consultorio" value="<?= h($consultorio) ?>">
                                                <input type="hidden" name="accion" value="llamar">
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-primary btn-icon"
                                                    title="Llamar en sala"
                                                ><i class="bi bi-megaphone"></i><span class="btn-label"> Llamar</span></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (!$isAtendidoRow): ?>
                                    <?php if (!$isLlegado): ?>
                                        <a class="btn btn-sm btn-ghost btn-icon" title="Editar turno" href="/turno_form.php?id=<?= (int) $r['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                        <form action="/turno_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar este turno?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i><span class="btn-label"> Eliminar</span></button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
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
                    <a class="btn btn-sm btn-primary" href="/turno_form.php?id=<?= (int) $turnoSel['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar turno</a>
                </p>
            <?php else: ?>
                <p class="muted">Seleccioná un turno con click en la fila (o Enter desde teclado) para ver el detalle rápido, como en la pantalla del exe.</p>
            <?php endif; ?>
        </aside>
    </div>
</div>

<div class="agenda-modal" id="agenda-obs-modal" hidden>
    <div class="agenda-modal-backdrop" data-agenda-obs-close></div>
    <div class="agenda-modal-card" role="dialog" aria-modal="true" aria-labelledby="agenda-obs-title">
        <form method="post" action="/agenda.php?a=observaciones">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="agenda_obs_id" value="">
            <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
            <input type="hidden" name="doctor" value="<?= (int) $doctorFiltro ?>">
            <input type="hidden" name="consultorio" value="<?= h($consultorio) ?>">
            <header class="agenda-modal-head">
                <div class="agenda-modal-title">
                    <span class="agenda-modal-icon"><i class="bi bi-chat-left-text" aria-hidden="true"></i></span>
                    <div>
                    <h2 id="agenda-obs-title">Observaciones del turno</h2>
                    <p class="muted" id="agenda_obs_paciente">Paciente</p>
                </div>
                </div>
                <button type="button" class="agenda-modal-close" data-agenda-obs-close aria-label="Cerrar"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </header>
            <label class="agenda-modal-label">Notas visibles en Agenda
                <textarea name="observaciones" id="agenda_obs_texto" rows="7" maxlength="2000" placeholder="Notas visibles desde Agenda"></textarea>
            </label>
            <p class="muted small agenda-modal-hint">Usalo para comentarios administrativos breves del turno. Para datos clínicos, usar Historia clínica.</p>
            <div class="agenda-modal-actions">
                <button type="button" class="btn btn-ghost" data-agenda-obs-close>Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar observaciones</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('agenda-obs-modal');
    var idInput = document.getElementById('agenda_obs_id');
    var textInput = document.getElementById('agenda_obs_texto');
    var paciente = document.getElementById('agenda_obs_paciente');
    if (!modal || !idInput || !textInput || !paciente) return;

    function openModal(btn) {
        idInput.value = btn.getAttribute('data-turno-id') || '';
        textInput.value = btn.getAttribute('data-observaciones') || '';
        paciente.textContent = btn.getAttribute('data-paciente') || 'Paciente';
        modal.hidden = false;
        textInput.focus();
    }

    function closeModal() {
        modal.hidden = true;
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.agenda-obs-btn');
        if (btn) {
            event.preventDefault();
            openModal(btn);
            return;
        }
        if (event.target.closest('[data-agenda-obs-close]')) {
            event.preventDefault();
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>

