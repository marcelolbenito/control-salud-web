<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class DeterminacionRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Lista todas las determinaciones activas, con datos del area asociada.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAllActivas(): array
    {
        $sql = "SELECT d.id, d.codigo, d.nombre, d.nombre_corto, d.unidad,
                       d.tipo_resultado, d.decimales, d.precio,
                       a.id AS area_id, a.codigo AS area_codigo, a.nombre AS area_nombre
                FROM lab_determinaciones d
                JOIN lab_areas a ON a.id = d.area_id
                WHERE d.activo = 1 AND d.deleted_at IS NULL
                  AND a.activo = 1 AND a.deleted_at IS NULL
                ORDER BY a.orden, d.nombre";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Devuelve solo las determinaciones activas que coincidan con los IDs.
     * Sirve para validar que un set de IDs sea valido antes de crear el pedido.
     *
     * @param array<int,int> $ids
     * @return array<int,array<string,mixed>>
     */
    public function findActivasByIds(array $ids): array
    {
        if (count($ids) === 0) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, codigo, nombre, unidad, precio
                FROM lab_determinaciones
                WHERE id IN ($placeholders)
                  AND activo = 1 AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($ids));

        return $stmt->fetchAll();
    }
}
