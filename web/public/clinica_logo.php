<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = db();
$user = auth_user();

if ($user !== null) {
    $sessionClinica = user_clinica_id($user);
    $requested = isset($_GET['c']) ? (int) $_GET['c'] : $sessionClinica;
    $idClinica = $requested > 0 ? $requested : $sessionClinica;
    if (!auth_is_superadmin($user) && $idClinica !== $sessionClinica) {
        http_response_code(403);
        exit('No autorizado');
    }
} else {
    $idClinica = isset($_GET['c']) ? max(1, (int) $_GET['c']) : 0;
    if ($idClinica < 1) {
        http_response_code(404);
        exit('Logo no encontrado');
    }
}

$logoPath = recordatorio_config($pdo, $idClinica, 'clinica.logo_path', '');
$full = $logoPath !== '' ? clinica_logo_full_path($logoPath) : null;
if ($full === null) {
    http_response_code(404);
    exit('Logo no encontrado');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
if ($ext === 'png') {
    $mime = 'image/png';
} elseif (in_array($ext, ['jpg', 'jpeg'], true)) {
    $mime = 'image/jpeg';
} else {
    $mime = 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . (string) filesize($full));
readfile($full);
exit;
