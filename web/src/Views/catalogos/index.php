<?php
declare(strict_types=1);
?>
<div class="container">
    <h1>Catálogos (listas tipificadas)</h1>
    <p class="muted">ABM de tablas <code>lista_*</code> alineadas al modelo del sistema original (obras sociales, planes, país, documento, etc.). Solo se exponen catálogos permitidos por lista blanca.</p>
    <ul class="catalog-index">
        <?php foreach ($items as $it): ?>
            <li>
                <?php if ($it['ok']): ?>
                    <a href="<?= h('/catalogos.php?a=list&tabla=' . rawurlencode($it['tabla'])) ?>"><?= h($it['titulo']) ?></a>
                    <span class="muted">(<code><?= h($it['tabla']) ?></code>)</span>
                <?php else: ?>
                    <span class="muted"><?= h($it['titulo']) ?> — tabla no presente en esta base</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <p><a href="/sistema.php">Volver a Sistema y configuración</a></p>
</div>
