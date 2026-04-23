<?php

declare(strict_types=1);

/** @var array<string, mixed> $p */
/** @var int $idPac */
/** @var int $nroHC */
/** @var string $nombre */
/** @var string $dni */
/** @var list<array<string, mixed>> $registros */
/** @var list<array<string, mixed>> $codigos */
/** @var bool $odontogramaExt */
/** @var bool $mapaSuperficiesActivo */
/** @var list<array<string, mixed>> $filasSuperficiesMapa */
/** @var string $imprimidoEn */
/** @var string $impPor */

$piezaSeleccionada = '';
$odontogramaSvgInteractivo = false;
$mapaSuperficiesActivo = $mapaSuperficiesActivo ?? false;
?>
<div class="odontograma-print-toolbar no-print">
    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir / PDF</button>
    <a class="btn btn-ghost" href="/odontograma.php?id=<?= (int) $idPac ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al odontograma</a>
</div>

<div class="container container-wide odontograma-print-sheet">
    <header class="odontograma-print-header">
        <h1>Odontograma — registro clínico</h1>
        <p class="odontograma-print-meta">
            <strong>Paciente:</strong> <?= h($nombre) ?>
            · <strong>Nro HC:</strong> <?= (int) $nroHC ?>
            <?php if ($dni !== ''): ?>
                · <strong>DNI:</strong> <?= h($dni) ?>
            <?php endif; ?>
        </p>
        <p class="odontograma-print-meta muted">
            <strong>Impreso:</strong> <?= h($imprimidoEn) ?>
            <?php if ($impPor !== ''): ?>
                · <strong>Usuario:</strong> <?= h($impPor) ?>
            <?php endif; ?>
        </p>
        <p class="muted small">
            <?php if ($mapaSuperficiesActivo): ?>
                Notación FDI (ISO 3950). El esquema impreso es el <strong>mapa por caras</strong> (permanentes y temporales); los colores y símbolos corresponden a los códigos configurados en el sistema.
            <?php else: ?>
                Notación FDI (ISO 3950). Arcadas permanentes (verde/azul) y temporales (violeta).
            <?php endif; ?>
        </p>
    </header>

    <?php if (!$mapaSuperficiesActivo && $codigos !== []): ?>
        <section class="odontograma-print-leyenda">
            <h2>Leyenda</h2>
            <ul class="odontograma-leyenda-list">
                <?php foreach ($codigos as $c): ?>
                    <?php
                    $sym = trim((string) ($c['codigo'] ?? ''));
                    $nom = trim((string) ($c['nombre'] ?? ''));
                    ?>
                    <li>
                        <?php if ($sym !== ''): ?>
                            <span class="odontograma-sim"><?= h($sym) ?></span>
                        <?php endif; ?>
                        <?= h($nom !== '' ? $nom : '—') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <section class="odontograma-print-graficos">
        <h2>Esquema dental</h2>
        <?php if ($mapaSuperficiesActivo): ?>
            <p class="muted small" style="margin:0 0 0.5rem 0;">Mapa actual por cara y marcas de pieza (mismo criterio que en pantalla).</p>
            <?php
            $filasSuperficiesMapa = $filasSuperficiesMapa ?? [];
            $mapaSoloImpresion = true;
            $odontogramaMapaImpresionIntegrada = true;
            require __DIR__ . '/_mapa_superficies.php';
            ?>
        <?php else: ?>
            <?php require __DIR__ . '/_svg_arcadas.php'; ?>
            <?php require __DIR__ . '/_svg_temporales.php'; ?>
        <?php endif; ?>
    </section>

    <section class="odontograma-print-historial">
        <h2>Historial de registros</h2>
        <?php if ($registros === []): ?>
            <p class="muted">Sin registros cargados.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table odontograma-print-table">
                    <thead>
                        <tr>
                            <th>Fecha / hora</th>
                            <th>Pieza</th>
                            <th>Caras</th>
                            <th>Código</th>
                            <th>Notas</th>
                            <?php if ($odontogramaExt): ?>
                                <th>Orden</th>
                                <th>Estado</th>
                            <?php endif; ?>
                            <th>Profesional</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                            <?php
                            $anul = !empty($r['anulado']);
                            $idOr = (int) ($r['id_orden'] ?? 0);
                            ?>
                            <tr class="<?= $anul ? 'odontograma-row-anulada' : '' ?>">
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>"><?= h((string) ($r['creado_en'] ?? '')) ?></td>
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>"><strong><?= (int) ($r['pieza_fdi'] ?? 0) ?></strong></td>
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>"><?= h((string) ($r['cara'] ?? '') ?: '—') ?></td>
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>">
                                    <?php
                                    $cs = trim((string) ($r['codigo_simbolo'] ?? ''));
                                    $cn = trim((string) ($r['codigo_nombre'] ?? ''));
                                    echo h($cs !== '' ? $cs . ($cn !== '' ? ' — ' . $cn : '') : ($cn !== '' ? $cn : '—'));
                                    ?>
                                </td>
                                <td class="<?= $anul ? 'odontograma-celda-tachada' : '' ?>"><?= h((string) ($r['notas'] ?? '')) ?></td>
                                <?php if ($odontogramaExt): ?>
                                    <td>
                                        <?php if ($idOr > 0): ?>
                                            #<?= (int) $idOr ?><?php
                                            $of = trim((string) ($r['orden_fecha'] ?? ''));
                                            echo $of !== '' ? ' · ' . h($of) : '';
                                            ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($anul): ?>
                                            Anulado: <?= h(trim((string) ($r['anulado_motivo'] ?? '')) ?: '—') ?>
                                            <?php
                                            $ae = trim((string) ($r['anulado_en'] ?? ''));
                                            if ($ae !== '') {
                                                echo ' · ' . h($ae);
                                            }
                                            ?>
                                        <?php else: ?>
                                            Vigente
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= h(trim((string) ($r['doctor_nombre'] ?? '')) ?: '—') ?></td>
                                <td><?= h(trim((string) ($r['usuario_web'] ?? '')) ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
