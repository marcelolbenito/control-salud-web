<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/FacturacionOrdenesController.php';
require_auth();

$user = auth_user();
$pdo = db();
$ctrl = new FacturacionOrdenesController($pdo, $user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? 'marcar');
    if ($accion === 'actualizar_costos') {
        $ctrl->actualizarCostos();
    } else {
        $ctrl->marcar();
    }
}

$ctrl->index();
