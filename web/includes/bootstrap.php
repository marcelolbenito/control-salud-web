<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Cookies de sesión: SameSite + HttpOnly antes de session_start()
// ---------------------------------------------------------------------------
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');

session_start();

// ---------------------------------------------------------------------------
// Headers de seguridad HTTP (enviados antes de cualquier output HTML)
// ---------------------------------------------------------------------------
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 0');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// ---------------------------------------------------------------------------
// Timeout de sesión por inactividad (30 minutos)
// ---------------------------------------------------------------------------
define('SESSION_TIMEOUT_SECONDS', 1800);
if (isset($_SESSION['user'], $_SESSION['_last_activity'])) {
    if ((time() - (int) $_SESSION['_last_activity']) > SESSION_TIMEOUT_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
if (isset($_SESSION['user'])) {
    $_SESSION['_last_activity'] = time();
}

// ---------------------------------------------------------------------------
// Dependencias del framework
// ---------------------------------------------------------------------------
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/db_schema.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

// Reescribe links root-relative en HTML para despliegues en subcarpeta.
ob_start(static fn (string $buffer): string => rewrite_html_with_base_path($buffer));

// Reescribe cabecera Location cuando apunta a raíz del sitio.
header_register_callback(static function (): void {
    $base = base_path();
    if ($base === '') {
        return;
    }

    foreach (headers_list() as $headerLine) {
        if (!str_starts_with(strtolower($headerLine), 'location:')) {
            continue;
        }

        $location = trim(substr($headerLine, strlen('Location:')));
        if ($location === '' || !str_starts_with($location, '/') || str_starts_with($location, '//') || str_starts_with($location, $base . '/')) {
            continue;
        }

        header_remove('Location');
        header('Location: ' . $base . $location, true);
        break;
    }
});

// ---------------------------------------------------------------------------
// ACL básica por rol (evolutiva)
// ---------------------------------------------------------------------------
if (auth_user() !== null) {
    $role = auth_user_role(auth_user());
    $path = request_path();

    $allowByRole = [
        'superadmin' => ['*'],
        'admin_clinica' => ['*'],
        'doctor' => [
            '/index.php',
            '/agenda.php',
            '/turno_form.php',
            '/turno_eliminar.php',
            '/agenda_slots.php',
            '/agenda_turnos_hora.php',
            '/agenda_proximos_libres.php',
            '/agenda_turno_anular.php',
            '/pacientes.php',
            '/pacientes_lookup.php',
            '/paciente_por_hc.php',
            '/historia_clinica.php',
            '/odontograma.php',
            '/odontograma_imprimir.php',
            '/odontograma_superficies_api.php',
            '/logout.php',
        ],
    ];

    $allowed = $allowByRole[$role] ?? [];
    $isAllowed = in_array('*', $allowed, true) || in_array($path, $allowed, true);
    if (!$isAllowed) {
        http_response_code(403);
        exit('No tenés permisos para esta pantalla.');
    }
}
