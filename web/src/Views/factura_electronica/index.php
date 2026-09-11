<?php

declare(strict_types=1);

/** @var bool $hasTable */
/** @var bool $gesisOk */
/** @var bool $production */
/** @var list<array<string,mixed>> $rows */
/** @var string|null $flash */
/** @var string $error */
/** @var array<string,mixed> $form */

$fmtMoney = static function ($v): string {
    if ($v === null || $v === '' || !is_numeric($v)) {
        return '0,00';
    }

    return number_format((float) $v, 2, ',', '.');
};
?>
<div class="container container-wide">
    <div class="page-head">
        <h1>Facturación electrónica</h1>
        <p class="muted">Módulo aparte: elegí un paciente (cliente), cargá el texto y el monto, y emití contra Gesis2 / AFIP.</p>
        <p class="muted">
            <a href="/fe_parametros.php"><i class="bi bi-gear" aria-hidden="true"></i> Parámetros Gesis</a>
            · Entorno: <strong><?= !empty($production) ? 'Producción AFIP' : 'Homologación' ?></strong>
        </p>
    </div>

    <?php if ($flash): ?>
        <p class="alert"><?= h($flash) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="alert alert-error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if (!$hasTable): ?>
        <p class="alert alert-error">Falta la migración <code>sql/migration_040_factura_electronica.sql</code>.</p>
    <?php else: ?>
        <?php if (!$gesisOk): ?>
            <p class="alert alert-error">Gesis no está configurado. Completá email/clave en <a href="/fe_parametros.php">Parámetros</a>.</p>
        <?php endif; ?>

        <section class="form-card form-section" style="margin-bottom:1.5rem;">
            <h2 class="form-section-title" style="margin-top:0;">Emitir factura</h2>
            <form method="post" class="form-paciente" id="fe-emitir-form">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="emitir">
                <input type="hidden" name="id_paciente" id="fe_id_paciente" value="<?= (int) ($form['id_paciente'] ?? 0) ?>">
                <div class="form-grid-ext">
                    <label class="span-2">
                        Buscar paciente
                        <input type="search" id="fe_paciente_buscar" placeholder="DNI, HC o nombre…" autocomplete="off"
                            value="<?= h((string) ($form['paciente_nombre'] ?? '')) ?>">
                        <div id="fe_paciente_sugerencias" class="turno-sugerencias" style="display:none;"></div>
                    </label>
                    <label>
                        Nombre del cliente
                        <input type="text" name="paciente_nombre" id="fe_paciente_nombre" required maxlength="200"
                            value="<?= h((string) ($form['paciente_nombre'] ?? '')) ?>">
                    </label>
                    <label>
                        DNI / CUIT
                        <input type="text" name="paciente_doc" id="fe_paciente_doc" maxlength="30"
                            value="<?= h((string) ($form['paciente_doc'] ?? '')) ?>">
                    </label>
                    <label class="span-2">
                        Concepto / texto de la factura
                        <textarea name="concepto" rows="3" required maxlength="500" placeholder="Ej. Consulta particular — control"><?= h((string) ($form['concepto'] ?? '')) ?></textarea>
                    </label>
                    <label>
                        Importe total ($)
                        <input type="text" name="importe" required inputmode="decimal" placeholder="0,00"
                            value="<?= h((string) ($form['importe'] ?? '')) ?>">
                    </label>
                </div>
                <div class="form-actions" style="margin-top:1rem;">
                    <button type="submit" class="btn btn-primary"<?= $gesisOk ? '' : ' disabled' ?>
                        onclick="return confirm('¿Emitir factura electrónica con estos datos?');">
                        <i class="bi bi-receipt" aria-hidden="true"></i> Emitir
                    </button>
                </div>
            </form>
        </section>

        <section class="form-section">
            <h2 class="form-section-title">Últimos comprobantes</h2>
            <?php if ($rows === []): ?>
                <p class="empty-state">Todavía no hay facturas emitidas desde este módulo.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table table-sm data-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Comprobante</th>
                                <th>Cliente</th>
                                <th>Concepto</th>
                                <th class="num">Importe</th>
                                <th>CAE</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= h((string) ($r['fecha_emision'] ?? '')) ?></td>
                                    <td>
                                        <?= h((string) ($r['letra'] ?? 'C')) ?>
                                        <?= h(sprintf('%05d-%08d', (int) ($r['punto_venta'] ?? 0), (int) ($r['numero'] ?? 0))) ?>
                                        <?php if (empty($r['production'])): ?>
                                            <span class="muted small">(homo)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h((string) ($r['paciente_nombre'] ?? '')) ?></td>
                                    <td><?= h((string) ($r['concepto_texto'] ?? '')) ?></td>
                                    <td class="num">$ <?= h($fmtMoney($r['importe_total'] ?? 0)) ?></td>
                                    <td><?= h((string) ($r['cae'] ?? '')) ?></td>
                                    <td><a class="btn btn-sm btn-ghost" href="/fe_imprimir.php?id=<?= (int) $r['id'] ?>" target="_blank">Imprimir</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<script>
(function () {
    var buscar = document.getElementById('fe_paciente_buscar');
    var box = document.getElementById('fe_paciente_sugerencias');
    var idPac = document.getElementById('fe_id_paciente');
    var nom = document.getElementById('fe_paciente_nombre');
    var doc = document.getElementById('fe_paciente_doc');
    if (!buscar || !box) return;
    var t = null;
    function pick(it) {
        idPac.value = String(it.id || 0);
        var label = (it.nombre || '') + (it.dni ? (' - DNI ' + it.dni) : '');
        buscar.value = label;
        nom.value = it.nombre || '';
        doc.value = it.dni || '';
        box.style.display = 'none';
        box.innerHTML = '';
    }
    buscar.addEventListener('input', function () {
        idPac.value = '0';
        clearTimeout(t);
        var q = buscar.value.trim();
        if (q.length < 2) {
            box.style.display = 'none';
            return;
        }
        t = setTimeout(function () {
            fetch('/pacientes_lookup.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var items = (data && data.items) ? data.items : [];
                    if (!items.length) {
                        box.style.display = 'none';
                        return;
                    }
                    box.innerHTML = '';
                    items.forEach(function (it) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'turno-sugerencia-item';
                        b.textContent = 'HC ' + (it.nrohc || '') + ' — ' + (it.nombre || '') + (it.dni ? (' · DNI ' + it.dni) : '');
                        b.addEventListener('click', function () { pick(it); });
                        box.appendChild(b);
                    });
                    box.style.display = 'block';
                })
                .catch(function () { box.style.display = 'none'; });
        }, 250);
    });
})();
</script>
