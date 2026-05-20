<?php

$pageTitle = 'Facturacion OS';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-receipt"></i> Facturacion</p>
                <h1>Facturaci&oacute;n a Obras Sociales</h1>
                <p class="lead">Generar lotes de pedidos por OS, exportar (PDF/CSV) y registrar el cobro.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <nav class="tabs" role="tablist">
            <button class="tab is-active" data-tab="listado" role="tab" aria-selected="true">Lotes existentes</button>
            <button class="tab" data-tab="nuevo" role="tab" aria-selected="false">Nuevo lote</button>
        </nav>

        <section id="tab-listado" class="tab-panel" role="tabpanel">
            <fieldset class="form-section">
                <legend>Filtros</legend>
                <div class="grid">
                    <label>OS <input type="number" id="filtro-os" placeholder="ID de OS"></label>
                    <label>Estado
                        <select id="filtro-estado">
                            <option value="">Todos</option>
                            <option value="abierto">Abierto</option>
                            <option value="cobrado">Cobrado</option>
                            <option value="anulado">Anulado</option>
                        </select>
                    </label>
                    <label>Desde <input type="date" id="filtro-desde"></label>
                    <label>Hasta <input type="date" id="filtro-hasta"></label>
                </div>
                <button id="btn-buscar-lotes" class="btn btn-primary">Buscar</button>
            </fieldset>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N&uacute;mero</th>
                            <th>OS</th>
                            <th>Periodo</th>
                            <th>Pedidos</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Acci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody id="lotes-tbody"></tbody>
                </table>
            </div>
        </section>

        <section id="tab-nuevo" class="tab-panel" role="tabpanel" hidden>
            <fieldset class="form-section">
                <legend>Buscar pedidos elegibles</legend>
                <div class="grid">
                    <label>Obra social <input type="number" id="nuevo-os" required></label>
                    <label>Desde <input type="date" id="nuevo-desde" required></label>
                    <label>Hasta <input type="date" id="nuevo-hasta" required></label>
                </div>
                <button id="btn-buscar-elegibles" class="btn btn-primary">Buscar pedidos elegibles</button>
            </fieldset>

            <div id="elegibles-resultado" hidden>
                <p id="elegibles-resumen" class="lead"></p>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="check-todos"></th>
                                <th>Pedido</th>
                                <th>Fecha</th>
                                <th>HC</th>
                                <th>Paciente</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody id="elegibles-tbody"></tbody>
                    </table>
                </div>
                <label>Observaciones <input type="text" id="nuevo-obs" maxlength="500"></label>
                <p id="seleccion-resumen"></p>
                <button id="btn-generar-lote" class="btn btn-primary">Generar lote</button>
            </div>
        </section>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_h('/assets/js/facturacion-os/index.js') ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
