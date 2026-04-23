<?php

declare(strict_types=1);

/** @var array<string, string|int> $f */
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $doctores */
/** @var list<array{id:int|string,nombre:?string}> $cobOpts */
/** @var string $queryString */
/** @var bool $hayFiltros */
/** @var int $movimientosMigrados */
/** @var float $totalHoyGeneral */
/** @var float $totalFiltro */
/** @var list<array{doctor:string,total:float}> $resumenPorDoctor */

$qsSuffix = $queryString !== '' ? '&' . $queryString : '';
$nuevoQs = $queryString !== '' ? '?' . $queryString : '';

$fmtMoney = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '0,00';
    }

    return number_format((float) $v, 2, ',', '.');
};
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Caja</h1>
        <p class="muted">Movimientos en <code>caja</code> (doctor, fecha, importe, cobertura y observaciones).</p>
    </div>

    <div class="dashboard-stat-grid" style="margin-top:0;margin-bottom:1rem;">
        <div class="stat-card stat-card-kpi">
            <div class="stat-card-kpi-head"><span class="stat-card-kpi-label">Caja general hoy</span></div>
            <div class="stat-card-kpi-display"><span class="stat-card-kpi-value"><?= h($fmtMoney($totalHoyGeneral)) ?></span></div>
        </div>
        <?php if ($hayFiltros): ?>
            <div class="stat-card stat-card-kpi">
                <div class="stat-card-kpi-head"><span class="stat-card-kpi-label">Total del filtro</span></div>
                <div class="stat-card-kpi-display"><span class="stat-card-kpi-value"><?= h($fmtMoney($totalFiltro)) ?></span></div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($hayFiltros && $resumenPorDoctor !== []): ?>
        <div class="form-card" style="margin-bottom:1rem;">
            <h2 class="form-section-title">Resumen por profesional (filtro actual)</h2>
            <ul class="odontograma-leyenda-list" style="margin:0;">
                <?php foreach ($resumenPorDoctor as $d): ?>
                    <li><?= h($d['doctor']) ?>: <strong><?= h($fmtMoney($d['total'])) ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="get" class="agenda-filters form-card" action="/caja.php">
        <h2 class="form-section-title" style="margin-bottom:0.35rem;">Buscador de caja</h2>
        <div class="filter-row">
            <label>Profesional
                <select name="doctor">
                    <option value="0">Todos</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"<?= (int) ($f['doctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Fecha desde
                <input type="date" name="fecha_desde" value="<?= h((string) ($f['fecha_desde'] ?? '')) ?>">
            </label>
            <label>Fecha hasta
                <input type="date" name="fecha_hasta" value="<?= h((string) ($f['fecha_hasta'] ?? '')) ?>">
            </label>
            <label>Cobertura
                <select name="idcoberturacaja">
                    <?php catalogo_select_options($cobOpts, (int) ($f['idcoberturacaja'] ?? 0), 'Todas') ?>
                </select>
            </label>
            <label>Texto (turno / observ.)
                <input type="text" name="q" maxlength="120" placeholder="Buscar texto..." value="<?= h((string) ($f['q'] ?? '')) ?>">
            </label>
        </div>
        <div class="filter-row">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
            <?php if ($hayFiltros): ?>
                <a class="btn btn-ghost" href="/caja.php"><i class="bi bi-funnel" aria-hidden="true"></i> Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="page-actions">
        <a class="btn btn-primary" href="/caja_form.php<?= h($nuevoQs) ?>"><i class="bi bi-cash-coin" aria-hidden="true"></i> Nuevo movimiento</a>
    </div>

    <?php if ($rows === []): ?>
        <p class="empty-state">No hay movimientos de caja con estos criterios.</p>
    <?php else: ?>
        <?php
        $totalImporte = 0.0;
        ?>
        <div class="table-wrap table-wrap-datatable">
            <table id="tbl-caja" class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Profesional</th>
                        <th>Cobertura</th>
                        <th>Turno</th>
                        <th>Observaciones</th>
                        <th>Importe</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $imp = (float) ($r['importecaja'] ?? 0);
                        $totalImporte += $imp;
                        $turno = trim((string) ($r['turnocaja'] ?? ''));
                        $obs = trim((string) ($r['observaciones'] ?? ''));
                        $cob = trim((string) ($r['cobertura_nombre'] ?? ''));
                        if ($cob === '') {
                            $idCob = (int) ($r['idcoberturacaja'] ?? 0);
                            $cob = $idCob > 0 ? 'Sin catálogo · #' . $idCob : '—';
                        }
                        ?>
                        <tr>
                            <td><?= h((string) ($r['fechacaja'] ?? '')) ?></td>
                            <td><?= h(trim((string) ($r['doctor_nombre'] ?? '')) ?: '—') ?></td>
                            <td class="cell-clip" title="<?= h($cob) ?>"><?= h($cob) ?></td>
                            <td class="cell-clip" title="<?= h($turno) ?>"><?= h($turno !== '' ? $turno : '—') ?></td>
                            <td class="cell-clip" title="<?= h($obs) ?>"><?= h($obs !== '' ? $obs : '—') ?></td>
                            <td><strong><?= h($fmtMoney($imp)) ?></strong></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" title="Editar" href="/caja_form.php?id=<?= (int) ($r['id'] ?? 0) ?><?= h($qsSuffix) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                <form action="/caja_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar este movimiento de caja?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i><span class="btn-label"> Eliminar</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="ordenes-totales-row">
                        <td colspan="5"><strong>Total (filtro actual)</strong></td>
                        <td><strong><?= h($fmtMoney($totalImporte)) ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

