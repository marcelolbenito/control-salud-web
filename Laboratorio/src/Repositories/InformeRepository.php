<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Informe;
use PDO;

final class InformeRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Siguiente correlativo del año en base al mayor I-YYYY-NNNNN existente.
     */
    public function getNextNumeroForYear(int $year): int
    {
        $prefix = sprintf('I-%04d-', $year);

        $sql = "SELECT MAX(CAST(SUBSTRING(numero, ?) AS UNSIGNED)) AS last_num
                FROM lab_informes
                WHERE numero LIKE ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            strlen($prefix) + 1,
            $prefix . '%',
        ]);
        $row = $stmt->fetch();

        return ((int) ($row['last_num'] ?? 0)) + 1;
    }

    public function insert(Informe $i): int
    {
        $sql = "INSERT INTO lab_informes
                (pedido_id, numero, ruta_pdf, hash_pdf, es_parcial,
                 usuario_emision_id, fecha_emision,
                 entregado, fecha_entrega, usuario_entrega_id,
                 destinatario, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $i->pedidoId,
            $i->numero,
            $i->rutaPdf,
            $i->hashPdf,
            (int) $i->esParcial,
            $i->usuarioEmisionId,
            $i->fechaEmision,
            (int) $i->entregado,
            $i->fechaEntrega,
            $i->usuarioEntregaId,
            $i->destinatario,
            $i->observaciones,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT id, pedido_id, numero, ruta_pdf, hash_pdf, es_parcial,
                       usuario_emision_id, fecha_emision,
                       entregado, fecha_entrega, usuario_entrega_id,
                       destinatario, observaciones,
                       created_at, updated_at
                FROM lab_informes
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findByPedidoId(int $pedidoId): array
    {
        $sql = "SELECT id, pedido_id, numero, ruta_pdf, hash_pdf, es_parcial,
                       usuario_emision_id, fecha_emision,
                       entregado, fecha_entrega, usuario_entrega_id,
                       destinatario, observaciones,
                       created_at, updated_at
                FROM lab_informes
                WHERE pedido_id = :pid AND deleted_at IS NULL
                ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $pedidoId]);

        return $stmt->fetchAll();
    }

    public function marcarEntregado(
        int $id,
        int $usuarioEntregaId,
        ?string $destinatario,
        string $fechaEntrega,
    ): bool {
        $sql = "UPDATE lab_informes
                SET entregado = 1,
                    fecha_entrega = :fecha,
                    usuario_entrega_id = :usr,
                    destinatario = :dest
                WHERE id = :id AND deleted_at IS NULL AND entregado = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha' => $fechaEntrega,
            ':usr'   => $usuarioEntregaId,
            ':dest'  => $destinatario,
            ':id'    => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string,mixed> $filtros
     * @return array<int,array<string,mixed>>
     */
    public function listarRecientes(array $filtros = []): array
    {
        $where  = ['i.deleted_at IS NULL'];
        $params = [];

        $numero = isset($filtros['numero']) ? trim((string) $filtros['numero']) : '';
        if ($numero !== '') {
            $where[] = '(i.numero LIKE :numero1 OR p.numero LIKE :numero2)';
            $params[':numero1'] = $params[':numero2'] = '%' . $numero . '%';
        }

        $paciente = isset($filtros['paciente']) ? trim((string) $filtros['paciente']) : '';
        if ($paciente !== '') {
            $where[] = "JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.nombre')) LIKE :paciente";
            $params[':paciente'] = '%' . $paciente . '%';
        }

        $dni = isset($filtros['dni']) ? trim((string) $filtros['dni']) : '';
        if ($dni !== '') {
            $where[] = "JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.dni')) LIKE :dni";
            $params[':dni'] = '%' . $dni . '%';
        }

        $desde = isset($filtros['desde']) ? trim((string) $filtros['desde']) : '';
        if ($desde !== '') {
            $where[] = 'i.fecha_emision >= :desde';
            $params[':desde'] = $desde . ' 00:00:00';
        }

        $hasta = isset($filtros['hasta']) ? trim((string) $filtros['hasta']) : '';
        if ($hasta !== '') {
            $where[] = 'i.fecha_emision <= :hasta';
            $params[':hasta'] = $hasta . ' 23:59:59';
        }

        if (isset($filtros['entregado']) && $filtros['entregado'] !== null && $filtros['entregado'] !== '') {
            $where[] = 'i.entregado = :entregado';
            $params[':entregado'] = (int) $filtros['entregado'];
        }

        $limit = (int) ($filtros['limit'] ?? 50);
        if ($limit <= 0 || $limit > 200) {
            $limit = 50;
        }

        $sql = "SELECT i.id, i.pedido_id, i.numero, i.ruta_pdf, i.es_parcial,
                       i.fecha_emision, i.entregado, i.fecha_entrega, i.destinatario,
                       p.numero AS pedido_numero,
                       p.estado AS pedido_estado,
                       JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.nombre')) AS paciente_nombre,
                       JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.dni'))    AS paciente_dni,
                       pac.telefono AS paciente_telefono
                  FROM lab_informes i
                  JOIN lab_pedidos p ON p.id = i.pedido_id AND p.deleted_at IS NULL
                  LEFT JOIN pacientes pac ON pac.id = p.paciente_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY i.fecha_emision DESC, i.id DESC
                 LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Pedidos pendientes de informe: activos, no anulados y SIN un informe
     * COMPLETO emitido. Incluye cuantos resultados tienen cargados y si ya se
     * les emitio un informe parcial. Filtros opcionales: q (nombre/dni/numero), dia.
     *
     * @param array{q?:string|null, dia?:string|null, limit?:int|null} $filtros
     * @return array<int,array<string,mixed>>
     */
    public function findPendientesDeInforme(array $filtros = []): array
    {
        $where = [
            'p.deleted_at IS NULL',
            "p.estado <> 'anulado'",
            "NOT EXISTS (SELECT 1 FROM lab_informes inf
                          WHERE inf.pedido_id = p.id AND inf.deleted_at IS NULL AND inf.es_parcial = 0)",
        ];
        $params = [];

        $q = isset($filtros['q']) ? trim((string) $filtros['q']) : '';
        if ($q !== '') {
            $where[] = "(JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.nombre')) LIKE :q1
                      OR JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.dni'))    LIKE :q2
                      OR p.numero LIKE :q3)";
            $params[':q1'] = $params[':q2'] = $params[':q3'] = '%' . $q . '%';
        }

        $dia = isset($filtros['dia']) ? trim((string) $filtros['dia']) : '';
        if ($dia !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) {
            $where[] = 'p.fecha_solicitud >= :d1 AND p.fecha_solicitud <= :d2';
            $params[':d1'] = $dia . ' 00:00:00';
            $params[':d2'] = $dia . ' 23:59:59';
        }

        $limit = (int) ($filtros['limit'] ?? 200);
        if ($limit <= 0 || $limit > 500) {
            $limit = 200;
        }

        $sql = "SELECT p.id, p.numero, p.estado, p.prioridad, p.fecha_solicitud,
                       JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.nombre')) AS paciente_nombre,
                       JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.dni'))    AS paciente_dni,
                       (SELECT COUNT(*) FROM lab_pedido_items pi
                          JOIN lab_determinaciones d ON d.id = pi.determinacion_id
                         WHERE pi.pedido_id = p.id AND pi.deleted_at IS NULL
                           AND d.solo_facturacion = 0) AS items_clinicos,
                       (SELECT COUNT(*) FROM lab_resultados r
                          JOIN lab_pedido_items pi2 ON pi2.id = r.pedido_item_id
                         WHERE pi2.pedido_id = p.id AND r.deleted_at IS NULL AND pi2.deleted_at IS NULL) AS resultados_cargados,
                       EXISTS (SELECT 1 FROM lab_informes inf2
                                WHERE inf2.pedido_id = p.id AND inf2.deleted_at IS NULL AND inf2.es_parcial = 1) AS tiene_parcial
                  FROM lab_pedidos p
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY p.fecha_solicitud DESC, p.id DESC
                 LIMIT " . $limit;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
