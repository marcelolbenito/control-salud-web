<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/catalogos.php';
require_once dirname(__DIR__) . '/Repositories/ListaPreciosRepository.php';
require_once dirname(__DIR__) . '/Repositories/OrdenesRepository.php';

final class ListaPreciosController
{
    private const PER_PAGE = 50;

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
        $repo = new ListaPreciosRepository($this->pdo);
        $tabla = $repo->resolveTable();
        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');

        $idCobertura = isset($_GET['cobertura']) ? (int) $_GET['cobertura'] : 0;
        $coberturaTxt = trim((string) ($_GET['cobertura_txt'] ?? ''));
        if ($idCobertura < 1 && $coberturaTxt !== '') {
            $idCobertura = catalogo_resolver_id($cobOpts, $coberturaTxt);
        }
        if ($idCobertura > 0 && isset($_GET['cobertura']) && (int) $_GET['cobertura'] < 1 && $coberturaTxt !== '') {
            $redir = '/aranceles.php?cobertura=' . $idCobertura;
            $qTmp = trim((string) ($_GET['q'] ?? ''));
            if ($qTmp !== '') {
                $redir .= '&q=' . rawurlencode($qTmp);
            }
            header('Location: ' . $redir);
            exit;
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $coberturaNoResuelta = $coberturaTxt !== '' && $idCobertura < 1;

        $rows = [];
        $total = 0;
        $coberturaNombre = '';
        if ($idCobertura > 0 && $tabla !== null) {
            foreach ($cobOpts as $c) {
                if ((int) $c['id'] === $idCobertura) {
                    $coberturaNombre = (string) ($c['nombre'] ?? '');
                    break;
                }
            }
            $total = $repo->contarFiltrado($idCobertura, $q !== '' ? $q : null);
            $offset = ($page - 1) * self::PER_PAGE;
            $rows = $repo->listar($idCobertura, $q !== '' ? $q : null, self::PER_PAGE, $offset);
        }

        $totalPages = $total > 0 ? (int) ceil($total / self::PER_PAGE) : 1;

        $body = $this->renderView('aranceles/index', [
            'tabla' => $tabla['name'] ?? null,
            'cobOpts' => $cobOpts,
            'idCobertura' => $idCobertura,
            'coberturaTxt' => $coberturaTxt !== '' ? $coberturaTxt : catalogo_valor_datalist($cobOpts, $idCobertura),
            'coberturaNoResuelta' => $coberturaNoResuelta,
            'coberturaNombre' => $coberturaNombre,
            'q' => $q,
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => self::PER_PAGE,
        ]);
        layout_render('Aranceles por obra social', $body, $this->user);
    }

    public function form(): void
    {
        $repo = new ListaPreciosRepository($this->pdo);
        if ($repo->resolveTable() === null) {
            flash_set('No existe la tabla de precios. Ejecutá sql/migration_032_lista_precios.sql o importá «Lista Precios».');
            header('Location: /aranceles.php');
            exit;
        }

        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');
        $practicaOpts = catalogo_lista($this->pdo, 'lista_practicas', 'prioridad_id');
        $planes = catalogo_lista($this->pdo, 'lista_planes', 'nombre');

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $prefCobertura = isset($_GET['cobertura']) ? (int) $_GET['cobertura'] : 0;

        $row = [
            'id' => $id,
            'idobrasocial' => $prefCobertura > 0 ? $prefCobertura : null,
            'idpractica' => null,
            'idplan' => 0,
            'costopaciente' => null,
            'costocobertura' => null,
        ];
        if ($id > 0) {
            $loaded = $repo->findById($id);
            if (!$loaded) {
                flash_set('Arancel no encontrado.');
                header('Location: /aranceles.php');
                exit;
            }
            $row = $loaded;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $id = (int) ($_POST['id'] ?? 0);
            $parsed = $this->parsePost($error);
            if ($error === '') {
                $dup = $repo->findPorCombinacion(
                    (int) $parsed['idobrasocial'],
                    (int) $parsed['idpractica'],
                    (int) $parsed['idplan'],
                    $id > 0 ? $id : null
                );
                if ($dup !== null) {
                    $error = 'Ya existe un arancel para esa obra social, práctica y plan (id ' . (int) $dup['id'] . '). Editá ese registro.';
                }
            }
            if ($error === '') {
                if ($id > 0) {
                    $repo->update($id, $parsed);
                    $msg = 'Arancel actualizado.';
                } else {
                    $id = $repo->insert($parsed);
                    $msg = 'Arancel creado.';
                }

                $propagadas = $this->propagarCostosOrdenesSiCorresponde($parsed);
                if ($propagadas > 0) {
                    $msg .= ' Se actualizaron los costos de ' . $propagadas . ' orden(es) pendientes de facturar (A).';
                }

                flash_set($msg);
                $back = '/aranceles.php?cobertura=' . (int) $parsed['idobrasocial'];
                header('Location: ' . $back);
                exit;
            }
            $row = array_merge($row, $_POST);
        }

        $body = $this->renderView('aranceles/form', [
            'row' => $row,
            'cobOpts' => $cobOpts,
            'practicaOpts' => $practicaOpts,
            'planes' => $planes,
            'error' => $error,
            'propagarDesde' => date('Y-m-01'),
            'propagarHasta' => date('Y-m-d'),
        ]);
        $sub = ((int) ($row['id'] ?? 0)) > 0 ? 'Editar arancel' : 'Nuevo arancel';
        layout_render($sub, $body, $this->user);
    }

    public function deletePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /aranceles.php');
            exit;
        }
        csrf_verify();
        $repo = new ListaPreciosRepository($this->pdo);
        $id = (int) ($_POST['id'] ?? 0);
        $cobertura = (int) ($_POST['cobertura'] ?? 0);
        if ($id > 0) {
            try {
                $repo->deleteById($id);
                flash_set('Arancel eliminado.');
            } catch (Throwable $e) {
                flash_set('No se pudo eliminar el arancel.');
            }
        }
        $dest = '/aranceles.php';
        if ($cobertura > 0) {
            $dest .= '?cobertura=' . $cobertura;
        }
        header('Location: ' . $dest);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePost(string &$error): array
    {
        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');
        $practicaOpts = catalogo_lista($this->pdo, 'lista_practicas', 'prioridad_id');

        $idCobertura = (int) ($_POST['idobrasocial'] ?? 0);
        if ($idCobertura < 1) {
            $idCobertura = catalogo_resolver_id($cobOpts, trim((string) ($_POST['idobrasocial_txt'] ?? '')));
        }
        $idPractica = (int) ($_POST['idpractica'] ?? 0);
        $practicaTxt = trim((string) ($_POST['idpractica_txt'] ?? ''));
        if ($practicaOpts !== [] && $practicaTxt !== '') {
            $idPractica = catalogo_resolver_id_practica($practicaOpts, $practicaTxt);
        } elseif ($idPractica < 1 && $practicaTxt !== '') {
            $idPractica = 0;
        }
        $idPlan = (int) ($_POST['idplan'] ?? 0);
        if ($idCobertura < 1) {
            $error = 'Elegí la obra social / cobertura (código o nombre de la lista).';
        } elseif ($idPractica < 1) {
            $error = 'Elegí la práctica / estudio.';
        }

        $costoPaciente = post_float_null('costopaciente');
        $costoCobertura = post_float_null('costocobertura');
        if ($error === '' && $costoPaciente === null && $costoCobertura === null) {
            $error = 'Indicá al menos un importe (paciente u obra social).';
        }

        if ($error !== '') {
            return [];
        }

        return [
            'idobrasocial' => $idCobertura,
            'idpractica' => $idPractica,
            'idplan' => $idPlan > 0 ? $idPlan : 0,
            'costopaciente' => $costoPaciente ?? 0,
            'costocobertura' => $costoCobertura ?? 0,
            'usarporcentaje' => 0,
            'costoporcentaje' => 0,
            'cobradr' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private function propagarCostosOrdenesSiCorresponde(array $parsed): int
    {
        if (empty($_POST['actualizar_ordenes'])) {
            return 0;
        }
        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            return 0;
        }

        $fechaDesde = trim((string) ($_POST['propagar_desde'] ?? ''));
        $fechaHasta = trim((string) ($_POST['propagar_hasta'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            return 0;
        }

        $ordRepo = new OrdenesRepository($this->pdo, user_clinica_id($this->user));

        return $ordRepo->actualizarCostosPendientesPorArancel(
            (int) $parsed['idobrasocial'],
            (int) $parsed['idpractica'],
            (int) ($parsed['idplan'] ?? 0),
            $fechaDesde,
            $fechaHasta,
            (float) ($parsed['costopaciente'] ?? 0),
            (float) ($parsed['costocobertura'] ?? 0)
        );
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
