<?php

declare(strict_types=1);

/**
 * Copiá este archivo como config.local.php y ajustá usuario/clave de MySQL.
 * La base debe existir y tener importado ../sql/schema_mysql.sql
 *
 * Con Docker (mysql del docker-compose en la raíz del proyecto): host 127.0.0.1,
 * user root, pass la de MYSQL_ROOT_PASSWORD del .env (ej. salud_root_dev).
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'control_salud',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Control Salud Web',
        // Dejar vacío para raíz (local). Para subcarpeta usar, por ejemplo: '/controlsalud'
        'base_path' => '',
    ],
];
