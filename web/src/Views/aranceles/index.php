<?php

declare(strict_types=1);
?>
<div class="container">
    <div class="page-head">
        <h1>Aranceles por obra social</h1>
        <p class="muted">
            Cada fila define cuánto paga el <strong>paciente</strong> y cuánto factura la <strong>obra social</strong>
            para una práctica. El código y nombre del nomenclador se administran en «Prácticas / estudios».
        </p>
        <?php if ($tabla !== null): ?>
            <p class="muted small">Tabla en uso: <code><?= h((string) $tabla) ?></code></p>
        <?php else: ?>
            <p class="alert alert-error">No hay tabla <code>lista_precios</code> ni <code>Lista Precios</code>. Ejecutá <code>sql/migration_032_lista_precios.sql</code> o importá los datos del sistema original.</p>
        <?php endif; ?>
    </div>

    <div class="page-actions">
        <?php if ($tabla !== null && $idCobertura > 0): ?>
            <a class="btn btn-primary" href="<?= h('/aranceles.php?a=form&cobertura=' . (int) $idCobertura) ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo arancel</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="/catalogos.php?a=list&amp;tabla=lista_practicas"><i class="bi bi-journals" aria-hidden="true"></i> Prácticas / estudios</a>
        <a class="btn btn-ghost" href="/catalogos.php?a=list&amp;tabla=lista_coberturas"><i class="bi bi-building" aria-hidden="true"></i> Obras sociales</a>
    </div>

    <?php if (($cobOpts ?? []) !== []): ?>
        <form method="get" action="/aranceles.php" id="aranceles_filtro" class="form-card" style="margin-bottom:1rem;">
            <h2 class="form-section-title" style="margin-top:0;">Filtrar aranceles</h2>
            <div class="filter-row">
                <?php
                $cbLabel = 'Obra social / cobertura';
                $cbPlaceholder = 'Buscar por código o nombre';
                $cbName = 'cobertura';
                $cbHiddenId = 'aranceles_cobertura';
                $cbInputId = 'aranceles_cobertura_buscar';
                $cbListId = 'aranceles_coberturas_lista';
                $cbOpts = $cobOpts;
                $cbSelected = $idCobertura;
                $cbRequired = false;
                $cbAutoSubmitFormId = 'aranceles_filtro';
                $cbGrowClass = 'filter-grow';
                $cbSubmitTextName = 'cobertura_txt';
                $cbSelected = $idCobertura;
                $cbHint = 'Código interno o nombre de la cobertura.';
                $cbValor = trim((string) ($coberturaTxt ?? ''));
                if ($cbValor === '' && $idCobertura > 0) {
                    $cbValor = catalogo_valor_datalist($cobOpts, $idCobertura);
                }
                require dirname(__DIR__) . '/_partials/catalogo_buscar.php';
                ?>
                <label>
                    Buscar práctica en la lista
                    <input type="search" name="q" value="<?= h($q) ?>" placeholder="Código o nombre de práctica"<?= $idCobertura < 1 ? ' disabled' : '' ?>>
                </label>
                <label class="filter-actions-label">
                    &nbsp;
                    <button type="submit" class="btn btn-primary"<?= $idCobertura < 1 ? ' disabled' : '' ?>>Buscar</button>
                </label>
            </div>
            <p class="muted small" style="margin:0;">Al elegir una obra social se cargan sus aranceles. Después podés filtrar por práctica.</p>
        </form>
    <?php else: ?>
        <p class="alert alert-error">No hay catálogo de coberturas (<code>lista_coberturas</code>). Cargalo desde Tablas auxiliares.</p>
    <?php endif; ?>

    <?php if (!empty($coberturaNoResuelta)): ?>
        <p class="alert alert-error">No se reconoció la obra social «<?= h((string) $coberturaTxt) ?>». Elegí de la lista (ej. <strong>11 - OMINT</strong>) o probá el enlace directo: <a href="/aranceles.php?cobertura=11">OMINT (11)</a>.</p>
    <?php elseif ($idCobertura < 1): ?>
        <p class="empty-state">Escribí el código o nombre (ej. <strong>11</strong> o <strong>OMINT</strong>) y pulsá Buscar, o elegí de la lista desplegable.</p>
    <?php elseif ($tabla === null): ?>
        <p class="empty-state">Sin tabla de precios no se pueden consultar aranceles.</p>
    <?php elseif ($rows === []): ?>
        <p class="empty-state">No hay aranceles<?= $q !== '' ? ' que coincidan con la búsqueda' : '' ?> para <?= h($coberturaNombre) ?>.</p>
    <?php else: ?>
        <p class="muted small">
            <?= h($coberturaNombre) ?> — <?= number_format((int) $total, 0, ',', '.') ?> registro(s)
            <?php if ($totalPages > 1): ?>
                · página <?= (int) $page ?> de <?= (int) $totalPages ?>
            <?php endif; ?>
        </p>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Práctica</th>
                        <th>Plan</th>
                        <th class="num">Paciente</th>
                        <th class="num">Obra social</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int) $r['id'] ?></td>
                            <td>
                                <?php if (!empty($r['practica_nombre'])): ?>
                                    <?php if (trim((string) ($r['practica_codigo'] ?? '')) !== ''): ?>
                                        <strong><?= h((string) $r['practica_codigo']) ?></strong> —
                                    <?php endif; ?>
                                    <?= h((string) $r['practica_nombre']) ?>
                                <?php else: ?>
                                    <span class="muted">Práctica sin catálogo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $idPlan = (int) ($r['idplan'] ?? 0);
                                if ($idPlan > 0 && !empty($r['plan_nombre'])) {
                                    echo h((string) $r['plan_nombre']);
                                } elseif ($idPlan > 0) {
                                    echo '#' . $idPlan;
                                } else {
                                    echo '<span class="muted">General</span>';
                                }
                                ?>
                            </td>
                            <td class="num"><?= $r['costopaciente'] !== null && $r['costopaciente'] !== '' ? '$' . number_format((float) $r['costopaciente'], 2, ',', '.') : '—' ?></td>
                            <td class="num"><?= $r['costocobertura'] !== null && $r['costocobertura'] !== '' ? '$' . number_format((float) $r['costocobertura'], 2, ',', '.') : '—' ?></td>
                            <td class="table-actions">
                                <a class="btn btn-sm btn-ghost" href="<?= h('/aranceles.php?a=form&id=' . (int) $r['id']) ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar</a>
                                <form method="post" action="/aranceles.php?a=delete" class="inline-form" onsubmit="return confirm('¿Eliminar este arancel?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <input type="hidden" name="cobertura" value="<?= (int) $idCobertura ?>">
                                    <button type="submit" class="btn btn-sm btn-ghost btn-danger-text">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Páginas">
                <?php
                $base = '/aranceles.php?cobertura=' . (int) $idCobertura;
                if ($q !== '') {
                    $base .= '&q=' . rawurlencode($q);
                }
                ?>
                <?php if ($page > 1): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= h($base . '&p=' . ($page - 1)) ?>">← Anterior</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= h($base . '&p=' . ($page + 1)) ?>">Siguiente →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php if (!empty($cobOpts)): ?>
    <?php catalogo_buscar_script_tag(); ?>
<?php endif; ?>
