<?php

declare(strict_types=1);

function flash_set(string $message): void
{
    $_SESSION['_flash_ok'] = $message;
}

function flash_take(): ?string
{
    if (empty($_SESSION['_flash_ok'])) {
        return null;
    }
    $m = (string) $_SESSION['_flash_ok'];
    unset($_SESSION['_flash_ok']);

    return $m;
}
