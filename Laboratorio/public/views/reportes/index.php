<?php

$pageTitle = 'Reportes';
require __DIR__ . '/../_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-graph-up"></i> Reportes</p>
                <h1>Recaudacion del laboratorio</h1>
                <p class="lead">Lo recaudado por mes, con desglose entre pagos particulares y obras sociales.</p>
            </div>
            <a href="<?= lab_h('/') ?>" class="link-back"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
        </header>

        <fieldset class="form-section">
            <legend>Mes seleccionado</legend>
            <form id="rep-form" class="grid">
                <label>
                    Mes
                    <input type="month" id="rep-mes" name="mes">
                </label>
                <button type="submit" class="grid-btn btn btn-primary">
                    <i class="bi bi-search"></i> Ver mes
                </button>
                <button type="button" id="rep-btn-actual" class="grid-btn btn btn-ghost">
                    <i class="bi bi-calendar-event"></i> Mes actual
                </button>
            </form>
        </fieldset>

        <h2 class="section-title" id="rep-titulo-mes">Recaudacion del mes</h2>
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-card-icon" style="background: rgba(13, 148, 136, 0.18); color: var(--accent-dark);">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="stat-card-value" id="rep-total">$ 0,00</div>
                    <div class="stat-card-label">Total recaudado</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" style="background: rgba(37, 99, 235, 0.14); color: #2563eb;">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <div class="stat-card-value" id="rep-particular">$ 0,00</div>
                    <div class="stat-card-label">Particular (pacientes)</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" style="background: rgba(99, 102, 241, 0.14); color: #4f46e5;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <div class="stat-card-value" id="rep-obra-social">$ 0,00</div>
                    <div class="stat-card-label">Obras sociales</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-icon" style="background: rgba(217, 119, 6, 0.14); color: #c2410c;">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div class="stat-card-value" id="rep-cantidad">0</div>
                    <div class="stat-card-label">Cantidad de pagos</div>
                </div>
            </div>
        </div>

        <h2 class="section-title">Ultimos 12 meses</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th style="text-align: right;">Particular</th>
                        <th style="text-align: right;">Obra social</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Pagos</th>
                    </tr>
                </thead>
                <tbody id="rep-tbody-serie">
                    <tr><td colspan="5" class="muted" style="text-align:center; padding: 1.5rem;">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <script type="module" src="<?= lab_h('/assets/js/reportes/index.js') ?>"></script>
<?php require __DIR__ . '/../_layout/footer.php'; ?>
