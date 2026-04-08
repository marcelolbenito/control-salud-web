<?php

declare(strict_types=1);

final class TurnosRepository
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasExtendedAgendaColumns(): bool
    {
        return db_table_has_column($this->pdo, 'agenda_turnos', 'motivo');
    }

    public function listDoctores(): array
    {
        return $this->pdo->query('SELECT id, nombre FROM lista_doctores ORDER BY nombre ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM agenda_turnos WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function deleteById(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM agenda_turnos WHERE id = ?');
        $st->execute([$id]);
    }

    public function pacienteExistsByNroHC(int $nroHC): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM pacientes WHERE NroHC = ? LIMIT 1');
        $st->execute([$nroHC]);
        return (bool) $st->fetch();
    }

    public function pacienteNombreParaTurno(int $nroHC): string
    {
        if (db_table_has_column($this->pdo, 'pacientes', 'apellido')) {
            $stn = $this->pdo->prepare('SELECT apellido, Nombres FROM pacientes WHERE NroHC = ? LIMIT 1');
            $stn->execute([$nroHC]);
            $pn = $stn->fetch();
            if ($pn) {
                $a = trim((string) ($pn['apellido'] ?? ''));
                $n = trim((string) ($pn['Nombres'] ?? ''));
                return trim($a . ' ' . $n) ?: $n;
            }
        } else {
            $stn = $this->pdo->prepare('SELECT Nombres FROM pacientes WHERE NroHC = ? LIMIT 1');
            $stn->execute([$nroHC]);
            $pn = $stn->fetch();
            if ($pn) {
                return trim((string) ($pn['Nombres'] ?? ''));
            }
        }
        return '';
    }

    /**
     * @param array<string, mixed> $ex
     */
    public function insertExtended(
        string $fecha,
        $hora,
        int $nroHC,
        int $doctor,
        $idorden,
        string $estado,
        string $observaciones,
        array $ex
    ): void {
        $st = $this->pdo->prepare(
            'INSERT INTO agenda_turnos (Fecha, hora, NroHC, Doctor, idorden, estado, observaciones,
            paciente_nombre, motivo, atendido, pagado, llegado, llegado_hora, confirmado, falta_turno, reingresar, primera_vez,
            num_sesion, id_sesion, id_caja, usuario_asignado, fechahora_asignado, alta_paci_web)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones,
            $ex['paciente_nombre'] ?: null, $ex['motivo'], $ex['atendido'], $ex['pagado'], $ex['llegado'], $ex['llegado_hora'],
            $ex['confirmado'], $ex['falta_turno'], $ex['reingresar'], $ex['primera_vez'],
            $ex['num_sesion'], $ex['id_sesion'], $ex['id_caja'], $ex['usuario_asignado'], $ex['fechahora_asignado'], $ex['alta_paci_web'],
        ]);
    }

    /**
     * @param array<string, mixed> $ex
     */
    public function updateExtended(
        int $id,
        string $fecha,
        $hora,
        int $nroHC,
        int $doctor,
        $idorden,
        string $estado,
        string $observaciones,
        array $ex
    ): void {
        $st = $this->pdo->prepare(
            'UPDATE agenda_turnos SET Fecha=?, hora=?, NroHC=?, Doctor=?, idorden=?, estado=?, observaciones=?,
            paciente_nombre=?, motivo=?, atendido=?, pagado=?, llegado=?, llegado_hora=?, confirmado=?, falta_turno=?, reingresar=?, primera_vez=?,
            num_sesion=?, id_sesion=?, id_caja=?, usuario_asignado=?, fechahora_asignado=?, alta_paci_web=?
            WHERE id=?'
        );
        $st->execute([
            $fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones,
            $ex['paciente_nombre'] ?: null, $ex['motivo'], $ex['atendido'], $ex['pagado'], $ex['llegado'], $ex['llegado_hora'],
            $ex['confirmado'], $ex['falta_turno'], $ex['reingresar'], $ex['primera_vez'],
            $ex['num_sesion'], $ex['id_sesion'], $ex['id_caja'], $ex['usuario_asignado'], $ex['fechahora_asignado'], $ex['alta_paci_web'],
            $id,
        ]);
    }

    public function insertBase(
        string $fecha,
        $hora,
        int $nroHC,
        int $doctor,
        $idorden,
        string $estado,
        string $observaciones
    ): void {
        $st = $this->pdo->prepare(
            'INSERT INTO agenda_turnos (Fecha, hora, NroHC, Doctor, idorden, estado, observaciones) VALUES (?,?,?,?,?,?,?)'
        );
        $st->execute([$fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones]);
    }

    public function updateBase(
        int $id,
        string $fecha,
        $hora,
        int $nroHC,
        int $doctor,
        $idorden,
        string $estado,
        string $observaciones
    ): void {
        $st = $this->pdo->prepare(
            'UPDATE agenda_turnos SET Fecha=?, hora=?, NroHC=?, Doctor=?, idorden=?, estado=?, observaciones=? WHERE id=?'
        );
        $st->execute([$fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones, $id]);
    }
}
