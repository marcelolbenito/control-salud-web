<?php

declare(strict_types=1);

/** @var string $fecha */
/** @var string $turno */
/** @var array{ingresos:float,egresos:float,total:float,cantidad:int} $resumen */
/** @var list<array<string,mixed>> $movimientos */
/** @var array<string,mixed>|null $cierre */
/** @var list<array<string,mixed>> $cierres */
/** @var string $error */
/** @var bool $cajaDisponible */
/** @var list<array{modopago:int|null,ingresos:float,egresos:float,total:float,cantidad:int}> $resumenMedios */
/** @var list<array<string,mixed>> $resumenProfesionales */
/** @var int $doctorDetalle */
/** @var bool $cierresDisponible */

$resumenMedios = $resumenMedios ?? [];
$resumenProfesionales = $resumenProfesionales ?? [];
$doctorDetalle = max(0, (int) ($doctorDetalle ?? 0));

$fmtMoney = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '0,00';
    }

    return number_format((float) $v, 2, ',', '.');
};
$turnoLabel = static function (string $t): string {
    return caja_turno_cierre_label($t);
};
$efectivoSistema = 0.0;
foreach ($resumenMedios as $rm) {
    if (($rm['modopago'] ?? -1) === 0) {
        $efectivoSistema = (float) ($rm['total'] ?? 0);
        break;
    }
}
$efectivoDefault = $cierre ? (string) ($cierre['efectivo_declarado'] ?? '') : (string) $efectivoSistema;
$totalBaseDistribuible = 0.0;
$totalProfesional = 0.0;
$totalClinica = 0.0;
$doctorDetalleNombre = '';
foreach ($resumenProfesionales as $rp) {
    if (!empty($rp['distribuible'])) {
        $totalBaseDistribuible += (float) ($rp['ingresos'] ?? 0);
        $totalProfesional += (float) ($rp['profesional_70'] ?? 0);
        $totalClinica += (float) ($rp['clinica_30'] ?? 0);
    }
    if ($doctorDetalle > 0 && (int) ($rp['doctor'] ?? 0) === $doctorDetalle) {
        $doctorDetalleNombre = trim((string) ($rp['doctor_nombre'] ?? ''));
    }
}
$detalleTotal = 0.0;
$detalleProfesional = 0.0;
$detalleClinica = 0.0;
foreach ($movimientos as $m) {
    $importeDetalle = (float) ($m['importecaja'] ?? 0);
    $detalleTotal += $importeDetalle;
    if (
        $importeDetalle > 0
        && (int) ($m['doctor'] ?? 0) > 0
        && trim((string) ($m['doctor_nombre'] ?? '')) !== ''
    ) {
        $parteProfesional = round($importeDetalle * 0.70, 2);
        $detalleProfesional += $parteProfesional;
        $detalleClinica += round($importeDetalle - $parteProfesional, 2);
    }
}
?>
<style>
.caja-detalle-solo-impresion { display: none; }
@media print {
    @page { size: A4 landscape; margin: 10mm; }
    .no-print, nav, .sidebar, .app-nav, header, footer { display: none !important; }
    html, body {
        width: 100%;
        min-height: auto;
        margin: 0;
        background: #fff;
    }
    .app-shell.is-auth {
        display: block !important;
        width: 100%;
        min-height: auto;
    }
    .app-main,
    .site-main {
        width: 100%;
        min-width: 0;
    }
    .site-main { padding: 0 !important; }
    .caja-cierre-page {
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0;
    }
    .table { font-size: 11px; }
    body.print-caja-detalle .caja-cierre-page > * { display: none !important; }
    body.print-caja-detalle .caja-cierre-page > .clinica-print-header,
    body.print-caja-detalle .caja-cierre-page > #detalle-movimientos { display: block !important; }
    body.print-caja-detalle #detalle-movimientos .datatable-top,
    body.print-caja-detalle #detalle-movimientos .datatable-bottom,
    body.print-caja-detalle #detalle-movimientos .dt-export-toolbar { display: none !important; }
    body.print-caja-detalle #detalle-movimientos {
        border: 0;
        box-shadow: none;
        padding: 0;
    }
    body.print-caja-detalle #detalle-movimientos .caja-detalle-pantalla { display: none !important; }
    body.print-caja-detalle #detalle-movimientos .caja-detalle-solo-impresion { display: block !important; }
    body.print-caja-detalle #detalle-movimientos table {
        width: 100%;
        min-width: 0 !important;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 10pt;
    }
    body.print-caja-detalle #detalle-movimientos th,
    body.print-caja-detalle #detalle-movimientos td {
        border: 1px solid #777;
        padding: 3px 4px;
        white-space: normal !important;
        overflow-wrap: anywhere;
    }
    body.print-caja-detalle #detalle-movimientos thead { display: table-header-group; }
    body.print-caja-detalle #detalle-movimientos tfoot { display: table-footer-group; }
    body.print-caja-detalle #detalle-movimientos tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
<div class="container container-wide caja-cierre-page">
    <?= clinica_render_print_header(db(), user_clinica_id(auth_user())) ?>
    <div class="page-head">
        <div>
            <h1>Cierre de caja</h1>
            <p class="muted">Control final de entradas, salidas, efectivo declarado y diferencia (como en Control Salud).</p>
        </div>
        <p class="muted no-print">
            <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
            <a href="/caja.php?fecha_desde=<?= rawurlencode($fecha) ?>&fecha_hasta=<?= rawurlencode($fecha) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Ver movimientos</a>
        </p>
    </div>

    <?php if (!$cajaDisponible): ?>
        <p class="alert alert-error">Falta la tabla <code>caja</code>.</p>
    <?php endif; ?>
    <?php if (!$cierresDisponible): ?>
        <p class="alert alert-error">Falta aplicar la migración <code>sql/migration_031_caja_cierres.sql</code>.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="get" class="agenda-filters form-card no-print" action="/caja_cierre.php">
        <div class="filter-row">
            <label>Fecha
                <input type="date" name="fecha" value="<?= h($fecha) ?>">
            </label>
            <label>Turno
                <select name="turno">
                    <option value="dia"<?= $turno === 'dia' ? ' selected' : '' ?>>Día completo</option>
                    <option value="mañana"<?= $turno === 'mañana' ? ' selected' : '' ?>>Mañana</option>
                    <option value="tarde"<?= $turno === 'tarde' ? ' selected' : '' ?>>Tarde</option>
                </select>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Calcular</button>
        </div>
    </form>

    <div class="agenda-summary">
        <div class="agenda-kpi"><span>Movimientos</span><strong><?= (int) $resumen['cantidad'] ?></strong></div>
        <div class="agenda-kpi"><span>Ingresos</span><strong><?= h($fmtMoney($resumen['ingresos'])) ?></strong></div>
        <div class="agenda-kpi"><span>Egresos</span><strong><?= h($fmtMoney($resumen['egresos'])) ?></strong></div>
        <div class="agenda-kpi"><span>Total sistema</span><strong><?= h($fmtMoney($resumen['total'])) ?></strong></div>
        <?php if ($cierre): ?>
            <div class="agenda-kpi"><span>Declarado</span><strong><?= h($fmtMoney($cierre['efectivo_declarado'] ?? 0)) ?></strong></div>
            <div class="agenda-kpi"><span>Diferencia</span><strong><?= h($fmtMoney($cierre['diferencia'] ?? 0)) ?></strong></div>
        <?php endif; ?>
    </div>

    <?php if ($resumenMedios !== []): ?>
        <section class="card-like caja-cierre-medios">
            <h2>Totales por medio de pago</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Medio</th>
                            <th>Movimientos</th>
                            <th>Ingresos</th>
                            <th>Egresos</th>
                            <th>Neto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumenMedios as $rm): ?>
                            <tr>
                                <td><?= h(caja_modopago_label($rm['modopago'] ?? null)) ?></td>
                                <td><?= (int) ($rm['cantidad'] ?? 0) ?></td>
                                <td><?= h($fmtMoney($rm['ingresos'] ?? 0)) ?></td>
                                <td><?= h($fmtMoney($rm['egresos'] ?? 0)) ?></td>
                                <td><strong><?= h($fmtMoney($rm['total'] ?? 0)) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="muted">Efectivo en sistema (modo 0): <strong><?= h($fmtMoney($efectivoSistema)) ?></strong></p>
        </section>
    <?php endif; ?>

    <?php if ($resumenProfesionales !== []): ?>
        <section class="card-like caja-cierre-profesionales">
            <h2>Distribución informativa por profesional</h2>
            <p class="muted">
                Simulación sobre los ingresos positivos: 70% para el profesional y 30% para la clínica.
                No genera pagos, liquidaciones ni movimientos de caja.
            </p>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Profesional</th>
                            <th>Movimientos</th>
                            <th>Ingresos (base)</th>
                            <th>Egresos</th>
                            <th>Neto caja</th>
                            <th>Profesional 70%</th>
                            <th>Clínica 30%</th>
                            <th class="no-print">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumenProfesionales as $rp): ?>
                            <?php
                            $distribuible = !empty($rp['distribuible']);
                            $nombreProfesional = trim((string) ($rp['doctor_nombre'] ?? ''));
                            if ($nombreProfesional === '') {
                                $nombreProfesional = 'Sin profesional / no distribuible';
                            }
                            ?>
                            <tr>
                                <td>
                                    <?= h($nombreProfesional) ?>
                                    <?php if (!$distribuible): ?>
                                        <span class="muted small">(sin reparto)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) ($rp['cantidad'] ?? 0) ?></td>
                                <td><?= h($fmtMoney($rp['ingresos'] ?? 0)) ?></td>
                                <td><?= h($fmtMoney($rp['egresos'] ?? 0)) ?></td>
                                <td><?= h($fmtMoney($rp['total'] ?? 0)) ?></td>
                                <td><strong><?= $distribuible ? h($fmtMoney($rp['profesional_70'] ?? 0)) : '—' ?></strong></td>
                                <td><strong><?= $distribuible ? h($fmtMoney($rp['clinica_30'] ?? 0)) : '—' ?></strong></td>
                                <td class="no-print">
                                    <?php if ((int) ($rp['doctor'] ?? 0) > 0): ?>
                                        <a class="btn btn-sm btn-ghost" href="/caja_cierre.php?fecha=<?= rawurlencode($fecha) ?>&amp;turno=<?= rawurlencode($turno) ?>&amp;doctor=<?= (int) $rp['doctor'] ?>#detalle-movimientos">
                                            Ver detalle
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2">Totales distribuibles</th>
                            <th><?= h($fmtMoney($totalBaseDistribuible)) ?></th>
                            <th colspan="2"></th>
                            <th><?= h($fmtMoney($totalProfesional)) ?></th>
                            <th><?= h($fmtMoney($totalClinica)) ?></th>
                            <th class="no-print"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="muted small">Los egresos se informan aparte y no reducen la base del reparto.</p>
        </section>
    <?php endif; ?>

    <?php if ($cierre): ?>
        <p class="alert alert-info">
            Caja cerrada para <?= h($fecha) ?> · <?= h($turnoLabel($turno)) ?>.
            Los movimientos se muestran como detalle histórico; cualquier corrección debe hacerse con un nuevo contra movimiento.
        </p>
    <?php endif; ?>

    <section class="card-like" id="detalle-movimientos">
        <div class="page-head">
            <h2>
                Detalle de movimientos
                <?php if ($doctorDetalle > 0): ?>
                    · <?= h($doctorDetalleNombre !== '' ? $doctorDetalleNombre : ('Profesional #' . $doctorDetalle)) ?>
                <?php endif; ?>
            </h2>
            <?php if ($movimientos !== []): ?>
                <button type="button" class="btn btn-sm btn-ghost no-print" onclick="imprimirDetalleCaja()">
                    <i class="bi bi-printer" aria-hidden="true"></i> Imprimir detalle
                </button>
            <?php endif; ?>
        </div>
        <?php if ($doctorDetalle > 0): ?>
            <p class="no-print">
                <a class="btn btn-sm btn-ghost" href="/caja_cierre.php?fecha=<?= rawurlencode($fecha) ?>&amp;turno=<?= rawurlencode($turno) ?>#detalle-movimientos">
                    Ver todos los profesionales
                </a>
            </p>
        <?php endif; ?>
        <?php if ($movimientos === []): ?>
            <p class="empty-state">No hay movimientos para esta fecha y turno.</p>
        <?php else: ?>
            <div class="table-wrap caja-detalle-pantalla">
                <table id="tbl-caja-cierre-movs" class="table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Profesional</th>
                            <th>Cobertura</th>
                            <th>Turno</th>
                            <th>Medio</th>
                            <th>Observaciones</th>
                            <th>Tipo</th>
                            <th>Importe total</th>
                            <th>Profesional 70%</th>
                            <th>Clínica 30%</th>
                            <?php if (!$cierre): ?>
                                <th class="no-print">Corrección</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <?php
                            $imp = (float) ($m['importecaja'] ?? 0);
                            $cob = trim((string) ($m['cobertura_nombre'] ?? ''));
                            if ($cob === '') {
                                $idCob = (int) ($m['idcoberturacaja'] ?? 0);
                                $cob = $idCob > 0 ? 'Sin catálogo · #' . $idCob : '—';
                            }
                            $turnoMov = caja_turno_label((string) ($m['turnocaja'] ?? ''));
                            $medioMov = caja_modopago_label(isset($m['modopago']) && $m['modopago'] !== '' && $m['modopago'] !== null ? (int) $m['modopago'] : null);
                            $obsMov = trim((string) ($m['observaciones'] ?? ''));
                            $movDistribuible = $imp > 0
                                && (int) ($m['doctor'] ?? 0) > 0
                                && trim((string) ($m['doctor_nombre'] ?? '')) !== '';
                            $movProfesional = $movDistribuible ? round($imp * 0.70, 2) : 0.0;
                            $movClinica = $movDistribuible ? round($imp - $movProfesional, 2) : 0.0;
                            ?>
                            <tr>
                                <td>#<?= (int) ($m['id'] ?? 0) ?></td>
                                <td><?= h(trim((string) ($m['doctor_nombre'] ?? '')) ?: '—') ?></td>
                                <td class="cell-clip" title="<?= h($cob) ?>"><?= h($cob) ?></td>
                                <td><?= h($turnoMov !== '' ? $turnoMov : '—') ?></td>
                                <td><?= h($medioMov) ?></td>
                                <td class="cell-clip" title="<?= h($obsMov) ?>"><?= h($obsMov !== '' ? $obsMov : '—') ?></td>
                                <td><?= $imp < 0 ? 'Egreso' : 'Ingreso' ?></td>
                                <td><strong><?= h($fmtMoney($imp)) ?></strong></td>
                                <td><strong><?= $movDistribuible ? h($fmtMoney($movProfesional)) : '—' ?></strong></td>
                                <td><strong><?= $movDistribuible ? h($fmtMoney($movClinica)) : '—' ?></strong></td>
                                <?php if (!$cierre): ?>
                                    <td class="no-print"><a class="btn btn-sm btn-ghost" href="/caja_form.php?contra=<?= (int) ($m['id'] ?? 0) ?>"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Contra movimiento</a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="caja-detalle-solo-impresion">
                <table class="table">
                    <colgroup>
                        <col style="width:16%">
                        <col style="width:12%">
                        <col style="width:30%">
                        <col style="width:10%">
                        <col style="width:11%">
                        <col style="width:11%">
                        <col style="width:10%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Cobertura</th>
                            <th>Medio</th>
                            <th>Observaciones</th>
                            <th>Tipo</th>
                            <th>Total</th>
                            <th>Prof. 70%</th>
                            <th>Clínica 30%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <?php
                            $imp = (float) ($m['importecaja'] ?? 0);
                            $cob = trim((string) ($m['cobertura_nombre'] ?? ''));
                            if ($cob === '') {
                                $idCob = (int) ($m['idcoberturacaja'] ?? 0);
                                $cob = $idCob > 0 ? 'Sin catálogo · #' . $idCob : '—';
                            }
                            $medioMov = caja_modopago_label(isset($m['modopago']) && $m['modopago'] !== '' && $m['modopago'] !== null ? (int) $m['modopago'] : null);
                            $obsMov = trim((string) ($m['observaciones'] ?? ''));
                            $movDistribuible = $imp > 0
                                && (int) ($m['doctor'] ?? 0) > 0
                                && trim((string) ($m['doctor_nombre'] ?? '')) !== '';
                            $movProfesional = $movDistribuible ? round($imp * 0.70, 2) : 0.0;
                            $movClinica = $movDistribuible ? round($imp - $movProfesional, 2) : 0.0;
                            ?>
                            <tr>
                                <td><?= h($cob) ?></td>
                                <td><?= h($medioMov) ?></td>
                                <td><?= h($obsMov !== '' ? $obsMov : '—') ?></td>
                                <td><?= $imp < 0 ? 'Egreso' : 'Ingreso' ?></td>
                                <td><strong><?= h($fmtMoney($imp)) ?></strong></td>
                                <td><strong><?= $movDistribuible ? h($fmtMoney($movProfesional)) : '—' ?></strong></td>
                                <td><strong><?= $movDistribuible ? h($fmtMoney($movClinica)) : '—' ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-wrap caja-detalle-totales" style="margin-top:0.75rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Total de movimientos</th>
                            <th>Total profesional 70%</th>
                            <th>Total clínica 30%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?= h($fmtMoney($detalleTotal)) ?></strong></td>
                            <td><strong><?= h($fmtMoney($detalleProfesional)) ?></strong></td>
                            <td><strong><?= h($fmtMoney($detalleClinica)) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!$cierre): ?>
        <section class="form-section card-like">
            <h2 class="form-section-title">Registrar cierre</h2>
            <form method="post" class="form-paciente">
                <?= csrf_field() ?>
                <input type="hidden" name="fecha" value="<?= h($fecha) ?>">
                <input type="hidden" name="turno" value="<?= h($turno) ?>">
                <div class="form-grid-ext">
                    <label>Fecha
                        <input type="text" value="<?= h($fecha) ?>" disabled>
                    </label>
                    <label>Turno
                        <input type="text" value="<?= h($turnoLabel($turno)) ?>" disabled>
                    </label>
                    <label>Total sistema
                        <input type="text" value="<?= h($fmtMoney($resumen['total'])) ?>" disabled>
                    </label>
                    <label>Efectivo en caja (arqueo) *
                        <input type="text" name="efectivo_declarado" required inputmode="decimal" value="<?= h($efectivoDefault) ?>">
                    </label>
                    <p class="muted span-2">Se compara contra el efectivo registrado en movimientos (modo 0): <?= h($fmtMoney($efectivoSistema)) ?></p>
                    <label class="span-2">Observaciones
                        <textarea name="observaciones" rows="3" placeholder="Diferencias, aclaraciones o detalle de arqueo"></textarea>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-lock" aria-hidden="true"></i> Cerrar caja</button>
                    <a class="btn btn-ghost" href="/caja.php?fecha_desde=<?= rawurlencode($fecha) ?>&fecha_hasta=<?= rawurlencode($fecha) ?>">Ver movimientos</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="card-like">
        <h2>Últimos cierres</h2>
        <?php if ($cierres === []): ?>
            <p class="empty-state">Todavía no hay cierres registrados.</p>
        <?php else: ?>
            <div class="table-wrap table-wrap-datatable">
                <table id="tbl-caja-cierres" class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th>Total sistema</th>
                            <th>Declarado</th>
                            <th>Diferencia</th>
                            <th>Usuario</th>
                            <th>Cerrado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cierres as $c): ?>
                            <?php $t = (string) ($c['turno'] ?? 'dia'); ?>
                            <tr>
                                <td><?= h((string) ($c['fecha'] ?? '')) ?></td>
                                <td><?= h($turnoLabel($t)) ?></td>
                                <td><?= h($fmtMoney($c['total_sistema'] ?? 0)) ?></td>
                                <td><?= h($fmtMoney($c['efectivo_declarado'] ?? 0)) ?></td>
                                <td><strong><?= h($fmtMoney($c['diferencia'] ?? 0)) ?></strong></td>
                                <td><?= h(trim((string) ($c['usuario_cierre'] ?? '')) ?: '—') ?></td>
                                <td><?= h((string) ($c['cerrado_en'] ?? '')) ?></td>
                                <td><a class="btn btn-sm btn-ghost" href="/caja_cierre.php?fecha=<?= rawurlencode((string) ($c['fecha'] ?? '')) ?>&turno=<?= rawurlencode($t) ?>">Ver</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<script>
function imprimirDetalleCaja() {
    document.body.classList.add('print-caja-detalle');
    try {
        window.print();
    } finally {
        window.setTimeout(function () {
            document.body.classList.remove('print-caja-detalle');
        }, 0);
    }
}
</script>
