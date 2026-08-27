<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/OrdenesRepository.php';
require_once dirname(__DIR__, 2) . '/includes/catalogos.php';

final class FacturacionOrdenesController
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
            $body = '<div class="container"><p class="alert alert-error">Falta la tabla <code>Pacientes Ordenes</code>.</p></div>';
            layout_render('Facturación obra social', $body, $this->user);
            return;
        }

        $idOs = (int) ($_GET['idobrasocial'] ?? $_POST['idobrasocial'] ?? 0);
        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? $_POST['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? $_POST['fecha_hasta'] ?? ''));

        if ($fechaDesde === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $fechaDesde = date('Y-m-01');
        }
        if ($fechaHasta === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $fechaHasta = date('Y-m-d');
        }
        if ($fechaDesde > $fechaHasta) {
            [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
        }

        $cobOpts = catalogo_lista($this->pdo, 'lista_coberturas', 'prioridad_id');
        $cobNombre = '';
        foreach ($cobOpts as $c) {
            if ((int) ($c['id'] ?? 0) === $idOs) {
                $cobNombre = trim((string) ($c['nombre'] ?? ''));
                break;
            }
        }

        $repo = new OrdenesRepository($this->pdo, user_clinica_id($this->user));
        $vista = self::normalizarVista((string) ($_GET['vista'] ?? $_POST['vista'] ?? 'pendientes'));
        $rows = [];
        $mostrarReporte = $idOs > 0 && isset($_GET['buscar']);
        if ($mostrarReporte) {
            $estadoOs = $vista === 'facturadas' ? 'F' : 'A';
            $rows = $repo->listFacturacionOs($idOs, $fechaDesde, $fechaHasta, $estadoOs);
        }

        $body = $this->renderView('facturacion_ordenes/index', [
            'idOs' => $idOs,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'vista' => $vista,
            'cobOpts' => $cobOpts,
            'cobNombre' => $cobNombre,
            'rows' => $rows,
            'mostrarReporte' => $mostrarReporte,
        ]);
        layout_render('Facturación obra social', $body, $this->user);
    }

    public function marcar(): void
    {
        csrf_verify();

        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            flash_set('Falta la tabla Pacientes Ordenes.');
            header('Location: /facturacion_ordenes.php');
            exit;
        }

        $idOs = (int) ($_POST['idobrasocial'] ?? 0);
        $fechaDesde = trim((string) ($_POST['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_POST['fecha_hasta'] ?? ''));
        $ids = isset($_POST['orden_ids']) && is_array($_POST['orden_ids']) ? $_POST['orden_ids'] : [];

        if ($idOs < 1) {
            flash_set('Elegí una obra social.');
            header('Location: /facturacion_ordenes.php');
            exit;
        }
        if ($ids === []) {
            flash_set('Seleccioná al menos una orden para marcar como facturada.');
            header('Location: ' . self::redirectUrl($idOs, $fechaDesde, $fechaHasta));
            exit;
        }

        $repo = new OrdenesRepository($this->pdo, user_clinica_id($this->user));
        $n = $repo->marcarFacturadasOs($ids, $idOs);

        if ($n < 1) {
            flash_set('No se actualizó ninguna orden (puede que ya estén facturadas o no correspondan a la obra social).');
        } else {
            flash_set($n === 1
                ? '1 orden marcada como facturada (estado_os = F).'
                : $n . ' órdenes marcadas como facturadas (estado_os = F).');
        }

        header('Location: ' . self::redirectUrl($idOs, $fechaDesde, $fechaHasta, 'facturadas'));
        exit;
    }

    public function actualizarCostos(): void
    {
        csrf_verify();

        if (!db_table_exists($this->pdo, OrdenesRepository::tableSqlName())) {
            flash_set('Falta la tabla Pacientes Ordenes.');
            header('Location: /facturacion_ordenes.php');
            exit;
        }

        $idOs = (int) ($_POST['idobrasocial'] ?? 0);
        $fechaDesde = trim((string) ($_POST['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_POST['fecha_hasta'] ?? ''));

        if ($idOs < 1) {
            flash_set('Elegí una obra social.');
            header('Location: /facturacion_ordenes.php');
            exit;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            flash_set('Indicá un rango de fechas válido.');
            header('Location: ' . self::redirectUrl($idOs, $fechaDesde, $fechaHasta));
            exit;
        }

        $repo = new OrdenesRepository($this->pdo, user_clinica_id($this->user));
        $n = $repo->actualizarCostosPendientesDesdeAranceles($idOs, $fechaDesde, $fechaHasta);

        if ($n < 1) {
            flash_set('No se actualizó ninguna orden (sin pendientes A en el período o sin arancel para alguna práctica).');
        } else {
            flash_set($n === 1
                ? '1 orden pendiente actualizada desde aranceles.'
                : $n . ' órdenes pendientes actualizadas desde aranceles.');
        }

        header('Location: ' . self::redirectUrl($idOs, $fechaDesde, $fechaHasta, 'pendientes'));
        exit;
    }

    private static function normalizarVista(string $vista): string
    {
        return $vista === 'facturadas' ? 'facturadas' : 'pendientes';
    }

    private static function redirectUrl(int $idOs, string $fechaDesde, string $fechaHasta, string $vista = 'pendientes'): string
    {
        $q = [
            'buscar' => '1',
            'idobrasocial' => (string) $idOs,
            'vista' => self::normalizarVista($vista),
        ];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $q['fecha_desde'] = $fechaDesde;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $q['fecha_hasta'] = $fechaHasta;
        }

        return '/facturacion_ordenes.php?' . http_build_query($q);
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
