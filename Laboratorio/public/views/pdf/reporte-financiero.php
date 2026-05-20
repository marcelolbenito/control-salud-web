<?php
/**
 * Template del REPORTE FINANCIERO (sub-proyecto 6).
 *
 * Variables:
 *   array<int,array<string,mixed>> $filas
 *   int $total
 *   array<string,string> $totales
 *   array<string,string> $filtros
 *   string $generado
 */


$esc = static fn (mixed $s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
$money = static fn (mixed $n): string => number_format((float) $n, 2, ',', '.');

$filtroLbl = [
    'numero'           => 'Nº Orden',
    'hc_desde'         => 'HC desde',
    'hc_hasta'         => 'HC hasta',
    'fecha_desde'      => 'Fecha desde',
    'fecha_hasta'      => 'Fecha hasta',
    'estado_paciente'  => 'Estado pac.',
    'estado_seguro'    => 'Estado seg.',
    'medico_id'        => 'Médico (id)',
    'obra_social_id'   => 'OS (id)',
    'solo_con_saldo'   => 'Solo con saldo',
    'incluir_anulados' => 'Incluir anulados',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 10mm 8mm; size: A4 landscape; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #222; }
    h1 { font-size: 13pt; margin: 0; color: #2c5aa0; }
    .meta { font-size: 7.5pt; color: #666; margin-top: 2px; }
    .header { border-bottom: 2px solid #2c5aa0; padding-bottom: 4px; margin-bottom: 8px; }

    .filtros { font-size: 7.5pt; color: #555; margin: 0 0 6px 0; }
    .filtros span { display: inline-block; margin-right: 10px; }
    .filtros strong { color: #333; }

    .totales { display: table; width: 100%; border-collapse: collapse; margin: 4px 0 8px 0; }
    .totales td { border: 1px solid #aac; padding: 4px 6px; vertical-align: middle; }
    .totales .lbl { background: #eef3fa; color: #555; font-size: 7pt; text-transform: uppercase; }
    .totales .val { font-weight: 700; color: #1d3f73; text-align: right; }

    table.rep { width: 100%; border-collapse: collapse; }
    table.rep th, table.rep td { border: 1px solid #999; padding: 2px 4px; vertical-align: middle; }
    table.rep thead th { background: #2c5aa0; color: #fff; font-size: 7.5pt; }
    table.rep tbody td { font-size: 7.5pt; }
    table.rep td.num { text-align: right; white-space: nowrap; }
    table.rep td.saldo { color: #b34f00; font-weight: 700; }
    .pie { margin-top: 8px; font-size: 7pt; color: #666; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <table style="width:100%; border:0;">
      <tr>
        <td style="border:0;">
          <h1>REPORTE FINANCIERO</h1>
          <div class="meta">Total de órdenes: <?= (int) ($totales['cantidad'] ?? 0) ?></div>
        </td>
        <td style="border:0; text-align:right; font-size:7.5pt; color:#666;">
          Generado: <?= $esc($generado ?? '') ?>
        </td>
      </tr>
    </table>
</div>

<?php if (!empty($filtros)): ?>
<div class="filtros">
    <strong>Filtros:</strong>
    <?php foreach ($filtros as $k => $v): ?>
        <span><strong><?= $esc($filtroLbl[$k] ?? $k) ?>:</strong> <?= $esc($v) ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<table class="totales">
    <tr>
        <td class="lbl">Cant. Órdenes</td>
        <td class="val"><?= (int) ($totales['cantidad'] ?? 0) ?></td>
        <td class="lbl">$ Paciente</td>
        <td class="val"><?= $money($totales['sum_monto_paciente'] ?? 0) ?></td>
        <td class="lbl">$ Seguro</td>
        <td class="val"><?= $money($totales['sum_monto_seguro'] ?? 0) ?></td>
        <td class="lbl">$ Honorarios</td>
        <td class="val"><?= $money($totales['sum_honorarios'] ?? 0) ?></td>
        <td class="lbl">Saldo Pac.</td>
        <td class="val"><?= $money($totales['sum_saldo_paciente'] ?? 0) ?></td>
        <td class="lbl">Saldo Seg.</td>
        <td class="val"><?= $money($totales['sum_saldo_seguro'] ?? 0) ?></td>
    </tr>
</table>

<?php if (count($filas) === 0): ?>
    <p style="color:#888; font-style:italic;">No se encontraron órdenes con esos filtros.</p>
<?php else: ?>
<table class="rep">
    <thead>
        <tr>
            <th>Nº Orden</th>
            <th>Fecha</th>
            <th>HC</th>
            <th>Paciente</th>
            <th>Médico</th>
            <th>Obra Social</th>
            <th>$ Pac.</th>
            <th>Pagado pac.</th>
            <th>Saldo pac.</th>
            <th>$ Seg.</th>
            <th>Pagado seg.</th>
            <th>Saldo seg.</th>
            <th>Honor.</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($filas as $f): ?>
        <?php
            $pac = trim(($f['paciente_apellido'] ?? '') . ', ' . ($f['paciente_nombres'] ?? ''));
            $med = !empty($f['medico_apellido'])
                ? trim($f['medico_apellido'] . ', ' . ($f['medico_nombres'] ?? ''))
                : (string) ($f['medico_externo'] ?? '');
            $cls = static fn ($n) => ((float) $n) > 0 ? 'num saldo' : 'num';
        ?>
        <tr>
            <td><?= $esc($f['numero']) ?></td>
            <td><?= $esc(substr((string) $f['fecha_solicitud'], 0, 10)) ?></td>
            <td><?= $esc($f['paciente_nro_hc']) ?></td>
            <td><?= $esc($pac) ?></td>
            <td><?= $esc($med) ?></td>
            <td><?= $esc($f['obra_social_nombre']) ?></td>
            <td class="num"><?= $money($f['monto_paciente']) ?></td>
            <td class="num"><?= $money($f['pagado_paciente']) ?></td>
            <td class="<?= $cls($f['saldo_paciente']) ?>"><?= $money($f['saldo_paciente']) ?></td>
            <td class="num"><?= $money($f['monto_seguro']) ?></td>
            <td class="num"><?= $money($f['pagado_seguro']) ?></td>
            <td class="<?= $cls($f['saldo_seguro']) ?>"><?= $money($f['saldo_seguro']) ?></td>
            <td class="num"><?= $money($f['monto_honorarios']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<div class="pie">Reporte generado el <?= $esc($generado ?? '') ?> · <?= count($filas) ?> filas mostradas</div>

</body>
</html>
