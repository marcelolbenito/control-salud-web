<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LoteOs;
use PDO;

final class LoteOsRepository
{
    private const ORDENES_VALIDOS = [
        'fecha_desc' => 'l.fecha_generacion DESC, l.id DESC',
        'fecha_asc'  => 'l.fecha_generacion ASC, l.id ASC',
        'numero_desc' => 'l.numero DESC',
        'numero_asc'  => 'l.numero ASC',
        'monto_desc' => 'l.monto_total DESC',
    ];

    private const ORDEN_DEFAULT = 'fecha_desc';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Siguiente correlativo para el formato L-YYYY-NNNNN.
     * Devuelve 1 si no hay lotes del año.
     */
    public function getNextNumeroForYear(int $year): int
    {
        $prefix = sprintf('L-%04d-', $year);
        $sql = "SELECT MAX(CAST(SUBSTRING(numero, ?) AS UNSIGNED)) AS last_num
                FROM lab_lotes_os
                WHERE numero LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            strlen($prefix) + 1,
            $prefix . '%',
        ]);
        $row = $stmt->fetch();
        return ((int) ($row['last_num'] ?? 0)) + 1;
    }

    /**
     * Inserta el lote (sin pedidos). Devuelve el id.
     */
    public function insert(LoteOs $lote, ?int $usuarioId): int
    {
        $sql = "INSERT INTO lab_lotes_os
                (numero, obra_social_id, fecha_desde, fecha_hasta, estado,
                 monto_total, cantidad_pedidos, usuario_generacion_id, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $lote->numero,
            $lote->obraSocialId,
            $lote->fechaDesde,
            $lote->fechaHasta,
            $lote->estado,
            $lote->montoTotal,
            $lote->cantidadPedidos,
            $usuarioId,
            $lote->observaciones,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Inserta una fila en el pivot lote-pedido con snapshot de monto_seguro.
     */
    public function insertPivot(int $loteId, int $pedidoId, float $montoSnapshot): void
    {
        $sql = "INSERT INTO lab_lote_pedidos (lote_id, pedido_id, monto_seguro_snapshot)
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$loteId, $pedidoId, $montoSnapshot]);
    }

    /**
     * Actualiza el snapshot del monto del seguro en el pivot.
     */
    public function updatePivotMonto(int $loteId, int $pedidoId, float $monto): void
    {
        $sql = "UPDATE lab_lote_pedidos
                SET monto_seguro_snapshot = :m
                WHERE lote_id = :l AND pedido_id = :p";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':m' => $monto, ':l' => $loteId, ':p' => $pedidoId]);
    }

    /**
     * Marca un item del pedido como excluido del lote (no cubre OS).
     * Si ya estaba excluido, no hace nada (idempotente).
     */
    public function excluirItem(int $loteId, int $pedidoItemId, ?int $usuarioId, ?string $motivo = null): void
    {
        $sql = "INSERT IGNORE INTO lab_lote_pedido_item_excluido
                (lote_id, pedido_item_id, usuario_id, motivo)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$loteId, $pedidoItemId, $usuarioId, $motivo]);
    }

    /**
     * Vuelve a incluir un item previamente excluido.
     */
    public function incluirItem(int $loteId, int $pedidoItemId): void
    {
        $sql = "DELETE FROM lab_lote_pedido_item_excluido
                WHERE lote_id = :l AND pedido_item_id = :i";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':l' => $loteId, ':i' => $pedidoItemId]);
    }

    /**
     * Devuelve true si el item esta excluido en el lote.
     */
    public function itemEstaExcluido(int $loteId, int $pedidoItemId): bool
    {
        $sql = "SELECT 1 FROM lab_lote_pedido_item_excluido
                WHERE lote_id = :l AND pedido_item_id = :i
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':l' => $loteId, ':i' => $pedidoItemId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Verifica que un item pertenezca a un pedido que esta en el lote.
     * Devuelve el pedido_id si es valido, o null.
     */
    public function findPedidoIdDeItemEnLote(int $loteId, int $pedidoItemId): ?int
    {
        $sql = "SELECT i.pedido_id
                FROM lab_pedido_items i
                INNER JOIN lab_lote_pedidos lp
                       ON lp.pedido_id = i.pedido_id AND lp.lote_id = :l
                WHERE i.id = :i AND i.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':l' => $loteId, ':i' => $pedidoItemId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }

    public function deletePivot(int $loteId, int $pedidoId): bool
    {
        $sql = "DELETE FROM lab_lote_pedidos WHERE lote_id = ? AND pedido_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$loteId, $pedidoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Devuelve los pedido_id ya incluidos en otros lotes abierto/cobrado.
     * Util para validar doble-loteo antes de generar.
     *
     * @param int[] $pedidoIds
     * @return int[]
     */
    public function findPedidosYaEnLote(array $pedidoIds, ?int $excluyendoLoteId = null): array
    {
        if ($pedidoIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($pedidoIds), '?'));
        $sql = "SELECT lp.pedido_id
                FROM lab_lote_pedidos lp
                INNER JOIN lab_lotes_os l ON l.id = lp.lote_id
                WHERE lp.pedido_id IN ($placeholders)
                  AND l.estado IN ('abierto','cobrado')
                  AND l.deleted_at IS NULL";
        $params = $pedidoIds;
        if ($excluyendoLoteId !== null) {
            $sql .= " AND l.id <> ?";
            $params[] = $excluyendoLoteId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(static fn($r) => (int) $r['pedido_id'], $stmt->fetchAll());
    }

    public function findById(int $id): ?LoteOs
    {
        $sql = "SELECT id, numero, obra_social_id, fecha_desde, fecha_hasta, estado,
                       monto_total, cantidad_pedidos, fecha_generacion, fecha_cobro,
                       fecha_anulacion, motivo_anulacion, usuario_generacion_id,
                       usuario_cobro_id, usuario_anulacion_id, observaciones
                FROM lab_lotes_os
                WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? LoteOs::fromRow($row) : null;
    }

    /**
     * Devuelve los pedidos del lote con datos para PDF/CSV/listado.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findPedidosDelLote(int $loteId): array
    {
        $sql = "SELECT lp.pedido_id, lp.monto_seguro_snapshot,
                       p.numero AS pedido_numero,
                       p.fecha_solicitud, p.estado_seguro,
                       pac.nro_hc AS paciente_nro_hc,
                       pac.apellido AS paciente_apellido,
                       pac.nombres AS paciente_nombres
                FROM lab_lote_pedidos lp
                INNER JOIN lab_pedidos p ON p.id = lp.pedido_id
                LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                WHERE lp.lote_id = :id
                ORDER BY p.fecha_solicitud ASC, p.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $loteId]);
        return $stmt->fetchAll();
    }

    /**
     * Items del lote a nivel determinacion (para CSV detallado).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findItemsDelLote(int $loteId): array
    {
        $subValor = "(SELECT vv.valor_unitario FROM lab_nbu_valores_os vv
                       WHERE vv.obra_social_id = p.obra_social_id
                         AND vv.deleted_at IS NULL
                         AND vv.fecha_desde <= DATE(p.fecha_solicitud)
                       ORDER BY vv.fecha_desde DESC, vv.id DESC
                       LIMIT 1)";

        $sql = "SELECT lote_numero, pedido_numero, paciente_nro_hc, paciente_nombre,
                       determinacion_codigo, determinacion_nombre,
                       nbu_unidades, nbu_valor_unitario, monto_item
                FROM (
                    SELECT l.numero AS lote_numero, p.numero AS pedido_numero,
                           pac.nro_hc AS paciente_nro_hc,
                           CONCAT_WS(', ', pac.apellido, pac.nombres) AS paciente_nombre,
                           d.codigo AS determinacion_codigo, d.nombre AS determinacion_nombre,
                           n.unidades AS nbu_unidades,
                           $subValor AS nbu_valor_unitario,
                           ROUND(COALESCE(n.unidades, 0) * COALESCE($subValor, 0), 2) AS monto_item,
                           0 AS orden_linea, p.fecha_solicitud AS f_ord, p.id AS ped_ord
                    FROM lab_lote_pedidos lp
                    INNER JOIN lab_lotes_os l ON l.id = lp.lote_id
                    INNER JOIN lab_pedidos p ON p.id = lp.pedido_id
                    INNER JOIN lab_pedido_items i ON i.pedido_id = p.id
                    INNER JOIN lab_determinaciones d ON d.id = i.determinacion_id
                    LEFT JOIN lab_nbu_determinaciones n ON n.determinacion_id = d.id AND n.deleted_at IS NULL
                    LEFT JOIN lab_perfiles pf ON pf.id = i.perfil_id AND pf.deleted_at IS NULL
                    LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                    LEFT JOIN lab_lote_pedido_item_excluido x
                           ON x.pedido_item_id = i.id AND x.lote_id = lp.lote_id
                    WHERE lp.lote_id = :id_a AND i.deleted_at IS NULL AND x.id IS NULL
                      AND (pf.id IS NULL OR pf.nbu_unidades IS NULL)

                    UNION ALL

                    SELECT l.numero, p.numero, pac.nro_hc,
                           CONCAT_WS(', ', pac.apellido, pac.nombres),
                           pf.codigo, pf.nombre,
                           pf.nbu_unidades,
                           $subValor,
                           ROUND(COALESCE(pf.nbu_unidades, 0) * COALESCE($subValor, 0), 2),
                           1 AS orden_linea, p.fecha_solicitud, p.id
                    FROM lab_lote_pedidos lp
                    INNER JOIN lab_lotes_os l ON l.id = lp.lote_id
                    INNER JOIN lab_pedidos p ON p.id = lp.pedido_id
                    LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                    INNER JOIN lab_perfiles pf
                           ON pf.deleted_at IS NULL AND pf.nbu_unidades IS NOT NULL
                          AND pf.id IN (
                              SELECT DISTINCT i.perfil_id FROM lab_pedido_items i
                               WHERE i.pedido_id = p.id AND i.deleted_at IS NULL
                                 AND i.perfil_id IS NOT NULL)
                    WHERE lp.lote_id = :id_b
                ) lineas
                ORDER BY f_ord ASC, ped_ord ASC, orden_linea ASC, determinacion_nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_a' => $loteId, ':id_b' => $loteId]);
        return $stmt->fetchAll();
    }

    /**
     * Detalle item-por-item del lote para la planilla de facturacion (PDF),
     * con los datos del pedido necesarios para agrupar por orden.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findDetalleParaPlanilla(int $loteId): array
    {
        $subValor = "(SELECT v.valor_unitario FROM lab_nbu_valores_os v
                       WHERE v.obra_social_id = p.obra_social_id
                         AND v.deleted_at IS NULL
                         AND v.fecha_desde <= DATE(p.fecha_solicitud)
                       ORDER BY v.fecha_desde DESC, v.id DESC
                       LIMIT 1)";

        $sql = "SELECT pedido_id, pedido_numero, fecha_solicitud, numero_afiliado, snapshot_paciente,
                       paciente_nro_hc, paciente_join,
                       determinacion_codigo, determinacion_nombre,
                       nbu_unidades, nbu_valor_unitario, monto_item, orden_linea
                FROM (
                    SELECT p.id AS pedido_id, p.numero AS pedido_numero, p.fecha_solicitud,
                           p.numero_afiliado, p.snapshot_paciente,
                           pac.nro_hc AS paciente_nro_hc,
                           CONCAT_WS(', ', pac.apellido, pac.nombres) AS paciente_join,
                           d.codigo AS determinacion_codigo, d.nombre AS determinacion_nombre,
                           n.unidades AS nbu_unidades,
                           $subValor AS nbu_valor_unitario,
                           ROUND(COALESCE(n.unidades, 0) * COALESCE($subValor, 0), 2) AS monto_item,
                           0 AS orden_linea, p.id AS ped_ord, p.fecha_solicitud AS f_ord
                    FROM lab_lote_pedidos lp
                    INNER JOIN lab_lotes_os l ON l.id = lp.lote_id
                    INNER JOIN lab_pedidos p ON p.id = lp.pedido_id
                    INNER JOIN lab_pedido_items i ON i.pedido_id = p.id
                    INNER JOIN lab_determinaciones d ON d.id = i.determinacion_id
                    LEFT JOIN lab_nbu_determinaciones n ON n.determinacion_id = d.id AND n.deleted_at IS NULL
                    LEFT JOIN lab_perfiles pf ON pf.id = i.perfil_id AND pf.deleted_at IS NULL
                    LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                    LEFT JOIN lab_lote_pedido_item_excluido x
                           ON x.pedido_item_id = i.id AND x.lote_id = lp.lote_id
                    WHERE lp.lote_id = :id_a AND i.deleted_at IS NULL AND x.id IS NULL
                      AND (pf.id IS NULL OR pf.nbu_unidades IS NULL)

                    UNION ALL

                    SELECT p.id, p.numero, p.fecha_solicitud, p.numero_afiliado, p.snapshot_paciente,
                           pac.nro_hc, CONCAT_WS(', ', pac.apellido, pac.nombres),
                           pf.codigo, pf.nombre,
                           pf.nbu_unidades,
                           $subValor,
                           ROUND(COALESCE(pf.nbu_unidades, 0) * COALESCE($subValor, 0), 2),
                           1 AS orden_linea, p.id, p.fecha_solicitud
                    FROM lab_lote_pedidos lp
                    INNER JOIN lab_lotes_os l ON l.id = lp.lote_id
                    INNER JOIN lab_pedidos p ON p.id = lp.pedido_id
                    LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                    INNER JOIN lab_perfiles pf
                           ON pf.deleted_at IS NULL AND pf.nbu_unidades IS NOT NULL
                          AND pf.id IN (
                              SELECT DISTINCT i.perfil_id FROM lab_pedido_items i
                               WHERE i.pedido_id = p.id AND i.deleted_at IS NULL
                                 AND i.perfil_id IS NOT NULL)
                    WHERE lp.lote_id = :id_b
                ) lineas
                ORDER BY f_ord ASC, ped_ord ASC, orden_linea ASC, determinacion_nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_a' => $loteId, ':id_b' => $loteId]);
        return $stmt->fetchAll();
    }

    /**
     * Pedidos elegibles para entrar a un lote nuevo:
     *   estado_seguro IN ('A','F') AND monto_seguro > 0
     *   AND obra_social_id = :os
     *   AND fecha_solicitud BETWEEN :desde AND :hasta
     *   AND no esta en otro lote abierto/cobrado
     *
     * @return array<int,array<string,mixed>>
     */
    public function findPedidosElegibles(int $obraSocialId, string $fechaDesde, string $fechaHasta): array
    {
        $sql = "SELECT p.id, p.numero, p.fecha_solicitud, p.monto_seguro, p.estado_seguro,
                       pac.nro_hc AS paciente_nro_hc,
                       CONCAT_WS(', ', pac.apellido, pac.nombres) AS paciente_nombre
                FROM lab_pedidos p
                LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                WHERE p.deleted_at IS NULL
                  AND p.obra_social_id = :os
                  AND p.estado_seguro IN ('A','F')
                  AND p.monto_seguro > 0
                  AND p.fecha_solicitud >= :desde
                  AND p.fecha_solicitud <= :hasta
                  AND p.id NOT IN (
                      SELECT lp.pedido_id
                      FROM lab_lote_pedidos lp
                      INNER JOIN lab_lotes_os l ON l.id = lp.lote_id
                      WHERE l.estado IN ('abierto','cobrado') AND l.deleted_at IS NULL
                  )
                ORDER BY p.fecha_solicitud ASC, p.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':os' => $obraSocialId,
            ':desde' => $fechaDesde . ' 00:00:00',
            ':hasta' => $fechaHasta . ' 23:59:59',
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Listado paginado de lotes con filtros.
     *
     * @param array<string,mixed> $filtros: estado, obra_social_id, fecha_desde, fecha_hasta, orden
     * @return array{lotes: array<int,array<string,mixed>>, total: int}
     */
    public function buscar(array $filtros, int $limite = 50, int $offset = 0): array
    {
        [$where, $params] = $this->construirWhereBuscar($filtros);

        $sqlBase = "FROM lab_lotes_os l
                    LEFT JOIN obras_sociales os ON os.id = l.obra_social_id
                    WHERE l.deleted_at IS NULL"
                  . ($where !== '' ? " AND $where" : '');

        $stmtCount = $this->db->prepare("SELECT COUNT(*) AS total $sqlBase");
        $stmtCount->execute($params);
        $total = (int) ($stmtCount->fetchColumn() ?: 0);

        $orden = self::ORDENES_VALIDOS[$filtros['orden'] ?? self::ORDEN_DEFAULT]
              ?? self::ORDENES_VALIDOS[self::ORDEN_DEFAULT];

        $sql = "SELECT l.id, l.numero, l.obra_social_id, l.fecha_desde, l.fecha_hasta,
                       l.estado, l.monto_total, l.cantidad_pedidos, l.fecha_generacion,
                       l.fecha_cobro,
                       os.nombre AS obra_social_nombre
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
            'lotes' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
    }

    /**
     * Recalcula monto_total y cantidad_pedidos sumando el pivot. Usado tras quitar.
     */
    public function recalcularTotales(int $loteId): void
    {
        $sql = "UPDATE lab_lotes_os l
                SET monto_total = COALESCE(
                        (SELECT SUM(monto_seguro_snapshot) FROM lab_lote_pedidos WHERE lote_id = l.id), 0
                    ),
                    cantidad_pedidos = COALESCE(
                        (SELECT COUNT(*) FROM lab_lote_pedidos WHERE lote_id = l.id), 0
                    )
                WHERE l.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $loteId]);
    }

    public function marcarCobrado(int $loteId, ?int $usuarioId): bool
    {
        $sql = "UPDATE lab_lotes_os
                SET estado = 'cobrado',
                    fecha_cobro = CURRENT_TIMESTAMP,
                    usuario_cobro_id = :uid
                WHERE id = :id AND estado = 'abierto' AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $usuarioId, ':id' => $loteId]);
        return $stmt->rowCount() > 0;
    }

    public function marcarAnulado(int $loteId, string $motivo, ?int $usuarioId): bool
    {
        $sql = "UPDATE lab_lotes_os
                SET estado = 'anulado',
                    fecha_anulacion = CURRENT_TIMESTAMP,
                    motivo_anulacion = :motivo,
                    usuario_anulacion_id = :uid
                WHERE id = :id AND estado = 'abierto' AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':motivo' => $motivo, ':uid' => $usuarioId, ':id' => $loteId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string,mixed> $f
     * @return array{0:string,1:array<string,mixed>}
     */
    private function construirWhereBuscar(array $f): array
    {
        $clauses = [];
        $params = [];

        if (!empty($f['estado'])) {
            $clauses[] = 'l.estado = :estado';
            $params[':estado'] = (string) $f['estado'];
        }
        if (!empty($f['obra_social_id'])) {
            $clauses[] = 'l.obra_social_id = :os';
            $params[':os'] = (int) $f['obra_social_id'];
        }
        if (!empty($f['fecha_desde'])) {
            $clauses[] = 'l.fecha_desde >= :fd';
            $params[':fd'] = (string) $f['fecha_desde'];
        }
        if (!empty($f['fecha_hasta'])) {
            $clauses[] = 'l.fecha_hasta <= :fh';
            $params[':fh'] = (string) $f['fecha_hasta'];
        }
        return [implode(' AND ', $clauses), $params];
    }
}
