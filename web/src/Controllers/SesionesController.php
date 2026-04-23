<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/SesionesRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class SesionesController
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
        $repo = new SesionesRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>pacientes_sesiones</code>. Import? <code>sql/schema_mysql.sql</code>.</p></div>';
            layout_render('Sesiones', $body, $this->user);

            return;
        }

        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));
        $f = self::collectFiltros();
        $rows = $repo->listForIndex($f);
        $doctores = $docRepo->listAllOrdered();
        $queryString = self::buildQueryString($f);
        $hayFiltros = self::hayFiltros($f);

        $totalCant = 0;
        foreach ($rows as $r) {
            $totalCant += (int) ($r['cantidad_sesiones'] ?? 0);
        }

        $body = $this->renderView('sesiones/index', [
            'rows' => $rows,
            'doctores' => $doctores,
            'f' => $f,
            'queryString' => $queryString,
            'hayFiltros' => $hayFiltros,
            'totalCantidadSesiones' => $totalCant,
            'totalFilasEnTabla' => $repo->countAll(),
        ]);
        layout_render('Sesiones', $body, $this->user);
    }

    public function form(): void
    {
        $repo = new SesionesRepository($this->pdo, user_clinica_id($this->user));
        if (!$repo->tableExists()) {
            flash_set('Falta la tabla pacientes_sesiones.');
            header('Location: /index.php');
            exit;
        }

        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $queryString = self::buildQueryString(self::collectFiltros());
        $volver = '/sesiones.php' . ($queryString !== '' ? '?' . $queryString : '');

        $row = [
            'id' => 0,
            'idorden' => isset($_GET['idorden']) ? (int) $_GET['idorden'] : '',
            'NroPaci' => isset($_GET['nrohc']) ? (int) $_GET['nrohc'] : '',
            'iddoctor' => '',
            'fecha_sesion' => date('Y-m-d'),
            'cantidad_sesiones' => 1,
            'observaciones' => '',
        ];

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Sesi?n no encontrada.');
                header('Location: /sesiones.php');
                exit;
            }
            $row = array_merge($row, $loaded);
            $row['fecha_sesion'] = !empty($loaded['fecha_sesion']) ? substr((string) $loaded['fecha_sesion'], 0, 10) : '';
            $row['cantidad_sesiones'] = (int) ($loaded['cantidad_sesiones'] ?? 1);
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $idOrden = (int) ($_POST['idorden'] ?? 0);
            $nroHc = (int) ($_POST['NroPaci'] ?? 0);
            $iddoctor = (int) ($_POST['iddoctor'] ?? 0);
            $fecha = trim((string) ($_POST['fecha_sesion'] ?? ''));
            $cantTxt = trim((string) ($_POST['cantidad_sesiones'] ?? '1'));
            $obs = trim((string) ($_POST['observaciones'] ?? ''));

            $ordenCab = $idOrden > 0 ? $repo->findOrdenCabecera($idOrden) : null;
            if ($idOrden < 1) {
                $error = 'Indic? el id de orden.';
            } elseif ($ordenCab === null) {
                $error = 'La orden no existe.';
            } elseif ($nroHc < 1) {
                $error = 'Indic? Nro HC del paciente.';
            } elseif ($ordenCab['NroPaci'] !== $nroHc) {
                $error = 'El paciente no coincide con la orden.';
            } elseif ($iddoctor < 1 || !$repo->doctorExists($iddoctor)) {
                $error = 'Profesional inv?lido.';
            } elseif ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $error = 'Fecha de sesi?n inv?lida.';
            } elseif ($cantTxt === '' || !ctype_digit($cantTxt) || (int) $cantTxt < 1) {
                $error = 'Cantidad de sesiones debe ser un entero ÿÿÿ 1.';
            } else {
                $vals = [
                    'idorden' => $idOrden,
                    'NroPaci' => $nroHc,
                    'iddoctor' => $iddoctor,
                    'fecha_sesion' => $fecha,
                    'cantidad_sesiones' => (int) $cantTxt,
                    'observaciones' => $obs !== '' ? $obs : null,
                ];
                $idOrdenSync = $idOrden;
                if ($id > 0) {
                    $prev = $repo->findById($id);
                    $repo->updateRow($id, $vals);
                    if ($prev && (int) ($prev['idorden'] ?? 0) !== $idOrden) {
                        $repo->syncOrdenSesionesReali((int) $prev['idorden']);
                    }
                    flash_set('Sesi?n actualizada.');
                } else {
                    $repo->insertRow($vals);
                    flash_set('Sesi?n registrada.');
                }
                $repo->syncOrdenSesionesReali($idOrdenSync);

                $retQs = trim((string) ($_POST['sesiones_return_qs'] ?? $queryString));
                header('Location: /sesiones.php' . ($retQs !== '' ? '?' . $retQs : ''));
                exit;
            }

            $row = [
                'id' => $id,
                'idorden' => $idOrden > 0 ? $idOrden : '',
                'NroPaci' => $nroHc > 0 ? $nroHc : '',
                'iddoctor' => $iddoctor,
                'fecha_sesion' => $fecha,
                'cantidad_sesiones' => $cantTxt,
                'observaciones' => $obs,
            ];
            $queryString = trim((string) ($_POST['sesiones_return_qs'] ?? $queryString));
            $volver = '/sesiones.php' . ($queryString !== '' ? '?' . $queryString : '');
        }

        $doctores = $docRepo->listActivos();
        if ($doctores === []) {
            $doctores = $docRepo->listAllOrdered();
        }

        $titulo = (int) ($row['id'] ?? 0) > 0 ? 'Editar sesi?n' : 'Nueva sesi?n';
        $body = $this->renderView('sesiones/form', [
            'row' => $row,
            'doctores' => $doctores,
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
            header('Location: /sesiones.php');
            exit;
        }
        csrf_verify();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /sesiones.php');
            exit;
        }
        $repo = new SesionesRepository($this->pdo, user_clinica_id($this->user));
        $prev = $repo->findById($id);
        if (!$prev) {
            header('Location: /sesiones.php');
            exit;
        }
        $idOrden = (int) ($prev['idorden'] ?? 0);
        $repo->deleteById($id);
        if ($idOrden > 0) {
            $repo->syncOrdenSesionesReali($idOrden);
        }
        flash_set('Sesi?n eliminada.');
        header('Location: /sesiones.php');
        exit;
    }

    /**
     * @return array<string, string|int>
     */
    private static function collectFiltros(): array
    {
        return [
            'nrohc' => isset($_GET['nrohc']) ? (int) $_GET['nrohc'] : 0,
            'idorden' => isset($_GET['idorden']) ? (int) $_GET['idorden'] : 0,
            'doctor' => isset($_GET['doctor']) ? (int) $_GET['doctor'] : 0,
            'fecha_desde' => isset($_GET['fecha_desde']) ? trim((string) $_GET['fecha_desde']) : '',
            'fecha_hasta' => isset($_GET['fecha_hasta']) ? trim((string) $_GET['fecha_hasta']) : '',
            'paciente' => isset($_GET['paciente']) ? trim((string) $_GET['paciente']) : '',
        ];
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function buildQueryString(array $f): string
    {
        $q = [];
        foreach (['nrohc', 'idorden', 'doctor'] as $k) {
            if (($f[$k] ?? 0) > 0) {
                $q[$k] = (int) $f[$k];
            }
        }
        foreach (['fecha_desde', 'fecha_hasta'] as $k) {
            if (($f[$k] ?? '') !== '') {
                $q[$k] = (string) $f[$k];
            }
        }
        if (trim((string) ($f['paciente'] ?? '')) !== '') {
            $q['paciente'] = (string) $f['paciente'];
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
