<?php

declare(strict_types=1);

/** @var array<string, mixed> $row */
/** @var list<array<string, mixed>> $doctores */
/** @var list<array{id:int|string,nombre:?string}> $cobOpts */
/** @var list<array<string, mixed>> $planesOpts */
/** @var list<array{id:int|string,nombre:?string}> $practicaOpts */
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
    $practicaActualTexto = catalogo_valor_datalist($practicaOpts, (int) ($row['idpractica'] ?? 0));
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
        <input type="hidden" name="formato_orden" value="compacto">
        <input type="hidden" name="ordenes_return_qs" value="<?= h((string) ($ordenesReturnQs ?? '')) ?>">
        <input type="hidden" name="idplan" id="orden_idplan" value="<?= h((string) ($row['idplan'] ?? '')) ?>">
        <?php if (($turnoVinculadoId ?? 0) > 0): ?>
            <input type="hidden" name="turno" value="<?= (int) $turnoVinculadoId ?>">
        <?php endif; ?>

        <section class="form-section">
            <h2 class="form-section-title">Paciente y fecha</h2>
            <p class="muted small">Estos datos identifican la orden. Los campos de uso diario están en la sección siguiente.</p>
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
            </div>
        </section>

        <p class="alert alert-info" id="orden_paciente_msg"<?= $pacienteOrden !== null ? ' style="display:none;"' : '' ?>>Ingresá un Nro. HC válido para cargar los datos del paciente.</p>

        <section class="form-section">
            <h2 class="form-section-title">Datos de la orden</h2>
            <p class="muted small">Formulario reducido según los campos utilizados en Control Salud.</p>
            <div class="form-grid-ext">
                <label>Médico *
                    <select name="iddoctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) ($row['iddoctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <?php if ($cobOpts !== []): ?>
                    <label>Cobertura médica
                        <select name="idobrasocial" id="orden_idobrasocial">
                            <?php catalogo_select_options($cobOpts, $row['idobrasocial'] ?? '', 'Sin especificar'); ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>Id cobertura (sin catálogo en BD)
                        <input type="number" name="idobrasocial" id="orden_idobrasocial" min="0" placeholder="Id numérico" value="<?= h((string) ($row['idobrasocial'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <?php if ($practicaOpts !== []): ?>
                    <?php
                    $cbLabel = 'Práctica / estudio';
                    $cbPlaceholder = 'Buscar por código o nombre de práctica';
                    $cbName = 'idpractica';
                    $cbHiddenId = 'orden_idpractica';
                    $cbInputId = 'orden_practica_buscar';
                    $cbListId = 'orden_practicas_lista';
                    $cbOpts = $practicaOpts;
                    $cbSelected = $row['idpractica'] ?? 0;
                    $cbRequired = false;
                    $cbAutoSubmitFormId = null;
                    $cbGrowClass = 'span-2';
                    $cbSubmitTextName = null;
                    $cbValor = $practicaActualTexto;
                    $cbHint = 'Código de nomenclador (ej. 420101) o parte del nombre.';
                    $cbSoloCodigo = true;
                    require dirname(__DIR__) . '/_partials/catalogo_buscar.php';
                    ?>
                <?php else: ?>
                    <label>Id práctica
                        <input type="number" name="idpractica" id="orden_idpractica" min="0" value="<?= h((string) ($row['idpractica'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <label>Costo paciente
                    <input type="text" name="costo" id="orden_costo" inputmode="decimal" placeholder="0,00" value="<?= h((string) ($row['costo'] ?? '')) ?>">
                </label>
                <label>Costo obra social
                    <input type="text" name="costo_os" id="orden_costo_os" inputmode="decimal" placeholder="0,00" value="<?= h((string) ($row['costo_os'] ?? '')) ?>">
                </label>
                <p class="muted small span-2" id="orden_precio_msg">Los importes se proponen desde Aranceles al elegir cobertura y práctica. Podés ajustarlos antes de guardar.</p>

                <label>Paga IVA
                    <select name="pagaiva" id="orden_pagaiva">
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
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
        </div>
    </form>
</div>
<?php if (($practicaOpts ?? []) !== []): ?>
    <?php catalogo_buscar_script_tag(); ?>
<?php endif; ?>
<script>
(function () {
    var endpoint = '<?= h(url('/orden_precio.php')) ?>';
    var cob = document.getElementById('orden_idobrasocial');
    var practica = document.getElementById('orden_idpractica');
    var plan = document.getElementById('orden_idplan');
    var costo = document.getElementById('orden_costo');
    var costoOs = document.getElementById('orden_costo_os');
    var msg = document.getElementById('orden_precio_msg');
    if (!cob || !practica || !costo || !costoOs) return;

    var requestSeq = 0;

    function setMsg(text, isError) {
        if (!msg) return;
        msg.textContent = text;
        msg.classList.toggle('alert', !!isError);
        msg.classList.toggle('alert-error', !!isError);
        msg.classList.toggle('muted', !isError);
        msg.classList.toggle('small', !isError);
    }

    function fmtInput(v) {
        if (v === null || v === undefined || v === '') return '';
        var n = Number(v);
        return isNaN(n) ? '' : String(n);
    }

    function cargarArancel() {
        var idCob = parseInt(String(cob.value || '0'), 10) || 0;
        var idPractica = parseInt(String(practica.value || '0'), 10) || 0;
        var idPlan = plan ? (parseInt(String(plan.value || '0'), 10) || 0) : 0;
        if (idCob < 1 || idPractica < 1) {
            setMsg('Elegí cobertura y práctica para proponer importes desde Aranceles.', false);
            return;
        }

        requestSeq += 1;
        var seq = requestSeq;
        setMsg('Consultando arancel...', false);

        var url = endpoint
            + '?idobrasocial=' + encodeURIComponent(String(idCob))
            + '&idpractica=' + encodeURIComponent(String(idPractica));
        if (idPlan > 0) {
            url += '&idplan=' + encodeURIComponent(String(idPlan));
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (seq !== requestSeq) return;
                if (!data || !data.ok) {
                    setMsg('No se pudo consultar el arancel.', true);
                    return;
                }
                if (!data.found || !data.precio) {
                    setMsg(data.message || 'No hay arancel cargado para esa combinación.', true);
                    return;
                }
                costo.value = fmtInput(data.precio.costopaciente);
                costoOs.value = fmtInput(data.precio.costocobertura);
                setMsg('Importes propuestos desde Aranceles. Podés ajustarlos antes de guardar.', false);
            })
            .catch(function () {
                if (seq !== requestSeq) return;
                setMsg('No se pudo consultar el arancel.', true);
            });
    }

    cob.addEventListener('change', cargarArancel);
    practica.addEventListener('change', cargarArancel);
    if (plan) {
        plan.addEventListener('change', cargarArancel);
    }

    if (
        (parseInt(String(cob.value || '0'), 10) || 0) > 0
        && (parseInt(String(practica.value || '0'), 10) || 0) > 0
        && String(costo.value || '').trim() === ''
        && String(costoOs.value || '').trim() === ''
    ) {
        cargarArancel();
    }
})();
</script>
<script>
(function () {
    var endpoint = '<?= h(url('/orden_paciente.php')) ?>';
    var esNueva = <?= (int) ($row['id'] ?? 0) < 1 ? 'true' : 'false' ?>;
    var nro = document.getElementById('orden_nropaci');
    var msg = document.getElementById('orden_paciente_msg');
    var cob = document.getElementById('orden_idobrasocial');
    var plan = document.getElementById('orden_idplan');
    var pagaIva = document.getElementById('orden_pagaiva');
    var pacienteActual = document.getElementById('orden-paciente-actual');
    var pacienteBuscar = document.getElementById('orden-paciente-buscar');
    var pacienteResultados = document.getElementById('orden-paciente-resultados');
    if (!nro || !msg) return;

    var timer = null;
    var requestSeq = 0;

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
            msg.style.display = '';
            msg.textContent = 'No se encontró un paciente con ese Nro. HC.';
            if (pacienteActual) {
                pacienteActual.value = '—';
            }
            return;
        }

        if (pacienteActual) {
            pacienteActual.value = formatearPacienteActual(item);
        }

        msg.style.display = 'none';
        maybeSetSelect(cob, Number(item.id_cobertura || 0));
        maybeSetSelect(plan, Number(item.id_plan || 0));
        if (pagaIva && esNueva && String(pagaIva.value || '') === '') {
            pagaIva.value = item.paga_iva ? '1' : '0';
        }
    }

    function loadPatient() {
        var id = String(nro.value || '').trim();
        requestSeq += 1;
        var seq = requestSeq;
        if (!id) {
            msg.style.display = '';
            msg.textContent = 'Ingresá un Nro. HC válido para cargar los datos del paciente.';
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
