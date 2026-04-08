<?php

declare(strict_types=1);

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
?>
<div class="container">
    <div class="page-head">
        <h1>Pacientes</h1>
        <p class="muted">Alta, edición y baja — alineado a Control Salud (exe + listas en MySQL).</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="/paciente_form.php"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo paciente</a>
    </div>
    <?php if ($rows === []): ?>
        <p class="empty-state">No hay pacientes cargados. Usá «Nuevo paciente» para comenzar.</p>
    <?php else: ?>
        <div class="table-wrap table-wrap-datatable">
            <table id="tbl-pacientes" class="table">
                <thead>
                    <tr>
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
                                <a class="btn btn-sm btn-ghost btn-icon" title="Historia clínica" href="/historia_clinica.php?id=<?= (int) $r['id'] ?>"><i class="bi bi-journal-medical" aria-hidden="true"></i><span class="btn-label"> HC</span></a>
                                <a class="btn btn-sm btn-ghost btn-icon" title="Editar" href="/paciente_form.php?id=<?= (int) $r['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
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

