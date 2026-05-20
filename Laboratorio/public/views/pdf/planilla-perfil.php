<?php
/**
 * Template de PLANILLA DE TRABAJO por perfil.
 * Filas = pedidos, columnas = determinaciones del perfil seleccionado.
 *
 * Variables:
 *   array{id,codigo,nombre} $perfil
 *   array<int,array{id,codigo,nombre,nombre_corto,unidad}> $determinaciones
 *   array<int,array{numero,fecha_solicitud,paciente_nombre,paciente_dni,
 *                   paciente_sexo,paciente_fnac}> $pedidos
 *   string $fecha_emision
 */


$esc = static fn (?string $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$nombreCorto = static function (array $d): string {
    return (string) ($d['nombre_corto'] !== null && $d['nombre_corto'] !== '' ? $d['nombre_corto'] : $d['codigo']);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 12mm 10mm; size: A4 landscape; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #222; }
    h1 { font-size: 12pt; margin: 0; color: #2c5aa0; }
    .meta { font-size: 8pt; color: #666; margin-top: 2px; }
    .header { border-bottom: 2px solid #2c5aa0; padding-bottom: 4px; margin-bottom: 8px; }

    table.planilla { width: 100%; border-collapse: collapse; }
    table.planilla th, table.planilla td { border: 1px solid #999; padding: 2px 4px; vertical-align: middle; }
    table.planilla thead th { background: #2c5aa0; color: #fff; font-size: 8pt; text-align: center; }
    table.planilla tbody td { height: 22px; }
    table.planilla .col-info { background: #f3f6fa; font-size: 7.5pt; }
    table.planilla .col-valor { width: 40px; text-align: center; background: #fff; }
    table.planilla .det-header { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; height: 90px; padding: 4px 2px; }

    .pie { margin-top: 14px; font-size: 7.5pt; color: #666; text-align: right; }
    .firma { margin-top: 18px; }
    .firma td { border: 0; padding-top: 24px; border-top: 1px solid #888; text-align: center; font-size: 7.5pt; color: #666; }
</style>
</head>
<body>

<div class="header">
    <table style="width:100%; border:0;">
      <tr>
        <td style="border:0;">
          <h1>PLANILLA DE TRABAJO — <?= $esc($perfil['nombre']) ?></h1>
          <div class="meta">Código de perfil: <?= $esc($perfil['codigo']) ?> · <?= count($pedidos) ?> pedidos · <?= count($determinaciones) ?> determinaciones</div>
        </td>
        <td style="border:0; text-align: right; font-size: 8pt; color: #666;">
          Emitida: <?= $esc($fecha_emision) ?>
        </td>
      </tr>
    </table>
</div>

<?php if (count($pedidos) === 0 || count($determinaciones) === 0): ?>
  <p style="color:#888; font-style: italic;">No hay datos para generar la planilla.</p>
<?php else: ?>

<table class="planilla">
  <thead>
    <tr>
      <th style="width: 7%;">Nº Orden</th>
      <th style="width: 7%;">Fecha</th>
      <th style="width: 18%;">Paciente</th>
      <th style="width: 9%;">DNI</th>
      <th style="width: 5%;">Sexo</th>
      <th style="width: 7%;">F. Nac.</th>
      <?php foreach ($determinaciones as $d): ?>
        <th class="det-header" title="<?= $esc($d['nombre']) ?>"><?= $esc($nombreCorto($d)) ?></th>
      <?php endforeach; ?>
    </tr>
    <tr>
      <th colspan="6" style="background:#e9eef5; color:#666;">unidad →</th>
      <?php foreach ($determinaciones as $d): ?>
        <th style="background:#e9eef5; color:#666; font-size: 7pt; font-weight: normal;"><?= $esc($d['unidad']) ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($pedidos as $p): ?>
    <tr>
      <td class="col-info"><?= $esc($p['numero']) ?></td>
      <td class="col-info"><?= $esc(substr((string) $p['fecha_solicitud'], 0, 10)) ?></td>
      <td class="col-info"><?= $esc($p['paciente_nombre']) ?></td>
      <td class="col-info"><?= $esc($p['paciente_dni']) ?></td>
      <td class="col-info" style="text-align:center;"><?= $esc($p['paciente_sexo']) ?></td>
      <td class="col-info"><?= $esc($p['paciente_fnac']) ?></td>
      <?php foreach ($determinaciones as $_d): ?>
        <td class="col-valor"></td>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<table class="firma" style="width: 100%; margin-top: 28px;">
  <tr>
    <td style="width: 33%;">Procesado por</td>
    <td style="width: 33%;">Validado por</td>
    <td style="width: 33%;">Fecha</td>
  </tr>
</table>

<?php endif; ?>

<div class="pie"><?= $esc($perfil['codigo']) ?> · Planilla emitida <?= $esc($fecha_emision) ?></div>

</body>
</html>
