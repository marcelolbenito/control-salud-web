<?php

$pageTitle = 'Aranceles / NBU';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-cash-stack"></i> Aranceles</p>
                <h1>Aranceles / NBU</h1>
                <p class="lead">Unidades NBU por an&aacute;lisis y valor por obra social. Edici&oacute;n inline; los cambios se guardan al salir del campo.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <nav class="tabs" role="tablist">
            <button class="tab is-active" data-tab="nbu" role="tab" aria-selected="true">NBU por an&aacute;lisis</button>
            <button class="tab" data-tab="os" role="tab" aria-selected="false">Valor por obra social</button>
        </nav>

        <section id="tab-nbu" class="tab-panel" role="tabpanel">
            <fieldset class="form-section">
                <legend>Unidades NBU por an&aacute;lisis</legend>
                <div class="grid">
                    <label>
                        Buscar
                        <input type="search" id="nbu-buscar" placeholder="C&oacute;digo o nombre">
                    </label>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>C&oacute;digo</th>
                                <th>An&aacute;lisis</th>
                                <th>&Aacute;rea</th>
                                <th style="width:140px">Unidades</th>
                                <th style="width:80px">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody id="nbu-tbody"></tbody>
                    </table>
                </div>
            </fieldset>
        </section>

        <section id="tab-os" class="tab-panel" role="tabpanel" hidden>
            <fieldset class="form-section">
                <legend>Valor por unidad NBU por obra social</legend>
                <p class="hint">Cada cambio de valor crea una <strong>vigencia</strong> con fecha desde. Al armar un lote de facturaci&oacute;n, se usa el valor vigente al d&iacute;a del lote.</p>
                <div class="grid">
                    <label>
                        Buscar
                        <input type="search" id="os-buscar" placeholder="Nombre de OS">
                    </label>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Obra social</th>
                                <th style="width:140px">$ / UB (vigente)</th>
                                <th style="width:120px">Desde</th>
                                <th style="width:140px">&Uacute;lt. actualizaci&oacute;n</th>
                                <th style="width:240px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="os-tbody"></tbody>
                    </table>
                </div>
            </fieldset>
        </section>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <div id="os-modal" class="modal-overlay" hidden role="dialog" aria-modal="true">
            <div class="modal">
                <h2 id="os-modal-titulo"></h2>
                <div id="os-modal-cuerpo"></div>
            </div>
        </div>

        <script type="module" src="<?= lab_h('/assets/js/aranceles/index.js') ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
