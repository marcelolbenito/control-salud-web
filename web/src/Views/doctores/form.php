<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="/doctores.php">← Volver al listado</a></p>
    </div>
    <?php if (!$ext): ?>
        <p class="alert alert-error" style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
            Para datos como en el exe (especialidad, matrícula, etc.), ejecutá en MySQL <code>sql/migration_003_doctores_agenda_exe.sql</code>.
        </p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">

        <section class="form-section">
            <h2 class="form-section-title">Identificación (Lista Doctores / exe)</h2>
            <div class="form-grid-ext">
                <label class="span-2">Nombre completo *
                    <input type="text" name="nombre" required value="<?= h((string) $row['nombre']) ?>">
                </label>
                <?php if ($ext): ?>
                    <label>Especialidad
                        <input type="text" name="especialidad" value="<?= h((string) ($row['especialidad'] ?? '')) ?>" maxlength="100">
                    </label>
                    <label>Matrícula
                        <input type="text" name="matricula" value="<?= h((string) ($row['matricula'] ?? '')) ?>" maxlength="20">
                    </label>
                    <label>Teléfono
                        <input type="text" name="telefono" value="<?= h((string) ($row['telefono'] ?? '')) ?>">
                    </label>
                    <label>Consultorio
                        <input type="text" name="consultorio" value="<?= h((string) ($row['consultorio'] ?? '')) ?>" maxlength="30">
                    </label>
                    <label>Domicilio
                        <input type="text" name="domicilio" value="<?= h((string) ($row['domicilio'] ?? '')) ?>">
                    </label>
                    <label>Localidad
                        <input type="text" name="localidad" value="<?= h((string) ($row['localidad'] ?? '')) ?>">
                    </label>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Opciones</h2>
            <div class="form-grid-ext">
                <label class="form-check">
                    <input type="checkbox" name="medicoconvenio" value="1" <?= (int) $row['medicoconvenio'] ? ' checked' : '' ?>>
                    Médico convenio
                </label>
                <label class="form-check">
                    <input type="checkbox" name="bloquearmisconsultas" value="1" <?= (int) $row['bloquearmisconsultas'] ? ' checked' : '' ?>>
                    Bloquear mis consultas
                </label>
                <label class="form-check">
                    <input type="checkbox" name="activo" value="1" <?= (int) $row['activo'] ? ' checked' : '' ?>>
                    Activo
                </label>
                <label class="span-2">Notas internas
                    <textarea name="notas"><?= h((string) $row['notas']) ?></textarea>
                </label>
                <?php if (!$ext): ?>
                    <p class="hint span-2">En Access hay muchos permisos por usuario (accesoagenda, etc.); la web por ahora no los replica.</p>
                <?php endif; ?>
            </div>
        </section>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="/doctores.php">Cancelar</a>
        </div>
    </form>
</div>
