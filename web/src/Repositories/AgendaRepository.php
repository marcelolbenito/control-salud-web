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
}

