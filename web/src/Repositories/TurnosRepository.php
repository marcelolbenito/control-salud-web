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

    /**
     * @return array<string,int> hora HH:MM => cantidad de turnos
     */
    public function horasOcupadasPorFechaDoctor(string $fecha, int $doctor, int $excludeId = 0): array
    {
        if ($doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return [];
        }

        $sql = "SELECT DATE_FORMAT(hora, '%H:%i') AS h, COUNT(*) AS c
                FROM agenda_turnos
                WHERE Fecha = ? AND Doctor = ? AND hora IS NOT NULL";
        $params = [$fecha, $doctor];
        if ($excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= " GROUP BY DATE_FORMAT(hora, '%H:%i')";
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        $out = [];
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $h = (string) ($r['h'] ?? '');
            if ($h !== '') {
                $out[$h] = (int) ($r['c'] ?? 0);
            }
        }

        return $out;
    }

    /**
     * Búsqueda rápida para alta de turno (DNI y/o nombre/apellido).
     *
     * @return list<array{nrohc:int,dni:string,nombre:string}>
     */
    public function buscarPacientesTurno(string $q, int $limit = 12): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $lim = max(1, min(30, $limit));
        $hasApellido = db_table_has_column($this->pdo, 'pacientes', 'apellido');
        $sqlNombre = $hasApellido
            ? "TRIM(CONCAT(COALESCE(p.apellido,''), ' ', COALESCE(p.Nombres,'')))"
            : "TRIM(COALESCE(p.Nombres,''))";

        $like = '%' . $q . '%';
        $sql = "SELECT p.NroHC AS nrohc,
                       COALESCE(p.DNI,'') AS dni,
                       {$sqlNombre} AS nombre
                FROM pacientes p
                WHERE p.NroHC IS NOT NULL
                  AND (
                    p.DNI LIKE :like
                    OR p.Nombres LIKE :like
                    " . ($hasApellido ? " OR p.apellido LIKE :like " : '') . "
                    OR {$sqlNombre} LIKE :like
                  )
                ORDER BY p.NroHC DESC
                LIMIT {$lim}";
        $st = $this->pdo->prepare($sql);
        $st->execute(['like' => $like]);

        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = [
                'nrohc' => (int) ($r['nrohc'] ?? 0),
                'dni' => trim((string) ($r['dni'] ?? '')),
                'nombre' => trim((string) ($r['nombre'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * Turnos de un profesional en una hora puntual.
     *
     * @return list<array{id:int,hora:string,estado:string,nrohc:int,paciente:string}>
     */
    public function turnosEnHorario(string $fecha, int $doctor, string $hora): array
    {
        if ($doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return [];
        }

        $hasApellido = db_table_has_column($this->pdo, 'pacientes', 'apellido');
        $nombrePaciente = $hasApellido
            ? "TRIM(CONCAT(COALESCE(p.apellido,''), ' ', COALESCE(p.Nombres,'')))"
            : "TRIM(COALESCE(p.Nombres,''))";

        $sql = "SELECT t.id,
                       DATE_FORMAT(t.hora, '%H:%i') AS hora,
                       COALESCE(t.estado, 'pendiente') AS estado,
                       COALESCE(t.NroHC, 0) AS nrohc,
                       COALESCE(NULLIF(TRIM(t.paciente_nombre), ''), {$nombrePaciente}, '(sin nombre)') AS paciente
                FROM agenda_turnos t
                LEFT JOIN pacientes p ON p.NroHC = t.NroHC
                WHERE t.Fecha = ?
                  AND t.Doctor = ?
                  AND t.hora IS NOT NULL
                  AND DATE_FORMAT(t.hora, '%H:%i') = ?
                ORDER BY t.id ASC";
        $st = $this->pdo->prepare($sql);
        $st->execute([$fecha, $doctor, $hora]);

        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[] = [
                'id' => (int) ($r['id'] ?? 0),
                'hora' => (string) ($r['hora'] ?? $hora),
                'estado' => (string) ($r['estado'] ?? 'pendiente'),
                'nrohc' => (int) ($r['nrohc'] ?? 0),
                'paciente' => trim((string) ($r['paciente'] ?? '(sin nombre)')),
            ];
        }

        return $out;
    }

    private const LEGACY_HORARIO_TABLE = 'Agenda Turnos Horarios';

    /** PHP date('w'): 0=domingo … 6=sábado → prefijo columnas Access/VB6 */
    private const LEGACY_DIA_PREFIJOS = [
        0 => 'Do',
        1 => 'Lu',
        2 => 'Ma',
        3 => 'Mi',
        4 => 'Ju',
        5 => 'Vi',
        6 => 'Sa',
    ];

    /**
     * Slots + ocupación para la grilla de nuevo/editar turno (respeta `Agenda Turnos Horarios` si existe).
     *
     * @return array{slots: list<string>, occupied: array<string,int>, source: string, step: int, sin_franja_dia: bool}
     */
    public function disponibilidadVisual(string $fecha, int $doctor, int $excludeTurnoId = 0): array
    {
        $occupied = $this->horasOcupadasPorFechaDoctor($fecha, $doctor, $excludeTurnoId);
        // Fin exclusivo: último inicio 20:00 con paso 15 (misma lógica que la grilla fija anterior).
        $defaultSlots = $this->buildSlotList('08:00', '20:15', 15);

        if ($doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return [
                'slots' => $this->mergeSlotsWithOccupied($defaultSlots, $occupied),
                'occupied' => $occupied,
                'source' => 'none',
                'step' => 15,
                'sin_franja_dia' => false,
            ];
        }

        $legacy = $this->findLegacyHorarioRow($doctor, $fecha);
        if ($legacy === null) {
            return [
                'slots' => $this->mergeSlotsWithOccupied($defaultSlots, $occupied),
                'occupied' => $occupied,
                'source' => 'default',
                'step' => 15,
                'sin_franja_dia' => false,
            ];
        }

        $w = (int) date('w', strtotime($fecha));
        $pref = self::LEGACY_DIA_PREFIJOS[$w] ?? 'Lu';
        $durturCol = 'durtur' . (string) ($w + 1);
        $step = (int) ($legacy[$durturCol] ?? 0);
        if ($step < 5) {
            $step = 15;
        }

        $maD = $pref . 'MaDesde';
        $maH = $pref . 'MaHasta';
        $taD = $pref . 'TaDesde';
        $taH = $pref . 'TaHasta';

        $morning = $this->buildSlotListFromLegacyBounds($legacy[$maD] ?? null, $legacy[$maH] ?? null, $step);
        $afternoon = $this->buildSlotListFromLegacyBounds($legacy[$taD] ?? null, $legacy[$taH] ?? null, $step);
        $slots = array_values(array_unique(array_merge($morning, $afternoon)));
        sort($slots, SORT_STRING);

        $sinFranja = $slots === [];
        if ($sinFranja) {
            return [
                'slots' => $this->mergeSlotsWithOccupied($defaultSlots, $occupied),
                'occupied' => $occupied,
                'source' => 'legacy_no_day',
                'step' => 15,
                'sin_franja_dia' => true,
            ];
        }

        // Si la franja legacy queda demasiado limitada, completamos con la base
        // para asegurar opciones visibles al cargar un nuevo turno.
        if (count($slots) <= max(3, count($occupied))) {
            return [
                'slots' => $this->mergeSlotsWithOccupied($defaultSlots, $occupied),
                'occupied' => $occupied,
                'source' => 'legacy_sparse',
                'step' => 15,
                'sin_franja_dia' => false,
            ];
        }

        return [
            'slots' => $this->mergeSlotsWithOccupied($slots, $occupied),
            'occupied' => $occupied,
            'source' => 'legacy',
            'step' => $step,
            'sin_franja_dia' => false,
        ];
    }

    /**
     * @return array<string, mixed>|null fila legacy vigente para la fecha
     */
    public function findLegacyHorarioRow(int $doctor, string $fecha): ?array
    {
        if (!db_table_exists($this->pdo, self::LEGACY_HORARIO_TABLE)) {
            return null;
        }
        if ($doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return null;
        }

        $sql = 'SELECT * FROM `' . str_replace('`', '', self::LEGACY_HORARIO_TABLE) . '`
                WHERE iddoctor = ?
                  AND DATE(fechadesde) <= ?
                  AND DATE(fechahasta) >= ?
                ORDER BY fechadesde DESC
                LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute([$doctor, $fecha, $fecha]);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    /**
     * @param mixed $desdeHasta valores datetime Access (1899-12-30 HH:MM:SS) o null
     * @return list<string> HH:MM
     */
    private function buildSlotListFromLegacyBounds($desde, $hasta, int $stepMin): array
    {
        $from = $this->legacyDateTimeToHi($desde);
        $to = $this->legacyDateTimeToHi($hasta);
        if ($from === null || $to === null) {
            return [];
        }

        return $this->buildSlotList($from, $to, $stepMin);
    }

    /**
     * @param mixed $v
     */
    private function legacyDateTimeToHi($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $ts = strtotime((string) $v);
        if ($ts === false) {
            return null;
        }

        return date('H:i', $ts);
    }

    /**
     * Inicio inclusivo, fin exclusivo (último slot &lt; hasta).
     *
     * @return list<string>
     */
    private function buildSlotList(string $fromHi, string $toHi, int $stepMin): array
    {
        $step = max(5, $stepMin) * 60;
        $ini = strtotime('1970-01-01 ' . $fromHi . ':00');
        $fin = strtotime('1970-01-01 ' . $toHi . ':00');
        if ($ini === false || $fin === false || $ini >= $fin) {
            return [];
        }
        $out = [];
        for ($t = $ini; $t < $fin; $t += $step) {
            $out[] = date('H:i', $t);
        }

        return $out;
    }

    /**
     * @param list<string> $slots
     * @param array<string,int> $occupied
     * @return list<string>
     */
    private function mergeSlotsWithOccupied(array $slots, array $occupied): array
    {
        if ($occupied === []) {
            return $slots;
        }

        $merged = array_values(array_unique(array_merge($slots, array_keys($occupied))));
        sort($merged, SORT_STRING);
        return $merged;
    }
}
