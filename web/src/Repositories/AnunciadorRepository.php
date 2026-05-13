<?php

declare(strict_types=1);

final class AnunciadorRepository
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

    public function existeTabla(): bool
    {
        return db_table_exists($this->pdo, 'agenda_llamados');
    }

    public function llamarDesdeTurno(int $idTurno, string $consultorio, ?int $idUsuarioAccion, string $origenAccion): array
    {
        if (!$this->existeTabla()) {
            return [false, 'Falta la tabla agenda_llamados (ejecutá migration_030_agenda_llamados.sql).'];
        }
        if ($idTurno < 1) {
            return [false, 'Turno inválido.'];
        }

        $selLlegado = db_table_has_column($this->pdo, 'agenda_turnos', 'llegado')
            ? 't.llegado'
            : '0 AS llegado';
        $selPacienteAgenda = db_table_has_column($this->pdo, 'agenda_turnos', 'paciente_nombre')
            ? 't.paciente_nombre'
            : "'' AS paciente_agenda_nombre";
        $st = $this->pdo->prepare(
            "SELECT t.id, t.id_clinica, t.NroHC, t.Doctor, {$selLlegado}, t.estado,
                    {$selPacienteAgenda},
                    p.Nombres AS paciente_nombre
             FROM agenda_turnos t
             LEFT JOIN pacientes p ON p.NroHC = t.NroHC" . (db_table_has_column($this->pdo, 'pacientes', 'id_clinica') ? ' AND p.id_clinica = t.id_clinica' : '') . "
             WHERE t.id = ?" . (db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica') ? ' AND t.id_clinica = ?' : '') . "
             LIMIT 1"
        );
        $params = [$idTurno];
        if (db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica')) {
            $params[] = $this->idClinica;
        }
        $st->execute($params);
        $turno = $st->fetch(PDO::FETCH_ASSOC);
        if (!$turno) {
            return [false, 'No se encontró el turno.'];
        }

        if (db_table_has_column($this->pdo, 'agenda_turnos', 'llegado') && (int) ($turno['llegado'] ?? 0) !== 1) {
            return [false, 'Solo se puede llamar turnos marcados como "Llegó".'];
        }

        $doctorId = (int) ($turno['Doctor'] ?? 0);
        if ($doctorId < 1) {
            return [false, 'El turno no tiene profesional asociado.'];
        }

        $nroHc = (int) ($turno['NroHC'] ?? 0);
        $nombreAgenda = trim((string) ($turno['paciente_agenda_nombre'] ?? ''));
        $nombrePacientes = trim((string) ($turno['paciente_nombre'] ?? ''));
        $nombreBase = $nombreAgenda !== '' ? $nombreAgenda : $nombrePacientes;
        $pacienteDisplay = $this->toPacienteDisplay($nombreBase, $nroHc);
        $origen = trim($origenAccion) !== '' ? trim($origenAccion) : 'doctor';
        $consultorioNorm = trim($consultorio) !== '' ? trim($consultorio) : 'Consultorio';

        $this->pdo->beginTransaction();
        try {
            $close = $this->pdo->prepare(
                "UPDATE agenda_llamados
                 SET estado_llamado = 'finalizado',
                     finalizado_en = COALESCE(finalizado_en, NOW()),
                     actualizado_en = NOW(),
                     id_usuario_accion = ?
                 WHERE id_turno = ?
                   AND id_clinica = ?
                   AND estado_llamado IN ('llamando', 'en_consultorio')"
            );
            $close->execute([(int) ($idUsuarioAccion ?? 0), $idTurno, $this->idClinica]);

            $ins = $this->pdo->prepare(
                "INSERT INTO agenda_llamados
                    (id_clinica, id_turno, id_doctor, nro_hc, paciente_display, consultorio, estado_llamado, origen_accion, id_usuario_accion, llamado_en)
                 VALUES (?, ?, ?, ?, ?, ?, 'llamando', ?, ?, NOW())"
            );
            $ins->execute([
                $this->idClinica,
                $idTurno,
                $doctorId,
                $nroHc,
                $pacienteDisplay,
                $consultorioNorm,
                $origen,
                (int) ($idUsuarioAccion ?? 0),
            ]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[control-salud] anunciador llamar error: ' . $e->getMessage());
            return [false, 'No se pudo registrar el llamado.'];
        }

        return [true, 'Paciente llamado en sala.'];
    }

    public function listarSala(string $estado = 'llamando', int $limit = 30): array
    {
        if (!$this->existeTabla()) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $estados = ['llamando', 'en_consultorio', 'llego', 'finalizado'];
        $estadoUse = in_array($estado, $estados, true) ? $estado : 'llamando';

        $joinPaciente = 'p.NroHC = l.nro_hc';
        if (db_table_has_column($this->pdo, 'pacientes', 'id_clinica')) {
            $joinPaciente .= ' AND p.id_clinica = l.id_clinica';
        }
        $sql = "SELECT l.id, l.id_turno, l.id_doctor, l.nro_hc,
                       CASE
                           WHEN l.paciente_display LIKE 'HC %' AND COALESCE(NULLIF(TRIM(p.Nombres), ''), '') <> ''
                               THEN p.Nombres
                           ELSE l.paciente_display
                       END AS paciente_display,
                       l.consultorio,
                       l.estado_llamado, l.llamado_en, l.en_consultorio_en, l.finalizado_en, l.actualizado_en,
                       d.nombre AS doctor_nombre
                FROM agenda_llamados l
                LEFT JOIN lista_doctores d ON d.id = l.id_doctor"
            . (db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica') ? ' AND d.id_clinica = l.id_clinica' : '') . "
                LEFT JOIN pacientes p ON {$joinPaciente}
                WHERE l.id_clinica = ? AND l.estado_llamado = ?
                ORDER BY l.prioridad DESC, l.llamado_en DESC, l.id DESC
                LIMIT {$limit}";
        $st = $this->pdo->prepare($sql);
        $st->execute([$this->idClinica, $estadoUse]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function cambiarEstado(int $idLlamado, string $estadoDestino, ?int $idUsuarioAccion): bool
    {
        if (!$this->existeTabla()) {
            return false;
        }
        $estado = trim($estadoDestino);
        if (!in_array($estado, ['en_consultorio', 'finalizado'], true)) {
            return false;
        }
        $sql = "UPDATE agenda_llamados
                SET estado_llamado = ?, actualizado_en = NOW(), id_usuario_accion = ?";
        if ($estado === 'en_consultorio') {
            $sql .= ", en_consultorio_en = COALESCE(en_consultorio_en, NOW())";
        } else {
            $sql .= ", finalizado_en = COALESCE(finalizado_en, NOW())";
        }
        $sql .= " WHERE id = ? AND id_clinica = ?";
        $st = $this->pdo->prepare($sql);
        return $st->execute([$estado, (int) ($idUsuarioAccion ?? 0), $idLlamado, $this->idClinica]);
    }

    public function finalizarPorTurno(int $idTurno, ?int $idUsuarioAccion): bool
    {
        if (!$this->existeTabla() || $idTurno < 1) {
            return false;
        }
        $st = $this->pdo->prepare(
            "UPDATE agenda_llamados
             SET estado_llamado = 'finalizado',
                 finalizado_en = COALESCE(finalizado_en, NOW()),
                 actualizado_en = NOW(),
                 id_usuario_accion = ?
             WHERE id_turno = ?
               AND id_clinica = ?
               AND estado_llamado IN ('llamando', 'en_consultorio')"
        );
        return $st->execute([(int) ($idUsuarioAccion ?? 0), $idTurno, $this->idClinica]);
    }

    private function toPacienteDisplay(string $nombreCompleto, int $nroHc): string
    {
        $nombreCompleto = trim($nombreCompleto);
        if ($nombreCompleto === '') {
            return 'HC ' . $nroHc;
        }
        $partes = preg_split('/\s+/', $nombreCompleto) ?: [];
        $first = (string) ($partes[0] ?? '');
        $last = (string) ($partes[count($partes) - 1] ?? '');
        if ($first === '') {
            return 'HC ' . $nroHc;
        }
        $lastInitial = $last !== '' && $last !== $first ? strtoupper(substr($last, 0, 1)) . '.' : '';
        $out = trim($first . ' ' . $lastInitial);
        return $out !== '' ? $out : ('HC ' . $nroHc);
    }
}

