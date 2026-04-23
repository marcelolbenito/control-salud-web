<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/src/Repositories/AgendaBloqueosRepository.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /agenda_bloqueos.php');
    exit;
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    header('Location: /agenda_bloqueos.php');
    exit;
}

$repo = new AgendaBloqueosRepository(db(), user_clinica_id(auth_user()));
$repo->deleteById($id);
flash_set('Bloqueo eliminado.');
header('Location: /agenda_bloqueos.php');
exit;
