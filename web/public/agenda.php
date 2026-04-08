<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/AgendaController.php';
require_auth();

$user = auth_user();
$pdo = db();
$controller = new AgendaController($pdo, $user);
$controller->index();
