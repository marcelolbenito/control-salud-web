<?php

declare(strict_types=1);

final class DoctoresRepository
{
    private const LEGACY_HORARIO_TABLE = 'Agenda Turnos Horarios';
    private const ESPECIALIDADES_TABLE = 'lista_especialidades_doctores';

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    private function doctoresTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica');
    }

    public function hasExtendedColumns(): bool
    {
        return db_table_has_column($this->pdo, 'lista_doctores', 'especialidad');
    }

    public function listForIndex(bool $extDoc): array
    {
        $joinUsuario = $this->usuariosDoctorDisponible();
        $sel = $extDoc
            ? 'SELECT d.id, d.nombre, d.especialidad, d.matricula, d.telefono, d.medicoconvenio, d.activo'
            : 'SELECT d.id, d.nombre, d.medicoconvenio, d.activo';
        $sel .= $joinUsuario ? ', u.usuario AS usuario_login, u.activo AS usuario_activo' : ', NULL AS usuario_login, NULL AS usuario_activo';
        $sel .= ' FROM lista_doctores d';
        if ($joinUsuario) {
            $sel .= ' LEFT JOIN usuarios u ON u.id_doctor = d.id';
            if ($this->doctoresTieneClinica() && db_table_has_column($this->pdo, 'usuarios', 'id_clinica')) {
                $sel .= ' AND u.id_clinica = d.id_clinica';
            }
        }
        if ($this->doctoresTieneClinica()) {
            $sel .= ' WHERE d.id_clinica = ?';
        }
        $sel .= ' ORDER BY d.nombre ASC LIMIT 500';
        if ($this->doctoresTieneClinica()) {
            $st = $this->pdo->prepare($sel);
            $st->execute([$this->idClinica]);

            return $st->fetchAll();
        }

        return $this->pdo->query($sel)->fetchAll();
    }

    public function usuariosDoctorDisponible(): bool
    {
        return db_table_exists($this->pdo, 'usuarios')
            && db_table_has_column($this->pdo, 'usuarios', 'usuario')
            && db_table_has_column($this->pdo, 'usuarios', 'password_hash')
            && db_table_has_column($this->pdo, 'usuarios', 'rol')
            && db_table_has_column($this->pdo, 'usuarios', 'id_doctor');
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findUsuarioByDoctor(int $doctorId): ?array
    {
        if ($doctorId < 1 || !$this->usuariosDoctorDisponible()) {
            return null;
        }

        $sql = 'SELECT id, usuario, nombre, email, activo, rol, id_doctor';
        if (db_table_has_column($this->pdo, 'usuarios', 'id_clinica')) {
            $sql .= ', id_clinica';
        }
        $sql .= ' FROM usuarios WHERE id_doctor = ?';
        $params = [$doctorId];
        if (db_table_has_column($this->pdo, 'usuarios', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function usuarioLoginDisponible(string $usuario, int $excludeUserId = 0): bool
    {
        if (!$this->usuariosDoctorDisponible()) {
            return false;
        }
        $sql = 'SELECT id FROM usuarios WHERE usuario = ?';
        $params = [$usuario];
        if ($excludeUserId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeUserId;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return !$st->fetch();
    }

    public function guardarUsuarioDoctor(
        int $doctorId,
        string $usuario,
        ?string $clavePlano,
        string $nombre,
        int $activo,
        ?int $usuarioId = null
    ): int {
        if ($doctorId < 1 || !$this->usuariosDoctorDisponible()) {
            return 0;
        }

        $usuario = trim($usuario);
        $nombre = trim($nombre);
        $activo = $activo ? 1 : 0;
        $existing = $usuarioId !== null && $usuarioId > 0 ? $usuarioId : 0;

        if ($existing > 0) {
            $sets = ['usuario = ?', 'nombre = ?', 'activo = ?', 'rol = ?', 'id_doctor = ?'];
            $params = [$usuario, $nombre, $activo, 'doctor', $doctorId];
            if ($clavePlano !== null && $clavePlano !== '') {
                $sets[] = 'password_hash = ?';
                $params[] = password_hash($clavePlano, PASSWORD_DEFAULT);
            }
            if (db_table_has_column($this->pdo, 'usuarios', 'id_clinica')) {
                $sets[] = 'id_clinica = ?';
                $params[] = $this->idClinica;
            }
            $params[] = $existing;
            $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?';
            $this->pdo->prepare($sql)->execute($params);

            return $existing;
        }

        $cols = ['usuario', 'password_hash', 'nombre', 'activo', 'rol', 'id_doctor'];
        $vals = [$usuario, password_hash((string) $clavePlano, PASSWORD_DEFAULT), $nombre, $activo, 'doctor', $doctorId];
        if (db_table_has_column($this->pdo, 'usuarios', 'id_clinica')) {
            $cols[] = 'id_clinica';
            $vals[] = $this->idClinica;
        }
        $colSql = implode(', ', $cols);
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        $st = $this->pdo->prepare('INSERT INTO usuarios (' . $colSql . ') VALUES (' . $ph . ')');
        $st->execute($vals);

        return (int) $this->pdo->lastInsertId();
    }

    public function desactivarUsuarioDoctor(int $doctorId): void
    {
        if ($doctorId < 1 || !$this->usuariosDoctorDisponible()) {
            return;
        }
        $sql = 'UPDATE usuarios SET activo = 0 WHERE id_doctor = ?';
        $params = [$doctorId];
        if (db_table_has_column($this->pdo, 'usuarios', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $this->pdo->prepare($sql)->execute($params);
    }

    public function listActivos(): array
    {
        if ($this->doctoresTieneClinica()) {
            $st = $this->pdo->prepare(
                'SELECT id, nombre FROM lista_doctores WHERE activo = 1 AND id_clinica = ? ORDER BY nombre ASC'
            );
            $st->execute([$this->idClinica]);

            return $st->fetchAll();
        }

        return $this->pdo->query(
            'SELECT id, nombre FROM lista_doctores WHERE activo = 1 ORDER BY nombre ASC'
        )->fetchAll();
    }

    public function listAllOrdered(): array
    {
        if ($this->doctoresTieneClinica()) {
            $st = $this->pdo->prepare('SELECT id, nombre FROM lista_doctores WHERE id_clinica = ? ORDER BY nombre ASC');
            $st->execute([$this->idClinica]);

            return $st->fetchAll();
        }

        return $this->pdo->query('SELECT id, nombre FROM lista_doctores ORDER BY nombre ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM lista_doctores WHERE id = ?';
        $par = [$id];
        if ($this->doctoresTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function hasEspecialidadesCatalog(): bool
    {
        return db_table_exists($this->pdo, self::ESPECIALIDADES_TABLE);
    }

    /**
     * @return list<array{id:int,nombre:string}>
     */
    public function listEspecialidadesCatalog(): array
    {
        if (!$this->hasEspecialidadesCatalog()) {
            return [];
        }
        try {
            return $this->pdo->query(
                'SELECT id, nombre FROM `' . self::ESPECIALIDADES_TABLE . '` ORDER BY nombre ASC'
            )->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findEspecialidadNombreById(int $id): ?string
    {
        if ($id < 1 || !$this->hasEspecialidadesCatalog()) {
            return null;
        }
        $st = $this->pdo->prepare(
            'SELECT nombre FROM `' . self::ESPECIALIDADES_TABLE . '` WHERE id = ? LIMIT 1'
        );
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $nombre = trim((string) ($row['nombre'] ?? ''));
        return $nombre !== '' ? $nombre : null;
    }

    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM lista_doctores WHERE id = ?';
        $par = [$id];
        if ($this->doctoresTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function deactivateById(int $id): void
    {
        $sql = 'UPDATE lista_doctores SET activo = 0 WHERE id = ?';
        $par = [$id];
        if ($this->doctoresTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    /**
     * @return array{total:int,detalle:array<string,int>}
     */
    public function linkedUsageCounts(int $doctorId): array
    {
        $checks = [
            'agenda_turnos' => ['table' => 'agenda_turnos', 'where' => 'Doctor'],
            'ordenes' => ['table' => 'Pacientes Ordenes', 'where' => 'iddoctor'],
            'sesiones' => ['table' => 'pacientes_sesiones', 'where' => 'iddoctor'],
            'consultas' => ['table' => 'consultas', 'where' => 'iddoctor'],
            'caja' => ['table' => 'caja', 'where' => 'doctor'],
        ];

        $detalle = [];
        $total = 0;
        foreach ($checks as $key => $cfg) {
            $tbl = (string) $cfg['table'];
            $col = (string) $cfg['where'];
            if (!db_table_exists($this->pdo, $tbl) || !db_table_has_column($this->pdo, $tbl, $col)) {
                $detalle[$key] = 0;
                continue;
            }
            $sql = 'SELECT COUNT(*) AS c FROM `' . str_replace('`', '', $tbl) . '` WHERE `' . str_replace('`', '', $col) . '` = ?';
            $par = [$doctorId];
            if (db_table_has_column($this->pdo, $tbl, 'id_clinica')) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $st = $this->pdo->prepare($sql);
            $st->execute($par);
            $cnt = (int) (($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0));
            $detalle[$key] = $cnt;
            $total += $cnt;
        }

        return ['total' => $total, 'detalle' => $detalle];
    }

    public function insertExtended(
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas,
        string $especialidad,
        string $matricula,
        string $telefono,
        string $domicilio,
        string $localidad,
        string $consultorio
    ): void {
        if ($this->doctoresTieneClinica()) {
            $st = $this->pdo->prepare(
                'INSERT INTO lista_doctores (id_clinica, nombre, medicoconvenio, bloquearmisconsultas, activo, notas,
                especialidad, matricula, telefono, domicilio, localidad, consultorio)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $this->idClinica, $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas,
                $especialidad, $matricula, $telefono, $domicilio, $localidad, $consultorio,
            ]);

            return;
        }
        $st = $this->pdo->prepare(
            'INSERT INTO lista_doctores (nombre, medicoconvenio, bloquearmisconsultas, activo, notas,
            especialidad, matricula, telefono, domicilio, localidad, consultorio)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas,
            $especialidad, $matricula, $telefono, $domicilio, $localidad, $consultorio,
        ]);
    }

    public function updateExtended(
        int $id,
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas,
        string $especialidad,
        string $matricula,
        string $telefono,
        string $domicilio,
        string $localidad,
        string $consultorio
    ): void {
        $sql = 'UPDATE lista_doctores SET nombre=?, medicoconvenio=?, bloquearmisconsultas=?, activo=?, notas=?,
            especialidad=?, matricula=?, telefono=?, domicilio=?, localidad=?, consultorio=?
            WHERE id=?';
        $par = [
            $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas,
            $especialidad, $matricula, $telefono, $domicilio, $localidad, $consultorio,
            $id,
        ];
        if ($this->doctoresTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function insertBase(
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas
    ): void {
        if ($this->doctoresTieneClinica()) {
            $st = $this->pdo->prepare(
                'INSERT INTO lista_doctores (id_clinica, nombre, medicoconvenio, bloquearmisconsultas, activo, notas) VALUES (?,?,?,?,?,?)'
            );
            $st->execute([$this->idClinica, $nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas]);

            return;
        }
        $st = $this->pdo->prepare(
            'INSERT INTO lista_doctores (nombre, medicoconvenio, bloquearmisconsultas, activo, notas) VALUES (?,?,?,?,?)'
        );
        $st->execute([$nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas]);
    }

    public function updateBase(
        int $id,
        string $nombre,
        int $medicoconvenio,
        int $bloquearmisconsultas,
        int $activo,
        string $notas
    ): void {
        $sql = 'UPDATE lista_doctores SET nombre=?, medicoconvenio=?, bloquearmisconsultas=?, activo=?, notas=? WHERE id=?';
        $par = [$nombre, $medicoconvenio, $bloquearmisconsultas, $activo, $notas, $id];
        if ($this->doctoresTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function hasLegacyHorarioTable(): bool
    {
        return db_table_exists($this->pdo, self::LEGACY_HORARIO_TABLE)
            && db_table_has_column($this->pdo, self::LEGACY_HORARIO_TABLE, 'iddoctor')
            && db_table_has_column($this->pdo, self::LEGACY_HORARIO_TABLE, 'fechadesde')
            && db_table_has_column($this->pdo, self::LEGACY_HORARIO_TABLE, 'fechahasta');
    }

    public function findLegacyHorarioByDoctor(int $doctorId): ?array
    {
        if ($doctorId < 1 || !$this->hasLegacyHorarioTable()) {
            return null;
        }

        $sql = 'SELECT * FROM `' . str_replace('`', '', self::LEGACY_HORARIO_TABLE) . '`
                WHERE iddoctor = ?
                ORDER BY fechadesde DESC
                LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([$doctorId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function saveLegacyHorarioByDoctor(int $doctorId, array $data): void
    {
        if ($doctorId < 1 || !$this->hasLegacyHorarioTable()) {
            return;
        }

        $table = '`' . str_replace('`', '', self::LEGACY_HORARIO_TABLE) . '`';
        $current = $this->findLegacyHorarioByDoctor($doctorId);

        if ($current && isset($current['id'])) {
            $sql = "UPDATE {$table} SET
                    fechadesde=?, fechahasta=?,
                    DoMaDesde=?, DoMaHasta=?, DoTaDesde=?, DoTaHasta=?,
                    LuMaDesde=?, LuMaHasta=?, LuTaDesde=?, LuTaHasta=?,
                    MaMaDesde=?, MaMaHasta=?, MaTaDesde=?, MaTaHasta=?,
                    MiMaDesde=?, MiMaHasta=?, MiTaDesde=?, MiTaHasta=?,
                    JuMaDesde=?, JuMaHasta=?, JuTaDesde=?, JuTaHasta=?,
                    ViMaDesde=?, ViMaHasta=?, ViTaDesde=?, ViTaHasta=?,
                    SaMaDesde=?, SaMaHasta=?, SaTaDesde=?, SaTaHasta=?,
                    durtur1=?, durtur2=?, durtur3=?, durtur4=?, durtur5=?, durtur6=?, durtur7=?
                WHERE id = ?";
            $params = [
                $data['fechadesde'], $data['fechahasta'],
                $data['DoMaDesde'], $data['DoMaHasta'], $data['DoTaDesde'], $data['DoTaHasta'],
                $data['LuMaDesde'], $data['LuMaHasta'], $data['LuTaDesde'], $data['LuTaHasta'],
                $data['MaMaDesde'], $data['MaMaHasta'], $data['MaTaDesde'], $data['MaTaHasta'],
                $data['MiMaDesde'], $data['MiMaHasta'], $data['MiTaDesde'], $data['MiTaHasta'],
                $data['JuMaDesde'], $data['JuMaHasta'], $data['JuTaDesde'], $data['JuTaHasta'],
                $data['ViMaDesde'], $data['ViMaHasta'], $data['ViTaDesde'], $data['ViTaHasta'],
                $data['SaMaDesde'], $data['SaMaHasta'], $data['SaTaDesde'], $data['SaTaHasta'],
                $data['durtur1'], $data['durtur2'], $data['durtur3'], $data['durtur4'], $data['durtur5'], $data['durtur6'], $data['durtur7'],
                (int) $current['id'],
            ];
            $st = $this->pdo->prepare($sql);
            $st->execute($params);
            return;
        }

        $sql = "INSERT INTO {$table}
                (iddoctor, fechadesde, fechahasta,
                 DoMaDesde, DoMaHasta, DoTaDesde, DoTaHasta,
                 LuMaDesde, LuMaHasta, LuTaDesde, LuTaHasta,
                 MaMaDesde, MaMaHasta, MaTaDesde, MaTaHasta,
                 MiMaDesde, MiMaHasta, MiTaDesde, MiTaHasta,
                 JuMaDesde, JuMaHasta, JuTaDesde, JuTaHasta,
                 ViMaDesde, ViMaHasta, ViTaDesde, ViTaHasta,
                 SaMaDesde, SaMaHasta, SaTaDesde, SaTaHasta,
                 durtur1, durtur2, durtur3, durtur4, durtur5, durtur6, durtur7)
                VALUES
                (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = [
            $doctorId, $data['fechadesde'], $data['fechahasta'],
            $data['DoMaDesde'], $data['DoMaHasta'], $data['DoTaDesde'], $data['DoTaHasta'],
            $data['LuMaDesde'], $data['LuMaHasta'], $data['LuTaDesde'], $data['LuTaHasta'],
            $data['MaMaDesde'], $data['MaMaHasta'], $data['MaTaDesde'], $data['MaTaHasta'],
            $data['MiMaDesde'], $data['MiMaHasta'], $data['MiTaDesde'], $data['MiTaHasta'],
            $data['JuMaDesde'], $data['JuMaHasta'], $data['JuTaDesde'], $data['JuTaHasta'],
            $data['ViMaDesde'], $data['ViMaHasta'], $data['ViTaDesde'], $data['ViTaHasta'],
            $data['SaMaDesde'], $data['SaMaHasta'], $data['SaTaDesde'], $data['SaTaHasta'],
            $data['durtur1'], $data['durtur2'], $data['durtur3'], $data['durtur4'], $data['durtur5'], $data['durtur6'], $data['durtur7'],
        ];
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }
}

