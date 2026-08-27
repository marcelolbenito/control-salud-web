<?php
/**
 * Plantilla HTML del informe PDF - formato real Centro Privado Salud.
 * La consume Dompdf via InformePdfRenderer.
 *
 * Variables disponibles (inyectadas por InformePdfRenderer):
 *   string $numero, string $fecha_emision, bool $es_parcial,
 *   array $pedido, array $paciente, array $resultados_por_area,
 *   array $lab (assoc-array con claves de lab_config),
 *   array $firmante,
 *   array $tecnicoPrincipal
 *
 * NUNCA echo de variables sin esc(): (XSS).
 */


/** @var string $numero */
/** @var string $fecha_emision */
/** @var bool $es_parcial */
/** @var array<string,mixed> $pedido */
/** @var array<string,mixed> $paciente */
/** @var array<string,array<int,array<string,mixed>>> $resultados_por_area */
/** @var array<string,mixed> $lab */
/** @var array<string,mixed> $firmante */
/** @var array<string,mixed> $tecnicoPrincipal */

$esc = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$fmtValor = static function (array $r): string {
    if (isset($r['valor_numerico']) && $r['valor_numerico'] !== null && $r['valor_numerico'] !== '') {
        $decimales = (int) ($r['decimales'] ?? 2);
        return number_format((float) $r['valor_numerico'], $decimales, ',', '.');
    }
    if (!empty($r['valor_texto'])) {
        return (string) $r['valor_texto'];
    }
    return '-';
};

$fmtReferencia = static function (array $r): string {
    $texto = trim((string) ($r['texto_referencia'] ?? ''));
    if ($texto !== '') {
        return $texto;
    }
    $min = $r['valor_referencia_min'] ?? null;
    $max = $r['valor_referencia_max'] ?? null;
    $dec = (int) ($r['decimales'] ?? 2);
    $fmt = static fn (mixed $v): string => number_format((float) $v, $dec, ',', '.');
    $unidad = trim((string) ($r['unidad'] ?? ''));
    $u = $unidad !== '' ? ' ' . $unidad : '';
    $tieneMin = $min !== null && $min !== '';
    $tieneMax = $max !== null && $max !== '';
    if ($tieneMin && $tieneMax) {
        return $fmt($min) . ' a ' . $fmt($max) . $u;
    }
    if ($tieneMin) {
        return '> ' . $fmt($min) . $u;
    }
    if ($tieneMax) {
        return '< ' . $fmt($max) . $u;
    }
    return '';
};

$storageBase = realpath(__DIR__ . '/../../../storage') ?: '';
$logoPath = (string) ($lab['laboratorio_logo_path'] ?? '');
$logoFull = $logoPath !== '' && $storageBase !== '' ? $storageBase . '/' . $logoPath : '';
$firmaPath = (string) ($firmante['firma_path'] ?? '');
$firmaFull = $firmaPath !== '' && $storageBase !== '' ? $storageBase . '/' . $firmaPath : '';

$apellidoPaciente = (string) ($paciente['apellido'] ?? '');
$nombresPaciente = (string) ($paciente['nombres'] ?? $paciente['nombre'] ?? '');
$apellidoNombre = trim($apellidoPaciente . ', ' . $nombresPaciente, ', ');
if ($apellidoNombre === '') {
    $apellidoNombre = (string) ($paciente['nombre'] ?? '');
}

$bqApellido = (string) ($firmante['apellido'] ?? '');
$bqNombres = (string) ($firmante['nombres'] ?? '');
$bqMatricula = (string) ($firmante['matricula'] ?? '');
$bqTitulo = (string) ($firmante['titulo'] ?? 'Bioquimica');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe <?= $esc($numero) ?></title>
    <style>
        @page { margin: 42mm 15mm 38mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1a1a1a; }

        header {
            position: fixed; top: -37mm; left: 0; right: 0; height: 32mm;
        }
        header .logo { float: left; width: 38mm; }
        header .logo img { max-width: 35mm; max-height: 28mm; }
        header .titulo { text-align: center; }
        header .titulo h1 { margin: 0; font-size: 17pt; font-weight: bold; }
        header .titulo h2 { margin: 0; font-size: 11pt; font-weight: normal; }
        header .firmantes { text-align: center; font-size: 9pt; margin-top: 2mm; }
        header .firmantes div { margin: 0; }
        header hr { border: none; border-top: 1px solid #000; margin-top: 3mm; }

        footer {
            position: fixed; bottom: -33mm; left: 0; right: 0; height: 32mm;
            text-align: center; font-size: 8.5pt;
        }
        footer .firma img { max-height: 14mm; display: block; margin: 0 auto; }
        footer .firma-data { font-weight: bold; font-size: 9pt; }
        footer .firma-data .titulo,
        footer .firma-data .matricula { font-weight: normal; font-size: 8.5pt; display: block; }
        footer .direccion { border-top: 1px solid #444; padding-top: 2mm; margin-top: 2mm; }
        footer .pagenum:after { content: counter(page); }

        .paciente { margin-top: 1mm; }
        .paciente .label { font-weight: bold; }
        .paciente div { margin-bottom: 1mm; }

        .legal {
            font-size: 7.5pt; color: #444;
            border-top: 1px solid #888; border-bottom: 1px solid #888;
            padding: 2mm 0; margin: 4mm 0 5mm 0;
            line-height: 1.4;
        }

        .header-tabla {
            margin: 5mm 0 1mm 0; padding: 1mm 0;
            border-bottom: 1px solid #000;
        }
        .header-tabla .col { display: inline-block; font-weight: bold; text-decoration: underline; font-size: 10pt; }
        .header-tabla .c1 { width: 40%; }
        .header-tabla .c2 { width: 20%; }
        .header-tabla .c3 { width: 38%; }

        h2.seccion { margin: 5mm 0 1mm 0; font-size: 10.5pt; font-weight: bold; }

        table.deters { width: 100%; border-collapse: collapse; }
        table.deters td { vertical-align: top; padding: 1mm 0; }
        table.deters .deter { width: 40%; }
        table.deters .deter .nombre { font-weight: bold; }
        table.deters .deter .metodo { font-style: italic; font-size: 8.5pt; padding-left: 3mm; color: #444; }
        table.deters .resultado { width: 22%; font-family: DejaVu Sans Mono, monospace; }
        table.deters .resultado.anormal { font-weight: bold; }
        table.deters .resultado.critico { font-weight: bold; color: #b42318; }
        table.deters .resultado.critico:after { content: " <?= str_replace(['"', '\\'], '', (string) ($lab['informe_label_critico'] ?? '') ?: 'CRITICO') ?>"; font-size: 7.5pt; background: #b42318; color: white; padding: 0 3px; margin-left: 2px; border-radius: 2px; }
        table.deters .referencia { width: 38%; font-size: 8.5pt; line-height: 1.3; }
        table.deters .referencia.multiline { white-space: pre-line; }

        .parcial-banner { background: #fef3f2; color: #b42318; padding: 4px 8px; border-radius: 3px; font-weight: bold; display: inline-block; font-size: 9pt; margin-bottom: 3mm; }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <?php if ($logoFull !== '' && is_file($logoFull)): ?>
                <img src="<?= $esc($logoFull) ?>" alt="Logo">
            <?php endif; ?>
        </div>
        <div class="titulo">
            <h1><?= $esc((string) ($lab['laboratorio_nombre'] ?? '')) ?></h1>
            <h2><?= $esc((string) ($lab['laboratorio_subtitulo'] ?? '')) ?></h2>
            <div class="firmantes">
                <div><?= $esc($bqTitulo) ?> <?= $esc($bqApellido) ?> <?= $esc($bqNombres) ?> - Matricula Prof. N&deg; <?= $esc($bqMatricula) ?></div>
                <div><?= $esc((string) ($tecnicoPrincipal['titulo'] ?? '')) ?> <?= $esc((string) ($tecnicoPrincipal['nombre'] ?? '')) ?></div>
            </div>
        </div>
        <hr style="clear: both;">
    </header>

    <footer>
        <div class="firma">
            <?php if ($firmaFull !== '' && is_file($firmaFull)): ?>
                <img src="<?= $esc($firmaFull) ?>" alt="Firma">
            <?php endif; ?>
            <div class="firma-data">
                <?= $esc(strtoupper(trim($bqApellido . ' ' . $bqNombres))) ?>
                <span class="titulo"><?= $esc(strtoupper($bqTitulo)) ?></span>
                <span class="matricula"><?= $esc($bqMatricula) ?></span>
            </div>
        </div>
        <div class="direccion">
            <?= $esc((string) ($lab['laboratorio_direccion'] ?? '')) ?>
            (<?= $esc((string) ($lab['laboratorio_telefono'] ?? '')) ?>)
            &mdash; P&aacute;gina <span class="pagenum"></span>
        </div>
    </footer>

    <?php if (!empty($es_parcial)): ?>
        <div class="parcial-banner">INFORME PARCIAL</div>
    <?php endif; ?>

    <div class="paciente">
        <div><span class="label">Paciente:</span> <?= $esc(strtoupper($apellidoNombre)) ?></div>
        <div><span class="label">DNI:</span><?= $esc((string) ($paciente['dni'] ?? '')) ?></div>
        <div><span class="label">Obra Social:</span> <?= $esc((string) ($pedido['obra_social'] ?? '')) ?></div>
        <div><span class="label">Fecha de Orden:</span> <?= $esc((string) ($pedido['fecha_solicitud'] ?? '')) ?></div>
    </div>

    <div class="legal">
        <?= $esc((string) ($lab['informe_legal_prefijo'] ?? '') ?: 'LABORATORIO AUTORIZADO POR EL COLEGIO DE BIOQUÍMICOS DE LA PROVINCIA DE CÓRDOBA SEGÚN') ?>
        <?= $esc((string) ($lab['laboratorio_resolucion_colegio'] ?? '')) ?> CON VENCIMIENTO
        <?= $esc((string) ($lab['laboratorio_resolucion_vencimiento'] ?? '')) ?> DIRECTOR T&Eacute;CNICO:
        <?= $esc(strtoupper(trim($bqApellido . ' ' . $bqNombres))) ?>
        MP:<?= $esc($bqMatricula) ?><br>
        Registro SISA: <?= $esc((string) ($lab['laboratorio_registro_sisa_razon_social'] ?? '')) ?>
        bajo el c&oacute;digo <?= $esc((string) ($lab['laboratorio_registro_sisa_codigo'] ?? '')) ?>
        <?php $notaPie = trim((string) ($lab['informe_nota_pie'] ?? '')); ?>
        <?php if ($notaPie !== ''): ?><br><?= $esc($notaPie) ?><?php endif; ?>
    </div>

    <div class="header-tabla">
        <span class="col c1"><?= $esc((string) ($lab['informe_label_determinacion'] ?? '') ?: 'DETERMINACIÓN') ?></span>
        <span class="col c2"><?= $esc((string) ($lab['informe_label_resultado'] ?? '') ?: 'RESULTADO') ?></span>
        <span class="col c3"><?= $esc((string) ($lab['informe_label_valores_referencia'] ?? '') ?: 'VALORES DE REFERENCIA') ?></span>
    </div>

    <?php foreach ($resultados_por_area as $areaNombre => $resultados): ?>
        <h2 class="seccion"><?= $esc(strtoupper((string) $areaNombre)) ?></h2>
        <table class="deters">
            <?php foreach ($resultados as $r): ?>
                <?php
                $clase = '';
                if (!empty($r['es_critico'])) $clase = 'critico';
                elseif (!empty($r['es_anormal'])) $clase = 'anormal';

                $resultadoStr = $fmtValor($r);
                $unidad = (string) ($r['unidad'] ?? '');
                $valorConUnidad = $resultadoStr . ($unidad !== '' ? ' ' . $unidad : '');
                $metodo = (string) ($r['determinacion_metodo'] ?? $r['metodo'] ?? '');
                $textoRef = $fmtReferencia($r);
                $multiline = str_contains($textoRef, "\n");
                ?>
                <tr>
                    <td class="deter">
                        <span class="nombre"><?= $esc((string) ($r['determinacion_nombre'] ?? '')) ?></span>
                        <?php if ($metodo !== ''): ?>
                            <div class="metodo">(<?= $esc($metodo) ?>)</div>
                        <?php endif; ?>
                    </td>
                    <td class="resultado <?= $esc($clase) ?>">
                        <?= $esc($valorConUnidad) ?>
                    </td>
                    <td class="referencia <?= $multiline ? 'multiline' : '' ?>"><?= $esc($textoRef) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>
</body>
</html>
