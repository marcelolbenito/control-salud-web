<?php

declare(strict_types=1);

/** @var array<string, string|int> $f */
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $doctores */
/** @var string $queryString */
/** @var bool $hayFiltros */
/** @var int $totalCantidadSesiones */
/** @var int $totalFilasEnTabla */

$qsSuffix = $queryString !== '' ? '&' . $queryString : '';
$nuevoQs = $queryString !== '' ? '?' . $queryString : '';
$totalBd = (int) ($totalFilasEnTabla ?? 0);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Sesiones</h1>
        <p class="muted">
            Cada fila es una <strong>atención registrada contra una orden</strong> (cuándo, quién atiende, cuántas unidades).
            Si la base todavía no tiene datos en <code>pacientes_sesiones</code>, la grilla queda vacía hasta que cargues sesiones (p. ej. desde <strong>Editar orden → Nueva sesión</strong>).
            <?php if ($totalBd > 0): ?>
                Hay <strong><?= h(number_format($totalBd, 0, ',', '.')) ?></strong> registro(s) en total en la tabla.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($hayFiltros): ?>
        <div class="dashboard-stat-grid" style="margin-top:0;margin-bottom:1rem;">
            <div class="stat-card stat-card-kpi">
                <div class="stat-card-kpi-head"><span class="stat-card-kpi-label">Total sesiones (suma cantidades, listado)</span></div>
                <div class="stat-card-kpi-display"><span class="stat-card-kpi-value"><?= h(number_format((int) $totalCantidadSesiones, 0, ',', '.')) ?></span></div>
            </div>
        </div>
    <?php endif; ?>

    <form method="get" class="agenda-filters form-card" action="/sesiones.php">
        <h2 class="form-section-title" style="margin-bottom:0.35rem;">Buscador de sesiones</h2>
        <div class="filter-row">
            <label>Paciente (nombre, apellido, DNI o Nro HC)
                <input type="text" name="paciente" maxlength="120" placeholder="Ej. García o 12345" value="<?= h((string) ($f['paciente'] ?? '')) ?>">
            </label>
            <label>Nro HC
                <input type="number" name="nrohc" min="0" placeholder="Exacto" value="<?= h((string) ($f['nrohc'] ?? '')) ?>">
            </label>
            <label>Id orden
                <input type="number" name="idorden" min="0" value="<?= h((string) ($f['idorden'] ?? '')) ?>">
            </label>
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
        </div>
        <div class="filter-row">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
            <?php if ($hayFiltros): ?>
                <a class="btn btn-ghost" href="/sesiones.php"><i class="bi bi-funnel" aria-hidden="true"></i> Limpiar filtros</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="page-actions">
        <a class="btn btn-primary" href="/sesion_form.php<?= h($nuevoQs) ?>"><i class="bi bi-calendar2-plus" aria-hidden="true"></i> Nueva sesión</a>
    </div>

    <?php if ($rows === []): ?>
        <?php if (!$hayFiltros && $totalBd === 0): ?>
            <p class="empty-state">
                No hay sesiones cargadas en la base. Podés crear la primera desde una orden guardada:
                <strong>Órdenes</strong> → abrir la orden → <strong>Nueva sesión</strong>.
            </p>
        <?php else: ?>
            <p class="empty-state">No hay sesiones que coincidan con estos filtros. Probá ampliar fechas o limpiar el buscador.</p>
        <?php endif; ?>
    <?php else: ?>
        <div class="table-wrap table-wrap-datatable">
            <table id="tbl-sesiones" class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Paciente</th>
                        <th>Orden</th>
                        <th>Profesional</th>
                        <th>Cant.</th>
                        <th>Observaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $obs = trim((string) ($r['observaciones'] ?? ''));
                        $docNom = trim((string) ($r['doctor_nombre'] ?? ''));
                        $numOr = $r['orden_numero'] ?? null;
                        $ordenLabel = (int) ($r['idorden'] ?? 0) > 0
                            ? ('#' . (int) $r['idorden'] . ($numOr !== null && $numOr !== '' ? ' (nº ' . (int) $numOr . ')' : ''))
                            : '—';
                        $hc = (int) ($r['NroPaci'] ?? 0);
                        $etiq = trim((string) ($r['paciente_etiqueta'] ?? ''));
                        $pacCell = $etiq !== '' ? $etiq . ' · HC ' . $hc : ('HC ' . $hc);
                        ?>
                        <tr>
                            <td><?= h((string) ($r['fecha_sesion'] ?? '')) ?></td>
                            <td><?= h($pacCell) ?></td>
                            <td><?= h($ordenLabel) ?></td>
                            <td><?= h($docNom !== '' ? $docNom : '—') ?></td>
                            <td><strong><?= (int) ($r['cantidad_sesiones'] ?? 0) ?></strong></td>
                            <td class="cell-clip" title="<?= h($obs) ?>"><?= h($obs !== '' ? $obs : '—') ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" href="/sesion_form.php?id=<?= (int) ($r['id'] ?? 0) ?><?= h($qsSuffix) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                <form action="/sesion_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar esta sesión?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) ($r['id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-icon"><i class="bi bi-trash" aria-hidden="true"></i><span class="btn-label"> Eliminar</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
