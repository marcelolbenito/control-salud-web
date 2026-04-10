<?php

declare(strict_types=1);
?>
<div class="container">
    <div class="page-head">
        <h1>Doctores / Profesionales</h1>
        <p class="muted">Alta, edición y baja de profesionales que atienden en el sistema.</p>
        <?php if (!$extDoc): ?>
            <p class="muted" style="font-size:0.9rem;">Especialidad y matrícula se listan tras <code>sql/migration_003_doctores_agenda_exe.sql</code>.</p>
        <?php endif; ?>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="/doctor_form.php"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo profesional</a>
    </div>
    <?php if ($rows === []): ?>
        <p class="empty-state">No hay profesionales cargados. Usá «Nuevo profesional» para comenzar.</p>
    <?php else: ?>
        <div class="table-wrap table-wrap-datatable">
            <table id="tbl-doctores" class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <?php if ($extDoc): ?>
                            <th>Especialidad</th>
                            <th>Matrícula</th>
                            <th>Teléfono</th>
                        <?php endif; ?>
                        <th>Médico convenio</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int) $r['id'] ?></td>
                            <td><?= h($r['nombre']) ?></td>
                            <?php if ($extDoc): ?>
                                <td><?= h((string) ($r['especialidad'] ?? '')) ?: '—' ?></td>
                                <td><?= h((string) ($r['matricula'] ?? '')) ?: '—' ?></td>
                                <td><?= h((string) ($r['telefono'] ?? '')) ?: '—' ?></td>
                            <?php endif; ?>
                            <td><?= (int) $r['medicoconvenio'] ? 'Sí' : 'No' ?></td>
                            <td><?= (int) $r['activo'] ? 'Sí' : 'No' ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" title="Editar" href="/doctor_form.php?id=<?= (int) $r['id'] ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i><span class="btn-label"> Editar</span></a>
                                <form action="/doctor_eliminar.php" method="post" class="table-action-form" onsubmit="return confirm('¿Eliminar este profesional? Si tiene turnos u órdenes asociadas, conviene revisar antes.');">
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

