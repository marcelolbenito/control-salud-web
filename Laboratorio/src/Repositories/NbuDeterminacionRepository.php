<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NbuDeterminacionRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findUnidades(int $determinacionId): ?float
    {
        $stmt = $this->db->prepare(
            'SELECT unidades FROM lab_nbu_determinaciones
             WHERE determinacion_id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $determinacionId]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }
        return (float) $row['unidades'];
    }

    /**
     * Lista todas las determinaciones activas con su NBU (LEFT JOIN para incluir
     * las que aun no tienen unidades cargadas).
     *
     * @return array<int,array{determinacion_id:int, codigo:string, nombre:string, area:string, unidades:?float}>
     */
    public function listAll(): array
    {
        $sql = "SELECT d.id   AS determinacion_id,
                       d.codigo,
                       d.nombre,
                       a.nombre AS area,
                       n.unidades
                FROM lab_determinaciones d
                LEFT JOIN lab_areas a ON a.id = d.area_id
                LEFT JOIN lab_nbu_determinaciones n
                       ON n.determinacion_id = d.id AND n.deleted_at IS NULL
                WHERE d.deleted_at IS NULL AND d.activo = 1
                ORDER BY a.nombre ASC, d.nombre ASC";

        $rows = $this->db->query($sql)->fetchAll();

        return array_map(static fn(array $r): array => [
            'determinacion_id' => (int) $r['determinacion_id'],
            'codigo'           => (string) $r['codigo'],
            'nombre'           => (string) $r['nombre'],
            'area'             => (string) ($r['area'] ?? ''),
            'unidades'         => $r['unidades'] !== null ? (float) $r['unidades'] : null,
        ], $rows);
    }

    public function upsert(int $determinacionId, float $unidades): void
    {
        $sql = "INSERT INTO lab_nbu_determinaciones (determinacion_id, unidades)
                VALUES (:id, :u)
                ON DUPLICATE KEY UPDATE unidades = VALUES(unidades), deleted_at = NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $determinacionId,
            ':u'  => $unidades,
        ]);
    }
}
