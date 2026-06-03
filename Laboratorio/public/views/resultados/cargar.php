<?php

$pageTitle = 'Cargar resultados';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-input-cursor-text"></i> Resultados</p>
                <h1>Cargar resultados</h1>
                <p class="lead">Cargar valores numericos o textuales para los items de un pedido.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Buscar pedido</legend>
            <div class="buscador-pedido">
                <label class="busqueda-rapida">
                    Buscar
                    <input type="search" id="buscar-q" autocomplete="off"
                           placeholder="DNI, apellido, nombre, HC o N&deg; orden...">
                </label>
                <ul id="buscar-resultados" class="dropdown-resultados" hidden></ul>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Pedidos</legend>
            <p class="hint">Todos los pedidos. Filtr&aacute; por d&iacute;a y hac&eacute; click en &quot;Cargar&quot; para ingresar resultados.</p>
            <div class="form-actions" style="margin-bottom: 1rem; align-items: flex-end; gap: 0.75rem; flex-wrap: wrap;">
                <label class="busqueda-rapida" style="margin:0;">
                    D&iacute;a (fecha de solicitud)
                    <input type="date" id="filtro-dia">
                </label>
                <button type="button" id="btn-hoy" class="btn btn-sm">Hoy</button>
                <button type="button" id="btn-todos" class="btn btn-ghost btn-sm">Ver todos</button>
                <span id="pedidos-info" class="muted" style="margin-left:auto;"></span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N&deg; orden</th>
                            <th>Fecha solicitud</th>
                            <th>Paciente</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="lista-pedidos"></tbody>
                </table>
            </div>
        </fieldset>

        <section id="panel-pedido" hidden>
            <fieldset class="form-section">
                <legend>Pedido</legend>
                <div id="info-pedido" class="muted"></div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Items</legend>
                <p class="hint">Carg&aacute; los valores (Tab o Enter para pasar al siguiente) y guard&aacute; todo junto con el bot&oacute;n de abajo.</p>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Determinacion</th>
                                <th>Valor</th>
                                <th>Unidad</th>
                                <th>Referencia</th>
                                <th>Estado</th>
                                <th>Marca</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="lista-items"></tbody>
                    </table>
                </div>
                <div class="form-actions" style="margin-top: 1rem;">
                    <button type="button" id="btn-guardar-todos" class="btn btn-primary">
                        <i class="bi bi-save2"></i> Guardar todos los resultados
                    </button>
                </div>
            </fieldset>
        </section>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_asset_h('/assets/js/resultados/cargar.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
