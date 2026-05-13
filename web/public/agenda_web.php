<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Controllers/AgendaWebController.php';

$pdo = db();
(new AgendaWebController($pdo))->index();
