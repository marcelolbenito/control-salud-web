<?php

declare(strict_types=1);

/** @var bool $mapaSuperficiesActivo */
/** @var int $nroHC */
/** @var list<array<string, mixed>> $codigos */
/** @var bool $mapaSoloImpresion solo lectura (impresión) */
/** @var list<array<string, mixed>> $filasSuperficiesMapa filas de pacientes_odontograma_superficies + color + mapa_overlay */
/** @var bool $odontogramaMapaEmbebido si true, sin <section> propia (va dentro de la tarjeta de registro) */
/** @var bool $odontogramaMapaImpresionIntegrada impresión: solo el mapa, sin tarjeta/título propio (ya hay «Esquema dental») */
/** @var string $formPiezaFdiSeleccionada valor FDI del formulario para resaltar pieza */

if (empty($mapaSuperficiesActivo)) {
    return;
}

$mapaSoloImpresion = !empty($mapaSoloImpresion);
$odontogramaMapaImpresionIntegrada = !empty($odontogramaMapaImpresionIntegrada);
$odontogramaMapaEmbebido = !empty($odontogramaMapaEmbebido);
$formPiezaFdiSeleccionada = (string) ($formPiezaFdiSeleccionada ?? '');
$filasSuperficiesMapa = $filasSuperficiesMapa ?? [];

$estadoMapaSuperficies = [];
$marcaPiezaPorFdi = [];
foreach ($filasSuperficiesMapa as $row) {
    $fdi = (int) ($row['pieza_fdi'] ?? 0);
    $cara = strtoupper(trim((string) ($row['cara'] ?? '')));
    $idc = (int) ($row['id_codigo'] ?? 0);
    if ($fdi < 1 || $cara === '' || $idc < 1) {
        continue;
    }
    $hx = trim((string) ($row['color_hex'] ?? ''));
    if ($hx === '' || !preg_match('/^#[0-9A-Fa-f]{6}$/', $hx)) {
        $hx = '#cbd5e1';
    }
    if ($cara === 'P') {
        $ov = strtolower(trim((string) ($row['mapa_overlay'] ?? '')));
        if ($ov === '' || strpos($ov, 'pieza') !== 0) {
            $ov = 'pieza_relleno';
        }
        $marcaPiezaPorFdi[$fdi] = ['hex' => $hx, 'overlay' => $ov];
    } else {
        $estadoMapaSuperficies[$fdi . '-' . $cara] = $hx;
    }
}

$carasLateralesPorFdi = static function (int $fdi): array {
    $q = intdiv($fdi, 10);
    if (in_array($q, [1, 4, 5, 8], true)) {
        return ['D', 'M'];
    }
    if (in_array($q, [2, 3, 6, 7], true)) {
        return ['M', 'D'];
    }

    return ['M', 'D'];
};

$caraTitulo = [
    'V' => 'Vestibular',
    'M' => 'Mesial',
    'O' => 'Oclusal / incisal',
    'D' => 'Distal',
    'L' => 'Lingual / palatino',
];
$defaultFill = '#f8fafc';
$strokeColor = '#475569';

$colorHexCodigo = static function (array $c): string {
    $h = trim((string) ($c['color_hex'] ?? ''));
    if ($h !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $h)) {
        return $h;
    }

    return '#cbd5e1';
};

$poligonosBase = [
    'V' => '10,10 90,10 65,35 35,35',
    'L' => '10,90 90,90 65,65 35,65',
    'O' => '35,35 65,35 65,65 35,65',
];

/** SVG interno (viewBox 0 0 100 100) para marca de pieza completa */
$svgMarcaPieza = static function (string $overlay, string $hex): string {
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
        $hex = '#64748b';
    }
    $h = h($hex);
    $sw = '3.5';
    $ve = ' vector-effect="non-scaling-stroke"';
    switch ($overlay) {
        case 'pieza_diagonal':
            return '<line x1="14" y1="14" x2="86" y2="86" stroke="' . $h . '" stroke-width="' . $sw . '" stroke-linecap="round"' . $ve . '/>';
        case 'pieza_x':
            return '<line x1="14" y1="14" x2="86" y2="86" stroke="' . $h . '" stroke-width="' . $sw . '" stroke-linecap="round"' . $ve . '/>'
                . '<line x1="14" y1="86" x2="86" y2="14" stroke="' . $h . '" stroke-width="' . $sw . '" stroke-linecap="round"' . $ve . '/>';
        case 'pieza_circulo':
            return '<rect x="11" y="11" width="78" height="78" rx="12" fill="none" stroke="' . $h . '" stroke-width="' . $sw . '"' . $ve . '/>';
        case 'pieza_relleno':
            return '<rect x="10" y="10" width="80" height="80" rx="6" fill="' . $h . '" fill-opacity="0.42" stroke="' . $h . '" stroke-width="1"' . $ve . '/>';
        default:
            return '';
    }
};

$renderDiente = static function (int $fdi) use (
    $mapaSoloImpresion,
    $estadoMapaSuperficies,
    $marcaPiezaPorFdi,
    $svgMarcaPieza,
    $defaultFill,
    $strokeColor,
    $carasLateralesPorFdi,
    $caraTitulo,
    $poligonosBase,
    $formPiezaFdiSeleccionada
): void {
    [$cIzq, $cDer] = $carasLateralesPorFdi($fdi);
    $carasOrden = ['V', $cIzq, 'O', $cDer, 'L'];
    $polyIzq = '10,10 35,35 35,65 10,90';
    $polyDer = '90,10 90,90 65,65 65,35';

    $polyPorCara = [
        'V' => $poligonosBase['V'],
        $cIzq => $polyIzq,
        'O' => $poligonosBase['O'],
        $cDer => $polyDer,
        'L' => $poligonosBase['L'],
    ];
    $picked = $formPiezaFdiSeleccionada !== '' && (int) $formPiezaFdiSeleccionada === $fdi;
    $marca = $marcaPiezaPorFdi[$fdi] ?? null;
    ?>
    <div class="odonto-tooth-map<?= $picked ? ' is-picked-form' : '' ?>" data-fdi="<?= (int) $fdi ?>">
        <?php if ($mapaSoloImpresion): ?>
            <span class="odonto-tooth-num"><?= (int) $fdi ?></span>
        <?php else: ?>
            <button type="button" class="odonto-tooth-num odonto-tooth-pick-fdi" data-fdi="<?= (int) $fdi ?>"
                title="Elegir pieza para el registro. Mayús+clic: limpiar mapa de este diente."
                aria-label="Elegir pieza <?= (int) $fdi ?> para el registro del historial"><?= (int) $fdi ?></button>
        <?php endif; ?>
        <svg class="odonto-tooth-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <?php foreach ($carasOrden as $cara):
                $pts = $polyPorCara[$cara] ?? '';
                if ($pts === '') {
                    continue;
                }
                $key = $fdi . '-' . $cara;
                $fill = $estadoMapaSuperficies[$key] ?? $defaultFill;
                $tit = $caraTitulo[$cara] ?? $cara;
                $label = 'Pieza ' . $fdi . ' · ' . $tit;
                ?>
                <?php if ($mapaSoloImpresion): ?>
                    <polygon class="odonto-surface odonto-surface-static" points="<?= h($pts) ?>"
                        fill="<?= h($fill) ?>" stroke="<?= h($strokeColor) ?>" stroke-width="1"
                        vector-effect="non-scaling-stroke" data-cara="<?= h($cara) ?>"/>
                <?php else: ?>
                    <polygon class="odonto-surface" points="<?= h($pts) ?>"
                        fill="<?= h($fill) ?>" stroke="<?= h($strokeColor) ?>" stroke-width="1"
                        vector-effect="non-scaling-stroke" data-cara="<?= h($cara) ?>"
                        role="button" tabindex="0"
                        aria-label="<?= h($label) ?>"/>
                <?php endif; ?>
            <?php endforeach; ?>
            <g class="odonto-pieza-marca" data-fdi="<?= (int) $fdi ?>" style="pointer-events:none"><?php
                if ($marca !== null && ($marca['overlay'] ?? '') !== '') {
                    echo $svgMarcaPieza((string) $marca['overlay'], (string) $marca['hex']);
                }
            ?></g>
        </svg>
    </div>
    <?php
};

$archosPermSup = ['t' => 'Permanentes — arcada superior', 's' => array_merge(range(18, 11), [null], range(21, 28))];
$archosTempSup = ['t' => 'Temporales / deciduas — superior', 's' => array_merge(range(55, 51), [null], range(61, 65))];
$archosTempInf = ['t' => 'Temporales / deciduas — inferior', 's' => array_merge(range(85, 81), [null], range(71, 75))];
$archosPermInf = ['t' => 'Permanentes — arcada inferior', 's' => array_merge(range(48, 41), [null], range(31, 38))];

$renderArcho = static function (string $titulo, array $secuencia) use ($renderDiente): void {
    ?>
    <div class="odonto-mapa-archo">
        <p class="odonto-mapa-archo-tit muted small"><?= h($titulo) ?></p>
        <div class="odonto-mapa-fila">
            <?php foreach ($secuencia as $t): ?>
                <?php if ($t === null): ?>
                    <div class="odonto-mapa-separador" aria-hidden="true"></div>
                <?php else: ?>
                    <?php $renderDiente((int) $t); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
};
?>
<?php if ($odontogramaMapaImpresionIntegrada): ?>
<div class="odontograma-mapa-superficies odontograma-mapa-impresion-integrada">
<?php elseif ($mapaSoloImpresion || !$odontogramaMapaEmbebido): ?>
<section class="form-card odontograma-mapa-superficies" id="odontograma-mapa-seccion">
<?php else: ?>
<div class="odontograma-mapa-superficies odontograma-mapa-embebido">
<?php endif; ?>
    <?php if (!$odontogramaMapaImpresionIntegrada && ($mapaSoloImpresion || !$odontogramaMapaEmbebido)): ?>
        <h2 class="form-section-title">Mapa por caras (esquema clásico)</h2>
        <?php if (!$mapaSoloImpresion): ?>
            <p class="muted small">
                <strong>Caras:</strong> código con <em>marca pieza</em> vacía en tablas auxiliares. <strong>Pieza completa:</strong> código con
                <code>pieza_diagonal</code> (ausente / extracción), <code>pieza_x</code>, <code>pieza_circulo</code> (corona), <code>pieza_relleno</code>.
                <strong>Mayús+clic en el número</strong> limpia ese diente en el mapa. Guardá con <strong>Guardar mapa</strong>.
            </p>
        <?php else: ?>
            <p class="muted small">
                Esquema por caras y marcas de pieza (P). Leyenda en Tablas auxiliares.
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <div class="odonto-mapa-layout">
        <div class="odonto-mapa-chart">
            <div class="odontograma-mapa-visual" id="odontograma-mapa-root"
                 data-nrohc="<?= (int) $nroHC ?>"
                 data-solo-lectura="<?= $mapaSoloImpresion ? '1' : '0' ?>"
                 data-codigos-json="<?= h(json_encode($codigos, JSON_UNESCAPED_UNICODE)) ?>">
                <?php $renderArcho($archosPermSup['t'], $archosPermSup['s']); ?>
                <?php $renderArcho($archosTempSup['t'], $archosTempSup['s']); ?>
                <?php $renderArcho($archosTempInf['t'], $archosTempInf['s']); ?>
                <?php $renderArcho($archosPermInf['t'], $archosPermInf['s']); ?>
            </div>
        </div>

        <?php if (!$mapaSoloImpresion): ?>
            <aside class="odonto-mapa-sidebar no-print" aria-label="Pinceles de código clínico">
                <div class="odonto-pinceles odonto-pinceles-vertical" id="odontograma-mapa-pinceles">
                    <p class="odonto-pinceles-heading">Código / color</p>
                    <label class="odonto-pincel-op">
                        <input type="radio" name="odonto_pincel" value="0" checked>
                        <span class="odonto-pincel-borrar">Borrar cara o marca</span>
                    </label>
                    <?php foreach ($codigos as $c): ?>
                        <?php
                        $cid = (int) ($c['id'] ?? 0);
                        if ($cid < 1) {
                            continue;
                        }
                        $hx = $colorHexCodigo($c);
                        $sym = trim((string) ($c['codigo'] ?? ''));
                        $nom = trim((string) ($c['nombre'] ?? ''));
                        $ov = strtolower(trim((string) ($c['mapa_overlay'] ?? '')));
                        $suf = ($ov !== '' && strpos($ov, 'pieza') === 0) ? ' · pieza' : '';
                        $lab = ($sym !== '' ? $sym . ' — ' : '') . ($nom !== '' ? $nom : 'Código') . $suf;
                        ?>
                        <label class="odonto-pincel-op">
                            <input type="radio" name="odonto_pincel" value="<?= $cid ?>" data-color="<?= h($hx) ?>">
                            <span class="odonto-pincel-dot" style="background-color:<?= h($hx) ?>"></span>
                            <span><?= h($lab) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p style="margin:0.75rem 0 0;">
                    <button type="button" class="btn btn-primary" id="odontograma-mapa-guardar">Guardar mapa</button>
                </p>
                <p class="muted small" id="odontograma-mapa-estado" style="margin:0.35rem 0 0;"></p>
            </aside>
        <?php endif; ?>
    </div>
<?php if ($odontogramaMapaImpresionIntegrada): ?>
</div>
<?php elseif ($mapaSoloImpresion || !$odontogramaMapaEmbebido): ?>
</section>
<?php else: ?>
</div>
<?php endif; ?>

<?php if (!$mapaSoloImpresion): ?>
<script>
(function () {
    var root = document.getElementById('odontograma-mapa-root');
    if (!root || root.getAttribute('data-solo-lectura') === '1') return;
    var nrohc = parseInt(root.getAttribute('data-nrohc') || '0', 10);
    var estadoMsg = document.getElementById('odontograma-mapa-estado');
    var codigosJson = root.getAttribute('data-codigos-json') || '[]';
    var codigos;
    try { codigos = JSON.parse(codigosJson); } catch (e) { codigos = []; }
    var colorPorId = { '0': '#f8fafc' };
    var overlayPorId = { '0': '' };
    codigos.forEach(function (c) {
        var id = String(c.id);
        var h = (c.color_hex || '').trim();
        if (/^#[0-9A-Fa-f]{6}$/.test(h)) colorPorId[id] = h;
        else colorPorId[id] = '#cbd5e1';
        overlayPorId[id] = String(c.mapa_overlay || '').trim().toLowerCase();
    });

    function pincelActual() {
        var r = document.querySelector('input[name="odonto_pincel"]:checked');
        return r ? r.value : '0';
    }

    function colorPincel() {
        var p = pincelActual();
        return colorPorId[p] || '#f8fafc';
    }

    function esPincelPiezaCompleta(p) {
        if (!p || p === '0') return false;
        var o = overlayPorId[String(p)] || '';
        return o.indexOf('pieza') === 0;
    }

    function keyPiezaCara(fdi, cara) {
        return String(fdi) + '-' + cara;
    }

    var mapaLocal = {};

    function setFill(el, hex) {
        if (!el) return;
        el.setAttribute('fill', hex);
    }

    function svgMarcaInner(overlay, hex) {
        var sw = '3.5';
        var ve = ' vector-effect="non-scaling-stroke"';
        if (!/^#[0-9A-Fa-f]{6}$/.test(hex)) hex = '#64748b';
        switch (overlay) {
            case 'pieza_diagonal':
                return '<line x1="14" y1="14" x2="86" y2="86" stroke="' + hex + '" stroke-width="' + sw + '" stroke-linecap="round"' + ve + '/>';
            case 'pieza_x':
                return '<line x1="14" y1="14" x2="86" y2="86" stroke="' + hex + '" stroke-width="' + sw + '" stroke-linecap="round"' + ve + '/>'
                    + '<line x1="14" y1="86" x2="86" y2="14" stroke="' + hex + '" stroke-width="' + sw + '" stroke-linecap="round"' + ve + '/>';
            case 'pieza_circulo':
                return '<rect x="11" y="11" width="78" height="78" rx="12" fill="none" stroke="' + hex + '" stroke-width="' + sw + '"' + ve + '/>';
            case 'pieza_relleno':
                return '<rect x="10" y="10" width="80" height="80" rx="6" fill="' + hex + '" fill-opacity="0.42" stroke="' + hex + '" stroke-width="1"' + ve + '/>';
            default:
                return '';
        }
    }

    function refreshToothVisual(fdi) {
        var tooth = root.querySelector('.odonto-tooth-map[data-fdi="' + fdi + '"]');
        if (!tooth) return;
        tooth.querySelectorAll('.odonto-surface').forEach(function (face) {
            var cara = face.getAttribute('data-cara') || '';
            if (!cara) return;
            var k = keyPiezaCara(fdi, cara);
            var idc = mapaLocal[k];
            setFill(face, idc ? (colorPorId[String(idc)] || '#cbd5e1') : colorPorId['0']);
        });
        var g = tooth.querySelector('.odonto-pieza-marca');
        if (!g) return;
        var pk = keyPiezaCara(fdi, 'P');
        var pid = mapaLocal[pk];
        g.innerHTML = '';
        if (!pid) return;
        var ov = overlayPorId[String(pid)] || '';
        if (ov.indexOf('pieza') !== 0) ov = 'pieza_relleno';
        var hx = colorPorId[String(pid)] || '#ef4444';
        g.innerHTML = svgMarcaInner(ov, hx);
    }

    function borrarTodasLasClavesDelDiente(fdi) {
        var prefix = String(fdi) + '-';
        Object.keys(mapaLocal).forEach(function (k) {
            if (k.indexOf(prefix) === 0) delete mapaLocal[k];
        });
        refreshToothVisual(fdi);
    }

    window.addEventListener('odonto-clear-tooth-map', function (ev) {
        var d = ev.detail || {};
        var fdi = parseInt(d.fdi, 10);
        if (!fdi) return;
        borrarTodasLasClavesDelDiente(fdi);
    });

    function aplicarEstadoInicial(rows) {
        mapaLocal = {};
        (rows || []).forEach(function (row) {
            var f = parseInt(row.pieza_fdi, 10);
            var cara = String(row.cara || '').toUpperCase();
            var idc = parseInt(row.id_codigo, 10);
            if (!f || !cara || idc < 1) return;
            mapaLocal[keyPiezaCara(f, cara)] = idc;
        });
        root.querySelectorAll('.odonto-tooth-map').forEach(function (w) {
            refreshToothVisual(parseInt(w.getAttribute('data-fdi') || '0', 10));
        });
    }

    function recolectarCeldas() {
        var out = [];
        Object.keys(mapaLocal).forEach(function (k) {
            var idc = mapaLocal[k];
            if (!idc || idc < 1) return;
            var parts = k.split('-');
            if (parts.length < 2) return;
            var pieza = parseInt(parts[0], 10);
            var cara = parts[1];
            out.push({ pieza_fdi: pieza, cara: cara, id_codigo: idc });
        });
        return out;
    }

    function bindTooth(tooth) {
        var fdi = parseInt(tooth.getAttribute('data-fdi') || '0', 10);
        tooth.querySelectorAll('.odonto-surface').forEach(function (face) {
            function applyClick() {
                var cara = face.getAttribute('data-cara') || '';
                var p = pincelActual();
                var k = keyPiezaCara(fdi, cara);
                if (p === '0') {
                    delete mapaLocal[k];
                    refreshToothVisual(fdi);
                    return;
                }
                if (esPincelPiezaCompleta(p)) {
                    borrarTodasLasClavesDelDiente(fdi);
                    mapaLocal[keyPiezaCara(fdi, 'P')] = parseInt(p, 10);
                    refreshToothVisual(fdi);
                    return;
                }
                delete mapaLocal[keyPiezaCara(fdi, 'P')];
                mapaLocal[k] = parseInt(p, 10);
                refreshToothVisual(fdi);
            }
            face.addEventListener('click', applyClick);
            face.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    applyClick();
                }
            });
        });
    }

    root.querySelectorAll('.odonto-tooth-map').forEach(bindTooth);

    fetch('/odontograma_superficies_api.php?nrohc=' + encodeURIComponent(String(nrohc)), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.ok && data.celdas) aplicarEstadoInicial(data.celdas);
        })
        .catch(function () {});

    var btnGuardar = document.getElementById('odontograma-mapa-guardar');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', function () {
            if (nrohc < 1) return;
            btnGuardar.disabled = true;
            if (estadoMsg) estadoMsg.textContent = 'Guardando…';
            fetch('/odontograma_superficies_api.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nrohc: nrohc, celdas: recolectarCeldas() })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        if (estadoMsg) estadoMsg.textContent = 'Mapa guardado.';
                    } else {
                        if (estadoMsg) estadoMsg.textContent = (data && data.error) ? data.error : 'No se pudo guardar.';
                    }
                })
                .catch(function () {
                    if (estadoMsg) estadoMsg.textContent = 'Error de red.';
                })
                .finally(function () {
                    btnGuardar.disabled = false;
                });
        });
    }
})();
</script>
<?php endif; ?>
