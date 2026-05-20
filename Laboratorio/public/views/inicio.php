<?php

$pageTitle = 'Inicio';
require __DIR__ . '/_layout/header.php';
?>
        <header class="page-head">
            <div>
                <p class="page-eyebrow"><i class="bi bi-house-door"></i> Inicio</p>
                <h1>Modulo de Laboratorio</h1>
                <p class="lead">Gestion de pedidos, resultados e informes.</p>
            </div>
        </header>

        <h2 class="section-title">Flujo del laboratorio</h2>
        <div class="stat-grid">
            <a href="<?= lab_h('/pedidos/nuevo') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-clipboard2-plus"></i></div>
                <div>
                    <div class="stat-card-value">Nuevo pedido</div>
                    <div class="stat-card-label">Cargar una orden de analisis</div>
                </div>
            </a>
            <a href="<?= lab_h('/pedidos') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-list-check"></i></div>
                <div>
                    <div class="stat-card-value">Listado de pedidos</div>
                    <div class="stat-card-label">Buscar y ver detalle de ordenes</div>
                </div>
            </a>
            <a href="<?= lab_h('/resultados/cargar') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-input-cursor-text"></i></div>
                <div>
                    <div class="stat-card-value">Cargar resultados</div>
                    <div class="stat-card-label">Tecnico</div>
                </div>
            </a>
            <a href="<?= lab_h('/informes') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                <div>
                    <div class="stat-card-value">Informes</div>
                    <div class="stat-card-label">Emitir y descargar PDF</div>
                </div>
            </a>
            <a href="<?= lab_h('/historial') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-card-value">Historial</div>
                    <div class="stat-card-label">Buscar pedidos por paciente</div>
                </div>
            </a>
        </div>

        <h2 class="section-title">Administracion</h2>
        <div class="stat-grid">
            <a href="<?= lab_h('/pacientes') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-people"></i></div>
                <div>
                    <div class="stat-card-value">Pacientes</div>
                    <div class="stat-card-label">Buscador de pacientes</div>
                </div>
            </a>
            <a href="<?= lab_h('/reportes') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-graph-up"></i></div>
                <div>
                    <div class="stat-card-value">Reportes</div>
                    <div class="stat-card-label">Recaudacion del mes</div>
                </div>
            </a>
            <a href="<?= lab_h('/aranceles') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-card-value">Aranceles / NBU</div>
                    <div class="stat-card-label">Unidades NBU por an&aacute;lisis y valor por obra social</div>
                </div>
            </a>
            <a href="<?= lab_h('/facturacion-os') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-receipt"></i></div>
                <div>
                    <div class="stat-card-value">Facturacion OS</div>
                    <div class="stat-card-label">Lotes y cobros por obra social</div>
                </div>
            </a>
            <a href="<?= lab_h('/configuracion') ?>" class="stat-card">
                <div class="stat-card-icon"><i class="bi bi-gear"></i></div>
                <div>
                    <div class="stat-card-value">Configuraci&oacute;n del laboratorio</div>
                    <div class="stat-card-label">Datos institucionales del informe PDF</div>
                </div>
            </a>
        </div>
<?php require __DIR__ . '/_layout/footer.php'; ?>
