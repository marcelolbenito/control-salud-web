<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/NovedadesController.php';

require_auth();

$user = auth_user();
$ctrl = new NovedadesController(db(), $user);
$ctrl->index();
