<?php

declare(strict_types=1);

use App\Integration\ControlSaludIntegration;

/**
 * Misma sesión PHP que Control Salud Web (mismo host / cookie).
 * No hace login; solo expone usuario_id para auditoría del lab.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!ControlSaludIntegration::enabled()) {
    return;
}

$user = $_SESSION['user'] ?? null;
if (is_array($user) && isset($user['id'])) {
    $_SESSION['usuario_id'] = (int) $user['id'];
    $cid = (int) ($user['id_clinica'] ?? 1);
    $_SESSION['lab_id_clinica'] = $cid > 0 ? $cid : 1;
}
