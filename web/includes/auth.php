<?php

declare(strict_types=1);

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function auth_login(int $id, string $usuario, string $nombre): void
{
    $_SESSION['user'] = [
        'id' => $id,
        'usuario' => $usuario,
        'nombre' => $nombre,
    ];
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_auth(): void
{
    if (auth_user() === null) {
        header('Location: /login.php');
        exit;
    }
}
