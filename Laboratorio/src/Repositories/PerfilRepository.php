<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PerfilRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Devuelve todos los perfiles activos junto con sus determinaciones.
     *
     * @return array<int,array{
     *   id:int, codigo:string, nombre:string, descripcion:?string,
     *   determinaciones: array<int,array{id:int, codigo:string, nombre:string}>
     * }>
     */
    public function findAllActivosConDeterminaciones(): array
    {
        $sql = "SELECT p.id AS perfil_id, p.codigo AS perfil_codigo, p.nombre AS perfil_nombre,
                       p.descripcion,
                       d.id AS det_id, d.codigo AS det_codigo, d.nombre AS det_nombre,
                       pd.orden
                FROM lab_perfiles p
                LEFT JOIN lab_perfil_determinaciones pd ON pd.perfil_id = p.id
                LEFT JOIN lab_determinaciones d
                       ON d.id = pd.determinacion_id
                      AND d.activo = 1
                      AND d.deleted_at IS NULL
                WHERE p.activo = 1 AND p.deleted_at IS NULL
                ORDER BY p.nombre, pd.orden";

        $rows = $this->db->query($sql)->fetchAll();

        $perfiles = [];
        foreach ($rows as $row) {
            $pid = (int) $row['perfil_id'];
            if (!isset($perfiles[$pid])) {
                $perfiles[$pid] = [
                    'id'              => $pid,
                    'codigo'          => $row['perfil_codigo'],
                    'nombre'          => $row['perfil_nombre'],
                    'descripcion'     => $row['descripcion'],
                    'determinaciones' => [],
                ];
            }
            if ($row['det_id'] !== null) {
                $perfiles[$pid]['determinaciones'][] = [
                    'id'     => (int) $row['det_id'],
                    'codigo' => $row['det_codigo'],
                    'nombre' => $row['det_nombre'],
                ];
            }
        }

        return array_values($perfiles);
    }

    /**
     * Devuelve los IDs de determinaciones activas que componen un perfil,
     * en el orden definido en lab_perfil_determinaciones.
     *
     * @return array<int,int>
     */
    public function getDeterminacionIdsByPerfilId(int $perfilId): array
    {
        $sql = "SELECT pd.determinacion_id
                FROM lab_perfil_determinaciones pd
                JOIN lab_determinaciones d ON d.id = pd.determinacion_id
                WHERE pd.perfil_id = :perfil_id
                  AND d.activo = 1
                  AND d.deleted_at IS NULL
                ORDER BY pd.orden, pd.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':perfil_id' => $perfilId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'determinacion_id'));
    }

    /**
     * Datos basicos de un perfil por id (para encabezados de planillas).
     *
     * @return array{id:int,codigo:string,nombre:string,descripcion:?string}|null
     */
    public function findById(int $perfilId): ?array
    {
        $sql = "SELECT id, codigo, nombre, descripcion
                FROM lab_perfiles
                WHERE id = :id AND activo = 1 AND deleted_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $perfilId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Datos basicos (id, codigo, nombre, unidad) de varias determinaciones,
     * preservando el orden de los ids recibidos.
     *
     * @param array<int,int> $detIds
     * @return array<int,array{id:int,codigo:string,nombre:string,nombre_corto:?string,unidad:string}>
     */
    public function findDeterminacionesByIds(array $detIds): array
    {
        if ($detIds === []) {
            return [];
        }

        $place = implode(',', array_fill(0, count($detIds), '?'));
        $sql = "SELECT id, codigo, nombre, nombre_corto, unidad
                FROM lab_determinaciones
                WHERE id IN ($place) AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values(array_map('intval', $detIds)));

        $byId = [];
        foreach ($stmt->fetchAll() as $row) {
            $byId[(int) $row['id']] = [
                'id'           => (int) $row['id'],
                'codigo'       => (string) $row['codigo'],
                'nombre'       => (string) $row['nombre'],
                'nombre_corto' => $row['nombre_corto'] !== null ? (string) $row['nombre_corto'] : null,
                'unidad'       => (string) $row['unidad'],
            ];
        }

        // Mantener el orden segun $detIds.
        $out = [];
        foreach ($detIds as $id) {
            if (isset($byId[(int) $id])) {
                $out[] = $byId[(int) $id];
            }
        }
        return $out;
    }
}
