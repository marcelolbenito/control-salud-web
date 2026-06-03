<?php

$pageTitle = 'Perfiles';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-collection"></i> Perfiles</p>
                <h1>Perfiles de an&aacute;lisis</h1>
                <p class="lead">Crear y editar perfiles (conjuntos de determinaciones) que se cargan de una en el pedido.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Perfiles existentes</legend>
            <div class="form-actions" style="margin-bottom: 1rem;">
                <button type="button" id="btn-nuevo" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nuevo perfil</button>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:120px">C&oacute;digo</th>
                            <th>Nombre</th>
                            <th>Determinaciones</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody id="perfiles-tbody"></tbody>
                </table>
            </div>
        </fieldset>

        <fieldset class="form-section" id="editor" hidden>
            <legend id="editor-titulo">Nuevo perfil</legend>
            <input type="hidden" id="f-id">
            <div class="grid">
                <label>
                    C&oacute;digo
                    <input type="text" id="f-codigo" maxlength="20" required placeholder="Ej. HMG">
                </label>
                <label>
                    Nombre
                    <input type="text" id="f-nombre" maxlength="150" required placeholder="Ej. Hemograma completo">
                </label>
                <label style="grid-column: 1 / -1;">
                    Descripci&oacute;n <span class="hint-inline">(opcional)</span>
                    <input type="text" id="f-desc" maxlength="500">
                </label>
            </div>

            <h3>Determinaciones del perfil <span id="sel-count" class="tag">0</span></h3>
            <p class="hint">Click en una determinaci&oacute;n para agregarla; click en una del perfil para quitarla.</p>
            <div class="items-grid">
                <div>
                    <h3>Disponibles</h3>
                    <input type="search" id="buscar-det" placeholder="Buscar por nombre o codigo..." autocomplete="off">
                    <ul id="lista-det" class="lista" aria-label="Determinaciones disponibles"></ul>
                </div>
                <div>
                    <h3>En el perfil</h3>
                    <ul id="lista-sel" class="lista lista-sel" aria-label="Determinaciones del perfil"></ul>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" id="btn-guardar" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Guardar perfil</button>
                <button type="button" id="btn-cancelar" class="btn btn-ghost"><i class="bi bi-x-lg"></i> Cancelar</button>
            </div>
        </fieldset>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_asset_h('/assets/js/perfiles/index.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
