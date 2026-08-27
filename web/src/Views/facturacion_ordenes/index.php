<?php

declare(strict_types=1);

/** @var int $idOs */
/** @var string $fechaDesde */
/** @var string $fechaHasta */
/** @var list<array{id:int|string,nombre:?string}> $cobOpts */
/** @var string $cobNombre */
/** @var string $vista pendientes|facturadas */
/** @var list<array<string,mixed>> $rows */
/** @var bool $mostrarReporte */

$esFacturadas = ($vista ?? 'pendientes') === 'facturadas';
$estadoLabel = $esFacturadas ? 'F (facturadas)' : 'A (a facturar)';

$fmtMoney = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '0,00';
    }

    return number_format((float) $v, 2, ',', '.');
};

$pacienteNombre = static function (array $r): string {
    $a = trim((string) ($r['paciente_apellido'] ?? ''));
    $n = trim((string) ($r['paciente_nombres'] ?? ''));
    if ($a !== '' && $n !== '') {
        return $a . ', ' . $n;
    }
    if ($a !== '') {
        return $a;
    }
    if ($n !== '') {
        return $n;
    }

    return '—';
};

$cantidad = static function (array $r): int {
    $s = (int) ($r['sesiones'] ?? 0);

    return $s > 0 ? $s : 1;
};

$totalCosto = 0.0;
$totalCant = 0;
foreach ($rows as $r) {
    $c = $cantidad($r);
    $totalCant += $c;
    $totalCosto += is_numeric($r['costo_os'] ?? null) ? (float) $r['costo_os'] : 0.0;
}
?>
<style>
@media print {
    @page { size: A4 landscape; margin: 10mm; }
    .sidebar,
    .topbar,
    .no-print { display: none !important; }
    html,
    body { min-height: auto; background: #fff; color: #000; }
    .app-shell.is-auth { display: block !important; min-height: auto; }
    .app-main { width: 100%; }
    .site-main { padding: 0 !important; }
    .site-main > .container:not(.container-wide) { display: none !important; }
    .container.container-wide {
        width: 100%;
        max-width: none;
        margin: 0;
        padding: 0;
    }
    .facturacion-reporte {
        margin: 0;
        padding: 0;
        box-shadow: none;
        border: none;
        background: #fff;
    }
    .facturacion-reporte .table-wrap { overflow: visible; }
    .facturacion-reporte table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
    }
    .facturacion-reporte thead { display: table-header-group; }
    .facturacion-reporte tfoot { display: table-footer-group; }
    .facturacion-reporte tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .facturacion-reporte th,
    .facturacion-reporte td {
        border: 1px solid #777;
        padding: 4px 6px;
    }
    .facturacion-reporte th {
        background: #e9ecef !important;
        color: #000;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .facturacion-reporte a {
        color: #000;
        text-decoration: none;
    }
}
.facturacion-reporte { margin-top: 1.5rem; }
.facturacion-reporte table { width: 100%; font-size: 0.9rem; }
.facturacion-reporte th, .facturacion-reporte td { padding: 0.35rem 0.5rem; vertical-align: top; }
.facturacion-reporte .num { text-align: right; white-space: nowrap; }
.facturacion-meta { margin-bottom: 1rem; }
</style>

<div class="container container-wide">
    <div class="page-head no-print">
        <div>
            <h1>Facturación a obra social</h1>
            <p class="muted">Generá el reporte para imprimir. <strong>A facturar</strong> permite marcar órdenes como facturadas; <strong>Ya facturadas</strong> sirve para reimprimir un lote ya cerrado.</p>
        </div>
        <p class="muted"><a href="/ordenes.php?estado_os=<?= $esFacturadas ? 'F' : 'A' ?><?= $idOs > 0 ? '&idobrasocial=' . (int) $idOs : '' ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Órdenes</a></p>
    </div>

    <form method="get" action="/facturacion_ordenes.php" class="form-card no-print">
        <input type="hidden" name="buscar" value="1">
        <div class="filter-row">
            <label>
                Mostrar
                <select name="vista">
                    <option value="pendientes"<?= !$esFacturadas ? ' selected' : '' ?>>A facturar (pendientes)</option>
                    <option value="facturadas"<?= $esFacturadas ? ' selected' : '' ?>>Ya facturadas (reimpresión)</option>
                </select>
            </label>
            <label>
                Obra social / cobertura
                <select name="idobrasocial" required>
                    <option value="">— Elegir —</option>
                    <?php foreach ($cobOpts as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= $idOs === (int) $c['id'] ? ' selected' : '' ?>><?= h((string) ($c['nombre'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Desde
                <input type="date" name="fecha_desde" value="<?= h($fechaDesde) ?>" required>
            </label>
            <label>
                Hasta
                <input type="date" name="fecha_hasta" value="<?= h($fechaHasta) ?>" required>
            </label>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Generar reporte</button>
            </div>
        </div>
    </form>

    <?php if ($mostrarReporte): ?>
        <div class="facturacion-reporte form-card" id="reporte-facturacion">
            <?= clinica_render_print_header(db(), user_clinica_id(auth_user())) ?>
            <div class="facturacion-meta">
                <h2 style="margin:0 0 0.5rem;">Reporte de facturación</h2>
                <p style="margin:0;"><strong>Obra social:</strong> <?= h($cobNombre !== '' ? $cobNombre : ('#' . $idOs)) ?></p>
                <p style="margin:0.25rem 0 0;"><strong>Período:</strong> <?= h($fechaDesde) ?> — <?= h($fechaHasta) ?></p>
                <p style="margin:0.25rem 0 0;" class="muted"><?= count($rows) ?> orden(es) con estado <strong><?= h($estadoLabel) ?></strong></p>
            </div>

            <?php if ($rows === []): ?>
                <p class="alert"><?= $esFacturadas
                    ? 'No hay órdenes facturadas para esos filtros.'
                    : 'No hay órdenes pendientes de facturar para esos filtros.' ?></p>
            <?php else: ?>
                <div class="no-print page-actions" style="margin-bottom:1rem;">
                    <button type="button" class="btn btn-primary" onclick="window.print();"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir / PDF</button>
                    <?php if ($esFacturadas): ?>
                        <a class="btn btn-ghost" href="/facturacion_ordenes.php?buscar=1&amp;vista=pendientes&amp;idobrasocial=<?= (int) $idOs ?>&amp;fecha_desde=<?= rawurlencode($fechaDesde) ?>&amp;fecha_hasta=<?= rawurlencode($fechaHasta) ?>">Ver pendientes (A)</a>
                    <?php else: ?>
                        <form method="post" action="/facturacion_ordenes.php" style="display:inline;"
                            onsubmit="return confirm('¿Actualizar costos de TODAS las órdenes pendientes (A) del período desde los aranceles vigentes?\n\nNo se modifican órdenes ya facturadas.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="actualizar_costos">
                            <input type="hidden" name="idobrasocial" value="<?= (int) $idOs ?>">
                            <input type="hidden" name="fecha_desde" value="<?= h($fechaDesde) ?>">
                            <input type="hidden" name="fecha_hasta" value="<?= h($fechaHasta) ?>">
                            <button type="submit" class="btn btn-ghost">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Actualizar costos desde aranceles
                            </button>
                        </form>
                        <a class="btn btn-ghost" href="/facturacion_ordenes.php?buscar=1&amp;vista=facturadas&amp;idobrasocial=<?= (int) $idOs ?>&amp;fecha_desde=<?= rawurlencode($fechaDesde) ?>&amp;fecha_hasta=<?= rawurlencode($fechaHasta) ?>">Ver facturadas (F)</a>
                    <?php endif; ?>
                </div>

                <?php if (!$esFacturadas): ?>
                <form method="post" action="/facturacion_ordenes.php" id="form-marcar-facturadas">
                    <?= csrf_field() ?>
                    <input type="hidden" name="idobrasocial" value="<?= (int) $idOs ?>">
                    <input type="hidden" name="fecha_desde" value="<?= h($fechaDesde) ?>">
                    <input type="hidden" name="fecha_hasta" value="<?= h($fechaHasta) ?>">
                <?php endif; ?>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php if (!$esFacturadas): ?>
                                    <th class="no-print" style="width:2.5rem;">
                                        <input type="checkbox" id="chk-todos" checked title="Seleccionar todas">
                                    </th>
                                    <?php endif; ?>
                                    <th>Afiliado</th>
                                    <th>Paciente</th>
                                    <th>Cód. práctica</th>
                                    <th>Práctica</th>
                                    <th>Fecha</th>
                                    <th class="num">Cant.</th>
                                    <th class="num">Costo práctica</th>
                                    <th class="no-print">Orden</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <?php
                                    $c = $cantidad($r);
                                    $costo = is_numeric($r['costo_os'] ?? null) ? (float) $r['costo_os'] : 0.0;
                                    ?>
                                    <tr>
                                        <?php if (!$esFacturadas): ?>
                                        <td class="no-print">
                                            <input type="checkbox" name="orden_ids[]" value="<?= (int) $r['id'] ?>" checked class="chk-orden">
                                        </td>
                                        <?php endif; ?>
                                        <td><?= h((string) ($r['paciente_nro_os'] ?? '—')) ?></td>
                                        <td><?= h($pacienteNombre($r)) ?></td>
                                        <td><?= trim((string) ($r['practica_codigo'] ?? '')) !== ''
                                            ? h((string) $r['practica_codigo'])
                                            : '—' ?></td>
                                        <td><?= h(trim((string) ($r['practica_nombre'] ?? '')) !== '' ? (string) $r['practica_nombre'] : '—') ?></td>
                                        <td><?= h((string) ($r['fecha_orden'] ?? '—')) ?></td>
                                        <td class="num"><?= $c ?></td>
                                        <td class="num">$ <?= h($fmtMoney($costo)) ?></td>
                                        <td class="no-print"><a href="/orden_form.php?id=<?= (int) $r['id'] ?>">#<?= (int) $r['id'] ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="<?= $esFacturadas ? 5 : 6 ?>" class="num">Totales</th>
                                    <th class="num"><?= $totalCant ?></th>
                                    <th class="num">$ <?= h($fmtMoney($totalCosto)) ?></th>
                                    <th class="no-print"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                <?php if (!$esFacturadas): ?>
                    <div class="page-actions" style="margin-top:1rem;">
                        <button type="submit" class="btn btn-primary"
                            onclick="return confirm('¿Marcar las órdenes seleccionadas como FACTURADAS (estado_os = F)?');">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i> Marcar seleccionadas como facturadas
                        </button>
                    </div>
                </form>

                <script>
                (function () {
                    const master = document.getElementById('chk-todos');
                    const boxes = document.querySelectorAll('.chk-orden');
                    if (!master) return;
                    master.addEventListener('change', function () {
                        boxes.forEach(function (b) { b.checked = master.checked; });
                    });
                })();
                </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php elseif ($idOs < 1): ?>
        <p class="muted no-print" style="margin-top:1.5rem;">Elegí obra social y rango de fechas para ver el reporte.</p>
    <?php endif; ?>
</div>
