<?php

declare(strict_types=1);

$local = __DIR__ . '/config.local.php';

return is_file($local)
    ? require $local
    : require __DIR__ . '/config.example.php';
