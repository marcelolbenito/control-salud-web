<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Historia clínica</h1>
        <p class="muted">
            Paciente: <strong><?= h($nombre) ?></strong>
            · Nro HC: <strong><?= (int) ($p['NroHC'] ?? 0) ?></strong>
        </p>
        <p class="muted"><a href="/pacientes.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Pacientes</a></p>
    </div>

    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if (!$hasNotasHc): ?>
        <p class="alert alert-error">Falta la tabla <code>pacientes_hc_notas</code>. Ejecutá <code>sql/migration_026_hc_notas_inmutables.sql</code>.</p>
    <?php endif; ?>

    <?php if ($hasNotasHc && !$hasAdjuntosHc): ?>
        <p class="alert alert-error">Falta la tabla <code>pacientes_hc_adjuntos</code>. Ejecutá <code>sql/migration_027_hc_adjuntos.sql</code>.</p>
    <?php endif; ?>

    <form method="post" class="form-paciente" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <section class="form-section">
            <h2 class="form-section-title">Antecedentes clínicos vigentes</h2>
            <div class="form-grid-ext">
                <label class="span-2">
                    <textarea name="antecedentes_hc" rows="8" placeholder="Antecedentes clínicos relevantes"><?= h($antecedentes) ?></textarea>
                </label>
            </div>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Anotaciones registradas</h2>
            <?php if ($notasHc === []): ?>
                <p class="muted">Todavía no hay anotaciones de evolución para este paciente.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha/hora</th>
                                <th>Usuario</th>
                                <th>Anotación</th>
                                <th>Adjuntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notasHc as $n): ?>
                                <?php
                                $nid = (int) ($n['id'] ?? 0);
                                $adj = isset($adjuntosPorNota[$nid]) && is_array($adjuntosPorNota[$nid]) ? $adjuntosPorNota[$nid] : [];
                                ?>
                                <tr>
                                    <td><?= h((string) ($n['fecha_hora'] ?? $n['creado_en'] ?? '')) ?></td>
                                    <td><?= h((string) ($n['usuario_nombre'] ?? '')) ?></td>
                                    <td style="white-space:pre-wrap;"><?= h((string) ($n['texto'] ?? '')) ?></td>
                                    <td>
                                        <?php if ($adj === []): ?>
                                            <span class="muted">—</span>
                                        <?php else: ?>
                                            <?php foreach ($adj as $a): ?>
                                                <?php
                                                $tipoAdj = (string) ($a['tipo'] ?? '');
                                                $nombreAdj = (string) ($a['nombre'] ?? 'Adjunto');
                                                $mimeAdj = trim((string) ($a['mime'] ?? ''));
                                                $tamAdj = (int) ($a['tamano_bytes'] ?? 0);
                                                $sizeLabel = '';
                                                if ($tamAdj > 0) {
                                                    $sizeLabel = $tamAdj >= 1024 * 1024
                                                        ? number_format($tamAdj / (1024 * 1024), 2, ',', '.') . ' MB'
                                                        : number_format($tamAdj / 1024, 1, ',', '.') . ' KB';
                                                }
                                                ?>
                                                <?php if ((string) ($a['tipo'] ?? '') === 'archivo' && trim((string) ($a['ruta_archivo'] ?? '')) !== ''): ?>
                                                    <?php
                                                    $icono = 'bi-paperclip';
                                                    if (stripos($mimeAdj, 'pdf') !== false) {
                                                        $icono = 'bi-file-earmark-pdf';
                                                    } elseif (stripos($mimeAdj, 'image/') === 0) {
                                                        $icono = 'bi-image';
                                                    }
                                                    ?>
                                                    <div>
                                                        <a href="<?= h((string) $a['ruta_archivo']) ?>" target="_blank" rel="noopener">
                                                            <i class="bi <?= h($icono) ?>" aria-hidden="true"></i>
                                                            <?= h($nombreAdj) ?>
                                                        </a>
                                                        <?php if ($sizeLabel !== '' || $mimeAdj !== ''): ?>
                                                            <div class="muted small">
                                                                <?= h($sizeLabel !== '' ? $sizeLabel : '') ?>
                                                                <?= h($sizeLabel !== '' && $mimeAdj !== '' ? ' · ' : '') ?>
                                                                <?= h($mimeAdj !== '' ? $mimeAdj : '') ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ((string) ($a['tipo'] ?? '') === 'link' && trim((string) ($a['url'] ?? '')) !== ''): ?>
                                                    <div>
                                                        <a href="<?= h((string) $a['url']) ?>" target="_blank" rel="noopener">
                                                            <i class="bi bi-link-45deg" aria-hidden="true"></i>
                                                            <?= h((string) ($a['nombre'] ?? $a['url'] ?? 'Link')) ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Nueva anotación de evolución</h2>
            <div class="form-grid-ext">
                <label class="span-2">
                    <textarea name="hc_nueva_nota" rows="6" placeholder="Escribí la evolución del paciente..." <?= $hasNotasHc ? '' : 'disabled' ?>></textarea>
                </label>
                <label>Adjuntar archivo (PDF/JPG/PNG/WebP)
                    <input type="file" name="hc_adjunto_archivo" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" <?= ($hasNotasHc && $hasAdjuntosHc) ? '' : 'disabled' ?>>
                </label>
                <label>Link de estudio
                    <input type="url" name="hc_link_url" placeholder="https://...">
                </label>
                <label>Nombre del link (opcional)
                    <input type="text" name="hc_link_titulo" maxlength="180" placeholder="Ej. Resultado laboratorio 03/04">
                </label>
                <p class="muted span-2">Cada anotación se guarda con fecha/hora y queda inmutable (sin edición ni borrado).</p>
            </div>
        </section>

        <div class="form-actions form-section">
            <button type="submit" class="btn btn-primary" <?= $hasNotasHc ? '' : 'disabled' ?>>Agregar anotación</button>
            <a class="btn btn-ghost" href="/odontograma.php?id=<?= (int) $id ?>"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Odontograma</a>
            <a class="btn btn-ghost" href="/paciente_form.php?id=<?= (int) $id ?>"><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Editar datos del paciente</a>
        </div>

        <section class="form-section">
            <h2 class="form-section-title">Resumen histórico heredado (solo lectura)</h2>
            <div class="form-grid-ext">
                <label class="span-2">
                    <textarea rows="10" readonly><?= h((string) ($hcBaseDisplay ?? $hcBase ?? '')) ?></textarea>
                </label>
            </div>
        </section>
    </form>

</div>

