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

        <section id="panel-pedido" hidden>
            <fieldset class="form-section">
                <legend>Pedido</legend>
                <div id="info-pedido" class="muted"></div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Agregar análisis al pedido</legend>
                <div class="buscador-pedido">
                    <label class="busqueda-rapida">
                        Buscar determinación o perfil
                        <input type="search" id="cargar-add" autocomplete="off"
                               placeholder="Nombre o código… (Enter agrega el primero)">
                    </label>
                    <ul id="cargar-add-resultados" class="dropdown-resultados" hidden></ul>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Items</legend>
                <p class="hint">Cargá los valores (Tab o Enter para pasar al siguiente) y guardá todo junto con el botón de abajo. Tocá el nombre de un análisis para ver sus resultados anteriores. Podés sacar un análisis con el botón de cada fila.</p>
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
