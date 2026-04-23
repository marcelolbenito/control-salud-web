<?php

declare(strict_types=1);

/** @var array<string, mixed> $p */
/** @var int $idPac */
/** @var int $nroHC */
/** @var string $nombre */
/** @var list<array<string, mixed>> $registros */
/** @var list<array<string, mixed>> $codigos */
/** @var list<array<string, mixed>> $doctores */
/** @var list<array{label: string, piezas: list<int>}> $piezasOpts */
/** @var string $error */
/** @var array<string, mixed> $formOld */
/** @var list<array<string, mixed>> $ordenesMini */
/** @var bool $odontogramaExt */
/** @var bool $mapaSuperficiesActivo */
/** @var list<array<string, mixed>> $filasSuperficiesMapa */

$fo = static function (string $k, $default = '') use ($formOld) {
    return array_key_exists($k, $formOld) ? $formOld[$k] : $default;
};

$caraSel = [];
$caraPost = $fo('cara', null);
if (is_array($caraPost)) {
    foreach ($caraPost as $x) {
        $u = strtoupper(trim((string) $x));
        if ($u !== '') {
            $caraSel[$u] = true;
        }
    }
}

$caraDef = static function (string $l) use ($caraSel): string {
    return !empty($caraSel[$l]) ? ' checked' : '';
};

$piezaVal = (string) $fo('pieza_fdi', '');
$idCodVal = (string) $fo('id_codigo', '');
$idDocVal = (string) $fo('iddoctor', '');
$notasVal = (string) $fo('notas', '');
$idOrdVal = (string) $fo('id_orden', '');
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Odontograma</h1>
        <p class="muted">
            <strong><?= h($nombre) ?></strong>
            · Nro HC <strong><?= (int) $nroHC ?></strong>
            · Notación <strong>FDI</strong> (ISO 3950)
        </p>
        <p class="muted">
            <a href="/pacientes.php"><i class="bi bi-people" aria-hidden="true"></i> Pacientes</a>
            · <a href="/historia_clinica.php?id=<?= (int) $idPac ?>"><i class="bi bi-journal-medical" aria-hidden="true"></i> Historia clínica</a>
            · <a href="/paciente_form.php?id=<?= (int) $idPac ?>"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Ficha del paciente</a>
            · <a href="/ordenes.php?nrohc=<?= (int) $nroHC ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Órdenes de este paciente</a>
            · <a href="/odontograma_imprimir.php?id=<?= (int) $idPac ?>" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir / PDF</a>
            · <a href="#odontograma-historial"><i class="bi bi-clock-history" aria-hidden="true"></i> Historial</a>
        </p>
    </div>

    <p class="muted small odontograma-aviso">
        <strong>Historial</strong> (tabla cronológica) está <a href="#odontograma-historial"><i class="bi bi-arrow-down-circle" aria-hidden="true"></i> más abajo</a>: cada registro queda con fecha, usuario y (opcional) anulación con motivo.
        El <strong>mapa de colores</strong> es el estado visual actual por cara/pieza (se guarda aparte con «Guardar mapa»).
        Los mismos códigos aparecen en el <strong>pincel del mapa</strong> y en el desplegable <strong>Código clínico</strong> del registro; se administran en
        <a href="/catalogos.php?a=list&amp;tabla=lista_odontograma_codigos"><i class="bi bi-journals" aria-hidden="true"></i> Tablas auxiliares — Códigos odontograma</a>.
        <?php if (!$odontogramaExt): ?>
            Para <strong>vincular órdenes</strong> y <strong>anular con motivo</strong> (sin borrar el historial), ejecutá
            <code>sql/migration_015_odontograma_orden_anulacion.sql</code>.
        <?php endif; ?>
    </p>

    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php $mapaSuperficiesActivo = $mapaSuperficiesActivo ?? false; ?>

    <?php if ($mapaSuperficiesActivo): ?>
    <section class="form-card odontograma-unificado" id="odontograma-panel-principal">
        <h2 class="form-section-title">Esquema dental — mapa y registro</h2>
        <p class="muted small" style="margin:0 0 0.75rem 0;">
            Un solo dibujo FDI: <strong>número de pieza</strong> = selección para el historial;
            <strong>caras del esquema</strong> + pincel = mapa de colores (persiste con Guardar mapa).
            También podés elegir pieza en el desplegable.
        </p>
        <?php
        $odontogramaMapaEmbebido = true;
        $formPiezaFdiSeleccionada = $piezaVal;
        $mapaSoloImpresion = false;
        require __DIR__ . '/_mapa_superficies.php';
        ?>

        <h3 class="form-section-title odontograma-subtit-registro">Registro en el historial</h3>
        <p class="muted small" style="margin:0 0 0.65rem 0;">
            <strong>Guardar mapa</strong> solo guarda el <em>dibujo actual</em> (colores y marcas en el esquema).
            <strong>Registrar en odontograma</strong> agrega una <em>línea nueva al historial</em> con fecha, usuario, profesional, notas y (si aplica) orden:
            es la “constancia” clínica por visita o procedimiento. Podés usar solo el mapa, solo el historial, o ambos según tu práctica.
        </p>
        <form method="post" class="form-paciente" id="form-odontograma">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="registrar">

            <div class="form-grid-ext">
    <?php else: ?>
    <?php
    $odontogramaMapaEmbebido = false;
    $formPiezaFdiSeleccionada = '';
    $mapaSoloImpresion = false;
    require __DIR__ . '/_mapa_superficies.php';
    ?>

    <section class="form-card">
        <h2 class="form-section-title">Nuevo registro</h2>
        <form method="post" class="form-paciente" id="form-odontograma">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="registrar">
            <p class="muted small" style="margin:0 0 0.5rem 0;">Elegí la pieza en el <strong>esquema dental</strong> (clic o teclado) o en el desplegable <strong>Pieza (FDI)</strong>.</p>
            <p class="muted small" style="margin:0 0 0.65rem 0;">Cada <strong>Registrar en odontograma</strong> guarda una fila en el historial (fecha, usuario, notas, etc.). No tenés mapa de colores en esta instalación hasta aplicar la migración del mapa por superficies.</p>

            <?php
            $piezaSeleccionada = $piezaVal;
            $odontogramaSvgInteractivo = true;
            require __DIR__ . '/_svg_arcadas.php';
            require __DIR__ . '/_svg_temporales.php';
            ?>

            <div class="form-grid-ext" style="margin-top:1rem;">
    <?php endif; ?>
                <label>Pieza (FDI) *
                    <select name="pieza_fdi" id="odontograma_pieza_fdi" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($piezasOpts as $gr): ?>
                            <optgroup label="<?= h($gr['label']) ?>">
                                <?php foreach ($gr['piezas'] as $t): ?>
                                    <option value="<?= (int) $t ?>"<?= $piezaVal !== '' && (int) $piezaVal === (int) $t ? ' selected' : '' ?>><?= (int) $t ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Profesional (opcional)
                    <select name="iddoctor">
                        <option value="">— Sin especificar —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= $idDocVal !== '' && (int) $idDocVal === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Código clínico
                    <select name="id_codigo">
                        <option value="">— Sin código —</option>
                        <?php foreach ($codigos as $c): ?>
                            <?php
                            $cid = (int) ($c['id'] ?? 0);
                            $sym = trim((string) ($c['codigo'] ?? ''));
                            $nom = trim((string) ($c['nombre'] ?? ''));
                            $lab = ($sym !== '' ? $sym . ' — ' : '') . $nom;
                            ?>
                            <option value="<?= $cid ?>"<?= $idCodVal !== '' && (int) $idCodVal === $cid ? ' selected' : '' ?>><?= h($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($odontogramaExt): ?>
                    <label>Orden relacionada (opcional)
                        <select name="id_orden">
                            <option value="">— Sin vínculo —</option>
                            <?php foreach ($ordenesMini as $o): ?>
                                <?php
                                $oid = (int) ($o['id'] ?? 0);
                                $fd = trim((string) ($o['fecha_orden'] ?? ''));
                                $lab = 'Orden #' . $oid . ($fd !== '' ? ' · ' . $fd : '');
                                ?>
                                <option value="<?= $oid ?>"<?= $idOrdVal !== '' && (int) $idOrdVal === $oid ? ' selected' : '' ?>><?= h($lab) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <?php if ($ordenesMini === []): ?>
                        <p class="muted small span-2" style="margin:0;">No hay órdenes cargadas para este paciente. Podés crearlas en <a href="/ordenes.php?nrohc=<?= (int) $nroHC ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Órdenes</a>.</p>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="span-2">
                    <span class="label-like">Caras (opcional)</span>
                    <div class="fdi-caras-grid">
                        <label class="form-check"><input type="checkbox" name="cara[]" value="M"<?= $caraDef('M') ?>> Mesial (M)</label>
                        <label class="form-check"><input type="checkbox" name="cara[]" value="O"<?= $caraDef('O') ?>> Oclusal / Incisal (O)</label>
                        <label class="form-check"><input type="checkbox" name="cara[]" value="D"<?= $caraDef('D') ?>> Distal (D)</label>
                        <label class="form-check"><input type="checkbox" name="cara[]" value="V"<?= $caraDef('V') ?>> Vestibular (V)</label>
                        <label class="form-check"><input type="checkbox" name="cara[]" value="L"<?= $caraDef('L') ?>> Lingual / Palatina (L)</label>
                        <label class="form-check"><input type="checkbox" name="cara[]" value="I"<?= $caraDef('I') ?>> Incisal (I)</label>
                    </div>
                </div>
                <label class="span-2">Notas
                    <textarea name="notas" rows="3" placeholder="Detalle clínico, materiales, etc."><?= h($notasVal) ?></textarea>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Registrar en odontograma</button>
            </div>
        </form>
    </section>

    <section class="form-section" id="odontograma-historial" style="margin-top:2rem;">
        <h2 class="form-section-title">Historial (cronológico)</h2>
        <p class="muted small" style="margin:0 0 0.75rem 0;">Listado de <strong>eventos clínicos</strong> guardados (no se borran; con migración 015 podés <strong>anular</strong> con motivo). Para cambiar el dibujo del mapa, usá el esquema y <strong>Guardar mapa</strong>.</p>
        <?php if ($registros === []): ?>
            <p class="empty-state">Aún no hay registros de odontograma para este paciente.</p>
        <?php else: ?>
            <div class="table-wrap table-wrap-datatable">
                <table id="tbl-odontograma" class="table">
                    <thead>
                        <tr>
                            <th>Fecha / hora</th>
                            <th>Pieza</th>
                            <th>Caras</th>
                            <th>Código</th>
                            <th>Notas</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Profesional</th>
                            <th>Usuario</th>
                            <?php if ($odontogramaExt): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                            <?php
                            $anul = !empty($r['anulado']);
                            $rid = (int) ($r['id'] ?? 0);
                            $idOr = (int) ($r['id_orden'] ?? 0);
                            ?>
                            <tr class="<?= $anul ? 'odontograma-row-anulada' : '' ?>">
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>"><?= h((string) ($r['creado_en'] ?? '')) ?></td>
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>"><strong><?= (int) ($r['pieza_fdi'] ?? 0) ?></strong></td>
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>"><?= h((string) ($r['cara'] ?? '') ?: '—') ?></td>
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>">
                                    <?php
                                    $cs = trim((string) ($r['codigo_simbolo'] ?? ''));
                                    $cn = trim((string) ($r['codigo_nombre'] ?? ''));
                                    echo h($cs !== '' ? $cs . ($cn !== '' ? ' — ' . $cn : '') : ($cn !== '' ? $cn : '—'));
                                    ?>
                                </td>
                                <td class="cell-clip<?= $anul ? ' odontograma-celda-tachada' : '' ?>"><?= h((string) ($r['notas'] ?? '')) ?></td>
                                <td>
                                    <?php if ($idOr > 0): ?>
                                        <a href="/orden_form.php?id=<?= $idOr ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> #<?= $idOr ?></a>
                                        <?php
                                        $of = trim((string) ($r['orden_fecha'] ?? ''));
                                        if ($of !== ''):
                                            ?>
                                            <span class="muted small"><br><?= h($of) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($anul): ?>
                                        <span class="badge-odontograma-anulado">Anulado</span>
                                        <div class="muted small" style="margin-top:0.25rem;">
                                            <?= h(trim((string) ($r['anulado_motivo'] ?? '')) ?: '—') ?>
                                            <?php
                                            $ae = trim((string) ($r['anulado_en'] ?? ''));
                                            if ($ae !== ''):
                                                ?>
                                                <br><span title="Fecha anulación"><?= h($ae) ?></span>
                                            <?php endif; ?>
                                            <?php
                                            $au = trim((string) ($r['anulado_usuario'] ?? ''));
                                            if ($au !== ''):
                                                ?>
                                                · <?= h($au) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="muted">Vigente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= h(trim((string) ($r['doctor_nombre'] ?? '')) ?: '—') ?></td>
                                <td><?= h(trim((string) ($r['usuario_web'] ?? '')) ?: '—') ?></td>
                                <?php if ($odontogramaExt): ?>
                                    <td class="odontograma-acciones-cell">
                                        <?php if (!$anul): ?>
                                            <form method="post" class="odontograma-anular-form" action="/odontograma.php?id=<?= (int) $idPac ?>"
                                                  onsubmit="var m=this.anular_motivo.value.trim(); if(!m){alert('Indicá el motivo de anulación.'); return false;} return confirm('¿Anular este registro? Quedará marcado como anulado en el historial.');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="anular">
                                                <input type="hidden" name="registro_id" value="<?= $rid ?>">
                                                <input type="text" name="anular_motivo" class="odontograma-anular-motivo" placeholder="Motivo" required maxlength="255" autocomplete="off">
                                                <button type="submit" class="btn btn-sm btn-danger">Anular</button>
                                            </form>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<script>
(function () {
    var sel = document.getElementById('odontograma_pieza_fdi');
    if (!sel) return;

    function highlightFdi(v) {
        document.querySelectorAll('.fdi-svg-tooth').forEach(function (g) {
            var rect = g.querySelector('rect');
            var t = g.getAttribute('data-fdi');
            var on = v && String(t) === String(v);
            g.classList.toggle('is-picked', on);
            if (rect) {
                rect.setAttribute('fill', on ? '#f0fdfa' : '#ffffff');
                rect.setAttribute('stroke', on ? '#0d9488' : '#64748b');
                rect.setAttribute('stroke-width', on ? '2' : '1');
            }
        });
        document.querySelectorAll('.odonto-tooth-map').forEach(function (wrap) {
            var t = wrap.getAttribute('data-fdi');
            var on = v && String(t) === String(v);
            wrap.classList.toggle('is-picked-form', on);
        });
    }

    function pickFdi(v) {
        if (!v) return;
        sel.value = v;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        highlightFdi(v);
    }

    document.querySelectorAll('.fdi-pick-trigger').forEach(function (el) {
        el.addEventListener('click', function () {
            pickFdi(el.getAttribute('data-fdi'));
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                pickFdi(el.getAttribute('data-fdi'));
            }
        });
    });

    document.querySelectorAll('.odonto-tooth-pick-fdi').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (e.shiftKey) {
                e.preventDefault();
                e.stopPropagation();
                window.dispatchEvent(new CustomEvent('odonto-clear-tooth-map', { detail: { fdi: btn.getAttribute('data-fdi') } }));
                return;
            }
            e.preventDefault();
            pickFdi(btn.getAttribute('data-fdi'));
        });
    });

    sel.addEventListener('change', function () {
        highlightFdi(sel.value);
    });
    highlightFdi(sel.value);
})();
</script>
