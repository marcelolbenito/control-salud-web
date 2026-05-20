<?php

$pageTitle = 'Historial';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-clock-history"></i> Historial</p>
                <h1>Historial de pedidos</h1>
                <p class="lead">Buscar pedidos por paciente con filtros de estado y fecha.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Filtros</legend>
            <form id="form-filtros" class="grid">
                <label>
                    ID paciente
                    <input type="number" name="paciente_id" min="1" required inputmode="numeric">
                </label>
                <label>
                    Estado
                    <select name="estado">
                        <option value="">(todos)</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="parcial">Parcial</option>
                        <option value="completo">Completo</option>
                        <option value="entregado">Entregado</option>
                        <option value="anulado">Anulado</option>
                    </select>
                </label>
                <label>
                    Desde
                    <input type="date" name="desde">
                </label>
                <label>
                    Hasta
                    <input type="date" name="hasta">
                </label>
                <button type="submit" class="grid-btn"><i class="bi bi-search"></i> Buscar</button>
            </form>
        </fieldset>

        <section id="resultados-wrap" hidden>
            <div class="muted" id="info-total" style="margin: 0 0 0.6rem;"></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Fecha solicitud</th>
                            <th>Prioridad</th>
                            <th>Items</th>
                            <th>Estado</th>
                            <th>Marca</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="lista-pedidos"></tbody>
                </table>
            </div>
            <div class="form-actions">
                <button type="button" id="btn-prev" class="btn-ghost" disabled><i class="bi bi-chevron-left"></i> Anterior</button>
                <button type="button" id="btn-next" class="btn-ghost" disabled>Siguiente <i class="bi bi-chevron-right"></i></button>
            </div>
        </section>

        <section id="dossier-wrap" hidden>
            <fieldset class="form-section">
                <legend>Dossier del pedido</legend>
                <div id="dossier"></div>
            </fieldset>
        </section>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_h('/assets/js/historial/index.js') ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
