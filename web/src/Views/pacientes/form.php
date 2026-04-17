<?php

declare(strict_types=1);

/** @var bool $ext */
/** @var bool $fotoDisponible */
/** @var array<string, mixed> $row */
/** @var array<string, mixed> $listas */
/** int $defaultNro, $sugeridoNro */

function paciente_dt_local($v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    $s = str_replace(' ', 'T', trim((string) $v));

    return strlen($s) >= 16 ? substr($s, 0, 16) : $s;
}

function paciente_date_only($v): string
{
    if ($v === null || $v === '') {
        return '';
    }

    return substr((string) $v, 0, 10);
}
?>
<div class="container container-wide patient-form-page">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="/pacientes.php">← Volver al listado</a></p>
    </div>
    <?php if (!$ext): ?>
        <p class="alert alert-error" style="background:#fffbeb;border-color:#fcd34d;color:#92400e;">
            La base no tiene los campos ampliados del backup. Ejecutá <code>sql/migration_002_pacientes_campos_exe.sql</code>, catálogos (<code>sql/migracion/schema_listas_minimo.sql</code> + datos), <code>sql/migration_007_lista_sexo_grupo_factor_ruta_foto.sql</code> (sexo, grupo/factor y foto) y <code>sql/migration_008_lista_identidad_orientacion.sql</code> (identidad / orientación). Tras importar pacientes, <code>sql/utilidad_sembrar_codigos_desde_pacientes.sql</code> completa códigos faltantes en esas listas.
        </p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($ext && (int) ($row['id'] ?? 0) > 0): ?>
        <nav class="patient-toolbar" aria-label="Acciones del paciente">
            <span class="patient-toolbar-id muted">Nº ID sistema: <strong><?= (int) $row['id'] ?></strong> · Nro HC: <strong><?= (int) ($row['NroHC'] ?? 0) ?></strong></span>
            <a class="btn btn-sm btn-ghost" href="/ordenes.php?nrohc=<?= (int) ($row['NroHC'] ?? 0) ?>"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i> Órdenes</a>
            <a class="btn btn-sm btn-ghost" href="/historia_clinica.php?id=<?= (int) $row['id'] ?>"><i class="bi bi-journal-medical" aria-hidden="true"></i> Historia clínica</a>
        </nav>
    <?php elseif ($ext): ?>
        <p class="muted patient-toolbar-id">Nuevo paciente — al guardar se asignará Nº ID automático.</p>
    <?php endif; ?>

    <?php if ($ext): ?>
    <nav class="form-subnav patient-form-subnav" aria-label="Secciones">
        <a href="#pf-ident">Identificación</a>
        <a href="#pf-foto">Foto</a>
        <a href="#pf-seg">Seguimiento</a>
        <a href="#pf-cob">Cobertura</a>
        <a href="#pf-ubic">Ubicación</a>
        <a href="#pf-contacto">Contacto</a>
        <a href="#pf-familia">Familia</a>
        <a href="#pf-contacto2">Contacto alt.</a>
        <a href="#pf-clin">Clínico / HC</a>
        <a href="#pf-notas">Notas</a>
    </nav>
    <?php endif; ?>

    <form method="post" class="form-paciente" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">

        <section id="pf-ident" class="form-section patient-form-section">
            <h2 class="form-section-title">Identificación</h2>
            <div class="form-grid-ext">
                <label>Nro HC *
                    <input type="number" name="NroHC" required min="1" value="<?= (int) ($row['NroHC'] ?: $defaultNro) ?>">
                </label>
                <?php if (!$row['id']): ?>
                    <p class="hint span-2">Sugerido: <?= (int) $sugeridoNro ?></p>
                <?php endif; ?>
                <?php if ($ext): ?>
                    <label>N° historia (texto, exe)
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
                <label>DNI / Nº doc.
                    <input type="text" name="DNI" value="<?= h((string) $row['DNI']) ?>">
                </label>
                <?php if ($ext): ?>
                    <label>DNI sin uso (legacy)
                        <input type="text" name="dni_sin_uso" value="<?= h((string) ($row['dni_sin_uso'] ?? '')) ?>" maxlength="10">
                    </label>
                    <label>Tipo de documento
                        <select name="id_tipo_doc"><?php catalogo_select_options($listas['tdoc'], $row['id_tipo_doc'] ?? null); ?></select>
                    </label>
                    <label>Sexo
                        <?php if (!empty($listas['sexo'])): ?>
                            <select name="sexo"><?php catalogo_select_options($listas['sexo'], $row['sexo'] ?? null); ?></select>
                        <?php else: ?>
                            <input type="number" name="sexo" step="1" value="<?= $row['sexo'] !== null && $row['sexo'] !== '' ? (int) $row['sexo'] : '' ?>" placeholder="Código (sin lista_sexo)">
                        <?php endif; ?>
                    </label>
                    <label>Identidad de género
                        <?php if (!empty($listas['idgen'])): ?>
                            <select name="identidad_gen"><?php catalogo_select_options($listas['idgen'], $row['identidad_gen'] ?? null); ?></select>
                        <?php else: ?>
                            <input type="number" name="identidad_gen" step="1" value="<?= $row['identidad_gen'] !== null && $row['identidad_gen'] !== '' ? (int) $row['identidad_gen'] : '' ?>" placeholder="Código (sin lista_identidad_genero)">
                        <?php endif; ?>
                    </label>
                    <label>Orientación sexual
                        <?php if (!empty($listas['orient'])): ?>
                            <select name="orientacion_sex"><?php catalogo_select_options($listas['orient'], $row['orientacion_sex'] ?? null); ?></select>
                        <?php else: ?>
                            <input type="number" name="orientacion_sex" step="1" value="<?= $row['orientacion_sex'] !== null && $row['orientacion_sex'] !== '' ? (int) $row['orientacion_sex'] : '' ?>" placeholder="Código (sin lista_orientacion_sex)">
                        <?php endif; ?>
                    </label>
                <?php endif; ?>
                <label>Fecha de nacimiento
                    <input type="date" name="fecha_nacimiento" value="<?= h(paciente_date_only($row['fecha_nacimiento'] ?? '')) ?>">
                </label>
            </div>
        </section>

        <?php if ($ext && !empty($fotoDisponible)): ?>
        <section id="pf-foto" class="form-section patient-form-section">
            <h2 class="form-section-title">Foto del paciente</h2>
            <div class="patient-foto-row">
                <div class="patient-foto-preview">
                    <?php $rf = trim((string) ($row['ruta_foto'] ?? '')); ?>
                    <?php if ($rf !== ''): ?>
                        <img src="<?= h($rf) ?>" alt="Foto actual" class="patient-foto-img">
                    <?php else: ?>
                        <p class="muted">Sin foto cargada.</p>
                    <?php endif; ?>
                </div>
                <div class="patient-foto-actions form-grid-ext">
                    <label class="span-2">Subir imagen (JPG, PNG, WebP, máx. ~3,5 MB)
                        <input type="file" name="foto_paciente" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <?php if ($rf !== ''): ?>
                        <label class="form-check span-2"><input type="checkbox" name="borrar_foto" value="1"> Quitar foto actual</label>
                    <?php endif; ?>
                </div>
            </div>
            <p class="muted small">Se guarda en <code>/uploads/pacientes/</code> (ejecutá <code>sql/migration_007_lista_sexo_grupo_factor_ruta_foto.sql</code> para columna <code>ruta_foto</code>).</p>
        </section>
        <?php endif; ?>

        <?php if ($ext): ?>
        <section id="pf-seg" class="form-section patient-form-section">
            <h2 class="form-section-title">Seguimiento y referencia (exe)</h2>
            <div class="form-grid-ext">
                <label>Última consulta
                    <input type="datetime-local" name="ultima_cons" value="<?= h(paciente_dt_local($row['ultima_cons'] ?? null)) ?>">
                </label>
                <label>Referido por / referente
                    <input type="text" name="referente" value="<?= h((string) ($row['referente'] ?? '')) ?>" maxlength="100">
                </label>
                <label class="form-check span-2"><input type="checkbox" name="embarazo" value="1" <?= !empty($row['embarazo']) ? ' checked' : '' ?>> Embarazo</label>
                <label>Ulti. embarazo (código)
                    <input type="number" name="ulti_emba" step="1" value="<?= $row['ulti_emba'] !== null && $row['ulti_emba'] !== '' ? (int) $row['ulti_emba'] : '' ?>">
                </label>
                <label class="span-2">Motivo inactividad
                    <input type="text" name="motivo_inactividad" value="<?= h((string) ($row['motivo_inactividad'] ?? '')) ?>" maxlength="250">
                </label>
                <label>Cobertura (código legacy Access)
                    <input type="number" name="cobertura" step="1" value="<?= $row['cobertura'] !== null && $row['cobertura'] !== '' ? (int) $row['cobertura'] : '' ?>">
                </label>
                <label>Estatus / país (lista estatus)
                    <select name="id_estatus"><?php catalogo_select_options($listas['estatus'] ?? [], $row['id_estatus'] ?? null); ?></select>
                </label>
                <label>Alta paciente web (código)
                    <input type="number" name="alta_paci_web" step="1" value="<?= $row['alta_paci_web'] !== null && $row['alta_paci_web'] !== '' ? (int) $row['alta_paci_web'] : '' ?>">
                </label>
            </div>
        </section>

        <section id="pf-cob" class="form-section patient-form-section">
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
                <label class="form-check"><input type="checkbox" name="paga_iva" value="1" <?= !empty($row['paga_iva']) ? ' checked' : '' ?>> Paga IVA</label>
                <label class="form-check"><input type="checkbox" name="convenio" value="1" <?= (int) $row['convenio'] ? ' checked' : '' ?>> Tiene convenio</label>
            </div>
        </section>

        <section id="pf-ubic" class="form-section patient-form-section">
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

        <section id="pf-contacto" class="form-section patient-form-section">
            <h2 class="form-section-title">Contacto y datos personales</h2>
            <div class="form-grid-ext">
                <label>Teléfono (particular)
                    <input type="text" name="telefono" value="<?= h((string) $row['telefono']) ?>">
                </label>
                <label>Celular
                    <input type="text" name="tel_celular" value="<?= h((string) ($row['tel_celular'] ?? '')) ?>">
                </label>
                <label>Tel. laboral
                    <input type="text" name="tel_laboral" value="<?= h((string) ($row['tel_laboral'] ?? '')) ?>">
                </label>
                <label>Email
                    <input type="email" name="email" value="<?= h((string) $row['email']) ?>">
                </label>
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
            </div>
        </section>

        <section id="pf-familia" class="form-section patient-form-section">
            <h2 class="form-section-title">Familia (exe)</h2>
            <div class="form-grid-ext">
                <label>Padre — nombre
                    <input type="text" name="nombre_padre" value="<?= h((string) ($row['nombre_padre'] ?? '')) ?>">
                </label>
                <label>Padre — nacimiento
                    <input type="date" name="naci_padre" value="<?= h(paciente_date_only($row['naci_padre'] ?? '')) ?>">
                </label>
                <label>Padre — ocupación
                    <select name="id_ocupacion_padre"><?php catalogo_select_options($listas['ocup'], $row['id_ocupacion_padre'] ?? null); ?></select>
                </label>
                <label>Padre — horas hogar
                    <input type="text" name="horas_hogar_padre" value="<?= h((string) ($row['horas_hogar_padre'] ?? '')) ?>" maxlength="10">
                </label>
                <label>Madre — nombre
                    <input type="text" name="nombre_madre" value="<?= h((string) ($row['nombre_madre'] ?? '')) ?>">
                </label>
                <label>Madre — nacimiento
                    <input type="date" name="naci_madre" value="<?= h(paciente_date_only($row['naci_madre'] ?? '')) ?>">
                </label>
                <label>Madre — ocupación
                    <select name="id_ocupacion_madre"><?php catalogo_select_options($listas['ocup'], $row['id_ocupacion_madre'] ?? null); ?></select>
                </label>
                <label>Madre — horas hogar
                    <input type="text" name="horas_hogar_madre" value="<?= h((string) ($row['horas_hogar_madre'] ?? '')) ?>" maxlength="10">
                </label>
                <label>Nº hermanos
                    <input type="text" name="nro_hermanos" value="<?= h((string) ($row['nro_hermanos'] ?? '')) ?>" maxlength="2">
                </label>
                <label>Edad hermanos
                    <input type="text" name="edad_hermanos" value="<?= h((string) ($row['edad_hermanos'] ?? '')) ?>">
                </label>
                <label>Nº hermanas
                    <input type="text" name="nro_hermanas" value="<?= h((string) ($row['nro_hermanas'] ?? '')) ?>" maxlength="2">
                </label>
                <label>Edad hermanas
                    <input type="text" name="edad_hermanas" value="<?= h((string) ($row['edad_hermanas'] ?? '')) ?>">
                </label>
                <label class="span-2">Detalles familia
                    <textarea name="detalles_familia" rows="2" maxlength="250"><?= h((string) ($row['detalles_familia'] ?? '')) ?></textarea>
                </label>
            </div>
        </section>

        <section id="pf-contacto2" class="form-section patient-form-section">
            <h2 class="form-section-title">Persona de contacto alternativo</h2>
            <div class="form-grid-ext">
                <label>Apellido 1
                    <input type="text" name="ape1_contacto" value="<?= h((string) ($row['ape1_contacto'] ?? '')) ?>">
                </label>
                <label>Apellido 2
                    <input type="text" name="ape2_contacto" value="<?= h((string) ($row['ape2_contacto'] ?? '')) ?>">
                </label>
                <label>Nombre
                    <input type="text" name="nombre_contacto" value="<?= h((string) ($row['nombre_contacto'] ?? '')) ?>">
                </label>
                <label>Relación
                    <select name="id_relacion"><?php catalogo_select_options($listas['rel'] ?? [], $row['id_relacion'] ?? null); ?></select>
                </label>
                <label>Tel. particular
                    <input type="text" name="tel_par_contacto" value="<?= h((string) ($row['tel_par_contacto'] ?? '')) ?>">
                </label>
                <label>Tel. celular
                    <input type="text" name="tel_cel_contacto" value="<?= h((string) ($row['tel_cel_contacto'] ?? '')) ?>">
                </label>
                <label>Tel. laboral
                    <input type="text" name="tel_lab_contacto" value="<?= h((string) ($row['tel_lab_contacto'] ?? '')) ?>">
                </label>
            </div>
        </section>

        <section id="pf-clin" class="form-section patient-form-section">
            <h2 class="form-section-title">Clínico / historia (texto en BD)</h2>
            <p class="muted small">Los mismos campos que ves en «Historia clínica»; aquí podés editarlos junto al resto del paciente.</p>
            <div class="form-grid-ext">
                <label>Grupo sanguíneo
                    <?php if (!empty($listas['gsang'])): ?>
                        <select name="grupo_sanguineo"><?php catalogo_select_options($listas['gsang'], $row['grupo_sanguineo'] ?? null); ?></select>
                    <?php else: ?>
                        <input type="number" name="grupo_sanguineo" step="1" value="<?= $row['grupo_sanguineo'] !== null && $row['grupo_sanguineo'] !== '' ? (int) $row['grupo_sanguineo'] : '' ?>" placeholder="Código">
                    <?php endif; ?>
                </label>
                <label>Factor Rh
                    <?php if (!empty($listas['fsang'])): ?>
                        <select name="factor_sanguineo"><?php catalogo_select_options($listas['fsang'], $row['factor_sanguineo'] ?? null); ?></select>
                    <?php else: ?>
                        <input type="number" name="factor_sanguineo" step="1" value="<?= $row['factor_sanguineo'] !== null && $row['factor_sanguineo'] !== '' ? (int) $row['factor_sanguineo'] : '' ?>" placeholder="Código">
                    <?php endif; ?>
                </label>
                <label class="span-2">Alergias
                    <textarea name="alergias" rows="3"><?= h((string) ($row['alergias'] ?? '')) ?></textarea>
                </label>
                <label class="span-2">Historia clínica (hc_texto / Access HC)
                    <textarea name="hc_texto" rows="6"><?= h((string) ($row['hc_texto'] ?? '')) ?></textarea>
                </label>
                <label class="span-2">Antecedentes HC
                    <textarea name="antecedentes_hc" rows="4"><?= h((string) ($row['antecedentes_hc'] ?? '')) ?></textarea>
                </label>
            </div>
        </section>

        <section id="pf-notas" class="form-section patient-form-section">
            <h2 class="form-section-title">Estado y notas</h2>
            <div class="form-grid-ext">
                <label class="form-check">
                    <input type="checkbox" name="activo" value="1" <?= (int) $row['activo'] ? ' checked' : '' ?>>
                    Activo (inverso de paciente inactivo en Access)
                </label>
                <label class="span-2">Notas
                    <textarea name="notas"><?= h((string) $row['notas']) ?></textarea>
                </label>
            </div>
        </section>
        <?php else: ?>
        <section class="form-section">
            <h2 class="form-section-title">Cobertura</h2>
            <div class="form-grid-ext">
                <label class="form-check">
                    <input type="checkbox" name="convenio" value="1" <?= (int) $row['convenio'] ? ' checked' : '' ?>>
                    Convenio
                </label>
            </div>
        </section>
        <section class="form-section">
            <h2 class="form-section-title">Contacto</h2>
            <div class="form-grid-ext">
                <label>Teléfono
                    <input type="text" name="telefono" value="<?= h((string) $row['telefono']) ?>">
                </label>
                <label>Email
                    <input type="email" name="email" value="<?= h((string) $row['email']) ?>">
                </label>
            </div>
        </section>
        <section class="form-section">
            <h2 class="form-section-title">Domicilio</h2>
            <div class="form-grid-ext">
                <label class="span-2">Dirección
                    <textarea name="direccion"><?= h((string) $row['direccion']) ?></textarea>
                </label>
            </div>
        </section>
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
        <?php endif; ?>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="/pacientes.php">Cancelar</a>
        </div>
    </form>
</div>
