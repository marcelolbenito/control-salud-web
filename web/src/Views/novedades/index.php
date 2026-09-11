<?php

declare(strict_types=1);

/** @var bool $hasTable */
/** @var list<array<string,mixed>> $rows */
/** @var string|null $flash */
/** @var string $error */
/** @var string $rol */
/** @var int $idUsuario */

$fmtSize = static function ($bytes): string {
    $n = (int) ($bytes ?? 0);
    if ($n <= 0) {
        return '—';
    }
    if ($n >= 1024 * 1024) {
        return number_format($n / (1024 * 1024), 2, ',', '.') . ' MB';
    }

    return number_format($n / 1024, 1, ',', '.') . ' KB';
};

$puedeBorrar = static function (array $r) use ($rol, $idUsuario): bool {
    if (in_array($rol, ['superadmin', 'admin_clinica'], true)) {
        return true;
    }
    $owner = (int) ($r['id_usuario'] ?? 0);

    return $idUsuario > 0 && $owner > 0 && $idUsuario === $owner;
};

$iconoMime = static function (?string $mime): string {
    $mime = (string) $mime;
    if (str_contains($mime, 'pdf')) {
        return 'bi-file-earmark-pdf';
    }
    if (str_starts_with($mime, 'image/')) {
        return 'bi-image';
    }

    return 'bi-paperclip';
};
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Info / Novedades</h1>
        <p class="muted">Carpeta compartida de la clínica: subí PDF o fotos de información general para que el resto del equipo las vea o descargue.</p>
    </div>

    <?php if ($flash): ?>
        <p class="alert"><?= h($flash) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if (!$hasTable): ?>
        <p class="alert alert-error">Falta la tabla <code>novedades_archivos</code>. Ejecutá <code>sql/migration_039_novedades.sql</code>.</p>
    <?php else: ?>
        <section class="form-card form-section" style="margin-bottom:1.5rem;">
            <h2 class="form-section-title" style="margin-top:0;">Publicar archivo</h2>
            <form method="post" enctype="multipart/form-data" class="form-paciente">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="subir">
                <div class="form-grid-ext">
                    <label>
                        Título (opcional)
                        <input type="text" name="titulo" maxlength="255" placeholder="Ej. Circulares OS septiembre">
                    </label>
                    <label>
                        Archivo (PDF / JPG / PNG / WebP, máx. 10 MB)
                        <input type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required>
                    </label>
                    <label class="span-2">
                        Nota breve (opcional)
                        <textarea name="descripcion" rows="2" maxlength="1000" placeholder="Texto corto para el equipo…"></textarea>
                    </label>
                </div>
                <div class="form-actions" style="margin-top:1rem;">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload" aria-hidden="true"></i> Subir</button>
                </div>
            </form>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Archivos publicados</h2>
            <?php if ($rows === []): ?>
                <p class="empty-state">Todavía no hay novedades. Subí el primer PDF o foto arriba.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table table-sm data-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Título</th>
                                <th>Archivo</th>
                                <th>Tamaño</th>
                                <th>Publicó</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $id = (int) ($r['id'] ?? 0);
                                $mime = (string) ($r['mime'] ?? '');
                                $esImg = str_starts_with($mime, 'image/');
                                $href = '/novedad_archivo.php?id=' . $id;
                                ?>
                                <tr>
                                    <td><?= h((string) ($r['creado_en'] ?? '')) ?></td>
                                    <td>
                                        <strong><?= h((string) ($r['titulo'] ?? '')) ?></strong>
                                        <?php if (trim((string) ($r['descripcion'] ?? '')) !== ''): ?>
                                            <div class="muted small"><?= h((string) $r['descripcion']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($esImg): ?>
                                            <div style="margin-top:0.4rem;">
                                                <a href="<?= h($href) ?>" target="_blank" rel="noopener">
                                                    <img src="<?= h($href) ?>" alt="" style="max-width:140px;max-height:90px;border-radius:4px;border:1px solid #ddd;">
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= h($href) ?>" target="_blank" rel="noopener">
                                            <i class="bi <?= h($iconoMime($mime)) ?>" aria-hidden="true"></i>
                                            <?= h((string) ($r['nombre_original'] ?? 'archivo')) ?>
                                        </a>
                                    </td>
                                    <td><?= h($fmtSize($r['tamano_bytes'] ?? 0)) ?></td>
                                    <td><?= h((string) ($r['usuario_nombre'] ?? '—')) ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-ghost" href="<?= h($href) ?>" download><i class="bi bi-download" aria-hidden="true"></i> Bajar</a>
                                        <?php if ($puedeBorrar($r)): ?>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este archivo de Info / Novedades?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-sm btn-ghost"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
