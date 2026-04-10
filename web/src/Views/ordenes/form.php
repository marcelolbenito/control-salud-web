<?php

declare(strict_types=1);
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="<?= h($volver) ?>">← Volver al listado de órdenes</a></p>
    </div>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente">
        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
        <input type="hidden" name="ordenes_return_qs" value="<?= h((string) ($ordenesReturnQs ?? '')) ?>">

        <section class="form-section">
            <h2 class="form-section-title">Orden (Pacientes Ordenes)</h2>
            <div class="form-grid-ext">
                <label>Nro. HC (paciente) *
                    <input type="number" name="NroPaci" required min="1" value="<?= $row['NroPaci'] === '' || $row['NroPaci'] === null ? '' : (int) $row['NroPaci'] ?>">
                </label>
                <label>Fecha orden
                    <input type="date" name="fecha_orden" value="<?= h((string) $row['fecha_orden']) ?>">
                </label>
                <label>Profesional *
                    <select name="iddoctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) ($row['iddoctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form-check span-2"><input type="checkbox" name="autorizada" value="1" <?= !empty($row['autorizada']) ? ' checked' : '' ?>> Autorizada</label>
                <label class="form-check span-2"><input type="checkbox" name="entregada" value="1" <?= !empty($row['entregada']) ? ' checked' : '' ?>> Entregada</label>
                <label class="span-2">Observaciones
                    <textarea name="observaciones" rows="4" placeholder="Notas de la orden"><?= h((string) ($row['observaciones'] ?? '')) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>">Cancelar</a>
        </div>
    </form>
</div>
