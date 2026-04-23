<?php

declare(strict_types=1);

final class AgendaRepository
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

    private function agendaTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica');
    }

    private function pacientesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'pacientes', 'id_clinica');
    }

    private function joinPacienteTurno(): string
    {
        $on = 'p.NroHC = t.NroHC';
        if ($this->agendaTieneClinica() && $this->pacientesTieneClinica()) {
            $on .= ' AND p.id_clinica = t.id_clinica';
        }

        return $on;
    }

    public function hasExtendedColumns(): bool
    {
        return db_table_has_column($this->pdo, 'agenda_turnos', 'motivo');
    }

    public function listByFechaYDoctor(string $fecha, int $doctorFiltro, bool $extAgenda): array
    {
        $pacienteExpr = $extAgenda
            ? "COALESCE(NULLIF(TRIM(t.paciente_nombre), ''), p.Nombres) AS paciente_nombre"
            : 'p.Nombres AS paciente_nombre';
        $extraSel = $extAgenda
            ? ', t.atendido, t.llegado, t.confirmado, t.falta_turno'
            : '';

        $joinDoc = 'd.id = t.Doctor';
        if ($this->agendaTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = t.id_clinica';
        }
        $sql = "SELECT t.id, t.Fecha, t.hora, t.NroHC, t.Doctor, t.estado, t.idorden, t.observaciones,
            {$pacienteExpr}, d.nombre AS doctor_nombre{$extraSel}
            FROM agenda_turnos t
            LEFT JOIN pacientes p ON " . $this->joinPacienteTurno() . '
            LEFT JOIN lista_doctores d ON ' . $joinDoc . '
            WHERE t.Fecha = ?';
        $params = [$fecha];
        if ($this->agendaTieneClinica()) {
            $sql .= ' AND t.id_clinica = ?';
            $params[] = $this->idClinica;
        }

        if ($doctorFiltro > 0) {
            $sql .= ' AND t.Doctor = ?';
            $params[] = $doctorFiltro;
        }

        $sql .= ' ORDER BY t.hora IS NULL, t.hora ASC, t.id ASC';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    public function findById(int $id, bool $extAgenda): ?array
    {
        $pacienteExpr = $extAgenda
            ? "COALESCE(NULLIF(TRIM(t.paciente_nombre), ''), p.Nombres) AS paciente_nombre"
            : 'p.Nombres AS paciente_nombre';
        $extraSel = $extAgenda
            ? ', t.atendido, t.llegado, t.confirmado, t.falta_turno, t.pagado, t.motivo'
            : '';
        $joinDoc = 'd.id = t.Doctor';
        if ($this->agendaTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = t.id_clinica';
        }
        $sql = "SELECT t.id, t.Fecha, t.hora, t.NroHC, t.Doctor, t.estado, t.idorden, t.observaciones,
            {$pacienteExpr}, d.nombre AS doctor_nombre{$extraSel}
            FROM agenda_turnos t
            LEFT JOIN pacientes p ON " . $this->joinPacienteTurno() . '
            LEFT JOIN lista_doctores d ON ' . $joinDoc . '
            WHERE t.id = ?';
        $par = [$id];
        if ($this->agendaTieneClinica()) {
            $sql .= ' AND t.id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= '
            LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $row = $st->fetch();

        return $row ?: null;
    }

    /**
     * @return array{total:int,pendientes:int,atendidos:int,no_asistio:int,llegados:int,confirmados:int}
     */
    public function resumenDia(string $fecha, int $doctorFiltro, bool $extAgenda): array
    {
        $where = ' WHERE Fecha = ?';
        $params = [$fecha];
        if ($this->agendaTieneClinica()) {
            $where .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        if ($doctorFiltro > 0) {
            $where .= ' AND Doctor = ?';
            $params[] = $doctorFiltro;
        }

        if ($extAgenda) {
            $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'atendido' OR atendido = 1 THEN 1 ELSE 0 END) AS atendidos,
                SUM(CASE WHEN estado = 'no_asistio' OR falta_turno = 1 THEN 1 ELSE 0 END) AS no_asistio,
                SUM(CASE WHEN llegado = 1 THEN 1 ELSE 0 END) AS llegados,
                SUM(CASE WHEN confirmado = 1 THEN 1 ELSE 0 END) AS confirmados
            FROM agenda_turnos{$where}";
        } else {
            $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN estado = 'atendido' THEN 1 ELSE 0 END) AS atendidos,
                SUM(CASE WHEN estado = 'no_asistio' THEN 1 ELSE 0 END) AS no_asistio,
                0 AS llegados,
                0 AS confirmados
            FROM agenda_turnos{$where}";
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch();

        return [
            'total' => (int) ($r['total'] ?? 0),
            'pendientes' => (int) ($r['pendientes'] ?? 0),
            'atendidos' => (int) ($r['atendidos'] ?? 0),
            'no_asistio' => (int) ($r['no_asistio'] ?? 0),
            'llegados' => (int) ($r['llegados'] ?? 0),
            'confirmados' => (int) ($r['confirmados'] ?? 0),
        ];
    }

    public function updateQuickStatus(int $id, string $accion, bool $extAgenda): bool
    {
        if (!$extAgenda) {
            return false;
        }

        if ($accion === 'llego') {
            $sql = "UPDATE agenda_turnos SET llegado = 1, estado = IF(estado='no_asistio','pendiente',estado) WHERE id = ?";
            $par = [$id];
            if ($this->agendaTieneClinica()) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $st = $this->pdo->prepare($sql);

            return $st->execute($par);
        }
        if ($accion === 'confirmado') {
            $sql = 'UPDATE agenda_turnos SET confirmado = 1 WHERE id = ?';
            $par = [$id];
            if ($this->agendaTieneClinica()) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $st = $this->pdo->prepare($sql);

            return $st->execute($par);
        }
        if ($accion === 'atendido') {
            $sql = "UPDATE agenda_turnos SET atendido = 1, falta_turno = 0, estado = 'atendido' WHERE id = ?";
            $par = [$id];
            if ($this->agendaTieneClinica()) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $st = $this->pdo->prepare($sql);

            return $st->execute($par);
        }
        if ($accion === 'ausente') {
            $sql = "UPDATE agenda_turnos SET falta_turno = 1, atendido = 0, estado = 'no_asistio' WHERE id = ?";
            $par = [$id];
            if ($this->agendaTieneClinica()) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $st = $this->pdo->prepare($sql);

            return $st->execute($par);
        }

        return false;
    }
}

