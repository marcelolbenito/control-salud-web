<?php

declare(strict_types=1);

final class TurnosRepository
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

    /**
     * @param list<mixed> $params
     */
    private function appendAgendaClinica(string &$sql, array &$params): void
    {
        if ($this->agendaTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
    }

    private function joinPacienteNroHC(string $aliasTurno = 't'): string
    {
        $on = "p.NroHC = {$aliasTurno}.NroHC";
        if ($this->agendaTieneClinica() && $this->pacientesTieneClinica()) {
            $on .= " AND p.id_clinica = {$aliasTurno}.id_clinica";
        }

        return $on;
    }

    public function hasExtendedAgendaColumns(): bool
    {
        return db_table_has_column($this->pdo, 'agenda_turnos', 'motivo');
    }

    public function listDoctores(): array
    {
        if (db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $st = $this->pdo->prepare('SELECT id, nombre FROM lista_doctores WHERE id_clinica = ? ORDER BY nombre ASC');
            $st->execute([$this->idClinica]);

            return $st->fetchAll();
        }

        return $this->pdo->query('SELECT id, nombre FROM lista_doctores ORDER BY nombre ASC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM agenda_turnos WHERE id = ?';
        $par = [$id];
        $this->appendAgendaClinica($sql, $par);
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM agenda_turnos WHERE id = ?';
        $par = [$id];
        $this->appendAgendaClinica($sql, $par);
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function pacienteExistsByNroHC(int $nroHC): bool
    {
        $sql = 'SELECT id FROM pacientes WHERE NroHC = ?';
        $par = [$nroHC];
        if ($this->pacientesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        return (bool) $st->fetch();
    }

    public function pacienteNombreParaTurno(int $nroHC): string
    {
        if (db_table_has_column($this->pdo, 'pacientes', 'apellido')) {
            $sql = 'SELECT apellido, Nombres FROM pacientes WHERE NroHC = ?';
            $par = [$nroHC];
            if ($this->pacientesTieneClinica()) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $sql .= ' LIMIT 1';
            $stn = $this->pdo->prepare($sql);
            $stn->execute($par);
            $pn = $stn->fetch();
            if ($pn) {
                $a = trim((string) ($pn['apellido'] ?? ''));
                $n = trim((string) ($pn['Nombres'] ?? ''));
                return trim($a . ' ' . $n) ?: $n;
            }
        } else {
            $sql = 'SELECT Nombres FROM pacientes WHERE NroHC = ?';
            $par = [$nroHC];
            if ($this->pacientesTieneClinica()) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $sql .= ' LIMIT 1';
            $stn = $this->pdo->prepare($sql);
            $stn->execute($par);
            $pn = $stn->fetch();
            if ($pn) {
                return trim((string) ($pn['Nombres'] ?? ''));
            }
        }
        return '';
    }

    /**
     * @return array{nrohc:int,dni:string,nombre:string}|null
     */
    public function pacientePorNroHC(int $nroHC): ?array
    {
        if ($nroHC < 1) {
            return null;
        }
        $hasApellido = db_table_has_column($this->pdo, 'pacientes', 'apellido');
        $sqlNombre = $hasApellido
            ? "TRIM(CONCAT(COALESCE(apellido,''), ' ', COALESCE(Nombres,'')))"
            : "TRIM(COALESCE(Nombres,''))";
        $sql = "SELECT NroHC AS nrohc, COALESCE(DNI,'') AS dni, {$sqlNombre} AS nombre
                FROM pacientes
                WHERE NroHC = ?";
        $par = [$nroHC];
        if ($this->pacientesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return null;
        }

        return [
            'nrohc' => (int) ($r['nrohc'] ?? 0),
            'dni' => trim((string) ($r['dni'] ?? '')),
            'nombre' => trim((string) ($r['nombre'] ?? '')),
        ];
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
        if ($this->agendaTieneClinica()) {
            $st = $this->pdo->prepare(
                'INSERT INTO agenda_turnos (id_clinica, Fecha, hora, NroHC, Doctor, idorden, estado, observaciones,
                paciente_nombre, motivo, atendido, pagado, llegado, llegado_hora, confirmado, falta_turno, reingresar, primera_vez,
                num_sesion, id_sesion, id_caja, usuario_asignado, fechahora_asignado, alta_paci_web)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $st->execute([
                $this->idClinica, $fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones,
                $ex['paciente_nombre'] ?: null, $ex['motivo'], $ex['atendido'], $ex['pagado'], $ex['llegado'], $ex['llegado_hora'],
                $ex['confirmado'], $ex['falta_turno'], $ex['reingresar'], $ex['primera_vez'],
                $ex['num_sesion'], $ex['id_sesion'], $ex['id_caja'], $ex['usuario_asignado'], $ex['fechahora_asignado'], $ex['alta_paci_web'],
            ]);

            return;
        }
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
        $sql = 'UPDATE agenda_turnos SET Fecha=?, hora=?, NroHC=?, Doctor=?, idorden=?, estado=?, observaciones=?,
            paciente_nombre=?, motivo=?, atendido=?, pagado=?, llegado=?, llegado_hora=?, confirmado=?, falta_turno=?, reingresar=?, primera_vez=?,
            num_sesion=?, id_sesion=?, id_caja=?, usuario_asignado=?, fechahora_asignado=?, alta_paci_web=?
            WHERE id=?';
        $par = [
            $fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones,
            $ex['paciente_nombre'] ?: null, $ex['motivo'], $ex['atendido'], $ex['pagado'], $ex['llegado'], $ex['llegado_hora'],
            $ex['confirmado'], $ex['falta_turno'], $ex['reingresar'], $ex['primera_vez'],
            $ex['num_sesion'], $ex['id_sesion'], $ex['id_caja'], $ex['usuario_asignado'], $ex['fechahora_asignado'], $ex['alta_paci_web'],
            $id,
        ];
        if ($this->agendaTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
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
        if ($this->agendaTieneClinica()) {
            $st = $this->pdo->prepare(
                'INSERT INTO agenda_turnos (id_clinica, Fecha, hora, NroHC, Doctor, idorden, estado, observaciones) VALUES (?,?,?,?,?,?,?,?)'
            );
            $st->execute([$this->idClinica, $fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones]);

            return;
        }
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
        $sql = 'UPDATE agenda_turnos SET Fecha=?, hora=?, NroHC=?, Doctor=?, idorden=?, estado=?, observaciones=? WHERE id=?';
        $par = [$fecha, $hora, $nroHC, $doctor, $idorden, $estado, $observaciones, $id];
        if ($this->agendaTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
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
        $this->appendAgendaClinica($sql, $params);
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
                WHERE p.NroHC IS NOT NULL";
        $bind = ['like' => $like];
        if ($this->pacientesTieneClinica()) {
            $sql .= ' AND p.id_clinica = :cid';
            $bind['cid'] = $this->idClinica;
        }
        $sql .= "
                  AND (
                    p.DNI LIKE :like
                    OR p.Nombres LIKE :like
                    " . ($hasApellido ? " OR p.apellido LIKE :like " : '') . "
                    OR {$sqlNombre} LIKE :like
                  )
                ORDER BY p.NroHC DESC
                LIMIT {$lim}";
        $st = $this->pdo->prepare($sql);
        $st->execute($bind);

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
                LEFT JOIN pacientes p ON " . $this->joinPacienteNroHC('t') . "
                WHERE t.Fecha = ?";
        $par = [$fecha];
        if ($this->agendaTieneClinica()) {
            $sql .= ' AND t.id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= "
                  AND t.Doctor = ?
                  AND t.hora IS NOT NULL
                  AND DATE_FORMAT(t.hora, '%H:%i') = ?
                ORDER BY t.id ASC";
        $par[] = $doctor;
        $par[] = $hora;
        $st = $this->pdo->prepare($sql);
        $st->execute($par);

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

    /**
     * Busca próximos horarios libres para un profesional.
     *
     * @return list<array{fecha:string,hora:string}>
     */
    public function proximosLibres(
        int $doctor,
        string $desdeFecha,
        int $maxDias = 30,
        int $limite = 5,
        int $excludeTurnoId = 0
    ): array {
        if ($doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desdeFecha)) {
            return [];
        }
        $dias = max(1, min(120, $maxDias));
        $take = max(1, min(200, $limite));
        $baseTs = strtotime($desdeFecha . ' 00:00:00');
        if ($baseTs === false) {
            return [];
        }

        $out = [];
        for ($i = 0; $i < $dias; $i++) {
            $fecha = date('Y-m-d', $baseTs + ($i * 86400));
            $disp = $this->disponibilidadVisual($fecha, $doctor, $excludeTurnoId);
            $slots = $disp['slots'] ?? [];
            $occupied = $disp['occupied'] ?? [];
            $blocked = $disp['blocked'] ?? [];
            foreach ($slots as $slot) {
                $h = (string) $slot;
                if (!preg_match('/^\d{2}:\d{2}$/', $h)) {
                    continue;
                }
                if ((int) ($occupied[$h] ?? 0) > 0) {
                    continue;
                }
                if ((int) ($blocked[$h] ?? 0) > 0) {
                    continue;
                }
                $out[] = ['fecha' => $fecha, 'hora' => $h];
                if (count($out) >= $take) {
                    return $out;
                }
            }
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

    private function agendaBloqueosTableExists(): bool
    {
        static $cache = null;
        if ($cache === null) {
            $cache = db_table_exists($this->pdo, 'agenda_bloqueos');
        }

        return (bool) $cache;
    }

    private function bloqueosTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, 'agenda_bloqueos', 'id_clinica');
    }

    /**
     * @param array<string, mixed> $row fila agenda_bloqueos (hora_desde / hora_hasta)
     */
    private function slotMatchesBloqueo(string $slotHi, array $row): bool
    {
        $hd = $row['hora_desde'] ?? null;
        $hh = $row['hora_hasta'] ?? null;
        $hdEmpty = $hd === null || $hd === '';
        $hhEmpty = $hh === null || $hh === '';
        if ($hdEmpty && $hhEmpty) {
            return true;
        }
        if ($hdEmpty || $hhEmpty) {
            return false;
        }
        $from = $this->normalizarHoraHi($hd);
        $to = $this->normalizarHoraHi($hh);
        if ($from === null || $to === null) {
            return false;
        }
        $sm = $this->minutosDesdeMedianoche($slotHi);
        $fm = $this->minutosDesdeMedianoche($from);
        $tm = $this->minutosDesdeMedianoche($to);

        return $sm >= $fm && $sm < $tm;
    }

    /**
     * @param mixed $v TIME / datetime / string
     */
    private function normalizarHoraHi($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $s = (string) $v;
        if (preg_match('/^(\d{1,2}):(\d{2})/', $s, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }
        $ts = strtotime($s);
        if ($ts === false) {
            return null;
        }

        return date('H:i', $ts);
    }

    private function minutosDesdeMedianoche(string $hi): int
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $hi, $m)) {
            return 0;
        }

        return ((int) $m[1]) * 60 + (int) $m[2];
    }

    /**
     * @param list<string> $slotList HH:MM
     *
     * @return array<string,int>
     */
    public function horasBloqueadasPorFechaDoctor(string $fecha, int $doctor, array $slotList): array
    {
        if (!$this->agendaBloqueosTableExists() || $doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $slotList === []) {
            return [];
        }

        $sql = 'SELECT hora_desde, hora_hasta FROM agenda_bloqueos
                WHERE doctor = ? AND ? BETWEEN fecha_desde AND fecha_hasta';
        $par = [$doctor, $fecha];
        if ($this->bloqueosTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($rows === []) {
            return [];
        }

        $out = [];
        foreach ($slotList as $slot) {
            if (!preg_match('/^\d{2}:\d{2}$/', (string) $slot)) {
                continue;
            }
            foreach ($rows as $r) {
                if ($this->slotMatchesBloqueo((string) $slot, $r)) {
                    $out[(string) $slot] = 1;
                    break;
                }
            }
        }

        return $out;
    }

    public function isHoraBloqueada(string $fecha, int $doctor, string $slotHi): bool
    {
        $m = $this->horasBloqueadasPorFechaDoctor($fecha, $doctor, [$slotHi]);

        return ($m[$slotHi] ?? 0) > 0;
    }

    /**
     * @param list<string> $rawSlots slots sin merge final con occupied
     * @param array<string,int> $occupied
     *
     * @return array{slots: list<string>, occupied: array<string,int>, blocked: array<string,int>, source: string, step: int, sin_franja_dia: bool}
     */
    private function finalizeDisponibilidad(
        string $fecha,
        int $doctor,
        array $rawSlots,
        array $occupied,
        string $source,
        int $step,
        bool $sinFranjaDia
    ): array {
        $merged = $this->mergeSlotsWithOccupied($rawSlots, $occupied);
        $blocked = [];
        if ($doctor >= 1 && $this->agendaBloqueosTableExists()) {
            $blocked = $this->horasBloqueadasPorFechaDoctor($fecha, $doctor, $merged);
            if ($blocked !== []) {
                $merged = $this->mergeSlotsWithOccupied($merged, $blocked);
            }
        }

        return [
            'slots' => $merged,
            'occupied' => $occupied,
            'blocked' => $blocked,
            'source' => $source,
            'step' => $step,
            'sin_franja_dia' => $sinFranjaDia,
        ];
    }

    /**
     * Slots + ocupación para la grilla de nuevo/editar turno (respeta `Agenda Turnos Horarios` si existe).
     *
     * @return array{slots: list<string>, occupied: array<string,int>, blocked: array<string,int>, source: string, step: int, sin_franja_dia: bool}
     */
    public function disponibilidadVisual(string $fecha, int $doctor, int $excludeTurnoId = 0): array
    {
        $occupied = $this->horasOcupadasPorFechaDoctor($fecha, $doctor, $excludeTurnoId);
        // Fin exclusivo: último inicio 20:00 con paso 15 (misma lógica que la grilla fija anterior).
        $defaultSlots = $this->buildSlotList('08:00', '20:15', 15);

        if ($doctor < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $this->finalizeDisponibilidad($fecha, $doctor, $defaultSlots, $occupied, 'none', 15, false);
        }

        $legacy = $this->findLegacyHorarioRow($doctor, $fecha);
        if ($legacy === null) {
            return $this->finalizeDisponibilidad($fecha, $doctor, $defaultSlots, $occupied, 'default', 15, false);
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
            return $this->finalizeDisponibilidad($fecha, $doctor, $defaultSlots, $occupied, 'legacy_no_day', 15, true);
        }

        // Si la franja legacy queda demasiado limitada, completamos con la base
        // para asegurar opciones visibles al cargar un nuevo turno.
        if (count($slots) <= max(3, count($occupied))) {
            return $this->finalizeDisponibilidad($fecha, $doctor, $defaultSlots, $occupied, 'legacy_sparse', 15, false);
        }

        return $this->finalizeDisponibilidad($fecha, $doctor, $slots, $occupied, 'legacy', $step, false);
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

        $sql = 'SELECT h.* FROM `' . str_replace('`', '', self::LEGACY_HORARIO_TABLE) . '` h
                WHERE h.iddoctor = ?
                  AND DATE(h.fechadesde) <= ?
                  AND DATE(h.fechahasta) >= ?';
        $par = [$doctor, $fecha, $fecha];
        if (db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $sql .= ' AND EXISTS (SELECT 1 FROM lista_doctores ld WHERE ld.id = h.iddoctor AND ld.id_clinica = ?)';
            $par[] = $this->idClinica;
        }
        $sql .= '
                ORDER BY h.fechadesde DESC
                LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
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
