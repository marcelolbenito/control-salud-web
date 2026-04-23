<?php

declare(strict_types=1);

/** @var array<string, string|int> $f */
/** @var list<array<string, mixed>> $rows */
/** @var list<array<string, mixed>> $doctores */
/** @var list<array{id:int|string,nombre:?string}> $cobOpts */
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

function orden_estado_checked(array $f, string $key, string $estado): string
{
    $vals = isset($f[$key]) && is_array($f[$key]) ? $f[$key] : [];
    return in_array($estado, $vals, true) ? ' checked' : '';
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

function orden_num($v): float
{
    if ($v === null || $v === '' || !is_numeric($v)) {
        return 0.0;
    }

    return (float) $v;
}

function orden_fmt_ref($idRaw, $nombreRaw): string
{
    $id = ($idRaw !== null && $idRaw !== '' && is_numeric($idRaw)) ? (int) $idRaw : 0;
    $nom = trim((string) $nombreRaw);
    if ($nom !== '' && $id > 0) {
        return $nom . ' · #' . $id;
    }
    if ($nom !== '') {
        return $nom;
    }
    if ($id > 0) {
        return 'Sin catálogo · #' . $id;
    }

    return '—';
}
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Órdenes</h1>
        <p class="muted">Listado sobre <code>Pacientes Ordenes</code> (misma tabla que el backup). Filtros alineados a columnas del esquema.</p>
    </div>

    <form method="get" class="agenda-filters form-card" action="/ordenes.php">
        <h2 class="form-section-title" style="margin-bottom:0.35rem;">Buscador de órdenes</h2>
        <p class="muted small" style="margin-top:0;margin-bottom:0.75rem;">
            Empezá con filtros rápidos (paciente, profesional y fecha). Si necesitás más precisión, abrí «Filtros avanzados».
        </p>

        <div class="filter-row">
            <label>
                Nro. HC (paciente)
                <input type="number" name="nrohc" min="1" placeholder="Ej. 1234" value="<?= ($f['nrohc'] ?? 0) > 0 ? (int) $f['nrohc'] : '' ?>">
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
                Fecha orden desde
                <input type="date" name="fecha_desde" value="<?= h((string) ($f['fecha_desde'] ?? '')) ?>">
            </label>
            <label>
                Fecha orden hasta
                <input type="date" name="fecha_hasta" value="<?= h((string) ($f['fecha_hasta'] ?? '')) ?>">
            </label>
            <label>
                ID desde
                <input type="number" name="id_desde" min="1" placeholder="Opcional" value="<?= ($f['id_desde'] ?? 0) > 0 ? (int) $f['id_desde'] : '' ?>">
            </label>
            <label>
                ID hasta
                <input type="number" name="id_hasta" min="1" placeholder="Opcional" value="<?= ($f['id_hasta'] ?? 0) > 0 ? (int) $f['id_hasta'] : '' ?>">
            </label>
        </div>

        <details style="margin-top:0.75rem;">
            <summary><strong>Filtros avanzados</strong> (cobertura, estados, IVA, autorización, honorarios)</summary>
            <div class="filter-row" style="margin-top:0.6rem;">
                <?php if ($cobOpts !== []): ?>
                    <label>
                        Cobertura / OS
                        <select name="idobrasocial">
                            <?php catalogo_select_options($cobOpts, (int) ($f['idobrasocial'] ?? 0), 'Todas') ?>
                        </select>
                    </label>
                <?php else: ?>
                    <label>
                        Id cobertura / OS
                        <input type="number" name="idobrasocial" min="1" placeholder="Código" value="<?= ($f['idobrasocial'] ?? 0) > 0 ? (int) $f['idobrasocial'] : '' ?>">
                    </label>
                <?php endif; ?>
                <label>
                    Id plan
                    <input type="number" name="idplan" min="1" placeholder="Código" value="<?= ($f['idplan'] ?? 0) > 0 ? (int) $f['idplan'] : '' ?>">
                </label>
                <label>
                    Id práctica
                    <input type="number" name="idpractica" min="1" placeholder="Código" value="<?= ($f['idpractica'] ?? 0) > 0 ? (int) $f['idpractica'] : '' ?>">
                </label>
                <label>
                    Id derivado
                    <input type="number" name="idderivado" min="1" placeholder="Código" value="<?= ($f['idderivado'] ?? 0) > 0 ? (int) $f['idderivado'] : '' ?>">
                </label>
                <label>
                    Médico sesión
                    <select name="sesion_doctor">
                        <option value="0">Todos</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) ($f['sesion_doctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    Estado sesiones
                    <select name="sesion_estado">
                        <option value=""<?= orden_tri_selected((string) ($f['sesion_estado'] ?? ''), '') ?>>Todos</option>
                        <option value="con"<?= orden_tri_selected((string) ($f['sesion_estado'] ?? ''), 'con') ?>>Con sesiones</option>
                        <option value="sin"<?= orden_tri_selected((string) ($f['sesion_estado'] ?? ''), 'sin') ?>>Sin sesiones</option>
                        <option value="pendientes"<?= orden_tri_selected((string) ($f['sesion_estado'] ?? ''), 'pendientes') ?>>Sesiones pendientes</option>
                        <option value="completas"<?= orden_tri_selected((string) ($f['sesion_estado'] ?? ''), 'completas') ?>>Sesiones completas</option>
                    </select>
                </label>
            </div>
            <div class="filter-row">
                <label>
                    Estado (orden)
                    <input type="text" name="estado" maxlength="4" placeholder="A / F / P" value="<?= h((string) ($f['estado'] ?? '')) ?>">
                </label>
                <label>
                    Estado (multi A/F/P)
                    <span class="checkbox-inline-group">
                        <label><input type="checkbox" name="estado_multi[]" value="A"<?= orden_estado_checked($f, 'estado_multi', 'A') ?>> A</label>
                        <label><input type="checkbox" name="estado_multi[]" value="F"<?= orden_estado_checked($f, 'estado_multi', 'F') ?>> F</label>
                        <label><input type="checkbox" name="estado_multi[]" value="P"<?= orden_estado_checked($f, 'estado_multi', 'P') ?>> P</label>
                    </span>
                </label>
                <label>
                    Estado OS
                    <input type="text" name="estado_os" maxlength="4" placeholder="A / F / P" value="<?= h((string) ($f['estado_os'] ?? '')) ?>">
                </label>
                <label>
                    Estado OS (multi A/F/P)
                    <span class="checkbox-inline-group">
                        <label><input type="checkbox" name="estado_os_multi[]" value="A"<?= orden_estado_checked($f, 'estado_os_multi', 'A') ?>> A</label>
                        <label><input type="checkbox" name="estado_os_multi[]" value="F"<?= orden_estado_checked($f, 'estado_os_multi', 'F') ?>> F</label>
                        <label><input type="checkbox" name="estado_os_multi[]" value="P"<?= orden_estado_checked($f, 'estado_os_multi', 'P') ?>> P</label>
                    </span>
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
                <label>
                    Paga IVA
                    <select name="pagaiva">
                        <option value=""<?= orden_tri_selected((string) ($f['pagaiva'] ?? ''), '') ?>>Todas</option>
                        <option value="1"<?= orden_tri_selected((string) ($f['pagaiva'] ?? ''), '1') ?>>Sí</option>
                        <option value="0"<?= orden_tri_selected((string) ($f['pagaiva'] ?? ''), '0') ?>>No</option>
                    </select>
                </label>
                <label>
                    Nº autorización
                    <select name="numeautorizacion">
                        <option value=""<?= orden_tri_selected((string) ($f['numeautorizacion'] ?? ''), '') ?>>Todas</option>
                        <option value="con"<?= orden_tri_selected((string) ($f['numeautorizacion'] ?? ''), 'con') ?>>Con número</option>
                        <option value="sin"<?= orden_tri_selected((string) ($f['numeautorizacion'] ?? ''), 'sin') ?>>Sin número</option>
                    </select>
                </label>
                <label>
                    Honorario desde
                    <input type="date" name="honorariofecha_desde" value="<?= h((string) ($f['honorariofecha_desde'] ?? '')) ?>">
                </label>
                <label>
                    Honorario hasta
                    <input type="date" name="honorariofecha_hasta" value="<?= h((string) ($f['honorariofecha_hasta'] ?? '')) ?>">
                </label>
            </div>
        </details>

        <div class="filter-row" style="margin-top:0.85rem;align-items:center;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Buscar órdenes</button>
            <?php if ($ordenesFiltrosActivos): ?>
                <a class="btn btn-ghost" href="/ordenes.php"><i class="bi bi-funnel" aria-hidden="true"></i> Limpiar filtros</a>
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
                        <th>Nro HC</th>
                        <th>Paciente</th>
                        <th>Nº orden</th>
                        <th>Cobertura</th>
                        <th>Práct.</th>
                        <th>Profesional</th>
                        <th>Fecha</th>
                        <th>Costo</th>
                        <th>Pago</th>
                        <th>Costo OS</th>
                        <th>Debe Paci.</th>
                        <th>Hon. extra</th>
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
                    <?php
                    $sumCosto = 0.0;
                    $sumPago = 0.0;
                    $sumCostoOs = 0.0;
                    $sumDebePaci = 0.0;
                    $sumHonorarioExtra = 0.0;
                    $sumSesiones = 0;
                    foreach ($rows as $r):
                        $costoNum = orden_num($r['costo'] ?? null);
                        $pagoNum = orden_num($r['pago'] ?? null);
                        $costoOsNum = orden_num($r['costo_os'] ?? null);
                        $honorNum = orden_num($r['honorarioextra'] ?? null);
                        $sesNum = isset($r['sesiones']) && $r['sesiones'] !== '' && $r['sesiones'] !== null && is_numeric($r['sesiones']) ? (int) $r['sesiones'] : 0;
                        $debePaciNum = $costoNum - $pagoNum;
                        $sumCosto += $costoNum;
                        $sumPago += $pagoNum;
                        $sumCostoOs += $costoOsNum;
                        $sumDebePaci += $debePaciNum;
                        $sumHonorarioExtra += $honorNum;
                        $sumSesiones += $sesNum;
                    ?>
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
                            <td><?= (int) $r['NroPaci'] ?></td>
                            <td><?= h(orden_vista_paciente($r)) ?></td>
                            <td><?= $numOrden !== null && $numOrden !== '' ? h((string) $numOrden) : '—' ?></td>
                            <?php
                            $cobTxt = orden_fmt_ref($r['idobrasocial'] ?? null, $r['cobertura_nombre'] ?? '');
                            ?>
                            <td class="cell-clip" title="<?= h($cobTxt) ?>"><?= h($cobTxt) ?></td>
                            <?php
                            $prTxt = orden_fmt_ref($r['idpractica'] ?? null, $r['practica_nombre'] ?? '');
                            ?>
                            <td class="cell-clip" title="<?= h($prTxt) ?>"><?= h($prTxt) ?></td>
                            <td><?= h((string) ($r['doctor_nombre'] ?? '')) ?></td>
                            <td><?= !empty($r['fecha_orden']) ? h((string) $r['fecha_orden']) : '—' ?></td>
                            <td><?= h(orden_fmt_money($costoNum)) ?></td>
                            <td><?= h(orden_fmt_money($pagoNum)) ?></td>
                            <td><?= h(orden_fmt_money($costoOsNum)) ?></td>
                            <td><?= h(orden_fmt_money($debePaciNum)) ?></td>
                            <td><?= h(orden_fmt_money($honorNum)) ?></td>
                            <td><?= $sesNum > 0 ? h((string) $sesNum) : '—' ?></td>
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
                    <tr class="ordenes-totales-row">
                        <td colspan="7"><strong>Totales (filtro actual)</strong></td>
                        <td><strong><?= h(orden_fmt_money($sumCosto)) ?></strong></td>
                        <td><strong><?= h(orden_fmt_money($sumPago)) ?></strong></td>
                        <td><strong><?= h(orden_fmt_money($sumCostoOs)) ?></strong></td>
                        <td><strong><?= h(orden_fmt_money($sumDebePaci)) ?></strong></td>
                        <td><strong><?= h(orden_fmt_money($sumHonorarioExtra)) ?></strong></td>
                        <td><strong><?= h((string) $sumSesiones) ?></strong></td>
                        <td colspan="7"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
