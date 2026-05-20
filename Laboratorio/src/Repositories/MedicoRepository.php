<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Integration\ControlSaludIntegration;
use App\Models\Medico;
use PDO;

/**
 * Solo lectura. En Control Salud: lista_doctores. En modo standalone: medicos.
 */
final class MedicoRepository
{
  private const ORDENES_VALIDOS = [
    'apellido_asc'  => 'apellido ASC, nombres ASC',
    'apellido_desc' => 'apellido DESC, nombres DESC',
    'matricula_asc' => 'matricula ASC',
    'especialidad_asc' => 'especialidad ASC, apellido ASC',
  ];

  private const ORDEN_DEFAULT = 'apellido_asc';

  public function __construct(private PDO $db)
  {
  }

  /**
   * @param array<string,mixed> $filtros
   * @return list<Medico>
   */
  public function buscar(array $filtros, int $limite = 100): array
  {
    $clauses = [];
    $params  = [];

    if (ControlSaludIntegration::enabled()) {
      if (empty($filtros['incluir_inactivos'])) {
        $clauses[] = 'COALESCE(d.activo, 1) = 1';
      }

      if (!empty($filtros['q'])) {
        $clauses[] = '(d.nombre LIKE :q1 OR d.matricula LIKE :q2 OR d.especialidad LIKE :q3)';
        $like = '%' . trim((string) $filtros['q']) . '%';
        $params[':q1'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
      }

      if (!empty($filtros['especialidad'])) {
        $clauses[] = 'd.especialidad = :esp';
        $params[':esp'] = trim((string) $filtros['especialidad']);
      }

      $orden = self::ORDENES_VALIDOS[$filtros['orden'] ?? self::ORDEN_DEFAULT]
        ?? self::ORDENES_VALIDOS[self::ORDEN_DEFAULT];

      $where = $clauses !== [] ? ' AND ' . implode(' AND ', $clauses) : '';

      $sql = ControlSaludIntegration::medicoSelectSql()
        . ControlSaludIntegration::medicoFromSql()
        . $where
        . " ORDER BY {$orden} LIMIT :limite";
    } else {
      $clauses[] = 'deleted_at IS NULL';

      if (empty($filtros['incluir_inactivos'])) {
        $clauses[] = 'activo = 1';
      }

      if (!empty($filtros['q'])) {
        $clauses[] = '(apellido LIKE :q1 OR nombres LIKE :q2 OR matricula LIKE :q3)';
        $like = '%' . trim((string) $filtros['q']) . '%';
        $params[':q1'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
      }

      if (!empty($filtros['especialidad'])) {
        $clauses[] = 'especialidad = :esp';
        $params[':esp'] = trim((string) $filtros['especialidad']);
      }

      $orden = self::ORDENES_VALIDOS[$filtros['orden'] ?? self::ORDEN_DEFAULT]
        ?? self::ORDENES_VALIDOS[self::ORDEN_DEFAULT];

      $sql = 'SELECT id, apellido, nombres, matricula, especialidad, telefono, email, activo
                FROM medicos
                WHERE ' . implode(' AND ', $clauses) . "
                ORDER BY {$orden}
                LIMIT :limite";
    }

    $stmt = $this->db->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $out[] = Medico::fromRow($row);
    }

    return $out;
  }

  public function obtener(int $id): ?Medico
  {
    if (ControlSaludIntegration::enabled()) {
      $sql = ControlSaludIntegration::medicoSelectSql()
        . ControlSaludIntegration::medicoFromSql()
        . ' AND d.id = :id LIMIT 1';
    } else {
      $sql = 'SELECT id, apellido, nombres, matricula, especialidad, telefono, email, activo
                FROM medicos
                WHERE id = :id AND deleted_at IS NULL
                LIMIT 1';
    }

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? Medico::fromRow($row) : null;
  }

  /**
   * @return list<string>
   */
  public function listarEspecialidades(): array
  {
    if (ControlSaludIntegration::enabled()) {
      $cid = ControlSaludIntegration::clinicaId();
      $sql = "SELECT DISTINCT TRIM(d.especialidad) AS especialidad
                FROM lista_doctores d
                WHERE d.id_clinica = {$cid}
                  AND COALESCE(d.activo, 1) = 1
                  AND d.especialidad IS NOT NULL
                  AND TRIM(d.especialidad) <> ''
                ORDER BY especialidad";
    } else {
      $sql = "SELECT DISTINCT especialidad
                FROM medicos
                WHERE deleted_at IS NULL AND activo = 1 AND especialidad IS NOT NULL
                ORDER BY especialidad";
    }

    $stmt = $this->db->query($sql);

    return array_map('strval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'especialidad'));
  }
}
