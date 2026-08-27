<?php

declare(strict_types=1);

/** @var array{id_clinica:int,nombre:string,direccion:string,encabezado:string,logo_path:string,logo_url:?string} $branding */
$tieneLogo = !empty($branding['logo_url']);
$tieneTexto = ($branding['nombre'] ?? '') !== ''
    || ($branding['direccion'] ?? '') !== ''
    || ($branding['encabezado'] ?? '') !== '';
if (!$tieneLogo && !$tieneTexto) {
    return;
}
?>
<div class="clinica-print-header">
    <?php if ($tieneLogo): ?>
        <img src="<?= h((string) $branding['logo_url']) ?>" alt="" class="clinica-print-logo">
    <?php endif; ?>
    <div class="clinica-print-header-text">
        <?php if (($branding['nombre'] ?? '') !== ''): ?>
            <div class="clinica-print-nombre"><?= h((string) $branding['nombre']) ?></div>
        <?php endif; ?>
        <?php if (($branding['direccion'] ?? '') !== ''): ?>
            <div class="clinica-print-direccion"><?= h((string) $branding['direccion']) ?></div>
        <?php endif; ?>
        <?php if (($branding['encabezado'] ?? '') !== ''): ?>
            <div class="clinica-print-encabezado"><?= nl2br(h((string) $branding['encabezado'])) ?></div>
        <?php endif; ?>
    </div>
</div>
