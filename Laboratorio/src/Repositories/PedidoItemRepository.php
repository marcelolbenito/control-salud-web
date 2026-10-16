<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PedidoItemRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Trae un item de pedido con datos clave de la determinacion (rangos
     * criticos, tipo_resultado, decimales, unidad) y el snapshot del paciente
     * del pedido para que el Service pueda calcular rango aplicable.
     *
     * @return array<string,mixed>|null
     */
    public function findByIdConContexto(int $id): ?array
    {
        $sql = "SELECT pi.id, pi.pedido_id, pi.determinacion_id, pi.perfil_id,
                       pi.estado AS item_estado,
                       d.codigo AS determinacion_codigo,
                       d.nombre AS determinacion_nombre,
                       d.unidad AS determinacion_unidad,
                       d.tipo_resultado, d.decimales,
                       d.valor_critico_min, d.valor_critico_max,
                       p.id AS pedido_id,
                       p.estado AS pedido_estado,
                       p.snapshot_paciente,
                       p.fecha_extraccion
                FROM lab_pedido_items pi
                JOIN lab_determinaciones d ON d.id = pi.determinacion_id
                JOIN lab_pedidos p ON p.id = pi.pedido_id
                WHERE pi.id = :id
                  AND pi.deleted_at IS NULL
                  AND p.deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        if (!empty($row['snapshot_paciente']) && is_string($row['snapshot_paciente'])) {
            $decoded = json_decode($row['snapshot_paciente'], true);
            $row['snapshot_paciente'] = is_array($decoded) ? $decoded : null;
        }

        return $row;
    }

    public function updateEstado(int $id, string $nuevoEstado): bool
    {
        $sql = "UPDATE lab_pedido_items
                SET estado = :estado
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estado' => $nuevoEstado,
            ':id'     => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Soft-delete de un item. Se usa al sacar un analisis de un pedido ya
     * creado: nunca borramos fisico datos clinicos.
     */
    public function softDelete(int $id): bool
    {
        $sql = "UPDATE lab_pedido_items
                SET deleted_at = CURRENT_TIMESTAMP
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Determinacion_ids de todos los items vivos de un pedido (incluye el acto
     * bioquimico / solo_facturacion). Sirve para deduplicar al agregar items.
     *
     * @return array<int,int>
     */
    public function listarDeterminacionIdsByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT determinacion_id
                FROM lab_pedido_items
                WHERE pedido_id = :pid AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $pedidoId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'determinacion_id'));
    }

    /**
     * Devuelve solo los estados de los items vivos de un pedido.
     * Sirve para decidir si el pedido transiciona a 'completo'.
     *
     * @return array<int,string>
     */
    public function listarEstadosByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT estado
                FROM lab_pedido_items
                WHERE pedido_id = :pid AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $pedidoId]);

        return array_column($stmt->fetchAll(), 'estado');
    }
}
