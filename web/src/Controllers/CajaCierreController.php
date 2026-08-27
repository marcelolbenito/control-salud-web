<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/CajaCierreRepository.php';

final class CajaCierreController
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
        $repo = new CajaCierreRepository($this->pdo, user_clinica_id($this->user));
        $fecha = self::fechaParam();
        $turno = self::turnoParam();
        $doctorDetalle = max(0, (int) ($_GET['doctor'] ?? 0));
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $fecha = self::fechaParam($_POST);
            $turno = self::turnoParam($_POST);
            $efectivoTxt = trim(str_replace(',', '.', (string) ($_POST['efectivo_declarado'] ?? '')));
            $observaciones = trim((string) ($_POST['observaciones'] ?? ''));
            if (!$repo->cierresTableExists()) {
                $error = 'Falta aplicar la migración sql/migration_031_caja_cierres.sql.';
            } elseif ($efectivoTxt === '' || !is_numeric($efectivoTxt)) {
                $error = 'Indicá el efectivo declarado.';
            } elseif ($repo->findCierre($fecha, $turno) !== null) {
                $error = 'La caja ya está cerrada para esa fecha y turno. El cierre queda como registro histórico.';
            } else {
                $res = $repo->resumenMovimientos($fecha, $turno);
                $medios = $repo->resumenPorMedioPago($fecha, $turno);
                $efectivoSistema = 0.0;
                foreach ($medios as $m) {
                    if (($m['modopago'] ?? -1) === 0) {
                        $efectivoSistema = (float) ($m['total'] ?? 0);
                        break;
                    }
                }
                $efectivo = (float) $efectivoTxt;
                $repo->guardarCierre([
                    'fecha' => $fecha,
                    'turno' => $turno,
                    'total_ingresos' => $res['ingresos'],
                    'total_egresos' => $res['egresos'],
                    'total_sistema' => $res['total'],
                    'efectivo_declarado' => $efectivo,
                    'diferencia' => $efectivo - $efectivoSistema,
                    'estado' => 'cerrada',
                    'observaciones' => $observaciones !== '' ? $observaciones : null,
                    'id_usuario_cierre' => (int) ($this->user['id'] ?? 0),
                ]);
                flash_set('Cierre de caja registrado.');
                header('Location: /caja_cierre.php?fecha=' . rawurlencode($fecha) . '&turno=' . rawurlencode($turno));
                exit;
            }
        }

        $resumen = $repo->resumenMovimientos($fecha, $turno);
        $resumenMedios = $repo->resumenPorMedioPago($fecha, $turno);
        $resumenProfesionales = $repo->resumenPorProfesional($fecha, $turno);
        $movimientos = $repo->listMovimientos($fecha, $turno, $doctorDetalle);
        $cierre = $repo->findCierre($fecha, $turno);
        $cierres = $repo->ultimosCierres();
        $body = $this->renderView('caja/cierre', [
            'fecha' => $fecha,
            'turno' => $turno,
            'resumen' => $resumen,
            'resumenMedios' => $resumenMedios,
            'resumenProfesionales' => $resumenProfesionales,
            'movimientos' => $movimientos,
            'doctorDetalle' => $doctorDetalle,
            'cierre' => $cierre,
            'cierres' => $cierres,
            'error' => $error,
            'cajaDisponible' => $repo->cajaTableExists(),
            'cierresDisponible' => $repo->cierresTableExists(),
        ]);
        layout_render('Cierre de caja', $body, $this->user);
    }

    /**
     * @param array<string,mixed>|null $src
     */
    private static function fechaParam(?array $src = null): string
    {
        $src = $src ?? $_GET;
        $fecha = trim((string) ($src['fecha'] ?? ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : date('Y-m-d');
    }

    /**
     * @param array<string,mixed>|null $src
     */
    private static function turnoParam(?array $src = null): string
    {
        $src = $src ?? $_GET;
        $turno = strtolower(trim((string) ($src['turno'] ?? 'dia')));

        return in_array($turno, ['dia', 'mañana', 'tarde'], true) ? $turno : 'dia';
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
