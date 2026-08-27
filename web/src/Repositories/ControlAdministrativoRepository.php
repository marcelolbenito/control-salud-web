<?php

declare(strict_types=1);

final class ControlAdministrativoRepository
{
    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    public function agendaDisponible(): bool
    {
        return db_table_exists($this->pdo, 'agenda_turnos');
    }

    private function agendaTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica');
    }

    private function pacientesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'pacientes', 'id_clinica');
    }

    private function ordenesDisponibles(): bool
    {
        return db_table_exists($this->pdo, 'Pacientes Ordenes');
    }

    private function ordenesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'Pacientes Ordenes', 'id_clinica');
    }

    private function pagosDisponibles(): bool
    {
        return db_table_exists($this->pdo, 'pacientes_pagos');
    }

    private function pagosTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'pacientes_pagos', 'id_clinica');
    }

    private function cajaDisponible(): bool
    {
        return db_table_exists($this->pdo, 'caja');
    }

    private function cajaTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'caja', 'id_clinica');
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listDia(string $fecha, int $doctorFiltro): array
    {
        if (!$this->agendaDisponible() || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return [];
        }

        $hasIdOrden = db_table_has_column($this->pdo, 'agenda_turnos', 'idorden');
        $idOrdenExpr = $hasIdOrden ? 't.idorden' : 'NULL';
        $selIdOrden = $hasIdOrden ? 't.idorden AS idorden' : 'NULL AS idorden';
        $selLlegado = db_table_has_column($this->pdo, 'agenda_turnos', 'llegado') ? 't.llegado AS llegado' : '0 AS llegado';
        $selAtendido = db_table_has_column($this->pdo, 'agenda_turnos', 'atendido') ? 't.atendido AS atendido' : "CASE WHEN t.estado = 'atendido' THEN 1 ELSE 0 END AS atendido";
        $selFalta = db_table_has_column($this->pdo, 'agenda_turnos', 'falta_turno') ? 't.falta_turno AS falta_turno' : "CASE WHEN t.estado = 'no_asistio' THEN 1 ELSE 0 END AS falta_turno";
        $pacienteExpr = db_table_has_column($this->pdo, 'agenda_turnos', 'paciente_nombre')
            ? "COALESCE(NULLIF(TRIM(t.paciente_nombre), ''), NULLIF(TRIM(p.Nombres), ''), CONCAT('HC ', t.NroHC)) AS paciente_nombre"
            : "COALESCE(NULLIF(TRIM(p.Nombres), ''), CONCAT('HC ', t.NroHC)) AS paciente_nombre";

        $joinPaciente = 'p.NroHC = t.NroHC';
        if ($this->agendaTieneClinica() && $this->pacientesTieneClinica()) {
            $joinPaciente .= ' AND p.id_clinica = t.id_clinica';
        }
        $joinDoc = 'd.id = t.Doctor';
        if ($this->agendaTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = t.id_clinica';
        }

        $ordenMatch = $this->ordenesDisponibles() ? $this->ordenMatchSql('o') : '';
        $ordenIdSql = 'NULL AS orden_referencia, 0 AS tiene_orden';
        if ($this->ordenesDisponibles()) {
            $idOrdenValido = $hasIdOrden ? "(COALESCE({$idOrdenExpr}, 0) > 0)" : '0';
            $ordenIdSql = "COALESCE(NULLIF({$idOrdenExpr}, 0), (SELECT MIN(o.id) FROM `Pacientes Ordenes` o WHERE {$ordenMatch})) AS orden_referencia,
                CASE WHEN {$idOrdenValido} OR EXISTS(SELECT 1 FROM `Pacientes Ordenes` o WHERE {$ordenMatch}) THEN 1 ELSE 0 END AS tiene_orden";
        }

        $pagosSql = $this->pagosTotalSql($idOrdenExpr, $hasIdOrden);
        $cajaSql = $this->cajaTotalSql($idOrdenExpr, $hasIdOrden);

        $sql = "SELECT t.id, t.Fecha, t.hora, t.NroHC, t.Doctor, t.estado, {$selIdOrden},
                {$selLlegado}, {$selAtendido}, {$selFalta},
                {$pacienteExpr}, d.nombre AS doctor_nombre,
                {$ordenIdSql},
                {$pagosSql} AS pagos_total,
                {$cajaSql} AS caja_total
            FROM agenda_turnos t
            LEFT JOIN pacientes p ON {$joinPaciente}
            LEFT JOIN lista_doctores d ON {$joinDoc}
            WHERE t.Fecha = ?
              AND t.NroHC <> -111";
        $params = [$fecha];
        if ($this->agendaTieneClinica()) {
            $sql .= ' AND (t.id_clinica = ? OR t.id_clinica IS NULL)';
            $params[] = $this->idClinica;
        }
        if ($doctorFiltro > 0) {
            $sql .= ' AND t.Doctor = ?';
            $params[] = $doctorFiltro;
        }
        $sql .= ' ORDER BY t.hora IS NULL, t.hora ASC, t.id ASC';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ordenMatchSql(string $alias): string
    {
        $sql = "{$alias}.NroPaci = t.NroHC AND DATE({$alias}.fecha) = t.Fecha AND {$alias}.iddoctor = t.Doctor";
        if ($this->agendaTieneClinica() && $this->ordenesTieneClinica()) {
            $sql .= " AND (t.id_clinica IS NULL OR {$alias}.id_clinica = t.id_clinica)";
        }

        return $sql;
    }

    private function pagosTotalSql(string $idOrdenExpr, bool $hasIdOrden): string
    {
        if (!$this->pagosDisponibles()) {
            return '0';
        }

        $links = ["(DATE(p.fecha) = t.Fecha AND (p.idorden IS NULL OR p.idorden = 0))"];
        if ($hasIdOrden) {
            $links[] = "(COALESCE({$idOrdenExpr}, 0) > 0 AND p.idorden = {$idOrdenExpr})";
        }
        if ($this->ordenesDisponibles()) {
            $links[] = 'p.idorden IN (SELECT op.id FROM `Pacientes Ordenes` op WHERE ' . $this->ordenMatchSql('op') . ')';
        }

        $sql = 'SELECT COALESCE(SUM(p.importe), 0) FROM pacientes_pagos p WHERE p.NroPaci = t.NroHC';
        if ($this->agendaTieneClinica() && $this->pagosTieneClinica()) {
            $sql .= ' AND (t.id_clinica IS NULL OR p.id_clinica = t.id_clinica)';
        }
        $sql .= ' AND (' . implode(' OR ', $links) . ')';

        return '(' . $sql . ')';
    }

    private function cajaTotalSql(string $idOrdenExpr, bool $hasIdOrden): string
    {
        if (!$this->cajaDisponible()) {
            return '0';
        }

        $hasObs = db_table_has_column($this->pdo, 'caja', 'observaciones');
        $hasTurno = db_table_has_column($this->pdo, 'caja', 'turnocaja');
        $links = [];
        if ($hasObs) {
            $links[] = "c.observaciones LIKE CONCAT('%Turno #', t.id, '%')";
            $links[] = "c.observaciones LIKE CONCAT('%HC: ', t.NroHC, '%')";
        }
        if ($hasTurno && $hasIdOrden) {
            $links[] = "(COALESCE({$idOrdenExpr}, 0) > 0 AND c.turnocaja = CONCAT('Orden #', {$idOrdenExpr}))";
        }
        if ($hasTurno && $this->ordenesDisponibles()) {
            $links[] = 'EXISTS (SELECT 1 FROM `Pacientes Ordenes` oc WHERE ' . $this->ordenMatchSql('oc') . " AND c.turnocaja = CONCAT('Orden #', oc.id))";
        }
        if ($links === []) {
            return '0';
        }

        $sql = 'SELECT COALESCE(SUM(c.importecaja), 0) FROM caja c WHERE c.fechacaja = t.Fecha';
        if (db_table_has_column($this->pdo, 'caja', 'doctor')) {
            $sql .= ' AND c.doctor = t.Doctor';
        }
        if ($this->agendaTieneClinica() && $this->cajaTieneClinica()) {
            $sql .= ' AND (t.id_clinica IS NULL OR c.id_clinica = t.id_clinica)';
        }
        $sql .= ' AND (' . implode(' OR ', $links) . ')';

        return '(' . $sql . ')';
    }
}
