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
            $where[] = '(i.numero LIKE :numero OR p.numero LIKE :numero)';
            $params[':numero'] = '%' . $numero . '%';
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
                       JSON_UNQUOTE(JSON_EXTRACT(p.snapshot_paciente,'$.dni'))    AS paciente_dni
                  FROM lab_informes i
                  JOIN lab_pedidos p ON p.id = i.pedido_id AND p.deleted_at IS NULL
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
}
