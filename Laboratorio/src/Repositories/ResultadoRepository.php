<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Resultado;
use PDO;

final class ResultadoRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function insert(Resultado $r): int
    {
        $sql = "INSERT INTO lab_resultados
                (pedido_item_id, valor_numerico, valor_texto, unidad,
                 es_anormal, es_critico, estado,
                 valor_referencia_min, valor_referencia_max, texto_referencia,
                 observaciones,
                 usuario_carga_id,
                 fecha_carga)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $r->pedidoItemId,
            $r->valorNumerico,
            $r->valorTexto,
            $r->unidad,
            (int) $r->esAnormal,
            (int) $r->esCritico,
            $r->estado,
            $r->valorReferenciaMin,
            $r->valorReferenciaMax,
            $r->textoReferencia,
            $r->observaciones,
            $r->usuarioCargaId,
            $r->fechaCarga,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, pedido_item_id, valor_numerico, valor_texto, unidad,
                       es_anormal, es_critico, estado,
                       valor_referencia_min, valor_referencia_max, texto_referencia,
                       observaciones,
                       usuario_carga_id,
                       fecha_carga,
                       created_at, updated_at
                FROM lab_resultados
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByPedidoItemId(int $pedidoItemId): ?array
    {
        $sql = "SELECT id, pedido_item_id, valor_numerico, valor_texto, unidad,
                       es_anormal, es_critico, estado,
                       valor_referencia_min, valor_referencia_max, texto_referencia,
                       observaciones,
                       usuario_carga_id,
                       fecha_carga,
                       created_at, updated_at
                FROM lab_resultados
                WHERE pedido_item_id = :pid AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $pedidoItemId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Lista todos los resultados activos de un pedido (con info de la
     * determinacion para mostrar en la UI o el PDF).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT r.id, r.pedido_item_id,
                       r.valor_numerico, r.valor_texto, r.unidad,
                       r.es_anormal, r.es_critico, r.estado,
                       r.valor_referencia_min, r.valor_referencia_max, r.texto_referencia,
                       r.observaciones,
                       r.usuario_carga_id,
                       r.fecha_carga,
                       pi.determinacion_id,
                       d.codigo AS determinacion_codigo,
                       d.nombre AS determinacion_nombre,
                       d.metodo AS determinacion_metodo,
                       d.tipo_resultado, d.decimales,
                       a.id AS area_id, a.codigo AS area_codigo, a.nombre AS area_nombre,
                       a.orden AS area_orden
                FROM lab_resultados r
                JOIN lab_pedido_items pi ON pi.id = r.pedido_item_id
                JOIN lab_determinaciones d ON d.id = pi.determinacion_id
                JOIN lab_areas a ON a.id = d.area_id
                WHERE pi.pedido_id = :pid
                  AND r.deleted_at IS NULL
                  AND pi.deleted_at IS NULL
                ORDER BY a.orden, d.nombre, r.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $pedidoId]);

        return $stmt->fetchAll();
    }

    /**
     * Actualiza el resultado vivo. SP9: la re-escritura es libre, no requiere
     * pasar por validacion. Cada edicion queda en lab_auditoria (lo registra
     * el service).
     */
    public function update(Resultado $r): bool
    {
        $sql = "UPDATE lab_resultados
                SET valor_numerico = ?,
                    valor_texto = ?,
                    unidad = ?,
                    es_anormal = ?,
                    es_critico = ?,
                    estado = ?,
                    valor_referencia_min = ?,
                    valor_referencia_max = ?,
                    texto_referencia = ?,
                    observaciones = ?,
                    usuario_carga_id = ?,
                    fecha_carga = ?
                WHERE id = ? AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $r->valorNumerico,
            $r->valorTexto,
            $r->unidad,
            (int) $r->esAnormal,
            (int) $r->esCritico,
            $r->estado,
            $r->valorReferenciaMin,
            $r->valorReferenciaMax,
            $r->textoReferencia,
            $r->observaciones,
            $r->usuarioCargaId,
            $r->fechaCarga,
            $r->id,
        ]);

        return $stmt->rowCount() > 0;
    }
}
