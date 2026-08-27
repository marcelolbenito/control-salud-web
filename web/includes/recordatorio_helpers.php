<?php

declare(strict_types=1);

function recordatorio_config(PDO $pdo, int $idClinica, string $clave, string $default = ''): string
{
    if (!db_table_exists($pdo, 'config')) {
        return $default;
    }
    $sql = 'SELECT valor FROM config WHERE clave = ?';
    $params = [$clave];
    if (db_table_has_column($pdo, 'config', 'id_clinica')) {
        $sql .= ' AND id_clinica = ?';
        $params[] = $idClinica;
    }
    $sql .= ' LIMIT 1';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();

    return $v !== false && $v !== null && trim((string) $v) !== '' ? trim((string) $v) : $default;
}

function recordatorio_habilitado(PDO $pdo, int $idClinica): bool
{
    return recordatorio_config($pdo, $idClinica, 'recordatorios.enabled', '1') === '1';
}

function recordatorio_auto_confirmar(PDO $pdo, int $idClinica): bool
{
    if (!recordatorio_habilitado($pdo, $idClinica)) {
        return false;
    }
    $v = recordatorio_config($pdo, $idClinica, 'recordatorios.auto_confirmar', '');
    if ($v !== '') {
        return $v === '1';
    }

    return recordatorio_config($pdo, $idClinica, 'exe.flag.auto_confirmar_whatsapp', '1') === '1';
}

function recordatorio_nombre_clinica(PDO $pdo, int $idClinica): string
{
    return recordatorio_config($pdo, $idClinica, 'recordatorios.nombre_clinica', 'CENTRO PRIVADO SALUD');
}

function recordatorio_direccion_clinica(PDO $pdo, int $idClinica): string
{
    return recordatorio_config($pdo, $idClinica, 'recordatorios.direccion_clinica', '12 de Octubre 158 - Unquillo');
}

function recordatorio_aviso_anulacion(PDO $pdo, int $idClinica): bool
{
    return recordatorio_config($pdo, $idClinica, 'recordatorios.aviso_anulacion', '1') === '1';
}

/** @return array<string, string> */
function recordatorio_vars_desde_turno(
    array $turno,
    array $paciente,
    string $doctorNombre,
    ?PDO $pdo = null,
    int $idClinica = 1
): array {
    $fecha = (string) ($turno['Fecha'] ?? '');
    $hora = !empty($turno['hora']) ? substr((string) $turno['hora'], 0, 5) : '';
    $pacienteNombre = trim((string) ($turno['paciente_nombre'] ?? ''));
    if ($pacienteNombre === '') {
        $ap = trim((string) ($paciente['apellido'] ?? $paciente['Apellido'] ?? ''));
        $no = trim((string) ($paciente['Nombres'] ?? $paciente['nombres'] ?? ''));
        $pacienteNombre = trim($ap . ($ap !== '' && $no !== '' ? ', ' : '') . $no);
    }

    $nombreClinica = 'CENTRO PRIVADO SALUD';
    $direccionClinica = '12 de Octubre 158 - Unquillo';
    if ($pdo !== null) {
        $nombreClinica = recordatorio_nombre_clinica($pdo, $idClinica);
        $direccionClinica = recordatorio_direccion_clinica($pdo, $idClinica);
    }

    return [
        'paciente' => $pacienteNombre,
        'fecha' => $fecha !== '' ? date('d/m/Y', strtotime($fecha)) : '',
        'hora' => $hora,
        'medico' => $doctorNombre,
        'dni' => trim((string) ($paciente['DNI'] ?? $paciente['dni'] ?? '')),
        'NOMBRE_CLINICA' => $nombreClinica,
        'DIRECCION_CLINICA' => $direccionClinica,
    ];
}

function recordatorio_render_plantilla(string $plantilla, array $vars): string
{
    $out = $plantilla;
    foreach ($vars as $k => $v) {
        $out = str_replace('<' . $k . '>', $v, $out);
        $out = str_replace('*<' . $k . '>*', $v, $out);
    }
    $out = str_replace(["\r\n", "\r"], "\n", $out);
    $out = preg_replace("/\*([^*<]+)\*/", '$1', $out) ?? $out;
    $out = preg_replace("/\n{3,}/", "\n\n", $out) ?? $out;

    return trim($out);
}

function recordatorio_normalizar_telefono(?string $tel): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $tel);
    if ($digits === null || $digits === '') {
        return null;
    }
    if (str_starts_with($digits, '549') && strlen($digits) >= 12) {
        return $digits;
    }
    if (str_starts_with($digits, '54') && strlen($digits) >= 11) {
        return $digits;
    }
    if (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }
    if (str_starts_with($digits, '15') && strlen($digits) >= 10) {
        $digits = '549' . substr($digits, 2);
    } elseif (strlen($digits) >= 10) {
        $digits = '549' . $digits;
    }
    if (strlen($digits) < 12) {
        return null;
    }

    return $digits;
}

function recordatorio_wa_me_url(string $telefonoDigits, string $mensaje): string
{
    return 'https://wa.me/' . $telefonoDigits . '?text=' . rawurlencode($mensaje);
}

function recordatorio_tipo_label(string $tipo): string
{
    if ($tipo === 'confirmacion') {
        return 'Confirmación';
    }
    if ($tipo === 'anulacion') {
        return 'Anulación';
    }

    return 'Recordatorio';
}

function recordatorio_modo(PDO $pdo, int $idClinica): string
{
    return recordatorio_config($pdo, $idClinica, 'recordatorios.modo', 'manual');
}

function recordatorio_set_config(PDO $pdo, int $idClinica, string $clave, string $valor): bool
{
    if (!db_table_exists($pdo, 'config')) {
        return false;
    }
    $hasClin = db_table_has_column($pdo, 'config', 'id_clinica');
    if ($hasClin) {
        $st = $pdo->prepare(
            'INSERT INTO config (id_clinica, clave, valor) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );

        return $st->execute([$idClinica, $clave, $valor]);
    }
    $st = $pdo->prepare(
        'INSERT INTO config (clave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
    );

    return $st->execute([$clave, $valor]);
}

/** @param array<string, mixed> $cfg */
function recordatorio_whatsapp_web_habilitado(array $cfg, PDO $pdo, int $idClinica): bool
{
    $w = $cfg['whatsapp_web'] ?? [];
    $enabled = $w['enabled'] ?? false;
    if ($enabled !== true && $enabled !== 1 && (string) $enabled !== '1') {
        return false;
    }
    $modo = recordatorio_modo($pdo, $idClinica);

    return in_array($modo, ['whatsapp_web', 'waha'], true);
}

/** @param array<string, mixed> $cfg */
function recordatorio_gesis_configurado(array $cfg): bool
{
    if (!function_exists('gesis_whatsapp_configured')) {
        require_once dirname(__DIR__) . '/includes/gesis_whatsapp_helpers.php';
    }

    return gesis_whatsapp_configured();
}

/** @param array<string, mixed> $cfg */
function recordatorio_gesis_habilitado(array $cfg, PDO $pdo, int $idClinica): bool
{
    if (!function_exists('gesis_whatsapp_configured')) {
        require_once dirname(__DIR__) . '/includes/gesis_whatsapp_helpers.php';
    }
    if (!gesis_whatsapp_configured()) {
        return false;
    }

    return recordatorio_modo($pdo, $idClinica) === 'gesis';
}

/** HC permitido para envío API en modo prueba (0 = todos). @param array<string, mixed> $cfg */
function recordatorio_gesis_test_only_nro_hc(array $cfg): int
{
    if (function_exists('gesis_whatsapp_test_only_nro_hc')) {
        return gesis_whatsapp_test_only_nro_hc();
    }
    $g = $cfg['gesis_whatsapp'] ?? [];

    return max(0, (int) ($g['test_only_nro_hc'] ?? 0));
}

/** @param array<string, mixed> $cfg */
function recordatorio_gesis_puede_enviar_api(array $cfg, int $nroHc): bool
{
    $solo = recordatorio_gesis_test_only_nro_hc($cfg);
    if ($solo < 1) {
        return true;
    }

    return $nroHc === $solo;
}

function recordatorio_estado_label(string $estado): string
{
    if ($estado === 'listo') {
        return 'Listo (WhatsApp)';
    }
    if ($estado === 'enviado') {
        return 'Enviado';
    }
    if ($estado === 'error') {
        return 'Error';
    }
    if ($estado === 'confirmado') {
        return 'Confirmado';
    }
    if ($estado === 'cancelado') {
        return 'Cancelado';
    }

    return 'Pendiente';
}
