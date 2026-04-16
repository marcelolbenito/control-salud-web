<?php

declare(strict_types=1);

/** @var string $piezaSeleccionada valor FDI actual del select (string) */
$odontogramaSvgInteractivo = $odontogramaSvgInteractivo ?? true;

$step = 34;
$gap = 28;
$rw = 30;
$rh = 34;
$stroke = '#64748b';
$fill = '#ffffff';
$font = '600 11px sans-serif';

$tooth = static function (int $x, int $y, int $fdi) use ($rw, $rh, $stroke, $fill, $font, $piezaSeleccionada, $odontogramaSvgInteractivo): void {
    $sel = $piezaSeleccionada !== '' && (int) $piezaSeleccionada === $fdi;
    $f = $sel ? '#f0fdfa' : $fill;
    $s = $sel ? '#0d9488' : $stroke;
    $sw = $sel ? 2 : 1;
    if ($odontogramaSvgInteractivo) {
        $cl = $sel ? 'fdi-svg-tooth fdi-pick-trigger is-picked' : 'fdi-svg-tooth fdi-pick-trigger';
        $extra = ' role="button" tabindex="0" aria-label="Pieza FDI ' . (int) $fdi . '"';
    } else {
        $cl = 'fdi-svg-tooth fdi-svg-static';
        $extra = ' aria-hidden="true"';
    }
    ?>
    <g class="<?= h($cl) ?>" data-fdi="<?= (int) $fdi ?>"<?= $extra ?>>
        <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $rw ?>" height="<?= $rh ?>" rx="7" ry="7" fill="<?= h($f) ?>" stroke="<?= h($s) ?>" stroke-width="<?= (int) $sw ?>"/>
        <text x="<?= $x + (int) ($rw / 2) ?>" y="<?= $y + 22 ?>" text-anchor="middle" fill="#0f172a" style="font: <?= h($font) ?>"><?= (int) $fdi ?></text>
    </g>
    <?php
};

$x0 = 12;
$yU = 14;
$yL = 78;
$x = $x0;
?>
<div class="odontograma-svg-wrap form-card odontograma-svg-permanentes" style="margin-top:0.75rem;padding:0.75rem 0.5rem;">
    <p class="muted small" style="margin:0 0 0.5rem 0.35rem;">Arcadas <strong>permanentes</strong> (FDI 11–48).<?= $odontogramaSvgInteractivo ? ' Clic o foco + Enter para elegir pieza.' : '' ?></p>
    <svg viewBox="0 0 620 132" class="odontograma-svg" role="img" aria-label="Odontograma piezas permanentes">
        <title>Piezas dentales permanentes FDI</title>
        <?php
        foreach (range(18, 11) as $fdi) {
            $tooth((int) $x, $yU, $fdi);
            $x += $step;
        }
        $x += $gap;
        foreach (range(21, 28) as $fdi) {
            $tooth((int) $x, $yU, $fdi);
            $x += $step;
        }
        $x = $x0;
        foreach (range(48, 41) as $fdi) {
            $tooth((int) $x, $yL, $fdi);
            $x += $step;
        }
        $x += $gap;
        foreach (range(31, 38) as $fdi) {
            $tooth((int) $x, $yL, $fdi);
            $x += $step;
        }
        ?>
    </svg>
</div>
