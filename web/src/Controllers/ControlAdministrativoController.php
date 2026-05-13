<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Repositories/ControlAdministrativoRepository.php';
require_once dirname(__DIR__) . '/Repositories/DoctoresRepository.php';

final class ControlAdministrativoController
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
        $fecha = trim((string) ($_GET['fecha'] ?? ''));
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $doctorFiltro = (int) ($_GET['doctor'] ?? 0);
        $filtro = trim((string) ($_GET['filtro'] ?? 'todos'));
        $filtrosValidos = [
            'todos',
            'atendidos_sin_gestion',
            'atendidos_sin_orden',
            'atendidos_sin_pago',
            'pagos_sin_caja',
            'llegados_sin_gestion',
            'con_orden_sin_pago',
        ];
        if (!in_array($filtro, $filtrosValidos, true)) {
            $filtro = 'todos';
        }

        $repo = new ControlAdministrativoRepository($this->pdo, user_clinica_id($this->user));
        $docRepo = new DoctoresRepository($this->pdo, user_clinica_id($this->user));
        $doctores = $docRepo->listAllOrdered();
        $rowsBase = $repo->listDia($fecha, $doctorFiltro);
        $resumen = self::resumen($rowsBase);
        $rows = self::filtrar($rowsBase, $filtro);
        $fechaPrev = date('Y-m-d', strtotime($fecha . ' -1 day'));
        $fechaNext = date('Y-m-d', strtotime($fecha . ' +1 day'));

        $body = $this->renderView('agenda/control_administrativo', [
            'fecha' => $fecha,
            'fechaPrev' => $fechaPrev,
            'fechaNext' => $fechaNext,
            'doctorFiltro' => $doctorFiltro,
            'filtro' => $filtro,
            'doctores' => $doctores,
            'rows' => $rows,
            'resumen' => $resumen,
            'agendaDisponible' => $repo->agendaDisponible(),
        ]);
        layout_render('Control diario', $body, $this->user);
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private static function filtrar(array $rows, string $filtro): array
    {
        if ($filtro === 'todos') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $r) use ($filtro): bool {
            $s = self::estadoAdmin($r);

            switch ($filtro) {
                case 'atendidos_sin_gestion':
                    return $s['atendido'] && !$s['tiene_orden'] && !$s['tiene_pago'];
                case 'atendidos_sin_orden':
                    return $s['atendido'] && !$s['tiene_orden'];
                case 'atendidos_sin_pago':
                    return $s['atendido'] && !$s['tiene_pago'];
                case 'pagos_sin_caja':
                    return $s['tiene_pago'] && !$s['tiene_caja'];
                case 'llegados_sin_gestion':
                    return $s['llegado'] && !$s['atendido'] && !$s['tiene_orden'] && !$s['tiene_pago'];
                case 'con_orden_sin_pago':
                    return $s['tiene_orden'] && !$s['tiene_pago'];
            }

            return true;
        }));
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return array<string,int>
     */
    private static function resumen(array $rows): array
    {
        $out = [
            'total' => count($rows),
            'atendidos' => 0,
            'atendidos_sin_gestion' => 0,
            'atendidos_sin_orden' => 0,
            'atendidos_sin_pago' => 0,
            'pagos_sin_caja' => 0,
            'llegados_sin_gestion' => 0,
        ];

        foreach ($rows as $r) {
            $s = self::estadoAdmin($r);
            if ($s['atendido']) {
                $out['atendidos']++;
                if (!$s['tiene_orden'] && !$s['tiene_pago']) {
                    $out['atendidos_sin_gestion']++;
                }
                if (!$s['tiene_orden']) {
                    $out['atendidos_sin_orden']++;
                }
                if (!$s['tiene_pago']) {
                    $out['atendidos_sin_pago']++;
                }
            }
            if ($s['tiene_pago'] && !$s['tiene_caja']) {
                $out['pagos_sin_caja']++;
            }
            if ($s['llegado'] && !$s['atendido'] && !$s['tiene_orden'] && !$s['tiene_pago']) {
                $out['llegados_sin_gestion']++;
            }
        }

        return $out;
    }

    /**
     * @return array{atendido:bool,llegado:bool,tiene_orden:bool,tiene_pago:bool,tiene_caja:bool}
     */
    public static function estadoAdmin(array $r): array
    {
        return [
            'atendido' => !empty($r['atendido']) || (string) ($r['estado'] ?? '') === 'atendido',
            'llegado' => !empty($r['llegado']),
            'tiene_orden' => !empty($r['tiene_orden']) || (int) ($r['orden_referencia'] ?? 0) > 0,
            'tiene_pago' => (float) ($r['pagos_total'] ?? 0) > 0.00001,
            'tiene_caja' => abs((float) ($r['caja_total'] ?? 0)) > 0.00001,
        ];
    }

    private function renderView(string $view, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';

        return (string) ob_get_clean();
    }
}
