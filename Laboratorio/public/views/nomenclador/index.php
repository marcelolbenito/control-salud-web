<?php

$pageTitle = 'Nomenclador';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-card-list"></i> Nomenclador</p>
                <h1>Nomenclador de an&aacute;lisis</h1>
                <p class="lead">Crear y editar determinaciones (ej. un an&aacute;lisis nuevo como COVID) con su unidad NBU.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Determinaciones</legend>
            <div class="form-actions" style="margin-bottom: 1rem; gap: .75rem; flex-wrap: wrap; align-items: flex-end;">
                <button type="button" id="btn-nuevo" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nueva determinaci&oacute;n</button>
                <input type="search" id="buscar" placeholder="Filtrar por nombre o codigo..." autocomplete="off" style="margin-left:auto;">
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:110px">C&oacute;digo</th>
                            <th>Nombre</th>
                            <th>&Aacute;rea</th>
                            <th>Unidad</th>
                            <th>NBU</th>
                            <th style="width:100px"></th>
                        </tr>
                    </thead>
                    <tbody id="det-tbody"></tbody>
                </table>
            </div>
        </fieldset>

        <div class="modal-overlay" id="editor-overlay" hidden>
        <fieldset class="form-section modal-card" id="editor">
            <button type="button" id="btn-cerrar" class="modal-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            <legend id="editor-titulo">Nueva determinaci&oacute;n</legend>
            <input type="hidden" id="f-id">
            <div class="grid">
                <label>
                    &Aacute;rea
                    <select id="f-area" required></select>
                </label>
                <label>
                    C&oacute;digo
                    <input type="text" id="f-codigo" maxlength="20" required placeholder="Ej. COVID">
                </label>
                <label>
                    Nombre
                    <input type="text" id="f-nombre" maxlength="150" required placeholder="Ej. Antigeno COVID-19">
                </label>
                <label>
                    Nombre corto <span class="hint-inline">(opcional)</span>
                    <input type="text" id="f-corto" maxlength="60">
                </label>
                <label>
                    Unidad <span class="hint-inline">(opcional)</span>
                    <input type="text" id="f-unidad" maxlength="20" placeholder="Ej. mg/dL">
                </label>
                <label>
                    M&eacute;todo <span class="hint-inline">(opcional)</span>
                    <input type="text" id="f-metodo" maxlength="100">
                </label>
                <label>
                    Tipo de resultado
                    <select id="f-tipo">
                        <option value="numerico">Num&eacute;rico</option>
                        <option value="texto">Texto</option>
                        <option value="seleccion">Selecci&oacute;n</option>
                    </select>
                </label>
                <label>
                    Decimales
                    <input type="number" id="f-decimales" min="0" max="6" value="2">
                </label>
                <label>
                    Precio particular <span class="hint-inline">(opcional)</span>
                    <input type="number" id="f-precio" min="0" step="0.01" placeholder="$">
                </label>
                <label>
                    NBU (unidades) <span class="hint-inline">(opcional)</span>
                    <input type="number" id="f-nbu" min="0" step="0.01" placeholder="Ej. 5">
                </label>
            </div>

            <label class="check-inline" style="display:flex; align-items:flex-start; gap:.5rem; margin-top:1rem;">
                <input type="checkbox" id="f-solo-facturacion" style="width:auto; margin-top:.2rem;">
                <span>
                    Acto bioqu&iacute;mico / solo facturaci&oacute;n
                    <span class="hint-inline" style="display:block;">Se agrega y cobra autom&aacute;ticamente en todo pedido; no figura como resultado ni en el informe.</span>
                </span>
            </label>

            <div class="form-actions">
                <button type="button" id="btn-guardar" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Guardar</button>
                <button type="button" id="btn-cancelar" class="btn btn-ghost"><i class="bi bi-x-lg"></i> Cancelar</button>
            </div>
        </fieldset>
        </div>

        <div class="modal-overlay" id="rangos-overlay" hidden>
        <fieldset class="form-section modal-card" id="rangos-modal">
            <button type="button" id="rg-cerrar" class="modal-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            <legend id="rg-titulo">Valores de referencia</legend>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:80px">Sexo</th>
                            <th>Edad</th>
                            <th>Rango / Texto</th>
                            <th style="width:120px"></th>
                        </tr>
                    </thead>
                    <tbody id="rg-tbody"></tbody>
                </table>
            </div>

            <div class="form-actions" style="margin:.75rem 0;">
                <button type="button" id="rg-nuevo" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Nuevo rango</button>
            </div>

            <fieldset class="form-section" id="rg-form" hidden style="margin-top:.5rem;">
                <legend id="rg-form-titulo">Nuevo rango</legend>
                <input type="hidden" id="rg-id">
                <div class="grid">
                    <label>
                        Sexo
                        <select id="rg-sexo">
                            <option value="ambos">Ambos</option>
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </label>
                    <label>
                        Edad desde <span class="hint-inline">(vac&iacute;o = sin l&iacute;mite)</span>
                        <span style="display:flex; gap:.3rem;">
                            <input type="number" id="rg-edad-min-anios" min="0" placeholder="a&ntilde;os" style="width:50%;">
                            <input type="number" id="rg-edad-min-meses" min="0" max="11" placeholder="meses" style="width:50%;">
                        </span>
                    </label>
                    <label>
                        Edad hasta <span class="hint-inline">(vac&iacute;o = sin l&iacute;mite)</span>
                        <span style="display:flex; gap:.3rem;">
                            <input type="number" id="rg-edad-max-anios" min="0" placeholder="a&ntilde;os" style="width:50%;">
                            <input type="number" id="rg-edad-max-meses" min="0" max="11" placeholder="meses" style="width:50%;">
                        </span>
                    </label>
                    <label>
                        Valor m&iacute;nimo <span class="hint-inline">(opcional)</span>
                        <input type="number" id="rg-valor-min" step="any" placeholder="Ej. 70">
                    </label>
                    <label>
                        Valor m&aacute;ximo <span class="hint-inline">(opcional)</span>
                        <input type="number" id="rg-valor-max" step="any" placeholder="Ej. 110">
                    </label>
                    <label>
                        Texto de referencia <span class="hint-inline">(ej. "Negativo")</span>
                        <input type="text" id="rg-texto" maxlength="500">
                    </label>
                    <label class="col-span-2">
                        Observaciones <span class="hint-inline">(opcional)</span>
                        <input type="text" id="rg-obs" maxlength="500">
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" id="rg-guardar" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Guardar rango</button>
                    <button type="button" id="rg-cancelar" class="btn btn-ghost"><i class="bi bi-x-lg"></i> Cancelar</button>
                </div>
            </fieldset>
        </fieldset>
        </div>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <script type="module" src="<?= lab_asset_h('/assets/js/nomenclador/index.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
