<?php

$pageTitle = 'Informes';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-file-earmark-pdf"></i> Informes</p>
                <h1>Informes PDF</h1>
                <p class="lead">Emitir, descargar y entregar informes de laboratorio.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Buscar pedido</legend>
            <form id="form-buscar" class="grid">
                <label>
                    Numero de pedido
                    <input type="number" id="buscar-pedido-id" min="1" required inputmode="numeric" placeholder="ID del pedido">
                </label>
                <button type="submit" class="grid-btn"><i class="bi bi-search"></i> Buscar</button>
            </form>
        </fieldset>

        <section id="panel-pedido" hidden>
            <fieldset class="form-section">
                <legend>Pedido</legend>
                <div id="info-pedido" class="muted"></div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Generar informe</legend>
                <form id="form-generar" class="grid">
                    <label>
                        Tipo
                        <select name="es_parcial">
                            <option value="0">Completo</option>
                            <option value="1">Parcial</option>
                        </select>
                    </label>
                    <label>
                        Firma (opcional)
                        <input type="text" name="firma" maxlength="150" placeholder="Bioq. Juan Perez - Mat. 12345">
                    </label>
                    <label class="span-2" style="grid-column: 1 / -1;">
                        Observaciones
                        <input type="text" name="observaciones" maxlength="500">
                    </label>
                    <div class="form-actions" style="grid-column: 1 / -1;">
                        <button type="submit"><i class="bi bi-file-earmark-plus"></i> Emitir informe</button>
                    </div>
                </form>
            </fieldset>

            <fieldset class="form-section">
                <legend>Informes emitidos</legend>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Emision</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="lista-informes"></tbody>
                    </table>
                </div>
            </fieldset>
        </section>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_h('/assets/js/informes/index.js') ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
