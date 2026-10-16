<?php
declare(strict_types=1);

$pageTitle = 'Historial';
require __DIR__ . '/../_layout/header.php';
?>
        <link rel="stylesheet" href="<?= lab_asset_h('/assets/js/pacientes/paciente-selector.css') ?>">
        <style>
            /* Tablas finas: filas bajas, sin fechas partidas, acciones compactas */
            .tabla-fina th, .tabla-fina td { padding: 3px 8px; font-size: .85rem; line-height: 1.3; white-space: nowrap; }
            .tabla-fina .badge { font-size: .62rem; padding: 2px 6px; }
            .tabla-fina .btn-sm { padding: 2px 7px; font-size: .78rem; }
            /* Fila de un pedido todavia sin informe: se distingue del resto. */
            .tabla-fina tr.sin-informe td { background: rgba(148, 163, 184, .07); }
            .filtros-barra { align-items: flex-end; gap: .6rem; flex-wrap: wrap; margin-bottom: .75rem; }
        </style>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-clock-history"></i> Historial</p>
                <h1>Historial e informes</h1>
                <p class="lead">Todos los pedidos y sus informes en una sola lista: buscá por paciente, DNI o número.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Buscar</legend>
            <div class="form-actions filtros-barra">
                <label class="busqueda-rapida" style="margin:0; min-width: 300px;">
                    Paciente, DNI o N° <span class="hint-inline">(Enter busca)</span>
                    <input type="search" id="buscar" autocomplete="off" placeholder="Ej. Lopez, 30555555, 61 o I-2026-00033">
                </label>
                <label class="busqueda-rapida" style="margin:0;">
                    Estado
                    <select id="f-estado">
                        <option value="">(todos)</option>
                        <option value="sin_informe">Sin informe</option>
                        <option value="0">Informe pendiente de entrega</option>
                        <option value="1">Informe entregado</option>
                    </select>
                </label>
                <label class="busqueda-rapida" style="margin:0;">
                    Desde
                    <input type="date" id="f-desde">
                </label>
                <label class="busqueda-rapida" style="margin:0;">
                    Hasta
                    <input type="date" id="f-hasta">
                </label>
                <button type="button" id="btn-modal-paciente" class="btn btn-sm"><i class="bi bi-person-lines-fill"></i> Elegir paciente</button>
                <button type="button" id="btn-limpiar" class="btn-ghost btn-sm"><i class="bi bi-x-lg"></i> Limpiar</button>
            </div>
            <div class="buscador-pedido" style="position:relative;">
                <ul id="pac-resultados" class="dropdown-resultados" hidden></ul>
            </div>
            <div class="muted" id="info-total"></div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Pedidos e informes</legend>
            <div class="table-wrap">
                <table class="table tabla-fina">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Pedido</th>
                            <th>Paciente</th>
                            <th>DNI</th>
                            <th>Informe</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="lista"></tbody>
                </table>
            </div>
        </fieldset>

        <section id="dossier-wrap" hidden>
            <fieldset class="form-section">
                <legend>Dossier del pedido</legend>
                <div id="dossier"></div>
            </fieldset>
        </section>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_asset_h('/assets/js/historial/index.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
