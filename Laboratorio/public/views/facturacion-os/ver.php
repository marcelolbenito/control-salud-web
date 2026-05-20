<?php

$pageTitle = 'Detalle de lote';
require __DIR__ . '/../_layout/header.php';

$loteId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-receipt"></i> Facturacion OS</p>
                <h1 id="lote-titulo">Lote</h1>
                <p id="lote-meta" class="lead"></p>
            </div>
            <a href="<?= lab_h('/facturacion-os') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al listado</a>
        </header>

        <div class="actions" id="lote-acciones"></div>

        <fieldset class="form-section">
            <legend>Pedidos del lote</legend>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>HC</th>
                            <th>Paciente</th>
                            <th>Monto</th>
                            <th>Acci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody id="pedidos-tbody"></tbody>
                </table>
            </div>
        </fieldset>

        <div id="modal-cobrar" class="modal" hidden>
            <div class="modal-content">
                <h2>Registrar cobro</h2>
                <form id="form-cobrar">
                    <label>Fecha de pago <input type="date" name="fecha_pago" required></label>
                    <label>Medio de pago
                        <select name="medio_pago" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cheque">Cheque</option>
                            <option value="otro">Otro</option>
                        </select>
                    </label>
                    <label>Referencia <input type="text" name="referencia" maxlength="100"></label>
                    <label>Observaciones <input type="text" name="observaciones"></label>
                    <p class="warning">Esta acci&oacute;n es irreversible.</p>
                    <button type="button" class="btn" data-cerrar>Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar cobro</button>
                </form>
            </div>
        </div>

        <div id="modal-anular" class="modal" hidden>
            <div class="modal-content">
                <h2>Anular lote</h2>
                <form id="form-anular">
                    <label>Motivo <input type="text" name="motivo" maxlength="500" required></label>
                    <p>Los pedidos volver&aacute;n a estar disponibles para incluirse en otro lote.</p>
                    <button type="button" class="btn" data-cerrar>Cancelar</button>
                    <button type="submit" class="btn btn-danger">Anular</button>
                </form>
            </div>
        </div>

        <div id="mensaje" class="mensaje" role="status" aria-live="polite" hidden></div>

        <style>
            .btn-toggle { background:none; border:1px solid #ccc; padding:2px 6px; cursor:pointer; border-radius:3px; margin-right:6px; }
            .items-row td { background:#f7f9fb; padding:12px 16px; }
            .items-cont .table-sm { font-size:13px; }
            .items-cont .table-sm th, .items-cont .table-sm td { padding:4px 8px; }
            .item-excluido { opacity:0.55; }
            .item-excluido .tachado { text-decoration:line-through; color:#b34f00; }
            .num { text-align:right; font-variant-numeric:tabular-nums; }
            .chk-cubre { width:18px; height:18px; cursor:pointer; }
        </style>

        <script type="module">
          import { initVerLote } from '<?= lab_h('/assets/js/facturacion-os/ver-lote.js') ?>';
          initVerLote(<?= (int) $loteId ?>);
        </script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
