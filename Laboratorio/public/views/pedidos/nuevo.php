<?php

$pageTitle = 'Nuevo pedido';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-clipboard2-pulse"></i> Pedidos</p>
                <h1>Nuevo pedido medico</h1>
                <p class="lead">Cargar una nueva orden de analisis para un paciente.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <form id="form-pedido" autocomplete="off" novalidate>
            <fieldset class="form-section">
                <legend>Datos del paciente</legend>
                <p class="hint">Buscá por DNI o nombre en el campo rápido, o usá el buscador completo. Los datos se cargan desde Control Salud.</p>
                <div class="grid">

                    <label class="grid-span-2">
                        Buscar por DNI, HC o nombre
                        <div class="input-with-btn">
                            <input type="search" id="busqueda-paciente" placeholder="Ej: 30123456 o García Juan" autocomplete="off">
                            <button type="button" id="btn-busqueda-paciente" class="btn btn-ghost btn-sm"><i class="bi bi-search"></i> Buscar</button>
                        </div>
                    </label>
                    <div id="busqueda-paciente-resultados" class="grid-span-2 busqueda-paciente-resultados" hidden></div>

                    <div class="paciente-picker grid-span-2" id="picker-paciente">
                        <span class="paciente-picker-label">Paciente seleccionado</span>
                        <input type="hidden" name="paciente_id" id="paciente-id" required>
                        <span id="paciente-label" class="paciente-picker-value">— ninguno —</span>
                        <div class="paciente-picker-actions">
                            <button type="button" id="btn-elegir-paciente" class="btn btn-primary btn-sm">
                                <i class="bi bi-people"></i> Buscador completo
                            </button>
                            <button type="button" id="btn-limpiar-paciente" class="btn btn-ghost btn-sm" disabled>
                                <i class="bi bi-x"></i> Quitar
                            </button>
                        </div>
                    </div>

                    <label>
                        Nombre completo (snapshot)
                        <input type="text" name="snap_nombre" id="snap-nombre" required maxlength="200" readonly>
                    </label>
                    <label>
                        DNI
                        <input type="text" name="snap_dni" id="snap-dni" maxlength="20">
                    </label>
                    <label>
                        Sexo
                        <select name="snap_sexo" id="snap-sexo" required>
                            <option value="">--</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                            <option value="X">No binario / no especificado</option>
                        </select>
                    </label>
                    <label>
                        Fecha de nacimiento
                        <input type="date" name="snap_fecha_nac" id="snap-fecha-nac" required>
                    </label>
                    <label>
                        Obra social
                        <select name="obra_social_id" id="obra-social-select">
                            <option value="">(particular / sin OS)</option>
                        </select>
                    </label>
                    <label>
                        N° de afiliado
                        <input type="text" name="numero_afiliado" id="numero-afiliado" maxlength="50">
                    </label>
                    <label>
                        Fecha de realizado
                        <input type="date" name="fecha_realizado" id="f-fecha-realizado" required>
                    </label>
                </div>
            </fieldset>

            <fieldset class="form-section" id="box-anteriores" hidden>
                <legend>Analisis anteriores del paciente</legend>
                <div class="table-wrap" style="max-height: 220px; overflow-y: auto;">
                    <table class="table" style="font-size: 0.85em;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>N° pedido</th>
                                <th>Items</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="anteriores-items"></tbody>
                    </table>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Items del pedido</legend>
                <p class="hint">Click sobre una determinacion o perfil para agregarla. Los perfiles se expanden automaticamente.</p>

                <div class="items-grid">
                    <div>
                        <h3>Determinaciones</h3>
                        <input type="search" id="search-det" placeholder="Buscar por nombre o codigo..." autocomplete="off">
                        <ul id="lista-det" class="lista" aria-label="Determinaciones disponibles"></ul>
                    </div>
                    <div>
                        <h3>Perfiles</h3>
                        <input type="search" id="search-perfil" placeholder="Buscar por nombre o codigo..." autocomplete="off">
                        <ul id="lista-perfil" class="lista" aria-label="Perfiles disponibles"></ul>
                    </div>
                </div>

                <div class="seleccionados">
                    <h3>Items seleccionados <span id="contador-sel" class="tag">0</span></h3>
                    <ul id="lista-seleccionados" class="lista lista-sel" aria-label="Items seleccionados"></ul>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Observaciones</legend>
                <label>
                    <textarea name="observaciones" rows="3" maxlength="2000" placeholder="Indicaciones, ayuno, medicacion previa, etc."></textarea>
                </label>
            </fieldset>

            <div class="form-actions">
                <button type="submit" id="btn-submit"><i class="bi bi-check2-circle"></i> Crear pedido y cargar resultados</button>
                <button type="reset" id="btn-reset"><i class="bi bi-eraser"></i> Limpiar</button>
            </div>

            <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>
        </form>

        <section id="workspace-pedido" hidden>
            <fieldset class="form-section">
                <legend>Pedido creado</legend>
                <div id="ws-info" class="muted"></div>
                <p class="hint">Cargá los valores (Enter pasa al siguiente) y guardá todo junto. Tocá el nombre de un análisis para ver sus resultados anteriores. Podés seguir agregando o sacando análisis mientras el pedido no esté entregado.</p>
            </fieldset>

            <fieldset class="form-section">
                <legend>Agregar análisis al pedido</legend>
                <div class="buscador-pedido">
                    <label class="busqueda-rapida">
                        Buscar determinación o perfil
                        <input type="search" id="ws-add" autocomplete="off"
                               placeholder="Nombre o código… (Enter agrega el primero)">
                    </label>
                    <ul id="ws-add-resultados" class="dropdown-resultados" hidden></ul>
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Resultados</legend>
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
                        <tbody id="ws-items"></tbody>
                    </table>
                </div>
                <div class="form-actions" style="margin-top: 1rem;">
                    <button type="button" id="ws-guardar" class="btn btn-primary">
                        <i class="bi bi-save2"></i> Guardar todos los resultados
                    </button>
                    <a href="<?= lab_h('/pedidos/nuevo') ?>" class="btn btn-ghost"><i class="bi bi-plus-circle"></i> Nuevo pedido</a>
                </div>
            </fieldset>

            <div id="ws-mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>
        </section>

        <link rel="stylesheet" href="<?= lab_asset_h('/assets/js/pacientes/paciente-selector.css') ?>">
        <script type="module" src="<?= lab_asset_h('/assets/js/pedidos/nuevo.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
