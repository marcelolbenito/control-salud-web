<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/ayuda_contenido.php';

require_auth();

$user = auth_user();
$rol = auth_user_role($user);
$mod = strtolower(trim((string) ($_GET['mod'] ?? 'inicio')));

$modulos = ayuda_filtrar_por_rol(ayuda_modulos(), $rol);
if (!isset($modulos[$mod])) {
    $mod = array_key_first($modulos) ?: 'inicio';
}
$actual = $modulos[$mod] ?? $modulos['inicio'];
if ($mod === 'agenda_web') {
    $cid = user_clinica_id($user);
    $actual['enlaces'] = array_merge(
        [['href' => portal_paciente_url($cid), 'texto' => 'Abrir portal de esta clínica']],
        $actual['enlaces'] ?? []
    );
}
$grupos = ayuda_agrupar_por_categoria($modulos);
$capturas = ayuda_capturas_disponibles($actual['capturas'] ?? []);
$portalPacienteAviso = portal_paciente_render_aviso(user_clinica_id($user));

ob_start();
require dirname(__DIR__) . '/src/Views/ayuda/index.php';
$body = (string) ob_get_clean();

layout_render('Ayuda de uso', $body, $user, ['skip_datatables' => true]);
