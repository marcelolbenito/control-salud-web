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
                </div>
            </fieldset>

            <fieldset class="form-section">
                <legend>Datos clinicos</legend>
                <div class="grid">
                    <label>
                        Medico (texto libre)
                        <input type="text" name="medico_externo" maxlength="150">
                    </label>
                    <label>
                        Diagnostico
                        <input type="text" name="diagnostico" maxlength="500">
                    </label>
                    <label>
                        Prioridad
                        <select name="prioridad">
                            <option value="rutina" selected>Rutina</option>
                            <option value="urgente">Urgente</option>
                            <option value="guardia">Guardia</option>
                        </select>
                    </label>
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
                <button type="submit" id="btn-submit"><i class="bi bi-check2-circle"></i> Crear pedido</button>
                <button type="reset" id="btn-reset"><i class="bi bi-eraser"></i> Limpiar</button>
            </div>

            <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>
        </form>

        <link rel="stylesheet" href="<?= lab_asset_h('/assets/js/pacientes/paciente-selector.css') ?>">
        <script type="module" src="<?= lab_asset_h('/assets/js/pedidos/nuevo.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
