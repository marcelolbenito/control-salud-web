<?php

declare(strict_types=1);

/**
 * Tabla física: `Pacientes Ordenes` (nombre del backup / exe).
 */
final class OrdenesRepository
{
    private const TABLE = '`Pacientes Ordenes`';

    /**
     * Columnas editables desde la web (orden de persistencia).
     * Se omiten las que no existan en la BD (information_schema).
     *
     * @var list<string>
     */
    private const COLUMNS_EDITABLES = [
        'NroPaci', 'numero', 'fecha', 'entregada', 'autorizada', 'sesiones',
        'costo', 'pago', 'iddoctor', 'idobrasocial', 'observaciones',
        'numeautorizacion', 'costo_os', 'estado', 'estado_os',
        'idpractica', 'idderivado', 'fechaderivacion', 'fechaautorizacion', 'fechaentrega',
        'idusuariocarga', 'sesionesreali', 'diente', 'cara', 'nusiniestro',
        'pagaiva', 'cerrada', 'tipoasistencia', 'liquidada',
        'honorarioextra', 'honorariofecha', 'idplan', 'sucursal',
    ];

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    private function ordenesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::tableSqlName(), 'id_clinica');
    }

    public static function tableSqlName(): string
    {
        return 'Pacientes Ordenes';
    }

    public function ordenPerteneceAPaciente(int $idOrden, int $nroHC): bool
    {
        if ($idOrden < 1 || $nroHC < 1) {
            return false;
        }
        try {
            $sql = 'SELECT id FROM ' . self::TABLE . ' WHERE id = ? AND NroPaci = ?';
            $par = [$idOrden, $nroHC];
            if ($this->ordenesTieneClinica()) {
                $sql .= ' AND id_clinica = ?';
                $par[] = $this->idClinica;
            }
            $sql .= ' LIMIT 1';
            $st = $this->pdo->prepare($sql);
            $st->execute($par);

            return (bool) $st->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Listado breve para selects (odontograma, etc.).
     *
     * @return list<array{id:int, fecha_orden:?string}>
     */
    public function listMiniPorNroPaci(int $nroHC, int $limite = 40): array
    {
        if ($nroHC < 1 || !db_table_exists($this->pdo, self::tableSqlName())) {
            return [];
        }
        $limite = max(1, min(100, $limite));
        $sql = 'SELECT id, DATE(fecha) AS fecha_orden FROM ' . self::TABLE
            . ' WHERE NroPaci = ?';
        $par = [$nroHC];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' ORDER BY fecha IS NULL, fecha DESC, id DESC LIMIT ' . $limite;
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute($par);

            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function filterExistingColumns(array $values): array
    {
        $t = self::tableSqlName();
        $out = [];
        foreach (self::COLUMNS_EDITABLES as $col) {
            if (!array_key_exists($col, $values)) {
                continue;
            }
            if (!db_table_has_column($this->pdo, $t, $col)) {
                continue;
            }
            $out[$col] = $values[$col];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insertRow(array $values): int
    {
        $values = $this->filterExistingColumns($values);
        if ($values === []) {
            throw new InvalidArgumentException('Sin columnas para insertar en Pacientes Ordenes.');
        }
        if ($this->ordenesTieneClinica()) {
            $values['id_clinica'] = $this->idClinica;
        }
        $cols = array_keys($values);
        $colSql = implode(', ', array_map(static function (string $c): string {
            return '`' . str_replace('`', '', $c) . '`';
        }, $cols));
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . self::TABLE . ' (' . $colSql . ') VALUES (' . $ph . ')';
        $st = $this->pdo->prepare($sql);
        $st->execute(array_values($values));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function updateRow(int $id, array $values): void
    {
        $values = $this->filterExistingColumns($values);
        unset($values['id_clinica']);
        if ($values === []) {
            return;
        }
        $sets = [];
        $params = [];
        foreach ($values as $c => $v) {
            $sets[] = '`' . str_replace('`', '', $c) . '` = ?';
            $params[] = $v;
        }
        $params[] = $id;
        $sql = 'UPDATE ' . self::TABLE . ' SET ' . implode(', ', $sets) . ' WHERE id = ?';
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    /**
     * @param array<string, mixed> $f Filtros desde GET (ver OrdenesController::collectFiltrosOrdenes)
     * @return list<array<string, mixed>>
     */
    public function listForIndex(array $f): array
    {
        $hasApellido = db_table_has_column($this->pdo, 'pacientes', 'apellido');
        $colApellido = $hasApellido ? 'p.apellido AS paciente_apellido' : 'NULL AS paciente_apellido';
        $joinCob = db_table_exists($this->pdo, 'lista_coberturas');
        $selCob = $joinCob ? ', lc.nombre AS cobertura_nombre' : ', NULL AS cobertura_nombre';
        $joinPr = db_table_exists($this->pdo, 'lista_practicas');
        $selPr = $joinPr ? ', lp.nombre AS practica_nombre' : ', NULL AS practica_nombre';
        $joinDer = db_table_exists($this->pdo, 'lista_derivaciones');
        $selDer = $joinDer ? ', ld.nombre AS derivacion_nombre' : ', NULL AS derivacion_nombre';
        $joinSuc = db_table_exists($this->pdo, 'lista_sucursales');
        $selSuc = $joinSuc ? ', ls.nombre AS sucursal_nombre' : ', NULL AS sucursal_nombre';

        $joinDoc = 'd.id = o.iddoctor';
        $joinPac = 'p.NroHC = o.NroPaci';
        if ($this->ordenesTieneClinica()) {
            if (db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
                $joinDoc .= ' AND d.id_clinica = o.id_clinica';
            }
            if (db_table_has_column($this->pdo, 'pacientes', 'id_clinica')) {
                $joinPac .= ' AND p.id_clinica = o.id_clinica';
            }
        }
        $sql = 'SELECT o.id, o.NroPaci, o.numero, o.iddoctor,
            DATE(o.fecha) AS fecha_orden,
            o.fecha AS fecha_hora,
            o.autorizada, o.entregada, o.observaciones,
            o.costo, o.pago, o.costo_os, o.honorarioextra, o.sesiones, o.liquidada,
            o.estado, o.estado_os, o.sucursal, o.idobrasocial, o.idpractica,
            o.idderivado, o.idplan,
            d.nombre AS doctor_nombre,
            p.Nombres AS paciente_nombres, ' . $colApellido . $selCob . $selPr . $selDer . $selSuc . '
            FROM ' . self::TABLE . ' o
            LEFT JOIN lista_doctores d ON ' . $joinDoc . '
            LEFT JOIN pacientes p ON ' . $joinPac;
        if ($joinCob) {
            $sql .= ' LEFT JOIN lista_coberturas lc ON lc.id = o.idobrasocial';
        }
        if ($joinPr) {
            $sql .= ' LEFT JOIN lista_practicas lp ON lp.id = o.idpractica';
        }
        if ($joinDer) {
            $sql .= ' LEFT JOIN lista_derivaciones ld ON ld.id = o.idderivado';
        }
        if ($joinSuc) {
            $sql .= ' LEFT JOIN lista_sucursales ls ON ls.id = o.sucursal';
        }
        $sql .= ' WHERE 1=1';
        $params = [];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND o.id_clinica = ?';
            $params[] = $this->idClinica;
        }

        $nro = (int) ($f['nrohc'] ?? 0);
        if ($nro > 0) {
            $sql .= ' AND o.NroPaci = ?';
            $params[] = $nro;
        }
        $doc = (int) ($f['doctor'] ?? 0);
        if ($doc > 0) {
            $sql .= ' AND o.iddoctor = ?';
            $params[] = $doc;
        }
        $idD = (int) ($f['id_desde'] ?? 0);
        if ($idD > 0) {
            $sql .= ' AND o.id >= ?';
            $params[] = $idD;
        }
        $idH = (int) ($f['id_hasta'] ?? 0);
        if ($idH > 0) {
            $sql .= ' AND o.id <= ?';
            $params[] = $idH;
        }
        $fd = trim((string) ($f['fecha_desde'] ?? ''));
        if ($fd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) {
            $sql .= ' AND o.fecha >= ?';
            $params[] = $fd . ' 00:00:00';
        }
        $fh = trim((string) ($f['fecha_hasta'] ?? ''));
        if ($fh !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fh)) {
            $sql .= ' AND o.fecha <= ?';
            $params[] = $fh . ' 23:59:59';
        }
        if (db_table_has_column($this->pdo, self::tableSqlName(), 'honorariofecha')) {
            $hfd = trim((string) ($f['honorariofecha_desde'] ?? ''));
            if ($hfd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hfd)) {
                $sql .= ' AND o.honorariofecha >= ?';
                $params[] = $hfd . ' 00:00:00';
            }
            $hfh = trim((string) ($f['honorariofecha_hasta'] ?? ''));
            if ($hfh !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hfh)) {
                $sql .= ' AND o.honorariofecha <= ?';
                $params[] = $hfh . ' 23:59:59';
            }
        }
        foreach (
            [
                'sucursal' => 'sucursal',
                'idobrasocial' => 'idobrasocial',
                'idpractica' => 'idpractica',
                'idderivado' => 'idderivado',
                'idplan' => 'idplan',
            ] as $key => $col
        ) {
            $v = (int) ($f[$key] ?? 0);
            if ($v > 0) {
                $sql .= ' AND o.' . $col . ' = ?';
                $params[] = $v;
            }
        }
        $estMulti = $this->sanitizeEstadoMulti($f['estado_multi'] ?? []);
        if ($estMulti !== []) {
            $sql .= ' AND o.estado IN (' . implode(', ', array_fill(0, count($estMulti), '?')) . ')';
            array_push($params, ...$estMulti);
        } else {
            $est = trim((string) ($f['estado'] ?? ''));
            if ($est !== '') {
                $sql .= ' AND o.estado <=> ?';
                $params[] = strtoupper(substr($est, 0, 1));
            }
        }
        $estOsMulti = $this->sanitizeEstadoMulti($f['estado_os_multi'] ?? []);
        if ($estOsMulti !== []) {
            $sql .= ' AND o.estado_os IN (' . implode(', ', array_fill(0, count($estOsMulti), '?')) . ')';
            array_push($params, ...$estOsMulti);
        } else {
            $estOs = trim((string) ($f['estado_os'] ?? ''));
            if ($estOs !== '') {
                $sql .= ' AND o.estado_os <=> ?';
                $params[] = strtoupper(substr($estOs, 0, 1));
            }
        }
        $sesionDoc = (int) ($f['sesion_doctor'] ?? 0);
        if ($sesionDoc > 0 && db_table_exists($this->pdo, 'pacientes_sesiones')) {
            $sesJoin = 's.idorden = o.id AND s.iddoctor = ?';
            if ($this->ordenesTieneClinica() && db_table_has_column($this->pdo, 'pacientes_sesiones', 'id_clinica')) {
                $sesJoin .= ' AND s.id_clinica = o.id_clinica';
            }
            $sql .= ' AND EXISTS (SELECT 1 FROM pacientes_sesiones s WHERE ' . $sesJoin . ')';
            $params[] = $sesionDoc;
        }
        $sesionEstado = trim((string) ($f['sesion_estado'] ?? ''));
        if ($sesionEstado !== '' && db_table_exists($this->pdo, 'pacientes_sesiones')) {
            $sesEx = 's.idorden = o.id';
            if ($this->ordenesTieneClinica() && db_table_has_column($this->pdo, 'pacientes_sesiones', 'id_clinica')) {
                $sesEx .= ' AND s.id_clinica = o.id_clinica';
            }
            if ($sesionEstado === 'con') {
                $sql .= ' AND EXISTS (SELECT 1 FROM pacientes_sesiones s WHERE ' . $sesEx . ')';
            } elseif ($sesionEstado === 'sin') {
                $sql .= ' AND NOT EXISTS (SELECT 1 FROM pacientes_sesiones s WHERE ' . $sesEx . ')';
            } elseif ($sesionEstado === 'pendientes') {
                $sql .= ' AND COALESCE(o.sesiones, 0) > COALESCE(o.sesionesreali, 0)';
            } elseif ($sesionEstado === 'completas') {
                $sql .= ' AND COALESCE(o.sesiones, 0) > 0 AND COALESCE(o.sesionesreali, 0) >= COALESCE(o.sesiones, 0)';
            }
        }
        self::appendTriState($sql, $params, 'o.autorizada', (string) ($f['autorizada'] ?? ''));
        self::appendTriState($sql, $params, 'o.entregada', (string) ($f['entregada'] ?? ''));
        self::appendTriState($sql, $params, 'o.liquidada', (string) ($f['liquidada'] ?? ''));
        if (db_table_has_column($this->pdo, self::tableSqlName(), 'pagaiva')) {
            self::appendTriState($sql, $params, 'o.pagaiva', (string) ($f['pagaiva'] ?? ''));
        }
        if (db_table_has_column($this->pdo, self::tableSqlName(), 'numeautorizacion')) {
            $na = trim((string) ($f['numeautorizacion'] ?? ''));
            if ($na === 'con') {
                $sql .= ' AND o.numeautorizacion IS NOT NULL AND o.numeautorizacion <> 0';
            } elseif ($na === 'sin') {
                $sql .= ' AND (o.numeautorizacion IS NULL OR o.numeautorizacion = 0)';
            }
        }

        $sql .= ' ORDER BY o.fecha IS NULL, o.fecha DESC, o.id DESC LIMIT 500';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    private static function appendTriState(string &$sql, array &$params, string $colExpr, string $v): void
    {
        if ($v === '' || $v === 'all') {
            return;
        }
        if ($v === '1') {
            $sql .= ' AND (' . $colExpr . ' = 1)';
            return;
        }
        if ($v === '0') {
            $sql .= ' AND (' . $colExpr . ' = 0 OR ' . $colExpr . ' IS NULL)';
        }
    }

    /**
     * @param mixed $values
     * @return list<string>
     */
    private function sanitizeEstadoMulti($values): array
    {
        if (!is_array($values)) {
            return [];
        }
        $out = [];
        foreach ($values as $v) {
            $s = strtoupper(substr(trim((string) $v), 0, 1));
            if (!in_array($s, ['A', 'F', 'P'], true)) {
                continue;
            }
            if (!in_array($s, $out, true)) {
                $out[] = $s;
            }
        }

        return $out;
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch();

        return $r ?: null;
    }

    public function nroHcExists(int $nroHC): bool
    {
        $sql = 'SELECT id FROM pacientes WHERE NroHC = ?';
        $par = [$nroHC];
        if (db_table_has_column($this->pdo, 'pacientes', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        return (bool) $st->fetch();
    }

    public function doctorExists(int $id): bool
    {
        $sql = 'SELECT id FROM lista_doctores WHERE id = ?';
        $par = [$id];
        if (db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        return (bool) $st->fetch();
    }

    public function countSesionesByOrden(int $idOrden): int
    {
        if (!db_table_exists($this->pdo, 'pacientes_sesiones')) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) c FROM pacientes_sesiones WHERE idorden = ?';
        $par = [$idOrden];
        if (db_table_has_column($this->pdo, 'pacientes_sesiones', 'id_clinica')) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);

        return (int) $st->fetch()['c'];
    }

    public function idExistsInTable(string $table, int $id): bool
    {
        if ($id < 1 || !db_table_exists($this->pdo, $table)) {
            return false;
        }
        try {
            $st = $this->pdo->prepare("SELECT id FROM `$table` WHERE id = ? LIMIT 1");
            $st->execute([$id]);

            return (bool) $st->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return list<array{id:int|string, nombre:?string, id_cobertura:?int}>
     */
    public function listPlanesConCobertura(): array
    {
        if (!db_table_exists($this->pdo, 'lista_planes')) {
            return [];
        }
        $hasFk = db_table_has_column($this->pdo, 'lista_planes', 'id_cobertura');
        $sql = $hasFk
            ? 'SELECT id, nombre, id_cobertura FROM lista_planes ORDER BY nombre, id'
            : 'SELECT id, nombre, NULL AS id_cobertura FROM lista_planes ORDER BY nombre, id';
        try {
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function planCompatibleConCobertura(int $idPlan, int $idCobertura): bool
    {
        if (!db_table_exists($this->pdo, 'lista_planes') || $idPlan < 1) {
            return true;
        }
        if (!db_table_has_column($this->pdo, 'lista_planes', 'id_cobertura')) {
            return true;
        }
        $st = $this->pdo->prepare('SELECT id_cobertura FROM lista_planes WHERE id = ? LIMIT 1');
        $st->execute([$idPlan]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }
        $idc = $row['id_cobertura'];
        if ($idc === null || $idc === '') {
            // Si el plan no tiene cobertura asociada, no es compatible
            // cuando el usuario ya selecciono una cobertura concreta.
            return false;
        }

        return (int) $idc === $idCobertura;
    }

    /**
     * @return list<array{id:int|string, nombre:?string}>
     */
    public function listCatalogIfExists(string $tabla): array
    {
        if (!db_table_exists($this->pdo, $tabla)) {
            return [];
        }
        try {
            return $this->pdo->query("SELECT id, nombre FROM `$tabla` ORDER BY nombre, id")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public static function normalizarFecha(?string $fechaYmd): ?string
    {
        if ($fechaYmd === null || $fechaYmd === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaYmd)) {
            return $fechaYmd . ' 00:00:00';
        }

        return $fechaYmd;
    }
}
