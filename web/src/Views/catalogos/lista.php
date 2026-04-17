<?php
declare(strict_types=1);
?>
<div class="container">
    <p class="toolbar">
        <a class="btn btn-primary" href="<?= h('/catalogos.php?a=form&tabla=' . rawurlencode($tabla) . '&id=0') ?>">Nuevo ítem</a>
        <a class="btn btn-ghost" href="/catalogos.php">Catálogos</a>
    </p>
    <h1><?= h($titulo) ?></h1>
    <div class="table-wrap">
        <table class="table data-table">
            <thead>
            <tr>
                <th>ID</th>
                <?php foreach ($campos as $labelMeta): ?>
                    <th><?= h($labelMeta['label']) ?></th>
                <?php endforeach; ?>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int) $r['id'] ?></td>
                    <?php foreach ($campos as $col => $_meta): ?>
                        <td><?= h(isset($r[$col]) && $r[$col] !== null && $r[$col] !== '' ? (string) $r[$col] : '—') ?></td>
                    <?php endforeach; ?>
                    <td class="actions">
                        <a class="btn btn-sm btn-ghost" href="<?= h('/catalogos.php?a=form&tabla=' . rawurlencode($tabla) . '&id=' . (int) $r['id']) ?>">Editar</a>
                        <form method="post" action="/catalogos.php?a=delete" class="inline-form" onsubmit="return confirm('¿Eliminar este ítem?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="tabla" value="<?= h($tabla) ?>">
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
