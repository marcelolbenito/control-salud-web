<?php
/**
 * Template del TALÓN: comprobante corto para el paciente.
 *
 * Variables: $numero, $fecha_solicitud, ?$fecha_entrega,
 *            $paciente_nombre, $paciente_dni, $cantidad_items, $fecha_emision
 */


$esc = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 8mm; size: A6 portrait; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #222; }
    .titulo { text-align: center; font-size: 11pt; font-weight: bold; color: #2c5aa0;
              border-bottom: 1px solid #2c5aa0; padding-bottom: 3px; margin-bottom: 6px; }
    .numero { text-align: center; font-size: 16pt; font-weight: bold; margin: 6px 0; }
    .row { margin: 4px 0; }
    .row .lbl { color: #666; font-size: 8pt; }
    .row .val { font-weight: bold; }
    .pie { text-align: center; font-size: 7pt; color: #888; margin-top: 10px;
           border-top: 1px dashed #ccc; padding-top: 4px; }
</style>
</head>
<body>
    <div class="titulo">LABORATORIO CLÍNICO</div>
    <div style="text-align:center; font-size: 8pt; color: #666;">Comprobante de orden</div>

    <div class="numero"><?= $esc($numero) ?></div>

    <div class="row">
        <div class="lbl">Paciente</div>
        <div class="val"><?= $esc($paciente_nombre) ?></div>
    </div>
    <div class="row">
        <div class="lbl">DNI</div>
        <div class="val"><?= $esc($paciente_dni) ?></div>
    </div>
    <div class="row">
        <div class="lbl">Fecha de solicitud</div>
        <div class="val"><?= $esc($fecha_solicitud) ?></div>
    </div>
    <?php if (!empty($fecha_entrega)): ?>
    <div class="row">
        <div class="lbl">Retirar a partir de</div>
        <div class="val"><?= $esc($fecha_entrega) ?></div>
    </div>
    <?php endif; ?>
    <div class="row">
        <div class="lbl">Cantidad de análisis</div>
        <div class="val"><?= (int) $cantidad_items ?></div>
    </div>

    <div class="pie">
        Conserve este talón para retirar el resultado.<br>
        Emitido: <?= $esc($fecha_emision) ?>
    </div>
</body>
</html>
