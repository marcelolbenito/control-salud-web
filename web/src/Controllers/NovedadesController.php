<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/NovedadesRepository.php';

final class NovedadesController
{
    private const MAX_BYTES = 10 * 1024 * 1024;
    /** @var array<string, string> */
    private const MIME_EXT = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private PDO $pdo;
    /** @var array<string, mixed> */
    private array $user;
    private int $idClinica;

    /**
     * @param array<string, mixed> $user
     */
    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->idClinica = user_clinica_id($user);
    }

    public function index(): void
    {
        $repo = new NovedadesRepository($this->pdo, $this->idClinica);
        $hasTable = $repo->tableExists();
        $rows = $hasTable ? $repo->listar() : [];
        $flash = flash_take();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $accion = (string) ($_POST['accion'] ?? 'subir');
            if ($accion === 'eliminar') {
                $this->eliminar($repo);
                return;
            }
            $error = $this->subir($repo);
            if ($error === '') {
                flash_set('Archivo publicado en Info / Novedades.');
                header('Location: /novedades.php');
                exit;
            }
            $rows = $hasTable ? $repo->listar() : [];
        }

        $body = $this->render('novedades/index', [
            'hasTable' => $hasTable,
            'rows' => $rows,
            'flash' => $flash,
            'error' => $error,
            'user' => $this->user,
            'rol' => auth_user_role($this->user),
            'idUsuario' => (int) ($this->user['id'] ?? 0),
        ]);
        layout_render('Info / Novedades', $body, $this->user, ['skip_datatables' => true]);
    }

    public function archivo(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new NovedadesRepository($this->pdo, $this->idClinica);
        $row = $repo->findById($id);
        if ($row === null) {
            http_response_code(404);
            echo 'Archivo no encontrado.';
            exit;
        }
        $full = $this->rutaAbsoluta((string) ($row['ruta_relativa'] ?? ''));
        if ($full === null || !is_file($full)) {
            http_response_code(404);
            echo 'El archivo ya no está en el servidor.';
            exit;
        }
        $mime = trim((string) ($row['mime'] ?? ''));
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }
        $nombre = (string) ($row['nombre_original'] ?? 'archivo');
        $inline = str_starts_with($mime, 'image/') || $mime === 'application/pdf';
        $disp = $inline ? 'inline' : 'attachment';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($full));
        header('Content-Disposition: ' . $disp . '; filename="' . $this->safeFilenameHeader($nombre) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($full);
        exit;
    }

    private function subir(NovedadesRepository $repo): string
    {
        if (!$repo->tableExists()) {
            return 'Falta la tabla novedades_archivos. Ejecutá sql/migration_039_novedades.sql.';
        }
        if (!isset($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
            return 'Elegí un archivo para subir.';
        }
        $fi = $_FILES['archivo'];
        $err = (int) ($fi['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return 'Elegí un archivo para subir.';
        }
        if ($err !== UPLOAD_ERR_OK) {
            return function_exists('clinica_mensaje_error_subida')
                ? clinica_mensaje_error_subida($err)
                : 'No se pudo subir el archivo.';
        }
        $tmp = (string) ($fi['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'Archivo inválido.';
        }
        $size = (int) ($fi['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_BYTES) {
            return 'El archivo debe pesar hasta 10 MB.';
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        if (!isset(self::MIME_EXT[$mime])) {
            return 'Formato no permitido. Usá PDF, JPG, PNG o WebP.';
        }
        $ext = self::MIME_EXT[$mime];
        $dir = $this->directorioClinica();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return 'No se pudo crear la carpeta de novedades en el servidor.';
        }
        $base = 'nov_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $base;
        if (!move_uploaded_file($tmp, $dest)) {
            return 'No se pudo guardar el archivo en disco.';
        }
        $nombreOrig = trim((string) ($fi['name'] ?? 'archivo'));
        if ($nombreOrig === '') {
            $nombreOrig = 'archivo.' . $ext;
        }
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        if ($titulo === '') {
            $titulo = $nombreOrig;
        }
        $desc = trim((string) ($_POST['descripcion'] ?? ''));
        $rel = 'clinica_' . $this->idClinica . '/' . $base;
        $idUsuario = (int) ($this->user['id'] ?? 0);
        $id = $repo->insert(
            mb_substr($titulo, 0, 255),
            $desc !== '' ? mb_substr($desc, 0, 1000) : null,
            mb_substr($nombreOrig, 0, 255),
            $rel,
            $mime,
            $size,
            $idUsuario > 0 ? $idUsuario : null
        );
        if ($id < 1) {
            @unlink($dest);

            return 'No se pudo registrar el archivo en la base.';
        }

        return '';
    }

    private function eliminar(NovedadesRepository $repo): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $row = $repo->findById($id);
        if ($row === null) {
            flash_set('No se encontró el archivo a eliminar.');
            header('Location: /novedades.php');
            exit;
        }
        if (!$this->puedeEliminar($row)) {
            flash_set('No tenés permiso para eliminar ese archivo.');
            header('Location: /novedades.php');
            exit;
        }
        $full = $this->rutaAbsoluta((string) ($row['ruta_relativa'] ?? ''));
        if ($repo->deleteById($id) && $full !== null && is_file($full)) {
            @unlink($full);
        }
        flash_set('Archivo eliminado.');
        header('Location: /novedades.php');
        exit;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function puedeEliminar(array $row): bool
    {
        $rol = auth_user_role($this->user);
        if (in_array($rol, ['superadmin', 'admin_clinica'], true)) {
            return true;
        }
        $uid = (int) ($this->user['id'] ?? 0);
        $owner = (int) ($row['id_usuario'] ?? 0);

        return $uid > 0 && $owner > 0 && $uid === $owner;
    }

    private function directorioClinica(): string
    {
        return dirname(__DIR__, 2) . '/storage/novedades/clinica_' . $this->idClinica;
    }

    private function rutaAbsoluta(string $relativa): ?string
    {
        $relativa = str_replace(['\\', "\0"], ['/', ''], trim($relativa));
        $relativa = ltrim($relativa, '/');
        if ($relativa === '' || str_contains($relativa, '..')) {
            return null;
        }
        $base = realpath(dirname(__DIR__, 2) . '/storage/novedades');
        if ($base === false) {
            return null;
        }
        $full = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativa);
        $real = realpath($full);
        if ($real === false || !str_starts_with($real, $base)) {
            return null;
        }

        return $real;
    }

    private function safeFilenameHeader(string $name): string
    {
        $name = preg_replace('/[^\w.\- ()áéíóúÁÉÍÓÚñÑ]+/u', '_', $name) ?? 'archivo';
        $name = trim($name);
        if ($name === '') {
            return 'archivo';
        }

        return substr($name, 0, 180);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
