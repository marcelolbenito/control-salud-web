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
                  AND d.solo_facturacion = 0
                  AND a.activo = 1 AND a.deleted_at IS NULL
                ORDER BY a.orden, d.nombre";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @return int[]
     */
    public function findSoloFacturacionIds(): array
    {
        $rows = $this->db->query(
            "SELECT id FROM lab_determinaciones
             WHERE solo_facturacion = 1 AND activo = 1 AND deleted_at IS NULL"
        )->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $rows);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listarAreas(): array
    {
        return $this->db->query(
            "SELECT id, codigo, nombre FROM lab_areas
             WHERE activo = 1 AND deleted_at IS NULL
             ORDER BY orden, nombre"
        )->fetchAll();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findAllParaAbm(): array
    {
        $sql = "SELECT d.id, d.area_id, d.codigo, d.nombre, d.nombre_corto, d.unidad,
                       d.metodo, d.tipo_resultado, d.decimales, d.precio, d.solo_facturacion,
                       a.codigo AS area_codigo, a.nombre AS area_nombre,
                       n.unidades AS nbu_unidades
                FROM lab_determinaciones d
                JOIN lab_areas a ON a.id = d.area_id
                LEFT JOIN lab_nbu_determinaciones n
                       ON n.determinacion_id = d.id AND n.deleted_at IS NULL
                WHERE d.activo = 1 AND d.deleted_at IS NULL
                ORDER BY a.orden, d.nombre";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByIdParaAbm(int $id): ?array
    {
        $sql = "SELECT d.id, d.area_id, d.codigo, d.nombre, d.nombre_corto, d.unidad,
                       d.metodo, d.tipo_resultado, d.decimales, d.precio, d.solo_facturacion,
                       n.unidades AS nbu_unidades
                FROM lab_determinaciones d
                LEFT JOIN lab_nbu_determinaciones n
                       ON n.determinacion_id = d.id AND n.deleted_at IS NULL
                WHERE d.id = :id AND d.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function codigoExiste(string $codigo, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM lab_determinaciones
                WHERE codigo = :c AND deleted_at IS NULL";
        $params = [':c' => $codigo];
        if ($exceptId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $exceptId;
        }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array<string,mixed> $d
     */
    public function crear(array $d): int
    {
        $sql = "INSERT INTO lab_determinaciones
                (area_id, codigo, nombre, nombre_corto, unidad, metodo,
                 tipo_resultado, decimales, precio, solo_facturacion, activo)
                VALUES (:area, :codigo, :nombre, :corto, :unidad, :metodo,
                        :tipo, :decimales, :precio, :solo_fact, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':area'      => (int) $d['area_id'],
            ':codigo'    => (string) $d['codigo'],
            ':nombre'    => (string) $d['nombre'],
            ':corto'     => $d['nombre_corto'] !== null ? (string) $d['nombre_corto'] : null,
            ':unidad'    => (string) ($d['unidad'] ?? ''),
            ':metodo'    => $d['metodo'] !== null ? (string) $d['metodo'] : null,
            ':tipo'      => (string) $d['tipo_resultado'],
            ':decimales' => (int) $d['decimales'],
            ':precio'    => $d['precio'] !== null ? (float) $d['precio'] : null,
            ':solo_fact' => !empty($d['solo_facturacion']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string,mixed> $d
     */
    public function actualizar(int $id, array $d): bool
    {
        $sql = "UPDATE lab_determinaciones
                SET area_id = :area, codigo = :codigo, nombre = :nombre,
                    nombre_corto = :corto, unidad = :unidad, metodo = :metodo,
                    tipo_resultado = :tipo, decimales = :decimales, precio = :precio,
                    solo_facturacion = :solo_fact
                WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':area'      => (int) $d['area_id'],
            ':codigo'    => (string) $d['codigo'],
            ':nombre'    => (string) $d['nombre'],
            ':corto'     => $d['nombre_corto'] !== null ? (string) $d['nombre_corto'] : null,
            ':unidad'    => (string) ($d['unidad'] ?? ''),
            ':metodo'    => $d['metodo'] !== null ? (string) $d['metodo'] : null,
            ':tipo'      => (string) $d['tipo_resultado'],
            ':decimales' => (int) $d['decimales'],
            ':precio'    => $d['precio'] !== null ? (float) $d['precio'] : null,
            ':solo_fact' => !empty($d['solo_facturacion']) ? 1 : 0,
            ':id'        => $id,
        ]);
        return $stmt->rowCount() >= 0;
    }

    /**
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
