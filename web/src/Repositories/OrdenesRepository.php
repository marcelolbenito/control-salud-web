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

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
            $st = $this->pdo->prepare('SELECT id FROM ' . self::TABLE . ' WHERE id = ? AND NroPaci = ? LIMIT 1');
            $st->execute([$idOrden, $nroHC]);

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
            . ' WHERE NroPaci = ? ORDER BY fecha IS NULL, fecha DESC, id DESC LIMIT ' . $limite;
        try {
            $st = $this->pdo->prepare($sql);
            $st->execute([$nroHC]);

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

        $sql = 'SELECT o.id, o.NroPaci, o.numero, o.iddoctor,
            DATE(o.fecha) AS fecha_orden,
            o.fecha AS fecha_hora,
            o.autorizada, o.entregada, o.observaciones,
            o.costo, o.pago, o.costo_os, o.sesiones, o.liquidada,
            o.estado, o.estado_os, o.sucursal, o.idobrasocial, o.idpractica,
            o.idderivado, o.idplan,
            d.nombre AS doctor_nombre,
            p.Nombres AS paciente_nombres, ' . $colApellido . $selCob . $selPr . $selDer . $selSuc . '
            FROM ' . self::TABLE . ' o
            LEFT JOIN lista_doctores d ON d.id = o.iddoctor
            LEFT JOIN pacientes p ON p.NroHC = o.NroPaci';
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
        $est = trim((string) ($f['estado'] ?? ''));
        if ($est !== '') {
            $sql .= ' AND o.estado <=> ?';
            $params[] = substr($est, 0, 1);
        }
        $estOs = trim((string) ($f['estado_os'] ?? ''));
        if ($estOs !== '') {
            $sql .= ' AND o.estado_os <=> ?';
            $params[] = substr($estOs, 0, 1);
        }
        self::appendTriState($sql, $params, 'o.autorizada', (string) ($f['autorizada'] ?? ''));
        self::appendTriState($sql, $params, 'o.entregada', (string) ($f['entregada'] ?? ''));
        self::appendTriState($sql, $params, 'o.liquidada', (string) ($f['liquidada'] ?? ''));

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

    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM ' . self::TABLE . ' WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();

        return $r ?: null;
    }

    public function nroHcExists(int $nroHC): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM pacientes WHERE NroHC = ? LIMIT 1');
        $st->execute([$nroHC]);
        return (bool) $st->fetch();
    }

    public function doctorExists(int $id): bool
    {
        $st = $this->pdo->prepare('SELECT id FROM lista_doctores WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return (bool) $st->fetch();
    }

    public function countSesionesByOrden(int $idOrden): int
    {
        if (!db_table_exists($this->pdo, 'pacientes_sesiones')) {
            return 0;
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) c FROM pacientes_sesiones WHERE idorden = ?');
        $st->execute([$idOrden]);

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
            return true;
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
        $st = $this->pdo->prepare('DELETE FROM ' . self::TABLE . ' WHERE id = ?');
        $st->execute([$id]);
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
