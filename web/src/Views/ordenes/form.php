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

$triSel = static function ($cur, $v): string {
    $c = (string) $cur;

    return $c === $v ? ' selected' : '';
};
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted">
            <a class="muted" href="<?= h($volver) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al listado de órdenes</a>
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

        <section class="form-section">
            <h2 class="form-section-title">Datos principales</h2>
            <div class="form-grid-ext">
                <label>Nro. HC (paciente) *
                    <input type="number" name="NroPaci" required min="1" value="<?= $row['NroPaci'] === '' || $row['NroPaci'] === null ? '' : (int) $row['NroPaci'] ?>">
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
                        <input type="number" name="idobrasocial" min="0" placeholder="Id numérico" value="<?= h((string) ($row['idobrasocial'] ?? '')) ?>">
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
                        <input type="number" name="idplan" min="0" value="<?= h((string) ($row['idplan'] ?? '')) ?>">
                    </label>
                <?php endif; ?>

                <?php if ($practicaOpts !== []): ?>
                    <label>Práctica / estudio
                        <select name="idpractica">
                            <?php catalogo_select_options($practicaOpts, $row['idpractica'] ?? '', 'Sin especificar'); ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>Id práctica
                        <input type="number" name="idpractica" min="0" value="<?= h((string) ($row['idpractica'] ?? '')) ?>">
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
            <div class="form-grid-ext">
                <label>Costo paciente
                    <input type="text" name="costo" inputmode="decimal" placeholder="0 o vacío" value="<?= h((string) ($row['costo'] ?? '')) ?>">
                </label>
                <label>Pago paciente
                    <input type="text" name="pago" inputmode="decimal" placeholder="0 o vacío" value="<?= h((string) ($row['pago'] ?? '')) ?>">
                </label>
                <label>Costo obra social
                    <input type="text" name="costo_os" inputmode="decimal" placeholder="0 o vacío" value="<?= h((string) ($row['costo_os'] ?? '')) ?>">
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
            o.hidden = cid !== '' && oc !== '' && oc !== cid;
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
