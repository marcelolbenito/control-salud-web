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
        ]);
        layout_render('Sistema y configuración', $body, $this->user);
    }

    public function seedExeConfigPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sistema.php');
            exit;
        }
        $n = ConfigExeFieldMap::aplicarSembradoConfig($this->pdo);
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
            $st = $this->pdo->prepare('SELECT id, clave, valor FROM config WHERE id = ? LIMIT 1');
            $st->execute([$id]);
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
            $id = (int) ($_POST['id'] ?? 0);
            $clave = trim((string) ($_POST['clave'] ?? ''));
            $valor = (string) ($_POST['valor'] ?? '');
            if ($clave === '') {
                $error = 'La clave no puede estar vacía.';
            } elseif (!preg_match('/^[a-z0-9_.-]{1,100}$/i', $clave)) {
                $error = 'Clave inválida: usá letras, números, puntos, guiones o guión bajo (máx. 100).';
            } else {
                if ($id > 0) {
                    $st = $this->pdo->prepare('UPDATE config SET clave = ?, valor = ? WHERE id = ?');
                    $st->execute([$clave, $valor === '' ? null : $valor, $id]);
                    flash_set('Parámetro actualizado.');
                } else {
                    try {
                        $st = $this->pdo->prepare('INSERT INTO config (clave, valor) VALUES (?, ?)');
                        $st->execute([$clave, $valor === '' ? null : $valor]);
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
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && db_table_exists($this->pdo, 'config')) {
            $st = $this->pdo->prepare('DELETE FROM config WHERE id = ?');
            $st->execute([$id]);
            flash_set('Parámetro eliminado.');
        }
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

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
