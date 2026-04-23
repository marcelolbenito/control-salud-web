<?php

declare(strict_types=1);

/** @var array<string, mixed> $row */
/** @var string $error */
/** @var string $titulo */
/** @var string $volver */
/** @var list<array<string, mixed>> $doctores */
/** @var string $picked_slots_json JSON array de HH:MM para la grilla (doble clic) */
$todo = !empty($row['todo_dia']) && (string) $row['todo_dia'] === '1';
$pickedAttr = htmlspecialchars((string) ($picked_slots_json ?? '[]'), ENT_QUOTES, 'UTF-8');
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="<?= h($volver) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al listado</a></p>
    </div>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <input type="hidden" name="bloques_slots_json" id="bloques_slots_json" value="<?= $pickedAttr ?>">
        <input type="hidden" name="bloqueo_step_min" id="bloqueo_step_min" value="15">

        <section class="form-section">
            <h2 class="form-section-title">Bloqueo</h2>
            <div class="form-grid-ext">
                <label>Profesional *
                    <select name="doctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) ($row['doctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fecha desde *
                    <input type="date" name="fecha_desde" required value="<?= h((string) ($row['fecha_desde'] ?? '')) ?>">
                </label>
                <label>Fecha hasta *
                    <input type="date" name="fecha_hasta" required value="<?= h((string) ($row['fecha_hasta'] ?? '')) ?>">
                </label>
                <label class="form-check span-2">
                    <input type="checkbox" name="todo_dia" value="1" id="bloqueo_todo_dia"<?= $todo ? ' checked' : '' ?>>
                    Día completo (no atiende en ningún horario de esas fechas)
                    <small class="muted" style="display:block;margin-top:0.25rem;font-weight:400;">Si desmarcás esta opción, aparece la grilla (verde / rojo): <strong>doble clic</strong> en cada turno que quieras bloquear (se van sumando); otro doble clic en el mismo horario lo quita.</small>
                </label>
                <label class="bloqueo-horas">Hora desde
                    <input type="time" name="hora_desde" id="bloqueo_hora_desde" value="<?= h((string) ($row['hora_desde'] ?? '')) ?>"<?= $todo ? ' disabled' : '' ?>>
                </label>
                <label class="bloqueo-horas">Hora hasta
                    <input type="time" name="hora_hasta" id="bloqueo_hora_hasta" value="<?= h((string) ($row['hora_hasta'] ?? '')) ?>"<?= $todo ? ' disabled' : '' ?>>
                    <small class="muted">Fin exclusivo (ej. hasta 12:00 bloquea hasta las 11:45 si el paso es 15 min).</small>
                </label>
                <label class="span-2">Motivo (opcional)
                    <input type="text" name="motivo" maxlength="255" value="<?= h((string) ($row['motivo'] ?? '')) ?>" placeholder="Ej: Congreso, licencia…">
                </label>
            </div>
            <div class="turno-disponibilidad bloqueo-disponibilidad" id="bloqueo-disponibilidad"
                 data-slots-url="/agenda_slots.php"
                 data-initial-picked="<?= $pickedAttr ?>"
                 <?= $todo ? ' hidden' : '' ?>>
                <p class="muted small" id="bloqueo-disp-lead"><strong>Horarios del día inicial</strong> (misma vista que turnos): verde = libre, rojo = ya hay turno, gris = ya bloqueado. <strong>Doble clic</strong> en cada hueco que quieras bloquear (se acumulan); violeta = seleccionado para guardar.</p>
                <p class="muted small" id="bloqueo-disp-dia"></p>
                <p class="muted small" id="bloqueo-picked-summary" style="margin-top:0.25rem;"></p>
                <div class="form-actions" style="margin:0.35rem 0 0.5rem;padding:0;">
                    <button type="button" class="btn btn-sm btn-ghost" id="bloqueo-disp-limpiar">Limpiar selección en grilla</button>
                </div>
                <div class="turno-slots" id="bloqueo-slots-grid"></div>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>">Cancelar</a>
        </div>
    </form>
    <script>
        (function () {
            const chk = document.getElementById('bloqueo_todo_dia');
            const hd = document.getElementById('bloqueo_hora_desde');
            const hh = document.getElementById('bloqueo_hora_hasta');
            const fd = document.querySelector('input[name="fecha_desde"]');
            const doc = document.querySelector('select[name="doctor"]');
            const root = document.getElementById('bloqueo-disponibilidad');
            const grid = document.getElementById('bloqueo-slots-grid');
            const diaInfo = document.getElementById('bloqueo-disp-dia');
            const pickedSummary = document.getElementById('bloqueo-picked-summary');
            const btnLimpiar = document.getElementById('bloqueo-disp-limpiar');
            const hiddenSlots = document.getElementById('bloques_slots_json');
            const hiddenStep = document.getElementById('bloqueo_step_min');
            if (!chk || !hd || !hh || !fd || !doc || !root || !grid || !hiddenSlots || !hiddenStep) return;

            const urlBase = root.getAttribute('data-slots-url') || '/agenda_slots.php';
            let slotStepMin = 15;
            const pickedSlots = new Set();
            try {
                const raw = root.getAttribute('data-initial-picked') || '[]';
                const arr = JSON.parse(raw);
                if (Array.isArray(arr)) {
                    arr.forEach((x) => {
                        if (typeof x === 'string' && /^\d{2}:\d{2}$/.test(x)) {
                            pickedSlots.add(x);
                        }
                    });
                }
            } catch (e) { /* vacío */ }

            function syncHiddenFromPicked() {
                const sorted = Array.from(pickedSlots).sort();
                hiddenSlots.value = JSON.stringify(sorted);
                hiddenStep.value = String(slotStepMin);
            }

            function sumarMinutosHi(hi, addM) {
                const m = String(hi || '').match(/^(\d{1,2}):(\d{2})$/);
                if (!m) return hi.substring(0, 5);
                let t = parseInt(m[1], 10) * 60 + parseInt(m[2], 10) + addM;
                t = ((t % 1440) + 1440) % 1440;
                const h = Math.floor(t / 60);
                const mm = t % 60;
                return String(h).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
            }

            function updateSummaryAndTimeInputs() {
                const sorted = Array.from(pickedSlots).sort();
                if (pickedSummary) {
                    if (!sorted.length) {
                        pickedSummary.textContent = '';
                    } else if (sorted.length === 1) {
                        const s = sorted[0];
                        pickedSummary.textContent = 'Listo para guardar: 1 turno (' + s + ' – ' + sumarMinutosHi(s, slotStepMin) + ' fin exclusivo).';
                    } else {
                        pickedSummary.textContent = 'Listo para guardar: ' + String(sorted.length) + ' turnos → se crearán ' + String(sorted.length) + ' bloqueos con el mismo rango de fechas y motivo.';
                    }
                }
                if (sorted.length === 1) {
                    const s = sorted[0];
                    hd.value = s;
                    hh.value = sumarMinutosHi(s, slotStepMin);
                } else {
                    hd.value = '';
                    hh.value = '';
                }
                syncHiddenFromPicked();
            }

            function filterPickedToSlots(slots) {
                const ok = new Set(slots);
                Array.from(pickedSlots).forEach((s) => {
                    if (!ok.has(s)) {
                        pickedSlots.delete(s);
                    }
                });
            }

            function applyPickedStyles() {
                grid.querySelectorAll('.turno-slot').forEach((el) => {
                    const s = el.getAttribute('data-slot') || '';
                    el.classList.toggle('is-picked-multi', pickedSlots.has(s));
                });
            }

            function sync() {
                const on = chk.checked;
                hd.disabled = on;
                hh.disabled = on;
                root.hidden = on;
                if (on) {
                    grid.innerHTML = '';
                    pickedSlots.clear();
                    syncHiddenFromPicked();
                    updateSummaryAndTimeInputs();
                } else {
                    refreshDisp();
                }
            }

            function actualizarDiaInfo() {
                if (!diaInfo) return;
                const fecha = String(fd.value || '');
                if (/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
                    diaInfo.innerHTML = '<span class="turno-dia-badge">Grilla según: ' + fecha + '</span>';
                } else {
                    diaInfo.textContent = '';
                }
            }

            function renderSlots(slots, occupied, blocked, stepMin) {
                grid.innerHTML = '';
                const step = Math.max(5, parseInt(String(stepMin || 15), 10) || 15);
                filterPickedToSlots(slots);
                slots.forEach((slot) => {
                    const ocup = parseInt(String(occupied[slot] || 0), 10) || 0;
                    const bloq = parseInt(String(blocked[slot] || 0), 10) || 0;
                    let cls = 'is-free';
                    if (bloq > 0) cls = 'is-blocked';
                    else if (ocup > 0) cls = 'is-occupied';
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'turno-slot ' + cls;
                    btn.setAttribute('data-slot', slot);
                    if (bloq > 0) btn.disabled = true;
                    const sp = document.createElement('span');
                    sp.textContent = slot;
                    btn.appendChild(sp);
                    if (ocup > 0) {
                        const sm = document.createElement('small');
                        sm.textContent = String(ocup);
                        btn.appendChild(sm);
                    }
                    btn.addEventListener('dblclick', (ev) => {
                        ev.preventDefault();
                        if (bloq > 0) return;
                        const key = slot.substring(0, 5);
                        if (pickedSlots.has(key)) {
                            pickedSlots.delete(key);
                        } else {
                            pickedSlots.add(key);
                        }
                        updateSummaryAndTimeInputs();
                        applyPickedStyles();
                    });
                    grid.appendChild(btn);
                });
                applyPickedStyles();
                updateSummaryAndTimeInputs();
            }

            async function refreshDisp() {
                if (chk.checked) return;
                const fecha = String(fd.value || '');
                const doctor = parseInt(String(doc.value || '0'), 10) || 0;
                if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha) || doctor < 1) {
                    grid.innerHTML = '';
                    return;
                }
                grid.innerHTML = '<div class="muted small">Cargando horarios…</div>';
                const u = urlBase + '?fecha=' + encodeURIComponent(fecha) + '&doctor=' + encodeURIComponent(String(doctor)) + '&exclude_id=0';
                try {
                    const res = await fetch(u, { credentials: 'same-origin' });
                    const data = await res.json();
                    if (!data || !data.ok) throw new Error('bad');
                    slotStepMin = Math.max(5, parseInt(String(data.step || 15), 10) || 15);
                    if (data.slots && data.slots.length) {
                        renderSlots(data.slots, data.occupied || {}, data.blocked || {}, slotStepMin);
                    } else {
                        grid.innerHTML = '<div class="muted small">Sin franja para mostrar.</div>';
                    }
                } catch (e) {
                    grid.innerHTML = '<div class="muted small">No se pudo cargar la grilla.</div>';
                }
                actualizarDiaInfo();
            }

            chk.addEventListener('change', sync);
            fd.addEventListener('change', () => { refreshDisp(); });
            doc.addEventListener('change', () => { refreshDisp(); });
            hd.addEventListener('change', () => {
                pickedSlots.clear();
                syncHiddenFromPicked();
                updateSummaryAndTimeInputs();
                applyPickedStyles();
            });
            hh.addEventListener('change', () => {
                pickedSlots.clear();
                syncHiddenFromPicked();
                updateSummaryAndTimeInputs();
                applyPickedStyles();
            });
            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', () => {
                    pickedSlots.clear();
                    hd.value = '';
                    hh.value = '';
                    syncHiddenFromPicked();
                    refreshDisp();
                });
            }

            syncHiddenFromPicked();
            sync();
            actualizarDiaInfo();
            if (!chk.checked) {
                refreshDisp();
            }
        })();
    </script>
</div>
