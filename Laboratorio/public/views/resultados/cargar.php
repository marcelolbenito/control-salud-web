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
                <legend>Items</legend>
                <p class="hint">Tab entre campos. Enter guarda el resultado del item activo.</p>
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
            </fieldset>
        </section>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_h('/assets/js/resultados/cargar.js') ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
