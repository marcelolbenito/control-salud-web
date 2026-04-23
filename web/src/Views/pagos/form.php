<?php

declare(strict_types=1);

/** @var array<string,mixed> $row */
/** @var string $error */
/** @var string $titulo */
/** @var string $volver */
/** @var string $queryString */
?>
<div class="container container-wide">
    <div class="page-head">
        <h1><?= h($titulo) ?></h1>
        <p class="muted"><a href="<?= h($volver) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a pagos</a></p>
    </div>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post" class="form-paciente">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
        <input type="hidden" name="pagos_return_qs" value="<?= h($queryString) ?>">

        <section class="form-section">
            <h2 class="form-section-title">Datos del pago</h2>
            <div class="form-grid-ext">
                <?php $quien = (string) ($row['quien'] ?? 'P'); ?>
                <label>Quién paga *
                    <select name="quien" required>
                        <option value="P"<?= $quien === 'P' ? ' selected' : '' ?>>Paciente</option>
                        <option value="C"<?= $quien === 'C' ? ' selected' : '' ?>>Cobertura</option>
                        <option value="O"<?= $quien === 'O' ? ' selected' : '' ?>>Otro</option>
                    </select>
                </label>
                <label>Nro HC *
                    <input type="number" name="NroPaci" required min="1" value="<?= h((string) ($row['NroPaci'] ?? '')) ?>">
                </label>
                <label>Id orden (opcional)
                    <input type="number" name="idorden" min="1" value="<?= h((string) ($row['idorden'] ?? '')) ?>">
                </label>
                <label>Importe *
                    <input type="text" name="importe" required inputmode="decimal" placeholder="0,00" value="<?= h((string) ($row['importe'] ?? '')) ?>">
                </label>
                <label>Fecha *
                    <input type="date" name="fecha" required value="<?= h((string) ($row['fecha'] ?? '')) ?>">
                </label>
                <label>Forma de pago
                    <?php $fp = trim((string) ($row['forma_pago'] ?? '')); ?>
                    <select name="forma_pago">
                        <option value="efectivo"<?= $fp === '' || $fp === 'efectivo' ? ' selected' : '' ?>>Efectivo</option>
                        <option value="transferencia"<?= $fp === 'transferencia' ? ' selected' : '' ?>>Transferencia</option>
                        <option value="debito"<?= $fp === 'debito' ? ' selected' : '' ?>>Tarjeta débito</option>
                        <option value="credito"<?= $fp === 'credito' ? ' selected' : '' ?>>Tarjeta crédito</option>
                        <option value="otro"<?= $fp === 'otro' ? ' selected' : '' ?>>Otro</option>
                    </select>
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

