<?php

declare(strict_types=1);

use App\Helpers\Response;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"success":false,"data":null,"error":{"code":"BOOTSTRAP","message":"Falta vendor/. Ejecutar composer install.","fields":{}}}';
    exit;
}

require $autoload;

Dotenv::createImmutable($root)->safeLoad();

require __DIR__ . '/session_bridge.php';

/** @var array{name:string,env:string,debug:bool,url:string,timezone:string,locale:string,log_path:string} $appConfig */
$appConfig = require $root . '/config/app.php';

date_default_timezone_set($appConfig['timezone']);

$logPath = $appConfig['log_path'];
if (!preg_match('#^(/|[A-Z]:[\\\\/])#i', $logPath)) {
    $logPath = $root . '/' . ltrim($logPath, '/');
}

$logDir = dirname($logPath);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}

$logger = new Logger('app');
try {
    $logger->pushHandler(new StreamHandler($logPath, Level::Debug));
} catch (\Throwable $logHandlerError) {
    $logger->pushHandler(new StreamHandler('php://stderr', Level::Warning));
}

set_exception_handler(static function (\Throwable $e) use ($appConfig, $logger): void {
    try {
        $logger->error($e->getMessage(), [
            'exception' => $e::class,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);
    } catch (\Throwable $logWriteError) {
        // Evitar 500 en cascada si storage/logs no es escribible.
    }

    if ($appConfig['debug']) {
        Response::error($e->getMessage(), 500, [
            'exception' => $e::class,
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);
        return;
    }

    Response::error('Internal server error', 500);
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

return [
    'app'    => $appConfig,
    'logger' => $logger,
    'root'   => $root,
];
