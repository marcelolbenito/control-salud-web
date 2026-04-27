<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/SistemaController.php';

require_auth();

$user = auth_user();
$pdo = db();
$ctrl = new SistemaController($pdo, $user);

$a = (string) ($_GET['a'] ?? 'index');
if ($a === 'config_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl->configDeletePost();
}

if ($a === 'seed_exe_config' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl->seedExeConfigPost();
}

if ($a === 'user_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl->userDeletePost();
}

if ($a === 'config_form') {
    $ctrl->configForm();
    exit;
}

if ($a === 'user_form') {
    $ctrl->userForm();
    exit;
}

$ctrl->index();
