<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/catalogos.php';
require_once dirname(__DIR__) . '/Repositories/CajaRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class CajaController
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
        $repo = new CajaRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>caja</code>. Importá el esquema en <code>sql/schema_mysql.sql</code>.</p></div>';
            layout_render('Caja', $body, $this->user);

            return;
        }

        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));
        $f = self::collectFiltros();
        $rows = $repo->listForIndex($f);
        $movimientosMigrados = $repo->countRows();
        $totalHoyGeneral = $repo->sumByDate(date('Y-m-d'));
        $totalFiltro = $repo->sumForFilters($f);
        $resumenPorDoctor = $repo->resumenPorDoctor($f, 8);
        $doctores = $docRepo->listAllOrdered();
        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');
        $queryString = self::buildQueryString($f);

        $body = $this->renderView('caja/index', [
            'rows' => $rows,
            'doctores' => $doctores,
            'cobOpts' => $cobOpts,
            'f' => $f,
            'queryString' => $queryString,
            'hayFiltros' => self::hayFiltros($f),
            'movimientosMigrados' => $movimientosMigrados,
            'totalHoyGeneral' => $totalHoyGeneral,
            'totalFiltro' => $totalFiltro,
            'resumenPorDoctor' => $resumenPorDoctor,
        ]);
        layout_render('Caja', $body, $this->user);
    }

    public function form(): void
    {
        $repo = new CajaRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            flash_set('Falta la tabla caja (importar esquema).');
            header('Location: /index.php');
            exit;
        }

        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $queryString = self::buildQueryString(self::collectFiltros());
        $volver = '/caja.php' . ($queryString !== '' ? '?' . $queryString : '');

        $row = [
            'id' => 0,
            'doctor' => '',
            'fechacaja' => date('Y-m-d'),
            'importecaja' => '',
            'tipo_movimiento' => 'ingreso',
            'idcoberturacaja' => '',
            'turnocaja' => '',
            'observaciones' => '',
        ];
        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Registro de caja no encontrado.');
                header('Location: /caja.php');
                exit;
            }
            $row = array_merge($row, $loaded);
            $row['fechacaja'] = !empty($loaded['fechacaja']) ? substr((string) $loaded['fechacaja'], 0, 10) : '';
            $imp = $loaded['importecaja'] ?? '';
            if ($imp !== null && $imp !== '' && is_numeric($imp)) {
                $v = (float) $imp;
                $row['tipo_movimiento'] = $v < 0 ? 'egreso' : 'ingreso';
                $abs = abs($v);
                $row['importecaja'] = $abs == floor($abs) ? (string) (int) $abs : rtrim(rtrim(number_format($abs, 4, '.', ''), '0'), '.');
            }
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $doctor = (int) ($_POST['doctor'] ?? 0);
            $fecha = trim((string) ($_POST['fechacaja'] ?? ''));
            $importeTxt = trim(str_replace(',', '.', (string) ($_POST['importecaja'] ?? '')));
            $tipoMov = trim((string) ($_POST['tipo_movimiento'] ?? 'ingreso'));
            $idCobTxt = trim((string) ($_POST['idcoberturacaja'] ?? ''));
            $idCob = $idCobTxt === '' ? null : (int) $idCobTxt;
            $turno = trim((string) ($_POST['turnocaja'] ?? ''));
            $obs = trim((string) ($_POST['observaciones'] ?? ''));

            if ($doctor < 1) {
                $error = 'Elegí un profesional.';
            } elseif (!$repo->doctorExists($doctor)) {
                $error = 'Profesional no válido.';
            } elseif ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $error = 'Indicá una fecha válida.';
            } elseif ($importeTxt !== '' && !is_numeric($importeTxt)) {
                $error = 'El importe debe ser numérico.';
            } elseif (!in_array($tipoMov, ['ingreso', 'egreso'], true)) {
                $error = 'Tipo de movimiento no válido.';
            } elseif ($idCob !== null && $idCob > 0 && !$repo->coberturaExists($idCob)) {
                $error = 'Cobertura no válida.';
            } else {
                $importeSigned = $importeTxt === '' ? 0.0 : (float) $importeTxt;
                $importeSigned = abs($importeSigned);
                if ($tipoMov === 'egreso') {
                    $importeSigned = -$importeSigned;
                }
                $vals = [
                    'doctor' => $doctor,
                    'fechacaja' => $fecha,
                    'importecaja' => $importeSigned,
                    'idcoberturacaja' => ($idCob !== null && $idCob > 0) ? $idCob : null,
                    'turnocaja' => $turno !== '' ? $turno : null,
                    'observaciones' => $obs !== '' ? $obs : null,
                ];
                if ($id > 0) {
                    $repo->updateRow($id, $vals);
                    flash_set('Registro de caja actualizado.');
                } else {
                    $repo->insertRow($vals);
                    flash_set('Registro de caja creado.');
                }
                $retQs = trim((string) ($_POST['caja_return_qs'] ?? $queryString));
                header('Location: /caja.php' . ($retQs !== '' ? '?' . $retQs : ''));
                exit;
            }

            $row = array_merge($row, [
                'id' => $id,
                'doctor' => $doctor,
                'fechacaja' => $fecha,
                'importecaja' => $importeTxt,
                'tipo_movimiento' => $tipoMov,
                'idcoberturacaja' => $idCobTxt,
                'turnocaja' => $turno,
                'observaciones' => $obs,
            ]);
            $queryString = trim((string) ($_POST['caja_return_qs'] ?? $queryString));
            $volver = '/caja.php' . ($queryString !== '' ? '?' . $queryString : '');
        }

        $doctores = $docRepo->listActivos();
        if ($doctores === []) {
            $doctores = $docRepo->listAllOrdered();
        }
        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');
        $titulo = (int) ($row['id'] ?? 0) > 0 ? 'Editar caja' : 'Nuevo registro de caja';
        $body = $this->renderView('caja/form', [
            'row' => $row,
            'doctores' => $doctores,
            'cobOpts' => $cobOpts,
            'error' => $error,
            'titulo' => $titulo,
            'volver' => $volver,
            'queryString' => $queryString,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /caja.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /caja.php');
            exit;
        }
        $repo = new CajaRepository($this->pdo, user_clinica_id($this->user));
        $repo->deleteById($id);
        flash_set('Registro de caja eliminado.');
        header('Location: /caja.php');
        exit;
    }

    /**
     * @return array<string, string|int>
     */
    private static function collectFiltros(): array
    {
        $g = static function (string $k, $default = ''): string {
            return isset($_GET[$k]) ? trim((string) $_GET[$k]) : (string) $default;
        };
        $gi = static function (string $k): int {
            return isset($_GET[$k]) ? (int) $_GET[$k] : 0;
        };

        return [
            'doctor' => $gi('doctor'),
            'fecha_desde' => $g('fecha_desde'),
            'fecha_hasta' => $g('fecha_hasta'),
            'idcoberturacaja' => $gi('idcoberturacaja'),
            'q' => $g('q'),
        ];
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function buildQueryString(array $f): string
    {
        $q = [];
        if (($f['doctor'] ?? 0) > 0) {
            $q['doctor'] = (int) $f['doctor'];
        }
        if (($f['fecha_desde'] ?? '') !== '') {
            $q['fecha_desde'] = (string) $f['fecha_desde'];
        }
        if (($f['fecha_hasta'] ?? '') !== '') {
            $q['fecha_hasta'] = (string) $f['fecha_hasta'];
        }
        if (($f['idcoberturacaja'] ?? 0) > 0) {
            $q['idcoberturacaja'] = (int) $f['idcoberturacaja'];
        }
        if (($f['q'] ?? '') !== '') {
            $q['q'] = (string) $f['q'];
        }

        return http_build_query($q);
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function hayFiltros(array $f): bool
    {
        foreach ($f as $v) {
            if ($v === '' || $v === 0) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}

