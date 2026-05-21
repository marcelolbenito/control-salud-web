<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Integration\ControlSaludIntegration;
use App\Models\Pedido;
use App\Models\PedidoItem;
use PDO;

final class PedidoRepository
{
    /**
     * Whitelist de orderings validos para el listado paginado (sub-proyecto 2).
     */
    private const ORDENES_VALIDOS = [
        'fecha_solicitud_desc' => 'p.fecha_solicitud DESC, p.id DESC',
        'fecha_solicitud_asc'  => 'p.fecha_solicitud ASC, p.id ASC',
        'fecha_entrega_desc'   => 'p.fecha_entrega DESC, p.id DESC',
        'fecha_entrega_asc'    => 'p.fecha_entrega ASC, p.id ASC',
        'numero_desc'          => 'p.numero DESC',
        'numero_asc'           => 'p.numero ASC',
        'estado_asc'           => 'p.estado ASC, p.fecha_solicitud DESC',
        'estado_desc'          => 'p.estado DESC, p.fecha_solicitud DESC',
        'prioridad_asc'        => "FIELD(p.prioridad,'rutina','urgente','guardia') ASC, p.fecha_solicitud DESC",
        'prioridad_desc'       => "FIELD(p.prioridad,'rutina','urgente','guardia') DESC, p.fecha_solicitud DESC",
    ];

    private const ORDEN_DEFAULT = 'fecha_solicitud_desc';

    public function __construct(private PDO $db)
    {
        if (ControlSaludIntegration::enabled()) {
            ControlSaludIntegration::bindDb($db);
        }
    }

    /**
     * Obtiene el siguiente correlativo del año (1, 2, 3, ...) en base al mayor
     * numero P-YYYY-NNNNN existente.
     */
    public function getNextNumeroForYear(int $year): int
    {
        $prefix = sprintf('P-%04d-', $year);

        $sql = "SELECT MAX(CAST(SUBSTRING(numero, ?) AS UNSIGNED)) AS last_num
                FROM lab_pedidos
                WHERE numero LIKE ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            strlen($prefix) + 1,
            $prefix . '%',
        ]);
        $row = $stmt->fetch();

        return ((int) ($row['last_num'] ?? 0)) + 1;
    }

    public function insert(Pedido $pedido): int
    {
        $sql = "INSERT INTO lab_pedidos
                (numero, paciente_id, medico_id, medico_externo, obra_social_id,
                 numero_afiliado, diagnostico, prioridad, estado, es_critico,
                 fecha_solicitud, usuario_recepcion_id, observaciones, snapshot_paciente)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $pedido->numero,
            $pedido->pacienteId,
            $pedido->medicoId,
            $pedido->medicoExterno,
            $pedido->obraSocialId,
            $pedido->numeroAfiliado,
            $pedido->diagnostico,
            $pedido->prioridad,
            $pedido->estado,
            (int) $pedido->esCritico,
            $pedido->fechaSolicitud,
            $pedido->usuarioRecepcionId,
            $pedido->observaciones,
            $pedido->snapshotPaciente !== null
                ? json_encode($pedido->snapshotPaciente, JSON_UNESCAPED_UNICODE)
                : null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function insertItem(int $pedidoId, PedidoItem $item): int
    {
        $sql = "INSERT INTO lab_pedido_items
                (pedido_id, determinacion_id, perfil_id, estado, precio, observaciones)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $pedidoId,
            $item->determinacionId,
            $item->perfilId,
            $item->estado,
            $item->precio,
            $item->observaciones,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, numero, paciente_id, medico_id, medico_externo,
                       obra_social_id, numero_afiliado, diagnostico,
                       prioridad, estado, es_critico,
                       fecha_solicitud, fecha_extraccion, fecha_entrega,
                       usuario_recepcion_id, usuario_anulacion_id, motivo_anulacion,
                       observaciones, snapshot_paciente,
                       estado_paciente, estado_seguro,
                       monto_paciente, monto_seguro, monto_honorarios,
                       created_at, updated_at
                FROM lab_pedidos
                WHERE id = :id AND deleted_at IS NULL";

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

    /**
     * Setea fecha_entrega del pedido. Idempotente: si ya tenia, no la pisa.
     */
    public function updateFechaEntrega(int $id, string $fechaEntrega): bool
    {
        $sql = "UPDATE lab_pedidos
                SET fecha_entrega = COALESCE(fecha_entrega, :fecha)
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha' => $fechaEntrega,
            ':id'    => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Marca el flag es_critico del pedido. Idempotente.
     */
    public function updateEsCritico(int $id, bool $esCritico): bool
    {
        $sql = "UPDATE lab_pedidos
                SET es_critico = :flag
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':flag' => (int) $esCritico,
            ':id'   => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Cambia el estado de un pedido y, opcionalmente, setea fecha_extraccion.
     * Devuelve true si afecto una fila (el pedido existe y no esta soft-deleted).
     */
    public function updateEstado(int $id, string $nuevoEstado, ?string $fechaExtraccion = null): bool
    {
        if ($fechaExtraccion !== null) {
            $sql = "UPDATE lab_pedidos
                    SET estado = :estado,
                        fecha_extraccion = COALESCE(fecha_extraccion, :fecha)
                    WHERE id = :id AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':estado' => $nuevoEstado,
                ':fecha'  => $fechaExtraccion,
                ':id'     => $id,
            ]);
        } else {
            $sql = "UPDATE lab_pedidos
                    SET estado = :estado
                    WHERE id = :id AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':estado' => $nuevoEstado,
                ':id'     => $id,
            ]);
        }

        return $stmt->rowCount() > 0;
    }

    /**
     * Lista resumida de pedidos de un paciente. Usada por el historial.
     *
     * Filtros soportados (todos opcionales):
     *   - estado: 'pendiente'|'en_proceso'|'parcial'|'completo'|'entregado'|'anulado'
     *   - desde:  'YYYY-MM-DD' (filtra fecha_solicitud >= desde)
     *   - hasta:  'YYYY-MM-DD' (filtra fecha_solicitud <= hasta + 23:59:59)
     *   - limit:  default 50, max 200
     *   - offset: default 0
     *
     * @param array<string,mixed> $filtros
     * @return array<int,array<string,mixed>>
     */
    public function findByPacienteId(int $pacienteId, array $filtros = []): array
    {
        $where = ['p.paciente_id = :pid', 'p.deleted_at IS NULL'];
        $params = [':pid' => $pacienteId];

        if (!empty($filtros['estado'])) {
            $where[] = 'p.estado = :estado';
            $params[':estado'] = (string) $filtros['estado'];
        }
        if (!empty($filtros['desde'])) {
            $where[] = 'p.fecha_solicitud >= :desde';
            $params[':desde'] = (string) $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $where[] = 'p.fecha_solicitud <= :hasta';
            $params[':hasta'] = (string) $filtros['hasta'] . ' 23:59:59';
        }

        $limit = isset($filtros['limit']) ? max(1, min(200, (int) $filtros['limit'])) : 50;
        $offset = isset($filtros['offset']) ? max(0, (int) $filtros['offset']) : 0;

        $sql = "SELECT p.id, p.numero, p.estado, p.prioridad, p.es_critico,
                       p.fecha_solicitud, p.fecha_extraccion, p.fecha_entrega,
                       p.medico_externo, p.medico_id,
                       (SELECT COUNT(*) FROM lab_pedido_items pi
                          WHERE pi.pedido_id = p.id AND pi.deleted_at IS NULL) AS items_count,
                       (SELECT COUNT(*) FROM lab_pedido_items pi
                          WHERE pi.pedido_id = p.id AND pi.deleted_at IS NULL
                            AND pi.estado = 'validado') AS items_validados
                FROM lab_pedidos p
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.fecha_solicitud DESC, p.id DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countByPacienteId(int $pacienteId, array $filtros = []): int
    {
        $where = ['paciente_id = :pid', 'deleted_at IS NULL'];
        $params = [':pid' => $pacienteId];

        if (!empty($filtros['estado'])) {
            $where[] = 'estado = :estado';
            $params[':estado'] = (string) $filtros['estado'];
        }
        if (!empty($filtros['desde'])) {
            $where[] = 'fecha_solicitud >= :desde';
            $params[':desde'] = (string) $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])) {
            $where[] = 'fecha_solicitud <= :hasta';
            $params[':hasta'] = (string) $filtros['hasta'] . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) AS c FROM lab_pedidos
                WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int) ($row['c'] ?? 0);
    }

    /**
     * Busqueda paginada de pedidos con filtros multiples (sub-proyecto 2).
     *
     * Filtros admitidos (todos opcionales, vacios se ignoran):
     *   estado, paciente_id, numero (LIKE %x%), prioridad,
     *   medico (LIKE %x% sobre medico_externo), obra_social_id,
     *   fecha_solicitud_desde, fecha_solicitud_hasta (YYYY-MM-DD),
     *   fecha_entrega_desde, fecha_entrega_hasta (YYYY-MM-DD),
     *   solo_criticos (bool), incluir_anulados (bool, default false),
     *   orden (whitelist).
     *
     * @param array<string,mixed> $filtros
     * @return array{pedidos: array<int,array<string,mixed>>, total: int}
     */
    public function buscar(array $filtros, int $limite = 50, int $offset = 0): array
    {
        $orden = self::ORDENES_VALIDOS[$filtros['orden'] ?? self::ORDEN_DEFAULT]
              ?? self::ORDENES_VALIDOS[self::ORDEN_DEFAULT];

        [$where, $params] = $this->construirWhereBuscar($filtros);

        $joins = ControlSaludIntegration::pedidoListJoinsSql();
        $pacCols = ControlSaludIntegration::pedidoListPacienteSelectSql('pac');

        $sqlBase = "FROM lab_pedidos p
                    {$joins}
                    WHERE p.deleted_at IS NULL"
                  . ($where !== '' ? " AND $where" : '');

        $stmtCount = $this->db->prepare("SELECT COUNT(*) AS total $sqlBase");
        $stmtCount->execute($params);
        $total = (int) ($stmtCount->fetchColumn() ?: 0);

        $sql = "SELECT p.id, p.numero, p.paciente_id, p.medico_id, p.medico_externo,
                       p.obra_social_id, p.estado, p.prioridad, p.es_critico,
                       p.fecha_solicitud, p.fecha_entrega,
                       {$pacCols},
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
            'pedidos' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
        ];
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
            $clauses[] = 'p.estado = :estado';
            $params[':estado'] = $f['estado'];
        }
        if (empty($f['estado']) && empty($f['incluir_anulados'])) {
            $clauses[] = "p.estado <> 'anulado'";
        }
        if (!empty($f['paciente_id'])) {
            $clauses[] = 'p.paciente_id = :paciente_id';
            $params[':paciente_id'] = (int) $f['paciente_id'];
        }
        if (!empty($f['numero'])) {
            $clauses[] = 'p.numero LIKE :numero';
            $params[':numero'] = '%' . $f['numero'] . '%';
        }
        if (!empty($f['prioridad'])) {
            $clauses[] = 'p.prioridad = :prioridad';
            $params[':prioridad'] = $f['prioridad'];
        }
        if (!empty($f['medico'])) {
            $clauses[] = 'p.medico_externo LIKE :medico';
            $params[':medico'] = '%' . $f['medico'] . '%';
        }
        if (!empty($f['medico_id'])) {
            $clauses[] = 'p.medico_id = :medico_id';
            $params[':medico_id'] = (int) $f['medico_id'];
        }
        if (!empty($f['obra_social_id'])) {
            $clauses[] = 'p.obra_social_id = :obra_social_id';
            $params[':obra_social_id'] = (int) $f['obra_social_id'];
        }
        if (!empty($f['fecha_solicitud_desde'])) {
            $clauses[] = 'p.fecha_solicitud >= :fsd';
            $params[':fsd'] = $f['fecha_solicitud_desde'] . ' 00:00:00';
        }
        if (!empty($f['fecha_solicitud_hasta'])) {
            $clauses[] = 'p.fecha_solicitud <= :fsh';
            $params[':fsh'] = $f['fecha_solicitud_hasta'] . ' 23:59:59';
        }
        if (!empty($f['fecha_entrega_desde'])) {
            $clauses[] = 'p.fecha_entrega >= :fed';
            $params[':fed'] = $f['fecha_entrega_desde'] . ' 00:00:00';
        }
        if (!empty($f['fecha_entrega_hasta'])) {
            $clauses[] = 'p.fecha_entrega <= :feh';
            $params[':feh'] = $f['fecha_entrega_hasta'] . ' 23:59:59';
        }
        if (!empty($f['solo_criticos'])) {
            $clauses[] = 'p.es_critico = 1';
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * Marca un pedido como anulado. Devuelve true si actualizo una fila.
     */
    public function anular(int $id, string $motivo, ?int $usuarioId): bool
    {
        $sql = "UPDATE lab_pedidos
                SET estado = 'anulado',
                    motivo_anulacion = :motivo,
                    usuario_anulacion_id = :uid
                WHERE id = :id
                  AND deleted_at IS NULL
                  AND estado NOT IN ('entregado','anulado')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':motivo' => $motivo,
            ':uid'    => $usuarioId,
            ':id'     => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Sub-proyecto 5: cambia uno de los estados de facturacion del pedido.
     * @param 'paciente'|'seguro' $tipo
     * @param 'A'|'F'|'P'|'N' $nuevo
     */
    public function cambiarEstadoFacturacion(int $id, string $tipo, string $nuevo): bool
    {
        $col = $tipo === 'paciente' ? 'estado_paciente' : 'estado_seguro';
        $sql = "UPDATE lab_pedidos
                SET $col = :estado
                WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estado' => $nuevo, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Sub-proyecto 5: actualiza montos de un pedido.
     * @param array<string,mixed> $datos campos: monto_paciente, monto_seguro, monto_honorarios.
     */
    public function actualizarFacturacion(int $id, array $datos): bool
    {
        $set = [];
        $params = [':id' => $id];
        foreach (['monto_paciente', 'monto_seguro', 'monto_honorarios'] as $k) {
            if (isset($datos[$k])) {
                $set[] = "$k = :$k";
                $params[":$k"] = $datos[$k];
            }
        }
        if ($set === []) return false;

        $sql = "UPDATE lab_pedidos SET " . implode(', ', $set)
             . " WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Soft-delete del pedido.
     */
    public function softDelete(int $id): bool
    {
        $sql = "UPDATE lab_pedidos
                SET deleted_at = CURRENT_TIMESTAMP
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findItemsByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT pi.id, pi.pedido_id, pi.determinacion_id, pi.perfil_id,
                       pi.estado, pi.precio, pi.observaciones,
                       d.codigo AS determinacion_codigo, d.nombre AS determinacion_nombre,
                       d.unidad,
                       pf.codigo AS perfil_codigo, pf.nombre AS perfil_nombre
                FROM lab_pedido_items pi
                JOIN lab_determinaciones d ON d.id = pi.determinacion_id
                LEFT JOIN lab_perfiles pf ON pf.id = pi.perfil_id
                WHERE pi.pedido_id = :pedido_id AND pi.deleted_at IS NULL
                ORDER BY pi.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pedido_id' => $pedidoId]);

        return $stmt->fetchAll();
    }

    /**
     * Actualiza monto_seguro y monto_paciente del pedido.
     */
    public function updateMontos(int $pedidoId, float $montoSeguro, float $montoPaciente): void
    {
        $sql = "UPDATE lab_pedidos
                SET monto_seguro = :s, monto_paciente = :p
                WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':s'  => $montoSeguro,
            ':p'  => $montoPaciente,
            ':id' => $pedidoId,
        ]);
    }

    /**
     * Recalcula monto_seguro de un pedido usando la vigencia NBU de la OS
     * que aplica a la fecha indicada. Persiste en lab_pedidos.monto_seguro
     * y devuelve el total.
     *
     * monto_seguro = SUM(unidades_NBU * valor_vigente_a_fecha)
     */
    public function recalcularMontoSeguroAlVuelo(int $pedidoId, string $fecha): float
    {
        $stmt = $this->db->prepare(
            "SELECT ROUND(COALESCE(SUM(n.unidades * v.valor_unitario), 0), 2) AS total
             FROM lab_pedido_items i
             INNER JOIN lab_pedidos p ON p.id = i.pedido_id
             LEFT JOIN lab_nbu_determinaciones n ON n.determinacion_id = i.determinacion_id
             LEFT JOIN lab_nbu_valores_os v
                    ON v.obra_social_id = p.obra_social_id
                   AND v.deleted_at IS NULL
                   AND v.fecha_desde <= :fecha_a
                   AND (v.fecha_hasta IS NULL OR v.fecha_hasta >= :fecha_b)
             WHERE i.pedido_id = :id AND i.deleted_at IS NULL"
        );
        $stmt->execute([':id' => $pedidoId, ':fecha_a' => $fecha, ':fecha_b' => $fecha]);
        $total = (float) ($stmt->fetchColumn() ?: 0);

        $stmt = $this->db->prepare(
            'UPDATE lab_pedidos SET monto_seguro = :s WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':s' => $total, ':id' => $pedidoId]);

        return $total;
    }

    /**
     * Recalcula monto_seguro y monto_paciente de un pedido en el contexto de un lote,
     * considerando los items excluidos para ese lote (que no cubre la OS).
     *
     * monto_seguro   = SUM(unidades * valor) WHERE item NOT IN excluidos(lote)
     * monto_paciente = SUM(unidades * valor) WHERE item IN excluidos(lote)
     *
     * Persiste en lab_pedidos y devuelve el par.
     *
     * @return array{monto_seguro:float, monto_paciente:float}
     */
    public function recalcularPedidoConExclusiones(int $pedidoId, int $loteId, string $fecha): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                ROUND(COALESCE(SUM(CASE WHEN x.id IS NULL THEN n.unidades * v.valor_unitario ELSE 0 END), 0), 2) AS seguro,
                ROUND(COALESCE(SUM(CASE WHEN x.id IS NOT NULL THEN n.unidades * v.valor_unitario ELSE 0 END), 0), 2) AS paciente
             FROM lab_pedido_items i
             INNER JOIN lab_pedidos p ON p.id = i.pedido_id
             LEFT JOIN lab_nbu_determinaciones n ON n.determinacion_id = i.determinacion_id
             LEFT JOIN lab_nbu_valores_os v
                    ON v.obra_social_id = p.obra_social_id
                   AND v.deleted_at IS NULL
                   AND v.fecha_desde <= :fecha_a
                   AND (v.fecha_hasta IS NULL OR v.fecha_hasta >= :fecha_b)
             LEFT JOIN lab_lote_pedido_item_excluido x
                    ON x.pedido_item_id = i.id AND x.lote_id = :lote_id
             WHERE i.pedido_id = :id AND i.deleted_at IS NULL"
        );
        $stmt->execute([
            ':id'      => $pedidoId,
            ':lote_id' => $loteId,
            ':fecha_a' => $fecha,
            ':fecha_b' => $fecha,
        ]);
        $row = $stmt->fetch();
        $seguro = (float) ($row['seguro'] ?? 0);
        $paciente = (float) ($row['paciente'] ?? 0);

        $stmt = $this->db->prepare(
            'UPDATE lab_pedidos
             SET monto_seguro = :s, monto_paciente = :p
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':s' => $seguro, ':p' => $paciente, ':id' => $pedidoId]);

        return ['monto_seguro' => $seguro, 'monto_paciente' => $paciente];
    }

    /**
     * Items del pedido con su monto calculado para el contexto de un lote.
     * Incluye flag `excluido` segun si esta en lab_lote_pedido_item_excluido.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findItemsConExclusionDeLote(int $pedidoId, int $loteId, string $fecha): array
    {
        $stmt = $this->db->prepare(
            "SELECT i.id AS item_id,
                    d.codigo AS determinacion_codigo,
                    d.nombre AS determinacion_nombre,
                    COALESCE(n.unidades, 0) AS unidades_nbu,
                    COALESCE(v.valor_unitario, 0) AS valor_unitario,
                    ROUND(COALESCE(n.unidades, 0) * COALESCE(v.valor_unitario, 0), 2) AS monto,
                    CASE WHEN x.id IS NULL THEN 0 ELSE 1 END AS excluido
             FROM lab_pedido_items i
             INNER JOIN lab_pedidos p ON p.id = i.pedido_id
             INNER JOIN lab_determinaciones d ON d.id = i.determinacion_id
             LEFT JOIN lab_nbu_determinaciones n ON n.determinacion_id = i.determinacion_id
             LEFT JOIN lab_nbu_valores_os v
                    ON v.obra_social_id = p.obra_social_id
                   AND v.deleted_at IS NULL
                   AND v.fecha_desde <= :fecha_a
                   AND (v.fecha_hasta IS NULL OR v.fecha_hasta >= :fecha_b)
             LEFT JOIN lab_lote_pedido_item_excluido x
                    ON x.pedido_item_id = i.id AND x.lote_id = :lote_id
             WHERE i.pedido_id = :id AND i.deleted_at IS NULL
             ORDER BY d.nombre ASC, i.id ASC"
        );
        $stmt->execute([
            ':id'      => $pedidoId,
            ':lote_id' => $loteId,
            ':fecha_a' => $fecha,
            ':fecha_b' => $fecha,
        ]);
        return $stmt->fetchAll() ?: [];
    }
}
