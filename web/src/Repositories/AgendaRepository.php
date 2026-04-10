<?php

declare(strict_types=1);

final class AgendaRepository
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

        $sql = "SELECT t.id, t.Fecha, t.hora, t.NroHC, t.Doctor, t.estado, t.idorden, t.observaciones,
            {$pacienteExpr}, d.nombre AS doctor_nombre{$extraSel}
            FROM agenda_turnos t
            LEFT JOIN pacientes p ON p.NroHC = t.NroHC
            LEFT JOIN lista_doctores d ON d.id = t.Doctor
            WHERE t.Fecha = ?";
        $params = [$fecha];

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
        $st = $this->pdo->prepare(
            "SELECT t.id, t.Fecha, t.hora, t.NroHC, t.Doctor, t.estado, t.idorden, t.observaciones,
            {$pacienteExpr}, d.nombre AS doctor_nombre{$extraSel}
            FROM agenda_turnos t
            LEFT JOIN pacientes p ON p.NroHC = t.NroHC
            LEFT JOIN lista_doctores d ON d.id = t.Doctor
            WHERE t.id = ?
            LIMIT 1"
        );
        $st->execute([$id]);
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
            $st = $this->pdo->prepare("UPDATE agenda_turnos SET llegado = 1, estado = IF(estado='no_asistio','pendiente',estado) WHERE id = ?");
            return $st->execute([$id]);
        }
        if ($accion === 'confirmado') {
            $st = $this->pdo->prepare("UPDATE agenda_turnos SET confirmado = 1 WHERE id = ?");
            return $st->execute([$id]);
        }
        if ($accion === 'atendido') {
            $st = $this->pdo->prepare("UPDATE agenda_turnos SET atendido = 1, falta_turno = 0, estado = 'atendido' WHERE id = ?");
            return $st->execute([$id]);
        }
        if ($accion === 'ausente') {
            $st = $this->pdo->prepare("UPDATE agenda_turnos SET falta_turno = 1, atendido = 0, estado = 'no_asistio' WHERE id = ?");
            return $st->execute([$id]);
        }

        return false;
    }
}

