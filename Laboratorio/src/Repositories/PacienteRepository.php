<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Integration\ControlSaludIntegration;
use App\Models\ObraSocial;
use App\Models\Paciente;
use PDO;

final class PacienteRepository
{
  private const ORDENES_VALIDOS = [
    'apellido_asc'  => 'p.apellido ASC, p.nombres ASC',
    'apellido_desc' => 'p.apellido DESC, p.nombres DESC',
    'nombres_asc'   => 'p.nombres ASC, p.apellido ASC',
    'nombres_desc'  => 'p.nombres DESC, p.apellido DESC',
    'nro_hc_asc'    => 'p.nro_hc ASC',
    'nro_hc_desc'   => 'p.nro_hc DESC',
    'dni_asc'       => 'p.dni ASC',
    'dni_desc'      => 'p.dni DESC',
  ];

  private const ORDEN_DEFAULT = 'apellido_asc';

  public function __construct(private PDO $db)
  {
    if (ControlSaludIntegration::enabled()) {
      ControlSaludIntegration::bindDb($db);
    }
  }

  /**
   * @param array<string,mixed> $filtros
   * @return array{pacientes: list<Paciente>, total_encontrados: int}
   */
  public function buscar(array $filtros, int $limite = 100): array
  {
    [$where, $params] = $this->construirWhere($filtros);

    $ordenes = ControlSaludIntegration::enabled()
      ? ControlSaludIntegration::pacienteOrdenes()
      : self::ORDENES_VALIDOS;

    $orden = $ordenes[$filtros['orden'] ?? self::ORDEN_DEFAULT]
      ?? $ordenes[self::ORDEN_DEFAULT];

    if (ControlSaludIntegration::enabled()) {
      $sqlBase = ControlSaludIntegration::pacienteFromSql()
        . ($where !== '' ? ' AND ' . $where : '');

      $stmtCount = $this->db->prepare('SELECT COUNT(*) AS total ' . $sqlBase);
      $stmtCount->execute($params);
      $total = (int) ($stmtCount->fetchColumn() ?: 0);

      $sql = ControlSaludIntegration::pacienteSelectSql()
        . $sqlBase
        . " ORDER BY {$orden} LIMIT :limite";
    } else {
      $sqlBase = "FROM pacientes p
                    LEFT JOIN obras_sociales os ON os.id = p.obra_social_id
                    WHERE p.deleted_at IS NULL"
        . ($where !== '' ? " AND $where" : '');

      $stmtCount = $this->db->prepare("SELECT COUNT(*) AS total $sqlBase");
      $stmtCount->execute($params);
      $total = (int) ($stmtCount->fetchColumn() ?: 0);

      $sql = "SELECT p.id, p.nro_hc, p.dni, p.apellido, p.nombres, p.telefono,
                       p.fecha_nacimiento, p.sexo, p.obra_social_id, p.nro_afiliado,
                       os.nombre AS obra_social_nombre
                $sqlBase
                ORDER BY $orden
                LIMIT :limite";
    }

    $stmt = $this->db->prepare($sql);
    foreach ($params as $k => $v) {
      $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    $pacientes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $pacientes[] = Paciente::fromRow($row);
    }

    return [
      'pacientes' => $pacientes,
      'total_encontrados' => $total,
    ];
  }

  public function obtener(int $id): ?Paciente
  {
    if (ControlSaludIntegration::enabled()) {
      $sql = ControlSaludIntegration::pacienteSelectSql()
        . ControlSaludIntegration::pacienteFromSql()
        . ' AND p.id = :id LIMIT 1';
    } else {
      $sql = "SELECT p.id, p.nro_hc, p.dni, p.apellido, p.nombres, p.telefono,
                       p.fecha_nacimiento, p.sexo, p.obra_social_id, p.nro_afiliado,
                       os.nombre AS obra_social_nombre
                FROM pacientes p
                LEFT JOIN obras_sociales os ON os.id = p.obra_social_id
                WHERE p.id = :id AND p.deleted_at IS NULL
                LIMIT 1";
    }

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? Paciente::fromRow($row) : null;
  }

  /**
   * @return list<ObraSocial>
   */
  public function listarObrasSociales(): array
  {
    if (ControlSaludIntegration::enabled()) {
      $sql = 'SELECT id, nombre, 1 AS activo
                FROM lista_coberturas
                WHERE nombre IS NOT NULL AND TRIM(nombre) <> \'\'
                ORDER BY nombre ASC';
    } else {
      $sql = 'SELECT id, nombre, activo
                FROM obras_sociales
                WHERE activo = 1
                ORDER BY nombre ASC';
    }

    $stmt = $this->db->query($sql);
    $obras = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $obras[] = ObraSocial::fromRow($row);
    }

    return $obras;
  }

  /**
   * @param array<string,mixed> $filtros
   * @return array{0:string,1:array<string,mixed>}
   */
  private function construirWhere(array $filtros): array
  {
    if (ControlSaludIntegration::enabled()) {
      return $this->construirWhereControlSalud($filtros);
    }

    $clauses = [];
    $params = [];

    if (!empty($filtros['nro_hc'])) {
      $clauses[] = 'p.nro_hc = :nro_hc';
      $params[':nro_hc'] = $filtros['nro_hc'];
    }
    if (!empty($filtros['dni'])) {
      $clauses[] = 'p.dni = :dni';
      $params[':dni'] = $filtros['dni'];
    }
    if (!empty($filtros['apellido'])) {
      $clauses[] = 'p.apellido LIKE :apellido';
      $params[':apellido'] = $filtros['apellido'] . '%';
    }
    if (!empty($filtros['nombres'])) {
      $clauses[] = 'p.nombres LIKE :nombres';
      $params[':nombres'] = $filtros['nombres'] . '%';
    }
    if (!empty($filtros['telefono'])) {
      $clauses[] = 'p.telefono LIKE :telefono';
      $params[':telefono'] = '%' . $filtros['telefono'] . '%';
    }
    if (!empty($filtros['obra_social_id'])) {
      $clauses[] = 'p.obra_social_id = :obra_social_id';
      $params[':obra_social_id'] = (int) $filtros['obra_social_id'];
    }
    if (!empty($filtros['nro_afiliado'])) {
      $clauses[] = 'p.nro_afiliado = :nro_afiliado';
      $params[':nro_afiliado'] = $filtros['nro_afiliado'];
    }
    if (!empty($filtros['fecha_nacimiento'])) {
      $clauses[] = 'p.fecha_nacimiento = :fecha_nacimiento';
      $params[':fecha_nacimiento'] = $filtros['fecha_nacimiento'];
    }
    if (!empty($filtros['sexo'])) {
      $clauses[] = 'p.sexo = :sexo';
      $params[':sexo'] = $filtros['sexo'];
    }

    return [implode(' AND ', $clauses), $params];
  }

  /**
   * @param array<string,mixed> $filtros
   * @return array{0:string,1:array<string,mixed>}
   */
  private function construirWhereControlSalud(array $filtros): array
  {
    $clauses = [];
    $params = [];

    if (!empty($filtros['q'])) {
      $term = trim((string) $filtros['q']);
      $like = '%' . $term . '%';
      $parts = ControlSaludIntegration::pacienteBusquedaRapidaClauses();
      $clauses[] = '(' . implode(' OR ', $parts) . ')';
      $params[':q1'] = $like;
      $params[':q3'] = $like;
      $params[':q4'] = $like;
      if (ControlSaludIntegration::pacienteHasColumn('apellido')) {
        $params[':q2'] = $like;
      }

      return [implode(' AND ', $clauses), $params];
    }

    if (!empty($filtros['nro_hc'])) {
      $clauses[] = 'p.NroHC = :nro_hc';
      $params[':nro_hc'] = $filtros['nro_hc'];
    }
    if (!empty($filtros['dni'])) {
      $clauses[] = 'p.DNI = :dni';
      $params[':dni'] = $filtros['dni'];
    }
    if (!empty($filtros['apellido']) && ControlSaludIntegration::pacienteHasColumn('apellido')) {
      $clauses[] = 'p.apellido LIKE :apellido';
      $params[':apellido'] = $filtros['apellido'] . '%';
    }
    if (!empty($filtros['nombres'])) {
      $clauses[] = 'p.Nombres LIKE :nombres';
      $params[':nombres'] = $filtros['nombres'] . '%';
    }
    if (!empty($filtros['telefono'])) {
      $clauses[] = 'p.telefono LIKE :telefono';
      $params[':telefono'] = '%' . $filtros['telefono'] . '%';
    }
    if (!empty($filtros['obra_social_id']) && ControlSaludIntegration::pacienteHasColumn('id_cobertura')) {
      $clauses[] = 'p.id_cobertura = :obra_social_id';
      $params[':obra_social_id'] = (int) $filtros['obra_social_id'];
    }
    if (!empty($filtros['nro_afiliado']) && ControlSaludIntegration::pacienteHasColumn('nro_os')) {
      $clauses[] = 'p.nro_os = :nro_afiliado';
      $params[':nro_afiliado'] = $filtros['nro_afiliado'];
    }
    if (!empty($filtros['fecha_nacimiento'])) {
      $clauses[] = 'DATE(p.fecha_nacimiento) = :fecha_nacimiento';
      $params[':fecha_nacimiento'] = $filtros['fecha_nacimiento'];
    }
    if (!empty($filtros['sexo']) && ControlSaludIntegration::pacienteHasColumn('sexo')) {
      $clauses[] = "CASE UPPER(LEFT(TRIM(COALESCE(CAST(p.sexo AS CHAR), '')), 1))
                        WHEN 'M' THEN 'M' WHEN 'F' THEN 'F' ELSE 'X' END = :sexo";
      $params[':sexo'] = $filtros['sexo'];
    }

    return [implode(' AND ', $clauses), $params];
  }
}
