<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/ListaPreciosController.php';

require_auth();

$user = auth_user();
$pdo = db();
$ctrl = new ListaPreciosController($pdo, $user);

$a = (string) ($_GET['a'] ?? 'index');
if ($a === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl->deletePost();
}

if ($a === 'form') {
    $ctrl->form();
    exit;
}

$ctrl->index();
