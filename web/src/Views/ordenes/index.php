<?php

declare(strict_types=1);

/** @var array<string, string|int> $f */
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $doctores */
/** @var string $ordenesQueryString */
/** @var bool $ordenesFiltrosActivos */

$qsSuffix = $ordenesQueryString !== '' ? '&' . $ordenesQueryString : '';
$nuevaQs = $ordenesQueryString !== '' ? '?' . $ordenesQueryString : '';

function orden_vista_paciente(array $r): string
{
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
}

function orden_tri_selected(string $cur, string $val): string
{
    return (string) $cur === $val ? ' selected' : '';
}

function orden_fmt_money($v): string
{
    if ($v === null || $v === '') {
        return '—';
    }
    if (!is_numeric($v)) {
        return '—';
    }

    return number_format((float) $v, 2, ',', '.');
}
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Órdenes</h1>
        <p class="muted">Listado sobre <code>Pacientes Ordenes</code> (misma tabla que el backup). Filtros alineados a columnas del esquema.</p>
    </div>

    <form method="get" class="agenda-filters form-card" action="/ordenes.php">
        <div class="filter-row">
            <label>
                Nro. HC
                <input type="number" name="nrohc" min="1" placeholder="Todos" value="<?= ($f['nrohc'] ?? 0) > 0 ? (int) $f['nrohc'] : '' ?>">
            </label>
            <label>
                Profesional
                <select name="doctor">
                    <option value="0">Todos</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"<?= (int) ($f['doctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                ID desde
                <input type="number" name="id_desde" min="1" placeholder="—" value="<?= ($f['id_desde'] ?? 0) > 0 ? (int) $f['id_desde'] : '' ?>">
            </label>
            <label>
                ID hasta
                <input type="number" name="id_hasta" min="1" placeholder="—" value="<?= ($f['id_hasta'] ?? 0) > 0 ? (int) $f['id_hasta'] : '' ?>">
            </label>
            <label>
                Fecha desde
                <input type="date" name="fecha_desde" value="<?= h((string) ($f['fecha_desde'] ?? '')) ?>">
            </label>
            <label>
                Fecha hasta
                <input type="date" name="fecha_hasta" value="<?= h((string) ($f['fecha_hasta'] ?? '')) ?>">
            </label>
        </div>
        <div class="filter-row">
            <label>
                Sucursal
                <input type="number" name="sucursal" min="1" placeholder="—" value="<?= ($f['sucursal'] ?? 0) > 0 ? (int) $f['sucursal'] : '' ?>">
            </label>
            <label>
                Id cobertura / OS
                <input type="number" name="idobrasocial" min="1" placeholder="—" value="<?= ($f['idobrasocial'] ?? 0) > 0 ? (int) $f['idobrasocial'] : '' ?>">
            </label>
            <label>
                Id práctica
                <input type="number" name="idpractica" min="1" placeholder="—" value="<?= ($f['idpractica'] ?? 0) > 0 ? (int) $f['idpractica'] : '' ?>">
            </label>
            <label>
                Id derivado
                <input type="number" name="idderivado" min="1" placeholder="—" value="<?= ($f['idderivado'] ?? 0) > 0 ? (int) $f['idderivado'] : '' ?>">
            </label>
            <label>
                Id plan
                <input type="number" name="idplan" min="1" placeholder="—" value="<?= ($f['idplan'] ?? 0) > 0 ? (int) $f['idplan'] : '' ?>">
            </label>
        </div>
        <div class="filter-row">
            <label>
                Estado
                <input type="text" name="estado" maxlength="4" placeholder="Código" value="<?= h((string) ($f['estado'] ?? '')) ?>">
            </label>
            <label>
                Estado OS
                <input type="text" name="estado_os" maxlength="4" placeholder="Código" value="<?= h((string) ($f['estado_os'] ?? '')) ?>">
            </label>
            <label>
                Autorizada
                <select name="autorizada">
                    <option value=""<?= orden_tri_selected((string) ($f['autorizada'] ?? ''), '') ?>>Todas</option>
                    <option value="1"<?= orden_tri_selected((string) ($f['autorizada'] ?? ''), '1') ?>>Sí</option>
                    <option value="0"<?= orden_tri_selected((string) ($f['autorizada'] ?? ''), '0') ?>>No</option>
                </select>
            </label>
            <label>
                Entregada
                <select name="entregada">
                    <option value=""<?= orden_tri_selected((string) ($f['entregada'] ?? ''), '') ?>>Todas</option>
                    <option value="1"<?= orden_tri_selected((string) ($f['entregada'] ?? ''), '1') ?>>Sí</option>
                    <option value="0"<?= orden_tri_selected((string) ($f['entregada'] ?? ''), '0') ?>>No</option>
                </select>
            </label>
            <label>
                Liquidada
                <select name="liquidada">
                    <option value=""<?= orden_tri_selected((string) ($f['liquidada'] ?? ''), '') ?>>Todas</option>
                    <option value="1"<?= orden_tri_selected((string) ($f['liquidada'] ?? ''), '1') ?>>Sí</option>
                    <option value="0"<?= orden_tri_selected((string) ($f['liquidada'] ?? ''), '0') ?>>No</option>
                </select>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Filtrar</button>
            <?php if ($ordenesFiltrosActivos): ?>
                <a class="btn btn-ghost" href="/ordenes.php">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="page-actions">
        <a class="btn btn-primary" href="/orden_form.php<?= h($nuevaQs) ?>"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i> Nueva orden</a>
    </div>

    <?php if ($rows === []): ?>
        <p class="empty-state">No hay órdenes con estos criterios. Usá «Nueva orden» o ajustá los filtros.</p>
    <?php else: ?>
        <div class="table-wrap table-wrap-datatable">
            <table id="tbl-ordenes" class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nro HC</th>
                        <th>Paciente</th>
                        <th>Nº orden</th>
                        <th>Suc.</th>
                        <th>Cobertura</th>
                        <th>Práct.</th>
                        <th>Profesional</th>
                        <th>Fecha</th>
                        <th>Costo</th>
                        <th>Pago</th>
                        <th>Costo OS</th>
                        <th>Ses.</th>
                        <th>Aut.</th>
                        <th>Entr.</th>
                        <th>Liq.</th>
                        <th>Est.</th>
                        <th>Est. OS</th>
                        <th>Obs.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $obs = (string) ($r['observaciones'] ?? '');
                        $obsClip = function_exists('mb_strimwidth')
                            ? mb_strimwidth($obs, 0, 28, '…', 'UTF-8')
                            : (strlen($obs) > 28 ? substr($obs, 0, 25) . '...' : $obs);
                        $siNo = static function ($x): string {
                            return !empty($x) ? 'Sí' : 'No';
                        };
                        $numOrden = $r['numero'] ?? null;
                        ?>
                        <tr>
                            <td><?= (int) $r['id'] ?></td>
                            <td><?= (int) $r['NroPaci'] ?></td>
                            <td><?= h(orden_vista_paciente($r)) ?></td>
                            <td><?= $numOrden !== null && $numOrden !== '' ? h((string) $numOrden) : '—' ?></td>
                            <?php
                            $idSuc = isset($r['sucursal']) && $r['sucursal'] !== '' && $r['sucursal'] !== null ? (int) $r['sucursal'] : 0;
                            $nomSuc = trim((string) ($r['sucursal_nombre'] ?? ''));
                            $sucTxt = $nomSuc !== '' ? $nomSuc : ($idSuc > 0 ? (string) $idSuc : '—');
                            ?>
                            <td class="cell-clip" title="<?= h($sucTxt) ?>"><?= h($sucTxt) ?></td>
                            <?php
                            $idOs = (int) ($r['idobrasocial'] ?? 0);
                            $nomCob = trim((string) ($r['cobertura_nombre'] ?? ''));
                            $cobTxt = $nomCob !== '' ? $nomCob : ($idOs > 0 ? (string) $idOs : '—');
                            ?>
                            <td class="cell-clip" title="<?= h($cobTxt) ?>"><?= h($cobTxt) ?></td>
                            <?php
                            $idPr = (int) ($r['idpractica'] ?? 0);
                            $nomPr = trim((string) ($r['practica_nombre'] ?? ''));
                            $prTxt = $nomPr !== '' ? $nomPr : ($idPr > 0 ? (string) $idPr : '—');
                            ?>
                            <td class="cell-clip" title="<?= h($prTxt) ?>"><?= h($prTxt) ?></td>
                            <td><?= h((string) ($r['doctor_nombre'] ?? '')) ?></td>
                            <td><?= !empty($r['fecha_orden']) ? h((string) $r['fecha_orden']) : '—' ?></td>
                            <td><?= h(orden_fmt_money($r['costo'] ?? null)) ?></td>
                            <td><?= h(orden_fmt_money($r['pago'] ?? null)) ?></td>
                            <td><?= h(orden_fmt_money($r['costo_os'] ?? null)) ?></td>
                            <td><?= isset($r['sesiones']) && $r['sesiones'] !== '' && $r['sesiones'] !== null ? h((string) $r['sesiones']) : '—' ?></td>
                            <td><?= $siNo($r['autorizada'] ?? 0) ?></td>
                            <td><?= $siNo($r['entregada'] ?? 0) ?></td>
                            <td><?= $siNo($r['liquidada'] ?? 0) ?></td>
                            <td><?= isset($r['estado']) && (string) $r['estado'] !== '' ? h((string) $r['estado']) : '—' ?></td>
                            <td><?= isset($r['estado_os']) && (string) $r['estado_os'] !== '' ? h((string) $r['estado_os']) : '—' ?></td>
                            <td class="cell-clip" title="<?= h($obs) ?>"><?= h($obsClip) ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" title="Editar" href="/orden_form.php?id=<?= (int) $r['id'] ?><?= h($qsSuffix) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                <form action="/orden_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar esta orden?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Eliminar"><i class="bi bi-trash" aria-hidden="true"></i><span class="btn-label"> Eliminar</span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
