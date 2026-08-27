<?php

declare(strict_types=1);

/** @var array{id_clinica:int,nombre:string,direccion:string,encabezado:string,logo_path:string,logo_url:?string} $branding */
/** @var string $error */
/** @var string $clinicaNombreTabla */
/** @var bool $isSuperadmin */
/** @var list<array{id:int,nombre:string}> $clinicasOpts */
/** @var int $editClinicaId */
?>
<div class="container" style="max-width:720px;">
    <div class="page-head">
        <h1>Identidad de la clínica</h1>
        <p class="muted">Logo y datos que aparecen en el menú lateral y en las impresiones (recibos, caja, odontograma).</p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($isSuperadmin && count($clinicasOpts) > 1): ?>
        <form method="get" action="/sistema.php" class="toolbar no-print" style="margin-bottom:1rem;">
            <input type="hidden" name="a" value="clinica_branding">
            <label>
                Clínica
                <select name="clinica" onchange="this.form.submit()">
                    <?php foreach ($clinicasOpts as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === $editClinicaId ? ' selected' : '' ?>>
                            <?= h((string) $c['nombre']) ?> (#<?= (int) $c['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </form>
    <?php else: ?>
        <p class="muted">Clínica: <strong><?= h($clinicaNombreTabla !== '' ? $clinicaNombreTabla : ('#' . $editClinicaId)) ?></strong></p>
    <?php endif; ?>

    <form method="post" action="/sistema.php?a=clinica_branding<?= $isSuperadmin ? '&amp;clinica=' . $editClinicaId : '' ?>" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <?php if ($isSuperadmin): ?>
            <input type="hidden" name="clinica" value="<?= (int) $editClinicaId ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Logo (PNG o JPG, máx. 2 MB)</label>
            <?php if (!empty($branding['logo_url'])): ?>
                <div class="clinica-branding-preview">
                    <img src="<?= h((string) $branding['logo_url']) ?>" alt="Logo actual" class="clinica-branding-logo-preview">
                </div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/png,image/jpeg">
            <p class="muted form-hint">Se redimensiona automáticamente (máx. 420×120 px). Fondo transparente o blanco recomendado.</p>
        </div>

        <div class="form-group">
            <label for="nombre_clinica">Nombre visible</label>
            <input type="text" id="nombre_clinica" name="nombre_clinica" maxlength="200"
                   value="<?= h((string) $branding['nombre']) ?>">
            <p class="muted form-hint">Menú lateral, recordatorios WhatsApp e impresiones.</p>
        </div>

        <div class="form-group">
            <label for="direccion_clinica">Dirección</label>
            <input type="text" id="direccion_clinica" name="direccion_clinica" maxlength="300"
                   value="<?= h((string) $branding['direccion']) ?>">
        </div>

        <div class="form-group">
            <label for="encabezado_impresion">Encabezado adicional (impresiones)</label>
            <textarea id="encabezado_impresion" name="encabezado_impresion" rows="4" maxlength="2000"><?= h((string) $branding['encabezado']) ?></textarea>
            <p class="muted form-hint">Teléfono, especialidad u otro texto bajo el nombre en recibos y reportes.</p>
        </div>

        <div class="page-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <?php if (!empty($branding['logo_path'])): ?>
                <button type="submit" name="quitar_logo" value="1" class="btn btn-ghost"
                        onclick="return confirm('¿Quitar el logo actual?');">Quitar logo</button>
            <?php endif; ?>
            <a class="btn btn-ghost" href="/sistema.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver</a>
        </div>
    </form>
</div>
