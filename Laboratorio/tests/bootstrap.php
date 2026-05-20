<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Permite que PHPUnit mockee clases declaradas como final (Repositories, Services).
// Solo afecta a la suite de tests; en runtime la palabra final sigue vigente.
\DG\BypassFinals::enable();

if (file_exists(__DIR__ . '/../.env.testing')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.testing')->load();
} elseif (file_exists(__DIR__ . '/../.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}
