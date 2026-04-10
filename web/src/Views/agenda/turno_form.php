<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="<?= h($volver) ?>">← Volver a la agenda</a></p>
    </div>
    <?php if (!$ext): ?>
        <p class="alert alert-error" style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
            Para usar campos adicionales (llegó, confirmado, motivo, etc.), ejecutá <code>sql/migration_003_doctores_agenda_exe.sql</code>.
        </p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">

        <section class="form-section">
            <h2 class="form-section-title">Turno</h2>
            <div class="form-grid-ext">
                <label>Fecha *
                    <input type="date" name="Fecha" required value="<?= h((string) $row['Fecha']) ?>">
                </label>
                <label>Hora
                    <input type="time" name="hora" value="<?= h((string) $row['hora']) ?>">
                </label>
                <label>Nro HC *
                    <input type="number" name="NroHC" required min="1" value="<?= $row['NroHC'] === '' ? '' : (int) $row['NroHC'] ?>">
                </label>
                <label class="span-2">Buscar paciente (DNI y/o nombre)
                    <input type="text" id="turno-paciente-buscar" autocomplete="off" placeholder="Ej: 30111222 o Perez Ana">
                    <small class="muted">Elegí un resultado para completar Nro HC automáticamente.</small>
                    <div id="turno-paciente-resultados" class="turno-paciente-resultados" hidden></div>
                </label>
                <?php if ($ext): ?>
                    <label class="span-2">Nombre paciente (texto)
                        <input type="text" name="paciente_nombre" value="<?= h((string) ($row['paciente_nombre'] ?? '')) ?>" maxlength="60" placeholder="Se puede completar solo al guardar desde el paciente">
                    </label>
                <?php endif; ?>
                <label>Profesional *
                    <select name="Doctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) $row['Doctor'] === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>ID orden
                    <input type="number" name="idorden" min="1" value="<?= h((string) $row['idorden']) ?>">
                </label>
                <label>Estado (resumen web)
                    <select name="estado">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= h($e) ?>"<?= $row['estado'] === $e ? ' selected' : '' ?>><?= h($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($ext): ?>
                    <label>Motivo (id — Lista Motivos)
                        <input type="number" name="motivo" value="<?= $row['motivo'] !== null && $row['motivo'] !== '' ? (int) $row['motivo'] : '' ?>">
                    </label>
                <?php endif; ?>
            </div>
            <?php
            $dispSource = $dispSource ?? 'default';
            $dispSinFranjaDia = !empty($dispSinFranjaDia);
            $dispStep = (int) ($dispStep ?? 15);
            $turnoExcludeId = (int) ($row['id'] ?? 0);
            $canDispGrid = (int) ($row['Doctor'] ?? 0) > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($row['Fecha'] ?? ''));
            $dispHintIni = '';
            if ($canDispGrid) {
                if ($dispSinFranjaDia && ($dispSource === 'legacy' || $dispSource === 'legacy_no_day')) {
                    $dispHintIni = 'Para este día no hay franja mañana/tarde cargada en la planilla: se muestran horarios de referencia para que puedas cargar el turno.';
                } elseif ($dispSource === 'legacy_sparse') {
                    $dispHintIni = 'La franja del profesional es limitada/incompleta para esta fecha: se muestran también horarios de referencia para facilitar la carga.';
                } elseif ($dispSource === 'legacy') {
                    $dispHintIni = 'Intervalo entre turnos según planilla: ' . $dispStep . ' minutos.';
                } elseif ($dispSource === 'default') {
                    $dispHintIni = 'No hay planilla de horarios vigente para este profesional en esta fecha: se muestran franjas de referencia (cada ' . $dispStep . ' min).';
                }
            }
            ?>
            <div class="turno-disponibilidad" id="turno-disponibilidad"
                 data-slots-url="/agenda_slots.php"
                 data-exclude-id="<?= $turnoExcludeId ?>"
                 style="">
                <p class="muted small" id="turno-disp-lead"><strong>Disponibilidad por profesional:</strong> la grilla usa los horarios cargados para ese médico (tabla de planilla). Verde = libre, rojo = ocupado (clic para ver/anular), azul = seleccionada.</p>
                <p class="muted small" id="turno-disp-hint"><?= h($dispHintIni) ?></p>
                <div class="turno-slots" id="turno-slots-grid">
                    <?php if ($dispSlots !== []): ?>
                        <?php foreach ($dispSlots as $slot): ?>
                            <?php
                            $ocup = (int) ($dispOcupadas[$slot] ?? 0);
                            $isSel = $horaSel === $slot;
                            $cls = $isSel ? 'is-selected' : ($ocup > 0 ? 'is-occupied' : 'is-free');
                            ?>
                            <button type="button"
                                    class="turno-slot <?= h($cls) ?>"
                                    data-slot="<?= h($slot) ?>">
                                <span><?= h($slot) ?></span>
                                <?php if ($ocup > 0): ?><small><?= $ocup ?></small><?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div id="turno-ocupados-detalle" class="turno-ocupados-detalle" hidden></div>
            </div>
            <script>
                (function () {
                    const root = document.getElementById('turno-disponibilidad');
                    const grid = document.getElementById('turno-slots-grid');
                    const hint = document.getElementById('turno-disp-hint');
                    const fechaInp = document.querySelector('input[name="Fecha"]');
                    const doctorSel = document.querySelector('select[name="Doctor"]');
                    const horaInput = document.querySelector('input[name="hora"]');
                    const nroHcInput = document.querySelector('input[name="NroHC"]');
                    const pacienteNombreInput = document.querySelector('input[name="paciente_nombre"]');
                    const pacienteBuscarInput = document.getElementById('turno-paciente-buscar');
                    const pacienteResultados = document.getElementById('turno-paciente-resultados');
                    if (!root || !grid || !fechaInp || !doctorSel || !horaInput) return;

                    const urlBase = root.getAttribute('data-slots-url') || '/agenda_slots.php';
                    const urlHoraDetalle = '/agenda_turnos_hora.php';
                    const urlAnularTurno = '/agenda_turno_anular.php';
                    const excludeId = root.getAttribute('data-exclude-id') || '0';
                    const ocupadosDetalle = document.getElementById('turno-ocupados-detalle');

                    function hintText(payload) {
                        if (!payload) return '';
                        if (payload.sin_franja_dia && (payload.source === 'legacy' || payload.source === 'legacy_no_day')) {
                            return 'Para este día no hay franja mañana/tarde cargada en la planilla: se muestran horarios de referencia para que puedas cargar el turno.';
                        }
                        if (payload.source === 'legacy') {
                            return 'Intervalo entre turnos según planilla: ' + payload.step + ' minutos.';
                        }
                        if (payload.source === 'legacy_sparse') {
                            return 'La franja del profesional es limitada/incompleta para esta fecha: se muestran tambien horarios de referencia para facilitar la carga.';
                        }
                        if (payload.source === 'default') {
                            return 'No hay planilla de horarios vigente para este profesional en esta fecha: se muestran franjas de referencia (cada ' + payload.step + ' min).';
                        }
                        return '';
                    }

                    function renderSlots(slots, occupied, selectedHora) {
                        grid.innerHTML = '';
                        slots.forEach((slot) => {
                            const ocup = parseInt(String(occupied[slot] || 0), 10) || 0;
                            const isSel = selectedHora === slot;
                            const cls = isSel ? 'is-selected' : (ocup > 0 ? 'is-occupied' : 'is-free');
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'turno-slot ' + cls;
                            btn.setAttribute('data-slot', slot);
                            const sp = document.createElement('span');
                            sp.textContent = slot;
                            btn.appendChild(sp);
                            if (ocup > 0) {
                                const sm = document.createElement('small');
                                sm.textContent = String(ocup);
                                btn.appendChild(sm);
                            }
                            btn.addEventListener('click', () => {
                                if (ocup > 0) {
                                    verTurnosEnHora(slot);
                                    return;
                                }
                                horaInput.value = slot;
                                grid.querySelectorAll('.turno-slot').forEach((b) => b.classList.remove('is-selected'));
                                btn.classList.add('is-selected');
                                if (ocupadosDetalle) {
                                    ocupadosDetalle.hidden = true;
                                    ocupadosDetalle.innerHTML = '';
                                }
                            });
                            grid.appendChild(btn);
                        });
                    }

                    async function verTurnosEnHora(slot) {
                        if (!ocupadosDetalle) return;
                        const fecha = fechaInp.value || '';
                        const doctor = parseInt(String(doctorSel.value || '0'), 10) || 0;
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha) || doctor < 1) return;
                        ocupadosDetalle.hidden = false;
                        ocupadosDetalle.innerHTML = '<div class="muted small">Cargando turnos de las ' + slot + '...</div>';
                        const u = urlHoraDetalle + '?fecha=' + encodeURIComponent(fecha) + '&doctor=' + encodeURIComponent(String(doctor)) + '&hora=' + encodeURIComponent(slot);
                        try {
                            const res = await fetch(u, { credentials: 'same-origin' });
                            const data = await res.json();
                            if (!data || !data.ok) throw new Error('bad');
                            const items = data.items || [];
                            if (!items.length) {
                                ocupadosDetalle.innerHTML = '<div class="muted small">No se encontraron turnos en esa hora.</div>';
                                return;
                            }
                            let html = '<div class="turno-ocupados-head"><strong>Turnos en ' + slot + '</strong></div>';
                            html += '<div class="turno-ocupados-list">';
                            items.forEach((it) => {
                                html += '<div class="turno-ocupado-item">';
                                html += '<div><strong>HC ' + String(it.nrohc || 0) + '</strong> - ' + (it.paciente || '(sin nombre)') + ' <span class="muted">(' + (it.estado || 'pendiente') + ')</span></div>';
                                html += '<div class="turno-ocupado-actions">';
                                html += '<a class="btn btn-sm btn-ghost" href="/turno_form.php?id=' + encodeURIComponent(String(it.id)) + '">Editar</a>';
                                html += '<button type="button" class="btn btn-sm btn-danger" data-anular-id="' + String(it.id) + '" data-anular-hora="' + slot + '">Anular</button>';
                                html += '</div>';
                                html += '</div>';
                            });
                            html += '</div>';
                            ocupadosDetalle.innerHTML = html;
                            ocupadosDetalle.querySelectorAll('button[data-anular-id]').forEach((btn) => {
                                btn.addEventListener('click', async () => {
                                    const id = parseInt(String(btn.getAttribute('data-anular-id') || '0'), 10) || 0;
                                    const h = String(btn.getAttribute('data-anular-hora') || slot);
                                    if (id < 1) return;
                                    if (!window.confirm('¿Deseás anular este turno?')) return;
                                    btn.disabled = true;
                                    try {
                                        const fd = new FormData();
                                        fd.append('id', String(id));
                                        const rr = await fetch(urlAnularTurno, {
                                            method: 'POST',
                                            body: fd,
                                            credentials: 'same-origin'
                                        });
                                        const jd = await rr.json();
                                        if (!jd || !jd.ok) throw new Error('bad');
                                        await refreshDisp();
                                        await verTurnosEnHora(h);
                                    } catch (e) {
                                        alert('No se pudo anular el turno. Reintentá.');
                                    } finally {
                                        btn.disabled = false;
                                    }
                                });
                            });
                        } catch (e) {
                            ocupadosDetalle.innerHTML = '<div class="muted small">No se pudo cargar el detalle de esa hora.</div>';
                        }
                    }

                    async function refreshDisp() {
                        const fecha = fechaInp.value || '';
                        const doctor = parseInt(String(doctorSel.value || '0'), 10) || 0;
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha) || doctor < 1) {
                            if (hint) hint.textContent = 'Seleccioná un profesional para ver disponibilidad específica; la grilla base queda visible para referencia.';
                            if (ocupadosDetalle) {
                                ocupadosDetalle.hidden = true;
                                ocupadosDetalle.innerHTML = '';
                            }
                            return;
                        }
                        if (hint) hint.textContent = 'Actualizando…';
                        const u = urlBase + '?fecha=' + encodeURIComponent(fecha) + '&doctor=' + encodeURIComponent(String(doctor)) + '&exclude_id=' + encodeURIComponent(excludeId);
                        try {
                            const res = await fetch(u, { credentials: 'same-origin' });
                            const data = await res.json();
                            if (!data || !data.ok) throw new Error('bad');
                            if (hint) hint.textContent = hintText(data);
                            const sel = (horaInput.value || '').trim().substring(0, 5);
                            if (data.slots && data.slots.length) {
                                renderSlots(data.slots, data.occupied || {}, sel);
                            } else {
                                grid.innerHTML = '';
                                if (hint) {
                                    const ht = hintText(data);
                                    hint.textContent = ht || 'No hay huecos en la grilla; podés cargar la hora manualmente.';
                                }
                            }
                        } catch (e) {
                            if (hint) hint.textContent = 'No se pudo cargar la disponibilidad. Reintentá o usá el campo Hora manual.';
                        }
                    }

                    fechaInp.addEventListener('change', refreshDisp);
                    doctorSel.addEventListener('change', refreshDisp);
                    // Asegura que la grilla inicial también quede clickeable al abrir el formulario.
                    refreshDisp();

                    function clearPacienteResultados() {
                        if (!pacienteResultados) return;
                        pacienteResultados.hidden = true;
                        pacienteResultados.innerHTML = '';
                    }

                    function renderPacienteResultados(items) {
                        if (!pacienteResultados) return;
                        pacienteResultados.innerHTML = '';
                        if (!items || !items.length) {
                            pacienteResultados.hidden = false;
                            pacienteResultados.innerHTML = '<div class="turno-paciente-item muted">Sin resultados.</div>';
                            return;
                        }
                        items.forEach((it) => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'turno-paciente-item';
                            btn.innerHTML = '<strong>HC ' + String(it.nrohc || '') + '</strong> - ' + (it.nombre || '(sin nombre)') + (it.dni ? ' - DNI ' + it.dni : '');
                            btn.addEventListener('click', () => {
                                if (nroHcInput) nroHcInput.value = String(it.nrohc || '');
                                if (pacienteNombreInput && it.nombre) pacienteNombreInput.value = String(it.nombre);
                                if (pacienteBuscarInput) pacienteBuscarInput.value = it.nombre ? (it.nombre + (it.dni ? ' - DNI ' + it.dni : '')) : ('HC ' + String(it.nrohc || ''));
                                clearPacienteResultados();
                            });
                            pacienteResultados.appendChild(btn);
                        });
                        pacienteResultados.hidden = false;
                    }

                    let tSearch = null;
                    async function buscarPacienteTurno() {
                        if (!pacienteBuscarInput || !pacienteResultados) return;
                        const q = (pacienteBuscarInput.value || '').trim();
                        if (q.length < 2) {
                            clearPacienteResultados();
                            return;
                        }
                        pacienteResultados.hidden = false;
                        pacienteResultados.innerHTML = '<div class="turno-paciente-item muted">Buscando...</div>';
                        try {
                            const u = '/pacientes_lookup.php?q=' + encodeURIComponent(q);
                            const res = await fetch(u, { credentials: 'same-origin' });
                            const data = await res.json();
                            if (!data || !data.ok) throw new Error('bad');
                            renderPacienteResultados(data.items || []);
                        } catch (e) {
                            pacienteResultados.hidden = false;
                            pacienteResultados.innerHTML = '<div class="turno-paciente-item muted">No se pudo buscar ahora.</div>';
                        }
                    }

                    if (pacienteBuscarInput) {
                        pacienteBuscarInput.addEventListener('input', () => {
                            if (tSearch) clearTimeout(tSearch);
                            tSearch = setTimeout(buscarPacienteTurno, 220);
                        });
                        pacienteBuscarInput.addEventListener('blur', () => {
                            setTimeout(clearPacienteResultados, 200);
                        });
                        pacienteBuscarInput.addEventListener('focus', () => {
                            if ((pacienteBuscarInput.value || '').trim().length >= 2) {
                                buscarPacienteTurno();
                            }
                        });
                    }
                })();
            </script>
        </section>

        <?php if ($ext): ?>
        <section class="form-section">
            <h2 class="form-section-title">Flags</h2>
            <div class="form-grid-ext">
                <label class="form-check"><input type="checkbox" name="atendido" value="1" <?= !empty($row['atendido']) ? ' checked' : '' ?>> Atendido</label>
                <label class="form-check"><input type="checkbox" name="pagado" value="1" <?= !empty($row['pagado']) ? ' checked' : '' ?>> Pagado</label>
                <label class="form-check"><input type="checkbox" name="llegado" value="1" <?= !empty($row['llegado']) ? ' checked' : '' ?>> Llegó</label>
                <label class="form-check"><input type="checkbox" name="confirmado" value="1" <?= !empty($row['confirmado']) ? ' checked' : '' ?>> Confirmado</label>
                <label class="form-check"><input type="checkbox" name="falta_turno" value="1" <?= !empty($row['falta_turno']) ? ' checked' : '' ?>> Faltó</label>
                <label class="form-check"><input type="checkbox" name="reingresar" value="1" <?= !empty($row['reingresar']) ? ' checked' : '' ?>> Reingresar</label>
                <label>Hora llegada
                    <input type="time" name="llegado_hora" value="<?= h((string) ($row['llegado_hora'] ?? '')) ?>">
                </label>
                <?php if (!empty($primeraVezOpts)): ?>
                    <label>Tipo de atención inicial
                        <select name="primera_vez">
                            <?php catalogo_select_options($primeraVezOpts, $row['primera_vez'] ?? null, '—') ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>Tipo de atención inicial (código)
                        <input type="number" name="primera_vez" value="<?= h((string) ($row['primera_vez'] ?? '')) ?>">
                    </label>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Sesión / caja</h2>
            <div class="form-grid-ext">
                <label>Sesión N°
                    <input type="number" name="num_sesion" value="<?= h((string) ($row['num_sesion'] ?? '')) ?>">
                </label>
                <label>Usuario asignó turno
                    <input type="text" name="usuario_asignado" value="<?= h((string) ($row['usuario_asignado'] ?? '')) ?>" maxlength="50">
                </label>
                <label>Fecha/hora asignado
                    <input type="datetime-local" name="fechahora_asignado" value="<?= h((string) ($row['fechahora_asignado'] ?? '')) ?>">
                </label>
            </div>

            <details class="form-advanced">
                <summary>Ver campos técnicos</summary>
                <div class="form-grid-ext" style="margin-top:.65rem;">
                    <label>Sesión (ID interno)
                        <input type="number" name="id_sesion" value="<?= h((string) ($row['id_sesion'] ?? '')) ?>">
                    </label>
                    <label>Caja (ID interno)
                        <input type="number" name="id_caja" value="<?= h((string) ($row['id_caja'] ?? '')) ?>">
                    </label>
                    <label class="form-check" style="align-self:end;">
                        <input type="checkbox" name="alta_paci_web" value="1" <?= !empty($row['alta_paci_web']) ? ' checked' : '' ?>>
                        Origen web
                    </label>
                </div>
            </details>
        </section>
        <?php endif; ?>

        <section class="form-section">
            <h2 class="form-section-title">Observaciones</h2>
            <div class="form-grid-ext">
                <label class="span-2">
                    <textarea name="observaciones" rows="3"><?= h((string) $row['observaciones']) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>">Cancelar</a>
        </div>
    </form>
</div>
