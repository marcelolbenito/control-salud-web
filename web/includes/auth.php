<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Auth helpers
// ---------------------------------------------------------------------------

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * id_clinica activo para el usuario logueado (multi-clínica). Sin sesión devuelve 1.
 */
function user_clinica_id(?array $user): int
{
    if ($user === null) {
        return 1;
    }
    $n = (int) ($user['id_clinica'] ?? 1);

    return $n > 0 ? $n : 1;
}

function auth_login(int $id, string $usuario, string $nombre, int $idClinica = 1): void
{
    auth_login_with_role($id, $usuario, $nombre, $idClinica, 'admin_clinica');
}

function auth_login_with_role(
    int $id,
    string $usuario,
    string $nombre,
    int $idClinica = 1,
    string $rol = 'admin_clinica',
    ?int $idDoctor = null
): void
{
    session_regenerate_id(true);
    unset($_SESSION['_csrf']); // regenerar token CSRF tras login
    $cid = $idClinica > 0 ? $idClinica : 1;
    $rol = trim(strtolower($rol));
    if (!in_array($rol, ['superadmin', 'admin_clinica', 'doctor'], true)) {
        $rol = 'admin_clinica';
    }
    $_SESSION['user'] = [
        'id' => $id,
        'usuario' => $usuario,
        'nombre' => $nombre,
        'id_clinica' => $cid,
        'rol' => $rol,
        'id_doctor' => ($idDoctor !== null && $idDoctor > 0) ? $idDoctor : null,
    ];
    $_SESSION['_last_activity'] = time();
}

function auth_user_doctor_id(?array $user): int
{
    if ($user === null) {
        return 0;
    }
    $n = (int) ($user['id_doctor'] ?? 0);

    return $n > 0 ? $n : 0;
}

function auth_user_role(?array $user): string
{
    if ($user === null) {
        return '';
    }
    $rol = strtolower(trim((string) ($user['rol'] ?? 'admin_clinica')));
    if (!in_array($rol, ['superadmin', 'admin_clinica', 'doctor'], true)) {
        return 'admin_clinica';
    }

    return $rol;
}

function auth_is_superadmin(?array $user): bool
{
    return auth_user_role($user) === 'superadmin';
}

/**
 * @param list<string> $roles
 */
function require_roles(array $roles): void
{
    $user = auth_user();
    if ($user === null) {
        header('Location: /login.php');
        exit;
    }
    $actual = auth_user_role($user);
    $norm = [];
    foreach ($roles as $r) {
        $s = strtolower(trim($r));
        if ($s !== '') {
            $norm[] = $s;
        }
    }
    if (!in_array($actual, $norm, true)) {
        http_response_code(403);
        exit('No tenés permisos para esta acción.');
    }
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

// ---------------------------------------------------------------------------
// CSRF helpers
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf'];
}

/**
 * Devuelve un <input type="hidden"> listo para insertar en formularios POST.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifica el token CSRF del POST. Termina con 403 si es inválido.
 */
function csrf_verify(): void
{
    $token  = (string) ($_POST['_csrf'] ?? '');
    $stored = (string) ($_SESSION['_csrf'] ?? '');
    if ($stored === '' || !hash_equals($stored, $token)) {
        http_response_code(403);
        exit('Solicitud inválida.');
    }
}
