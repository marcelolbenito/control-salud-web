<?php
/**
 * Planilla de facturacion de un lote por obra social.
 * La consume Dompdf via LoteOsPdfRenderer.
 *
 * @var array<string,string> $lab
 * @var string $obraSocialNombre
 * @var array<string,mixed> $lote
 * @var array<int,array<string,mixed>> $pedidos
 * @var float $totalGeneral
 * @var string $fechaEmision
 */

$h = static fn ($s): string => htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');

$money = static fn ($v): string => '$ ' . number_format((float) $v, 2, ',', '.');

$ub = static function ($v): string {
    if ($v === null || $v === '') {
        return '';
    }
    $s = number_format((float) $v, 2, ',', '');
    $s = rtrim(rtrim($s, '0'), ',');
    return $s === '' ? '0' : $s;
};

$ubOs = static function ($v) use ($ub): string {
    if ($v === null || $v === '') {
        return '';
    }
    return '$ ' . $ub($v);
};

$bioquimico = trim(($lab['firmante_apellido'] ?? '') . ' ' . ($lab['firmante_nombres'] ?? ''));
$matricula  = (string) ($lab['firmante_matricula'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 34mm 14mm 16mm 14mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; }

    header { position: fixed; top: -30mm; left: 0; right: 0; }
    header .lab { text-align: center; font-size: 14pt; font-weight: bold; }
    header .dir { text-align: center; font-size: 8.5pt; }
    header .titulo { text-align: center; font-size: 12pt; font-weight: bold; text-decoration: underline; margin-top: 2.5mm; }
    header .os { font-size: 11pt; font-weight: bold; margin-top: 2mm; border-bottom: 1px solid #000; padding-bottom: 1mm; }

    footer { position: fixed; bottom: -11mm; left: 0; right: 0; text-align: center; font-size: 7.5pt; color: #666; }
    footer .pagenum:after { content: counter(page); }

    .pedido { margin-top: 5mm; }
    table.cab { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    table.cab td { padding: 0; vertical-align: top; }
    table.cab .lbl { text-decoration: underline; font-weight: bold; }

    table.items { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-top: 1.5mm; }
    table.items th { border-bottom: 1px solid #000; text-align: left; font-size: 8pt; font-weight: bold; padding: 1mm 1.5mm; }
    table.items td { padding: 0.6mm 1.5mm; }
    table.items .num { text-align: right; white-space: nowrap; }
    table.items .cod { width: 9%; }
    table.items .ub { width: 9%; }
    table.items .ubos { width: 14%; }
    table.items .precio { width: 16%; }

    .subtotal { text-align: right; font-weight: bold; border-top: 1px solid #000; padding-top: 1mm; margin-top: 0.5mm; font-size: 9pt; }
    .total-general { text-align: right; font-weight: bold; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 2mm 0; margin-top: 5mm; font-size: 10.5pt; }
</style>
</head>
<body>
    <header>
        <div class="lab"><?= $h($lab['laboratorio_nombre'] ?? '') ?></div>
        <div class="dir"><?= $h($lab['laboratorio_direccion'] ?? '') ?></div>
        <div class="titulo">PLANILLA DE FACTURACIÓN</div>
        <div class="os"><?= $h($obraSocialNombre) ?></div>
    </header>

    <footer>Página <span class="pagenum"></span> · Lote <?= $h($lote['numero'] ?? '') ?> · Emitida <?= $h($fechaEmision) ?></footer>

    <?php if (count($pedidos) === 0): ?>
        <p style="font-style: italic; color: #666;">El lote no tiene pedidos.</p>
    <?php endif; ?>

    <?php foreach ($pedidos as $p): ?>
        <div class="pedido">
            <table class="cab">
                <tr>
                    <td style="width: 60%;"><span class="lbl">Paciente:</span> <?= $h($p['paciente']) ?></td>
                    <td style="width: 40%;"><span class="lbl">Nº Afiliado:</span> <?= $h($p['nro_afiliado']) ?></td>
                </tr>
                <tr>
                    <td><span class="lbl">Nº Orden:</span> <?= $h($p['numero']) ?>
                        &nbsp;&nbsp; <span class="lbl">Fecha Orden:</span> <?= $h($p['fecha']) ?></td>
                    <td><span class="lbl">Bioquímico:</span> <?= $h($bioquimico) ?> - Matrícula: <?= $h($matricula) ?></td>
                </tr>
            </table>

            <table class="items">
                <thead>
                    <tr>
                        <th class="cod">CODIGO</th>
                        <th>DESCRIPCION</th>
                        <th class="ub num">U.B.</th>
                        <th class="ubos num">U.B. O.S.</th>
                        <th class="precio num">PRECIO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($p['items'] as $it): ?>
                        <tr>
                            <td class="cod"><?= $h($it['codigo']) ?></td>
                            <td><?= $h($it['descripcion']) ?></td>
                            <td class="ub num"><?= $h($ub($it['ub'])) ?></td>
                            <td class="ubos num"><?= $h($ubOs($it['ub_os'])) ?></td>
                            <td class="precio num"><?= $h($money($it['precio'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="subtotal">SUB TOTAL: <?= $h($money($p['subtotal'])) ?></div>
        </div>
    <?php endforeach; ?>

    <div class="total-general">TOTAL GENERAL: <?= $h($money($totalGeneral)) ?></div>
</body>
</html>
