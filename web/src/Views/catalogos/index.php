<?php
declare(strict_types=1);
?>
<div class="container">
    <h1>Tablas auxiliares</h1>
    <p class="muted">CRUD de tablas secundarias (<code>lista_*</code>) del sistema: obras sociales, planes, países, documentos y demás valores tipificados.</p>

    <div class="catalog-cards">
        <?php foreach ($items as $it): ?>
            <article class="catalog-card<?= $it['ok'] ? '' : ' is-missing' ?>">
                <header>
                    <h2><?= h($it['titulo']) ?></h2>
                    <?php if ($it['ok']): ?>
                        <span class="badge badge-ok">Disponible</span>
                    <?php else: ?>
                        <span class="badge badge-missing">Falta tabla</span>
                    <?php endif; ?>
                </header>
                <p class="muted">
                    <code><?= h($it['tabla']) ?></code>
                </p>
                <p class="muted small">
                    Campos: <?= (int) $it['campos_count'] ?> · Registros:
                    <?= $it['rows_count'] !== null ? number_format((int) $it['rows_count'], 0, ',', '.') : '—' ?>
                </p>
                <p class="muted small">Orden: <?= h((string) $it['orden']) ?></p>

                <?php if ($it['ok']): ?>
                    <p class="catalog-card-actions">
                        <a class="btn btn-primary btn-sm" href="<?= h('/catalogos.php?a=list&tabla=' . rawurlencode($it['tabla'])) ?>">Abrir</a>
                    </p>
                <?php else: ?>
                    <p class="muted small">Ejecutá las migraciones de listas para habilitarla.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <p><a href="/sistema.php">Volver a Sistema y configuración</a></p>
</div>
