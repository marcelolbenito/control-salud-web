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
                    <input type="text" id="buscar-pedido-id" required maxlength="20" placeholder="Ej. 4 o P-2026-00004">
                </label>
                <button type="submit" class="grid-btn"><i class="bi bi-search"></i> Buscar</button>
            </form>
        </fieldset>

        <fieldset class="form-section">
            <legend>Pedidos listos para informar</legend>
            <p class="hint">Pedidos con resultados cargados (completos o parciales). Elegí uno y emití el informe.</p>
            <div class="form-actions" style="margin-bottom: 1rem; align-items: flex-end; gap: 0.75rem; flex-wrap: wrap;">
                <label class="busqueda-rapida" style="margin:0;">
                    Buscar (nombre o DNI)
                    <input type="search" id="listos-q" autocomplete="off" placeholder="Apellido, nombre o DNI...">
                </label>
                <label class="busqueda-rapida" style="margin:0;">
                    Día (fecha de solicitud)
                    <input type="date" id="listos-dia">
                </label>
                <button type="button" id="listos-limpiar" class="btn btn-ghost btn-sm"><i class="bi bi-x-lg"></i> Limpiar</button>
                <span id="listos-info" class="muted" style="margin-left:auto;"></span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° orden</th>
                            <th>Fecha solicitud</th>
                            <th>Paciente</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="lista-listos"></tbody>
                </table>
            </div>
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
                        Firma el informe
                        <input type="text" id="firmante-info" value="—" readonly tabindex="-1"
                               title="El firmante se configura en el laboratorio" style="background:#f1f3f5; cursor:default;">
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

        <fieldset class="form-section">
            <legend>Últimos informes emitidos</legend>
            <form id="form-filtros-informes" class="grid">
                <label>
                    Paciente
                    <input type="text" name="paciente" maxlength="100" placeholder="Apellido o nombre">
                </label>
                <label>
                    DNI
                    <input type="text" name="dni" maxlength="20" placeholder="40418318">
                </label>
                <label>
                    N° informe / pedido
                    <input type="text" name="numero" maxlength="20" placeholder="I-2026-... o 4">
                </label>
                <label>
                    Estado
                    <select name="entregado">
                        <option value="">(todos)</option>
                        <option value="0">Pendiente</option>
                        <option value="1">Entregado</option>
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
                <div class="form-actions" style="grid-column: 1 / -1;">
                    <button type="submit"><i class="bi bi-search"></i> Filtrar</button>
                    <button type="button" id="btn-limpiar-filtros" class="btn-ghost"><i class="bi bi-x-lg"></i> Limpiar</button>
                </div>
            </form>
            <div class="table-wrap" style="margin-top: 0.75rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N° informe</th>
                            <th>Emisión</th>
                            <th>Pedido</th>
                            <th>Paciente</th>
                            <th>DNI</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="lista-recientes"></tbody>
                </table>
            </div>
        </fieldset>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_asset_h('/assets/js/informes/index.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
