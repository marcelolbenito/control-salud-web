<?php

declare(strict_types=1);

/** @var array<string, string|int> $f */
/** @var list<array<string,mixed>> $rows */
/** @var bool $hayFiltros */
/** @var string $queryString */
/** @var float $totalFiltro */

$fmtMoney = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '0,00';
    }

    return number_format((float) $v, 2, ',', '.');
};
$qsSuffix = $queryString !== '' ? '&' . $queryString : '';
$nuevoQs = $queryString !== '' ? '?' . $queryString : '';
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Pagos</h1>
        <p class="muted">Cobros a paciente/cobertura con trazabilidad por orden y recibo imprimible.</p>
    </div>

    <form method="get" class="agenda-filters form-card" action="/pagos.php">
        <h2 class="form-section-title" style="margin-bottom:0.35rem;">Buscador de pagos</h2>
        <div class="filter-row">
            <label>Nro HC
                <input type="number" name="nrohc" min="0" value="<?= h((string) ($f['nrohc'] ?? '')) ?>">
            </label>
            <label>Id orden
                <input type="number" name="idorden" min="0" value="<?= h((string) ($f['idorden'] ?? '')) ?>">
            </label>
            <label>Quién paga
                <select name="quien">
                    <?php $quien = (string) ($f['quien'] ?? ''); ?>
                    <option value=""<?= $quien === '' ? ' selected' : '' ?>>Todos</option>
                    <option value="P"<?= $quien === 'P' ? ' selected' : '' ?>>Paciente</option>
                    <option value="C"<?= $quien === 'C' ? ' selected' : '' ?>>Cobertura</option>
                    <option value="O"<?= $quien === 'O' ? ' selected' : '' ?>>Otro</option>
                </select>
            </label>
            <label>Fecha desde
                <input type="date" name="fecha_desde" value="<?= h((string) ($f['fecha_desde'] ?? '')) ?>">
            </label>
            <label>Fecha hasta
                <input type="date" name="fecha_hasta" value="<?= h((string) ($f['fecha_hasta'] ?? '')) ?>">
            </label>
        </div>
        <div class="filter-row">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
            <?php if ($hayFiltros): ?>
                <a class="btn btn-ghost" href="/pagos.php"><i class="bi bi-funnel" aria-hidden="true"></i> Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="page-actions">
        <a class="btn btn-primary" href="/pagos_form.php<?= h($nuevoQs) ?>"><i class="bi bi-receipt"></i> Nuevo pago</a>
    </div>

    <?php if ($rows === []): ?>
        <p class="empty-state">No hay pagos para esos criterios.</p>
    <?php else: ?>
        <div class="table-wrap table-wrap-datatable">
            <table id="tbl-pagos" class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Quién</th>
                        <th>Nro HC</th>
                        <th>Orden</th>
                        <th>Forma pago</th>
                        <th>Observaciones</th>
                        <th>Importe</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $q = (string) ($r['quien'] ?? '');
                        $qLabel = $q === 'C' ? 'Cobertura' : ($q === 'O' ? 'Otro' : 'Paciente');
                        ?>
                        <tr>
                            <td><?= h((string) ($r['fecha'] ?? '')) ?></td>
                            <td><?= h($qLabel) ?></td>
                            <td><?= (int) ($r['NroPaci'] ?? 0) ?></td>
                            <td><?= (int) ($r['idorden'] ?? 0) > 0 ? ('#' . (int) $r['idorden']) : '—' ?></td>
                            <td><?= h(trim((string) ($r['forma_pago'] ?? '')) ?: '—') ?></td>
                            <td class="cell-clip" title="<?= h((string) ($r['observaciones'] ?? '')) ?>"><?= h(trim((string) ($r['observaciones'] ?? '')) ?: '—') ?></td>
                            <td><strong><?= h($fmtMoney($r['importe'] ?? 0)) ?></strong></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" href="/pagos_recibo.php?id=<?= (int) $r['id'] ?>" target="_blank" rel="noopener"><i class="bi bi-printer"></i><span class="btn-label"> Recibo</span></a>
                                <a class="btn btn-sm btn-ghost btn-icon" href="/pagos_form.php?id=<?= (int) $r['id'] ?><?= h($qsSuffix) ?>"><i class="bi bi-pencil-square"></i><span class="btn-label"> Editar</span></a>
                                <form action="/pagos_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar este pago?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-icon"><i class="bi bi-trash"></i><span class="btn-label"> Eliminar</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="ordenes-totales-row">
                        <td colspan="6"><strong>Total listado</strong></td>
                        <td><strong><?= h($fmtMoney($totalFiltro)) ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

