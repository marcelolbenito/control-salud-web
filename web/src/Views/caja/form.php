<?php

declare(strict_types=1);

/** @var array<string, mixed> $row */
/** @var list<array<string, mixed>> $doctores */
/** @var list<array{id:int|string,nombre:?string}> $cobOpts */
/** @var string $error */
/** @var string $titulo */
/** @var string $volver */
/** @var string $queryString */
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="<?= h($volver) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Caja</a></p>
    </div>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>
    <form method="post" class="form-paciente" id="form-caja">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <input type="hidden" name="caja_return_qs" value="<?= h($queryString) ?>">

        <section class="form-section">
            <h2 class="form-section-title">Datos del movimiento</h2>
            <div class="form-grid-ext">
                <label>Profesional *
                    <select name="doctor" required>
                        <option value="">— Elegí —</option>
                        <?php foreach ($doctores as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"<?= (int) ($row['doctor'] ?? 0) === (int) $d['id'] ? ' selected' : '' ?>><?= h((string) ($d['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Fecha *
                    <input type="date" name="fechacaja" required value="<?= h((string) ($row['fechacaja'] ?? '')) ?>">
                </label>
                <label>Importe *
                    <input type="text" name="importecaja" required inputmode="decimal" placeholder="0 o 0,00" value="<?= h((string) ($row['importecaja'] ?? '')) ?>">
                </label>
                <?php $tipoMov = (string) ($row['tipo_movimiento'] ?? 'ingreso'); ?>
                <label>Tipo movimiento *
                    <select name="tipo_movimiento" required>
                        <option value="ingreso"<?= $tipoMov === 'ingreso' ? ' selected' : '' ?>>Ingreso</option>
                        <option value="egreso"<?= $tipoMov === 'egreso' ? ' selected' : '' ?>>Egreso</option>
                    </select>
                </label>
                <label>Cobertura
                    <select name="idcoberturacaja">
                        <?php catalogo_select_options($cobOpts, $row['idcoberturacaja'] ?? '', 'Sin especificar') ?>
                    </select>
                </label>
                <label class="span-2">Turno / detalle corto
                    <input type="text" name="turnocaja" maxlength="255" value="<?= h((string) ($row['turnocaja'] ?? '')) ?>">
                </label>
                <label class="span-2">Observaciones
                    <textarea name="observaciones" rows="4"><?= h((string) ($row['observaciones'] ?? '')) ?></textarea>
                </label>
            </div>
        </section>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a class="btn btn-ghost" href="<?= h($volver) ?>"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
        </div>
    </form>
</div>

