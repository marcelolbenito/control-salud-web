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

        <link rel="stylesheet" href="<?= lab_asset_h('/assets/js/pacientes/paciente-selector.css') ?>">
        <script type="module">
            import { PacienteSelector } from '<?= lab_asset_h('/assets/js/pacientes/paciente-selector.js') ?>?v=<?= lab_scripts_version() ?>';

            const esc = (s) => String(s ?? '').replace(/[<>&"']/g, (c) => ({
                '<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'
            }[c]));

            document.getElementById('abrir-selector').addEventListener('click', async () => {
                const p = await PacienteSelector.elegir();
                const out = document.getElementById('resultado');
                if (!p) {
                    out.className = 'muted';
                    out.textContent = 'Cancelado.';
                    return;
                }
                out.className = '';
                out.innerHTML = `
                    <dl class="data-grid">
                        <dt>HC</dt><dd>${esc(p.nro_hc)}</dd>
                        <dt>Paciente</dt><dd><strong>${esc(p.apellido)}, ${esc(p.nombres)}</strong></dd>
                        <dt>DNI</dt><dd>${esc(p.dni)}</dd>
                        <dt>Sexo</dt><dd>${esc(p.sexo)}</dd>
                        <dt>Obra social</dt><dd>${esc(p.obra_social_nombre ?? '(sin)')}</dd>
                        <dt>N&deg; afiliado</dt><dd>${esc(p.nro_afiliado ?? '(sin)')}</dd>
                    </dl>
                `;
            });
        </script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
