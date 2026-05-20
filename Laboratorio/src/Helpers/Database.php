<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;
use RuntimeException;

/**
 * Singleton de conexion PDO a la base compartida.
 *
 * Uso: Database::connection()->prepare(...);
 */
final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        /** @var array{host:string,port:int,name:string,user:string,pass:string,charset:string,options:array<int,mixed>} $config */
        $config = require __DIR__ . '/../../config/database.php';

        if ($config['name'] === '') {
            throw new RuntimeException('La base de datos no esta configurada. Revisar .env (DB_NAME).');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        self::$connection = new PDO(
            $dsn,
            $config['user'],
            $config['pass'],
            $config['options']
        );

        return self::$connection;
    }

    /**
     * Resetea la conexion. Pensado para tests; no usar en runtime.
     */
    public static function reset(): void
    {
        self::$connection = null;
    }
}
