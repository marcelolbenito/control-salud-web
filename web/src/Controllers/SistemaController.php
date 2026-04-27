<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Config/ConfigExeFieldMap.php';

final class SistemaController
{
    /** @var PDO */
    private $pdo;
    /** @var array|null */
    private $user;

    public function __construct(PDO $pdo, ?array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function index(): void
    {
        $backupTable = ConfigExeFieldMap::detectarTablaBackup($this->pdo);
        $body = $this->renderView('sistema/index', [
            'configRows' => $this->listConfigWeb(),
            'legacyBackup' => $this->detectLegacyConfigBackup(),
            'exeMap' => ConfigExeFieldMap::porColumna(),
            'exePreview' => $backupTable !== null ? ConfigExeFieldMap::vistaPreviaLegacy($this->pdo, 64) : [],
            'backupConfigTable' => $backupTable,
            'canSeedFromExe' => $backupTable !== null && db_table_exists($this->pdo, 'config'),
            'rolesEnabled' => db_table_has_column($this->pdo, 'usuarios', 'rol'),
            'usersRows' => $this->listUsuarios(),
            'isSuperadmin' => auth_is_superadmin($this->user),
            'currentUserId' => (int) ($this->user['id'] ?? 0),
        ]);
        layout_render('Sistema y configuración', $body, $this->user);
    }

    public function seedExeConfigPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sistema.php');
            exit;
        }
        csrf_verify();
        $n = ConfigExeFieldMap::aplicarSembradoConfig($this->pdo, user_clinica_id($this->user));
        if ($n === 0) {
            flash_set('No se importó ningún valor: verificá tabla config, y que exista backup_legacy_Config_* con datos.');
        } else {
            flash_set('Se importaron ' . $n . ' parámetros desde el backup de Config del .exe (solo claves marcadas para sembrar y valores no vacíos).');
        }
        header('Location: /sistema.php');
        exit;
    }

    public function configForm(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $row = ['id' => 0, 'clave' => '', 'valor' => ''];
        if ($id > 0 && db_table_exists($this->pdo, 'config')) {
            $cid = user_clinica_id($this->user);
            $hasClin = db_table_has_column($this->pdo, 'config', 'id_clinica');
            $sql = 'SELECT id, clave, valor FROM config WHERE id = ?';
            $par = [$id];
            if ($hasClin) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $cid;
            }
            $sql .= ' LIMIT 1';
            $st = $this->pdo->prepare($sql);
            $st->execute($par);
            $loaded = $st->fetch(PDO::FETCH_ASSOC);
            if ($loaded) {
                $row = $loaded;
            } else {
                flash_set('Clave no encontrada.');
                header('Location: /sistema.php');
                exit;
            }
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $clave = trim((string) ($_POST['clave'] ?? ''));
            $valor = (string) ($_POST['valor'] ?? '');
            if ($clave === '') {
                $error = 'La clave no puede estar vacía.';
            } elseif (!preg_match('/^[a-z0-9_.-]{1,100}$/i', $clave)) {
                $error = 'Clave inválida: usá letras, números, puntos, guiones o guión bajo (máx. 100).';
            } else {
                $cid = user_clinica_id($this->user);
                $hasClin = db_table_has_column($this->pdo, 'config', 'id_clinica');
                if ($id > 0) {
                    $sql = 'UPDATE config SET clave = ?, valor = ? WHERE id = ?';
                    $par = [$clave, $valor === '' ? null : $valor, $id];
                    if ($hasClin) {
                        $sql .= ' AND id_clinica = ?';
                        $par[] = $cid;
                    }
                    $st = $this->pdo->prepare($sql);
                    $st->execute($par);
                    flash_set('Parámetro actualizado.');
                } else {
                    try {
                        if ($hasClin) {
                            $st = $this->pdo->prepare('INSERT INTO config (id_clinica, clave, valor) VALUES (?, ?, ?)');
                            $st->execute([$cid, $clave, $valor === '' ? null : $valor]);
                        } else {
                            $st = $this->pdo->prepare('INSERT INTO config (clave, valor) VALUES (?, ?)');
                            $st->execute([$clave, $valor === '' ? null : $valor]);
                        }
                    } catch (Throwable $e) {
                        $error = 'No se pudo guardar (¿clave duplicada?).';
                    }
                    if ($error === '') {
                        flash_set('Parámetro creado.');
                        header('Location: /sistema.php');
                        exit;
                    }
                }
                if ($error === '') {
                    header('Location: /sistema.php');
                    exit;
                }
            }
            $row = ['id' => $id, 'clave' => $clave, 'valor' => $valor];
        }

        $body = $this->renderView('sistema/config_form', [
            'row' => $row,
            'error' => $error,
        ]);
        $t = $row['id'] ? 'Editar parámetro' : 'Nuevo parámetro';
        layout_render($t, $body, $this->user);
    }

    public function configDeletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sistema.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && db_table_exists($this->pdo, 'config')) {
            $cid = user_clinica_id($this->user);
            $hasClin = db_table_has_column($this->pdo, 'config', 'id_clinica');
            $sql = 'DELETE FROM config WHERE id = ?';
            $par = [$id];
            if ($hasClin) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $cid;
            }
            $st = $this->pdo->prepare($sql);
            $st->execute($par);
            flash_set('Parámetro eliminado.');
        }
        header('Location: /sistema.php');
        exit;
    }

    public function userForm(): void
    {
        require_roles(['superadmin', 'admin_clinica']);
        if (!db_table_exists($this->pdo, 'usuarios')) {
            flash_set('Falta la tabla usuarios.');
            header('Location: /sistema.php');
            exit;
        }
        $isSuper = auth_is_superadmin($this->user);
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $row = [
            'id' => 0,
            'usuario' => '',
            'nombre' => '',
            'email' => '',
            'activo' => 1,
            'id_clinica' => user_clinica_id($this->user),
            'id_doctor' => 0,
            'rol' => 'doctor',
        ];
        $doctoresOpts = $this->listDoctoresDisponiblesParaUsuario($id > 0 ? $id : null, (int) ($row['id_doctor'] ?? 0));
        if ($id > 0) {
            $loaded = $this->findUsuarioById($id);
            if ($loaded === null) {
                flash_set('Usuario no encontrado.');
                header('Location: /sistema.php');
                exit;
            }
            if (!$isSuper && (int) ($loaded['id_clinica'] ?? 1) !== user_clinica_id($this->user)) {
                http_response_code(403);
                exit('No tenés permisos para editar este usuario.');
            }
            $row = array_merge($row, $loaded);
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $activo = isset($_POST['activo']) ? 1 : 0;
            $clave = (string) ($_POST['clave'] ?? '');
            $clave2 = (string) ($_POST['clave2'] ?? '');
            $rol = strtolower(trim((string) ($_POST['rol'] ?? 'doctor')));
            if (!in_array($rol, ['superadmin', 'admin_clinica', 'doctor'], true)) {
                $rol = 'doctor';
            }
            $idClinica = $isSuper ? max(1, (int) ($_POST['id_clinica'] ?? 1)) : user_clinica_id($this->user);
            $idDoctor = (int) ($_POST['id_doctor'] ?? 0);
            if ($rol !== 'doctor') {
                $idDoctor = 0;
            }
            $doctoresOpts = $this->listDoctoresDisponiblesParaUsuario($id > 0 ? $id : null, $idDoctor);

            if ($usuario === '') {
                $error = 'El usuario es obligatorio.';
            } elseif (!preg_match('/^[a-z0-9._-]{3,50}$/i', $usuario)) {
                $error = 'Usuario inválido: usar letras/números y . _ - (3 a 50).';
            } elseif ($id < 1 && strlen($clave) < 8) {
                $error = 'La contraseña inicial debe tener al menos 8 caracteres.';
            } elseif ($clave !== '' && $clave !== $clave2) {
                $error = 'Las contraseñas no coinciden.';
            } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'El email no tiene formato válido.';
            } elseif (!$isSuper && $rol === 'superadmin') {
                $error = 'Solo superadmin puede asignar rol superadmin.';
            } elseif ($rol === 'doctor' && $idDoctor < 1) {
                $error = 'Para rol doctor debés vincular un profesional.';
            }

            if ($error === '' && $id > 0) {
                $loaded = $this->findUsuarioById($id);
                if ($loaded === null) {
                    $error = 'Usuario no encontrado.';
                } elseif (!$isSuper && (int) ($loaded['id_clinica'] ?? 1) !== user_clinica_id($this->user)) {
                    $error = 'No tenés permisos para editar este usuario.';
                }
            }

            if ($error === '') {
                $exists = $this->existsOtherUsuario($usuario, $id);
                if ($exists) {
                    $error = 'Ya existe otro usuario con ese nombre.';
                }
            }

            if ($error === '') {
                if ($id > 0) {
                    $this->updateUsuario($id, $usuario, $nombre, $email, $activo, $idClinica, $idDoctor, $rol, $clave);
                    flash_set('Usuario actualizado.');
                } else {
                    $this->insertUsuario($usuario, $nombre, $email, $activo, $idClinica, $idDoctor, $rol, $clave);
                    flash_set('Usuario creado.');
                }
                header('Location: /sistema.php');
                exit;
            }

            $row = [
                'id' => $id,
                'usuario' => $usuario,
                'nombre' => $nombre,
                'email' => $email,
                'activo' => $activo,
                'id_clinica' => $idClinica,
                'id_doctor' => $idDoctor,
                'rol' => $rol,
            ];
        }

        $body = $this->renderView('sistema/user_form', [
            'row' => $row,
            'error' => $error,
            'isSuperadmin' => $isSuper,
            'rolesEnabled' => db_table_has_column($this->pdo, 'usuarios', 'rol'),
            'doctoresOpts' => $doctoresOpts,
        ]);
        $title = $row['id'] ? 'Editar usuario' : 'Nuevo usuario';
        layout_render($title, $body, $this->user);
    }

    public function userDeletePost(): void
    {
        require_roles(['superadmin', 'admin_clinica']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sistema.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /sistema.php');
            exit;
        }
        $u = $this->findUsuarioById($id);
        if ($u === null) {
            flash_set('Usuario no encontrado.');
            header('Location: /sistema.php');
            exit;
        }
        $isSuper = auth_is_superadmin($this->user);
        if (!$isSuper && (int) ($u['id_clinica'] ?? 1) !== user_clinica_id($this->user)) {
            http_response_code(403);
            exit('No tenés permisos para eliminar este usuario.');
        }
        if ((int) ($u['id'] ?? 0) === (int) ($this->user['id'] ?? 0)) {
            flash_set('No podés eliminar tu propio usuario.');
            header('Location: /sistema.php');
            exit;
        }
        if (!$isSuper && (string) ($u['rol'] ?? '') === 'superadmin') {
            flash_set('Solo superadmin puede eliminar un superadmin.');
            header('Location: /sistema.php');
            exit;
        }
        $st = $this->pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $st->execute([$id]);
        flash_set('Usuario eliminado.');
        header('Location: /sistema.php');
        exit;
    }

    /**
     * @return list<array{id:int,clave:string,valor:?string}>
     */
    private function listConfigWeb(): array
    {
        if (!db_table_exists($this->pdo, 'config')) {
            return [];
        }
        $cid = user_clinica_id($this->user);
        if (db_table_has_column($this->pdo, 'config', 'id_clinica')) {
            $st = $this->pdo->prepare('SELECT id, clave, valor FROM config WHERE id_clinica = ? ORDER BY clave ASC');
            $st->execute([$cid]);

            return $st->fetchAll(PDO::FETCH_ASSOC);
        }
        $st = $this->pdo->query('SELECT id, clave, valor FROM config ORDER BY clave ASC');

        return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * @return list<string>
     */
    private function detectLegacyConfigBackup(): array
    {
        $st = $this->pdo->query(
            "SELECT table_name FROM information_schema.tables
               WHERE table_schema = DATABASE() AND table_name LIKE 'backup_legacy_Config_%'
               ORDER BY table_name"
        );
        if (!$st) {
            return [];
        }
        $names = [];
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            // Compatibilidad: algunos drivers devuelven TABLE_NAME en mayúscula.
            $vals = array_values($r);
            if (isset($vals[0])) {
                $names[] = (string) $vals[0];
            }
        }

        return $names;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function listUsuarios(): array
    {
        if (!db_table_exists($this->pdo, 'usuarios')) {
            return [];
        }
        $hasRol = db_table_has_column($this->pdo, 'usuarios', 'rol');
        $hasDoc = db_table_has_column($this->pdo, 'usuarios', 'id_doctor');
        $selRol = $hasRol ? ', rol' : ", 'admin_clinica' AS rol";
        $selDoc = $hasDoc ? ', id_doctor' : ', NULL AS id_doctor';
        $sql = 'SELECT id, usuario, nombre, email, activo, id_clinica' . $selDoc . $selRol . ' FROM usuarios';
        $params = [];
        if (!auth_is_superadmin($this->user)) {
            $sql .= ' WHERE id_clinica = ?';
            $params[] = user_clinica_id($this->user);
        }
        $sql .= ' ORDER BY activo DESC, usuario ASC';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findUsuarioById(int $id): ?array
    {
        if ($id < 1 || !db_table_exists($this->pdo, 'usuarios')) {
            return null;
        }
        $hasRol = db_table_has_column($this->pdo, 'usuarios', 'rol');
        $hasDoc = db_table_has_column($this->pdo, 'usuarios', 'id_doctor');
        $selRol = $hasRol ? ', rol' : ", 'admin_clinica' AS rol";
        $selDoc = $hasDoc ? ', id_doctor' : ', NULL AS id_doctor';
        $st = $this->pdo->prepare('SELECT id, usuario, nombre, email, activo, id_clinica' . $selDoc . $selRol . ' FROM usuarios WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    private function existsOtherUsuario(string $usuario, int $excludeId): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id <> ? LIMIT 1');
        $st->execute([$usuario, $excludeId]);

        return (bool) $st->fetch();
    }

    private function insertUsuario(string $usuario, string $nombre, string $email, int $activo, int $idClinica, int $idDoctor, string $rol, string $clave): void
    {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $hasRol = db_table_has_column($this->pdo, 'usuarios', 'rol');
        $hasDoc = db_table_has_column($this->pdo, 'usuarios', 'id_doctor');
        if ($hasRol && $hasDoc) {
            $st = $this->pdo->prepare('INSERT INTO usuarios (usuario, password_hash, nombre, email, activo, id_clinica, id_doctor, rol) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$usuario, $hash, $nombre !== '' ? $nombre : $usuario, $email !== '' ? $email : null, $activo, $idClinica, $idDoctor > 0 ? $idDoctor : null, $rol]);
            return;
        }
        if ($hasRol) {
            $st = $this->pdo->prepare('INSERT INTO usuarios (usuario, password_hash, nombre, email, activo, id_clinica, rol) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$usuario, $hash, $nombre !== '' ? $nombre : $usuario, $email !== '' ? $email : null, $activo, $idClinica, $rol]);
            return;
        }
        if ($hasDoc) {
            $st = $this->pdo->prepare('INSERT INTO usuarios (usuario, password_hash, nombre, email, activo, id_clinica, id_doctor) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $st->execute([$usuario, $hash, $nombre !== '' ? $nombre : $usuario, $email !== '' ? $email : null, $activo, $idClinica, $idDoctor > 0 ? $idDoctor : null]);
            return;
        }
        $st = $this->pdo->prepare('INSERT INTO usuarios (usuario, password_hash, nombre, email, activo, id_clinica) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([$usuario, $hash, $nombre !== '' ? $nombre : $usuario, $email !== '' ? $email : null, $activo, $idClinica]);
    }

    private function updateUsuario(
        int $id,
        string $usuario,
        string $nombre,
        string $email,
        int $activo,
        int $idClinica,
        int $idDoctor,
        string $rol,
        string $clave
    ): void {
        $sets = ['usuario = ?', 'nombre = ?', 'email = ?', 'activo = ?', 'id_clinica = ?'];
        $params = [$usuario, $nombre !== '' ? $nombre : $usuario, $email !== '' ? $email : null, $activo, $idClinica];
        if (db_table_has_column($this->pdo, 'usuarios', 'id_doctor')) {
            $sets[] = 'id_doctor = ?';
            $params[] = $idDoctor > 0 ? $idDoctor : null;
        }
        if (db_table_has_column($this->pdo, 'usuarios', 'rol')) {
            $sets[] = 'rol = ?';
            $params[] = $rol;
        }
        if ($clave !== '') {
            $sets[] = 'password_hash = ?';
            $params[] = password_hash($clave, PASSWORD_DEFAULT);
        }
        $params[] = $id;
        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    /**
     * @return list<array{id:int,nombre:string}>
     */
    private function listDoctoresDisponiblesParaUsuario(?int $excludeUserId = null, int $includeDoctorId = 0): array
    {
        if (!db_table_exists($this->pdo, 'lista_doctores')) {
            return [];
        }
        $hasUsers = db_table_exists($this->pdo, 'usuarios');
        $hasIdDoctor = $hasUsers && db_table_has_column($this->pdo, 'usuarios', 'id_doctor');
        $params = [];
        $sql = 'SELECT d.id, d.nombre
            FROM lista_doctores d';
        if ($hasIdDoctor) {
            $sql .= ' LEFT JOIN usuarios u ON u.id_doctor = d.id';
            if ($excludeUserId !== null && $excludeUserId > 0) {
                $sql .= ' AND u.id <> ?';
                $params[] = $excludeUserId;
            }
        }
        $sql .= ' WHERE 1=1';
        if (!auth_is_superadmin($this->user) && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $sql .= ' AND d.id_clinica = ?';
            $params[] = user_clinica_id($this->user);
        }
        if ($hasIdDoctor) {
            $sql .= ' AND (u.id IS NULL';
            if ($includeDoctorId > 0) {
                $sql .= ' OR d.id = ?';
                $params[] = $includeDoctorId;
            }
            $sql .= ')';
        }
        $sql .= ' ORDER BY d.nombre ASC';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
