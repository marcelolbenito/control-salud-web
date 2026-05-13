<?php

declare(strict_types=1);
ini_set('display_errors', '1');
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/AnunciadorController.php';
require_auth();

$user = auth_user();
$pdo = db();
$ctrl = new AnunciadorController($pdo, $user);
$a = (string) ($_GET['a'] ?? 'index');
if ($a === 'estado' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl->cambiarEstadoPost();
}

$ctrl->index();

