<?php

declare(strict_types=1);

/** @var string $piezaSeleccionada */
$odontogramaSvgInteractivo = $odontogramaSvgInteractivo ?? true;

$step = 36;
$gap = 32;
$rw = 28;
$rh = 32;
$stroke = '#7c3aed';
$fill = '#faf5ff';
$font = '600 10px sans-serif';

$tooth = static function (int $x, int $y, int $fdi) use ($rw, $rh, $stroke, $fill, $font, $piezaSeleccionada, $odontogramaSvgInteractivo): void {
    $sel = $piezaSeleccionada !== '' && (int) $piezaSeleccionada === $fdi;
    $f = $sel ? '#ede9fe' : $fill;
    $s = $sel ? '#5b21b6' : $stroke;
    $sw = $sel ? 2 : 1;
    if ($odontogramaSvgInteractivo) {
        $cl = $sel ? 'fdi-svg-tooth fdi-svg-decidua fdi-pick-trigger is-picked' : 'fdi-svg-tooth fdi-svg-decidua fdi-pick-trigger';
        $extra = ' role="button" tabindex="0" aria-label="Pieza temporal FDI ' . (int) $fdi . '"';
    } else {
        $cl = 'fdi-svg-tooth fdi-svg-decidua fdi-svg-static';
        $extra = ' aria-hidden="true"';
    }
    ?>
    <g class="<?= h($cl) ?>" data-fdi="<?= (int) $fdi ?>"<?= $extra ?>>
        <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $rw ?>" height="<?= $rh ?>" rx="6" ry="6" fill="<?= h($f) ?>" stroke="<?= h($s) ?>" stroke-width="<?= (int) $sw ?>"/>
        <text x="<?= $x + (int) ($rw / 2) ?>" y="<?= $y + 21 ?>" text-anchor="middle" fill="#4c1d95" style="font: <?= h($font) ?>"><?= (int) $fdi ?></text>
    </g>
    <?php
};

$x0 = 48;
$yU = 14;
$yL = 72;
$x = $x0;
?>
<div class="odontograma-svg-wrap form-card odontograma-svg-temporales" style="margin-top:0.75rem;padding:0.75rem 0.5rem;">
    <p class="muted small" style="margin:0 0 0.5rem 0.35rem;">Arcadas <strong>temporales / deciduas</strong> (FDI 51–85).<?= $odontogramaSvgInteractivo ? ' Mismo uso que las permanentes.' : '' ?></p>
    <svg viewBox="0 0 520 124" class="odontograma-svg odontograma-svg-temp" role="img" aria-label="Odontograma piezas temporales">
        <title>Piezas dentales temporales FDI</title>
        <?php
        foreach ([55, 54, 53, 52, 51] as $fdi) {
            $tooth((int) $x, $yU, $fdi);
            $x += $step;
        }
        $x += $gap;
        foreach (range(61, 65) as $fdi) {
            $tooth((int) $x, $yU, $fdi);
            $x += $step;
        }
        $x = $x0;
        foreach ([85, 84, 83, 82, 81] as $fdi) {
            $tooth((int) $x, $yL, $fdi);
            $x += $step;
        }
        $x += $gap;
        foreach (range(71, 75) as $fdi) {
            $tooth((int) $x, $yL, $fdi);
            $x += $step;
        }
        ?>
    </svg>
</div>
