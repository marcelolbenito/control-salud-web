<?php

declare(strict_types=1);

final class RecordatorioRepository
{
    private const TABLE = 'agenda_recordatorios';

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    public function tableExists(): bool
    {
        return db_table_exists($this->pdo, self::TABLE);
    }

    public function existsForTurnoTipo(int $idTurno, string $tipo): bool
    {
        if (!$this->tableExists() || $idTurno < 1) {
            return false;
        }
        $sql = 'SELECT id FROM ' . self::TABLE . ' WHERE id_turno = ? AND tipo = ?';
        $params = [$idTurno, $tipo];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return (bool) $st->fetch();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insert(array $row): int
    {
        $row['id_clinica'] = $this->idClinica;
        $cols = [
            'id_clinica', 'id_turno', 'id_doctor', 'nro_hc', 'telefono_e164', 'tipo', 'canal', 'proveedor',
            'template_codigo', 'mensaje_render', 'enlace_whatsapp', 'estado', 'programado_en',
        ];
        $use = [];
        $vals = [];
        foreach ($cols as $c) {
            if (!array_key_exists($c, $row) || !db_table_has_column($this->pdo, self::TABLE, $c)) {
                continue;
            }
            $use[] = $c;
            $vals[] = $row[$c];
        }
        $sql = 'INSERT INTO ' . self::TABLE . ' (`' . implode('`, `', $use) . '`) VALUES ('
            . implode(', ', array_fill(0, count($use), '?')) . ')';
        $st = $this->pdo->prepare($sql);
        $st->execute($vals);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE id = ?';
        $params = [$id];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBandeja(string $estadoFiltro = '', int $limit = 200): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT r.*, t.Fecha AS turno_fecha, t.hora AS turno_hora, d.nombre AS doctor_nombre
            FROM ' . self::TABLE . ' r
            LEFT JOIN agenda_turnos t ON t.id = r.id_turno
            LEFT JOIN lista_doctores d ON d.id = r.id_doctor
            WHERE 1=1';
        $params = [];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND r.id_clinica = ?';
            $params[] = $this->idClinica;
        }
        if ($estadoFiltro !== '') {
            $sql .= ' AND r.estado = ?';
            $params[] = $estadoFiltro;
        }
        $sql .= ' ORDER BY r.programado_en DESC, r.id DESC LIMIT ' . $limit;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendientesParaProcesar(int $limit = 100): array
    {
        if (!$this->tableExists()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT * FROM ' . self::TABLE . '
            WHERE estado = ? AND programado_en <= NOW()';
        $params = ['pendiente'];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $sql .= ' ORDER BY programado_en ASC, id ASC LIMIT ' . $limit;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function turnosParaRecordatorio(string $fechaTurno): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaTurno)) {
            return [];
        }
        $sql = 'SELECT t.*, p.tel_celular, p.telefono, p.DNI, p.Nombres, p.apellido, d.nombre AS doctor_nombre
            FROM agenda_turnos t
            INNER JOIN pacientes p ON p.NroHC = t.NroHC
            LEFT JOIN lista_doctores d ON d.id = t.Doctor
            WHERE t.Fecha = ?
              AND COALESCE(t.falta_turno, 0) = 0
              AND COALESCE(t.atendido, 0) = 0
              AND COALESCE(t.estado, \'pendiente\') NOT IN (\'cancelado\', \'no_asistio\')';
        $params = [$fechaTurno];
        if (db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica')) {
            $sql .= ' AND t.id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function turnoConPaciente(int $idTurno): ?array
    {
        if ($idTurno < 1) {
            return null;
        }
        $sql = 'SELECT t.*, p.tel_celular, p.telefono, p.DNI, p.Nombres, p.apellido, d.nombre AS doctor_nombre
            FROM agenda_turnos t
            INNER JOIN pacientes p ON p.NroHC = t.NroHC
            LEFT JOIN lista_doctores d ON d.id = t.Doctor
            WHERE t.id = ?';
        $params = [$idTurno];
        if (db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica')) {
            $sql .= ' AND t.id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public function plantillaSucursal(int $idSucursal, string $tipo): string
    {
        if (!db_table_exists($this->pdo, 'Sucursales')) {
            return '';
        }
        $col = $tipo === 'confirmacion' ? 'mensajerecordatorio'
            : ($tipo === 'anulacion' ? 'mensajeanular' : 'mensajerecordatorio2');
        if (!db_table_has_column($this->pdo, 'Sucursales', $col)) {
            return '';
        }
        $st = $this->pdo->prepare('SELECT `' . $col . '` FROM Sucursales WHERE id = ? LIMIT 1');
        $st->execute([$idSucursal]);
        $v = $st->fetchColumn();

        return is_string($v) ? trim($v) : '';
    }

    /** @return list<array{id:int,nombre:string}> */
    public function listSucursalesPlantillas(): array
    {
        if (!db_table_exists($this->pdo, 'Sucursales')) {
            return [];
        }
        $cols = ['id'];
        $hasNombre = db_table_has_column($this->pdo, 'Sucursales', 'nombre');
        if ($hasNombre) {
            $cols[] = 'nombre';
        }
        $sql = 'SELECT `' . implode('`, `', $cols) . '` FROM Sucursales WHERE id IS NOT NULL ORDER BY id';
        $st = $this->pdo->query($sql);
        if ($st === false) {
            return [];
        }
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'nombre' => trim((string) ($row['nombre'] ?? '')) ?: ('Sucursal ' . $id),
            ];
        }

        return $out;
    }

    /** @return array{mensajerecordatorio:string,mensajerecordatorio2:string,mensajeanular:string,nombre:string}|null */
    public function plantillasSucursal(int $idSucursal): ?array
    {
        if (!db_table_exists($this->pdo, 'Sucursales') || $idSucursal < 1) {
            return null;
        }
        $cols = ['mensajerecordatorio', 'mensajerecordatorio2', 'mensajeanular'];
        $select = ['id'];
        if (db_table_has_column($this->pdo, 'Sucursales', 'nombre')) {
            $select[] = 'nombre';
        }
        foreach ($cols as $c) {
            if (db_table_has_column($this->pdo, 'Sucursales', $c)) {
                $select[] = $c;
            }
        }
        $st = $this->pdo->prepare('SELECT `' . implode('`, `', $select) . '` FROM Sucursales WHERE id = ? LIMIT 1');
        $st->execute([$idSucursal]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return [
            'nombre' => trim((string) ($row['nombre'] ?? '')) ?: ('Sucursal ' . $idSucursal),
            'mensajerecordatorio' => trim((string) ($row['mensajerecordatorio'] ?? '')),
            'mensajerecordatorio2' => trim((string) ($row['mensajerecordatorio2'] ?? '')),
            'mensajeanular' => trim((string) ($row['mensajeanular'] ?? '')),
        ];
    }

    public function guardarPlantillasSucursal(int $idSucursal, string $confirmacion, string $recordatorio, string $anulacion): bool
    {
        if (!db_table_exists($this->pdo, 'Sucursales') || $idSucursal < 1) {
            return false;
        }
        $sets = [];
        $params = [];
        $map = [
            'mensajerecordatorio' => $confirmacion,
            'mensajerecordatorio2' => $recordatorio,
            'mensajeanular' => $anulacion,
        ];
        foreach ($map as $col => $val) {
            if (!db_table_has_column($this->pdo, 'Sucursales', $col)) {
                continue;
            }
            $sets[] = '`' . $col . '` = ?';
            $params[] = $val;
        }
        if ($sets === []) {
            return false;
        }
        $params[] = $idSucursal;
        $sql = 'UPDATE Sucursales SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $st = $this->pdo->prepare($sql);

        return $st->execute($params);
    }

    public function marcarListo(int $id, string $mensaje, string $waUrl): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET mensaje_render = ?, enlace_whatsapp = ?, estado = ?, intentos = intentos + 1, ultimo_error = NULL';
        $params = [$mensaje, $waUrl, 'listo'];
        if (db_table_has_column($this->pdo, self::TABLE, 'actualizado_en')) {
            $sql .= ', actualizado_en = CURRENT_TIMESTAMP';
        }
        $sql .= ' WHERE id = ?';
        $params[] = $id;
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function marcarEnviado(int $id): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET estado = ?, enviado_en = CURRENT_TIMESTAMP';
        if (db_table_has_column($this->pdo, self::TABLE, 'actualizado_en')) {
            $sql .= ', actualizado_en = CURRENT_TIMESTAMP';
        }
        $sql .= ' WHERE id = ?';
        $params = ['enviado', $id];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function marcarEnviadoApi(int $id, string $mensaje, string $waUrl, ?string $idMensajeExterno): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET mensaje_render = ?, enlace_whatsapp = ?, estado = ?, enviado_en = CURRENT_TIMESTAMP, intentos = intentos + 1, ultimo_error = NULL';
        $params = [$mensaje, $waUrl, 'enviado'];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_mensaje_externo') && $idMensajeExterno !== null && $idMensajeExterno !== '') {
            $sql .= ', id_mensaje_externo = ?';
            $params[] = $idMensajeExterno;
        }
        if (db_table_has_column($this->pdo, self::TABLE, 'actualizado_en')) {
            $sql .= ', actualizado_en = CURRENT_TIMESTAMP';
        }
        $sql .= ' WHERE id = ?';
        $params[] = $id;
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findRecientePorTelefono(string $telefonoE164, int $horas = 72): ?array
    {
        if (!$this->tableExists() || $telefonoE164 === '') {
            return null;
        }
        $sql = 'SELECT * FROM ' . self::TABLE . '
            WHERE telefono_e164 = ?
              AND estado IN (\'enviado\', \'listo\')
              AND programado_en >= DATE_SUB(NOW(), INTERVAL ? HOUR)';
        $params = [$telefonoE164, max(1, $horas)];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $sql .= ' ORDER BY programado_en DESC, id DESC LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public function registrarRespuesta(int $id, string $texto, string $codigo): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET respuesta_texto = ?, respuesta_codigo = ?, respuesta_en = CURRENT_TIMESTAMP, estado = ?';
        $estado = $codigo === 'confirmado' ? 'confirmado' : ($codigo === 'cancelado' ? 'cancelado' : 'enviado');
        $params = [$texto, $codigo, $estado];
        if (db_table_has_column($this->pdo, self::TABLE, 'actualizado_en')) {
            $sql .= ', actualizado_en = CURRENT_TIMESTAMP';
        }
        $sql .= ' WHERE id = ?';
        $params[] = $id;
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function marcarTurnoConfirmado(int $idTurno): void
    {
        if ($idTurno < 1 || !db_table_exists($this->pdo, 'agenda_turnos')) {
            return;
        }
        if (!db_table_has_column($this->pdo, 'agenda_turnos', 'confirmado')) {
            return;
        }
        $sql = 'UPDATE agenda_turnos SET confirmado = 1';
        if (db_table_has_column($this->pdo, 'agenda_turnos', 'estado')) {
            $sql .= ", estado = 'pendiente'";
        }
        $sql .= ' WHERE id = ?';
        $params = [$idTurno];
        if (db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function marcarTurnoCanceladoPorPaciente(int $idTurno): void
    {
        if ($idTurno < 1 || !db_table_exists($this->pdo, 'agenda_turnos')) {
            return;
        }
        if (!db_table_has_column($this->pdo, 'agenda_turnos', 'estado')) {
            return;
        }
        $sql = "UPDATE agenda_turnos SET estado = 'cancelado'";
        $params = [];
        if (db_table_has_column($this->pdo, 'agenda_turnos', 'confirmado')) {
            $sql .= ', confirmado = 0';
        }
        $sql .= ' WHERE id = ?';
        $params[] = $idTurno;
        if (db_table_has_column($this->pdo, 'agenda_turnos', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function marcarError(int $id, string $error): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET estado = ?, ultimo_error = ?, intentos = intentos + 1';
        if (db_table_has_column($this->pdo, self::TABLE, 'actualizado_en')) {
            $sql .= ', actualizado_en = CURRENT_TIMESTAMP';
        }
        $sql .= ' WHERE id = ?';
        $params = ['error', mb_substr($error, 0, 255), $id];
        if (db_table_has_column($this->pdo, self::TABLE, 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }
}
