<?php

$pageTitle = 'Pedidos';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-clipboard2-pulse"></i> Pedidos</p>
                <h1>Listado de pedidos</h1>
                <p class="lead">Buscar ordenes con filtros, ver detalle y generar planilla de trabajo.</p>
            </div>
            <div class="form-actions">
                <a href="<?= lab_h('/pedidos/nuevo') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nuevo pedido</a>
                <a href="#" id="btn-abrir-planilla" class="btn btn-ghost"><i class="bi bi-printer"></i> Generar planilla</a>
                <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Inicio</a>
            </div>
        </header>

        <div id="planilla-overlay" class="modal-overlay" hidden>
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="planilla-title">
                <h2 id="planilla-title">Planilla de trabajo por perfil</h2>
                <p class="hint">
                    Genera un PDF con las ordenes <strong>actualmente listadas</strong> en pantalla,
                    con una columna por cada determinacion del perfil seleccionado. El tecnico anota los valores y los carga despues.
                </p>
                <label>
                    Perfil
                    <select id="planilla-perfil">
                        <option value="">-- elegir perfil --</option>
                    </select>
                </label>
                <div id="planilla-info" class="hint" style="margin-top: 0.5rem;"></div>
                <div class="form-actions" style="justify-content: flex-end;">
                    <button type="button" id="btn-cancelar-planilla" class="btn btn-ghost">Cancelar</button>
                    <button type="button" id="btn-generar-planilla" class="btn btn-primary" disabled>
                        <i class="bi bi-file-earmark-pdf"></i> Generar PDF
                    </button>
                </div>
            </div>
        </div>

        <fieldset class="form-section">
            <legend>Filtros</legend>
            <form id="ord-form" class="grid" autocomplete="off">
                <label>
                    Estado
                    <select id="f-estado" name="estado">
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
                    Prioridad
                    <select id="f-prioridad" name="prioridad">
                        <option value="">(todas)</option>
                        <option value="rutina">Rutina</option>
                        <option value="urgente">Urgente</option>
                        <option value="guardia">Guardia</option>
                    </select>
                </label>

                <label>
                    N&deg; orden
                    <input id="f-numero" name="numero" type="text" placeholder="P-2026-...">
                </label>

                <div class="paciente-picker" id="f-paciente-picker">
                    <span class="paciente-picker-label">Paciente</span>
                    <input type="hidden" name="paciente_id" id="f-paciente-id">
                    <span id="f-paciente-label" class="paciente-picker-value">-- ninguno --</span>
                    <div class="paciente-picker-actions">
                        <button type="button" id="btn-elegir-paciente" class="btn btn-ghost btn-sm">
                            <i class="bi bi-search"></i> Elegir
                        </button>
                        <button type="button" id="btn-limpiar-paciente" class="btn btn-ghost btn-sm" disabled>
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>

                <label>
                    Medico (lista)
                    <select id="f-medico-id" name="medico_id">
                        <option value="">(todos)</option>
                    </select>
                </label>

                <label>
                    Medico (texto libre)
                    <input id="f-medico" name="medico" type="text" placeholder="busca en medico_externo">
                </label>

                <label>
                    Obra social
                    <select id="f-obra" name="obra_social_id">
                        <option value="">(todas)</option>
                    </select>
                </label>

                <label>
                    F. solicitud desde
                    <input id="f-fsd" name="fecha_solicitud_desde" type="date">
                </label>
                <label>
                    F. solicitud hasta
                    <input id="f-fsh" name="fecha_solicitud_hasta" type="date">
                </label>
                <label>
                    F. entrega desde
                    <input id="f-fed" name="fecha_entrega_desde" type="date">
                </label>
                <label>
                    F. entrega hasta
                    <input id="f-feh" name="fecha_entrega_hasta" type="date">
                </label>

                <div class="check-row">
                    <label class="check-inline">
                        <input type="checkbox" name="solo_criticos" value="1"> Solo criticos
                    </label>
                    <label class="check-inline">
                        <input type="checkbox" name="incluir_anulados" value="1"> Incluir anulados
                    </label>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Buscar</button>
                    <button type="button" id="btn-limpiar" class="btn btn-ghost"><i class="bi bi-eraser"></i> Limpiar</button>
                </div>
            </form>
        </fieldset>

        <section aria-live="polite">
            <div class="list-meta">
                <span id="ord-info" class="muted">-- sin busqueda --</span>
                <span id="ord-banner-slot"></span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th data-sort="numero_desc">N&deg; orden</th>
                            <th data-sort="fecha_solicitud_desc">F. solicitud</th>
                            <th>HC</th>
                            <th>Paciente</th>
                            <th>Medico</th>
                            <th>Obra social</th>
                            <th data-sort="estado_asc">Estado</th>
                            <th data-sort="prioridad_asc">Prioridad</th>
                            <th><i class="bi bi-exclamation-triangle"></i></th>
                        </tr>
                    </thead>
                    <tbody id="ord-tbody">
                        <tr><td colspan="9" class="muted" style="text-align:center; padding: 2rem;">Aplica filtros y presiona Buscar.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="form-actions" style="justify-content: center; margin-top: 1rem;">
                <button id="btn-prev" class="btn btn-ghost btn-sm" disabled><i class="bi bi-chevron-left"></i> Anterior</button>
                <span id="paginacion-info" class="muted">Pagina 0 de 0</span>
                <button id="btn-next" class="btn btn-ghost btn-sm" disabled>Siguiente <i class="bi bi-chevron-right"></i></button>
            </div>
        </section>

        <link rel="stylesheet" href="<?= lab_asset_h('/assets/js/pacientes/paciente-selector.css') ?>?v=<?= lab_scripts_version() ?>">
        <script type="module" src="<?= lab_asset_h('/assets/js/pedidos/listado.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
