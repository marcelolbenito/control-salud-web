<?php

declare(strict_types=1);

final class SesionesRepository
{
    private const TABLE = 'pacientes_sesiones';
    private const ORDEN_TABLE = 'Pacientes Ordenes';

    /** @var PDO */
    private $pdo;
    /** @var int */
    private $idClinica;

    public function __construct(PDO $pdo, int $idClinica = 1)
    {
        $this->pdo = $pdo;
        $this->idClinica = max(1, $idClinica);
    }

    private function sesionesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::TABLE, 'id_clinica');
    }

    private function ordenesTieneClinica(): bool
    {
        return db_table_has_column($this->pdo, self::ORDEN_TABLE, 'id_clinica');
    }

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function tableExists(): bool
    {
        return db_table_exists($this->pdo, self::TABLE);
    }

    public function countAll(): int
    {
        if (!$this->tableExists()) {
            return 0;
        }
        if ($this->sesionesTieneClinica()) {
            $st = $this->pdo->prepare('SELECT COUNT(*) c FROM ' . self::TABLE . ' WHERE id_clinica = ?');
            $st->execute([$this->idClinica]);
            $r = $st->fetch(PDO::FETCH_ASSOC);

            return (int) ($r['c'] ?? 0);
        }
        $st = $this->pdo->query('SELECT COUNT(*) c FROM ' . self::TABLE);
        $r = $st ? $st->fetch(PDO::FETCH_ASSOC) : ['c' => 0];

        return (int) ($r['c'] ?? 0);
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

    /**
     * @return array{id:int,NroPaci:int,iddoctor:int}|null
     */
    public function findOrdenCabecera(int $idOrden): ?array
    {
        if (!db_table_exists($this->pdo, self::ORDEN_TABLE)) {
            return null;
        }
        $sql = 'SELECT id, NroPaci, iddoctor FROM `' . str_replace('`', '', self::ORDEN_TABLE) . '` WHERE id = ?';
        $par = [$idOrden];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ? [
            'id' => (int) $r['id'],
            'NroPaci' => (int) $r['NroPaci'],
            'iddoctor' => (int) ($r['iddoctor'] ?? 0),
        ] : null;
    }

    /**
     * @param array<string, string|int> $f
     * @return list<array<string, mixed>>
     */
    public function listForIndex(array $f): array
    {
        $joinPac = db_table_exists($this->pdo, 'pacientes');
        $hasApellido = $joinPac && db_table_has_column($this->pdo, 'pacientes', 'apellido');
        $selPac = $joinPac
            ? ($hasApellido
                ? ', TRIM(CONCAT(COALESCE(p.apellido, \'\'), \' \', COALESCE(p.Nombres, \'\'))) AS paciente_etiqueta'
                : ', TRIM(COALESCE(p.Nombres, \'\')) AS paciente_etiqueta')
            : ', NULL AS paciente_etiqueta';

        $joinDoc = 'd.id = s.iddoctor';
        if ($this->sesionesTieneClinica() && db_table_has_column($this->pdo, 'lista_doctores', 'id_clinica')) {
            $joinDoc .= ' AND d.id_clinica = s.id_clinica';
        }
        $joinOrd = 'o.id = s.idorden';
        if ($this->sesionesTieneClinica() && $this->ordenesTieneClinica()) {
            $joinOrd .= ' AND o.id_clinica = s.id_clinica';
        }
        $sql = 'SELECT s.*, d.nombre AS doctor_nombre,
            o.numero AS orden_numero' . $selPac . '
            FROM ' . self::TABLE . ' s
            LEFT JOIN lista_doctores d ON ' . $joinDoc . '
            LEFT JOIN `Pacientes Ordenes` o ON ' . $joinOrd;
        if ($joinPac) {
            $joinP = 'p.NroHC = s.NroPaci';
            if ($this->sesionesTieneClinica() && db_table_has_column($this->pdo, 'pacientes', 'id_clinica')) {
                $joinP .= ' AND p.id_clinica = s.id_clinica';
            }
            $sql .= ' LEFT JOIN pacientes p ON ' . $joinP;
        }
        $sql .= ' WHERE 1=1';
        $params = [];
        if ($this->sesionesTieneClinica()) {
            $sql .= ' AND s.id_clinica = ?';
            $params[] = $this->idClinica;
        }

        $nro = (int) ($f['nrohc'] ?? 0);
        if ($nro > 0) {
            $sql .= ' AND s.NroPaci = ?';
            $params[] = $nro;
        }
        $idOrd = (int) ($f['idorden'] ?? 0);
        if ($idOrd > 0) {
            $sql .= ' AND s.idorden = ?';
            $params[] = $idOrd;
        }
        $doc = (int) ($f['doctor'] ?? 0);
        if ($doc > 0) {
            $sql .= ' AND s.iddoctor = ?';
            $params[] = $doc;
        }
        $fd = trim((string) ($f['fecha_desde'] ?? ''));
        if ($fd !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fd)) {
            $sql .= ' AND s.fecha_sesion >= ?';
            $params[] = $fd;
        }
        $fh = trim((string) ($f['fecha_hasta'] ?? ''));
        if ($fh !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fh)) {
            $sql .= ' AND s.fecha_sesion <= ?';
            $params[] = $fh;
        }

        $pacTxt = trim((string) ($f['paciente'] ?? ''));
        if ($pacTxt !== '' && $joinPac) {
            $like = '%' . $pacTxt . '%';
            if (ctype_digit($pacTxt)) {
                $sql .= ' AND (s.NroPaci = ? OR p.Nombres LIKE ? OR p.DNI LIKE ?';
                $params[] = (int) $pacTxt;
                $params[] = $like;
                $params[] = $like;
                if ($hasApellido) {
                    $sql .= ' OR p.apellido LIKE ?';
                    $params[] = $like;
                }
                $sql .= ')';
            } else {
                $sql .= ' AND (p.Nombres LIKE ? OR p.DNI LIKE ?';
                $params[] = $like;
                $params[] = $like;
                if ($hasApellido) {
                    $sql .= ' OR p.apellido LIKE ?';
                    $params[] = $like;
                }
                $sql .= ')';
            }
        }

        $sql .= ' ORDER BY s.fecha_sesion IS NULL, s.fecha_sesion DESC, s.id DESC LIMIT 1000';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->sesionesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $sql .= ' LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
        $r = $st->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    public function insertRow(array $values): int
    {
        if ($this->sesionesTieneClinica()) {
            $sql = 'INSERT INTO ' . self::TABLE . ' (id_clinica, idorden, NroPaci, iddoctor, fecha_sesion, cantidad_sesiones, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)';
            $st = $this->pdo->prepare($sql);
            $st->execute([
                $this->idClinica,
                $values['idorden'],
                $values['NroPaci'],
                $values['iddoctor'],
                $values['fecha_sesion'],
                $values['cantidad_sesiones'],
                $values['observaciones'],
            ]);

            return (int) $this->pdo->lastInsertId();
        }
        $sql = 'INSERT INTO ' . self::TABLE . ' (idorden, NroPaci, iddoctor, fecha_sesion, cantidad_sesiones, observaciones)
                VALUES (?, ?, ?, ?, ?, ?)';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $values['idorden'],
            $values['NroPaci'],
            $values['iddoctor'],
            $values['fecha_sesion'],
            $values['cantidad_sesiones'],
            $values['observaciones'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateRow(int $id, array $values): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET idorden = ?, NroPaci = ?, iddoctor = ?, fecha_sesion = ?, cantidad_sesiones = ?, observaciones = ? WHERE id = ?';
        $par = [
            $values['idorden'],
            $values['NroPaci'],
            $values['iddoctor'],
            $values['fecha_sesion'],
            $values['cantidad_sesiones'],
            $values['observaciones'],
            $id,
        ];
        if ($this->sesionesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id = ?';
        $par = [$id];
        if ($this->sesionesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }

    public function countByOrden(int $idOrden): int
    {
        if ($idOrden < 1 || !$this->tableExists()) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) c FROM ' . self::TABLE . ' WHERE idorden = ?';
        $par = [$idOrden];
        if ($this->sesionesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);

        return (int) ($st->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    }

    public function sumCantidadByOrden(int $idOrden): int
    {
        if ($idOrden < 1 || !$this->tableExists()) {
            return 0;
        }
        $sql = 'SELECT COALESCE(SUM(cantidad_sesiones), 0) t FROM ' . self::TABLE . ' WHERE idorden = ?';
        $par = [$idOrden];
        if ($this->sesionesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);

        return (int) ($st->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);
    }

    public function syncOrdenSesionesReali(int $idOrden): void
    {
        if ($idOrden < 1 || !db_table_exists($this->pdo, self::ORDEN_TABLE)) {
            return;
        }
        if (!db_table_has_column($this->pdo, self::ORDEN_TABLE, 'sesionesreali')) {
            return;
        }
        $total = $this->sumCantidadByOrden($idOrden);
        $sql = 'UPDATE `Pacientes Ordenes` SET sesionesreali = ? WHERE id = ?';
        $par = [$total, $idOrden];
        if ($this->ordenesTieneClinica()) {
            $sql .= ' AND id_clinica = ?';
            $par[] = $this->idClinica;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($par);
    }
}
