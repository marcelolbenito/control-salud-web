<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pago;
use PDO;

final class PagoRepository
{
    private const ORDENES_VALIDOS = [
        'fecha_desc' => 'p.fecha_pago DESC, p.id DESC',
        'fecha_asc'  => 'p.fecha_pago ASC, p.id ASC',
        'monto_desc' => 'p.monto DESC',
        'monto_asc'  => 'p.monto ASC',
    ];

    private const ORDEN_DEFAULT = 'fecha_desc';

    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string,mixed> $filtros: hc_desde, hc_hasta,
     *                                      fecha_desde, fecha_hasta, quien_pago.
     * @return array{pagos: array<int,array<string,mixed>>, total: int, total_monto: string}
     */
    public function buscar(array $filtros, int $limite = 100, int $offset = 0): array
    {
        [$where, $params] = $this->construirWhere($filtros);

        $sqlBase = "FROM lab_pagos p
                    LEFT JOIN lab_pedidos ped ON ped.id = p.pedido_id
                    LEFT JOIN pacientes pac ON pac.id = ped.paciente_id
                    WHERE p.deleted_at IS NULL"
                  . ($where !== '' ? " AND $where" : '');

        // Total y suma.
        $stmtAgg = $this->db->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(p.monto),0) AS total_monto $sqlBase");
        $stmtAgg->execute($params);
        $agg = $stmtAgg->fetch(PDO::FETCH_ASSOC);

        $orden = self::ORDENES_VALIDOS[$filtros['orden'] ?? self::ORDEN_DEFAULT]
              ?? self::ORDENES_VALIDOS[self::ORDEN_DEFAULT];

        $sql = "SELECT p.id, p.pedido_id, p.quien_pago, p.monto,
                       p.fecha_pago, p.medio_pago, p.referencia, p.observaciones,
                       ped.numero AS pedido_numero,
                       pac.nro_hc AS paciente_nro_hc,
                       pac.apellido AS paciente_apellido,
                       pac.nombres AS paciente_nombres
                $sqlBase
                ORDER BY $orden
                LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'pagos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => (int) ($agg['total'] ?? 0),
            'total_monto' => (string) ($agg['total_monto'] ?? '0.00'),
        ];
    }

    /**
     * Ordenes facturadas pero no pagadas por el seguro.
     * @return array<int,array<string,mixed>>
     */
    public function ordenesPendientesSeguro(int $limite = 200): array
    {
        $sql = "SELECT ped.id, ped.numero, ped.fecha_solicitud, ped.monto_seguro,
                       ped.estado_seguro, ped.obra_social_id,
                       pac.nro_hc AS paciente_nro_hc,
                       pac.apellido AS paciente_apellido,
                       pac.nombres AS paciente_nombres,
                       os.nombre AS obra_social_nombre
                FROM lab_pedidos ped
                LEFT JOIN pacientes pac ON pac.id = ped.paciente_id
                LEFT JOIN obras_sociales os ON os.id = ped.obra_social_id
                WHERE ped.deleted_at IS NULL
                  AND ped.estado_seguro IN ('A','F')
                  AND ped.monto_seguro > 0
                ORDER BY ped.fecha_solicitud ASC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?Pago
    {
        $sql = "SELECT id, pedido_id, quien_pago, monto, fecha_pago,
                       medio_pago, referencia, observaciones, usuario_carga_id
                FROM lab_pagos
                WHERE id = :id AND deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Pago::fromRow($row) : null;
    }

    /**
     * @param array<string,mixed> $datos
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO lab_pagos
                (pedido_id, lote_id, quien_pago, monto, fecha_pago, medio_pago,
                 referencia, observaciones, usuario_carga_id)
                VALUES (:pedido, :lote, :quien, :monto, :fecha, :medio, :ref, :obs, :uid)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':pedido' => $datos['pedido_id'],
            ':lote'   => $datos['lote_id'] ?? null,
            ':quien'  => $datos['quien_pago'],
            ':monto'  => $datos['monto'],
            ':fecha'  => $datos['fecha_pago'],
            ':medio'  => $datos['medio_pago'],
            ':ref'    => $datos['referencia'] ?? null,
            ':obs'    => $datos['observaciones'] ?? null,
            ':uid'    => $datos['usuario_carga_id'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string,mixed> $datos
     */
    public function modificar(int $id, array $datos): bool
    {
        $sql = "UPDATE lab_pagos
                SET quien_pago    = :quien,
                    monto         = :monto,
                    fecha_pago    = :fecha,
                    medio_pago    = :medio,
                    referencia    = :ref,
                    observaciones = :obs
                WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':quien'  => $datos['quien_pago'],
            ':monto'  => $datos['monto'],
            ':fecha'  => $datos['fecha_pago'],
            ':medio'  => $datos['medio_pago'],
            ':ref'    => $datos['referencia'] ?? null,
            ':obs'    => $datos['observaciones'] ?? null,
            ':id'     => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function softDelete(int $id): bool
    {
        $sql = "UPDATE lab_pagos
                SET deleted_at = CURRENT_TIMESTAMP
                WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string,mixed> $f
     * @return array{0:string,1:array<string,mixed>}
     */
    private function construirWhere(array $f): array
    {
        $clauses = [];
        $params = [];

        if (!empty($f['quien_pago'])) {
            $clauses[] = 'p.quien_pago = :quien';
            $params[':quien'] = $f['quien_pago'];
        }
        if (!empty($f['hc_desde'])) {
            $clauses[] = 'pac.nro_hc >= :hcd';
            $params[':hcd'] = $f['hc_desde'];
        }
        if (!empty($f['hc_hasta'])) {
            $clauses[] = 'pac.nro_hc <= :hch';
            $params[':hch'] = $f['hc_hasta'];
        }
        if (!empty($f['fecha_desde'])) {
            $clauses[] = 'p.fecha_pago >= :fd';
            $params[':fd'] = $f['fecha_desde'];
        }
        if (!empty($f['fecha_hasta'])) {
            $clauses[] = 'p.fecha_pago <= :fh';
            $params[':fh'] = $f['fecha_hasta'];
        }
        if (!empty($f['pedido_id'])) {
            $clauses[] = 'p.pedido_id = :ped';
            $params[':ped'] = (int) $f['pedido_id'];
        }

        return [implode(' AND ', $clauses), $params];
    }
}
