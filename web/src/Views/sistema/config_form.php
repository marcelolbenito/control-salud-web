<?php
declare(strict_types=1);
$isEdit = (int) ($row['id'] ?? 0) > 0;
?>
<div class="container container-narrow">
    <p><a href="/sistema.php">← Volver</a></p>
    <h1><?= $isEdit ? 'Editar parámetro' : 'Nuevo parámetro' ?></h1>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <label>
            Clave
            <input type="text" name="clave" required maxlength="100" value="<?= h((string) ($row['clave'] ?? '')) ?>" placeholder="ej. app.nombre_sucursal" autocomplete="off">
        </label>
        <label>
            Valor (texto libre)
            <textarea name="valor" rows="6" placeholder="Texto o JSON según convenga"><?= h((string) ($row['valor'] ?? '')) ?></textarea>
        </label>
        <p class="muted small">Usá nombres estables (<code>modulo.campo</code>) para no chocar con futuras versiones.</p>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
