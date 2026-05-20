<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Reportes de recaudacion del laboratorio.
 *
 * Lee directamente de lab_pagos (que el sistema mayor llena cuando los
 * empleados cargan pagos). Este modulo solo CONSULTA: no inserta ni
 * modifica filas en lab_pagos.
 *
 * Las queries agrupan por quien_pago para devolver el desglose entre
 * 'paciente' (particular) y 'seguro' (obra social).
 */
final class ReporteRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Recaudacion total de UN mes especifico.
     *
     * @param string $mesYYYYMM Formato 'YYYY-MM'.
     * @return array{mes:string, particular:float, obra_social:float, total:float, cantidad_pagos:int}
     */
    public function recaudacionDelMes(string $mesYYYYMM): array
    {
        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN quien_pago = 'paciente' THEN monto ELSE 0 END), 0) AS particular,
                    COALESCE(SUM(CASE WHEN quien_pago = 'seguro'   THEN monto ELSE 0 END), 0) AS obra_social,
                    COALESCE(SUM(monto), 0) AS total,
                    COUNT(*) AS cantidad_pagos
                FROM lab_pagos
                WHERE deleted_at IS NULL
                  AND DATE_FORMAT(fecha_pago, '%Y-%m') = :mes";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mes' => $mesYYYYMM]);
        $row = $stmt->fetch();

        return [
            'mes'            => $mesYYYYMM,
            'particular'     => (float) ($row['particular']     ?? 0),
            'obra_social'    => (float) ($row['obra_social']    ?? 0),
            'total'          => (float) ($row['total']          ?? 0),
            'cantidad_pagos' => (int)   ($row['cantidad_pagos'] ?? 0),
        ];
    }

    /**
     * Serie historica: recaudacion mes por mes en un rango.
     * Solo devuelve meses que tengan al menos un pago (no rellena ceros).
     *
     * @param string $mesDesde 'YYYY-MM'
     * @param string $mesHasta 'YYYY-MM'
     * @return array<int,array{mes:string, particular:float, obra_social:float, total:float, cantidad_pagos:int}>
     */
    public function recaudacionPorMes(string $mesDesde, string $mesHasta): array
    {
        $sql = "SELECT
                    DATE_FORMAT(fecha_pago, '%Y-%m') AS mes,
                    COALESCE(SUM(CASE WHEN quien_pago = 'paciente' THEN monto ELSE 0 END), 0) AS particular,
                    COALESCE(SUM(CASE WHEN quien_pago = 'seguro'   THEN monto ELSE 0 END), 0) AS obra_social,
                    COALESCE(SUM(monto), 0) AS total,
                    COUNT(*) AS cantidad_pagos
                FROM lab_pagos
                WHERE deleted_at IS NULL
                  AND DATE_FORMAT(fecha_pago, '%Y-%m') BETWEEN :desde AND :hasta
                GROUP BY mes
                ORDER BY mes DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':desde' => $mesDesde,
            ':hasta' => $mesHasta,
        ]);

        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[] = [
                'mes'            => (string) $row['mes'],
                'particular'     => (float)  $row['particular'],
                'obra_social'    => (float)  $row['obra_social'],
                'total'          => (float)  $row['total'],
                'cantidad_pagos' => (int)    $row['cantidad_pagos'],
            ];
        }
        return $out;
    }
}
