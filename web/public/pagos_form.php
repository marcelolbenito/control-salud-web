<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/PagosController.php';
require_auth();

$user = auth_user();
$pdo = db();
(new PagosController($pdo, $user))->form();

