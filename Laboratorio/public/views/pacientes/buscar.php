<?php

$pageTitle = 'Pacientes';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-people"></i> Pacientes</p>
                <h1>Buscador de pacientes</h1>
                <p class="lead">Buscar pacientes del sistema clinico mayor por HC, DNI, apellido o nombre.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Seleccionar paciente</legend>
            <p class="hint">Abre el selector: veras los pacientes de Control Salud (primeros 100). Usa filtros o Buscar para acotar.</p>
            <div class="form-actions">
                <button id="abrir-selector" type="button" class="btn btn-primary">
                    <i class="bi bi-search"></i> Buscar paciente
                </button>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Paciente seleccionado</legend>
            <div id="resultado" class="muted">Ningun paciente seleccionado.</div>
        </fieldset>

        <section id="panel-analisis" hidden>
            <fieldset class="form-section">
                <legend>An&aacute;lisis del paciente</legend>
                <p class="hint">Todos los pedidos de este paciente. Entr&aacute; a uno para ver sus resultados.
                    <span id="pedidos-paciente-info" class="muted" style="float:right;"></span>
                </p>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>N&deg; orden</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Items</th>
                                <th>Prioridad</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="pedidos-paciente"></tbody>
                    </table>
                </div>
            </fieldset>
        </section>

        <link rel="stylesheet" href="<?= lab_asset_h('/assets/js/pacientes/paciente-selector.css') ?>?v=<?= lab_scripts_version() ?>">
        <script type="module" src="<?= lab_asset_h('/assets/js/pacientes/ficha.js') ?>?v=<?= lab_scripts_version() ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
