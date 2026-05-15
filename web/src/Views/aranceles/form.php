<?php

declare(strict_types=1);
$isEdit = (int) ($row['id'] ?? 0) > 0;
$volver = '/aranceles.php';
if (!empty($row['idobrasocial'])) {
    $volver .= '?cobertura=' . (int) $row['idobrasocial'];
}
?>
<div class="container container-narrow">
    <p><a href="<?= h($volver) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a aranceles</a></p>
    <h1><?= $isEdit ? 'Editar arancel' : 'Nuevo arancel' ?></h1>
    <p class="muted small">Los importes se usan al cargar órdenes (costo paciente y monto obra social).</p>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-card" id="aranceles_form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <?php if ($cobOpts !== []): ?>
            <?php
            $cbLabel = 'Obra social / cobertura';
            $cbPlaceholder = 'Buscar por código o nombre';
            $cbName = 'idobrasocial';
            $cbHiddenId = 'aranceles_form_cobertura';
            $cbInputId = 'aranceles_form_cobertura_buscar';
            $cbListId = 'aranceles_form_coberturas_lista';
            $cbOpts = $cobOpts;
            $cbSelected = $row['idobrasocial'] ?? 0;
            $cbRequired = true;
            $cbAutoSubmitFormId = null;
            $cbGrowClass = '';
            $cbSubmitTextName = 'idobrasocial_txt';
            require dirname(__DIR__) . '/_partials/catalogo_buscar.php';
            ?>
        <?php else: ?>
            <label>Id obra social
                <input type="number" name="idobrasocial" min="1" required value="<?= h((string) ($row['idobrasocial'] ?? '')) ?>">
            </label>
        <?php endif; ?>
        <?php if ($practicaOpts !== []): ?>
            <?php
            $cbLabel = 'Práctica / estudio';
            $cbPlaceholder = 'Buscar por código o nombre';
            $cbName = 'idpractica';
            $cbHiddenId = 'aranceles_form_practica';
            $cbInputId = 'aranceles_form_practica_buscar';
            $cbListId = 'aranceles_form_practicas_lista';
            $cbOpts = $practicaOpts;
            $cbSelected = $row['idpractica'] ?? 0;
            $cbRequired = true;
            $cbAutoSubmitFormId = null;
            $cbGrowClass = '';
            $cbSubmitTextName = 'idpractica_txt';
            require dirname(__DIR__) . '/_partials/catalogo_buscar.php';
            ?>
        <?php else: ?>
            <label>Id práctica
                <input type="number" name="idpractica" min="1" required value="<?= h((string) ($row['idpractica'] ?? '')) ?>">
            </label>
        <?php endif; ?>
        <label>Plan (opcional)
            <select name="idplan">
                <option value="0">General (sin plan)</option>
                <?php foreach ($planes as $pl): ?>
                    <?php $plid = (int) $pl['id']; ?>
                    <option value="<?= $plid ?>"<?= (int) ($row['idplan'] ?? 0) === $plid ? ' selected' : '' ?>><?= h((string) ($pl['nombre'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Costo paciente ($)
            <input type="text" inputmode="decimal" name="costopaciente" value="<?= isset($row['costopaciente']) && $row['costopaciente'] !== null && $row['costopaciente'] !== '' ? h((string) $row['costopaciente']) : '' ?>" placeholder="0">
        </label>
        <label>Costo obra social ($)
            <input type="text" inputmode="decimal" name="costocobertura" value="<?= isset($row['costocobertura']) && $row['costocobertura'] !== null && $row['costocobertura'] !== '' ? h((string) $row['costocobertura']) : '' ?>" placeholder="0">
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
        </div>
    </form>
</div>
<?php if (($cobOpts ?? []) !== [] || ($practicaOpts ?? []) !== []): ?>
    <?php catalogo_buscar_script_tag(); ?>
<?php endif; ?>
