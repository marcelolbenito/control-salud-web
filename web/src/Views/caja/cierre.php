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
/** @var bool $cierresDisponible */

$fmtMoney = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '0,00';
    }

    return number_format((float) $v, 2, ',', '.');
};
$turnoLabel = static function (string $t): string {
    if ($t === 'mañana') {
        return 'Mañana';
    }
    if ($t === 'tarde') {
        return 'Tarde';
    }

    return 'Día completo';
};
$efectivoDefault = $cierre ? (string) ($cierre['efectivo_declarado'] ?? '') : (string) $resumen['total'];
?>
<div class="container container-wide">
    <div class="page-head">
        <div>
            <h1>Cierre de caja</h1>
            <p class="muted">Control final de entradas, salidas, efectivo declarado y diferencia.</p>
        </div>
        <p class="muted"><a href="/caja.php?fecha_desde=<?= rawurlencode($fecha) ?>&fecha_hasta=<?= rawurlencode($fecha) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Ver movimientos</a></p>
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

    <form method="get" class="agenda-filters form-card" action="/caja_cierre.php">
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

    <?php if ($cierre): ?>
        <p class="alert alert-info">
            Caja cerrada para <?= h($fecha) ?> · <?= h($turnoLabel($turno)) ?>.
            Los movimientos se muestran como detalle histórico; cualquier corrección debe hacerse con un nuevo contra movimiento.
        </p>
    <?php endif; ?>

    <section class="card-like">
        <h2>Detalle de movimientos</h2>
        <?php if ($movimientos === []): ?>
            <p class="empty-state">No hay movimientos para esta fecha y turno.</p>
        <?php else: ?>
            <div class="table-wrap table-wrap-datatable">
                <table id="tbl-caja-cierre-movs" class="table">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Profesional</th>
                            <th>Cobertura</th>
                            <th>Turno / detalle</th>
                            <th>Observaciones</th>
                            <th>Tipo</th>
                            <th>Importe</th>
                            <?php if (!$cierre): ?>
                                <th>Corrección</th>
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
                            $turnoMov = trim((string) ($m['turnocaja'] ?? ''));
                            $obsMov = trim((string) ($m['observaciones'] ?? ''));
                            ?>
                            <tr>
                                <td>#<?= (int) ($m['id'] ?? 0) ?></td>
                                <td><?= h(trim((string) ($m['doctor_nombre'] ?? '')) ?: '—') ?></td>
                                <td class="cell-clip" title="<?= h($cob) ?>"><?= h($cob) ?></td>
                                <td class="cell-clip" title="<?= h($turnoMov) ?>"><?= h($turnoMov !== '' ? $turnoMov : '—') ?></td>
                                <td class="cell-clip" title="<?= h($obsMov) ?>"><?= h($obsMov !== '' ? $obsMov : '—') ?></td>
                                <td><?= $imp < 0 ? 'Egreso' : 'Ingreso' ?></td>
                                <td><strong><?= h($fmtMoney($imp)) ?></strong></td>
                                <?php if (!$cierre): ?>
                                    <td><a class="btn btn-sm btn-ghost" href="/caja_form.php?contra=<?= (int) ($m['id'] ?? 0) ?>"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Contra movimiento</a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
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
                    <label>Efectivo declarado *
                        <input type="text" name="efectivo_declarado" required inputmode="decimal" value="<?= h($efectivoDefault) ?>">
                    </label>
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
