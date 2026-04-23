<?php

declare(strict_types=1);

/** @var array<string,mixed> $row */
$fmtMoney = static function ($v): string {
    return number_format((float) $v, 2, ',', '.');
};
$quien = (string) ($row['quien'] ?? 'P');
$quienLabel = $quien === 'C' ? 'Cobertura' : ($quien === 'O' ? 'Otro' : 'Paciente');
?>
<div class="container" style="max-width:780px;">
    <div class="page-head">
        <h1>Recibo de pago #<?= (int) ($row['id'] ?? 0) ?></h1>
        <p class="muted">Comprobante simple para impresión</p>
    </div>
    <div class="form-card">
        <p><strong>Fecha:</strong> <?= h((string) ($row['fecha'] ?? '')) ?></p>
        <p><strong>Quién paga:</strong> <?= h($quienLabel) ?></p>
        <p><strong>Nro HC:</strong> <?= (int) ($row['NroPaci'] ?? 0) ?></p>
        <p><strong>Orden:</strong> <?= (int) ($row['idorden'] ?? 0) > 0 ? ('#' . (int) $row['idorden']) : '—' ?></p>
        <p><strong>Forma de pago:</strong> <?= h((string) ($row['forma_pago'] ?? '—')) ?></p>
        <p><strong>Importe:</strong> $ <?= h($fmtMoney($row['importe'] ?? 0)) ?></p>
        <p><strong>Observaciones:</strong> <?= h((string) ($row['observaciones'] ?? '')) ?></p>
        <hr>
        <p class="muted">Emitido desde Control Salud Web</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="window.print();">Imprimir</button>
        <a class="btn btn-ghost" href="/pagos.php"><i class="bi bi-x-lg" aria-hidden="true"></i> Cerrar</a>
    </div>
</div>

