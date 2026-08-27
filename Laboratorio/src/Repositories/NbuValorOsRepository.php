<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Integration\ControlSaludIntegration;
use PDO;

/**
 * Vigencias del valor unitario NBU por obra social.
 *
 * Una OS puede tener N vigencias historicas. Vigencia activa = fecha_hasta IS NULL
 * o futura. Una sola vigencia es valida para una fecha dada (no se permite overlap;
 * al crear una nueva, la anterior abierta se cierra automaticamente).
 */
final class NbuValorOsRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Valor vigente al `fecha` (YYYY-MM-DD). Null si no hay vigencia que aplique.
     *
     * Regla unica (step-function): se toma la vigencia con la fecha_desde mas
     * reciente que sea <= `fecha`. No se filtra por fecha_hasta: el valor cambia
     * recien cuando empieza la vigencia siguiente. Asi el resultado es siempre
     * inequivoco aunque hubiera rangos solapados.
     */
    public function findValorAt(int $obraSocialId, string $fecha): ?float
    {
        $stmt = $this->db->prepare(
            'SELECT valor_unitario FROM lab_nbu_valores_os
             WHERE obra_social_id = :id
               AND deleted_at IS NULL
               AND fecha_desde <= :fecha
             ORDER BY fecha_desde DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([':id' => $obraSocialId, ':fecha' => $fecha]);
        $row = $stmt->fetch();

        return $row === false ? null : (float) $row['valor_unitario'];
    }

    /**
     * Listado completo de OS activas con la vigencia actual (fecha_hasta IS NULL).
     * LEFT JOIN: incluye OS sin ninguna vigencia.
     *
     * @return array<int,array{obra_social_id:int, nombre:string, valor_unitario:?float, fecha_desde:?string, updated_at:?string}>
     */
    public function listAllVigentes(): array
    {
        $table = ControlSaludIntegration::obraSocialTable();
        $whereActivos = ControlSaludIntegration::obraSocialWhereActivosSql('os');

        $sql = "SELECT os.id AS obra_social_id,
                       os.nombre,
                       v.valor_unitario,
                       v.fecha_desde,
                       v.updated_at
                FROM {$table} os
                LEFT JOIN lab_nbu_valores_os v
                       ON v.obra_social_id = os.id
                      AND v.deleted_at IS NULL
                      AND v.fecha_hasta IS NULL
                WHERE {$whereActivos}
                ORDER BY os.nombre ASC";

        $rows = $this->db->query($sql)->fetchAll();

        return array_map(static fn(array $r): array => [
            'obra_social_id' => (int) $r['obra_social_id'],
            'nombre'         => (string) $r['nombre'],
            'valor_unitario' => $r['valor_unitario'] !== null ? (float) $r['valor_unitario'] : null,
            'fecha_desde'    => $r['fecha_desde'] !== null ? (string) $r['fecha_desde'] : null,
            'updated_at'     => $r['updated_at'] !== null ? (string) $r['updated_at'] : null,
        ], $rows);
    }

    /**
     * Historial de vigencias de una OS, ordenado por fecha_desde desc.
     *
     * @return array<int,array{id:int, valor_unitario:float, fecha_desde:string, fecha_hasta:?string}>
     */
    public function listarVigencias(int $obraSocialId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, valor_unitario, fecha_desde, fecha_hasta
             FROM lab_nbu_valores_os
             WHERE obra_social_id = :id AND deleted_at IS NULL
             ORDER BY fecha_desde DESC, id DESC'
        );
        $stmt->execute([':id' => $obraSocialId]);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static fn(array $r): array => [
            'id'             => (int) $r['id'],
            'valor_unitario' => (float) $r['valor_unitario'],
            'fecha_desde'    => (string) $r['fecha_desde'],
            'fecha_hasta'    => $r['fecha_hasta'] !== null ? (string) $r['fecha_hasta'] : null,
        ], $rows);
    }

    /**
     * Crea (o actualiza) una vigencia y deja la linea de tiempo de la OS sin
     * superposiciones, sin importar el orden de carga.
     *
     * @return int Id de la vigencia creada o actualizada
     */
    public function crearVigencia(int $obraSocialId, float $valorUnitario, string $fechaDesde): int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM lab_nbu_valores_os
             WHERE obra_social_id = :id AND deleted_at IS NULL AND fecha_desde = :fd
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':id' => $obraSocialId, ':fd' => $fechaDesde]);
        $existenteId = $stmt->fetchColumn();

        if ($existenteId !== false) {
            $vigenciaId = (int) $existenteId;
            $upd = $this->db->prepare(
                'UPDATE lab_nbu_valores_os SET valor_unitario = :v WHERE id = :id'
            );
            $upd->execute([':v' => $valorUnitario, ':id' => $vigenciaId]);
        } else {
            $ins = $this->db->prepare(
                'INSERT INTO lab_nbu_valores_os (obra_social_id, valor_unitario, fecha_desde, fecha_hasta)
                 VALUES (:id, :v, :fd, NULL)'
            );
            $ins->execute([':id' => $obraSocialId, ':v' => $valorUnitario, ':fd' => $fechaDesde]);
            $vigenciaId = (int) $this->db->lastInsertId();
        }

        $this->recomputarTimeline($obraSocialId);

        return $vigenciaId;
    }

    /**
     * Recalcula los fecha_hasta de todas las vigencias activas de la OS.
     */
    public function recomputarTimeline(int $obraSocialId): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, fecha_desde FROM lab_nbu_valores_os
             WHERE obra_social_id = :id AND deleted_at IS NULL
             ORDER BY fecha_desde ASC, id ASC'
        );
        $stmt->execute([':id' => $obraSocialId]);
        $rows = $stmt->fetchAll() ?: [];

        $upd = $this->db->prepare('UPDATE lab_nbu_valores_os SET fecha_hasta = :fh WHERE id = :id');

        $n = count($rows);
        foreach ($rows as $i => $row) {
            if ($i + 1 < $n) {
                $fechaHasta = (new \DateTimeImmutable((string) $rows[$i + 1]['fecha_desde']))
                    ->modify('-1 day')->format('Y-m-d');
            } else {
                $fechaHasta = null;
            }
            $upd->execute([':fh' => $fechaHasta, ':id' => (int) $row['id']]);
        }
    }

    public function softDelete(int $vigenciaId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE lab_nbu_valores_os SET deleted_at = CURRENT_TIMESTAMP
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $vigenciaId]);
        return $stmt->rowCount() > 0;
    }
}
