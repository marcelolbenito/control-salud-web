<?php

declare(strict_types=1);

/** @var bool $hasTable */
/** @var array<string,mixed> $row */
/** @var string|null $flash */
/** @var string $error */
?>
<div class="container">
    <div class="page-head">
        <h1>Parámetros — Facturación electrónica</h1>
        <p class="muted">Credenciales Gesis2 y datos del emisor (misma API que en Instituto).</p>
        <p class="muted"><a href="/factura_electronica.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al módulo</a></p>
    </div>

    <?php if ($flash): ?>
        <p class="alert"><?= h($flash) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if (!$hasTable): ?>
        <p class="alert alert-error">Ejecutá <code>sql/migration_040_factura_electronica.sql</code>.</p>
    <?php else: ?>
        <form method="post" class="form-card form-paciente">
            <?= csrf_field() ?>
            <section class="form-section">
                <h2 class="form-section-title">Conexión Gesis</h2>
                <div class="form-grid-ext">
                    <label class="span-2">
                        URL del servicio
                        <input type="url" name="gesis_url" value="<?= h((string) ($row['gesis_url'] ?? 'https://servicios.gesis2.com')) ?>" required>
                    </label>
                    <label>
                        Email
                        <input type="email" name="gesis_email" value="<?= h((string) ($row['gesis_email'] ?? '')) ?>" required>
                    </label>
                    <label>
                        Password<?= trim((string) ($row['gesis_password'] ?? '')) !== '' ? ' (dejar vacío para no cambiar)' : '' ?>
                        <input type="password" name="gesis_password" value="" autocomplete="new-password"
                            <?= trim((string) ($row['gesis_password'] ?? '')) === '' ? ' required' : '' ?>>
                    </label>
                    <label>
                        Punto de venta
                        <input type="number" name="punto_venta" min="1" value="<?= (int) ($row['punto_venta'] ?? 1) ?>" required>
                    </label>
                    <label>
                        Tipo comprobante
                        <select name="cbte_tipo">
                            <?php
                            $tipos = [11 => 'Factura C (11)', 6 => 'Factura B (6)', 1 => 'Factura A (1)'];
                            $cur = (int) ($row['cbte_tipo'] ?? 11);
                            foreach ($tipos as $k => $lab):
                            ?>
                                <option value="<?= $k ?>"<?= $cur === $k ? ' selected' : '' ?>><?= h($lab) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Concepto AFIP
                        <select name="concepto">
                            <?php $c = (int) ($row['concepto'] ?? 2); ?>
                            <option value="2"<?= $c === 2 ? ' selected' : '' ?>>Servicios (2)</option>
                            <option value="1"<?= $c === 1 ? ' selected' : '' ?>>Productos (1)</option>
                            <option value="3"<?= $c === 3 ? ' selected' : '' ?>>Productos y servicios (3)</option>
                        </select>
                    </label>
                    <label class="span-2">
                        <input type="checkbox" name="production" value="1"<?= !empty($row['production']) ? ' checked' : '' ?>>
                        Usar <strong>producción</strong> AFIP (sin marcar = homologación)
                    </label>
                </div>
            </section>
            <section class="form-section">
                <h2 class="form-section-title">Datos del emisor (impresión)</h2>
                <div class="form-grid-ext">
                    <label>
                        CUIT emisor
                        <input type="text" name="cuit_emisor" value="<?= h((string) ($row['cuit_emisor'] ?? '')) ?>" placeholder="XX-XXXXXXXX-X">
                    </label>
                    <label>
                        Condición IVA
                        <select name="condicion_iva_emisor">
                            <?php
                            $conds = [
                                'monotributo' => 'Monotributo',
                                'responsable_inscripto' => 'Responsable inscripto',
                                'exento' => 'Exento',
                                'no_inscripto' => 'No responsable',
                            ];
                            $cc = (string) ($row['condicion_iva_emisor'] ?? 'monotributo');
                            foreach ($conds as $k => $lab):
                            ?>
                                <option value="<?= h($k) ?>"<?= $cc === $k ? ' selected' : '' ?>><?= h($lab) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="span-2">
                        Razón social
                        <input type="text" name="razon_social" maxlength="200" value="<?= h((string) ($row['razon_social'] ?? '')) ?>">
                    </label>
                    <label class="span-2">
                        Domicilio comercial
                        <input type="text" name="domicilio_comercial" maxlength="255" value="<?= h((string) ($row['domicilio_comercial'] ?? '')) ?>">
                    </label>
                </div>
            </section>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar parámetros</button>
            </div>
        </form>
    <?php endif; ?>
</div>
