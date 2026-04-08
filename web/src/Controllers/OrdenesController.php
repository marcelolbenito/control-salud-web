<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/OrdenesRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class OrdenesController
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
        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            $tn = h(OrdenesRepository::tableSqlName());
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>' . $tn . '</code> (datos del backup). Importá el backup o el esquema en <code>sql/schema_mysql.sql</code>.</p></div>';
            layout_render('Órdenes', $body, $this->user);
            return;
        }

        $repo = new OrdenesRepository($this->pdo);
        $docRepo = new DoctoresRepository($this->pdo);

        $f = self::collectFiltrosOrdenes();
        $rows = $repo->listForIndex($f);
        $doctores = $docRepo->listAllOrdered();
        $ordenesQueryString = self::buildOrdenesQueryString($f);

        $body = $this->renderView('ordenes/index', [
            'rows' => $rows,
            'doctores' => $doctores,
            'f' => $f,
            'ordenesQueryString' => $ordenesQueryString,
            'ordenesFiltrosActivos' => self::ordenesHayFiltrosActivos($f),
        ]);
        layout_render('Órdenes', $body, $this->user);
    }

    public function form(): void
    {
        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            flash_set('Falta la tabla ' . OrdenesRepository::tableSqlName() . ' (importar backup / esquema).');
            header('Location: /index.php');
            exit;
        }

        $repo = new OrdenesRepository($this->pdo);
        $docRepo = new DoctoresRepository($this->pdo);

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $prefillNro = isset($_GET['nropaci']) ? (int) $_GET['nropaci'] : 0;
        if ($prefillNro < 1 && $id < 1) {
            $prefillNro = isset($_GET['nrohc']) ? (int) $_GET['nrohc'] : 0;
        }

        $row = [
            'id' => 0,
            'NroPaci' => $prefillNro > 0 ? $prefillNro : '',
            'iddoctor' => '',
            'fecha_orden' => '',
            'autorizada' => 0,
            'entregada' => 0,
            'observaciones' => '',
        ];

        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Orden no encontrada.');
                header('Location: /ordenes.php');
                exit;
            }
            $row = array_merge($row, $loaded);
            if (!empty($loaded['fecha'])) {
                $row['fecha_orden'] = substr((string) $loaded['fecha'], 0, 10);
            } else {
                $row['fecha_orden'] = '';
            }
        }

        $doctores = $docRepo->listActivos();
        if ($doctores === []) {
            $doctores = $docRepo->listAllOrdered();
        }

        $ordenesReturnQs = self::buildOrdenesQueryString(self::collectFiltrosOrdenes());
        if ($prefillNro > 0 && !isset($_GET['nrohc'])) {
            $ordenesReturnQs = self::buildOrdenesQueryString(array_merge(
                self::collectFiltrosOrdenes(),
                ['nrohc' => $prefillNro]
            ));
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $nroPaci = (int) ($_POST['NroPaci'] ?? 0);
            $iddoctor = (int) ($_POST['iddoctor'] ?? 0);
            $fechaRaw = trim((string) ($_POST['fecha_orden'] ?? ''));
            $fechaOrden = $fechaRaw !== '' ? $fechaRaw : null;
            $autorizada = isset($_POST['autorizada']) ? 1 : 0;
            $entregada = isset($_POST['entregada']) ? 1 : 0;
            $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

            if ($nroPaci < 1) {
                $error = 'Indicá un Nro. HC (paciente) válido.';
            } elseif (!$repo->nroHcExists($nroPaci)) {
                $error = 'No existe un paciente con ese Nro. HC.';
            } elseif ($iddoctor < 1) {
                $error = 'Elegí un profesional.';
            } elseif (!$repo->doctorExists($iddoctor)) {
                $error = 'Profesional no válido.';
            } else {
                if ($id > 0) {
                    $repo->update($id, $nroPaci, $iddoctor, $fechaOrden, $autorizada, $entregada, $observaciones);
                    flash_set('Orden actualizada.');
                } else {
                    $repo->insert($nroPaci, $iddoctor, $fechaOrden, $autorizada, $entregada, $observaciones);
                    flash_set('Orden registrada.');
                }
                $retQs = trim((string) ($_POST['ordenes_return_qs'] ?? ''));
                header('Location: /ordenes.php' . ($retQs !== '' ? '?' . $retQs : ''));
                exit;
            }

            $ordenesReturnQs = trim((string) ($_POST['ordenes_return_qs'] ?? $ordenesReturnQs));

            $row = array_merge($row, [
                'id' => $id,
                'NroPaci' => $nroPaci,
                'iddoctor' => $iddoctor,
                'fecha_orden' => $fechaRaw,
                'autorizada' => $autorizada,
                'entregada' => $entregada,
                'observaciones' => $observaciones,
            ]);
        }

        $volver = '/ordenes.php' . ($ordenesReturnQs !== '' ? '?' . $ordenesReturnQs : '');
        $titulo = $row['id'] ? 'Editar orden' : 'Nueva orden';
        $body = $this->renderView('ordenes/form', [
            'row' => $row,
            'doctores' => $doctores,
            'error' => $error,
            'titulo' => $titulo,
            'volver' => $volver,
            'ordenesReturnQs' => $ordenesReturnQs,
        ]);
        layout_render($titulo, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ordenes.php');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($id < 1) {
            header('Location: /ordenes.php');
            exit;
        }
        $repo = new OrdenesRepository($this->pdo);
        if ($repo->countSesionesByOrden($id) > 0) {
            flash_set('No se puede eliminar: esta orden tiene sesiones registradas (módulo sesiones pendiente).');
            header('Location: /ordenes.php');
            exit;
        }
        $repo->deleteById($id);
        flash_set('Orden eliminada.');
        header('Location: /ordenes.php');
        exit;
    }

    /**
     * @return array<string, string|int>
     */
    private static function collectFiltrosOrdenes(): array
    {
        $g = static function (string $k, $default = ''): string {
            return isset($_GET[$k]) ? trim((string) $_GET[$k]) : (string) $default;
        };
        $gi = static function (string $k): int {
            return isset($_GET[$k]) ? (int) $_GET[$k] : 0;
        };

        return [
            'nrohc' => $gi('nrohc'),
            'doctor' => $gi('doctor'),
            'id_desde' => $gi('id_desde'),
            'id_hasta' => $gi('id_hasta'),
            'fecha_desde' => $g('fecha_desde'),
            'fecha_hasta' => $g('fecha_hasta'),
            'sucursal' => $gi('sucursal'),
            'idobrasocial' => $gi('idobrasocial'),
            'idpractica' => $gi('idpractica'),
            'idderivado' => $gi('idderivado'),
            'idplan' => $gi('idplan'),
            'estado' => $g('estado'),
            'estado_os' => $g('estado_os'),
            'autorizada' => $g('autorizada'),
            'entregada' => $g('entregada'),
            'liquidada' => $g('liquidada'),
        ];
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function buildOrdenesQueryString(array $f): string
    {
        $q = [];
        if (($f['nrohc'] ?? 0) > 0) {
            $q['nrohc'] = (int) $f['nrohc'];
        }
        if (($f['doctor'] ?? 0) > 0) {
            $q['doctor'] = (int) $f['doctor'];
        }
        if (($f['id_desde'] ?? 0) > 0) {
            $q['id_desde'] = (int) $f['id_desde'];
        }
        if (($f['id_hasta'] ?? 0) > 0) {
            $q['id_hasta'] = (int) $f['id_hasta'];
        }
        if (($f['fecha_desde'] ?? '') !== '') {
            $q['fecha_desde'] = (string) $f['fecha_desde'];
        }
        if (($f['fecha_hasta'] ?? '') !== '') {
            $q['fecha_hasta'] = (string) $f['fecha_hasta'];
        }
        foreach (['sucursal', 'idobrasocial', 'idpractica', 'idderivado', 'idplan'] as $k) {
            if (($f[$k] ?? 0) > 0) {
                $q[$k] = (int) $f[$k];
            }
        }
        if (($f['estado'] ?? '') !== '') {
            $q['estado'] = (string) $f['estado'];
        }
        if (($f['estado_os'] ?? '') !== '') {
            $q['estado_os'] = (string) $f['estado_os'];
        }
        foreach (['autorizada', 'entregada', 'liquidada'] as $k) {
            if (($f[$k] ?? '') !== '') {
                $q[$k] = (string) $f[$k];
            }
        }

        return http_build_query($q);
    }

    /**
     * @param array<string, string|int> $f
     */
    private static function ordenesHayFiltrosActivos(array $f): bool
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
