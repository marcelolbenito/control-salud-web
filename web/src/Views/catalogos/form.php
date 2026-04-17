<?php
declare(strict_types=1);
$isEdit = (int) ($row['id'] ?? 0) > 0;
?>
<div class="container container-narrow">
    <p><a href="<?= h('/catalogos.php?a=list&tabla=' . rawurlencode($tabla)) ?>">← Volver al listado</a></p>
    <h1><?= $isEdit ? 'Editar' : 'Nuevo' ?> · <?= h($titulo) ?></h1>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <?php foreach ($campos as $col => $meta): ?>
            <label>
                <?= h($meta['label']) ?>
                <?php if (($meta['tipo'] ?? '') === 'fk'):
                    $ref = (string) ($meta['ref'] ?? '');
                    $opts = $fkOptions[$ref] ?? [];
                    $sel = $row[$col] ?? '';
                    ?>
                    <select name="<?= h($col) ?>">
                        <option value="">—</option>
                        <?php foreach ($opts as $o): ?>
                            <?php $oid = (int) $o['id']; ?>
                            <option value="<?= $oid ?>"<?= (string) $sel !== '' && (int) $sel === $oid ? ' selected' : '' ?>><?= h((string) ($o['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif (($meta['tipo'] ?? '') === 'int'): ?>
                    <input type="number" name="<?= h($col) ?>" value="<?= isset($row[$col]) && $row[$col] !== null && $row[$col] !== '' ? h((string) $row[$col]) : '' ?>">
                <?php elseif (($meta['tipo'] ?? '') === 'decimal'): ?>
                    <input type="text" inputmode="decimal" name="<?= h($col) ?>" value="<?= isset($row[$col]) && $row[$col] !== null && $row[$col] !== '' ? h((string) $row[$col]) : '' ?>" placeholder="ej. 80 o 80,5">
                <?php else: ?>
                    <input type="text" name="<?= h($col) ?>" value="<?= h((string) ($row[$col] ?? '')) ?>" maxlength="<?= (int) ($meta['max'] ?? 255) ?>">
                <?php endif; ?>
            </label>
        <?php endforeach; ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Guardar' : 'Crear' ?></button>
        </div>
    </form>
</div>
