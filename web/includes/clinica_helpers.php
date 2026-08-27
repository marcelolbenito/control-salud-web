<?php

declare(strict_types=1);

function clinica_logo_storage_base(): string
{
    return dirname(__DIR__) . '/storage/logos';
}

function clinica_usuario_php_actual(): string
{
    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $info = posix_getpwuid(posix_geteuid());
        if (is_array($info) && !empty($info['name'])) {
            return (string) $info['name'];
        }
    }

    $user = get_current_user();
    return $user !== '' ? $user : 'desconocido';
}

function clinica_mensaje_permiso_escritura(string $ruta): string
{
    $real = realpath($ruta);
    $mostrar = $real !== false ? $real : $ruta;
    $phpUser = clinica_usuario_php_actual();

    return 'Sin permiso de escritura en: ' . $mostrar . '. '
        . 'PHP corre como «' . $phpUser . '». '
        . 'En SSH (desde la raíz de la app): '
        . 'sudo chown -R ' . $phpUser . ':' . $phpUser . ' storage && sudo chmod -R 775 storage. '
        . 'Verificá con: ls -la storage storage/logos';
}

function clinica_asegurar_directorio_logos(int $idClinica): string
{
    $storageRoot = dirname(__DIR__) . '/storage';
    $base = clinica_logo_storage_base();
    if (!is_dir($storageRoot) && !@mkdir($storageRoot, 0775, true) && !is_dir($storageRoot)) {
        throw new RuntimeException(
            'No existe storage/ y PHP no pudo crearla. mkdir -p storage/logos desde la raíz de la app.'
        );
    }
    if (!is_writable($storageRoot)) {
        throw new RuntimeException(clinica_mensaje_permiso_escritura($storageRoot));
    }
    if (!is_dir($base) && !@mkdir($base, 0775, true) && !is_dir($base)) {
        throw new RuntimeException(
            'No existe storage/logos y PHP no pudo crearla. mkdir -p storage/logos && chmod 775 storage storage/logos'
        );
    }
    if (!is_writable($base)) {
        throw new RuntimeException(clinica_mensaje_permiso_escritura($base));
    }
    $destDir = $base . '/clinica_' . max(1, $idClinica);
    if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
        throw new RuntimeException('No se pudo crear la subcarpeta del logo (clinica_' . $idClinica . ').');
    }
    if (!is_writable($destDir)) {
        throw new RuntimeException(clinica_mensaje_permiso_escritura($destDir));
    }

    return $destDir;
}

function clinica_mensaje_error_subida(int $code): string
{
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        return 'El archivo supera el límite de subida del servidor (upload_max_filesize / post_max_size en PHP).';
    }
    if ($code === UPLOAD_ERR_PARTIAL) {
        return 'La subida se interrumpió. Intentá de nuevo.';
    }
    if ($code === UPLOAD_ERR_NO_TMP_DIR || $code === UPLOAD_ERR_CANT_WRITE) {
        return 'El servidor no tiene carpeta temporal para subidas (contactá al administrador).';
    }

    return 'Error al subir el archivo (código ' . $code . ').';
}

function clinica_set_config(PDO $pdo, int $idClinica, string $clave, ?string $valor): void
{
    if (!db_table_exists($pdo, 'config')) {
        return;
    }
    $hasClin = db_table_has_column($pdo, 'config', 'id_clinica');
    if ($hasClin) {
        $st = $pdo->prepare('SELECT id FROM config WHERE clave = ? AND id_clinica = ? LIMIT 1');
        $st->execute([$clave, $idClinica]);
        $existingId = $st->fetchColumn();
        if ($existingId !== false) {
            $upd = $pdo->prepare('UPDATE config SET valor = ? WHERE id = ?');
            $upd->execute([$valor === '' ? null : $valor, (int) $existingId]);

            return;
        }
        $ins = $pdo->prepare('INSERT INTO config (id_clinica, clave, valor) VALUES (?, ?, ?)');
        $ins->execute([$idClinica, $clave, $valor === '' ? null : $valor]);

        return;
    }
    $st = $pdo->prepare('SELECT id FROM config WHERE clave = ? LIMIT 1');
    $st->execute([$clave]);
    $existingId = $st->fetchColumn();
    if ($existingId !== false) {
        $upd = $pdo->prepare('UPDATE config SET valor = ? WHERE id = ?');
        $upd->execute([$valor === '' ? null : $valor, (int) $existingId]);

        return;
    }
    $ins = $pdo->prepare('INSERT INTO config (clave, valor) VALUES (?, ?)');
    $ins->execute([$clave, $valor === '' ? null : $valor]);
}

function clinica_nombre_tabla(PDO $pdo, int $idClinica): string
{
    if (!db_table_exists($pdo, 'clinicas')) {
        return '';
    }
    $st = $pdo->prepare('SELECT nombre FROM clinicas WHERE id = ? LIMIT 1');
    $st->execute([$idClinica]);
    $n = $st->fetchColumn();

    return $n !== false && $n !== null ? trim((string) $n) : '';
}

function clinica_nombre_visible(PDO $pdo, int $idClinica): string
{
    $nombre = recordatorio_config($pdo, $idClinica, 'recordatorios.nombre_clinica', '');
    if ($nombre !== '') {
        return $nombre;
    }
    $enc = recordatorio_config($pdo, $idClinica, 'clinica.encabezado_clinica_texto', '');
    if ($enc !== '') {
        $linea = strtok($enc, "\r\n");
        if (is_string($linea) && trim($linea) !== '') {
            return trim($linea);
        }
    }
    $tabla = clinica_nombre_tabla($pdo, $idClinica);
    if ($tabla !== '') {
        return $tabla;
    }
    $cfg = require dirname(__DIR__) . '/config/config.php';

    return (string) ($cfg['app']['name'] ?? 'Control Salud Web');
}

/**
 * @return array{id_clinica:int,nombre:string,direccion:string,encabezado:string,logo_path:string,logo_url:?string}
 */
function clinica_branding(PDO $pdo, int $idClinica): array
{
    $logoPath = recordatorio_config($pdo, $idClinica, 'clinica.logo_path', '');
    $encabezado = recordatorio_config($pdo, $idClinica, 'clinica.encabezado_clinica_texto', '');
    if ($encabezado === '') {
        $encabezado = recordatorio_config($pdo, $idClinica, 'clinica.encabezado_impresion_texto_plano', '');
    }

    return [
        'id_clinica' => $idClinica,
        'nombre' => clinica_nombre_visible($pdo, $idClinica),
        'direccion' => recordatorio_direccion_clinica($pdo, $idClinica),
        'encabezado' => $encabezado,
        'logo_path' => $logoPath,
        'logo_url' => $logoPath !== '' ? clinica_logo_url($idClinica, $logoPath) : null,
    ];
}

function clinica_logo_url(int $idClinica, string $logoPath = ''): string
{
    $query = 'c=' . $idClinica;
    if ($logoPath !== '') {
        $full = clinica_logo_full_path($logoPath);
        if ($full !== null) {
            $query .= '&v=' . (string) filemtime($full);
        }
    }

    return url('/clinica_logo.php?' . $query);
}

function clinica_logo_full_path(string $relativePath): ?string
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return null;
    }
    $base = realpath(clinica_logo_storage_base());
    if ($base === false) {
        return null;
    }
    $candidate = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $full = realpath($candidate);
    if ($full === false) {
        return null;
    }
    $basePrefix = $base . DIRECTORY_SEPARATOR;
    if (!cs_starts_with($full, $basePrefix) && $full !== $base) {
        return null;
    }

    return is_file($full) ? $full : null;
}

/** Redimensiona el logo guardado para uso en menú e impresiones (máx. 280×80 px). */
function clinica_optimizar_logo_imagen(string $path, int $maxWidth = 420, int $maxHeight = 120): void
{
    if (!function_exists('imagecreatetruecolor')) {
        return;
    }
    $info = @getimagesize($path);
    if ($info === false) {
        return;
    }
    $srcW = (int) ($info[0] ?? 0);
    $srcH = (int) ($info[1] ?? 0);
    if ($srcW < 1 || $srcH < 1) {
        return;
    }
    $scale = min($maxWidth / $srcW, $maxHeight / $srcH, 1.0);
    if ($scale >= 0.999) {
        return;
    }
    $dstW = max(1, (int) round($srcW * $scale));
    $dstH = max(1, (int) round($srcH * $scale));
    $mime = (string) ($info['mime'] ?? '');
    if ($mime === 'image/png') {
        $src = @imagecreatefrompng($path);
    } elseif ($mime === 'image/jpeg') {
        $src = @imagecreatefromjpeg($path);
    } else {
        $src = false;
    }
    if ($src === false) {
        return;
    }
    $dst = imagecreatetruecolor($dstW, $dstH);
    if ($dst === false) {
        imagedestroy($src);

        return;
    }
    if ($mime === 'image/png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
        }
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
    imagedestroy($src);
    $ok = $mime === 'image/png'
        ? imagepng($dst, $path, 6)
        : imagejpeg($dst, $path, 85);
    imagedestroy($dst);
    if (!$ok) {
        return;
    }
}

/**
 * @param array{tmp_name:string,name:string,error:int,size?:int} $file
 */
function clinica_subir_logo(PDO $pdo, int $idClinica, array $file): string
{
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        throw new RuntimeException(clinica_mensaje_error_subida($uploadError));
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('No llegó el archivo al servidor. Verifique upload_max_filesize y post_max_size en PHP.');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > 2 * 1024 * 1024) {
        throw new RuntimeException('El logo debe pesar entre 1 byte y 2 MB.');
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
        throw new RuntimeException('Solo se aceptan imágenes PNG o JPG.');
    }
    $destDir = clinica_asegurar_directorio_logos($idClinica);
    $nombre = 'logo_' . date('Ymd_His') . '.' . $ext;
    $destFull = $destDir . DIRECTORY_SEPARATOR . $nombre;
    if (!@move_uploaded_file($tmp, $destFull)) {
        throw new RuntimeException(
            'No se pudo guardar el logo en disco. '
            . 'Causa habitual en producción: permisos de storage/logos (ver ayuda de despliegue).'
        );
    }
    clinica_optimizar_logo_imagen($destFull);
    $relativo = 'clinica_' . $idClinica . '/' . $nombre;
    clinica_set_config($pdo, $idClinica, 'clinica.logo_path', $relativo);

    return $relativo;
}

function clinica_quitar_logo(PDO $pdo, int $idClinica): void
{
    $path = recordatorio_config($pdo, $idClinica, 'clinica.logo_path', '');
    if ($path !== '') {
        $full = clinica_logo_full_path($path);
        if ($full !== null) {
            @unlink($full);
        }
    }
    clinica_set_config($pdo, $idClinica, 'clinica.logo_path', null);
}

function clinica_render_print_header(PDO $pdo, int $idClinica): string
{
    $branding = clinica_branding($pdo, $idClinica);
    ob_start();
    require dirname(__DIR__) . '/src/Views/_partials/clinica_print_header.php';

    return (string) ob_get_clean();
}
