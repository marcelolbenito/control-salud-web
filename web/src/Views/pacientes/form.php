<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="/pacientes.php">← Volver al listado</a></p>
    </div>
    <?php if (!$ext): ?>
        <p class="alert alert-error" style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
            La base no tiene los campos ampliados del exe. Ejecutá en MySQL <code>sql/migration_002_pacientes_campos_exe.sql</code> y cargá catálogos con <code>sql/migracion/schema_listas_minimo.sql</code> + <code>migration_data_listas.sql</code>.
        </p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">

        <section class="form-section">
            <h2 class="form-section-title">Identificación (como en Control Salud)</h2>
            <div class="form-grid-ext">
                <label>Nro HC *
                    <input type="number" name="NroHC" required min="1" value="<?= (int) ($row['NroHC'] ?: $defaultNro) ?>">
                </label>
                <?php if (!$row['id']): ?>
                    <p class="hint span-2">Sugerido: <?= (int) $sugeridoNro ?></p>
                <?php endif; ?>
                <?php if ($ext): ?>
                    <label>N° historia (texto)
                        <input type="text" name="numehistoria" value="<?= h((string) ($row['numehistoria'] ?? '')) ?>" maxlength="30">
                    </label>
                <?php endif; ?>
                <label>Apellido
                    <input type="text" name="apellido" value="<?= h((string) ($row['apellido'] ?? '')) ?>" <?= $ext ? '' : ' disabled' ?>>
                </label>
                <label>Segundo apellido
                    <input type="text" name="apellido2" value="<?= h((string) ($row['apellido2'] ?? '')) ?>" <?= $ext ? '' : ' disabled' ?>>
                </label>
                <label>Nombres
                    <input type="text" name="Nombres" value="<?= h((string) $row['Nombres']) ?>">
                </label>
                <label>DNI
                    <input type="text" name="DNI" value="<?= h((string) $row['DNI']) ?>">
                </label>
                <?php if ($ext): ?>
                    <label>Tipo de documento
                        <select name="id_tipo_doc"><?php catalogo_select_options($listas['tdoc'], $row['id_tipo_doc'] ?? null); ?></select>
                    </label>
                    <label>Sexo (código numérico)
                        <input type="number" name="sexo" step="1" value="<?= $row['sexo'] !== null && $row['sexo'] !== '' ? (int) $row['sexo'] : '' ?>" placeholder="exe usa SMALLINT">
                    </label>
                <?php endif; ?>
                <label>Fecha de nacimiento
                    <input type="date" name="fecha_nacimiento" value="<?= h((string) $row['fecha_nacimiento']) ?>">
                </label>
            </div>
        </section>

        <?php if ($ext): ?>
        <section class="form-section">
            <h2 class="form-section-title">Cobertura / obra social</h2>
            <div class="form-grid-ext">
                <label>Cobertura principal
                    <select name="id_cobertura"><?php catalogo_select_options($listas['cob'], $row['id_cobertura'] ?? null); ?></select>
                </label>
                <label>Plan
                    <select name="id_plan"><?php catalogo_select_options($listas['planes'], $row['id_plan'] ?? null); ?></select>
                </label>
                <label>Nº afiliado / OS
                    <input type="text" name="nro_os" value="<?= h((string) ($row['nro_os'] ?? '')) ?>">
                </label>
                <label>Segunda cobertura
                    <select name="id_cobertura2"><?php catalogo_select_options($listas['cob'], $row['id_cobertura2'] ?? null); ?></select>
                </label>
                <label>Nº afiliado (2)
                    <input type="text" name="nu_afiliado2" value="<?= h((string) ($row['nu_afiliado2'] ?? '')) ?>">
                </label>
                <label class="form-check span-2">
                    <input type="checkbox" name="convenio" value="1" <?= (int) $row['convenio'] ? ' checked' : '' ?>>
                    Convenio
                </label>
            </div>
        </section>
        <?php else: ?>
            <section class="form-section">
                <div class="form-grid-ext">
                    <label class="form-check">
                        <input type="checkbox" name="convenio" value="1" <?= (int) $row['convenio'] ? ' checked' : '' ?>>
                        Convenio
                    </label>
                </div>
            </section>
        <?php endif; ?>

        <section class="form-section">
            <h2 class="form-section-title">Contacto</h2>
            <div class="form-grid-ext">
                <label>Teléfono
                    <input type="text" name="telefono" value="<?= h((string) $row['telefono']) ?>">
                </label>
                <?php if ($ext): ?>
                    <label>Celular
                        <input type="text" name="tel_celular" value="<?= h((string) ($row['tel_celular'] ?? '')) ?>">
                    </label>
                    <label>Tel. laboral
                        <input type="text" name="tel_laboral" value="<?= h((string) ($row['tel_laboral'] ?? '')) ?>">
                    </label>
                <?php endif; ?>
                <label>Email
                    <input type="email" name="email" value="<?= h((string) $row['email']) ?>">
                </label>
            </div>
        </section>

        <?php if ($ext): ?>
        <section class="form-section">
            <h2 class="form-section-title">Ubicación</h2>
            <div class="form-grid-ext">
                <label>País
                    <select name="id_pais"><?php catalogo_select_options($listas['pais'], $row['id_pais'] ?? null); ?></select>
                </label>
                <label>Provincia
                    <select name="id_provincia"><?php catalogo_select_options($listas['prov'], $row['id_provincia'] ?? null); ?></select>
                </label>
                <label>Ciudad
                    <select name="id_ciudad"><?php catalogo_select_options($listas['ciu'], $row['id_ciudad'] ?? null); ?></select>
                </label>
                <label>CP
                    <input type="text" name="cp" value="<?= h((string) ($row['cp'] ?? '')) ?>" maxlength="10">
                </label>
                <label class="span-2">Domicilio
                    <textarea name="direccion" rows="2"><?= h((string) $row['direccion']) ?></textarea>
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Otros datos</h2>
            <div class="form-grid-ext">
                <label>Ocupación
                    <select name="id_ocupacion"><?php catalogo_select_options($listas['ocup'], $row['id_ocupacion'] ?? null); ?></select>
                </label>
                <label>Detalle ocupación
                    <input type="text" name="detalle_ocupacion" value="<?= h((string) ($row['detalle_ocupacion'] ?? '')) ?>">
                </label>
                <label>Estado civil
                    <select name="id_estado_civil"><?php catalogo_select_options($listas['eciv'], $row['id_estado_civil'] ?? null); ?></select>
                </label>
                <label>Etnia
                    <select name="id_etnia"><?php catalogo_select_options($listas['etn'], $row['id_etnia'] ?? null); ?></select>
                </label>
                <label class="span-2">Alergias
                    <textarea name="alergias" rows="2"><?= h((string) ($row['alergias'] ?? '')) ?></textarea>
                </label>
            </div>
        </section>
        <?php else: ?>
            <section class="form-section">
                <h2 class="form-section-title">Domicilio</h2>
                <div class="form-grid-ext">
                    <label class="span-2">Dirección
                        <textarea name="direccion"><?= h((string) $row['direccion']) ?></textarea>
                    </label>
                </div>
            </section>
        <?php endif; ?>

        <section class="form-section">
            <h2 class="form-section-title">Estado y notas</h2>
            <div class="form-grid-ext">
                <label class="form-check">
                    <input type="checkbox" name="activo" value="1" <?= (int) $row['activo'] ? ' checked' : '' ?>>
                    Activo
                </label>
                <label class="span-2">Notas
                    <textarea name="notas"><?= h((string) $row['notas']) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="/pacientes.php">Cancelar</a>
        </div>
    </form>
</div>
