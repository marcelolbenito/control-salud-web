<?php

declare(strict_types=1);

final class CajaRepository
{
    private const TABLE = 'caja';

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    private function cajaTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::TABLE, 'id_clinica');
    }

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function tableExists(): bool
    {
        return db_table_exists($this->pdo, self::TABLE);
    }

    public function countRows(): int
    {
        if ($this->cajaTieneClinica()) {
            $st = $this->pdo->prepare('SELECT COUNT(*) c FROM ' . self::TABLE . ' WHERE id_clinica = ?');
            $st->execute([$this->idClinica]);
            $r = $st->fetch(PDO::FETCH_ASSOC);

            return (int) ($r['c'] ?? 0);
        }
        $st = $this->pdo->query('SELECT COUNT(*) c FROM ' . self::TABLE);
        $r = $st ? $st->fetch(PDO::FETCH_ASSOC) : ['c' => 0];

        return (int) ($r['c'] ?? 0);
    }

    public function sumByDate(string $ymd): float
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return 0.0;
        }
        if ($this->cajaTieneClinica()) {
            $st = $this->pdo->prepare('SELECT COALESCE(SUM(importecaja), 0) AS t FROM ' . self::TABLE . ' WHERE fechacaja = ? AND id_clinica = ?');
            $st->execute([$ymd, $this->idClinica]);
        } else {
            $st = $this->pdo->prepare('SELECT COALESCE(SUM(importecaja), 0) AS t FROM ' . self::TABLE . ' WHERE fechacaja = ?');
            $st->execute([$ymd]);
        }
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return (float) ($r['t'] ?? 0);
    }

    /**
     * @param array<string, string|int> $f
     */
    public function sumForFilters(array $f): float
    {
        [$whereSql, $params] = $this->buildWhereSql($f, true, true);
        $sql = 'SELECT COALESCE(SUM(c.importecaja), 0) AS t FROM ' . self::TABLE . ' c' . $whereSql;
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return (float) ($r['t'] ?? 0);
    }

    /**
     * @param array<string, string|int> $f
     * @return list<array{doctor:string,total:float}>
     */
    public function resumenPorDoctor(array $f, int $limit = 8): array
    {
        $joinDoctores = db_table_exists($this->pdo, 'lista_doctores');
        [$whereSql, $params] = $this->buildWhereSql($f, true, true);
        $sql = 'SELECT c.doctor, COALESCE(SUM(c.importecaja), 0) AS total';
        if ($joinDoctores) {
            $sql .= ", COALESCE(NULLIF(TRIM(d.nombre), ''), CONCAT('Doctor #', c.doctor)) AS doctor_nombre";
        }
        $sql .= ' FROM ' . self::TABLE . ' c';
        if ($joinDoctores) {
            $sql .= ' LEFT JOIN lista_doctores d ON d.id = c.doctor';
        }
        $sql .= $whereSql;
        $sql .= ' GROUP BY c.doctor';
        if ($joinDoctores) {
            $sql .= ', doctor_nombre';
        }
        $sql .= ' ORDER BY total DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $nombre = trim((string) ($r['doctor_nombre'] ?? ''));
            $doctorId = (int) ($r['doctor'] ?? 0);
            if ($nombre === '') {
                $nombre = $doctorId > 0 ? ('Doctor #' . $doctorId) : '—';
            }
            $out[] = [
                'doctor' => $nombre,
                'total' => (float) ($r['total'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, string|int> $f
     * @return list<array<string, mixed>>
     */
    public function listForIndex(array $f): array
    {
        $joinCob = db_table_exists($this->pdo, 'lista_coberturas');
        $selCob = $joinCob ? ', lc.nombre AS cobertura_nombre' : ', NULL AS cobertura_nombre';
        $hasTurno = db_table_has_column($this->pdo, self::TABLE, 'turnocaja');
        $hasObs = db_table_has_column($this->pdo, self::TABLE, 'observaciones');
        $selTurno = $hasTurno ? 'c.turnocaja' : 'NULL AS turnocaja';
        $selObs = $hasObs ? 'c.observaciones' : 'NULL AS observaciones';

        $joinDoc = 'd.id = c.doctor';
        if ($this->cajaTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = c.id_clinica';
        }
        $sql = 'SELECT c.id, c.doctor, c.fechacaja, c.importecaja, c.idcoberturacaja, '
            . $selTurno . ', ' . $selObs . ',
            d.nombre AS doctor_nombre' . $selCob . '
            FROM ' . self::TABLE . ' c
            LEFT JOIN lista_doctores d ON ' . $joinDoc;
        if ($joinCob) {
            $sql .= ' LEFT JOIN lista_coberturas lc ON lc.id = c.idcoberturacaja';
        }
        [$whereSql, $params] = $this->buildWhereSql($f, $hasTurno, $hasObs);
        $sql .= $whereSql;

        $sql .= ' ORDER BY c.fechacaja DESC, c.id DESC LIMIT 1000';

        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, string|int> $f
     * @return array{0:string,1:list<mixed>}
     */
    private function buildWhereSql(array $f, bool $hasTurno, bool $hasObs): array
    {
        $sql = ' WHERE 1=1';
        $params = [];
        if ($this->cajaTieneClinica()) {
            $sql .= ' AND c.id_clinica = ?';
            $params[] = $this->idClinica;
        }

        $doc = (int) ($f['doctor'] ?? 0);
        if ($doc > 0) {
            $sql .= ' AND c.doctor = ?';
            $params[] = $doc;
        }
        $fd = trim((string) ($f['fecha_desde'] ?? ''));
        if ($fd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) {
            $sql .= ' AND c.fechacaja >= ?';
            $params[] = $fd;
        }
        $fh = trim((string) ($f['fecha_hasta'] ?? ''));
        if ($fh !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fh)) {
            $sql .= ' AND c.fechacaja <= ?';
            $params[] = $fh;
        }
        $idCob = (int) ($f['idcoberturacaja'] ?? 0);
        if ($idCob > 0) {
            $sql .= ' AND c.idcoberturacaja = ?';
            $params[] = $idCob;
        }
        $texto = trim((string) ($f['q'] ?? ''));
        if ($texto !== '' && ($hasTurno || $hasObs)) {
            $parts = [];
            if ($hasTurno) {
                $parts[] = 'c.turnocaja LIKE ?';
            }
            if ($hasObs) {
                $parts[] = 'c.observaciones LIKE ?';
            }
            if ($parts !== []) {
                $sql .= ' AND (' . implode(' OR ', $parts) . ')';
                foreach ($parts as $_) {
                    $params[] = '%' . $texto . '%';
                }
            }
        }

        return [$sql, $params];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->cajaTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
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

    public function coberturaExists(int $id): bool
    {
        if (!db_table_exists($this->pdo, 'lista_coberturas')) {
            return false;
        }
        $st = $this->pdo->prepare('SELECT id FROM lista_coberturas WHERE id = ? LIMIT 1');
        $st->execute([$id]);

        return (bool) $st->fetch();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function insertRow(array $values): int
    {
        if ($this->cajaTieneClinica()) {
            $values['id_clinica'] = $this->idClinica;
        }
        $cols = [];
        $vals = [];
        foreach (['id_clinica', 'doctor', 'fechacaja', 'importecaja', 'idcoberturacaja', 'turnocaja', 'observaciones'] as $c) {
            if (!db_table_has_column($this->pdo, self::TABLE, $c)) {
                continue;
            }
            if (!array_key_exists($c, $values)) {
                continue;
            }
            $cols[] = $c;
            $vals[] = $values[$c];
        }
        if ($cols === []) {
            throw new InvalidArgumentException('Sin columnas para insertar en caja.');
        }
        $colSql = implode(', ', array_map(static function (string $c): string {
            return '`' . $c . '`';
        }, $cols));
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        $sql = 'INSERT INTO ' . self::TABLE . ' (' . $colSql . ') VALUES (' . $ph . ')';
        $st = $this->pdo->prepare($sql);
        $st->execute($vals);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function updateRow(int $id, array $values): void
    {
        $sets = [];
        $params = [];
        foreach (['doctor', 'fechacaja', 'importecaja', 'idcoberturacaja', 'turnocaja', 'observaciones'] as $c) {
            if (!db_table_has_column($this->pdo, self::TABLE, $c)) {
                continue;
            }
            if (!array_key_exists($c, $values)) {
                continue;
            }
            $sets[] = '`' . $c . '` = ?';
            $params[] = $values[$c];
        }
        if ($sets === []) {
            return;
        }
        $params[] = $id;
        $sql = 'UPDATE ' . self::TABLE . ' SET ' . implode(', ', $sets) . ' WHERE id = ?';
        if ($this->cajaTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $params[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
    }

    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->cajaTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }
}

