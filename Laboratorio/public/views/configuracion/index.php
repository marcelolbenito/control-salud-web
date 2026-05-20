<?php

$pageTitle = 'Configuración del laboratorio';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-gear"></i> Administración</p>
                <h1>Configuración del laboratorio</h1>
                <p class="lead">Datos institucionales que aparecen en los informes PDF.</p>
            </div>
        </header>

        <form id="form-config" enctype="multipart/form-data" class="config-form">
            <div id="config-msg" hidden></div>

            <fieldset>
                <legend>Identidad</legend>
                <div class="config-grid">
                    <label>Nombre
                        <input type="text" name="laboratorio_nombre" required>
                    </label>
                    <label>Subtítulo
                        <input type="text" name="laboratorio_subtitulo" required>
                    </label>
                    <label>Dirección
                        <input type="text" name="laboratorio_direccion" required>
                    </label>
                    <label>Teléfono / WhatsApp
                        <input type="text" name="laboratorio_telefono" required>
                    </label>
                    <label class="col-2">Logo (PNG/JPG)
                        <input type="file" name="logo" accept="image/png,image/jpeg">
                        <span id="logo-actual" class="config-hint"></span>
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Autorizaciones</legend>
                <div class="config-grid">
                    <label class="col-2">Resolución del Colegio
                        <input type="text" name="laboratorio_resolucion_colegio" required>
                    </label>
                    <label>Vencimiento
                        <input type="text" name="laboratorio_resolucion_vencimiento" required>
                    </label>
                    <label>Código REFE / SISA
                        <input type="text" name="laboratorio_registro_sisa_codigo" required>
                    </label>
                    <label class="col-2">Razón social SISA
                        <input type="text" name="laboratorio_registro_sisa_razon_social" required>
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Técnico de laboratorio</legend>
                <div class="config-grid">
                    <label>Nombre
                        <input type="text" name="tecnico_principal_nombre" required>
                    </label>
                    <label>Título
                        <input type="text" name="tecnico_principal_titulo" required>
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>Firmante (responsable de informes)</legend>
                <div class="config-grid">
                    <label>Apellido
                        <input type="text" name="firmante_apellido" required>
                    </label>
                    <label>Nombres
                        <input type="text" name="firmante_nombres" required>
                    </label>
                    <label>Matrícula profesional
                        <input type="text" name="firmante_matricula" required>
                    </label>
                    <label>Título
                        <input type="text" name="firmante_titulo" required placeholder="Bioquímica / Doctor en Bioquímica">
                    </label>
                    <label class="col-2">Firma escaneada (PNG/JPG)
                        <input type="file" name="firma" accept="image/png,image/jpeg">
                        <span id="firma-actual" class="config-hint"></span>
                    </label>
                </div>
            </fieldset>

            <div class="config-actions">
                <button type="submit" class="ord-btn ord-btn--primary">Guardar cambios</button>
            </div>
        </form>

        <style>
            .config-form { max-width: 900px; margin: 0 auto; padding: 0 16px 32px; }
            .config-form fieldset { background: #fff; border: 1px solid #d6dde6; border-radius: 6px; padding: 16px 20px; margin-bottom: 16px; }
            .config-form legend { padding: 0 8px; font-weight: bold; color: #1565c0; }
            .config-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 18px; }
            .config-grid label { display: flex; flex-direction: column; font-size: 13px; color: #555; gap: 4px; }
            .config-grid label.col-2 { grid-column: 1 / -1; }
            .config-grid input { padding: 6px 8px; font-size: 14px; border: 1px solid #aaa; border-radius: 3px; }
            .config-hint { font-size: 11px; color: #888; }
            .config-actions { text-align: right; padding-top: 8px; }
            #config-msg { padding: 8px 12px; border-radius: 3px; margin-bottom: 12px; }
            #config-msg.ok { background: #e6f5e6; color: #2e7d32; }
            #config-msg.error { background: #fdecea; color: #b42318; }
        </style>

        <script type="module" src="<?= lab_h('/assets/js/configuracion/index.js') ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
