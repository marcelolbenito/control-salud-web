<?php
/** @var array<string,mixed> $lote */
/** @var array<int,array<string,mixed>> $pedidos */
/** @var string $obraSocialNombre */
/** @var string $fechaEmision */

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #222; }
  h1 { font-size: 18px; margin: 0 0 4px; }
  .meta { font-size: 10px; color: #666; margin-bottom: 16px; }
  .card { border: 1px solid #ccc; padding: 8px 12px; margin-bottom: 12px; }
  .card .row { display: flex; justify-content: space-between; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th, td { border-bottom: 1px solid #eee; padding: 5px 6px; text-align: left; }
  th { background: #f5f5f5; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
  td.right, th.right { text-align: right; }
  tfoot td { font-weight: bold; border-top: 2px solid #333; }
  .estado { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
  .estado-abierto { background: #fff3cd; }
  .estado-cobrado { background: #d4edda; }
  .estado-anulado { background: #f8d7da; }
</style>
</head>
<body>
  <h1>Lote de facturacion <?= $h($lote['numero']) ?></h1>
  <p class="meta">Emitido: <?= $h($fechaEmision) ?></p>

  <div class="card">
    <div class="row">
      <strong>Obra social:</strong>
      <span><?= $h($obraSocialNombre) ?></span>
    </div>
    <div class="row">
      <strong>Periodo:</strong>
      <span><?= $h((string) $lote['fecha_desde']) ?> a <?= $h((string) $lote['fecha_hasta']) ?></span>
    </div>
    <div class="row">
      <strong>Estado:</strong>
      <span class="estado estado-<?= $h((string) $lote['estado']) ?>"><?= $h(strtoupper((string) $lote['estado'])) ?></span>
    </div>
    <div class="row">
      <strong>Pedidos:</strong>
      <span><?= (int) $lote['cantidad_pedidos'] ?></span>
    </div>
    <div class="row">
      <strong>Monto total:</strong>
      <span>$ <?= number_format((float) $lote['monto_total'], 2, ',', '.') ?></span>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Pedido</th>
        <th>Fecha</th>
        <th>HC</th>
        <th>Paciente</th>
        <th class="right">Monto</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pedidos as $p): ?>
        <tr>
          <td><?= $h((string) ($p['pedido_numero'] ?? '')) ?></td>
          <td><?= $h(substr((string) ($p['fecha_solicitud'] ?? ''), 0, 10)) ?></td>
          <td><?= $h((string) ($p['paciente_nro_hc'] ?? '')) ?></td>
          <td><?= $h(trim((string) ($p['paciente_apellido'] ?? '') . ', ' . (string) ($p['paciente_nombres'] ?? ''))) ?></td>
          <td class="right">$ <?= number_format((float) ($p['monto_seguro_snapshot'] ?? 0), 2, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="right">TOTAL</td>
        <td class="right">$ <?= number_format((float) $lote['monto_total'], 2, ',', '.') ?></td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
