<?php

declare(strict_types=1);

return [
    'name'     => 'Sistema de Laboratorio Clinico',
    'env'      => $_ENV['APP_ENV'] ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'America/Argentina/Cordoba',
    'locale'   => 'es_AR',
    'log_path' => $_ENV['LOG_PATH'] ?? __DIR__ . '/../storage/logs/app.log',
];
