<?php

declare(strict_types=1);

/** @var array<string, mixed> $row */
/** @var list<array<string, mixed>> $doctores */
/** @var list<array{id:int|string,nombre:?string}> $cobOpts */
/** @var list<array<string, mixed>> $planesOpts */
/** @var list<array{id:int|string,nombre:?string}> $practicaOpts */
/** @var list<array{id:int|string,nombre:?string}> $derivacionOpts */
/** @var list<array{id:int|string,nombre:?string}> $sucursalOpts */
/** @var string $error */
/** @var string $titulo */
/** @var string $volver */
/** @var string $ordenesReturnQs */
/** @var string $sesionesResumen Resumen de sesiones vinculadas (vacío si no hay tabla o datos) */
/** @var int $turnoVinculadoId */
/** @var array<string, mixed>|null $pacienteOrden */

$triSel = static function ($cur, $v): string {
    $c = (string) $cur;

    return $c === $v ? ' selected' : '';
};
$pacienteDato = static function (?array $p, string $key, string $empty = 'Sin dato'): string {
    if ($p === null) {
        return $empty;
    }
    $v = trim((string) ($p[$key] ?? ''));

    return $v !== '' && $v !== '0' ? $v : $empty;
};
$pacienteCoberturaLabel = static function (?array $p, string $idKey, string $nameKey): string {
    if ($p === null) {
        return 'Sin dato';
    }
    $id = (int) ($p[$idKey] ?? 0);
    $nombre = trim((string) ($p[$nameKey] ?? ''));
    if ($id < 1 && $nombre === '') {
        return 'Sin dato';
    }
    if ($nombre !== '') {
        return $nombre . ($id > 0 ? ' (#' . $id . ')' : '');
    }

    return '#' . $id;
};
$practicaActualTexto = '';
if ($practicaOpts !== [] && (int) ($row['idpractica'] ?? 0) > 0) {
    foreach ($practicaOpts as $pr) {
        if ((int) ($pr['id'] ?? 0) === (int) ($row['idpractica'] ?? 0)) {
            $practicaActualTexto = (int) $pr['id'] . ' - ' . trim((string) ($pr['nombre'] ?? ''));
            break;
        }
    }
}
$pacienteActualOrden = '—';
if ($pacienteOrden !== null) {
    $pacienteActualOrden = $pacienteDato($pacienteOrden, 'nombre', 'Paciente HC ' . (int) ($row['NroPaci'] ?? 0));
    $dniActual = $pacienteDato($pacienteOrden, 'dni', '');
    if ($dniActual !== '') {
        $pacienteActualOrden .= ' - DNI ' . $dniActual;
    }
}
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted">
            <a class="muted" href="<?= h($volver) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver</a>
            <?php
            $nroOrd = (int) ($row['NroPaci'] ?? 0);
            if ($nroOrd > 0):
                ?>
                · <a href="/odontograma.php?nrohc=<?= $nroOrd ?>"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Odontograma del paciente</a>
                <?php if ((int) ($row['id'] ?? 0) > 0): ?>
                    · <a href="/pagos_form.php?idorden=<?= (int) $row['id'] ?>&nrohc=<?= $nroOrd ?>"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i> Registrar pago</a>
                    · <a href="/sesiones.php?idorden=<?= (int) $row['id'] ?>"><i class="bi bi-calendar2-check" aria-hidden="true"></i> Sesiones de esta orden</a>
                    · <a href="/sesion_form.php?idorden=<?= (int) $row['id'] ?>&nrohc=<?= $nroOrd ?>"><i class="bi bi-calendar2-plus" aria-hidden="true"></i> Nueva sesión</a>
                <?php endif; ?>
                <?php
                $sr = trim((string) ($sesionesResumen ?? ''));
                if ($sr !== ''):
                    ?>
                    <span class="muted"> · <?= h($sr) ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente" id="form-orden">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
        <input type="hidden" name="ordenes_return_qs" value="<?= h((string) ($ordenesReturnQs ?? '')) ?>">
        <?php if (($turnoVinculadoId ?? 0) > 0): ?>
            <input type="hidden" name="turno" value="<?= (int) $turnoVinculadoId ?>">
        <?php endif; ?>

        <section class="form-section">
            <h2 class="form-section-title">Datos principales</h2>
            <div class="form-grid-ext">
                <?php if (($turnoVinculadoId ?? 0) < 1): ?>
                    <label class="span-2 turno-paciente-busqueda">Buscar paciente (DNI y/o nombre)
                        <input type="text" id="orden-paciente-buscar" autocomplete="off" placeholder="Ej: 30111222 o Perez Ana">
                        <small class="muted">Elegí un resultado para completar Nro. HC automáticamente.</small>
                        <div id="orden-paciente-resultados" class="turno-paciente-resultados" hidden></div>
                    </label>
                <?php endif; ?>
                <label>Nro. HC (paciente) *
                    <input type="number" name="NroPaci" id="orden_nropaci" required min="1" value="<?= $row['NroPaci'] === '' || $row['NroPaci'] === null ? '' : (int) $row['NroPaci'] ?>">
                </label>
                <label>Paciente actual
                    <input type="text" id="orden-paciente-actual" value="<?= h($pacienteActualOrden) ?>" readonly>
                </label>
                <label>Fecha orden
                    <input type="date" name="fecha_orden" value="<?= h((string) ($row['fecha_orden'] ?? '')) ?>">
                </label>
                <label>Profesional *
                    <select name="iddoctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) ($row['iddoctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Nº orden (interno)
                    <input type="number" name="numero" min="0" placeholder="Opcional" value="<?= h((string) ($row['numero'] ?? '')) ?>">
                </label>
                <label class="form-check span-2"><input type="checkbox" name="autorizada" value="1" <?= !empty($row['autorizada']) ? ' checked' : '' ?>> Autorizada</label>
                <label class="form-check span-2"><input type="checkbox" name="entregada" value="1" <?= !empty($row['entregada']) ? ' checked' : '' ?>> Entregada</label>
                <label class="form-check span-2"><input type="checkbox" name="liquidada" value="1" <?= !empty($row['liquidada']) ? ' checked' : '' ?>> Liquidada (honorarios)</label>
                <label class="span-2">Observaciones
                    <textarea name="observaciones" rows="3" placeholder="Notas de la orden"><?= h((string) ($row['observaciones'] ?? '')) ?></textarea>
                </label>
            </div>
        </section>

        <section class="form-section" id="orden_paciente_box"<?= $pacienteOrden === null ? ' style="display:none;"' : '' ?>>
            <h2 class="form-section-title">Datos cargados del paciente</h2>
            <p class="muted small">Estos datos vienen de la ficha del paciente y ayudan a cargar la orden. Si la orden es nueva, cobertura y plan se proponen automáticamente cuando están vacíos.</p>
            <div class="form-grid-ext">
                <p><strong>Paciente</strong><br><span id="orden_paciente_nombre"><?= h($pacienteDato($pacienteOrden, 'nombre')) ?></span></p>
                <p><strong>DNI</strong><br><span id="orden_paciente_dni"><?= h($pacienteDato($pacienteOrden, 'dni')) ?></span></p>
                <p><strong>Cobertura principal</strong><br><span id="orden_paciente_cobertura"><?= h($pacienteCoberturaLabel($pacienteOrden, 'id_cobertura', 'cobertura_nombre')) ?></span></p>
                <p><strong>Plan</strong><br><span id="orden_paciente_plan"><?= h($pacienteCoberturaLabel($pacienteOrden, 'id_plan', 'plan_nombre')) ?></span></p>
                <p><strong>Nº afiliado / OS</strong><br><span id="orden_paciente_nro_os"><?= h($pacienteDato($pacienteOrden, 'nro_os')) ?></span></p>
                <p><strong>Segunda cobertura</strong><br><span id="orden_paciente_cobertura2"><?= h($pacienteCoberturaLabel($pacienteOrden, 'id_cobertura2', 'cobertura2_nombre')) ?></span></p>
                <p><strong>Nº afiliado (2)</strong><br><span id="orden_paciente_afiliado2"><?= h($pacienteDato($pacienteOrden, 'nu_afiliado2')) ?></span></p>
                <p><strong>Paga IVA</strong><br><span id="orden_paciente_iva"><?= !empty($pacienteOrden['paga_iva'] ?? 0) ? 'Sí' : 'No' ?></span></p>
            </div>
        </section>
        <p class="alert alert-info" id="orden_paciente_msg"<?= $pacienteOrden !== null ? ' style="display:none;"' : '' ?>>Ingresá un Nro. HC válido para ver cobertura, plan y afiliado del paciente.</p>

        <section class="form-section">
            <h2 class="form-section-title">Cobertura, práctica y sucursal</h2>
            <div class="form-grid-ext">
                <?php if ($cobOpts !== []): ?>
                    <label>Cobertura / obra social
                        <select name="idobrasocial" id="orden_idobrasocial">
                            <?php catalogo_select_options($cobOpts, $row['idobrasocial'] ?? '', 'Sin especificar'); ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>Id cobertura (sin catálogo en BD)
                        <input type="number" name="idobrasocial" id="orden_idobrasocial" min="0" placeholder="Id numérico" value="<?= h((string) ($row['idobrasocial'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <?php if ($planesOpts !== []): ?>
                    <label>Plan
                        <select name="idplan" id="orden_idplan">
                            <option value="">— Sin plan —</option>
                            <?php foreach ($planesOpts as $pl): ?>
                                <?php
                                $idc = $pl['id_cobertura'] ?? null;
                                $idcAttr = ($idc === null || $idc === '') ? '' : (string) (int) $idc;
                                ?>
                                <option value="<?= (int) $pl['id'] ?>" data-id-cobertura="<?= h($idcAttr) ?>"<?= (int) ($row['idplan'] ?? 0) === (int) $pl['id'] ? ' selected' : '' ?>><?= h((string) ($pl['nombre'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>Id plan
                        <input type="number" name="idplan" id="orden_idplan" min="0" value="<?= h((string) ($row['idplan'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <?php if ($practicaOpts !== []): ?>
                    <label class="span-2">Práctica / estudio
                        <input type="hidden" name="idpractica" id="orden_idpractica" value="<?= h((string) ($row['idpractica'] ?? '')) ?>">
                        <input type="text" id="orden_practica_buscar" list="orden_practicas_lista" placeholder="Buscar por código o nombre de práctica" value="<?= h($practicaActualTexto) ?>" autocomplete="off">
                        <datalist id="orden_practicas_lista">
                            <?php foreach ($practicaOpts as $pr): ?>
                                <?php
                                $pid = (int) ($pr['id'] ?? 0);
                                $pnombre = trim((string) ($pr['nombre'] ?? ''));
                                ?>
                                <option value="<?= h($pid . ' - ' . $pnombre) ?>" data-id="<?= $pid ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <span class="hint">Ejemplo: escribí el código <strong>1674</strong> o parte del nombre.</span>
                    </label>
                <?php else: ?>
                    <label>Id práctica
                        <input type="number" name="idpractica" id="orden_idpractica" min="0" value="<?= h((string) ($row['idpractica'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <?php if ($derivacionOpts !== []): ?>
                    <label>Derivación
                        <select name="idderivado">
                            <?php catalogo_select_options($derivacionOpts, $row['idderivado'] ?? '', 'Sin especificar'); ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>Id derivado
                        <input type="number" name="idderivado" min="0" value="<?= h((string) ($row['idderivado'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <?php if ($sucursalOpts !== []): ?>
                    <label>Sucursal
                        <select name="sucursal">
                            <?php catalogo_select_options($sucursalOpts, $row['sucursal'] ?? '', 'Sin especificar'); ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>Sucursal (código)
                        <input type="number" name="sucursal" min="0" placeholder="Nº sucursal" value="<?= h((string) ($row['sucursal'] ?? '')) ?>">
                    </label>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Montos y sesiones</h2>
            <p class="muted small" id="orden_precio_msg">Al elegir cobertura y práctica, se buscará el arancel cargado para completar los importes.</p>
            <div class="form-grid-ext">
                <label>Costo paciente
                    <input type="text" name="costo" id="orden_costo" inputmode="decimal" placeholder="0 o vacío" value="<?= h((string) ($row['costo'] ?? '')) ?>">
                </label>
                <label>Pago paciente
                    <input type="text" name="pago" id="orden_pago" inputmode="decimal" placeholder="0 o vacío" value="<?= h((string) ($row['pago'] ?? '')) ?>">
                </label>
                <label>Costo obra social
                    <input type="text" name="costo_os" id="orden_costo_os" inputmode="decimal" placeholder="0 o vacío" value="<?= h((string) ($row['costo_os'] ?? '')) ?>">
                </label>
                <label>Honorario extra
                    <input type="text" name="honorarioextra" inputmode="decimal" placeholder="0 o vacío" value="<?= h((string) ($row['honorarioextra'] ?? '')) ?>">
                </label>
                <label>Sesiones (prescriptas)
                    <input type="number" name="sesiones" min="0" value="<?= h((string) ($row['sesiones'] ?? '')) ?>">
                </label>
                <label>Sesiones realizadas
                    <input type="number" name="sesionesreali" min="0" value="<?= h((string) ($row['sesionesreali'] ?? '')) ?>">
                </label>
                <label>Nº autorización (numérico)
                    <input type="number" name="numeautorizacion" min="0" value="<?= h((string) ($row['numeautorizacion'] ?? '')) ?>">
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Estados (facturación / OS)</h2>
            <p class="muted small" style="margin-top:-0.5rem;">En el sistema de escritorio suelen usarse códigos de una letra (p. ej. A / F / P según cobertura).</p>
            <div class="form-grid-ext">
                <label>Estado (paciente / orden)
                    <input type="text" name="estado" id="orden_estado" maxlength="1" style="max-width:4rem" placeholder="Ej. A, F, P" value="<?= h((string) ($row['estado'] ?? '')) ?>" list="lista-estado-orden" autocomplete="off">
                    <datalist id="lista-estado-orden">
                        <option value="A"><option value="F"><option value="P">
                    </datalist>
                </label>
                <label>Estado obra social
                    <input type="text" name="estado_os" id="orden_estado_os" maxlength="1" style="max-width:4rem" placeholder="Ej. A, F, P" value="<?= h((string) ($row['estado_os'] ?? '')) ?>" list="lista-estado-os" autocomplete="off">
                    <datalist id="lista-estado-os">
                        <option value="A"><option value="F"><option value="P">
                    </datalist>
                </label>
                <label>Paga IVA
                    <select name="pagaiva">
                        <option value=""<?= $triSel($row['pagaiva'] ?? '', '') ?>>—</option>
                        <option value="0"<?= $triSel($row['pagaiva'] ?? '', '0') ?>>No</option>
                        <option value="1"<?= $triSel($row['pagaiva'] ?? '', '1') ?>>Sí</option>
                    </select>
                </label>
                <label>Cerrada
                    <select name="cerrada">
                        <option value=""<?= $triSel($row['cerrada'] ?? '', '') ?>>—</option>
                        <option value="0"<?= $triSel($row['cerrada'] ?? '', '0') ?>>No</option>
                        <option value="1"<?= $triSel($row['cerrada'] ?? '', '1') ?>>Sí</option>
                    </select>
                </label>
                <label>Tipo asistencia (código numérico)
                    <input type="number" name="tipoasistencia" placeholder="Opcional" value="<?= h((string) ($row['tipoasistencia'] ?? '')) ?>">
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Fechas adicionales</h2>
            <div class="form-grid-ext">
                <label>Derivación
                    <input type="date" name="fechaderivacion" value="<?= h((string) ($row['fechaderivacion'] ?? '')) ?>">
                </label>
                <label>Autorización
                    <input type="date" name="fechaautorizacion" value="<?= h((string) ($row['fechaautorizacion'] ?? '')) ?>">
                </label>
                <label>Entrega
                    <input type="date" name="fechaentrega" value="<?= h((string) ($row['fechaentrega'] ?? '')) ?>">
                </label>
                <label>Honorario (fecha)
                    <input type="date" name="honorariofecha" value="<?= h((string) ($row['honorariofecha'] ?? '')) ?>">
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Odontología / siniestro</h2>
            <div class="form-grid-ext">
                <label>Diente
                    <input type="text" name="diente" maxlength="2" value="<?= h((string) ($row['diente'] ?? '')) ?>">
                </label>
                <label>Cara
                    <input type="text" name="cara" maxlength="5" value="<?= h((string) ($row['cara'] ?? '')) ?>">
                </label>
                <label class="span-2">Nº siniestro
                    <input type="text" name="nusiniestro" maxlength="30" value="<?= h((string) ($row['nusiniestro'] ?? '')) ?>">
                </label>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
        </div>
    </form>
</div>
<?php if ($planesOpts !== [] && $cobOpts !== []): ?>
<script>
(function () {
    var cob = document.getElementById('orden_idobrasocial');
    var plan = document.getElementById('orden_idplan');
    if (!cob || !plan) return;
    function sync() {
        var cid = String(cob.value || '');
        var opts = plan.querySelectorAll('option');
        opts.forEach(function (o) {
            if (!o.value) {
                o.hidden = false;
                return;
            }
            var oc = o.getAttribute('data-id-cobertura') || '';
            // Si hay cobertura elegida, solo se muestran planes vinculados a esa cobertura.
            o.hidden = cid !== '' && oc !== cid;
        });
        var sel = plan.options[plan.selectedIndex];
        if (sel && sel.hidden) {
            plan.value = '';
        }
    }
    cob.addEventListener('change', sync);
    sync();
})();
</script>
<?php endif; ?>
<script>
(function () {
    var input = document.getElementById('orden_practica_buscar');
    var hidden = document.getElementById('orden_idpractica');
    var list = document.getElementById('orden_practicas_lista');
    if (!input || !hidden || !list) return;

    var options = Array.from(list.querySelectorAll('option'));

    function normalize(s) {
        return String(s || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function findMatch(raw) {
        var q = normalize(raw);
        if (!q) {
            return null;
        }

        for (var i = 0; i < options.length; i++) {
            if (normalize(options[i].value) === q) {
                return options[i];
            }
        }

        var code = q.match(/^\#?(\d+)/);
        if (code) {
            for (var j = 0; j < options.length; j++) {
                if (String(options[j].getAttribute('data-id') || '') === code[1]) {
                    return options[j];
                }
            }
        }

        var partials = options.filter(function (o) {
            return normalize(o.value).indexOf(q) !== -1;
        });
        return partials.length === 1 ? partials[0] : null;
    }

    function syncPractica() {
        var match = findMatch(input.value);
        var next = match ? String(match.getAttribute('data-id') || '') : '';
        if (match && input.value !== match.value) {
            input.value = match.value;
        }
        if (hidden.value !== next) {
            hidden.value = next;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    input.addEventListener('change', syncPractica);
    input.addEventListener('blur', syncPractica);
})();
</script>
<script>
(function () {
    var endpoint = '<?= h(url('/orden_paciente.php')) ?>';
    var esNueva = <?= (int) ($row['id'] ?? 0) < 1 ? 'true' : 'false' ?>;
    var nro = document.getElementById('orden_nropaci');
    var box = document.getElementById('orden_paciente_box');
    var msg = document.getElementById('orden_paciente_msg');
    var cob = document.getElementById('orden_idobrasocial');
    var plan = document.getElementById('orden_idplan');
    var pacienteActual = document.getElementById('orden-paciente-actual');
    var pacienteBuscar = document.getElementById('orden-paciente-buscar');
    var pacienteResultados = document.getElementById('orden-paciente-resultados');
    if (!nro || !box || !msg) return;

    var fields = {
        nombre: document.getElementById('orden_paciente_nombre'),
        dni: document.getElementById('orden_paciente_dni'),
        cobertura: document.getElementById('orden_paciente_cobertura'),
        plan: document.getElementById('orden_paciente_plan'),
        nroOs: document.getElementById('orden_paciente_nro_os'),
        cobertura2: document.getElementById('orden_paciente_cobertura2'),
        afiliado2: document.getElementById('orden_paciente_afiliado2'),
        iva: document.getElementById('orden_paciente_iva')
    };
    var timer = null;
    var requestSeq = 0;

    function text(v) {
        v = String(v || '').trim();
        return v !== '' && v !== '0' ? v : 'Sin dato';
    }

    function catalogLabel(item, idKey, nameKey) {
        var id = Number(item && item[idKey] ? item[idKey] : 0);
        var name = String(item && item[nameKey] ? item[nameKey] : '').trim();
        if (!id && !name) return 'Sin dato';
        if (name) return name + (id ? ' (#' + id + ')' : '');
        return '#' + id;
    }

    function setText(el, value) {
        if (el) el.textContent = value;
    }

    function formatearPacienteActual(item) {
        if (!item) return '—';
        var nombre = String(item.nombre || '').trim();
        var dni = String(item.dni || '').trim();
        if (nombre && dni) {
            return nombre + ' - DNI ' + dni;
        }
        return nombre || '—';
    }

    function maybeSetSelect(select, value) {
        if (!select || !value || !esNueva || String(select.value || '') !== '') {
            return;
        }
        select.value = String(value);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function applyPatient(item) {
        if (!item) {
            box.style.display = 'none';
            msg.style.display = '';
            msg.textContent = 'No se encontró un paciente con ese Nro. HC.';
            if (pacienteActual) {
                pacienteActual.value = '—';
            }
            return;
        }

        setText(fields.nombre, text(item.nombre));
        setText(fields.dni, text(item.dni));
        setText(fields.cobertura, catalogLabel(item, 'id_cobertura', 'cobertura_nombre'));
        setText(fields.plan, catalogLabel(item, 'id_plan', 'plan_nombre'));
        setText(fields.nroOs, text(item.nro_os));
        setText(fields.cobertura2, catalogLabel(item, 'id_cobertura2', 'cobertura2_nombre'));
        setText(fields.afiliado2, text(item.nu_afiliado2));
        setText(fields.iva, item.paga_iva ? 'Sí' : 'No');
        if (pacienteActual) {
            pacienteActual.value = formatearPacienteActual(item);
        }

        box.style.display = '';
        msg.style.display = 'none';
        maybeSetSelect(cob, Number(item.id_cobertura || 0));
        maybeSetSelect(plan, Number(item.id_plan || 0));
    }

    function loadPatient() {
        var id = String(nro.value || '').trim();
        requestSeq += 1;
        var seq = requestSeq;
        if (!id) {
            box.style.display = 'none';
            msg.style.display = '';
            msg.textContent = 'Ingresá un Nro. HC válido para ver cobertura, plan y afiliado del paciente.';
            return;
        }

        fetch(endpoint + '?nrohc=' + encodeURIComponent(id), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (seq !== requestSeq) return;
                applyPatient(data && data.ok ? data.item : null);
            })
            .catch(function () {
                if (seq !== requestSeq) return;
                box.style.display = 'none';
                msg.style.display = '';
                msg.textContent = 'No se pudieron consultar los datos del paciente.';
            });
    }

    nro.addEventListener('blur', loadPatient);
    nro.addEventListener('change', loadPatient);
    nro.addEventListener('input', function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(loadPatient, 450);
    });

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
        items.forEach(function (it) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'turno-paciente-item';
            btn.innerHTML = '<strong>HC ' + String(it.nrohc || '') + '</strong> - ' + (it.nombre || '(sin nombre)') + (it.dni ? ' - DNI ' + it.dni : '');
            btn.addEventListener('click', function () {
                nro.value = String(it.nrohc || '');
                if (pacienteActual) {
                    pacienteActual.value = formatearPacienteActual(it);
                }
                if (pacienteBuscar) {
                    pacienteBuscar.value = it.nombre ? (it.nombre + (it.dni ? ' - DNI ' + it.dni : '')) : ('HC ' + String(it.nrohc || ''));
                }
                clearPacienteResultados();
                loadPatient();
            });
            pacienteResultados.appendChild(btn);
        });
        pacienteResultados.hidden = false;
    }

    var searchTimer = null;
    function buscarPacienteOrden() {
        if (!pacienteBuscar || !pacienteResultados) return;
        var q = String(pacienteBuscar.value || '').trim();
        if (q.length < 2) {
            clearPacienteResultados();
            return;
        }
        pacienteResultados.hidden = false;
        pacienteResultados.innerHTML = '<div class="turno-paciente-item muted">Buscando...</div>';
        fetch('<?= h(url('/pacientes_lookup.php')) ?>?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) throw new Error('bad');
                renderPacienteResultados(data.items || []);
            })
            .catch(function () {
                pacienteResultados.hidden = false;
                pacienteResultados.innerHTML = '<div class="turno-paciente-item muted">No se pudo buscar ahora.</div>';
            });
    }

    if (pacienteBuscar) {
        pacienteBuscar.addEventListener('input', function () {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(buscarPacienteOrden, 220);
        });
        pacienteBuscar.addEventListener('blur', function () {
            window.setTimeout(clearPacienteResultados, 200);
        });
        pacienteBuscar.addEventListener('focus', function () {
            if (String(pacienteBuscar.value || '').trim().length >= 2) {
                buscarPacienteOrden();
            }
        });
    }
})();
</script>
<script>
(function () {
    var endpoint = '<?= h(url('/orden_precio.php')) ?>';
    var cob = document.getElementById('orden_idobrasocial');
    var plan = document.getElementById('orden_idplan');
    var practica = document.getElementById('orden_idpractica');
    var costo = document.getElementById('orden_costo');
    var costoOs = document.getElementById('orden_costo_os');
    var msg = document.getElementById('orden_precio_msg');
    if (!cob || !practica || !costo || !costoOs || !msg) return;

    var requestSeq = 0;
    var lastApplied = {
        costo: null,
        costoOs: null
    };

    function norm(v) {
        return String(v || '').replace(',', '.').trim();
    }

    function fmt(v) {
        if (v === null || typeof v === 'undefined' || v === '') {
            return '';
        }
        var n = Number(String(v).replace(',', '.'));
        if (!isFinite(n)) {
            return String(v);
        }
        return String(n.toFixed(4)).replace(/0+$/, '').replace(/\.$/, '');
    }

    function canReplace(input, key) {
        var current = norm(input.value);
        return current === '' || (lastApplied[key] !== null && current === lastApplied[key]);
    }

    function setAutoValue(input, key, value) {
        var formatted = fmt(value);
        if (formatted === '') {
            return false;
        }
        if (!canReplace(input, key)) {
            return false;
        }
        input.value = formatted;
        lastApplied[key] = norm(formatted);
        return true;
    }

    function setMsg(text) {
        msg.textContent = text;
    }

    function buscarPrecio() {
        var idCob = String(cob.value || '');
        var idPractica = String(practica.value || '');
        var idPlan = plan ? String(plan.value || '') : '';
        requestSeq += 1;
        var seq = requestSeq;

        if (!idCob || !idPractica) {
            setMsg('Al elegir cobertura y práctica, se buscará el arancel cargado para completar los importes.');
            return;
        }

        setMsg('Buscando arancel...');
        var qs = '?idobrasocial=' + encodeURIComponent(idCob)
            + '&idpractica=' + encodeURIComponent(idPractica)
            + '&idplan=' + encodeURIComponent(idPlan);

        fetch(endpoint + qs, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (seq !== requestSeq) return;
                if (!data || !data.ok || !data.found || !data.precio) {
                    setMsg(data && data.message ? data.message : 'No hay arancel cargado para esa combinación.');
                    return;
                }

                var p = data.precio;
                var aplicoPaciente = setAutoValue(costo, 'costo', p.costopaciente);
                var aplicoCobertura = setAutoValue(costoOs, 'costoOs', p.costocobertura);
                var detalle = [];
                if (p.costopaciente !== null) {
                    detalle.push('paciente $' + fmt(p.costopaciente));
                }
                if (p.costocobertura !== null) {
                    detalle.push('obra social $' + fmt(p.costocobertura));
                }
                if (p.usarporcentaje && p.costoporcentaje !== null) {
                    detalle.push('porcentaje ' + fmt(p.costoporcentaje) + '%');
                }

                if (aplicoPaciente || aplicoCobertura) {
                    setMsg('Arancel encontrado: ' + detalle.join(' · ') + '.');
                } else {
                    setMsg('Arancel encontrado (' + detalle.join(' · ') + '), pero no se pisaron importes editados manualmente.');
                }
            })
            .catch(function () {
                if (seq === requestSeq) {
                    setMsg('No se pudo consultar el arancel. Revisá conexión o permisos.');
                }
            });
    }

    cob.addEventListener('change', buscarPrecio);
    practica.addEventListener('change', buscarPrecio);
    if (plan) {
        plan.addEventListener('change', buscarPrecio);
    }
    buscarPrecio();
})();
</script>
