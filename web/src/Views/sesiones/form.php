<?php

declare(strict_types=1);

/** @var array<string, mixed> $row */
/** @var list<array<string, mixed>> $doctores */
/** @var string $error */
/** @var string $titulo */
/** @var string $volver */
/** @var string $queryString */
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="<?= h($volver) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a sesiones</a></p>
    </div>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form-paciente">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <input type="hidden" name="sesiones_return_qs" value="<?= h($queryString) ?>">

        <section class="form-section">
            <h2 class="form-section-title">Datos de la sesión</h2>
            <div class="form-grid-ext">
                <label>Id orden *
                    <input type="number" name="idorden" required min="1" value="<?= h((string) ($row['idorden'] ?? '')) ?>">
                </label>
                <label>Nro HC (paciente) *
                    <input type="number" name="NroPaci" required min="1" value="<?= h((string) ($row['NroPaci'] ?? '')) ?>">
                </label>
                <label>Profesional *
                    <select name="iddoctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) ($row['iddoctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fecha sesión *
                    <input type="date" name="fecha_sesion" required value="<?= h((string) ($row['fecha_sesion'] ?? '')) ?>">
                </label>
                <label>Cantidad *
                    <input type="number" name="cantidad_sesiones" required min="1" value="<?= h((string) ($row['cantidad_sesiones'] ?? '1')) ?>">
                </label>
                <label class="span-2">Observaciones
                    <textarea name="observaciones" rows="3"><?= h((string) ($row['observaciones'] ?? '')) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
        </div>
    </form>
</div>
