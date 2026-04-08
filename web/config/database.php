<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/config.php';
    $d = $cfg['db'];

    // En Docker (servicio webapp): DB_HOST=mysql, DB_PORT=3306
    $host = ($h = getenv('DB_HOST')) !== false && $h !== '' ? $h : $d['host'];
    $port = ($p = getenv('DB_PORT')) !== false && $p !== '' ? (int) $p : (int) $d['port'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $port,
        $d['name'],
        $d['charset']
    );

    $pdo = new PDO($dsn, $d['user'], $d['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
