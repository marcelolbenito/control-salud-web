<?php

declare(strict_types=1);

/** @var array<string, string|int> $f */
/** @var string $pacientesQueryString */
/** @var bool $pacientesFiltrosActivos */

function paciente_nombre_completo(array $r): string
{
    $a = trim((string) ($r['apellido'] ?? ''));
    $n = trim((string) ($r['Nombres'] ?? ''));
    if ($a !== '' && $n !== '') {
        return $a . ', ' . $n;
    }
    if ($a !== '') {
        return $a;
    }

    return $n;
}

$qsSuffix = $pacientesQueryString !== '' ? '&' . $pacientesQueryString : '';
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Pacientes</h1>
        <p class="muted">Búsqueda y listado — Nro. HC y datos alineados al exe. El buscador busca en nombre, apellidos, DNI y contacto (si la BD tiene campos extendidos).</p>
    </div>

    <form method="get" class="agenda-filters form-card" action="/pacientes.php">
        <div class="filter-row">
            <label class="filter-grow">
                Texto (apellido, nombre, DNI, teléfono, email…)
                <input type="search" name="q" value="<?= h((string) ($f['q'] ?? '')) ?>" placeholder="Buscar…" autocomplete="off">
            </label>
            <label>
                Nro. HC
                <input type="number" name="nrohc" min="1" placeholder="Todos" value="<?= ($f['nrohc'] ?? 0) > 0 ? (int) $f['nrohc'] : '' ?>">
            </label>
            <label>
                Nº ID (sistema)
                <input type="number" name="id" min="1" placeholder="Todos" value="<?= ($f['id'] ?? 0) > 0 ? (int) $f['id'] : '' ?>">
            </label>
            <label>
                Activo
                <select name="activo">
                    <option value="1"<?= (string) ($f['activo'] ?? '1') === '1' ? ' selected' : '' ?>>Solo activos</option>
                    <option value=""<?= (string) ($f['activo'] ?? '') === '' ? ' selected' : '' ?>>Todos</option>
                    <option value="0"<?= (string) ($f['activo'] ?? '') === '0' ? ' selected' : '' ?>>Solo inactivos</option>
                </select>
            </label>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
            <?php if ($pacientesFiltrosActivos): ?>
                <a class="btn btn-ghost" href="/pacientes.php">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="page-actions">
        <a class="btn btn-primary" href="/paciente_form.php<?= $pacientesQueryString !== '' ? '?' . h($pacientesQueryString) : '' ?>"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo paciente</a>
    </div>
    <?php if ($rows === []): ?>
        <p class="empty-state"><?= $pacientesFiltrosActivos ? 'No hay pacientes con estos criterios. Probá otro texto o pulsá «Limpiar».' : 'No hay pacientes cargados. Usá «Nuevo paciente» para comenzar.' ?></p>
    <?php else: ?>
        <div class="table-wrap table-wrap-datatable">
            <table id="tbl-pacientes" class="table">
                <thead>
                    <tr>
                        <th>Nº ID</th>
                        <th>Nro HC</th>
                        <th>Apellido y nombre</th>
                        <th>DNI</th>
                        <?php if ($hasExt): ?>
                            <th>Cobertura</th>
                        <?php endif; ?>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int) $r['id'] ?></td>
                            <td><?= (int) $r['NroHC'] ?></td>
                            <td><?= h(paciente_nombre_completo($r)) ?></td>
                            <td><?= h($r['DNI']) ?></td>
                            <?php if ($hasExt): ?>
                                <td><?= h((string) ($r['cobertura_nombre'] ?? '')) ?></td>
                            <?php endif; ?>
                            <td><?= h($r['telefono']) ?></td>
                            <td><?= h($r['email']) ?></td>
                            <td><?= (int) $r['activo'] ? 'Sí' : 'No' ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" title="Órdenes (HC)" href="/ordenes.php?nrohc=<?= (int) $r['NroHC'] ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span class="btn-label"> Ord.</span></a>
                                <a class="btn btn-sm btn-ghost btn-icon" title="Historia clínica" href="/historia_clinica.php?id=<?= (int) $r['id'] ?><?= h($qsSuffix) ?>"><i class="bi bi-journal-medical" aria-hidden="true"></i><span class="btn-label"> HC</span></a>
                                <a class="btn btn-sm btn-ghost btn-icon" title="Editar" href="/paciente_form.php?id=<?= (int) $r['id'] ?><?= h($qsSuffix) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                <form action="/paciente_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar este paciente? Esta acción no se puede deshacer.');">
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
