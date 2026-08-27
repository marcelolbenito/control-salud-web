<?php

declare(strict_types=1);

/** @var string $mod */
/** @var array<string, mixed> $actual */
/** @var array<string, list<array{id: string, titulo: string}>> $grupos */
/** @var list<array{url: string, titulo: string, texto: string}> $capturas */
/** @var string $portalPacienteAviso */
?>
<div class="container container-wide ayuda-page">
    <div class="page-head">
        <h1>Ayuda de uso</h1>
        <p class="muted">Guías por pantalla, capturas y preguntas frecuentes. Seleccione un tema en el índice.</p>
    </div>

    <?= $portalPacienteAviso ?>

    <div class="ayuda-layout">
        <nav class="ayuda-nav card-like" aria-label="Índice de ayuda">
            <?php foreach ($grupos as $categoria => $items): ?>
                <div class="ayuda-nav-group">
                    <h2 class="ayuda-nav-cat"><?= h($categoria) ?></h2>
                    <ul class="ayuda-nav-list">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <a class="ayuda-nav-link<?= $mod === $item['id'] ? ' is-active' : '' ?>"
                                   href="<?= h(url('/ayuda.php?mod=' . rawurlencode($item['id']))) ?>">
                                    <?= h($item['titulo']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </nav>

        <article class="ayuda-article card-like">
            <header class="ayuda-article-head">
                <p class="ayuda-article-cat muted"><?= h((string) ($actual['categoria'] ?? '')) ?></p>
                <h2><?= h((string) ($actual['titulo'] ?? '')) ?></h2>
                <p class="ayuda-article-resumen"><?= h((string) ($actual['resumen'] ?? '')) ?></p>
            </header>

            <?php if ($capturas !== []): ?>
                <section class="ayuda-capturas" aria-label="Diagramas y capturas">
                    <h3 class="ayuda-capturas-title"><i class="bi bi-diagram-3" aria-hidden="true"></i> <?= $mod === 'faq' ? 'Diagrama' : 'Capturas' ?></h3>
                    <div class="ayuda-capturas-grid">
                        <?php foreach ($capturas as $cap): ?>
                            <figure class="ayuda-captura">
                                <?php
                                $urlLower = strtolower($cap['url']);
                                $esSvg = strlen($urlLower) >= 4 && substr($urlLower, -4) === '.svg';
                                if ($esSvg) {
                                    ?>
                                    <img src="<?= h($cap['url']) ?>" alt="<?= h($cap['titulo']) ?>" loading="lazy">
                                    <?php
                                } else {
                                    ?>
                                    <a href="<?= h($cap['url']) ?>" target="_blank" rel="noopener">
                                        <img src="<?= h($cap['url']) ?>" alt="<?= h($cap['titulo']) ?>" loading="lazy">
                                    </a>
                                    <?php
                                }
                                ?>
                                <figcaption>
                                    <strong><?= h($cap['titulo']) ?></strong>
                                    <?php if ($cap['texto'] !== ''): ?>
                                        <span><?= h($cap['texto']) ?></span>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($actual['faqs'])): ?>
                <section class="ayuda-faq" aria-label="Preguntas frecuentes">
                    <h3 class="ayuda-faq-title"><i class="bi bi-chat-left-text" aria-hidden="true"></i> Preguntas frecuentes</h3>
                    <div class="ayuda-faq-list">
                        <?php foreach ($actual['faqs'] as $i => $faq): ?>
                            <details class="ayuda-faq-item"<?= $i === 0 ? ' open' : '' ?>>
                                <summary><?= h((string) ($faq['pregunta'] ?? '')) ?></summary>
                                <div class="ayuda-faq-answer">
                                    <p><?= h((string) ($faq['respuesta'] ?? '')) ?></p>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php foreach (($actual['secciones'] ?? []) as $sec): ?>
                <section class="ayuda-section">
                    <h3><?= h((string) ($sec['titulo'] ?? '')) ?></h3>
                    <ul class="ayuda-list">
                        <?php foreach (($sec['items'] ?? []) as $item): ?>
                            <li><?= h((string) $item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>

            <?php if (!empty($actual['enlaces'])): ?>
                <footer class="ayuda-enlaces">
                    <h3 class="ayuda-enlaces-title">Ir a la pantalla</h3>
                    <div class="ayuda-enlaces-actions">
                        <?php foreach ($actual['enlaces'] as $enlace): ?>
                            <a class="btn btn-ghost btn-sm" href="<?= h(url((string) ($enlace['href'] ?? '/'))) ?>">
                                <?= h((string) ($enlace['texto'] ?? 'Abrir')) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </footer>
            <?php endif; ?>

            <?php if ($mod === 'faq'): ?>
                <p class="muted ayuda-faq-hint">¿No encontró su duda? Revise el tema específico en el índice o consulte con el administrador del centro.</p>
            <?php endif; ?>
        </article>
    </div>
</div>
