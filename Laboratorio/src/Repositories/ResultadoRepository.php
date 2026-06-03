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
     * Resultados anteriores del mismo paciente para un conjunto de determinaciones.
     *
     * @param int[] $determinacionIds
     * @return array<int,array<int,array<string,mixed>>>
     */
    public function findAnterioresPorPaciente(
        ?int $pacienteId,
        ?string $dni,
        array $determinacionIds,
        int $excludePedidoId,
        int $limitPorDet = 5
    ): array {
        $tieneDni = $dni !== null && $dni !== '';
        if ($determinacionIds === [] || ($pacienteId === null && !$tieneDni)) {
            return [];
        }

        $matchParts = [];
        $params = [];
        if ($pacienteId !== null) {
            $matchParts[] = 'p.paciente_id = ?';
            $params[] = $pacienteId;
        }
        if ($tieneDni) {
            $matchParts[] = "JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente, '$.dni')) = ?";
            $params[] = $dni;
        }
        $matchSql = '(' . implode(' OR ', $matchParts) . ')';

        $detPlaceholders = implode(',', array_fill(0, count($determinacionIds), '?'));

        $sql = "SELECT pi.determinacion_id,
                       r.valor_numerico, r.valor_texto, r.unidad, r.es_anormal,
                       COALESCE(p.fecha_extraccion, p.fecha_solicitud) AS fecha,
                       p.numero AS pedido_numero
                FROM lab_resultados r
                JOIN lab_pedido_items pi ON pi.id = r.pedido_item_id
                JOIN lab_pedidos p ON p.id = pi.pedido_id
                WHERE $matchSql
                  AND p.id <> ?
                  AND pi.determinacion_id IN ($detPlaceholders)
                  AND r.deleted_at IS NULL
                  AND pi.deleted_at IS NULL
                  AND p.deleted_at IS NULL
                ORDER BY pi.determinacion_id, fecha DESC, p.id DESC";

        $params[] = $excludePedidoId;
        foreach ($determinacionIds as $d) {
            $params[] = (int) $d;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $det = (int) $row['determinacion_id'];
            if (!isset($map[$det])) {
                $map[$det] = [];
            }
            if (count($map[$det]) >= $limitPorDet) {
                continue;
            }
            $map[$det][] = [
                'valor_numerico' => $row['valor_numerico'],
                'valor_texto'    => $row['valor_texto'],
                'unidad'         => $row['unidad'],
                'es_anormal'     => (int) $row['es_anormal'],
                'fecha'          => $row['fecha'],
                'pedido_numero'  => $row['pedido_numero'],
            ];
        }

        return $map;
    }

    public function deleteByPedidoItemId(int $pedidoItemId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM lab_resultados WHERE pedido_item_id = :pid');
        $stmt->execute([':pid' => $pedidoItemId]);
        return $stmt->rowCount() > 0;
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
