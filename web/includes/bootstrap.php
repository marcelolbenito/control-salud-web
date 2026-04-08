<?php

declare(strict_types=1);

session_start();

require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/db_schema.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
