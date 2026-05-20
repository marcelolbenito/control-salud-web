<?php

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pageTitle = 'Detalle de pedido';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-clipboard2-check"></i> Pedidos</p>
                <h1>Detalle del pedido</h1>
            </div>
            <div class="form-actions">
                <a href="<?= lab_h('/pedidos') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al listado</a>
                <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-house-door"></i> Inicio</a>
            </div>
        </header>

        <div id="ord-loading" class="muted">Cargando...</div>
        <div id="ord-error" class="alert alert-error" hidden></div>

        <div id="ord-cuerpo" hidden>
            <fieldset class="form-section">
                <legend>Datos del pedido</legend>
                <dl class="data-grid">
                    <dt>N&deg; orden</dt><dd id="d-numero">--</dd>
                    <dt>Estado</dt><dd id="d-estado">--</dd>
                    <dt>Prioridad</dt><dd id="d-prioridad">--</dd>
                    <dt>Critico</dt><dd id="d-critico">--</dd>
                    <dt>Fecha solicitud</dt><dd id="d-fsol">--</dd>
                    <dt>Fecha extraccion</dt><dd id="d-fext">--</dd>
                    <dt>Fecha entrega</dt><dd id="d-fent">--</dd>
                </dl>
            </fieldset>

            <fieldset class="form-section">
                <legend>Paciente</legend>
                <dl class="data-grid">
                    <dt>HC</dt><dd id="d-hc">--</dd>
                    <dt>Nombre</dt><dd id="d-pac-nombre">--</dd>
                    <dt>DNI</dt><dd id="d-pac-dni">--</dd>
                    <dt>Sexo</dt><dd id="d-pac-sexo">--</dd>
                    <dt>Fecha nac.</dt><dd id="d-pac-fnac">--</dd>
                </dl>
                <p><a id="d-link-historial" href="#"><i class="bi bi-clock-history"></i> Ver historial completo del paciente</a></p>
            </fieldset>

            <fieldset class="form-section">
                <legend>Medico / Obra social</legend>
                <dl class="data-grid">
                    <dt>Medico</dt><dd id="d-medico">--</dd>
                    <dt>Obra social</dt><dd id="d-os">--</dd>
                    <dt>N&deg; afiliado</dt><dd id="d-afiliado">--</dd>
                    <dt>Diagnostico</dt><dd id="d-diag">--</dd>
                </dl>
            </fieldset>

            <fieldset class="form-section">
                <legend>Items (determinaciones / perfiles)</legend>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Determinacion</th>
                                <th>Perfil</th>
                                <th>Unidad</th>
                                <th>Estado</th>
                                <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody id="d-items"></tbody>
                    </table>
                </div>
            </fieldset>

            <fieldset class="form-section" id="ord-acciones">
                <legend>Acciones</legend>
                <div class="form-actions">
                    <button id="btn-portada" class="btn btn-primary"><i class="bi bi-file-earmark-text"></i> Portada</button>
                    <button id="btn-talon" class="btn btn-primary"><i class="bi bi-receipt"></i> Talon</button>
                    <button id="btn-anular" class="btn btn-danger"><i class="bi bi-x-octagon"></i> Anular pedido</button>
                    <button id="btn-eliminar" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar (soft)</button>
                </div>
                <p class="hint">Portada y talon se generan en PDF en pestaña nueva. Anular registra el motivo. Eliminar es soft delete.</p>
            </fieldset>

            <fieldset class="form-section" id="ord-billing">
                <legend>Facturacion</legend>
                <p class="hint">Los pagos los registra el sistema mayor. Aca se muestran los importes calculados.</p>
                <dl class="data-grid">
                    <dt>Estado paciente</dt><dd id="d-estado-pac">--</dd>
                    <dt>Monto paciente</dt><dd id="d-monto-pac">--</dd>
                    <dt>Estado seguro</dt><dd id="d-estado-seg">--</dd>
                    <dt>Monto seguro</dt><dd id="d-monto-seg">--</dd>
                </dl>
            </fieldset>
        </div>

        <script type="module">
            import { initVer } from '<?= lab_h('/assets/js/pedidos/ver.js') ?>';
            initVer(<?= (int) $id ?>);
        </script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
