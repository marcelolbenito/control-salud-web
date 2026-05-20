<?php
/**
 * Template HTML de la PORTADA del pedido.
 * Lo consume Dompdf via App\Helpers\PdfRenderer.
 *
 * Variables disponibles (extraidas del payload por PortadaService):
 *   string $numero, string $fecha_solicitud, ?string $fecha_extraccion,
 *   ?string $fecha_entrega, string $estado, string $prioridad, bool $es_critico,
 *   array{nombre,dni,sexo,fecha_nac} $paciente,
 *   string $medico, string $obra_social_nombre, string $numero_afiliado,
 *   string $diagnostico, string $observaciones,
 *   array<int,array{codigo,nombre,unidad,perfil,estado}> $items,
 *   string $fecha_emision
 *
 * NUNCA echo de variables sin esc(): (XSS).
 */


$esc = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 18mm 18mm 16mm 18mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #222; }
    h1 { font-size: 14pt; margin: 0; color: #2c5aa0; letter-spacing: 1px; }
    .subtitulo { font-size: 9pt; color: #666; margin: 0; }
    .header { border-bottom: 2px solid #2c5aa0; padding-bottom: 6px; margin-bottom: 14px; }
    .numero-grande { font-size: 22pt; font-weight: bold; color: #2c5aa0; text-align: right; line-height: 1; }
    .meta { font-size: 9pt; color: #555; text-align: right; }

    .grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .grid td { padding: 3px 6px; vertical-align: top; }
    .grid .label { color: #666; font-size: 8.5pt; width: 22%; }
    .grid .value { font-weight: bold; }
    .seccion-titulo { background: #e9eef5; color: #2c5aa0; padding: 4px 8px;
                      font-weight: bold; font-size: 9.5pt; margin-bottom: 4px; }

    table.items { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.items th { background: #2c5aa0; color: #fff; padding: 5px 8px; text-align: left; }
    table.items td { padding: 4px 8px; border-bottom: 1px solid #ddd; }
    table.items tr:nth-child(even) td { background: #f7f9fc; }

    .pie { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7.5pt; color: #999;
           border-top: 1px solid #ccc; padding-top: 4px; text-align: center; }
    .badge { display: inline-block; padding: 1px 8px; border-radius: 3px;
             font-size: 8.5pt; font-weight: bold; }
    .badge-prioridad-urgente { background: #ffe5b4; color: #884800; }
    .badge-prioridad-guardia { background: #ffcccc; color: #8a1a1a; }
    .badge-critico           { background: #ff5252; color: #fff; }
</style>
</head>
<body>

<table style="width:100%; border:0; margin:0;">
  <tr>
    <td style="width:60%;">
      <div class="header">
        <h1>LABORATORIO CLÍNICO</h1>
        <p class="subtitulo">Hoja de portada · Orden de análisis</p>
      </div>
    </td>
    <td style="width:40%; text-align:right; vertical-align:top;">
      <div class="numero-grande"><?= $esc($numero) ?></div>
      <div class="meta">Solicitud: <?= $esc($fecha_solicitud) ?></div>
      <div class="meta">Emitida: <?= $esc($fecha_emision) ?></div>
    </td>
  </tr>
</table>

<div class="seccion-titulo">PACIENTE</div>
<table class="grid">
  <tr>
    <td class="label">Nombre</td>
    <td class="value"><?= $esc($paciente['nombre'] ?? '') ?></td>
    <td class="label">DNI</td>
    <td class="value"><?= $esc($paciente['dni'] ?? '') ?></td>
  </tr>
  <tr>
    <td class="label">Sexo</td>
    <td class="value"><?= $esc($paciente['sexo'] ?? '') ?></td>
    <td class="label">Fecha de nacimiento</td>
    <td class="value"><?= $esc($paciente['fecha_nac'] ?? '') ?></td>
  </tr>
</table>

<div class="seccion-titulo">DATOS DE LA ORDEN</div>
<table class="grid">
  <tr>
    <td class="label">Médico solicitante</td>
    <td class="value"><?= $esc($medico) ?></td>
    <td class="label">Obra social</td>
    <td class="value"><?= $esc($obra_social_nombre) ?></td>
  </tr>
  <tr>
    <td class="label">Nº afiliado</td>
    <td class="value"><?= $esc($numero_afiliado) ?></td>
    <td class="label">Estado</td>
    <td class="value"><?= $esc($estado) ?></td>
  </tr>
  <tr>
    <td class="label">Prioridad</td>
    <td class="value">
      <?php if ($prioridad === 'urgente'): ?>
        <span class="badge badge-prioridad-urgente"><?= $esc($prioridad) ?></span>
      <?php elseif ($prioridad === 'guardia'): ?>
        <span class="badge badge-prioridad-guardia"><?= $esc($prioridad) ?></span>
      <?php else: ?>
        <?= $esc($prioridad) ?>
      <?php endif; ?>
      <?php if ($es_critico): ?>
        <span class="badge badge-critico">CRÍTICO</span>
      <?php endif; ?>
    </td>
    <td class="label">Fecha extracción</td>
    <td class="value"><?= $esc($fecha_extraccion) ?: '—' ?></td>
  </tr>
  <?php if ($diagnostico !== ''): ?>
  <tr>
    <td class="label">Diagnóstico</td>
    <td class="value" colspan="3"><?= $esc($diagnostico) ?></td>
  </tr>
  <?php endif; ?>
  <?php if ($observaciones !== ''): ?>
  <tr>
    <td class="label">Observaciones</td>
    <td class="value" colspan="3"><?= $esc($observaciones) ?></td>
  </tr>
  <?php endif; ?>
</table>

<div class="seccion-titulo">ANÁLISIS SOLICITADOS (<?= count($items) ?>)</div>
<?php if (count($items) === 0): ?>
  <p style="font-style: italic; color: #888;">Sin análisis cargados.</p>
<?php else: ?>
<table class="items">
  <thead>
    <tr>
      <th style="width: 12%;">Código</th>
      <th>Determinación</th>
      <th style="width: 18%;">Perfil</th>
      <th style="width: 12%;">Estado</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it): ?>
    <tr>
      <td><?= $esc($it['codigo']) ?></td>
      <td><?= $esc($it['nombre']) ?></td>
      <td><?= $esc($it['perfil']) ?></td>
      <td><?= $esc($it['estado']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="pie">
  Documento generado el <?= $esc($fecha_emision) ?> · <?= $esc($numero) ?>
</div>

</body>
</html>
