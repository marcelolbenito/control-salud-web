<?php
declare(strict_types=1);
?>
<div class="container">
    <h1>Tablas auxiliares</h1>
    <p class="muted">Administrá listas del sistema de forma guiada por area funcional.</p>
    <div class="form-card" style="margin-bottom:1rem;">
        <label>Buscar tabla auxiliar
            <input type="text" id="catalog-search" placeholder="Ej: obra social, plan, ciudad, practica...">
        </label>
    </div>

    <?php
    $groups = [];
    foreach ($items as $it) {
        $cat = (string) ($it['categoria'] ?? 'General');
        if (!isset($groups[$cat])) {
            $groups[$cat] = [];
        }
        $groups[$cat][] = $it;
    }
    ?>
    <?php foreach ($groups as $cat => $rows): ?>
        <section class="card-like catalog-group catalog-group-<?= h(strtolower(str_replace([' ', 'ó', 'í', 'é', 'á', 'ú'], ['_', 'o', 'i', 'e', 'a', 'u'], (string) $cat))) ?>" data-catalog-category="<?= h(strtolower((string) $cat)) ?>">
            <h2><?= h((string) $cat) ?></h2>
            <div class="catalog-cards">
                <?php foreach ($rows as $it): ?>
                    <article class="catalog-card<?= $it['ok'] ? '' : ' is-missing' ?>" data-catalog-search="<?= h(strtolower((string) ($it['titulo'] . ' ' . $it['tabla'] . ' ' . ($it['descripcion'] ?? '')))) ?>">
                        <header>
                            <h2><?= h($it['titulo']) ?></h2>
                            <?php if ($it['ok']): ?>
                                <span class="badge badge-ok">OK</span>
                            <?php else: ?>
                                <span class="badge badge-missing">Falta</span>
                            <?php endif; ?>
                        </header>
                        <p class="muted small"><?= h((string) ($it['descripcion'] ?? '')) ?></p>
                        <p class="muted">
                            <code><?= h($it['tabla']) ?></code>
                        </p>
                        <p class="muted small">
                            Registros: <?= $it['rows_count'] !== null ? number_format((int) $it['rows_count'], 0, ',', '.') : '—' ?>
                        </p>

                        <?php if ($it['ok']): ?>
                            <p class="catalog-card-actions">
                                <a class="btn btn-primary btn-sm" href="<?= h('/catalogos.php?a=list&tabla=' . rawurlencode($it['tabla'])) ?>"><i class="bi bi-folder2-open" aria-hidden="true"></i> Abrir</a>
                            </p>
                        <?php else: ?>
                            <p class="muted small">Ejecutá migraciones de listas para habilitarla.</p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <p><a href="/sistema.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Sistema y configuración</a></p>
</div>
<script>
(function () {
    var input = document.getElementById('catalog-search');
    if (!input) return;
    function sync() {
        var q = String(input.value || '').toLowerCase().trim();
        var cards = document.querySelectorAll('[data-catalog-search]');
        cards.forEach(function (card) {
            var txt = String(card.getAttribute('data-catalog-search') || '');
            card.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
        });
        var groups = document.querySelectorAll('.catalog-group');
        groups.forEach(function (g) {
            var anyVisible = g.querySelector('[data-catalog-search]:not([style*="display: none"])');
            g.style.display = anyVisible ? '' : 'none';
        });
    }
    input.addEventListener('input', sync);
})();
</script>
