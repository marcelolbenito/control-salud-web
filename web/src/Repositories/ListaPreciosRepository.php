<?php

declare(strict_types=1);

final class ListaPreciosRepository
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function tablaDisponible(): bool
    {
        return $this->resolveTable() !== null;
    }

    /**
     * @return array{sql:string, name:string}|null
     */
    public function resolveTable(): ?array
    {
        if (db_table_exists($this->pdo, 'lista_precios')) {
            return ['sql' => '`lista_precios`', 'name' => 'lista_precios'];
        }
        if (db_table_exists($this->pdo, 'Lista Precios')) {
            return ['sql' => '`Lista Precios`', 'name' => 'Lista Precios'];
        }

        return null;
    }

    public function contarPorCobertura(int $idCobertura): int
    {
        $t = $this->resolveTable();
        if ($t === null || $idCobertura < 1) {
            return 0;
        }
        $st = $this->pdo->prepare('SELECT COUNT(*) AS c FROM ' . $t['sql'] . ' WHERE idobrasocial = ?');
        $st->execute([$idCobertura]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listar(int $idCobertura, ?string $q, int $limit, int $offset): array
    {
        $t = $this->resolveTable();
        if ($t === null || $idCobertura < 1) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $joinPractica = db_table_exists($this->pdo, 'lista_practicas')
            ? ' LEFT JOIN `lista_practicas` pr ON pr.id = lp.idpractica '
            : '';
        $joinPlan = db_table_exists($this->pdo, 'lista_planes')
            ? ' LEFT JOIN `lista_planes` pl ON pl.id = lp.idplan AND lp.idplan > 0 '
            : '';

        $sql = 'SELECT lp.*'
            . ($joinPractica !== '' ? ', pr.nombre AS practica_nombre' : '')
            . ($joinPlan !== '' ? ', pl.nombre AS plan_nombre' : '')
            . ' FROM ' . $t['sql'] . ' lp'
            . $joinPractica
            . $joinPlan
            . ' WHERE lp.idobrasocial = ?';

        $params = [$idCobertura];
        $q = $q !== null ? trim($q) : '';
        if ($q !== '') {
            if (preg_match('/^\d+$/', $q)) {
                $sql .= ' AND (lp.idpractica = ?';
                $params[] = (int) $q;
                if ($joinPractica !== '') {
                    $sql .= ' OR pr.nombre LIKE ?';
                    $params[] = '%' . $q . '%';
                }
                $sql .= ')';
            } elseif ($joinPractica !== '') {
                $sql .= ' AND pr.nombre LIKE ?';
                $params[] = '%' . $q . '%';
            } else {
                $sql .= ' AND lp.idpractica LIKE ?';
                $params[] = '%' . $q . '%';
            }
        }

        $sql .= ' ORDER BY'
            . ($joinPractica !== '' ? ' pr.nombre IS NULL, pr.nombre,' : '')
            . ' lp.idpractica, lp.idplan, lp.id'
            . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function contarFiltrado(int $idCobertura, ?string $q): int
    {
        $t = $this->resolveTable();
        if ($t === null || $idCobertura < 1) {
            return 0;
        }

        $joinPractica = db_table_exists($this->pdo, 'lista_practicas')
            ? ' LEFT JOIN `lista_practicas` pr ON pr.id = lp.idpractica '
            : '';

        $sql = 'SELECT COUNT(*) AS c FROM ' . $t['sql'] . ' lp' . $joinPractica . ' WHERE lp.idobrasocial = ?';
        $params = [$idCobertura];
        $q = $q !== null ? trim($q) : '';
        if ($q !== '') {
            if (preg_match('/^\d+$/', $q)) {
                $sql .= ' AND (lp.idpractica = ?';
                $params[] = (int) $q;
                if ($joinPractica !== '') {
                    $sql .= ' OR pr.nombre LIKE ?';
                    $params[] = '%' . $q . '%';
                }
                $sql .= ')';
            } elseif ($joinPractica !== '') {
                $sql .= ' AND pr.nombre LIKE ?';
                $params[] = '%' . $q . '%';
            } else {
                $sql .= ' AND CAST(lp.idpractica AS CHAR) LIKE ?';
                $params[] = '%' . $q . '%';
            }
        }

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $t = $this->resolveTable();
        if ($t === null || $id < 1) {
            return null;
        }
        $st = $this->pdo->prepare('SELECT * FROM ' . $t['sql'] . ' WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPorCombinacion(int $idCobertura, int $idPractica, int $idPlan, ?int $excludeId = null): ?array
    {
        $t = $this->resolveTable();
        if ($t === null || $idCobertura < 1 || $idPractica < 1) {
            return null;
        }
        $sql = 'SELECT id FROM ' . $t['sql']
            . ' WHERE idobrasocial = ? AND idpractica = ? AND (idplan <=> ?)';
        $params = [$idCobertura, $idPractica, $idPlan > 0 ? $idPlan : 0];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' ORDER BY id LIMIT 1';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function nextId(): int
    {
        $t = $this->resolveTable();
        if ($t === null) {
            return 1;
        }
        $st = $this->pdo->query('SELECT COALESCE(MAX(id), 0) + 1 AS n FROM ' . $t['sql']);
        $row = $st ? $st->fetch(PDO::FETCH_ASSOC) : null;

        return (int) ($row['n'] ?? 1);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $t = $this->resolveTable();
        if ($t === null) {
            throw new RuntimeException('Tabla de precios no disponible.');
        }
        $id = $this->nextId();
        $cols = ['id', 'idobrasocial', 'idpractica', 'costopaciente', 'costocobertura', 'usarporcentaje', 'costoporcentaje', 'cobradr', 'idplan'];
        $vals = [
            $id,
            $data['idobrasocial'],
            $data['idpractica'],
            $data['costopaciente'],
            $data['costocobertura'],
            $data['usarporcentaje'] ?? 0,
            $data['costoporcentaje'] ?? 0,
            $data['cobradr'] ?? 0,
            $data['idplan'] ?? 0,
        ];
        $ph = implode(', ', array_fill(0, count($cols), '?'));
        $colSql = implode(', ', array_map(static function (string $c): string {
            return '`' . $c . '`';
        }, $cols));
        $sql = 'INSERT INTO ' . $t['sql'] . ' (' . $colSql . ') VALUES (' . $ph . ')';
        $st = $this->pdo->prepare($sql);
        $st->execute($vals);

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $t = $this->resolveTable();
        if ($t === null) {
            throw new RuntimeException('Tabla de precios no disponible.');
        }
        $sql = 'UPDATE ' . $t['sql'] . ' SET idobrasocial = ?, idpractica = ?, costopaciente = ?, costocobertura = ?, '
            . 'usarporcentaje = ?, costoporcentaje = ?, cobradr = ?, idplan = ? WHERE id = ?';
        $st = $this->pdo->prepare($sql);
        $st->execute([
            $data['idobrasocial'],
            $data['idpractica'],
            $data['costopaciente'],
            $data['costocobertura'],
            $data['usarporcentaje'] ?? 0,
            $data['costoporcentaje'] ?? 0,
            $data['cobradr'] ?? 0,
            $data['idplan'] ?? 0,
            $id,
        ]);
    }

    public function deleteById(int $id): void
    {
        $t = $this->resolveTable();
        if ($t === null) {
            throw new RuntimeException('Tabla de precios no disponible.');
        }
        $st = $this->pdo->prepare('DELETE FROM ' . $t['sql'] . ' WHERE id = ?');
        $st->execute([$id]);
    }
}
